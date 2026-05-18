<?php
// ============================================================
// VULA MARKET — Checkout Handler
// Delivery is Locker-to-Door (L2D) via TCG Locker.
// ============================================================
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/yoco.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/index.php');

$u = require_auth();
csrf_verify();

$product_id    = (int)($_POST['product_id']        ?? 0);
$street        = trim($_POST['street']             ?? '');
$suburb        = trim($_POST['suburb']             ?? '');
$city          = trim($_POST['city']               ?? '');
$province      = trim($_POST['province']           ?? '');
$postal_code   = trim($_POST['postal_code']        ?? '');
$shipping_cost = (float)($_POST['shipping_cost']   ?? 0);
$service_code  = trim($_POST['service_level_code'] ?? '');

if (!$product_id || !$street || !$city || !$province) {
    flash('error', 'Please enter a complete delivery address before proceeding.');
    redirect('/listings/view.php?id=' . $product_id);
}

$st = db()->prepare('SELECT * FROM products WHERE id=? AND is_active=1');
$st->execute([$product_id]);
$product = $st->fetch();

if (!$product) {
    flash('error', 'This listing is no longer available.');
    redirect('/index.php');
}

if ((int)$product['seller_id'] === $u['id']) {
    flash('error', "You can't buy your own listing.");
    redirect('/listings/view.php?id=' . $product_id);
}

$total = $product['price'] + $shipping_cost;
$delivery_address_str = implode(', ', array_filter([$street, $suburb, $city, $province, $postal_code]));

$db = db();
$db->beginTransaction();
try {
    $db->prepare('UPDATE products SET is_active=0 WHERE id=?')->execute([$product_id]);

    $db->prepare(
        'INSERT INTO orders
            (buyer_id, seller_id, product_id, amount, shipping_cost, total_amount,
             shipping_address, tcg_service_code, status)
         VALUES (?,?,?,?,?,?,?,?,?)'
    )->execute([
        $u['id'],
        $product['seller_id'],
        $product_id,
        $product['price'],
        $shipping_cost,
        $total,
        $delivery_address_str,
        $service_code ?: null,
        'pending',
    ]);
    $order_id = (int)$db->lastInsertId();
    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    flash('error', 'Could not create order. Please try again.');
    redirect('/listings/view.php?id=' . $product_id);
}

$idempotency = bin2hex(random_bytes(16));
try {
    $yoco = yoco_create_checkout($total, $order_id, $idempotency);
    db()->prepare('UPDATE orders SET yoco_checkout_id=? WHERE id=?')
        ->execute([$yoco['checkout_id'], $order_id]);
    header('Location: ' . $yoco['redirect_url']);
    exit;
} catch (Throwable $e) {
    db()->prepare("UPDATE orders SET status='cancelled' WHERE id=?")->execute([$order_id]);
    db()->prepare('UPDATE products SET is_active=1 WHERE id=?')->execute([$product_id]);
    flash('error', 'Payment gateway error: ' . $e->getMessage());
    redirect('/listings/view.php?id=' . $product_id);
}

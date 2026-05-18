<?php
// ============================================================
// Yoco redirects here after successful payment.
// 1. Verify payment with Yoco API
// 2. Mark order as paid_in_escrow
// 3. Auto-create L2D shipment on TCG Locker API
//    Seller drops at their locker → TCG delivers to buyer's door.
// ============================================================
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/yoco.php';
require_once __DIR__ . '/../includes/shipping.php';

$order_id = (int)($_GET['order_id'] ?? 0);
if (!$order_id) redirect('/index.php');

$u = require_auth();

$st = db()->prepare(
    'SELECT o.*, u.name AS buyer_name, u.email AS buyer_email
     FROM orders o
     JOIN users u ON u.id = o.buyer_id
     WHERE o.id = ? AND o.buyer_id = ?'
);
$st->execute([$order_id, $u['id']]);
$order = $st->fetch();

if (!$order) redirect('/orders/index.php');

if (in_array($order['status'], ['paid_in_escrow', 'completed'])) {
    flash('success', 'Your order is confirmed!');
    redirect('/orders/view.php?id=' . $order_id);
}

// ── Step 1: Verify payment ────────────────────────────────────
$yoco_status = yoco_checkout_status($order['yoco_checkout_id'] ?? '');

if ($yoco_status !== 'completed') {
    db()->prepare("UPDATE orders SET status='failed', updated_at=datetime('now') WHERE id=?")
        ->execute([$order_id]);
    db()->prepare('UPDATE products SET is_active=1 WHERE id=?')
        ->execute([$order['product_id']]);
    flash('error', 'Payment was not completed. Your order has been cancelled.');
    redirect('/listings/view.php?id=' . $order['product_id']);
}

// ── Step 2: Mark paid_in_escrow ───────────────────────────────
db()->prepare("UPDATE orders SET status='paid_in_escrow', updated_at=datetime('now') WHERE id=?")
    ->execute([$order_id]);

// ── Step 3: Notify — seller will book shipment from their order page ──
db()->prepare('INSERT INTO order_messages (order_id,user_id,message) VALUES (?,?,?)')
    ->execute([$order_id, $u['id'],
        "✅ Payment confirmed and held safely in escrow.\n" .
        "📦 The seller has been notified to book a TCG Locker collection and deposit the parcel."
    ]);

flash('success', 'Payment successful! Funds are held in escrow until you confirm delivery.');

redirect('/orders/view.php?id=' . $order_id);

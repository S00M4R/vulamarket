<?php
// Payment cancelled by user
require_once __DIR__ . '/../includes/helpers.php';

$order_id = (int)($_GET['order_id'] ?? 0);
if ($order_id) {
    $st = db()->prepare('SELECT product_id FROM orders WHERE id=?');
    $st->execute([$order_id]);
    $order = $st->fetch();
    if ($order) {
        db()->prepare("UPDATE orders SET status='cancelled', updated_at=datetime('now') WHERE id=?")
            ->execute([$order_id]);
        db()->prepare('UPDATE products SET is_active=1 WHERE id=?')
            ->execute([$order['product_id']]);
        flash('info', 'Payment cancelled. Your order has been cancelled.');
        redirect('/listings/view.php?id=' . $order['product_id']);
    }
}
flash('info', 'Payment cancelled.');
redirect('/index.php');

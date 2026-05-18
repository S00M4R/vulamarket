<?php
require_once __DIR__ . '/../includes/helpers.php';

$order_id = (int)($_GET['order_id'] ?? 0);
if ($order_id) {
    $st = db()->prepare('SELECT product_id FROM orders WHERE id=?');
    $st->execute([$order_id]);
    $order = $st->fetch();
    if ($order) {
        db()->prepare("UPDATE orders SET status='failed', updated_at=datetime('now') WHERE id=?")
            ->execute([$order_id]);
        db()->prepare('UPDATE products SET is_active=1 WHERE id=?')
            ->execute([$order['product_id']]);
    }
}
flash('error', 'Payment failed. Please check your card details and try again.');
redirect('/index.php');

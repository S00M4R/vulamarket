<?php
require_once __DIR__ . '/../includes/helpers.php';

$u  = require_auth();
$id = (int)($_GET['id'] ?? 0);

$st = db()->prepare('SELECT * FROM products WHERE id=? AND seller_id=?');
$st->execute([$id, $u['id']]);
$product = $st->fetch();

if (!$product) {
    flash('error', 'Listing not found.');
    redirect('/index.php');
}

// Soft delete — mark inactive
db()->prepare('UPDATE products SET is_active=0 WHERE id=?')->execute([$id]);

// Remove image file
$path = UPLOAD_DIR . $product['image_path'];
if (is_file($path)) @unlink($path);

flash('success', 'Listing removed.');
redirect('/index.php');

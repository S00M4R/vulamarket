<?php
// ============================================================
// API: Get TCG Locker L2D rate
// GET ?street=...&suburb=...&city=...&province=WC&code=8001&box_size=S&product_id=5
// ============================================================
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/shipping.php';

header('Content-Type: application/json');

$street     = trim($_GET['street']     ?? '');
$suburb     = trim($_GET['suburb']     ?? '');
$city       = trim($_GET['city']       ?? '');
$province   = trim($_GET['province']   ?? '');
$code       = trim($_GET['code']       ?? '');
$product_id = (int)($_GET['product_id'] ?? 0);

if (!$street || !$city || !$province) {
    echo json_encode(['error' => 'Please enter a complete delivery address (street, city, province).']);
    exit;
}

// Get seller's locker terminal and product's box_size
$terminal_id = SELLER_LOCKER_TERMINAL;
$box_size    = 'S';
if ($product_id) {
    $st = db()->prepare('SELECT u.locker_terminal, p.box_size FROM products p JOIN users u ON u.id = p.seller_id WHERE p.id = ?');
    $st->execute([$product_id]);
    $row = $st->fetch();
    if ($row) {
        if ($row['locker_terminal']) $terminal_id = $row['locker_terminal'];
        if ($row['box_size'])        $box_size    = $row['box_size'];
    }
}

try {
    $delivery_address = build_delivery_address($street, $suburb, $city, $province, $code);
    $quote = tcg_get_rate($terminal_id, $delivery_address, $box_size);
    echo json_encode($quote);
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

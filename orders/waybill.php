<?php
// ============================================================
// Waybill PDF proxy — fetches the label from TCG Locker API
// server-side and streams it to the browser.
// Only the seller or buyer of the order can access this.
// ============================================================
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/shipping.php';

$u        = require_auth();
$order_id = (int)($_GET['order_id'] ?? 0);

$st = db()->prepare('SELECT * FROM orders WHERE id=? AND (buyer_id=? OR seller_id=?)');
$st->execute([$order_id, $u['id'], $u['id']]);
$order = $st->fetch();

if (!$order || !$order['tcg_shipment_id']) {
    flash('error', 'Waybill not available for this order.');
    redirect('/orders/view.php?id=' . $order_id);
}

// TCG Locker waybill endpoint: GET /generate/waybill/{id}?api_key=...
// The tracking ref is the custom_tracking_reference like "TCGD000501"
// We need to first resolve the numeric shipment ID from the tracking ref.
try {
    // Look up the numeric shipment ID via the tracking API
    $wref = urlencode($order['tcg_shipment_id']);
    $w_paths = [
        '/api/v1/tracking/shipments/public?waybill=' . $wref,
        '/tracking/shipments/public?waybill='        . $wref,
    ];
    $data = [];
    foreach ($w_paths as $wp) {
        try { $data = tcg_get($wp); break; } catch (Throwable $e) {
            if (!str_contains($e->getMessage(), '404')) throw $e;
        }
    }
    $shipment_id = $data['shipment_id'] ?? null;

    if (!$shipment_id) {
        throw new RuntimeException('Could not resolve shipment ID for waybill.');
    }

    // Fetch the PDF
    $pdf_url = tcg_get_waybill_url((int)$shipment_id);
    $ch = curl_init($pdf_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $pdf  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if ($code !== 200 || !$pdf) {
        throw new RuntimeException("Label fetch failed (HTTP $code).");
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="waybill-order-' . $order_id . '.pdf"');
    header('Content-Length: ' . strlen($pdf));
    header('Cache-Control: private, max-age=3600');
    echo $pdf;
    exit;

} catch (Throwable $e) {
    flash('error', 'Could not fetch waybill: ' . $e->getMessage());
    redirect('/orders/view.php?id=' . $order_id);
}

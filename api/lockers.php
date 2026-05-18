<?php
// ============================================================
// API: Get all TCG Lockers
// GET /api/lockers.php
// ============================================================
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/shipping.php';

header('Content-Type: application/json');

try {
    $lockers = tcg_get_lockers();

    // Sort alphabetically by name
    usort($lockers, fn($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));

    echo json_encode(array_values($lockers));

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

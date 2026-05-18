<?php
// ============================================================
// VULA MARKET — TCG Locker (PUDO) Integration
// All shipments use Locker-to-Door (L2D):
//   Seller drops parcel at their chosen locker → TCG delivers to buyer's door.
// ============================================================
require_once __DIR__ . '/../config/config.php';

// ── Internal cURL helpers ────────────────────────────────────

function tcg_post(string $path, array $payload): array {
    $ch = curl_init(TCG_API_BASE . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . TCG_API_KEY,
        ],
        CURLOPT_TIMEOUT => 15,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false) throw new RuntimeException('TCG API: cURL connection failed.');
    $data = json_decode($body, true);
    if ($code >= 400) {
        $msg = $data['message'] ?? $data['error'] ?? $body;
        throw new RuntimeException("TCG API error (HTTP $code): $msg");
    }
    return $data ?? [];
}

function tcg_get(string $path): array {
    $url = TCG_API_BASE . $path;
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Authorization: Bearer ' . TCG_API_KEY,
        ],
        CURLOPT_TIMEOUT => 15,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false) throw new RuntimeException('TCG API: cURL connection failed to ' . $url);
    $data = json_decode($body, true);
    if ($code >= 400) {
        $msg = $data['message'] ?? $data['error'] ?? substr($body, 0, 200);
        throw new RuntimeException("TCG API error (HTTP $code) at $url: $msg");
    }
    return $data ?? [];
}

// ── Lockers ──────────────────────────────────────────────────

/**
 * Fetch all available TCG lockers.
 * Returns array of locker objects: code, name, address, latitude, longitude, openinghours, lstTypesBoxes
 */
function tcg_get_lockers(): array {
    $paths = [
        '/api/v1/lockers',
        '/api/v1/lockers-data',
        '/lockers',
        '/lockers-data',
    ];

    $lastError = null;
    foreach ($paths as $path) {
        try {
            $data = tcg_get($path);
            if (isset($data['data']))    return $data['data'];
            if (isset($data['lockers'])) return $data['lockers'];
            if (is_array($data) && !empty($data)) return $data;
        } catch (Throwable $e) {
            $lastError = $e;
            if (!str_contains($e->getMessage(), '404')) throw $e;
        }
    }

    throw new RuntimeException(
        'Could not find lockers endpoint. Tried: ' . implode(', ', $paths) .
        '. Last error: ' . ($lastError ? $lastError->getMessage() : 'unknown')
    );
}

// ── Rates ────────────────────────────────────────────────────

/**
 * Get Locker-to-Door (L2D) shipping rate.
 *
 * @param  string $terminal_id       Seller's drop-off locker terminal (e.g. "CG10")
 * @param  array  $delivery_address  TCG-formatted delivery address (buyer's home)
 * @return array  ['rate', 'service', 'service_level_code', 'delivery_date_from', 'delivery_date_to', 'box_type']
 */
function tcg_get_rate(string $terminal_id, array $delivery_address, string $box_size = 'S'): array {
    $payload = [
        'collection_address'      => ['terminal_id' => $terminal_id],
        'delivery_address'        => $delivery_address,
        'opt_in_rates'            => [],
        'opt_in_time_based_rates' => [],
        'box_type'                => $box_size,
    ];

    $paths = ['/api/v1/rates', '/rates'];
    $body  = null;
    foreach ($paths as $p) {
        try { $body = tcg_post($p, $payload); break; } catch (Throwable $e) {
            if (!str_contains($e->getMessage(), '404')) throw $e;
        }
    }
    if ($body === null) throw new RuntimeException('Could not find rates endpoint.');

    $rates = $body['rates'] ?? [];
    if (empty($rates)) {
        throw new RuntimeException('No shipping rates returned for this address.');
    }

    // Filter to rates matching the requested box size.
    // The service_level code follows the pattern L2DXS, L2DS, L2DM, L2DL, L2DXL.
    // box_type_name typically contains "Extra Small", "Small", "Medium", "Large", "Extra Large".
    $size_keywords = [
        'XS' => ['XS', 'extra small', 'extrasmall'],
        'S'  => ['L2DS',  '- S ',  ' S -',  'small'],
        'M'  => ['L2DM',  '- M ',  ' M -',  'medium'],
        'L'  => ['L2DL',  '- L ',  ' L -',  'large'],
        'XL' => ['XL', 'extra large', 'extralarge'],
    ];
    $keywords = $size_keywords[$box_size] ?? [];

    $matched = array_filter($rates, function($r) use ($keywords) {
        $sl_code = strtolower($r['service_level']['code']      ?? '');
        $sl_name = strtolower($r['service_level']['name']      ?? '');
        $bt_name = strtolower($r['service_level']['box_type_name'] ?? '');
        $haystack = $sl_code . ' ' . $sl_name . ' ' . $bt_name;
        foreach ($keywords as $kw) {
            if (str_contains($haystack, strtolower($kw))) return true;
        }
        return false;
    });

    // Fall back to all rates if no size-matched rate found
    $pool = !empty($matched) ? array_values($matched) : $rates;
    usort($pool, fn($a, $b) => (float)$a['rate'] <=> (float)$b['rate']);
    $cheapest = $pool[0];
    $sl       = $cheapest['service_level'] ?? [];

    return [
        'rate'               => (float)$cheapest['rate'],
        'service'            => $sl['name'] ?? 'Locker to Door',
        'service_level_code' => $sl['code'] ?? '',
        'collection_date'    => $sl['collection_date']    ?? null,
        'delivery_date_from' => $sl['delivery_date_from'] ?? null,
        'delivery_date_to'   => $sl['delivery_date_to']   ?? null,
        'box_type'           => $sl['box_type_name']      ?? null,
    ];
}

// ── Shipments ────────────────────────────────────────────────

/**
 * Create a Locker-to-Door (L2D) shipment on TCG.
 * Seller drops the parcel at their chosen locker; TCG delivers to buyer's door.
 *
 * @param  array  $order            Order row from DB
 * @param  array  $seller           Seller user row (for contact info)
 * @param  string $terminal_id      The locker terminal the seller will drop off at
 * @param  array  $delivery_address TCG-formatted buyer delivery address
 * @param  string $service_code     Service level code from tcg_get_rate()
 * @param  string $box_size         Box size code (XS/S/M/L/XL)
 * @return array  [
 *   'tracking_ref'     => string,   // waybill / custom tracking reference
 *   'raw_id'           => int|null, // numeric shipment ID (for waybill PDF)
 *   'collection_code'  => string|null, // PIN/code seller enters at the locker
 * ]
 */
function tcg_create_shipment(array $order, array $seller, string $terminal_id, array $delivery_address, string $service_code, string $box_size = 'S'): array {
    $now = date('Y-m-d\TH:i:s.000\Z');

    $payload = [
        'collection_address'      => ['terminal_id' => $terminal_id],
        'collection_contact'      => [
            'name'          => $seller['name'],
            'email'         => $seller['email'],
            'mobile_number' => $seller['phone'] ?? '+27000000000',
        ],
        'delivery_address'        => $delivery_address,
        'delivery_contact'        => [
            'name'          => $order['buyer_name'],
            'email'         => $order['buyer_email'],
            'mobile_number' => $order['buyer_phone'] ?? '+27000000000',
        ],
        'opt_in_rates'            => [],
        'opt_in_time_based_rates' => [],
        'service_level_code'      => $service_code,
        'box_type'                => $box_size,
        'collection_min_date'     => $now,
        'delivery_min_date'       => $now,
    ];

    $paths = ['/api/v1/shipments', '/shipments'];
    $body  = null;
    foreach ($paths as $p) {
        try { $body = tcg_post($p, $payload); break; } catch (Throwable $e) {
            if (!str_contains($e->getMessage(), '404')) throw $e;
        }
    }
    if ($body === null) throw new RuntimeException('Could not find shipments endpoint.');

    $raw_id = isset($body['id']) ? (int)$body['id'] : null;
    $ref    = $body['custom_tracking_reference']
           ?? ($raw_id ? 'TCGD' . $raw_id : null);
    if (!$ref) throw new RuntimeException('TCG Locker did not return a tracking reference.');

    // The deposit/collection PIN — field names vary across API versions
    $collection_code = $body['collection_code']
                    ?? $body['locker_code']
                    ?? $body['pin']
                    ?? $body['access_code']
                    ?? $body['collection_pin']
                    ?? $body['drop_off_code']
                    ?? null;

    // Also check inside nested objects the API sometimes returns
    if (!$collection_code && isset($body['collection'])) {
        $col = $body['collection'];
        $collection_code = $col['code'] ?? $col['pin'] ?? $col['access_code'] ?? null;
    }

    return [
        'tracking_ref'    => (string)$ref,
        'raw_id'          => $raw_id,
        'collection_code' => $collection_code ? (string)$collection_code : null,
        'raw_response'    => $body, // kept for debugging
    ];
}

// ── Tracking ─────────────────────────────────────────────────

function tcg_get_tracking(string $tracking_ref): array {
    $paths = [
        '/api/v1/tracking/shipments/public?waybill=' . urlencode($tracking_ref),
        '/tracking/shipments/public?waybill='        . urlencode($tracking_ref),
    ];
    $data = [];
    foreach ($paths as $p) {
        try { $data = tcg_get($p); break; } catch (Throwable $e) {
            if (!str_contains($e->getMessage(), '404')) throw $e;
        }
    }

    $events = [];
    foreach ($data['tracking_events'] ?? [] as $ev) {
        $events[] = [
            'timestamp'   => $ev['date']     ?? '',
            'description' => $ev['status']   ?? 'Update',
            'location'    => $ev['location'] ?? '',
        ];
    }
    usort($events, fn($a, $b) => strcmp($b['timestamp'], $a['timestamp']));

    return [
        'status'             => $data['status'] ?? ($events[0]['description'] ?? 'In transit'),
        'status_code'        => $data['status'] ?? '',
        'estimated_delivery' => null,
        'events'             => $events,
    ];
}

// ── Waybill PDF ──────────────────────────────────────────────

function tcg_get_waybill_url(int $shipment_id): string {
    return TCG_API_BASE . '/generate/waybill/' . $shipment_id
        . '?api_key=' . urlencode(TCG_API_KEY);
}

// ── Helpers ──────────────────────────────────────────────────

/**
 * Build a TCG-compatible delivery_address array from individual fields.
 * Used when constructing the buyer's delivery address for L2D rate quotes.
 */
function build_delivery_address(string $street, string $suburb, string $city, string $province, string $code): array {
    $zone_map = [
        'EC'=>'EC','FS'=>'FS','GP'=>'GP','KZN'=>'KZN',
        'LP'=>'LP','MP'=>'MP','NC'=>'NC','NW'=>'NW','WC'=>'WC',
    ];
    return [
        'type'            => 'residential',
        'street_address'  => $street,
        'local_area'      => $suburb ?: $street,
        'city'            => $city,
        'zone'            => $zone_map[$province] ?? $province,
        'country'         => 'South Africa',
        'code'            => $code,
        'entered_address' => implode(', ', array_filter([$street, $suburb, $city, $province, $code, 'South Africa'])),
    ];
}

/**
 * Get the seller's preferred drop-off locker terminal ID.
 * Falls back to the platform default (SELLER_LOCKER_TERMINAL) if not set.
 */
function get_seller_terminal(array $seller): string {
    return $seller['locker_terminal'] ?? SELLER_LOCKER_TERMINAL;
}

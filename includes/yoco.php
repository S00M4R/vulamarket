<?php
// ============================================================
// VULA MARKET — Yoco Checkout API Integration
// ============================================================
require_once __DIR__ . '/../config/config.php';

/**
 * Create a Yoco hosted checkout session.
 *
 * @param int    $amount_rands  Total amount in ZAR (will be converted to cents)
 * @param int    $order_id      Internal order ID for metadata/reconciliation
 * @param string $idempotency   Optional UUIDv4 to prevent duplicate charges
 * @return array  ['checkout_id' => string, 'redirect_url' => string]
 * @throws RuntimeException on failure
 */
function yoco_create_checkout(float $amount_rands, int $order_id, string $idempotency = ''): array {
    $amount_cents = (int)round($amount_rands * 100);

    $payload = [
        'amount'          => $amount_cents,
        'currency'        => 'ZAR',
        'successUrl'      => APP_URL . '/payment/success.php?order_id=' . $order_id,
        'cancelUrl'       => APP_URL . '/payment/cancel.php?order_id='  . $order_id,
        'failureUrl'      => APP_URL . '/payment/failure.php?order_id=' . $order_id,
        'externalId'      => (string)$order_id,
        'clientReferenceId' => 'order_' . $order_id,
        'metadata'        => [
            'order_id' => $order_id,
            'platform' => APP_NAME,
        ],
    ];

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . YOCO_SECRET_KEY,
    ];
    if ($idempotency) {
        $headers[] = 'Idempotency-Key: ' . $idempotency;
    }

    $ch = curl_init(YOCO_API_BASE . '/checkouts');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 15,
    ]);

    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $code !== 200) {
        throw new RuntimeException("Yoco API error (HTTP $code): $body");
    }

    $data = json_decode($body, true);
    if (empty($data['id']) || empty($data['redirectUrl'])) {
        throw new RuntimeException('Yoco returned unexpected response: ' . $body);
    }

    return [
        'checkout_id'  => $data['id'],
        'redirect_url' => $data['redirectUrl'],
    ];
}

/**
 * Verify a checkout session status directly from Yoco.
 * Used on the success landing page to confirm payment before updating DB.
 *
 * @param string $checkout_id  Yoco checkout ID
 * @return string  'completed' | 'created' | 'started' | 'processing'
 */
function yoco_checkout_status(string $checkout_id): string {
    $ch = curl_init(YOCO_API_BASE . '/checkouts/' . urlencode($checkout_id));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . YOCO_SECRET_KEY,
        ],
        CURLOPT_TIMEOUT => 10,
    ]);

    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $code !== 200) return 'unknown';

    $data = json_decode($body, true);
    return $data['status'] ?? 'unknown';
}

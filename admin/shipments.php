<?php
// ============================================================
// VULA MARKET — Admin: Shipments Dashboard
// Pulls all orders with a TCG waybill from local DB,
// then fetches live status from TCG Locker API for each one.
// ============================================================
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/shipping.php';

$u = require_admin();

// ── Fetch all orders that have a waybill booked ─────────────
$all_shipped = db()->query("
    SELECT o.id         AS order_id,
           o.tcg_shipment_id,
           o.tcg_service_code,
           o.status     AS order_status,
           o.amount,
           o.shipping_cost,
           o.shipping_address,
           o.created_at,
           o.updated_at,
           p.title      AS product_title,
           b.name       AS buyer_name,
           b.email      AS buyer_email,
           s.name       AS seller_name,
           s.email      AS seller_email
    FROM orders o
    JOIN products p ON p.id  = o.product_id
    JOIN users    b ON b.id  = o.buyer_id
    JOIN users    s ON s.id  = o.seller_id
    WHERE o.tcg_shipment_id IS NOT NULL
      AND o.tcg_shipment_id != ''
    ORDER BY o.created_at DESC
")->fetchAll();

// ── Count orders waiting for a shipment to be booked ────────
$unshipped_count = (int)db()->query("
    SELECT COUNT(*) FROM orders
    WHERE status = 'paid_in_escrow'
      AND (tcg_shipment_id IS NULL OR tcg_shipment_id = '')
")->fetchColumn();

// ── API key configured? ──────────────────────────────────────
$api_ready = (TCG_API_KEY !== 'YOUR_TCG_LOCKER_API_KEY');

// ── Fetch live statuses from TCG Locker API ──────────────────
// Cap at 20 live lookups per page load to avoid timeouts.
$live_status = [];
$api_error   = null;

if ($api_ready && !empty($all_shipped)) {
    $to_fetch = array_slice($all_shipped, 0, 20);
    foreach ($to_fetch as $row) {
        $ref = $row['tcg_shipment_id'];
        try {
            $aref = urlencode($ref);
            $a_paths = [
                '/api/v1/tracking/shipments/public?waybill=' . $aref,
                '/tracking/shipments/public?waybill='        . $aref,
            ];
            $data = [];
            foreach ($a_paths as $ap) {
                try { $data = tcg_get($ap); break; } catch (Throwable $e) {
                    if (!str_contains($e->getMessage(), '404')) throw $e;
                }
            }
            $events = $data['tracking_events'] ?? [];

            // Sort newest first
            usort($events, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
            $latest_event = $events[0]['status'] ?? null;

            $live_status[$ref] = [
                'status'       => $data['status'] ?? 'unknown',
                'status_label' => ucwords(str_replace('-', ' ', $data['status'] ?? 'Unknown')),
                'latest_event' => $latest_event,
                'eta'          => null,
                'event_count'  => count($events),
                'ok'           => true,
            ];
        } catch (Throwable $e) {
            $live_status[$ref] = ['ok' => false, 'error' => $e->getMessage()];
        }
    }
} elseif (!$api_ready) {
    $api_error = 'TCG Locker API key not configured. Add your key to config/config.php to see live statuses.';
}

// ── Status colour helper ─────────────────────────────────────
function tcg_badge(string $status): string {
    $s = strtolower($status);
    if (str_contains($s, 'delivered') || str_contains($s, 'complete'))
        return 'style="background:#d1fae5;color:#065f46;padding:.2rem .6rem;border-radius:99px;font-size:.78rem;font-weight:700"';
    if (str_contains($s, 'transit') || str_contains($s, 'hub'))
        return 'style="background:#dbeafe;color:#1e40af;padding:.2rem .6rem;border-radius:99px;font-size:.78rem;font-weight:700"';
    if (str_contains($s, 'collect') || str_contains($s, 'pickup') || str_contains($s, 'deposit') || str_contains($s, 'pending'))
        return 'style="background:#fef3c7;color:#92400e;padding:.2rem .6rem;border-radius:99px;font-size:.78rem;font-weight:700"';
    if (str_contains($s, 'out_for') || str_contains($s, 'delivery'))
        return 'style="background:#ffedd5;color:#9a3412;padding:.2rem .6rem;border-radius:99px;font-size:.78rem;font-weight:700"';
    if (str_contains($s, 'cancel'))
        return 'style="background:#f3f4f6;color:#374151;padding:.2rem .6rem;border-radius:99px;font-size:.78rem;font-weight:700"';
    if (str_contains($s, 'exception') || str_contains($s, 'fail') || str_contains($s, 'error'))
        return 'style="background:#fee2e2;color:#991b1b;padding:.2rem .6rem;border-radius:99px;font-size:.78rem;font-weight:700"';
    return 'style="background:#f3f4f6;color:#374151;padding:.2rem .6rem;border-radius:99px;font-size:.78rem;font-weight:700"';
}

layout_head('Shipments — Admin');
?>

<div class="page-header flex-between" style="flex-wrap:wrap;gap:.75rem">
    <div>
        <h1>🚚 Shipments</h1>
        <p>All parcels booked via TCG Locker API, with live tracking status.</p>
    </div>
    <a href="<?= APP_URL ?>/admin/index.php" class="btn btn-outline btn-sm">← Back to Admin</a>
</div>

<!-- Stats row -->
<?php
$total_shipped   = count($all_shipped);
$live_delivered  = count(array_filter($live_status, fn($s) => $s['ok'] && str_contains(strtolower($s['status']),'deliver')));
$live_in_transit = count(array_filter($live_status, fn($s) => $s['ok'] && str_contains(strtolower($s['status']),'transit')));
$live_exceptions = count(array_filter($live_status, fn($s) => $s['ok'] && (str_contains(strtolower($s['status']),'exception') || str_contains(strtolower($s['status']),'fail'))));
?>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;margin-bottom:2rem">
    <div class="card"><div class="card-body" style="text-align:center;padding:1.1rem">
        <div style="font-size:2rem;font-family:var(--font-display);font-weight:800"><?= $total_shipped ?></div>
        <div style="font-size:.8rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.04em">Total Shipped</div>
    </div></div>
    <div class="card"><div class="card-body" style="text-align:center;padding:1.1rem">
        <div style="font-size:2rem;font-family:var(--font-display);font-weight:800;color:var(--accent2-dark)"><?= $unshipped_count ?></div>
        <div style="font-size:.8rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.04em">Awaiting Booking</div>
    </div></div>
    <div class="card"><div class="card-body" style="text-align:center;padding:1.1rem">
        <div style="font-size:2rem;font-family:var(--font-display);font-weight:800;color:var(--blue)"><?= $live_in_transit ?></div>
        <div style="font-size:.8rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.04em">In Transit</div>
    </div></div>
    <div class="card"><div class="card-body" style="text-align:center;padding:1.1rem">
        <div style="font-size:2rem;font-family:var(--font-display);font-weight:800;color:var(--green)"><?= $live_delivered ?></div>
        <div style="font-size:.8rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.04em">Delivered</div>
    </div></div>
    <div class="card"><div class="card-body" style="text-align:center;padding:1.1rem">
        <div style="font-size:2rem;font-family:var(--font-display);font-weight:800;color:var(--accent)"><?= $live_exceptions ?></div>
        <div style="font-size:.8rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.04em">Exceptions</div>
    </div></div>
</div>

<?php if ($api_error): ?>
<div class="flash flash-error"><?= e($api_error) ?></div>
<?php endif; ?>

<?php if ($unshipped_count > 0): ?>
<div class="flash flash-info" style="margin-bottom:1.5rem">
    ⚠ <strong><?= $unshipped_count ?> paid order<?= $unshipped_count > 1 ? 's' : '' ?></strong>
    still awaiting shipment booking.
    <a href="<?= APP_URL ?>/admin/index.php?tab=orders" style="font-weight:700">View orders →</a>
</div>
<?php endif; ?>

<?php if (empty($all_shipped)): ?>
<div class="card">
    <div class="card-body empty-state">
        <h3>No shipments booked yet</h3>
        <p>Shipments are automatically created when buyers complete payment.<br>
           They will appear here once the TCG Locker API is configured and active.</p>
        <?php if (!$api_ready): ?>
        <div class="mt-2" style="background:#fef3c7;border-radius:8px;padding:1rem;font-size:.9rem;color:#92400e">
            <strong>Action required:</strong> Add your TCG Locker API key to
            <code>config/config.php</code> → <code>TCG_API_KEY</code>.<br>
            Register at <a href="https://sandbox.tcglocker.co.za" target="_blank">sandbox.tcglocker.co.za</a>
            → Settings → API Keys.
        </div>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Waybill</th>
                    <th>Product</th>
                    <th>Buyer</th>
                    <th>Seller</th>
                    <th>Locker</th>
                    <th>TCG Live Status</th>
                    <th>Order Status</th>
                    <th>Booked</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($all_shipped as $row):
                $ref = $row['tcg_shipment_id'];
                $live = $live_status[$ref] ?? null;

                // Parse "Locker Name [CG10]" from shipping_address
                preg_match('/^(.*?)\s*\[([^\]]+)\]$/', $row['shipping_address'], $lm);
                $locker_label    = $lm[1] ?? $row['shipping_address'];
                $locker_terminal = $lm[2] ?? '';
            ?>
            <tr>
                <td>
                    <a href="<?= APP_URL ?>/orders/view.php?id=<?= $row['order_id'] ?>"
                       style="font-family:var(--font-display);font-weight:700">#<?= $row['order_id'] ?></a>
                </td>
                <td>
                    <span style="font-family:var(--font-display);font-weight:700;letter-spacing:.04em;font-size:.92rem">
                        <?= e($ref) ?>
                    </span>
                </td>
                <td style="max-width:160px">
                    <span title="<?= e($row['product_title']) ?>">
                        <?= e(mb_strimwidth($row['product_title'], 0, 26, '…')) ?>
                    </span>
                </td>
                <td>
                    <div style="font-weight:600;font-size:.9rem"><?= e($row['buyer_name']) ?></div>
                    <div style="font-size:.78rem;color:var(--text-muted)"><?= e($row['buyer_email']) ?></div>
                </td>
                <td>
                    <div style="font-weight:600;font-size:.9rem"><?= e($row['seller_name']) ?></div>
                    <div style="font-size:.78rem;color:var(--text-muted)"><?= e($row['seller_email']) ?></div>
                </td>
                <td style="font-size:.88rem">
                    <div style="font-weight:600">📦 <?= e($locker_label) ?></div>
                    <?php if ($locker_terminal): ?>
                    <div style="color:var(--text-muted);font-size:.78rem"><?= e($locker_terminal) ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!$api_ready): ?>
                        <span style="color:var(--text-muted);font-size:.82rem">API key not set</span>
                    <?php elseif (!$live): ?>
                        <span style="color:var(--text-muted);font-size:.82rem">Not fetched</span>
                    <?php elseif (!$live['ok']): ?>
                        <span style="background:#fee2e2;color:#991b1b;padding:.2rem .5rem;border-radius:6px;font-size:.78rem">
                            ⚠ <?= e(mb_strimwidth($live['error'], 0, 40, '…')) ?>
                        </span>
                    <?php else: ?>
                        <div>
                            <span <?= tcg_badge($live['status']) ?>>
                                <?= e($live['status_label']) ?>
                            </span>
                            <?php if ($live['latest_event']): ?>
                            <div style="font-size:.78rem;color:var(--text-muted);margin-top:.25rem;max-width:180px">
                                <?= e(mb_strimwidth(ucwords(str_replace('-',' ',$live['latest_event'])), 0, 55, '…')) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge badge-<?= e($row['order_status']) ?>">
                        <?= e(str_replace('_', ' ', $row['order_status'])) ?>
                    </span>
                </td>
                <td style="font-size:.82rem;color:var(--text-muted);white-space:nowrap">
                    <?= substr($row['created_at'], 0, 10) ?>
                </td>
                <td>
                    <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                        <a href="<?= APP_URL ?>/orders/track.php?order_id=<?= $row['order_id'] ?>"
                           class="btn btn-sm btn-primary" title="Track parcel">🔍</a>
                        <a href="<?= APP_URL ?>/orders/waybill.php?order_id=<?= $row['order_id'] ?>"
                           target="_blank"
                           class="btn btn-sm btn-amber" title="Download waybill PDF">🖨</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (count($all_shipped) >= 20 && $api_ready): ?>
    <div class="card-footer text-muted" style="font-size:.85rem">
        ℹ Live status shown for the 20 most recent shipments per page load to avoid API rate limits.
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<!-- API connection status card -->
<div class="card mt-3">
    <div class="card-body" style="display:flex;align-items:center;gap:1.25rem;flex-wrap:wrap">
        <div style="font-size:2rem"><?= $api_ready ? '🟢' : '🔴' ?></div>
        <div style="flex:1">
            <div style="font-family:var(--font-display);font-weight:700;margin-bottom:.2rem">
                TCG Locker API — <?= $api_ready ? 'Connected' : 'Not Configured' ?>
            </div>
            <div style="font-size:.88rem;color:var(--text-muted)">
                <?php if ($api_ready): ?>
                    API key is set. Shipments are automatically created on payment confirmation via
                    <a href="https://sandbox.api-pudo.co.za" target="_blank" rel="noopener">sandbox.api-pudo.co.za</a>.
                <?php else: ?>
                    Add your key to <code>config/config.php</code> → <code>define('TCG_API_KEY', 'your_key')</code>.
                    Register at <a href="https://sandbox.tcglocker.co.za" target="_blank">sandbox.tcglocker.co.za</a>
                    → Settings → API Keys.
                <?php endif; ?>
            </div>
        </div>
        <?php if ($api_ready): ?>
        <a href="<?= APP_URL ?>/admin/shipments.php" class="btn btn-outline btn-sm">↻ Refresh</a>
        <?php endif; ?>
    </div>
</div>

<?php layout_foot(); ?>

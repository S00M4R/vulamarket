<?php
// ============================================================
// Live parcel tracking — TCG Locker L2D (Locker to Door)
// ============================================================
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/shipping.php';

$u        = require_auth();
$order_id = (int)($_GET['order_id'] ?? 0);

$st = db()->prepare(
    'SELECT o.*, p.title AS product_title,
            b.name AS buyer_name, s.name AS seller_name
     FROM orders o
     JOIN products p ON p.id = o.product_id
     JOIN users b ON b.id = o.buyer_id
     JOIN users s ON s.id = o.seller_id
     WHERE o.id = ? AND (o.buyer_id=? OR o.seller_id=?)'
);
$st->execute([$order_id, $u['id'], $u['id']]);
$order = $st->fetch();

if (!$order) {
    flash('error', 'Order not found.');
    redirect('/orders/index.php');
}

$tracking_ref = $order['tcg_shipment_id'];
$tracking     = null;
$track_error  = null;

if ($tracking_ref && TCG_API_KEY !== 'YOUR_TCG_LOCKER_API_KEY') {
    try {
        $tracking = tcg_get_tracking($tracking_ref);
    } catch (Throwable $e) {
        $track_error = $e->getMessage();
    }
}

function status_style(string $code): array {
    $code = strtolower($code);
    return match(true) {
        str_contains($code, 'deposit') || str_contains($code, 'pending')
            => ['\u23f3 Pending Drop-off', 'var(--accent2-dark)'],
        str_contains($code, 'collect') || str_contains($code, 'pickup') || str_contains($code, 'locker')
            => ['\ud83d\udce6 Collected from Locker', 'var(--accent2-dark)'],
        str_contains($code, 'transit') || str_contains($code, 'hub')
            => ['\ud83d\ude9a In Transit', 'var(--blue)'],
        str_contains($code, 'out_for') || (str_contains($code, 'deliver') && !str_contains($code, 'delivered'))
            => ['\ud83c\udfc3 Out for Delivery', 'var(--accent)'],
        str_contains($code, 'delivered') || str_contains($code, 'complete')
            => ['\u2705 Delivered', 'var(--green)'],
        str_contains($code, 'cancel')
            => ['\u274c Cancelled', '#c0392b'],
        str_contains($code, 'exception') || str_contains($code, 'fail')
            => ['\u26a0 Exception', '#c0392b'],
        default
            => ['\ud83d\udccd ' . ucwords(str_replace('_', ' ', $code ?: 'Processing')), 'var(--text-muted)'],
    };
}

layout_head('Track Order #' . $order_id);
?>

<div style="max-width:760px;margin:0 auto">

    <div class="page-header flex-between" style="flex-wrap:wrap;gap:.75rem">
        <div>
            <h1>\ud83d\udce6 Track Parcel</h1>
            <p><?= e($order['product_title']) ?> &middot; Order #<?= $order_id ?></p>
        </div>
        <a href="<?= APP_URL ?>/orders/view.php?id=<?= $order_id ?>" class="btn btn-outline btn-sm">&larr; Back to Order</a>
    </div>

    <!-- Delivery address banner -->
    <div class="card mb-2" style="border-left:4px solid var(--accent)">
        <div class="card-body" style="padding:1rem 1.25rem;display:flex;gap:1rem;align-items:center;flex-wrap:wrap">
            <div style="font-size:1.8rem">\ud83c\udfe0</div>
            <div>
                <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted)">Delivering To</div>
                <div style="font-weight:800;font-family:var(--font-display)"><?= e($order['buyer_name']) ?></div>
                <div style="font-size:.85rem;color:var(--text-muted)"><?= e($order['shipping_address']) ?></div>
            </div>
        </div>
    </div>

    <?php if (!$tracking_ref): ?>
    <div class="card">
        <div class="card-body text-center" style="padding:3rem 2rem">
            <div style="font-size:3rem;margin-bottom:1rem">\ud83d\udd50</div>
            <h3 style="font-family:var(--font-display);font-size:1.3rem;margin-bottom:.5rem">Shipment Not Booked Yet</h3>
            <p class="text-muted">
                <?php if ($order['status'] === 'pending' || $order['status'] === 'paid_in_escrow'): ?>
                    The shipment will be automatically booked with TCG Locker once payment is confirmed.
                <?php else: ?>
                    No tracking information is available for this order.
                <?php endif; ?>
            </p>
        </div>
    </div>

    <?php elseif ($track_error): ?>
    <div class="card">
        <div class="card-body">
            <div class="flash flash-error">Could not fetch tracking: <?= e($track_error) ?></div>
            <p class="text-muted mt-2">You can also check tracking on the
                <a href="https://thecourierguy.co.za/tracking" target="_blank" rel="noopener">TCG website</a>
                using waybill: <strong><?= e($tracking_ref) ?></strong>
            </p>
        </div>
    </div>

    <?php else: ?>

    <?php
        $code = strtolower($tracking['status_code'] ?? $tracking['status'] ?? '');
        [$label, $color] = status_style($code);
    ?>
    <div class="card mb-2" style="border-left:5px solid <?= $color ?>">
        <div class="card-body" style="padding:1.5rem">
            <div style="display:grid;grid-template-columns:1fr auto;gap:1rem;align-items:center;flex-wrap:wrap">
                <div>
                    <div style="font-size:.8rem;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.3rem">Current Status</div>
                    <div style="font-size:1.5rem;font-family:var(--font-display);font-weight:800;color:<?= $color ?>">
                        <?= e($label) ?>
                    </div>
                </div>
                <div style="text-align:right">
                    <div style="font-size:.8rem;color:var(--text-muted);margin-bottom:.3rem">Waybill Number</div>
                    <div style="font-family:var(--font-display);font-weight:800;font-size:1.3rem;letter-spacing:.05em">
                        <?= e($tracking_ref) ?>
                    </div>
                    <a href="https://thecourierguy.co.za/tracking" target="_blank" rel="noopener"
                       style="font-size:.8rem;color:var(--accent)">Track on TCG site &nearr;</a>
                </div>
            </div>
        </div>

        <?php if ((int)$order['seller_id'] === $u['id']): ?>
        <div class="card-footer" style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap">
            <a href="<?= APP_URL ?>/orders/waybill.php?order_id=<?= $order_id ?>"
               target="_blank"
               class="btn btn-primary btn-sm">
                \ud83d\uddb8 Download Waybill PDF
            </a>
            <span class="text-muted" style="font-size:.85rem">Print and attach to the parcel before dropping it at your locker.</span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Progress Bar -->
    <?php
    $steps = [
        'Order Placed', 'Payment Confirmed', 'Dropped at Locker',
        'In Transit', 'Out for Delivery', 'Delivered',
    ];
    $active = 1;
    if (str_contains($code, 'deposit') || str_contains($code, 'submitted')) $active = 1;
    if (str_contains($code, 'collect') || str_contains($code, 'locker'))    $active = 2;
    if (str_contains($code, 'transit') || str_contains($code, 'hub'))       $active = 3;
    if (str_contains($code, 'out_for') || str_contains($code, 'delivery'))  $active = 4;
    if (str_contains($code, 'delivered') || str_contains($code, 'complete'))$active = 5;
    ?>
    <div class="card mb-2">
        <div class="card-body" style="padding:1.5rem">
            <div class="section-title" style="margin-bottom:1.25rem">Delivery Progress</div>
            <div style="display:flex;align-items:flex-start;gap:0;overflow-x:auto;padding-bottom:.5rem">
                <?php foreach ($steps as $i => $step_label): ?>
                <?php $done = $i <= $active; $current = $i === $active; ?>
                <div style="flex:1;min-width:70px;text-align:center;position:relative">
                    <?php if ($i > 0): ?>
                    <div style="position:absolute;top:14px;right:50%;left:-50%;height:3px;background:<?= $done ? 'var(--green)' : 'var(--border)' ?>;z-index:0"></div>
                    <?php endif; ?>
                    <div style="width:28px;height:28px;border-radius:50%;margin:0 auto;position:relative;z-index:1;
                        background:<?= $done ? 'var(--green)' : 'var(--border)' ?>;
                        border:3px solid <?= $done ? 'var(--green)' : 'var(--border)' ?>;
                        display:flex;align-items:center;justify-content:center;
                        box-shadow:<?= $current ? '0 0 0 4px rgba(45,122,79,.2)' : 'none' ?>">
                        <?php if ($done): ?>
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                            <path d="M2 7l3.5 3.5L12 3.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <?php else: ?>
                        <div style="width:8px;height:8px;border-radius:50%;background:#fff;opacity:.5"></div>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:.72rem;margin-top:.4rem;font-weight:<?= $current ? '700' : '400' ?>;color:<?= $done ? 'var(--text)' : 'var(--text-muted)' ?>">
                        <?= $step_label ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Event Timeline -->
    <?php if (!empty($tracking['events'])): ?>
    <div class="card">
        <div class="card-body">
            <div class="section-title">Tracking History</div>
            <div style="position:relative;padding-left:1.5rem">
                <div style="position:absolute;left:7px;top:6px;bottom:6px;width:2px;background:var(--border)"></div>
                <?php foreach ($tracking['events'] as $i => $ev): ?>
                <div style="position:relative;margin-bottom:1.25rem;display:flex;gap:1rem;align-items:flex-start">
                    <div style="position:absolute;left:-1.5rem;width:14px;height:14px;border-radius:50%;
                        background:<?= $i===0 ? 'var(--accent)' : 'var(--border)' ?>;
                        border:2px solid <?= $i===0 ? 'var(--accent)' : '#ccc' ?>;
                        margin-top:3px;flex-shrink:0"></div>
                    <div style="flex:1">
                        <div style="font-weight:<?= $i===0 ? '700' : '500' ?>;color:<?= $i===0 ? 'var(--text)' : 'var(--text-muted)' ?>">
                            <?= e(ucwords(str_replace('-', ' ', $ev['description']))) ?>
                        </div>
                        <div style="font-size:.82rem;color:var(--text-muted);margin-top:.2rem">
                            <?php if ($ev['location']): ?>\ud83d\udccd <?= e($ev['location']) ?> &middot; <?php endif; ?>
                            <?= $ev['timestamp'] ? date('d M Y, H:i', strtotime($ev['timestamp'])) : '' ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="card-body text-center" style="padding:2rem;color:var(--text-muted)">
            <p>No detailed tracking events yet. Check back once the seller has dropped the parcel at their locker.</p>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>

</div>
<?php layout_foot(); ?>

<?php
// ============================================================
// VULA MARKET — Admin Panel
// ============================================================
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

$u = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action    = $_POST['action'] ?? '';
    $payout_id = (int)($_POST['payout_id'] ?? 0);
    if ($action === 'mark_paid' && $payout_id) {
        db()->prepare("UPDATE payouts SET status='paid', paid_at=datetime('now') WHERE id=? AND status='pending'")
            ->execute([$payout_id]);
        flash('success', "Payout #$payout_id marked as paid.");
    }
    redirect('/admin/index.php');
}

$orders = db()->query("
    SELECT o.*, p.title AS product_title,
           b.name AS buyer_name, b.email AS buyer_email,
           s.name AS seller_name, s.email AS seller_email
    FROM orders o
    JOIN products p ON p.id = o.product_id
    JOIN users b ON b.id = o.buyer_id
    JOIN users s ON s.id = o.seller_id
    ORDER BY o.created_at DESC
")->fetchAll();

$payouts = db()->query("
    SELECT py.*, u.name AS seller_name, u.email AS seller_email
    FROM payouts py JOIN users u ON u.id = py.seller_id
    ORDER BY py.requested_at DESC
")->fetchAll();

$stats = db()->query("
    SELECT COUNT(*) AS total_orders,
      SUM(CASE WHEN status='paid_in_escrow' THEN total_amount ELSE 0 END) AS escrow_total,
      SUM(CASE WHEN status='completed'      THEN total_amount ELSE 0 END) AS completed_total
    FROM orders
")->fetch();

$pending_payouts = count(array_filter($payouts, fn($p) => $p['status'] === 'pending'));

layout_head('Admin Panel');
?>

<div class="flex-between page-header" style="flex-wrap:wrap;gap:.75rem">
    <h1>Admin Panel</h1>
    <div style="display:flex;gap:.6rem;flex-wrap:wrap">
        <a href="<?= APP_URL ?>/admin/shipments.php" class="btn btn-primary btn-sm">🚚 Shipments</a>
        <span class="admin-link" style="line-height:2rem">🔒 Admin Only</span>
    </div>
</div>

<!-- Stats Row -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:1rem;margin-bottom:2rem">
    <div class="card"><div class="card-body" style="text-align:center">
        <div class="text-muted" style="font-size:.85rem">Total Orders</div>
        <div style="font-family:var(--font-head);font-size:2rem;font-weight:800"><?= $stats['total_orders'] ?></div>
    </div></div>
    <div class="card"><div class="card-body" style="text-align:center">
        <div class="text-muted" style="font-size:.85rem">In Escrow</div>
        <div style="font-family:var(--font-head);font-size:2rem;font-weight:800;color:var(--green)"><?= fmt_money($stats['escrow_total'] ?? 0) ?></div>
    </div></div>
    <div class="card"><div class="card-body" style="text-align:center">
        <div class="text-muted" style="font-size:.85rem">Completed GMV</div>
        <div style="font-family:var(--font-head);font-size:2rem;font-weight:800;color:var(--gold)"><?= fmt_money($stats['completed_total'] ?? 0) ?></div>
    </div></div>
    <div class="card"><div class="card-body" style="text-align:center">
        <div class="text-muted" style="font-size:.85rem">Pending Payouts</div>
        <div style="font-family:var(--font-head);font-size:2rem;font-weight:800;color:var(--danger)"><?= $pending_payouts ?></div>
    </div></div>
</div>

<!-- Payout Requests -->
<h2 style="margin-bottom:1rem">⚡ Payout Requests</h2>
<?php if (empty($payouts)): ?>
    <div class="empty-state"><p>No payout requests yet.</p></div>
<?php else: ?>
    <div class="table-wrap mb-3">
        <table>
            <thead>
                <tr><th>#</th><th>Seller</th><th>Amount</th><th>Bank Details</th><th>Status</th><th>Requested</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach ($payouts as $p): ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td>
                        <strong><?= e($p['seller_name']) ?></strong><br>
                        <small class="text-muted"><?= e($p['seller_email']) ?></small>
                    </td>
                    <td><strong style="color:var(--green)"><?= fmt_money($p['amount']) ?></strong></td>
                    <td style="font-size:.82rem;white-space:pre-line"><?= e($p['bank_details']) ?></td>
                    <td><span class="badge badge-<?= e($p['status']) ?>"><?= e($p['status']) ?></span></td>
                    <td style="font-size:.82rem"><?= e(substr($p['requested_at'],0,10)) ?></td>
                    <td>
                        <?php if ($p['status'] === 'pending'): ?>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="mark_paid">
                                <input type="hidden" name="payout_id" value="<?= $p['id'] ?>">
                                <button class="btn btn-primary btn-sm"
                                    data-confirm="Confirm EFT sent for payout #<?= $p['id'] ?> of <?= fmt_money($p['amount']) ?>?">
                                    ✓ Mark Paid
                                </button>
                            </form>
                        <?php else: ?>
                            <small class="text-muted">Paid <?= e(substr($p['paid_at'],0,10)) ?></small>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- All Orders -->
<h2 style="margin-bottom:1rem">📦 All Orders</h2>
<?php if (empty($orders)): ?>
    <div class="empty-state"><p>No orders yet.</p></div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>#</th><th>Item</th><th>Buyer</th><th>Seller</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td><?= $o['id'] ?></td>
                    <td><?= e(mb_substr($o['product_title'],0,28)) ?></td>
                    <td><?= e($o['buyer_name']) ?><br><small class="text-muted"><?= e($o['buyer_email']) ?></small></td>
                    <td><?= e($o['seller_name']) ?><br><small class="text-muted"><?= e($o['seller_email']) ?></small></td>
                    <td><?= fmt_money($o['total_amount']) ?></td>
                    <td><span class="badge badge-<?= e($o['status']) ?>"><?= e(str_replace('_',' ',$o['status'])) ?></span></td>
                    <td style="font-size:.82rem"><?= e(substr($o['created_at'],0,10)) ?></td>
                    <td><a href="<?= APP_URL ?>/orders/view.php?id=<?= $o['id'] ?>" class="btn btn-outline btn-sm">View</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php layout_foot(); ?>

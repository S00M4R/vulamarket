<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

$u = require_auth();

$tab = $_GET['tab'] ?? 'buying';

if ($tab === 'selling') {
    $st = db()->prepare(
        'SELECT o.*, p.title AS product_title, u.name AS buyer_name
         FROM orders o
         JOIN products p ON p.id = o.product_id
         JOIN users u ON u.id = o.buyer_id
         WHERE o.seller_id = ?
         ORDER BY o.created_at DESC'
    );
} else {
    $st = db()->prepare(
        'SELECT o.*, p.title AS product_title, u.name AS seller_name
         FROM orders o
         JOIN products p ON p.id = o.product_id
         JOIN users u ON u.id = o.seller_id
         WHERE o.buyer_id = ?
         ORDER BY o.created_at DESC'
    );
}
$st->execute([$u['id']]);
$orders = $st->fetchAll();

layout_head('My Orders');
?>
<div class="page-header flex-between" style="flex-wrap:wrap;gap:.5rem">
    <div>
        <h1>My Orders</h1>
        <p>Track your purchases and sales.</p>
    </div>
    <div class="flex gap-1">
        <a href="?tab=buying"  class="btn btn-sm <?= $tab==='buying'  ? 'btn-primary' : 'btn-outline' ?>">Buying</a>
        <a href="?tab=selling" class="btn btn-sm <?= $tab==='selling' ? 'btn-primary' : 'btn-outline' ?>">Selling</a>
    </div>
</div>

<?php if (empty($orders)): ?>
    <div class="empty-state">
        <h3>No orders yet</h3>
        <?php if ($tab === 'buying'): ?>
            <p>Browse listings and make your first purchase.</p>
            <a href="<?= APP_URL ?>/index.php" class="btn btn-primary mt-2">Browse Listings</a>
        <?php else: ?>
            <p>You haven't sold anything yet. Post a listing!</p>
            <a href="<?= APP_URL ?>/listings/create.php" class="btn btn-primary mt-2">Post a Listing</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item</th>
                        <th><?= $tab==='buying' ? 'Seller' : 'Buyer' ?></th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                    <tr>
                        <td><?= $o['id'] ?></td>
                        <td><?= e($o['product_title']) ?></td>
                        <td><?= e($tab==='buying' ? $o['seller_name'] : $o['buyer_name']) ?></td>
                        <td><?= fmt_money($o['total_amount']) ?></td>
                        <td><span class="badge badge-<?= e($o['status']) ?>"><?= e(str_replace('_',' ',$o['status'])) ?></span></td>
                        <td style="font-size:.85rem;color:var(--text-muted)"><?= substr($o['created_at'],0,10) ?></td>
                        <td><a href="<?= APP_URL ?>/orders/view.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
<?php layout_foot(); ?>

<?php
require_once __DIR__ . '/../includes/config.php';
$page_title = 'My Orders';
$user = require_auth();

$buying = db()->prepare("
    SELECT o.*, p.title AS product_title, p.image_path, s.name AS seller_name
    FROM orders o
    JOIN products p ON p.id = o.product_id
    JOIN users s ON s.id = o.seller_id
    WHERE o.buyer_id = ?
    ORDER BY o.created_at DESC
");
$buying->execute([$user['id']]);
$buying_orders = $buying->fetchAll();

$selling = db()->prepare("
    SELECT o.*, p.title AS product_title, p.image_path, b.name AS buyer_name
    FROM orders o
    JOIN products p ON p.id = o.product_id
    JOIN users b ON b.id = o.buyer_id
    WHERE o.seller_id = ?
    ORDER BY o.created_at DESC
");
$selling->execute([$user['id']]);
$selling_orders = $selling->fetchAll();

include __DIR__ . '/../includes/header.php';

function render_orders(array $orders, array $user, string $role): void {
    if (empty($orders)): ?>
      <div class="empty-state"><p>No <?= $role ?> orders yet.</p></div>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Item</th>
              <th><?= $role === 'buying' ? 'Seller' : 'Buyer' ?></th>
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
              <td>
                <div class="flex">
                  <img src="<?= UPLOAD_URL . h($o['image_path']) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:6px;">
                  <?= h(mb_substr($o['product_title'], 0, 30)) ?>
                </div>
              </td>
              <td><?= h($role === 'buying' ? $o['seller_name'] : $o['buyer_name']) ?></td>
              <td><?= money($o['total_amount']) ?></td>
              <td><span class="status status-<?= h($o['status']) ?>"><?= h(str_replace('_', ' ', $o['status'])) ?></span></td>
              <td style="white-space:nowrap;font-size:0.82rem;"><?= h(substr($o['created_at'], 0, 10)) ?></td>
              <td><a href="order.php?id=<?= $o['id'] ?>" class="btn btn-outline btn-sm">View</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif;
}
?>

<h1 class="page-title">My Orders</h1>

<h2 style="margin-bottom:1rem;">Buying</h2>
<?php render_orders($buying_orders, $user, 'buying'); ?>

<h2 style="margin:2rem 0 1rem;">Selling</h2>
<?php render_orders($selling_orders, $user, 'selling'); ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

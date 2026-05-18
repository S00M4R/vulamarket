<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

session_boot();
$u = auth_user_cached();

$search   = trim($_GET['q'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;

$where  = 'p.is_active = 1';
$params = [];
if ($search) {
    $where   .= ' AND (p.title LIKE ? OR p.description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$total_st = db()->prepare("SELECT COUNT(*) FROM products p WHERE $where");
$total_st->execute($params);
$total = (int)$total_st->fetchColumn();
$pg    = paginate($total, $per_page, $page);

$st = db()->prepare(
    "SELECT p.*, u.name AS seller_name
     FROM products p JOIN users u ON u.id = p.seller_id
     WHERE $where ORDER BY p.created_at DESC LIMIT ? OFFSET ?"
);
$st->execute(array_merge($params, [$per_page, $pg['offset']]));
$products = $st->fetchAll();

layout_head('Browse Listings');
?>

<?php if (!$search): ?>
<section class="hero">
    <div class="hero-inner">
        <h1>Buy &amp; Sell<br><span>Anything.</span> Anywhere.</h1>
        <p>South Africa's simplest C2C marketplace. List in minutes, pay safely, ship with ease.</p>
        <div class="hero-actions">
            <?php if ($u): ?>
                <a href="<?= APP_URL ?>/listings/create.php" class="btn btn-amber btn-lg">+ Post a Listing</a>
            <?php else: ?>
                <a href="<?= APP_URL ?>/auth/register.php" class="btn btn-amber btn-lg">Get Started Free</a>
                <a href="<?= APP_URL ?>/auth/login.php" class="btn btn-outline btn-lg" style="color:#fff;border-color:rgba(255,255,255,.3)">Log In</a>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<div class="flex-between mb-2">
    <div class="section-title" style="margin:0">
        <?= $search ? 'Results for "' . e($search) . '"' : 'Latest Listings' ?>
        <span class="text-muted" style="font-size:.9rem;font-weight:400"> (<?= $total ?>)</span>
    </div>
    <form method="get" action="<?= APP_URL ?>/index.php" style="display:flex;gap:.5rem">
        <input class="form-control" style="width:220px" name="q" placeholder="Search listings…" value="<?= e($search) ?>">
        <button class="btn btn-primary btn-sm" type="submit">Search</button>
        <?php if ($search): ?><a href="<?= APP_URL ?>/index.php" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
    </form>
</div>

<?php if (empty($products)): ?>
    <div class="empty-state">
        <h3>No listings found</h3>
        <p>Be the first to list something!</p>
        <?php if ($u): ?><a href="<?= APP_URL ?>/listings/create.php" class="btn btn-primary mt-2">Post a Listing</a><?php endif; ?>
    </div>
<?php else: ?>
    <div class="product-grid">
        <?php foreach ($products as $p): ?>
        <a href="<?= APP_URL ?>/listings/view.php?id=<?= $p['id'] ?>" class="product-card" style="display:block;text-decoration:none;color:inherit">
            <img src="<?= APP_URL ?>/uploads/<?= e($p['image_path']) ?>" alt="<?= e($p['title']) ?>" loading="lazy">
            <div class="product-card-body">
                <div class="product-card-title"><?= e($p['title']) ?></div>
                <div class="product-card-price"><?= fmt_money($p['price']) ?></div>
                <div class="product-card-seller">by <?= e($p['seller_name']) ?></div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if ($pg['pages'] > 1): ?>
    <div class="flex mt-3" style="gap:.5rem;justify-content:center">
        <?php for ($i = 1; $i <= $pg['pages']; $i++): ?>
            <a href="<?= APP_URL ?>/index.php?page=<?= $i ?>&q=<?= urlencode($search) ?>"
               class="btn btn-sm <?= $i === $pg['current'] ? 'btn-primary' : 'btn-outline' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>

<?php layout_foot(); ?>

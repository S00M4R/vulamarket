<?php
// ============================================================
// VULA MARKET — Layout Partials
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers.php';

function layout_head(string $title = '', string $extra_css = ''): void {
    $page_title = $title ? e($title) . ' · ' . APP_NAME : APP_NAME;
    $u = auth_user_cached();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $page_title ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/public/css/app.css">
    <script>window.APP_BASE = '<?= APP_URL ?>';</script>
    <?= $extra_css ?>
</head>
<body>
<nav class="nav">
    <a href="<?= APP_URL ?>/index.php" class="nav-brand">
        <span class="nav-brand-vula">VULA</span><span class="nav-brand-market">MARKET</span>
    </a>
    <div class="nav-links">
        <a href="<?= APP_URL ?>/index.php">Browse</a>
        <?php if ($u): ?>
            <a href="<?= APP_URL ?>/listings/create.php">+ Sell</a>
            <a href="<?= APP_URL ?>/orders/index.php">Orders</a>
            <a href="<?= APP_URL ?>/wallet.php">Wallet</a>
            <a href="<?= APP_URL ?>/auth/profile.php">Profile</a>
            <?php if ($u['is_admin']): ?>
                <a href="<?= APP_URL ?>/admin/index.php" class="admin-link">Admin</a>
            <?php endif; ?>
            <a href="<?= APP_URL ?>/auth/logout.php" class="btn btn-outline btn-sm">Logout</a>
        <?php else: ?>
            <a href="<?= APP_URL ?>/auth/login.php">Login</a>
            <a href="<?= APP_URL ?>/auth/register.php" class="btn btn-primary btn-sm">Sign Up</a>
        <?php endif; ?>
    </div>
    <button class="nav-toggle" onclick="document.querySelector('.nav-links').classList.toggle('open')">☰</button>
</nav>
<main class="main-content">
<?php
    // Flash messages
    foreach (['success','error','info'] as $type) {
        $msg = flash_get($type);
        if ($msg): ?>
        <div class="flash flash-<?= $type ?>"><?= e($msg) ?> <button onclick="this.parentElement.remove()">×</button></div>
        <?php endif;
    }
}

function layout_foot(): void { ?>
</main>
<footer class="site-footer">
    <div class="footer-inner">
        <span>© <?= date('Y') ?> <?= APP_NAME ?> · Buy &amp; Sell with Confidence</span>
        <span>Powered by Yoco &amp; The Courier Guy</span>
    </div>
</footer>
<script src="<?= APP_URL ?>/public/js/app.js"></script>
</body>
</html>
<?php }

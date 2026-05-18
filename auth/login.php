<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

session_boot();
if (auth_user_cached()) redirect('/index.php');

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $st = db()->prepare('SELECT * FROM users WHERE email = ?');
    $st->execute([$email]);
    $user = $st->fetch();

    if ($user && password_verify($pass, $user['password'])) {
        login_user($user['id']);
        flash('success', 'Welcome back, ' . $user['name'] . '!');
        $next = $_GET['next'] ?? '/';
        redirect($next);
    } else {
        $error = 'Incorrect email or password.';
    }
}

layout_head('Log In');
?>
<div class="auth-wrap">
    <div class="card">
        <div class="card-body">
            <div class="auth-title">Log In</div>
            <?php if ($error): ?>
                <div class="flash flash-error"><?= e($error) ?></div>
            <?php endif; ?>
            <form method="post" action="<?= APP_URL ?>/auth/login.php">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input class="form-control" id="email" name="email" type="email"
                           value="<?= e($_POST['email'] ?? '') ?>" required autocomplete="email">
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" id="password" name="password" type="password"
                           required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg">Log In</button>
            </form>
            <hr class="divider">
            <p class="text-center text-muted" style="font-size:.9rem">
                No account yet? <a href="<?= APP_URL ?>/auth/register.php">Sign up free</a>
            </p>
        </div>
    </div>
</div>
<?php layout_foot(); ?>

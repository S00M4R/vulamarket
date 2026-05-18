<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

session_boot();
if (auth_user_cached()) redirect('/index.php');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name  = trim($_POST['name']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password']   ?? '';
    $pass2 = $_POST['password2']  ?? '';

    if (!$name  || strlen($name)  < 2)   $errors[] = 'Name must be at least 2 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email.';
    if (strlen($pass) < 8)               $errors[] = 'Password must be at least 8 characters.';
    if ($pass !== $pass2)                $errors[] = 'Passwords do not match.';

    if (!$errors) {
        try {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $st = db()->prepare('INSERT INTO users (name,email,password) VALUES (?,?,?)');
            $st->execute([$name, $email, $hash]);
            $id = (int)db()->lastInsertId();
            login_user($id);
            flash('success', 'Welcome to ' . APP_NAME . '!');
            redirect('/index.php');
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'UNIQUE')) {
                $errors[] = 'An account with that email already exists.';
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}

layout_head('Create Account');
?>
<div class="auth-wrap">
    <div class="card">
        <div class="card-body">
            <div class="auth-title">Create Account</div>
            <?php foreach ($errors as $e): ?>
                <div class="flash flash-error"><?= e($e) ?></div>
            <?php endforeach; ?>
            <form method="post" action="<?= APP_URL ?>/auth/register.php">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label" for="name">Full Name <span class="required">*</span></label>
                    <input class="form-control" id="name" name="name" type="text"
                           value="<?= e($_POST['name'] ?? '') ?>" required autocomplete="name">
                </div>
                <div class="form-group">
                    <label class="form-label" for="email">Email Address <span class="required">*</span></label>
                    <input class="form-control" id="email" name="email" type="email"
                           value="<?= e($_POST['email'] ?? '') ?>" required autocomplete="email">
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Password <span class="required">*</span></label>
                    <input class="form-control" id="password" name="password" type="password"
                           required autocomplete="new-password" minlength="8">
                    <div class="form-hint">Minimum 8 characters.</div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password2">Confirm Password <span class="required">*</span></label>
                    <input class="form-control" id="password2" name="password2" type="password"
                           required autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg">Create Account</button>
            </form>
            <hr class="divider">
            <p class="text-center text-muted" style="font-size:.9rem">
                Already have an account? <a href="<?= APP_URL ?>/auth/login.php">Log in</a>
            </p>
        </div>
    </div>
</div>
<?php layout_foot(); ?>

<?php
// ============================================================
// VULA MARKET — My Profile
// ============================================================
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/shipping.php';

$u = require_auth();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name            = trim($_POST['name']            ?? '');
    $phone           = trim($_POST['phone']           ?? '');
    $locker_terminal = trim($_POST['locker_terminal'] ?? '');

    if (strlen($name) < 2)  $errors[] = 'Name must be at least 2 characters.';
    if (!$phone)             $errors[] = 'Phone number is required so TCG can contact you about collections.';

    if (!$errors) {
        db()->prepare(
            'UPDATE users SET name=?, phone=?, locker_terminal=? WHERE id=?'
        )->execute([$name, $phone, $locker_terminal ?: null, $u['id']]);

        $_SESSION['user']['name'] = $name;
        flash('success', 'Profile saved.');
        redirect('/auth/profile.php');
    }
}

$user = db()->prepare('SELECT * FROM users WHERE id=?');
$user->execute([$u['id']]);
$user = $user->fetch();

// Determine if profile is incomplete (missing phone or locker)
$profile_incomplete = empty($user['phone']) || empty($user['locker_terminal']);

// Load lockers for picker
$lockers = [];
try {
    $lockers = tcg_get_lockers();
    usort($lockers, fn($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));
} catch (Throwable) {}

layout_head('My Profile');
?>

<div style="max-width:560px;margin:0 auto">

    <div class="page-header">
        <h1>👤 My Profile</h1>
        <p>Update your details and preferred drop-off locker for selling.</p>
    </div>

    <?php if ($profile_incomplete): ?>
    <div class="flash flash-info" style="display:flex;gap:.75rem;align-items:flex-start">
        <span style="font-size:1.4rem;line-height:1">⚠️</span>
        <div>
            <strong>Your profile is incomplete.</strong><br>
            <span style="font-size:.9rem">
                <?php if (empty($user['phone'])): ?>
                    A <strong>phone number</strong> is required so TCG can contact you about parcel collections.<br>
                <?php endif; ?>
                <?php if (empty($user['locker_terminal'])): ?>
                    Please select your <strong>preferred drop-off locker</strong> — this is where you'll deposit parcels when you make a sale.
                <?php endif; ?>
            </span>
        </div>
    </div>
    <?php endif; ?>

    <?php foreach ($errors as $err): ?>
        <div class="flash flash-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" action="<?= APP_URL ?>/auth/profile.php">
        <?= csrf_field() ?>

        <!-- Basic info -->
        <div class="card mb-2">
            <div class="card-body">
                <div class="section-title">Basic Info</div>

                <div class="form-group">
                    <label class="form-label" for="name">Full Name <span class="required">*</span></label>
                    <input class="form-control" id="name" name="name" type="text"
                           value="<?= e($_POST['name'] ?? $user['name']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input class="form-control" value="<?= e($user['email']) ?>" type="email" disabled>
                    <div class="form-hint">Email cannot be changed.</div>
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label" for="phone">
                        Phone Number <span class="required">*</span>
                    </label>
                    <input class="form-control <?= empty($user['phone']) ? 'input-highlight' : '' ?>"
                           id="phone" name="phone" type="tel"
                           placeholder="+27 82 000 0000" required
                           value="<?= e($_POST['phone'] ?? $user['phone'] ?? '') ?>">
                    <div class="form-hint">Required — TCG uses this to coordinate parcel collection with you.</div>
                </div>
            </div>
        </div>

        <!-- Drop-off locker -->
        <div class="card mb-2">
            <div class="card-body">
                <div class="section-title">📦 Preferred Drop-off Locker <span class="required">*</span></div>
                <p style="font-size:.88rem;margin-bottom:1rem;color:var(--text-muted)">
                    When you sell an item, you drop the parcel at this locker and TCG delivers it to the buyer's door.
                    <?php if (empty($user['locker_terminal'])): ?>
                    <strong style="color:var(--accent)">Please choose your nearest locker below.</strong>
                    <?php endif; ?>
                </p>

                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label" for="locker_terminal">Select your nearest locker</label>
                    <?php if (!empty($lockers)): ?>
                    <select class="form-control <?= empty($user['locker_terminal']) ? 'input-highlight' : '' ?>"
                            id="locker_terminal" name="locker_terminal">
                        <option value="">— Choose a locker —</option>
                        <?php
                        $saved = $_POST['locker_terminal'] ?? $user['locker_terminal'] ?? '';
                        foreach ($lockers as $lk):
                            $code  = $lk['code'] ?? '';
                            $sel   = $saved === $code ? 'selected' : '';
                            $label = ($lk['name'] ?? $code) . ($lk['address'] ? ' — ' . $lk['address'] : '');
                        ?>
                        <option value="<?= e($code) ?>" <?= $sel ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php else: ?>
                    <input class="form-control" id="locker_terminal" name="locker_terminal" type="text"
                           placeholder="Terminal code e.g. CG10"
                           value="<?= e($_POST['locker_terminal'] ?? $user['locker_terminal'] ?? '') ?>">
                    <div class="form-hint">Could not load locker list — enter the terminal code manually.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">💾 Save Profile</button>
    </form>

    <div class="mt-2 text-center">
        <a href="<?= APP_URL ?>/index.php" class="text-muted" style="font-size:.88rem">← Back to listings</a>
    </div>
</div>

<style>
.input-highlight { border-color: var(--accent) !important; background: #fff8f5; }
</style>

<?php layout_foot(); ?>

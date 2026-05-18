<?php
require_once __DIR__ . '/../includes/config.php';
$page_title = 'My Wallet';
$user = require_auth();

// Refresh user data from DB for fresh balance
$u = db()->prepare("SELECT * FROM users WHERE id = ?");
$u->execute([$user['id']]);
$profile = $u->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'request_payout') {
        $amount       = (float)($_POST['amount'] ?? 0);
        $bank_details = trim($_POST['bank_details'] ?? '');

        if ($amount < 10)                      $errors[] = 'Minimum payout is R10.00.';
        if ($amount > $profile['balance'])     $errors[] = 'Amount exceeds your cleared balance.';
        if (strlen($bank_details) < 10)        $errors[] = 'Please provide valid banking details.';

        if (empty($errors)) {
            $pdo = db();
            $pdo->beginTransaction();
            try {
                // Deduct balance
                $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?")
                    ->execute([$amount, $user['id']]);

                // Create payout request
                $pdo->prepare("
                    INSERT INTO payouts (seller_id, amount, bank_details) VALUES (?, ?, ?)
                ")->execute([$user['id'], $amount, $bank_details]);

                $pdo->commit();
                flash('success', 'Payout request of ' . money($amount) . ' submitted. Admin will EFT within 2 business days.');
                redirect(BASE_URL . '/public/wallet.php');
            } catch (Throwable $e) {
                $pdo->rollBack();
                $errors[] = 'Failed to submit payout request. Please try again.';
            }
        }
    }
}

// Fetch payout history
$payouts = db()->prepare("SELECT * FROM payouts WHERE seller_id = ? ORDER BY requested_at DESC");
$payouts->execute([$user['id']]);
$payout_list = $payouts->fetchAll();

// Refresh balance
$u->execute([$user['id']]);
$profile = $u->fetch();

include __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">My Wallet</h1>

<div class="wallet-balance">
  <div class="text-muted" style="font-size:0.9rem;opacity:0.7;margin-bottom:0.25rem;">Cleared Balance</div>
  <div class="amount"><?= money($profile['balance']) ?></div>
  <p style="font-size:0.85rem;opacity:0.6;margin-top:0.5rem;">Funds released when buyers confirm delivery</p>
</div>

<?php if ($profile['balance'] >= 10): ?>
<div class="section-card mb-2">
  <h3>Request Payout</h3>
  <?php if ($errors): ?>
    <div class="flash flash-error mt-1"><?= implode('<br>', array_map('h', $errors)) ?></div>
  <?php endif; ?>
  <form method="post" class="mt-2">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="action" value="request_payout">

    <div class="form-group">
      <label>Amount (ZAR) — max <?= money($profile['balance']) ?></label>
      <input type="number" name="amount" step="0.01" min="10" max="<?= $profile['balance'] ?>"
             value="<?= h($_POST['amount'] ?? '') ?>" required placeholder="0.00">
    </div>
    <div class="form-group">
      <label>Banking Details</label>
      <textarea name="bank_details" required
                placeholder="Bank: FNB&#10;Account Name: Jane Doe&#10;Account Number: 123456789&#10;Branch Code: 250655"><?= h($_POST['bank_details'] ?? '') ?></textarea>
      <p class="form-hint">Admin will do a manual EFT. Please double-check your details.</p>
    </div>

    <button class="btn btn-gold" type="submit">Request Payout</button>
  </form>
</div>
<?php else: ?>
<div class="section-card mb-2" style="text-align:center;padding:2rem;">
  <p class="text-muted">You need at least R10.00 in cleared balance to request a payout.</p>
  <a href="listings.php" class="btn btn-primary mt-2">Browse to sell something</a>
</div>
<?php endif; ?>

<h2 style="margin-bottom:1rem;">Payout History</h2>

<?php if (empty($payout_list)): ?>
  <div class="empty-state"><p>No payout requests yet.</p></div>
<?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Ref</th><th>Amount</th><th>Status</th><th>Requested</th><th>Paid</th></tr>
      </thead>
      <tbody>
        <?php foreach ($payout_list as $p): ?>
        <tr>
          <td>#<?= $p['id'] ?></td>
          <td><?= money($p['amount']) ?></td>
          <td><span class="status status-<?= h($p['status']) ?>"><?= h($p['status']) ?></span></td>
          <td style="font-size:0.85rem;"><?= h(substr($p['requested_at'], 0, 10)) ?></td>
          <td style="font-size:0.85rem;"><?= $p['paid_at'] ? h(substr($p['paid_at'], 0, 10)) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

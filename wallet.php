<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

$u = require_auth();
// Refresh user from DB
$st = db()->prepare('SELECT * FROM users WHERE id=?');
$st->execute([$u['id']]);
$u = $st->fetch();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $bank = trim($_POST['bank_details'] ?? '');
    if (!$bank || strlen($bank) < 10) {
        $errors[] = 'Please enter your bank details.';
    }
    if ($u['balance'] <= 0) {
        $errors[] = 'You have no cleared balance to withdraw.';
    }
    if (!$errors) {
        // Check for existing pending payout
        $pending = db()->prepare('SELECT id FROM payouts WHERE seller_id=? AND status=?');
        $pending->execute([$u['id'], 'pending']);
        if ($pending->fetch()) {
            $errors[] = 'You already have a pending payout request. Please wait for it to be processed.';
        }
    }
    if (!$errors) {
        $amount = $u['balance'];
        $db = db();
        $db->beginTransaction();
        try {
            $db->prepare('INSERT INTO payouts (seller_id,amount,bank_details) VALUES (?,?,?)')
               ->execute([$u['id'], $amount, $bank]);
            $db->prepare('UPDATE users SET balance=0 WHERE id=?')->execute([$u['id']]);
            $db->commit();
            flash('success', 'Payout request of ' . fmt_money($amount) . ' submitted. The admin will process your EFT shortly.');
            redirect('/wallet.php');
        } catch (Throwable $e) {
            $db->rollBack();
            $errors[] = 'Could not submit payout: ' . $e->getMessage();
        }
    }
}

// Fetch payout history
$st = db()->prepare('SELECT * FROM payouts WHERE seller_id=? ORDER BY requested_at DESC');
$st->execute([$u['id']]);
$payouts = $st->fetchAll();

layout_head('My Wallet');
?>
<div style="max-width:680px;margin:0 auto">
    <div class="page-header">
        <h1>My Wallet</h1>
        <p>Your cleared earnings from completed sales.</p>
    </div>

    <div class="card mb-3" style="background:var(--text);color:#fff;border:none">
        <div class="card-body" style="padding:2rem">
            <div style="font-size:.85rem;color:rgba(255,255,255,.6);margin-bottom:.4rem;font-weight:600;letter-spacing:.06em;text-transform:uppercase">Cleared Balance</div>
            <div class="wallet-balance"><span>R <?= number_format($u['balance'], 2) ?></span></div>
            <div style="font-size:.82rem;color:rgba(255,255,255,.5);margin-top:.5rem">
                Funds are released when buyers confirm receipt of their orders.
            </div>
        </div>
    </div>

    <?php foreach ($errors as $e): ?>
        <div class="flash flash-error"><?= e($e) ?></div>
    <?php endforeach; ?>

    <?php if ($u['balance'] > 0): ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="section-title">Request a Payout</div>
            <p class="text-muted mb-2" style="font-size:.9rem">
                Provide your bank details and the admin will process your EFT within 1–2 business days.
                Your entire cleared balance of <strong><?= fmt_money($u['balance']) ?></strong> will be transferred.
            </p>
            <form method="post" action="<?= APP_URL ?>/wallet.php">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label" for="bank_details">Bank Details <span class="required">*</span></label>
                    <textarea class="form-control" id="bank_details" name="bank_details"
                              placeholder="Bank: FNB&#10;Account Name: Jane Doe&#10;Account Number: 62000000000&#10;Branch Code: 250655&#10;Account Type: Cheque"
                              required rows="5"></textarea>
                    <div class="form-hint">Enter your full banking details. These will be seen only by the admin.</div>
                </div>
                <button type="submit" class="btn btn-amber btn-block"
                        data-confirm="Request payout of <?= fmt_money($u['balance']) ?>?">
                    💸 Request Payout · <?= fmt_money($u['balance']) ?>
                </button>
            </form>
        </div>
    </div>
    <?php else: ?>
    <div class="card mb-3">
        <div class="card-body text-center" style="padding:2rem;color:var(--text-muted)">
            <p>You have no cleared balance at the moment.</p>
            <p class="mt-1" style="font-size:.85rem">Post listings and complete sales to earn money.</p>
            <a href="<?= APP_URL ?>/listings/create.php" class="btn btn-primary mt-2">Post a Listing</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Payout History -->
    <?php if ($payouts): ?>
    <div class="section-title">Payout History</div>
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>#</th><th>Amount</th><th>Status</th><th>Requested</th><th>Paid</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($payouts as $p): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><?= fmt_money($p['amount']) ?></td>
                        <td>
                            <span class="badge <?= $p['status']==='paid' ? 'badge-completed' : 'badge-pending' ?>">
                                <?= e($p['status']) ?>
                            </span>
                        </td>
                        <td style="font-size:.85rem"><?= substr($p['requested_at'],0,10) ?></td>
                        <td style="font-size:.85rem"><?= $p['paid_at'] ? substr($p['paid_at'],0,10) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php layout_foot(); ?>

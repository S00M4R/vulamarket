<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/shipping.php';

$u = require_auth();
$order_id = (int)($_GET['id'] ?? 0);

$st = db()->prepare(
    'SELECT o.*,
            p.title AS product_title, p.image_path, p.box_size,
            b.name AS buyer_name,  b.email AS buyer_email,  b.phone AS buyer_phone,
            s.name AS seller_name, s.email AS seller_email, s.phone AS seller_phone,
            s.locker_terminal AS seller_default_locker
     FROM orders o
     JOIN products p ON p.id = o.product_id
     JOIN users b    ON b.id = o.buyer_id
     JOIN users s    ON s.id = o.seller_id
     WHERE o.id = ? AND (o.buyer_id=? OR o.seller_id=?)'
);
$st->execute([$order_id, $u['id'], $u['id']]);
$order = $st->fetch();

if (!$order) {
    flash('error', 'Order not found.');
    redirect('/orders/index.php');
}

$is_buyer  = (int)$order['buyer_id']  === $u['id'];
$is_seller = (int)$order['seller_id'] === $u['id'];

// ── POST actions ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    // ── Send message ──────────────────────────────────────────
    if ($action === 'send_message') {
        $msg = trim($_POST['message'] ?? '');
        if ($msg) {
            db()->prepare('INSERT INTO order_messages (order_id,user_id,message) VALUES (?,?,?)')
                ->execute([$order_id, $u['id'], $msg]);
        }
        redirect('/orders/view.php?id=' . $order_id);
    }

    // ── Seller: Book shipment ─────────────────────────────────
    if ($action === 'book_shipment' && $is_seller && $order['status'] === 'paid_in_escrow' && !$order['tcg_shipment_id']) {
        $terminal_id = trim($_POST['locker_terminal'] ?? $order['seller_default_locker'] ?? SELLER_LOCKER_TERMINAL);
        if (!$terminal_id) {
            flash('error', 'Please select a drop-off locker.');
            redirect('/orders/view.php?id=' . $order_id);
        }

        try {
            $box_size = $order['box_size'] ?? 'S';

            $parts    = array_map('trim', explode(',', $order['shipping_address']));
            $street   = $parts[0] ?? '';
            $suburb   = $parts[1] ?? '';
            $city     = $parts[2] ?? '';
            $province = $parts[3] ?? '';
            $code     = $parts[4] ?? '';

            if (!$street || !$city) throw new RuntimeException('Incomplete delivery address on order.');

            $delivery_address = build_delivery_address($street, $suburb, $city, $province, $code);

            $service_code = $order['tcg_service_code'] ?? '';
            if (!$service_code) {
                $rate = tcg_get_rate($terminal_id, $delivery_address, $box_size);
                $service_code = $rate['service_level_code'];
            }

            $seller_row = ['name' => $order['seller_name'], 'email' => $order['seller_email'], 'phone' => $order['seller_phone']];
            $result     = tcg_create_shipment($order, $seller_row, $terminal_id, $delivery_address, $service_code, $box_size);

            $tracking_ref    = $result['tracking_ref'];
            $collection_code = $result['collection_code'];
            $raw_id          = $result['raw_id'];

            db()->prepare(
                "UPDATE orders SET
                    tcg_shipment_id=?,
                    tcg_shipment_raw_id=?,
                    tcg_collection_code=?,
                    tcg_locker_terminal=?,
                    tcg_service_code=?,
                    updated_at=datetime('now')
                 WHERE id=?"
            )->execute([$tracking_ref, $raw_id, $collection_code, $terminal_id, $service_code, $order_id]);

            $code_line = $collection_code
                ? "\n🔑 Your deposit code: {$collection_code} — enter this at the locker to open the compartment."
                : "\nCheck the TCG PUDO app or your email for your locker deposit code.";

            db()->prepare('INSERT INTO order_messages (order_id,user_id,message) VALUES (?,?,?)')
                ->execute([$order_id, $u['id'],
                    "📦 Shipment booked with TCG Locker.\n" .
                    "Tracking ref: {$tracking_ref}\n" .
                    "Drop-off locker: {$terminal_id}" .
                    $code_line
                ]);

            flash('success', 'Shipment booked!' . ($collection_code ? " Your deposit code is: {$collection_code}" : ' Check your email for the deposit code.'));
        } catch (Throwable $e) {
            flash('error', 'Shipment booking failed: ' . $e->getMessage());
        }
        redirect('/orders/view.php?id=' . $order_id);
    }

    // ── Buyer: Mark received ──────────────────────────────────
    if ($action === 'mark_received' && $is_buyer && $order['status'] === 'paid_in_escrow') {
        $fee        = $order['amount'] * (PLATFORM_FEE_PCT / 100);
        $seller_cut = $order['amount'] - $fee;

        $db = db();
        $db->beginTransaction();
        try {
            $db->prepare("UPDATE orders SET status='completed', updated_at=datetime('now') WHERE id=?")
               ->execute([$order_id]);
            $db->prepare('UPDATE users SET balance=balance+? WHERE id=?')
               ->execute([$seller_cut, $order['seller_id']]);
            $db->prepare('INSERT INTO order_messages (order_id,user_id,message) VALUES (?,?,?)')
               ->execute([$order_id, $u['id'],
                   "✅ Buyer confirmed receipt. Seller balance credited with " . fmt_money($seller_cut) .
                   " (after " . PLATFORM_FEE_PCT . "% platform fee)."
               ]);
            $db->commit();
            flash('success', 'Receipt confirmed. Seller has been paid!');
        } catch (Throwable $e) {
            $db->rollBack();
            flash('error', 'Could not complete action: ' . $e->getMessage());
        }
        redirect('/orders/view.php?id=' . $order_id);
    }

    redirect('/orders/view.php?id=' . $order_id);
}

// Fetch messages
$msgs_st = db()->prepare(
    'SELECT m.*, u.name AS user_name FROM order_messages m
     JOIN users u ON u.id = m.user_id
     WHERE m.order_id = ? ORDER BY m.created_at ASC'
);
$msgs_st->execute([$order_id]);
$messages = $msgs_st->fetchAll();

// Load lockers for seller picker
$lockers = [];
if ($is_seller && $order['status'] === 'paid_in_escrow' && !$order['tcg_shipment_id']) {
    try {
        $lockers = tcg_get_lockers();
        usort($lockers, fn($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));
    } catch (Throwable) {}
}

layout_head('Order #' . $order_id);
?>

<div class="page-header flex-between" style="flex-wrap:wrap;gap:.5rem">
    <div>
        <h1>Order #<?= $order_id ?></h1>
        <p><?= e($order['product_title']) ?></p>
    </div>
    <span class="badge badge-<?= e($order['status']) ?>" style="font-size:.95rem;padding:.35rem .9rem">
        <?= e(str_replace('_', ' ', $order['status'])) ?>
    </span>
</div>

<div class="order-grid">
    <div>
        <!-- Order Summary -->
        <div class="card mb-2">
            <div class="card-body">
                <div class="section-title">Order Summary</div>
                <img src="<?= APP_URL ?>/uploads/<?= e($order['image_path']) ?>"
                     alt="<?= e($order['product_title']) ?>"
                     style="width:100%;max-height:220px;object-fit:cover;border-radius:8px;margin-bottom:1rem">
                <table style="width:100%;border:none">
                    <tr><td style="color:var(--text-muted);padding:.3rem 0">Item Price</td><td style="text-align:right;font-weight:600"><?= fmt_money($order['amount']) ?></td></tr>
                    <tr><td style="color:var(--text-muted);padding:.3rem 0">Shipping</td><td style="text-align:right;font-weight:600"><?= fmt_money($order['shipping_cost']) ?></td></tr>
                    <tr><td style="padding:.4rem 0;font-weight:700;border-top:1px solid var(--border)">Total Paid</td><td style="text-align:right;font-weight:800;font-size:1.1rem;border-top:1px solid var(--border)"><?= fmt_money($order['total_amount']) ?></td></tr>
                </table>

                <hr class="divider">
                <div style="font-size:.9rem">
                    <div class="mb-1"><strong>Delivery Address:</strong><br>
                        <span style="color:var(--text-muted)"><?= e($order['shipping_address']) ?></span>
                    </div>
                    <div class="mb-1"><strong>Buyer:</strong> <?= e($order['buyer_name']) ?></div>
                    <div><strong>Seller:</strong> <?= e($order['seller_name']) ?></div>
                </div>

                <?php if ($order['tcg_shipment_id']): ?>
                <!-- ── Shipment info ── -->
                <hr class="divider">
                <div style="background:#F5F1EB;border-radius:var(--radius);padding:1rem">
                    <div style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);margin-bottom:.5rem">📦 Shipment</div>
                    <div style="font-family:var(--font-display);font-weight:800;font-size:1.1rem;margin-bottom:.25rem;letter-spacing:.04em">
                        <?= e($order['tcg_shipment_id']) ?>
                    </div>
                    <?php if ($order['tcg_locker_terminal']): ?>
                    <div style="font-size:.83rem;color:var(--text-muted);margin-bottom:.75rem">
                        Drop-off locker: <strong><?= e($order['tcg_locker_terminal']) ?></strong>
                    </div>
                    <?php endif; ?>

                    <?php if ($is_seller && $order['tcg_collection_code']): ?>
                    <!-- Deposit code — seller only -->
                    <div style="background:#fff;border:2px solid var(--accent);border-radius:var(--radius);padding:.85rem 1rem;margin-bottom:.85rem;text-align:center">
                        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--accent);margin-bottom:.35rem">
                            🔑 Locker Deposit Code
                        </div>
                        <div style="font-size:2.2rem;font-weight:900;font-family:var(--font-display);letter-spacing:.15em;color:var(--text)">
                            <?= e($order['tcg_collection_code']) ?>
                        </div>
                        <div style="font-size:.78rem;color:var(--text-muted);margin-top:.35rem">
                            Enter this code at the locker to open the compartment and deposit your parcel.
                        </div>
                    </div>
                    <?php elseif ($is_seller): ?>
                    <div style="font-size:.83rem;color:var(--text-muted);margin-bottom:.75rem">
                        Check your email or the TCG PUDO app for your deposit code.
                    </div>
                    <?php endif; ?>

                    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                        <a href="<?= APP_URL ?>/orders/track.php?order_id=<?= $order_id ?>"
                           class="btn btn-primary btn-sm">🔍 Track Parcel</a>
                        <?php if ($is_seller): ?>
                        <a href="<?= APP_URL ?>/orders/waybill.php?order_id=<?= $order_id ?>"
                           target="_blank"
                           class="btn btn-amber btn-sm">🖨 Waybill PDF</a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php elseif ($is_seller && $order['status'] === 'paid_in_escrow'): ?>
                <!-- ── Seller: Book shipment panel ── -->
                <hr class="divider">
                <div style="background:#EEF5EC;border:1px solid #b5d6b0;border-radius:var(--radius);padding:1rem">
                    <div style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#3a6e35;margin-bottom:.6rem">
                        📦 Book TCG Locker Collection
                    </div>
                    <p style="font-size:.86rem;color:var(--text-muted);margin-bottom:.85rem">
                        Choose the locker where you'll drop off this parcel.
                        Once booked you'll receive a deposit code to open the compartment.
                    </p>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="book_shipment">
                        <div class="form-group" style="margin-bottom:.75rem">
                            <label class="form-label" style="font-size:.85rem">Select drop-off locker</label>
                            <?php if (!empty($lockers)): ?>
                            <select class="form-control" name="locker_terminal" required>
                                <option value="">— Choose a locker —</option>
                                <?php
                                $default = $order['seller_default_locker'] ?? '';
                                foreach ($lockers as $lk):
                                    $lcode = $lk['code'] ?? '';
                                    $lsel  = $lcode === $default ? 'selected' : '';
                                    $llabel = ($lk['name'] ?? $lcode) . ($lk['address'] ? ' — ' . $lk['address'] : '');
                                ?>
                                <option value="<?= e($lcode) ?>" <?= $lsel ?>><?= e($llabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php else: ?>
                            <input class="form-control" name="locker_terminal" type="text"
                                   placeholder="Terminal code e.g. CG10"
                                   value="<?= e($order['seller_default_locker'] ?? '') ?>">
                            <div class="form-hint">Could not load locker list — enter terminal code manually.</div>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block"
                                data-confirm="Book this TCG collection? A deposit code will be generated.">
                            📦 Book Collection &amp; Get Deposit Code
                        </button>
                    </form>
                </div>

                <?php elseif ($order['status'] === 'paid_in_escrow'): ?>
                <hr class="divider">
                <div style="background:#FEF3C7;border-radius:var(--radius);padding:.85rem;font-size:.88rem;color:#92400E">
                    ⏳ Waiting for seller to book TCG Locker collection…
                </div>
                <?php endif; ?>
            </div>

            <?php if ($is_buyer && $order['status'] === 'paid_in_escrow'): ?>
            <div class="card-footer">
                <p class="text-muted mb-1" style="font-size:.85rem">
                    Once your item arrives, click below to release payment to the seller.
                </p>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="mark_received">
                    <button class="btn btn-green btn-block"
                            data-confirm="Confirm you received the item? This will release funds to the seller.">
                        ✅ Mark as Received
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <?php if ($order['status'] === 'completed'): ?>
            <div class="card-footer">
                <div class="flash flash-success" style="margin:0">Order completed successfully.</div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right: order chat -->
    <div>
        <div class="section-title">Order Messages</div>
        <div class="chat-box">
            <div class="chat-messages">
                <?php if (empty($messages)): ?>
                    <p class="text-muted text-center" style="font-size:.85rem;padding:1rem">
                        No messages yet. Ask a question or provide an update!
                    </p>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <?php $mine = (int)$msg['user_id'] === $u['id']; ?>
                        <div class="chat-msg <?= $mine ? 'mine' : '' ?>">
                            <div class="chat-msg-meta">
                                <strong><?= e($msg['user_name']) ?></strong>
                                · <?= substr($msg['created_at'], 0, 16) ?>
                            </div>
                            <div class="chat-bubble"><?= nl2br(e($msg['message'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php if ($order['status'] !== 'completed' && $order['status'] !== 'cancelled'): ?>
            <form method="post" class="chat-input-row">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="send_message">
                <input type="text" name="message" placeholder="Type a message…" required maxlength="1000" autocomplete="off">
                <button type="submit">Send</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php layout_foot(); ?>

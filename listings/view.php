<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('/index.php');

$st = db()->prepare(
    'SELECT p.*, u.name AS seller_name, u.id AS seller_id
     FROM products p
     JOIN users u ON u.id = p.seller_id
     WHERE p.id = ? AND p.is_active = 1'
);
$st->execute([$id]);
$product = $st->fetch();

if (!$product) {
    flash('error', 'Listing not found or no longer available.');
    redirect('/index.php');
}

$u        = auth_user_cached();
$is_owner = $u && $u['id'] === (int)$product['seller_id'];

// Check seller profile completeness
$sp = db()->prepare('SELECT phone, locker_terminal FROM users WHERE id=?');
$sp->execute([$product['seller_id']]);
$sp = $sp->fetch();
$seller_ready = !empty($sp['phone']) && !empty($sp['locker_terminal']);

// Check if logged-in buyer is missing their phone
$buyer_phone_missing = false;
if ($u && !$is_owner) {
    $bphone = db()->prepare('SELECT phone FROM users WHERE id=?');
    $bphone->execute([$u['id']]);
    $buyer_phone_missing = empty($bphone->fetchColumn());
}

layout_head(e($product['title']));
?>

<style>
#delivery-address-form .form-row {
    display:grid; grid-template-columns:1fr 1fr; gap:.75rem;
}
#shipping-quote-box {
    background:#EEF5EC; border:1px solid #b5d6b0;
    border-radius:var(--radius); padding:.85rem;
    font-size:.9rem; margin-top:.5rem; display:none;
}
@media(max-width:680px){
    div[style*="grid-template-columns"]{grid-template-columns:1fr !important;}
    #delivery-address-form .form-row { grid-template-columns:1fr !important; }
}
</style>

<div style="max-width:900px;margin:0 auto">
    <div style="display:grid;grid-template-columns:1fr 340px;gap:2rem;align-items:start">

        <!-- Left: image + description -->
        <div>
            <img src="/uploads/<?= e($product['image_path']) ?>"
                 alt="<?= e($product['title']) ?>"
                 class="product-detail-img">
            <div class="card mt-2">
                <div class="card-body">
                    <div class="section-title">About this item</div>
                    <p style="white-space:pre-wrap;color:var(--text)"><?= e($product['description']) ?></p>
                </div>
            </div>
        </div>

        <!-- Right: buy panel -->
        <div>
            <div class="card">
                <div class="card-body">
                    <h1 style="font-family:var(--font-display);font-size:1.4rem;font-weight:800;margin-bottom:.5rem"><?= e($product['title']) ?></h1>
                    <div style="font-size:2rem;font-weight:800;color:var(--accent);font-family:var(--font-display);margin-bottom:.5rem">
                        <?= fmt_money($product['price']) ?>
                    </div>
                    <p class="text-muted" style="font-size:.85rem;margin-bottom:1rem">
                        Listed by <strong><?= e($product['seller_name']) ?></strong>
                    </p>

                    <?php if ($is_owner): ?>
                        <div class="flash flash-info">This is your listing.</div>
                        <?php if (!$seller_ready): ?>
                        <div class="flash flash-error" style="font-size:.84rem;margin-top:.5rem">
                            &#9888;&#65039; <strong>Profile incomplete.</strong>
                            Buyers cannot get a shipping quote until you add your
                            <a href="<?= APP_URL ?>/auth/profile.php" style="font-weight:700">phone number &amp; drop-off locker</a>.
                        </div>
                        <?php endif; ?>
                        <a href="<?= APP_URL ?>/listings/delete.php?id=<?= $product['id'] ?>"
                           class="btn btn-outline btn-block"
                           data-confirm="Remove this listing? This can't be undone.">Remove Listing</a>

                    <?php elseif (!$u): ?>
                        <a href="<?= APP_URL ?>/auth/login.php" class="btn btn-primary btn-block btn-lg">Log in to Buy</a>

                    <?php else: ?>

                        <?php if ($buyer_phone_missing): ?>
                        <div class="flash flash-info" style="font-size:.84rem;margin-bottom:.75rem">
                            &#128241; Please add your <a href="<?= APP_URL ?>/auth/profile.php" style="font-weight:700">phone number</a>
                            so TCG can reach you for delivery.
                        </div>
                        <?php endif; ?>

                        <form method="post" action="<?= APP_URL ?>/orders/checkout.php" id="checkout-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="product_id"        value="<?= $product['id'] ?>">
                            <input type="hidden" name="shipping_cost"      id="f-cost"    value="">
                            <input type="hidden" name="service_level_code" id="f-service" value="">

                            <div id="delivery-address-form">

                                <!-- Delivery address fields -->
                                <div class="form-group">
                                    <label class="form-label">&#127968; Delivery Address <span class="required">*</span></label>
                                    <input class="form-control" name="street" id="f-street" type="text"
                                           placeholder="Street address" required autocomplete="street-address">
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <input class="form-control" name="suburb" id="f-suburb" type="text"
                                               placeholder="Suburb" autocomplete="address-level3">
                                    </div>
                                    <div class="form-group">
                                        <input class="form-control" name="city" id="f-city" type="text"
                                               placeholder="City" required autocomplete="address-level2">
                                    </div>
                                </div>
                                <div class="form-row" style="margin-bottom:.75rem">
                                    <div class="form-group" style="margin-bottom:0">
                                        <select class="form-control" name="province" id="f-province" required>
                                            <option value="">Province</option>
                                            <option value="EC">Eastern Cape</option>
                                            <option value="FS">Free State</option>
                                            <option value="GP">Gauteng</option>
                                            <option value="KZN">KwaZulu-Natal</option>
                                            <option value="LP">Limpopo</option>
                                            <option value="MP">Mpumalanga</option>
                                            <option value="NC">Northern Cape</option>
                                            <option value="NW">North West</option>
                                            <option value="WC">Western Cape</option>
                                        </select>
                                    </div>
                                    <div class="form-group" style="margin-bottom:0">
                                        <input class="form-control" name="postal_code" id="f-code" type="text"
                                               placeholder="Postal code" maxlength="10" autocomplete="postal-code">
                                    </div>
                                </div>

                                <button type="button" id="get-quote-btn" class="btn btn-outline btn-block btn-sm">
                                    Get Shipping Quote
                                </button>
                            </div>

                            <!-- Quote result -->
                            <div id="shipping-quote-box">
                                <div id="quote-loading" style="color:var(--text-muted);display:none">&#9203; Getting shipping rate&hellip;</div>
                                <div id="quote-result"></div>
                            </div>

                            <hr class="divider">
                            <button type="submit" id="checkout-btn" class="btn btn-primary btn-block btn-lg" disabled>
                                &#128274; Proceed to Payment
                            </button>
                            <p class="text-muted text-center mt-1" style="font-size:.8rem">
                                Payment held in escrow until you confirm delivery.
                            </p>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
(function () {
    var BASE       = window.APP_BASE || '';
    var PRODUCT_ID = <?= (int)$product['id'] ?>;

    var getQuoteBtn = document.getElementById('get-quote-btn');
    var quoteBox    = document.getElementById('shipping-quote-box');
    var quoteLoad   = document.getElementById('quote-loading');
    var quoteResult = document.getElementById('quote-result');
    var checkoutBtn = document.getElementById('checkout-btn');

    if (!getQuoteBtn) return;

    function escHtml(s) {
        return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function resetQuote() {
        checkoutBtn.disabled = true;
        document.getElementById('f-cost').value    = '';
        document.getElementById('f-service').value = '';
        quoteResult.innerHTML  = '';
        quoteBox.style.display = 'none';
    }

    function getQuote() {
        var street   = document.getElementById('f-street').value.trim();
        var suburb   = document.getElementById('f-suburb').value.trim();
        var city     = document.getElementById('f-city').value.trim();
        var province = document.getElementById('f-province').value;
        var code     = document.getElementById('f-code').value.trim();
        if (!street || !city || !province) {
            quoteResult.innerHTML = '<span style="color:var(--accent)">&#9888; Please fill in street, city and province.</span>';
            quoteBox.style.display = 'block';
            return;
        }

        quoteBox.style.display = 'block';
        quoteLoad.style.display = 'block';
        quoteResult.innerHTML   = '';
        checkoutBtn.disabled    = true;
        document.getElementById('f-cost').value    = '';
        document.getElementById('f-service').value = '';

        var params = new URLSearchParams({street:street, suburb:suburb, city:city, province:province, code:code, product_id:PRODUCT_ID});
        fetch(BASE + '/api/shipping_quote.php?' + params)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                quoteLoad.style.display = 'none';
                if (data.error) {
                    quoteResult.innerHTML = '<span style="color:var(--accent)">&#9888; ' + escHtml(data.error) + '</span>';
                    return;
                }
                var delivery = data.delivery_date_from
                    ? new Date(data.delivery_date_from).toLocaleDateString('en-ZA', {day:'numeric', month:'short'})
                    : 'a few business days';
                quoteResult.innerHTML =
                    '<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.4rem">'
                    + '<div><strong>&#128666; ' + escHtml(data.service) + '</strong><br>'
                    + '<span style="font-size:.82rem;color:#3a6e35">Est. delivery by ' + delivery + '</span>'
                    + (data.box_type ? '<br><span style="font-size:.79rem;color:var(--text-muted)">Box: ' + escHtml(data.box_type) + '</span>' : '')
                    + '</div>'
                    + '<div style="font-size:1.25rem;font-weight:800;color:var(--accent)">R ' + parseFloat(data.rate).toFixed(2) + '</div>'
                    + '</div>';
                document.getElementById('f-cost').value    = data.rate;
                document.getElementById('f-service').value = data.service_level_code;
                checkoutBtn.disabled = false;
            })
            .catch(function() {
                quoteLoad.style.display = 'none';
                quoteResult.innerHTML = '<span style="color:var(--accent)">&#9888; Could not get rate. Please try again.</span>';
            });
    }

    getQuoteBtn.addEventListener('click', getQuote);

    ['f-street','f-suburb','f-city','f-province','f-code'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('input', resetQuote);
    });
})();
</script>

<?php layout_foot(); ?>

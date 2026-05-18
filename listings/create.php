<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

$u = require_auth();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $title = trim($_POST['title'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);

    if (!$title || strlen($title) < 3)  $errors[] = 'Title must be at least 3 characters.';
    if (!$desc  || strlen($desc)  < 10) $errors[] = 'Description must be at least 10 characters.';
    if ($price  <= 0)                   $errors[] = 'Price must be greater than R0.';
    $box_size = strtoupper(trim($_POST['box_size'] ?? 'S'));
    if (!in_array($box_size, ['XS','S','M','L','XL'])) $box_size = 'S';

    $image_path = '';
    if (empty($_FILES['image']['name'])) {
        $errors[] = 'Please upload a product image.';
    } else {
        try {
            $image_path = handle_image_upload('image');
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (!$errors) {
        $st = db()->prepare(
            'INSERT INTO products (seller_id,title,description,price,image_path,box_size) VALUES (?,?,?,?,?,?)'
        );
        $st->execute([$u['id'], $title, $desc, $price, $image_path, $box_size]);
        $pid = db()->lastInsertId();
        flash('success', 'Listing published!');
        redirect('/listings/view.php?id=' . $pid);
    }
}

layout_head('Post a Listing');
?>
<div style="max-width:620px;margin:0 auto">
    <div class="page-header">
        <h1>Post a Listing</h1>
        <p>List your item and start selling today — no fees until it sells.</p>
    </div>

    <?php foreach ($errors as $err): ?>
        <div class="flash flash-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <div class="card">
        <div class="card-body">
            <form method="post" action="<?= APP_URL ?>/listings/create.php" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label class="form-label" for="title">Item Title <span class="required">*</span></label>
                    <input class="form-control" id="title" name="title" type="text"
                           placeholder="e.g. Sony PS5 Controller – Like New"
                           value="<?= e($_POST['title'] ?? '') ?>" required maxlength="120">
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Description <span class="required">*</span></label>
                    <textarea class="form-control" id="description" name="description"
                              placeholder="Describe the item's condition, what's included, and any other details…"
                              required maxlength="2000"><?= e($_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="price">Price (ZAR) <span class="required">*</span></label>
                    <input class="form-control" id="price" name="price" type="number"
                           placeholder="0.00" step="0.01" min="1"
                           value="<?= e($_POST['price'] ?? '') ?>" required>
                    <div class="form-hint">A <?= PLATFORM_FEE_PCT ?>% platform fee is deducted from your payout when the item sells.</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="image">Product Photo <span class="required">*</span></label>
                    <input class="form-control" id="image" name="image" type="file"
                           accept="image/jpeg,image/png,image/webp,image/gif" required>
                    <div class="form-hint">JPEG, PNG, or WebP. Max 5MB.</div>
                    <img id="image-preview" src="" alt="Preview" style="display:none;max-height:200px;margin-top:.75rem;border-radius:8px;object-fit:cover">
                </div>

                <div class="form-group">
                    <label class="form-label">&#128230; Parcel Size <span class="required">*</span></label>
                    <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:.6rem">
                        Choose the smallest locker size your packaged item will fit into.
                        This is used to quote accurate shipping costs for buyers.
                    </p>
                    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:.4rem" id="box-size-grid">
                        <?php
                        $box_sizes = [
                            'XS' => ['dims' => '20x15 cm', 'weight' => 'max 1 kg'],
                            'S'  => ['dims' => '32x22 cm', 'weight' => 'max 3 kg'],
                            'M'  => ['dims' => '40x30 cm', 'weight' => 'max 7 kg'],
                            'L'  => ['dims' => '50x40 cm', 'weight' => 'max 15 kg'],
                            'XL' => ['dims' => '60x50 cm', 'weight' => 'max 25 kg'],
                        ];
                        $saved_size = $_POST['box_size'] ?? 'S';
                        foreach ($box_sizes as $bk => $bs):
                            $active = $bk === $saved_size ? ' active' : '';
                        ?>
                        <label class="box-size-btn<?= $active ?>" data-size="<?= $bk ?>">
                            <input type="radio" name="box_size" value="<?= $bk ?>"
                                   style="display:none" <?= $bk === $saved_size ? 'checked' : '' ?>>
                            <div class="bsb-label"><?= $bk ?></div>
                            <div class="bsb-dims"><?= $bs['dims'] ?></div>
                            <div class="bsb-weight"><?= $bs['weight'] ?></div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">Publish Listing</button>
            </form>
        </div>
    </div>
</div>
<style>
.box-size-btn {
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    border:2px solid var(--border); border-radius:var(--radius);
    padding:.5rem .2rem; cursor:pointer; text-align:center;
    transition:border-color .15s, background .15s; user-select:none;
}
.box-size-btn:hover  { border-color:var(--accent); background:#fff8f5; }
.box-size-btn.active { border-color:var(--accent); background:#fff3ee; }
.bsb-label  { font-weight:800; font-size:1rem; font-family:var(--font-display); color:var(--accent); }
.bsb-dims   { font-size:.68rem; color:var(--text-muted); margin-top:.1rem; line-height:1.3; }
.bsb-weight { font-size:.65rem; color:var(--text-muted); }
</style>
<script>
document.querySelectorAll('.box-size-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.box-size-btn').forEach(function(b) { b.classList.remove('active'); });
        btn.classList.add('active');
        btn.querySelector('input[type=radio]').checked = true;
    });
});
</script>
<?php layout_foot(); ?>

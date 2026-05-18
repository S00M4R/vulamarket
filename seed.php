<?php
// ============================================================
// VULA MARKET — Database Seeder
// Run once: php seed.php
// ============================================================
require_once __DIR__ . '/includes/helpers.php';

$db = db();

echo "🌱 Seeding Vula Market database...\n\n";

// Admin user
$hash = password_hash('admin123', PASSWORD_BCRYPT);
try {
    $db->prepare('INSERT OR IGNORE INTO users (name,email,password,is_admin) VALUES (?,?,?,1)')
       ->execute(['Admin', 'admin@vulamarket.co.za', $hash]);
    echo "✅ Admin: admin@vulamarket.co.za / admin123\n";
} catch (PDOException $e) {
    echo "ℹ Admin already exists.\n";
}

// Sample buyer
$hash2 = password_hash('buyer123', PASSWORD_BCRYPT);
try {
    $db->prepare('INSERT OR IGNORE INTO users (name,email,password) VALUES (?,?,?)')
       ->execute(['Thabo Nkosi', 'buyer@test.co.za', $hash2]);
    echo "✅ Buyer: buyer@test.co.za / buyer123\n";
} catch (PDOException $e) {
    echo "ℹ Buyer already exists.\n";
}

// Sample seller
$hash3 = password_hash('seller123', PASSWORD_BCRYPT);
try {
    $db->prepare('INSERT OR IGNORE INTO users (name,email,password) VALUES (?,?,?)')
       ->execute(['Aisha Mokoena', 'seller@test.co.za', $hash3]);
    echo "✅ Seller: seller@test.co.za / seller123\n";
} catch (PDOException $e) {
    echo "ℹ Seller already exists.\n";
}

// Get seller ID
$seller = $db->query("SELECT id FROM users WHERE email='seller@test.co.za'")->fetch();
if ($seller) {
    // Sample listings (placeholder image)
    $items = [
        ['Sony WH-1000XM5 Headphones', 'Like new, barely used. Comes with original box and accessories. No scratches.', 3200],
        ['iPhone 13 Pro – 256GB', 'Sierra Blue. Minor screen scratch. Battery health 89%. All accessories included.', 9500],
        ['Vintage Leather Couch', '3-seater. Tan leather, some wear on armrests. Perfect for a bachelor pad.', 2800],
        ['Trek Mountain Bike 29"', 'Excellent condition. 21-speed, hydraulic brakes. Barely ridden.', 5500],
    ];
    // Create a placeholder image
    $placeholder = UPLOAD_DIR . 'placeholder.jpg';
    if (!file_exists($placeholder)) {
        // Create a simple grey placeholder
        if (extension_loaded('gd')) {
            $img = imagecreatetruecolor(400, 300);
            $bg  = imagecolorallocate($img, 220, 215, 205);
            $fg  = imagecolorallocate($img, 150, 140, 130);
            imagefill($img, 0, 0, $bg);
            imagestring($img, 5, 130, 130, 'VULA MARKET', $fg);
            imagejpeg($img, $placeholder, 85);
            imagedestroy($img);
        } else {
            file_put_contents($placeholder, ''); // empty fallback
        }
    }

    foreach ($items as [$title, $desc, $price]) {
        $exists = $db->prepare('SELECT id FROM products WHERE title=? AND seller_id=?');
        $exists->execute([$title, $seller['id']]);
        if (!$exists->fetch()) {
            $db->prepare('INSERT INTO products (seller_id,title,description,price,image_path) VALUES (?,?,?,?,?)')
               ->execute([$seller['id'], $title, $desc, $price, 'placeholder.jpg']);
            echo "✅ Listing: $title (R$price)\n";
        }
    }
}

echo "\n✅ Done! Visit http://localhost/\n";
echo "   Admin panel: http://localhost/admin/index.php\n";

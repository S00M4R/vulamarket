#!/usr/bin/env php
<?php
/**
 * VULA MARKET — Setup Script
 * Run once from CLI: php setup.php
 */
require_once __DIR__ . '/includes/helpers.php';

echo "\n=== VULA MARKET Setup ===\n\n";

// Init DB (auto-runs migration)
db();
echo "✓ Database initialized at: " . DB_PATH . "\n";

// Create uploads directory
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
    echo "✓ Uploads directory created: " . UPLOAD_DIR . "\n";
} else {
    echo "✓ Uploads directory: " . UPLOAD_DIR . "\n";
}

// Create admin user
echo "\nCreating admin user…\n";
$admin_pass = bin2hex(random_bytes(8));

$stmt = db()->prepare("INSERT OR IGNORE INTO users (name, email, password, is_admin) VALUES (?, ?, ?, 1)");
$stmt->execute(['Admin', ADMIN_EMAIL, password_hash($admin_pass, PASSWORD_BCRYPT)]);

if (db()->lastInsertId()) {
    echo "✓ Admin user created\n";
    echo "  Email:    " . ADMIN_EMAIL . "\n";
    echo "  Password: $admin_pass\n";
    echo "\n  *** SAVE THESE CREDENTIALS — shown only once ***\n";
} else {
    echo "→ Admin user already exists (skipped)\n";
}

echo "\n=== Done ===\n";
echo "Next: edit config/config.php to set:\n";
echo "  APP_URL, YOCO_SECRET_KEY, TCG_API_KEY, SELLER_COLLECTION address\n\n";

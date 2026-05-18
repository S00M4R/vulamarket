<?php
// ============================================================
// VULA MARKET — Configuration
// ============================================================
define('APP_NAME',    'Vula Market');
define('APP_VERSION', '1.0.0-mvp');

// --- Auto-detect base URL ---
$_app_scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_app_host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_app_docroot  = rtrim(str_replace('\\','/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$_app_selfdir  = rtrim(str_replace('\\','/', dirname(dirname(__FILE__))), '/');
$_app_subdir   = str_replace($_app_docroot, '', $_app_selfdir);
define('APP_URL',      rtrim($_app_scheme . '://' . $_app_host . $_app_subdir, '/'));
define('APP_SUBDIR',   rtrim($_app_subdir, '/'));

// --- Database ---
define('DB_PATH', __DIR__ . '/../db/vulamarket.sqlite');

// --- Sessions ---
define('SESSION_NAME',     'vulamarket_sess');
define('SESSION_LIFETIME', 86400); // 24h

// --- File Uploads ---
define('UPLOAD_DIR',      __DIR__ . '/../uploads/');
define('UPLOAD_URL',      APP_URL . '/uploads/');
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED',  ['image/jpeg','image/png','image/webp','image/gif']);

// --- Yoco ---
define('YOCO_SECRET_KEY',   'sk_test_1ad05fdbbBEOvv3148e4978a7cdb');
define('YOCO_API_BASE',     'https://payments.yoco.com/api');

// --- TCG Locker (The Courier Guy PUDO Sandbox) ---
// Register at sandbox.tcglocker.co.za → Settings → API Keys
define('TCG_API_KEY',  '4286|Ny8XAwgE4Odi2ep34dVGSgM67MxboQti55nmWJZKbe0a7a34');
define('TCG_API_BASE', 'https://sandbox.api-pudo.co.za');

// Seller's default drop-off locker terminal ID — used for Locker-to-Door (L2D) rate quotes
// when the seller has not saved their own preferred locker on their profile.
define('SELLER_LOCKER_TERMINAL', 'CG10');

// --- Admin ---
define('ADMIN_EMAIL', 'admin@vulamarket.co.za');

// --- Security ---
define('CSRF_TOKEN_LENGTH', 32);

// --- Marketplace Fee (%) deducted from seller payout ---
define('PLATFORM_FEE_PCT', 5); // 5%

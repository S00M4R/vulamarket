<?php
// ============================================================
// VULA MARKET — Core Helpers
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';

// ---- Session -----------------------------------------------
function session_boot(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => false, // set true in production (HTTPS)
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

// ---- Auth --------------------------------------------------
function auth_user(): ?array {
    session_boot();
    $id = $_SESSION['user_id'] ?? null;
    if (!$id) return null;
    return db()->prepare('SELECT * FROM users WHERE id=?')
               ->execute([$id]) ? db()->query("SELECT * FROM users WHERE id=$id")->fetch() : null;
}

function auth_user_cached(): ?array {
    static $u = false;
    if ($u === false) {
        session_boot();
        $id = $_SESSION['user_id'] ?? null;
        if (!$id) { $u = null; return null; }
        $st = db()->prepare('SELECT * FROM users WHERE id=?');
        $st->execute([$id]);
        $u = $st->fetch() ?: null;
    }
    return $u;
}

function require_auth(): array {
    $u = auth_user_cached();
    if (!$u) redirect('/auth/login.php');
    return $u;
}

function require_admin(): array {
    $u = require_auth();
    if (!$u['is_admin']) { http_response_code(403); die('Forbidden'); }
    return $u;
}

function login_user(int $id): void {
    session_boot();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $id;
}

function logout_user(): void {
    session_boot();
    session_destroy();
}

// ---- CSRF --------------------------------------------------
function csrf_token(): string {
    session_boot();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_verify(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        die('CSRF token mismatch. Go back and try again.');
    }
}

// ---- Flash messages ----------------------------------------
function flash(string $key, string $msg): void {
    session_boot();
    $_SESSION['flash'][$key] = $msg;
}

function flash_get(string $key): ?string {
    session_boot();
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

// ---- Redirect ----------------------------------------------
function redirect(string $url): never {
    // If $url starts with http it's already absolute; otherwise prepend APP_URL
    $location = (str_starts_with($url, 'http://') || str_starts_with($url, 'https://'))
        ? $url
        : APP_URL . $url;
    header('Location: ' . $location);
    exit;
}

// ---- Sanitise / escape -------------------------------------
function e(mixed $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function only(array $data, array $keys): array {
    return array_intersect_key($data, array_flip($keys));
}

// ---- File Upload -------------------------------------------
function handle_image_upload(string $field): string {
    $file = $_FILES[$field] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed or missing.');
    }
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        throw new RuntimeException('Image too large. Max 5MB.');
    }
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, UPLOAD_ALLOWED, true)) {
        throw new RuntimeException('Invalid image type. JPEG, PNG, WebP, or GIF only.');
    }
    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = bin2hex(random_bytes(16)) . '.' . strtolower($ext);
    $dest     = UPLOAD_DIR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Could not save uploaded file.');
    }
    return $filename;
}

// ---- Money formatting --------------------------------------
function fmt_money(float $amount): string {
    return 'R ' . number_format($amount, 2);
}

// ---- Pagination --------------------------------------------
function paginate(int $total, int $per_page, int $current): array {
    $pages = (int)ceil($total / $per_page);
    return [
        'total'    => $total,
        'per_page' => $per_page,
        'current'  => $current,
        'pages'    => $pages,
        'offset'   => ($current - 1) * $per_page,
    ];
}

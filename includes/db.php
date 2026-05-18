<?php
// ============================================================
// VULA MARKET — Database (SQLite via PDO)
// ============================================================
require_once __DIR__ . '/../config/config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dir = dirname(DB_PATH);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    $pdo->exec('PRAGMA journal_mode=WAL');
    $pdo->exec('PRAGMA foreign_keys=ON');

    db_migrate($pdo);
    return $pdo;
}

function db_migrate(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            name       TEXT    NOT NULL,
            email      TEXT    NOT NULL UNIQUE,
            password   TEXT    NOT NULL,
            balance    REAL    NOT NULL DEFAULT 0.00,
            is_admin   INTEGER NOT NULL DEFAULT 0,
            created_at TEXT    NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS products (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            seller_id   INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            title       TEXT    NOT NULL,
            description TEXT    NOT NULL,
            price       REAL    NOT NULL,
            image_path  TEXT    NOT NULL,
            is_active   INTEGER NOT NULL DEFAULT 1,
            created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS orders (
            id               INTEGER PRIMARY KEY AUTOINCREMENT,
            buyer_id         INTEGER NOT NULL REFERENCES users(id),
            seller_id        INTEGER NOT NULL REFERENCES users(id),
            product_id       INTEGER NOT NULL REFERENCES products(id),
            amount           REAL    NOT NULL,
            shipping_cost    REAL    NOT NULL DEFAULT 0.00,
            total_amount     REAL    NOT NULL,
            yoco_checkout_id TEXT    UNIQUE,
            tcg_shipment_id  TEXT,
            tcg_service_code TEXT,
            shipping_address TEXT    NOT NULL,
            status           TEXT    NOT NULL DEFAULT 'pending',
            created_at       TEXT    NOT NULL DEFAULT (datetime('now')),
            updated_at       TEXT    NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS order_messages (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id   INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
            user_id    INTEGER NOT NULL REFERENCES users(id),
            message    TEXT    NOT NULL,
            created_at TEXT    NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS payouts (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            seller_id    INTEGER NOT NULL REFERENCES users(id),
            amount       REAL    NOT NULL,
            bank_details TEXT    NOT NULL,
            status       TEXT    NOT NULL DEFAULT 'pending',
            requested_at TEXT    NOT NULL DEFAULT (datetime('now')),
            paid_at      TEXT
        );

        CREATE INDEX IF NOT EXISTS idx_products_seller  ON products(seller_id);
        CREATE INDEX IF NOT EXISTS idx_orders_buyer     ON orders(buyer_id);
        CREATE INDEX IF NOT EXISTS idx_orders_seller    ON orders(seller_id);
        CREATE INDEX IF NOT EXISTS idx_orders_status    ON orders(status);
        CREATE INDEX IF NOT EXISTS idx_order_msgs_order ON order_messages(order_id);
        CREATE INDEX IF NOT EXISTS idx_payouts_seller   ON payouts(seller_id);
        CREATE INDEX IF NOT EXISTS idx_payouts_status   ON payouts(status);
    ");

    // Safe migrations for existing installs — adds new columns if missing
    $existing = array_column(
        $pdo->query("PRAGMA table_info(orders)")->fetchAll(PDO::FETCH_ASSOC),
        'name'
    );
    if (!in_array('tcg_service_code', $existing)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN tcg_service_code TEXT");
    }

    $u_existing = array_column(
        $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC),
        'name'
    );
    if (!in_array('collection_address', $u_existing)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN collection_address TEXT");
    }
    if (!in_array('phone', $u_existing)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN phone TEXT");
    }
    if (!in_array('locker_terminal', $u_existing)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN locker_terminal TEXT");
    }

    $p_existing = array_column(
        $pdo->query("PRAGMA table_info(products)")->fetchAll(PDO::FETCH_ASSOC),
        'name'
    );
    if (!in_array('box_size', $p_existing)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN box_size TEXT NOT NULL DEFAULT 'S'");
    }

    $o_existing = array_column(
        $pdo->query("PRAGMA table_info(orders)")->fetchAll(PDO::FETCH_ASSOC),
        'name'
    );
    if (!in_array('collection_address', $o_existing)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN collection_address TEXT");
    }
    if (!in_array('tcg_collection_code', $o_existing)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN tcg_collection_code TEXT");
    }
    if (!in_array('tcg_locker_terminal', $o_existing)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN tcg_locker_terminal TEXT");
    }
    if (!in_array('tcg_shipment_raw_id', $o_existing)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN tcg_shipment_raw_id INTEGER");
    }
}

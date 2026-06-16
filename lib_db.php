<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_bootstrap.php';

function db_path(): string
{
    return __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'app.sqlite';
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $dir = dirname(db_path());
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $pdo = new PDO('sqlite:' . db_path(), null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Reasonable defaults
    $pdo->exec('PRAGMA foreign_keys = ON;');
    $pdo->exec('PRAGMA journal_mode = WAL;');
    $pdo->exec('PRAGMA synchronous = NORMAL;');

    db_init($pdo);
    return $pdo;
}

function db_init(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            full_name TEXT NOT NULL,
            phone TEXT,
            company_name TEXT,
            locale TEXT NOT NULL DEFAULT 'fr-FR',
            timezone TEXT NOT NULL DEFAULT 'UTC',
            default_shipping_country TEXT,
            default_shipping_line1 TEXT,
            default_shipping_line2 TEXT,
            default_shipping_city TEXT,
            default_shipping_region TEXT,
            default_shipping_postal_code TEXT,
            default_billing_country TEXT,
            default_billing_line1 TEXT,
            default_billing_line2 TEXT,
            default_billing_city TEXT,
            default_billing_region TEXT,
            default_billing_postal_code TEXT,
            email_verified_at TEXT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        );
    ");

    // Lightweight "migration" for existing DBs: add missing columns (SQLite)
    $cols = [];
    foreach ($pdo->query("PRAGMA table_info(users);") as $row) {
        if (is_array($row) && isset($row['name'])) $cols[(string)$row['name']] = true;
    }
    $addCols = [
        'default_shipping_country' => 'TEXT',
        'default_shipping_line1' => 'TEXT',
        'default_shipping_line2' => 'TEXT',
        'default_shipping_city' => 'TEXT',
        'default_shipping_region' => 'TEXT',
        'default_shipping_postal_code' => 'TEXT',
        'default_billing_country' => 'TEXT',
        'default_billing_line1' => 'TEXT',
        'default_billing_line2' => 'TEXT',
        'default_billing_city' => 'TEXT',
        'default_billing_region' => 'TEXT',
        'default_billing_postal_code' => 'TEXT',
    ];
    foreach ($addCols as $name => $type) {
        if (!isset($cols[$name])) {
            $pdo->exec("ALTER TABLE users ADD COLUMN {$name} {$type};");
        }
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS email_verification_tokens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            token_hash TEXT NOT NULL,
            expires_at TEXT NOT NULL,
            created_at TEXT NOT NULL,
            used_at TEXT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            token_hash TEXT NOT NULL,
            expires_at TEXT NOT NULL,
            created_at TEXT NOT NULL,
            used_at TEXT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT,
            ip TEXT,
            success INTEGER NOT NULL,
            created_at TEXT NOT NULL
        );
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_ref TEXT NOT NULL UNIQUE,
            user_id INTEGER NOT NULL,
            status TEXT NOT NULL,
            currency TEXT NOT NULL,
            subtotal REAL NOT NULL,
            shipping REAL NOT NULL,
            taxes REAL NOT NULL,
            tax_rate REAL NOT NULL,
            total REAL NOT NULL,
            shipping_country TEXT NOT NULL,
            shipping_line1 TEXT NOT NULL,
            shipping_line2 TEXT,
            shipping_city TEXT NOT NULL,
            shipping_region TEXT,
            shipping_postal_code TEXT NOT NULL,
            delivery_method TEXT NOT NULL,
            delivery_estimate_days TEXT NOT NULL,
            payment_method TEXT NOT NULL,
            payment_card_last4 TEXT,
            created_at TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
        );
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            sku TEXT NOT NULL,
            name TEXT NOT NULL,
            unit TEXT,
            unit_price REAL NOT NULL,
            qty INTEGER NOT NULL,
            line_total REAL NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        );
    ");
}


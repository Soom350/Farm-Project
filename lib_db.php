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
        'is_admin' => 'INTEGER NOT NULL DEFAULT 0',
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

    $orderCols = [];
    foreach ($pdo->query("PRAGMA table_info(orders);") as $row) {
        if (is_array($row) && isset($row['name'])) $orderCols[(string)$row['name']] = true;
    }
    $orderAddCols = [
        'stripe_checkout_session_id' => 'TEXT',
        'stripe_payment_intent_id' => 'TEXT',
    ];
    foreach ($orderAddCols as $name => $type) {
        if (!isset($orderCols[$name])) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN {$name} {$type};");
        }
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id TEXT NOT NULL UNIQUE,
            sku TEXT NOT NULL UNIQUE,
            name TEXT NOT NULL,
            short_description TEXT,
            description TEXT,
            price REAL NOT NULL,
            currency TEXT NOT NULL DEFAULT 'USD',
            unit TEXT,
            availability TEXT NOT NULL DEFAULT 'in_stock',
            stock_qty INTEGER NOT NULL DEFAULT 0,
            category_slugs TEXT,
            tags TEXT,
            shipping_eligible INTEGER NOT NULL DEFAULT 1,
            weight_kg REAL,
            image TEXT,
            specs_json TEXT,
            compliance_json TEXT,
            status TEXT NOT NULL DEFAULT 'active',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        );
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS blog_posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            post_id TEXT NOT NULL UNIQUE,
            slug TEXT NOT NULL UNIQUE,
            title TEXT NOT NULL,
            excerpt TEXT,
            content TEXT,
            category TEXT,
            author TEXT,
            published_at TEXT,
            read_minutes INTEGER NOT NULL DEFAULT 1,
            views INTEGER NOT NULL DEFAULT 0,
            comments INTEGER NOT NULL DEFAULT 0,
            image TEXT,
            image_alt TEXT,
            status TEXT NOT NULL DEFAULT 'draft',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        );
    ");

    db_seed_defaults($pdo);

    db_add_column_if_missing($pdo, 'blog_posts', 'video_url', 'TEXT');
}

function db_seed_defaults(PDO $pdo): void
{
    $productCount = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    if ($productCount === 0) {
        require_once __DIR__ . '/lib_catalog.php';
        $now = gmdate('c');
        $stmt = $pdo->prepare("
            INSERT INTO products(
                product_id, sku, name, short_description, description,
                price, currency, unit, availability, stock_qty,
                category_slugs, tags, shipping_eligible, weight_kg, image,
                specs_json, compliance_json, status, created_at, updated_at
            ) VALUES (
                :pid, :sku, :name, :short, :desc,
                :price, :currency, :unit, :avail, :stock,
                :cats, :tags, :ship, :weight, :image,
                :specs, :compliance, 'active', :created, :updated
            )
        ");
        foreach (catalog_products_seed() as $p) {
            $stmt->execute([
                ':pid' => (string)$p['id'],
                ':sku' => (string)$p['sku'],
                ':name' => (string)$p['name'],
                ':short' => (string)($p['short_description'] ?? ''),
                ':desc' => (string)($p['description'] ?? ''),
                ':price' => (float)($p['price'] ?? 0),
                ':currency' => (string)($p['currency'] ?? 'USD'),
                ':unit' => (string)($p['unit'] ?? ''),
                ':avail' => (string)($p['availability'] ?? 'in_stock'),
                ':stock' => (int)($p['stock_qty'] ?? 0),
                ':cats' => json_encode((array)($p['category_slugs'] ?? []), JSON_UNESCAPED_UNICODE),
                ':tags' => json_encode((array)($p['tags'] ?? []), JSON_UNESCAPED_UNICODE),
                ':ship' => !empty($p['shipping_eligible']) ? 1 : 0,
                ':weight' => isset($p['weight_kg']) ? (float)$p['weight_kg'] : null,
                ':image' => (string)($p['image'] ?? ''),
                ':specs' => json_encode((array)($p['specs'] ?? []), JSON_UNESCAPED_UNICODE),
                ':compliance' => json_encode((array)($p['compliance'] ?? []), JSON_UNESCAPED_UNICODE),
                ':created' => $now,
                ':updated' => $now,
            ]);
        }
    }

    $blogCount = (int)$pdo->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn();
    if ($blogCount === 0) {
        require_once __DIR__ . '/lib_blog.php';
        $now = gmdate('c');
        $stmt = $pdo->prepare("
            INSERT INTO blog_posts(
                post_id, slug, title, excerpt, content, category, author,
                published_at, read_minutes, views, comments, image, image_alt,
                status, created_at, updated_at
            ) VALUES (
                :pid, :slug, :title, :excerpt, :content, :category, :author,
                :published, :read, :views, :comments, :image, :alt,
                'published', :created, :updated
            )
        ");
        foreach (blog_posts_seed() as $post) {
            $stmt->execute([
                ':pid' => (string)$post['id'],
                ':slug' => (string)$post['slug'],
                ':title' => (string)$post['title'],
                ':excerpt' => (string)($post['excerpt'] ?? ''),
                ':content' => (string)($post['excerpt'] ?? ''),
                ':category' => (string)($post['category'] ?? ''),
                ':author' => (string)($post['author'] ?? 'Admin'),
                ':published' => (string)($post['published_at'] ?? gmdate('Y-m-d')),
                ':read' => (int)($post['read_minutes'] ?? 1),
                ':views' => (int)($post['views'] ?? 0),
                ':comments' => (int)($post['comments'] ?? 0),
                ':image' => (string)($post['image'] ?? ''),
                ':alt' => (string)($post['image_alt'] ?? ''),
                ':created' => $now,
                ':updated' => $now,
            ]);
        }
    }
}

function db_add_column_if_missing(PDO $pdo, string $table, string $name, string $type): void
{
    $cols = [];
    foreach ($pdo->query("PRAGMA table_info({$table});") as $row) {
        if (is_array($row) && isset($row['name'])) $cols[(string)$row['name']] = true;
    }
    if (!isset($cols[$name])) {
        $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$name} {$type};");
    }
}


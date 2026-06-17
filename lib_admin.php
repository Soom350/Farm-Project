<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_auth.php';
require_once __DIR__ . '/lib_catalog.php';
require_once __DIR__ . '/lib_blog.php';
require_once __DIR__ . '/lib_orders.php';
require_once __DIR__ . '/lib_checkout.php';

function auth_is_admin(?array $user = null): bool
{
    if (app_frontend_only()) return true;

    $user = $user ?? auth_user();
    if (!$user) return false;

    return (int)($user['is_admin'] ?? 0) === 1;
}

function admin_user(): ?array
{
    if (app_frontend_only()) {
        return [
            'id' => 0,
            'email' => 'admin@timbuktu-farming.test',
            'full_name' => 'Administrateur Demo',
            'is_admin' => 1,
        ];
    }

    $u = auth_user();
    return auth_is_admin($u) ? $u : null;
}

function admin_require_access(string $next): void
{
    if (app_frontend_only()) return;

    if (!auth_is_logged_in()) {
        redirect(url_with_params(app_url('auth/login.php'), ['next' => $next]));
    }

    if (!auth_is_admin()) {
        http_response_code(403);
        echo 'Acces admin refuse.';
        exit;
    }
}

function admin_url(string $path = 'index.php'): string
{
    return app_url('admin/' . ltrim($path, '/'));
}

function admin_nav_items(): array
{
    return [
        'index' => ['label' => 'Tableau de bord', 'href' => admin_url('index.php')],
        'products' => ['label' => 'Produits', 'href' => admin_url('products.php')],
        'blog' => ['label' => 'Blog', 'href' => admin_url('blog.php')],
        'orders' => ['label' => 'Commandes', 'href' => admin_url('orders.php')],
    ];
}

function admin_order_statuses(): array
{
    return [
        '' => 'Tous',
        'processing' => 'En traitement',
        'pending_payment' => 'Paiement en attente',
        'paid' => 'Payee',
        'shipped' => 'Expediee',
        'delivered' => 'Livree',
        'cancelled' => 'Annulee',
    ];
}

function admin_order_groups(): array
{
    return [
        'active' => [
            'label' => 'En cours',
            'statuses' => ['processing', 'pending_payment'],
        ],
        'validated' => [
            'label' => 'Validees',
            'statuses' => ['paid', 'shipped', 'delivered'],
        ],
        'cancelled' => [
            'label' => 'Annulees',
            'statuses' => ['cancelled'],
        ],
        'all' => [
            'label' => 'Historique',
            'statuses' => [],
        ],
    ];
}

function admin_order_group_statuses(string $group): array
{
    $groups = admin_order_groups();
    return (array)($groups[$group]['statuses'] ?? []);
}

function admin_country_label(string $code): string
{
    $code = strtoupper(trim($code));
    $countries = checkout_countries();
    return (string)($countries[$code] ?? $code);
}

function admin_dashboard_stats(): array
{
    if (app_frontend_only()) {
        return [
            'orders_count' => 0,
            'orders_pending' => 0,
            'revenue_total' => 0.0,
            'products_count' => count(catalog_products(true)),
            'blog_posts_count' => count(blog_posts_all(true)),
        ];
    }

    $pdo = db();
    $ordersCount = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    $ordersPending = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('processing', 'pending_payment')")->fetchColumn();
    $revenue = (float)$pdo->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status IN ('paid', 'shipped', 'delivered')")->fetchColumn();

    return [
        'orders_count' => $ordersCount,
        'orders_pending' => $ordersPending,
        'revenue_total' => $revenue,
        'products_count' => count(catalog_products(true)),
        'blog_posts_count' => count(blog_posts_all(true)),
    ];
}

function admin_orders_search(array $filters, int $limit, int $offset): array
{
    if (app_frontend_only()) return [];

    $limit = max(1, min(100, $limit));
    $offset = max(0, $offset);

    $where = ['1=1'];
    $params = [];

    $group = trim((string)($filters['group'] ?? ''));
    $groupStatuses = $group !== '' ? admin_order_group_statuses($group) : [];
    if ($groupStatuses) {
        $placeholders = [];
        foreach ($groupStatuses as $i => $st) {
            $key = ':gst' . $i;
            $placeholders[] = $key;
            $params[$key] = $st;
        }
        $where[] = 'o.status IN (' . implode(', ', $placeholders) . ')';
    }

    $status = trim((string)($filters['status'] ?? ''));
    if ($status !== '') {
        $where[] = 'o.status = :status';
        $params[':status'] = $status;
    }

    $q = trim((string)($filters['q'] ?? ''));
    if ($q !== '') {
        $where[] = '(o.order_ref LIKE :q OR u.email LIKE :q OR u.full_name LIKE :q OR u.phone LIKE :q)';
        $params[':q'] = '%' . $q . '%';
    }

    $sqlWhere = implode(' AND ', $where);
    $stmt = db()->prepare("
        SELECT o.*, u.email AS customer_email, u.full_name AS customer_name, u.phone AS customer_phone, u.id AS customer_user_id
        FROM orders o
        LEFT JOIN users u ON u.id = o.user_id
        WHERE {$sqlWhere}
        ORDER BY o.id DESC
        LIMIT {$limit} OFFSET {$offset}
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    return is_array($rows) ? $rows : [];
}

function admin_orders_count(array $filters): int
{
    if (app_frontend_only()) return 0;

    $where = ['1=1'];
    $params = [];

    $group = trim((string)($filters['group'] ?? ''));
    $groupStatuses = $group !== '' ? admin_order_group_statuses($group) : [];
    if ($groupStatuses) {
        $placeholders = [];
        foreach ($groupStatuses as $i => $st) {
            $key = ':gst' . $i;
            $placeholders[] = $key;
            $params[$key] = $st;
        }
        $where[] = 'o.status IN (' . implode(', ', $placeholders) . ')';
    }

    $status = trim((string)($filters['status'] ?? ''));
    if ($status !== '') {
        $where[] = 'o.status = :status';
        $params[':status'] = $status;
    }

    $q = trim((string)($filters['q'] ?? ''));
    if ($q !== '') {
        $where[] = '(o.order_ref LIKE :q OR u.email LIKE :q OR u.full_name LIKE :q OR u.phone LIKE :q)';
        $params[':q'] = '%' . $q . '%';
    }

    $sqlWhere = implode(' AND ', $where);
    $stmt = db()->prepare("
        SELECT COUNT(*) AS c
        FROM orders o
        LEFT JOIN users u ON u.id = o.user_id
        WHERE {$sqlWhere}
    ");
    $stmt->execute($params);
    $row = $stmt->fetch();

    return is_array($row) ? (int)($row['c'] ?? 0) : 0;
}

function admin_orders_group_counts(): array
{
    if (app_frontend_only()) {
        return ['active' => 0, 'validated' => 0, 'cancelled' => 0, 'all' => 0];
    }

    $counts = [];
    foreach (admin_order_groups() as $key => $group) {
        $counts[$key] = admin_orders_count(['group' => $key]);
    }

    return $counts;
}

function admin_order_get_by_ref(string $orderRef): ?array
{
    if (app_frontend_only()) return null;

    $orderRef = trim($orderRef);
    if ($orderRef === '') return null;

    $stmt = db()->prepare("
        SELECT o.*, u.email AS customer_email, u.full_name AS customer_name, u.phone AS customer_phone,
               u.id AS customer_user_id, u.company_name AS customer_company
        FROM orders o
        LEFT JOIN users u ON u.id = o.user_id
        WHERE o.order_ref = :ref
        LIMIT 1
    ");
    $stmt->execute([':ref' => $orderRef]);
    $row = $stmt->fetch();

    return is_array($row) ? $row : null;
}

function admin_order_update_status(int $orderId, string $status): array
{
    if (app_frontend_only()) {
        return ['ok' => false, 'errors' => ['Mode design: mise a jour de commande desactivee.']];
    }

    $status = trim($status);
    $allowed = array_values(array_filter(array_keys(admin_order_statuses()), static fn(string $k): bool => $k !== ''));
    if (!in_array($status, $allowed, true)) {
        return ['ok' => false, 'errors' => ['Statut invalide.']];
    }

    $stmt = db()->prepare('UPDATE orders SET status = :status WHERE id = :id LIMIT 1');
    $stmt->execute([
        ':status' => $status,
        ':id' => $orderId,
    ]);

    return ['ok' => true];
}

function admin_availability_label(string $availability): string
{
    return catalog_availability_options()[$availability] ?? $availability;
}

function admin_product_status_label(string $status): string
{
    return catalog_product_status_options()[$status] ?? $status;
}

function admin_blog_status_label(string $status): string
{
    return blog_status_options()[$status] ?? $status;
}

function admin_preview_store(string $type, array $data): void
{
    $_SESSION['_admin_preview'] = [
        'type' => $type,
        'data' => $data,
        'at' => time(),
    ];
}

function admin_preview_get(): ?array
{
    $preview = $_SESSION['_admin_preview'] ?? null;
    if (!is_array($preview)) return null;
    if ((time() - (int)($preview['at'] ?? 0)) > 3600) {
        unset($_SESSION['_admin_preview']);
        return null;
    }
    return $preview;
}

function admin_preview_product_from_input(array $input): array
{
    $categories = array_values(array_filter(array_map('trim', explode(',', (string)($input['category_slugs'] ?? '')))));
    $tags = array_values(array_filter(array_map('trim', explode(',', (string)($input['tags'] ?? '')))));

    return [
        'id' => (string)($input['product_id'] ?? 'preview'),
        'sku' => (string)($input['sku'] ?? 'PREVIEW'),
        'name' => (string)($input['name'] ?? 'Produit preview'),
        'short_description' => (string)($input['short_description'] ?? ''),
        'description' => (string)($input['description'] ?? ''),
        'price' => (float)($input['price'] ?? 0),
        'currency' => (string)($input['currency'] ?? 'USD'),
        'unit' => (string)($input['unit'] ?? ''),
        'availability' => (string)($input['availability'] ?? 'in_stock'),
        'stock_qty' => (int)($input['stock_qty'] ?? 0),
        'category_slugs' => $categories,
        'tags' => $tags,
        'shipping_eligible' => !empty($input['shipping_eligible']),
        'weight_kg' => ($input['weight_kg'] ?? '') !== '' ? (float)$input['weight_kg'] : null,
        'image' => (string)($input['image'] ?? ''),
        'status' => (string)($input['status'] ?? 'inactive'),
    ];
}

function admin_preview_blog_from_input(array $input): array
{
    $slug = trim((string)($input['slug'] ?? ''));
    if ($slug === '' && ($input['title'] ?? '') !== '') {
        $slug = blog_slugify((string)$input['title']);
    }

    return [
        'id' => (string)($input['post_id'] ?? 'preview'),
        'slug' => $slug !== '' ? $slug : 'preview',
        'title' => (string)($input['title'] ?? 'Article preview'),
        'excerpt' => (string)($input['excerpt'] ?? ''),
        'content' => (string)(($input['content'] ?? '') !== '' ? $input['content'] : ($input['excerpt'] ?? '')),
        'category' => (string)($input['category'] ?? ''),
        'author' => (string)($input['author'] ?? 'Admin'),
        'published_at' => (string)($input['published_at'] ?? gmdate('Y-m-d')),
        'read_minutes' => max(1, (int)($input['read_minutes'] ?? 1)),
        'views' => 0,
        'comments' => 0,
        'image' => (string)($input['image'] ?? ''),
        'image_alt' => (string)(($input['image_alt'] ?? '') !== '' ? $input['image_alt'] : ($input['title'] ?? '')),
        'video_url' => (string)($input['video_url'] ?? ''),
        'status' => (string)($input['status'] ?? 'draft'),
    ];
}

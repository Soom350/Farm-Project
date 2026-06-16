<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_db.php';
require_once __DIR__ . '/lib_auth.php';

function orders_list_for_user(int $userId, int $limit = 20): array
{
    if (app_frontend_only()) return [];
    $limit = max(1, min(100, $limit));
    $stmt = db()->prepare("SELECT * FROM orders WHERE user_id = :uid ORDER BY id DESC LIMIT {$limit}");
    $stmt->execute([':uid' => $userId]);
    $rows = $stmt->fetchAll();
    return is_array($rows) ? $rows : [];
}

function order_get_for_user_by_ref(int $userId, string $orderRef): ?array
{
    if (app_frontend_only()) return null;
    $stmt = db()->prepare('SELECT * FROM orders WHERE user_id = :uid AND order_ref = :ref LIMIT 1');
    $stmt->execute([':uid' => $userId, ':ref' => $orderRef]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function order_items(int $orderId): array
{
    if (app_frontend_only()) return [];
    $stmt = db()->prepare('SELECT * FROM order_items WHERE order_id = :oid ORDER BY id ASC');
    $stmt->execute([':oid' => $orderId]);
    $rows = $stmt->fetchAll();
    return is_array($rows) ? $rows : [];
}


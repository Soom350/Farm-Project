<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_db.php';
require_once __DIR__ . '/lib_auth.php';

/**
 * Account helpers (profile, addresses, orders).
 *
 * Notes:
 * - In APP_FRONTEND_ONLY mode, these helpers don't touch the DB.
 * - All callers must enforce auth + ownership (userId comes from session).
 */

function account_update_profile(int $userId, array $input): array
{
    if (app_frontend_only()) {
        return ['ok' => false, 'errors' => ['Mode design: modification du profil désactivée.']];
    }

    $fullName = trim((string)($input['full_name'] ?? ''));
    $phone = trim((string)($input['phone'] ?? ''));
    $company = trim((string)($input['company_name'] ?? ''));

    $errors = [];
    if ($fullName === '') $errors['full_name'] = 'Nom complet requis.';
    if ($phone === '') $errors['phone'] = 'Téléphone requis.';

    if ($errors) return ['ok' => false, 'errors' => $errors];

    $stmt = db()->prepare("
        UPDATE users
        SET full_name = :full_name,
            phone = :phone,
            company_name = :company,
            updated_at = :now
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([
        ':full_name' => $fullName,
        ':phone' => $phone,
        ':company' => $company !== '' ? $company : null,
        ':now' => gmdate('c'),
        ':id' => $userId,
    ]);

    return ['ok' => true];
}

function account_change_password(int $userId, string $currentPassword, string $newPassword): array
{
    if (app_frontend_only()) {
        return ['ok' => false, 'errors' => ['Mode design: changement de mot de passe désactivé.']];
    }

    $currentPassword = (string)$currentPassword;
    $newPassword = (string)$newPassword;

    $errors = [];
    if ($currentPassword === '') $errors['current_password'] = 'Mot de passe actuel requis.';
    if ($newPassword === '') $errors['new_password'] = 'Nouveau mot de passe requis.';
    foreach (auth_password_policy_errors($newPassword) as $msg) {
        $errors['new_password'] = ($errors['new_password'] ?? '') . ($errors['new_password'] ? ' ' : '') . $msg;
    }
    if ($errors) return ['ok' => false, 'errors' => $errors];

    $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $row = $stmt->fetch();
    $hash = is_array($row) ? (string)($row['password_hash'] ?? '') : '';
    if ($hash === '' || !password_verify($currentPassword, $hash)) {
        return ['ok' => false, 'errors' => ['current_password' => 'Mot de passe actuel incorrect.']];
    }

    $stmt = db()->prepare('UPDATE users SET password_hash = :ph, updated_at = :now WHERE id = :id LIMIT 1');
    $stmt->execute([
        ':ph' => password_hash($newPassword, PASSWORD_DEFAULT),
        ':now' => gmdate('c'),
        ':id' => $userId,
    ]);

    // Extra safety: rotate session id after credential change
    session_regenerate_id(true);

    return ['ok' => true];
}

function account_update_default_addresses(int $userId, array $shipping, array $billing): array
{
    if (app_frontend_only()) {
        return ['ok' => false, 'errors' => ['Mode design: modification des adresses désactivée.']];
    }

    // Normalisation légère (la validation métier est faite côté page via checkout_validate_address + pays).
    $ship = [
        'country' => strtoupper(trim((string)($shipping['country'] ?? ''))),
        'line1' => trim((string)($shipping['line1'] ?? '')),
        'line2' => trim((string)($shipping['line2'] ?? '')),
        'city' => trim((string)($shipping['city'] ?? '')),
        'region' => trim((string)($shipping['region'] ?? '')),
        'postal_code' => trim((string)($shipping['postal_code'] ?? '')),
    ];
    $bill = [
        'country' => strtoupper(trim((string)($billing['country'] ?? ''))),
        'line1' => trim((string)($billing['line1'] ?? '')),
        'line2' => trim((string)($billing['line2'] ?? '')),
        'city' => trim((string)($billing['city'] ?? '')),
        'region' => trim((string)($billing['region'] ?? '')),
        'postal_code' => trim((string)($billing['postal_code'] ?? '')),
    ];

    $stmt = db()->prepare("
        UPDATE users
        SET
            default_shipping_country = :sc,
            default_shipping_line1 = :sl1,
            default_shipping_line2 = :sl2,
            default_shipping_city = :sct,
            default_shipping_region = :sr,
            default_shipping_postal_code = :spc,
            default_billing_country = :bc,
            default_billing_line1 = :bl1,
            default_billing_line2 = :bl2,
            default_billing_city = :bct,
            default_billing_region = :br,
            default_billing_postal_code = :bpc,
            updated_at = :now
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([
        ':sc' => $ship['country'],
        ':sl1' => $ship['line1'],
        ':sl2' => $ship['line2'] !== '' ? $ship['line2'] : null,
        ':sct' => $ship['city'],
        ':sr' => $ship['region'] !== '' ? $ship['region'] : null,
        ':spc' => $ship['postal_code'],
        ':bc' => $bill['country'],
        ':bl1' => $bill['line1'],
        ':bl2' => $bill['line2'] !== '' ? $bill['line2'] : null,
        ':bct' => $bill['city'],
        ':br' => $bill['region'] !== '' ? $bill['region'] : null,
        ':bpc' => $bill['postal_code'],
        ':now' => gmdate('c'),
        ':id' => $userId,
    ]);

    return ['ok' => true];
}

function account_dashboard_stats(int $userId): array
{
    if (app_frontend_only()) {
        return [
            'orders_count' => 0,
            'total_spent' => 0.0,
            'last_order' => null,
        ];
    }

    $pdo = db();
    $stmt = $pdo->prepare('SELECT COUNT(*) AS c, COALESCE(SUM(total), 0) AS s FROM orders WHERE user_id = :uid');
    $stmt->execute([':uid' => $userId]);
    $row = $stmt->fetch();

    $ordersCount = is_array($row) ? (int)($row['c'] ?? 0) : 0;
    $totalSpent = is_array($row) ? (float)($row['s'] ?? 0.0) : 0.0;

    $stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = :uid ORDER BY id DESC LIMIT 1');
    $stmt->execute([':uid' => $userId]);
    $last = $stmt->fetch();

    return [
        'orders_count' => $ordersCount,
        'total_spent' => $totalSpent,
        'last_order' => is_array($last) ? $last : null,
    ];
}

function account_orders_search(int $userId, array $filters, int $limit, int $offset): array
{
    if (app_frontend_only()) return [];

    $limit = max(1, min(100, $limit));
    $offset = max(0, $offset);

    $where = ['user_id = :uid'];
    $params = [':uid' => $userId];

    $status = trim((string)($filters['status'] ?? ''));
    if ($status !== '') {
        $where[] = 'status = :status';
        $params[':status'] = $status;
    }

    $q = trim((string)($filters['q'] ?? ''));
    if ($q !== '') {
        $where[] = 'order_ref LIKE :q';
        $params[':q'] = '%' . $q . '%';
    }

    $sqlWhere = implode(' AND ', $where);
    $stmt = db()->prepare("
        SELECT *
        FROM orders
        WHERE {$sqlWhere}
        ORDER BY id DESC
        LIMIT {$limit} OFFSET {$offset}
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    return is_array($rows) ? $rows : [];
}

function account_orders_count(int $userId, array $filters): int
{
    if (app_frontend_only()) return 0;

    $where = ['user_id = :uid'];
    $params = [':uid' => $userId];

    $status = trim((string)($filters['status'] ?? ''));
    if ($status !== '') {
        $where[] = 'status = :status';
        $params[':status'] = $status;
    }

    $q = trim((string)($filters['q'] ?? ''));
    if ($q !== '') {
        $where[] = 'order_ref LIKE :q';
        $params[':q'] = '%' . $q . '%';
    }

    $sqlWhere = implode(' AND ', $where);
    $stmt = db()->prepare("SELECT COUNT(*) AS c FROM orders WHERE {$sqlWhere}");
    $stmt->execute($params);
    $row = $stmt->fetch();
    return is_array($row) ? (int)($row['c'] ?? 0) : 0;
}


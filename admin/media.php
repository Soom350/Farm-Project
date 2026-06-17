<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib_admin.php';
require_once dirname(__DIR__) . '/lib_media.php';

header('Content-Type: application/json; charset=UTF-8');

admin_require_access('admin/media.php');
if (!admin_user()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acces refuse']);
    exit;
}

$action = (string)($_GET['action'] ?? $_POST['action'] ?? 'list');

if ($action === 'list') {
    echo json_encode(['ok' => true, 'items' => media_list_images()], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'upload' && is_post()) {
    require_csrf();
    $result = media_upload($_FILES['file'] ?? []);
    if ($result['ok'] ?? false) {
        echo json_encode([
            'ok' => true,
            'path' => (string)$result['path'],
            'url' => (string)$result['url'],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => implode(' ', (array)($result['errors'] ?? ['Upload impossible.'])),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Action invalide']);

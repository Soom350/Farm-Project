<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_bootstrap.php';

function media_upload_dir(): string
{
    return __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'uploads';
}

function media_upload_rel_dir(): string
{
    return 'data/uploads';
}

function media_scan_dirs(): array
{
    return [
        'image',
        'logo_slide_img',
        media_upload_rel_dir(),
    ];
}

function media_allowed_extensions(): array
{
    return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
}

function media_is_allowed_path(string $relativePath): bool
{
    $relativePath = str_replace('\\', '/', trim($relativePath));
    if ($relativePath === '' || str_contains($relativePath, '..')) return false;

    $ext = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
    if (!in_array($ext, media_allowed_extensions(), true)) return false;

    foreach (media_scan_dirs() as $dir) {
        if ($relativePath === $dir || str_starts_with($relativePath, $dir . '/')) {
            $full = realpath(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
            $root = realpath(__DIR__);
            if ($full && $root && str_starts_with($full, $root)) {
                return is_file($full);
            }
        }
    }

    return false;
}

function media_url(string $relativePath): string
{
    return app_url(ltrim(str_replace('\\', '/', $relativePath), '/'));
}

/** @return list<array{path:string,url:string,name:string}> */
function media_list_images(): array
{
    $items = [];
    $seen = [];

    foreach (media_scan_dirs() as $dir) {
        $fullDir = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dir);
        if (!is_dir($fullDir)) continue;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($fullDir, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;
            $ext = strtolower($file->getExtension());
            if (!in_array($ext, media_allowed_extensions(), true)) continue;

            $full = $file->getPathname();
            $rel = ltrim(str_replace('\\', '/', substr($full, strlen(__DIR__))), '/');
            if (isset($seen[$rel])) continue;
            $seen[$rel] = true;

            $items[] = [
                'path' => $rel,
                'url' => media_url($rel),
                'name' => basename($rel),
            ];
        }
    }

    usort($items, static fn(array $a, array $b): int => strcmp($a['path'], $b['path']));

    return $items;
}

function media_upload(array $file): array
{
    if (app_frontend_only()) {
        return ['ok' => false, 'errors' => ['Mode design: upload desactive.']];
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'errors' => ['Upload echoue.']];
    }

    $tmp = (string)($file['tmp_name'] ?? '');
    $original = (string)($file['name'] ?? '');
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));

    if (!in_array($ext, media_allowed_extensions(), true)) {
        return ['ok' => false, 'errors' => ['Format non autorise.']];
    }

    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > 5 * 1024 * 1024) {
        return ['ok' => false, 'errors' => ['Fichier trop volumineux (max 5 Mo).']];
    }

    $dir = media_upload_dir();
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $base = preg_replace('/[^a-z0-9-]+/', '-', strtolower(pathinfo($original, PATHINFO_FILENAME))) ?: 'image';
    $filename = $base . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($tmp, $dest)) {
        return ['ok' => false, 'errors' => ['Impossible d enregistrer le fichier.']];
    }

    $rel = media_upload_rel_dir() . '/' . $filename;

    return [
        'ok' => true,
        'path' => $rel,
        'url' => media_url($rel),
    ];
}

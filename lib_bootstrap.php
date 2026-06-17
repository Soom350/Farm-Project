<?php
declare(strict_types=1);

// Bootstrap minimal for PHP "e-commerce" pages.
// - Session + cart
// - CSRF
// - Helpers output (XSS-safe)

if (session_status() !== PHP_SESSION_ACTIVE) {
    // Reasonable session cookie (to adapt in prod with HTTPS).
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_start();
}

// Defaults
$_SESSION['currency'] = $_SESSION['currency'] ?? 'USD';
$_SESSION['locale'] = $_SESSION['locale'] ?? 'en-US';

/**
* Mode "design only" (frontend only).
 * - true  => disable temporarily the business logic (auth/DB/checkout) to work on the design.
 * - false => reactivate the back afterwards.
 */
if (!defined('APP_FRONTEND_ONLY')) {
    define('APP_FRONTEND_ONLY', true);
}

function app_frontend_only(): bool
{
    return (bool)APP_FRONTEND_ONLY;
}

// Session idle timeout (basic hardening)
$now = time();
if (isset($_SESSION['_last_activity']) && is_int($_SESSION['_last_activity'])) {
    $idleSeconds = $now - $_SESSION['_last_activity'];
    if ($idleSeconds > 60 * 45) { // 45 minutes
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}
$_SESSION['_last_activity'] = $now;

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Base URL path of this project relative to the web server document root.
 *
 * Examples:
 * - If DOCUMENT_ROOT = C:\Sites and project is C:\Sites\Farm_project → "/Farm_project"
 * - If project is served as document root → ""
 */
function app_base_path(): string
{
    $docRoot = (string)($_SERVER['DOCUMENT_ROOT'] ?? '');
    $docRoot = rtrim(str_replace('\\', '/', $docRoot), '/');

    // lib_bootstrap.php lives in the project root folder
    $projectRoot = realpath(__DIR__) ?: __DIR__;
    $projectRoot = rtrim(str_replace('\\', '/', $projectRoot), '/');

    if ($docRoot !== '' && str_starts_with($projectRoot, $docRoot)) {
        $rel = substr($projectRoot, strlen($docRoot));
        $rel = '/' . ltrim((string)$rel, '/');
        return $rel === '/' ? '' : $rel;
    }

    // Fallback: map filesystem path to URL path using SCRIPT_FILENAME + SCRIPT_NAME
    $scriptFilename = (string)($_SERVER['SCRIPT_FILENAME'] ?? '');
    $scriptFilename = str_replace('\\', '/', $scriptFilename);
    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));

    if ($scriptFilename !== '' && $scriptName !== '' && str_starts_with($scriptFilename, $projectRoot . '/')) {
        $relFs = substr($scriptFilename, strlen($projectRoot)); // e.g. "/auth/login.php"
        $relFs = str_replace('\\', '/', (string)$relFs);
        if ($relFs !== '' && str_ends_with($scriptName, $relFs)) {
            $base = substr($scriptName, 0, -strlen($relFs));
            $base = rtrim((string)$base, '/');
            return $base;
        }
    }

    // Last resort: best effort based on script directory (may be too deep)
    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $dir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    return $dir === '' ? '' : $dir;
}

/** Build a URL within this project (handles subfolders). */
function app_url(string $path): string
{
    $base = app_base_path();
    return ($base !== '' ? $base : '') . '/' . ltrim($path, '/');
}

function url_with_params(string $path, array $params = []): string
{
    if (!$params) return $path;
    $qs = http_build_query($params);
    return $path . (str_contains($path, '?') ? '&' : '?') . $qs;
}

function money(float $amount, ?string $currency = null, ?string $locale = null): string
{
    $currency = $currency ?? ($_SESSION['currency'] ?? 'USD');
    $locale = $locale ?? ($_SESSION['locale'] ?? 'en-US');

    // intl may not be activated everywhere; fallback proper.
    if (class_exists(\NumberFormatter::class)) {
        try {
            $fmt = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
            $fmt->setTextAttribute(\NumberFormatter::CURRENCY_CODE, $currency);
            $out = $fmt->formatCurrency($amount, $currency);
            if (is_string($out)) return $out;
        } catch (\Throwable) {
            // ignore and fallback
        }
    }
    return $currency . ' ' . number_format($amount, 2, '.', ' ');
}

function csrf_token(): string
{
    if (!isset($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_meta_tag(): string
{
    return '<meta name="csrf-token" content="' . h(csrf_token()) . '">';
}

function require_csrf(): void
{
    $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        echo 'CSRF invalid or expired.';
        exit;
    }
}

function is_post(): bool
{
    return (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST');
}

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

function flash_set(string $key, string $message): void
{
    $_SESSION['_flash'] = is_array($_SESSION['_flash'] ?? null) ? $_SESSION['_flash'] : [];
    $_SESSION['_flash'][$key] = $message;
}

function flash_get(string $key): ?string
{
    if (!is_array($_SESSION['_flash'] ?? null)) return null;
    if (!array_key_exists($key, $_SESSION['_flash'])) return null;
    $msg = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return is_string($msg) ? $msg : null;
}

function current_url_path(): string
{
    $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
    $path = parse_url($uri, PHP_URL_PATH);
    return is_string($path) && $path !== '' ? $path : '/';
}

function safe_next_url(string $fallback = 'index.php'): string
{
    $next = trim((string)($_GET['next'] ?? $_POST['next'] ?? ''));
    if ($next === '') return $fallback;
    // Only allow relative paths within the site (no scheme, no host).
    if (preg_match('/^\w+:\/\//', $next)) return $fallback;
    if (str_starts_with($next, '//')) return $fallback;
    return $next;
}

require_once __DIR__ . '/lib_config.php';
require_once __DIR__ . '/lib_plugins.php';
plugin_load_all();


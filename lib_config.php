<?php
declare(strict_types=1);

/**
 * Charge .env (KEY=VALUE) depuis la racine du projet.
 */
function app_env(string $key, ?string $default = null): ?string
{
    static $loaded = false;
    static $env = [];

    if (!$loaded) {
        $loaded = true;
        $path = __DIR__ . DIRECTORY_SEPARATOR . '.env';
        if (is_file($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES);
            if (is_array($lines)) {
                foreach ($lines as $line) {
                    $line = trim((string)$line);
                    if ($line === '' || str_starts_with($line, '#')) continue;
                    if (!str_contains($line, '=')) continue;
                    [$k, $v] = explode('=', $line, 2);
                    $env[trim($k)] = trim($v, " \t\"'");
                }
            }
        }
    }

    if (array_key_exists($key, $env)) {
        $value = $env[$key];
        return $value === '' ? $default : $value;
    }

    $fromServer = getenv($key);
    if ($fromServer !== false && $fromServer !== '') {
        return (string)$fromServer;
    }

    return $default;
}

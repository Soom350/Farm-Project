<?php
declare(strict_types=1);

function plugins_enabled(): array
{
    static $enabled = null;
    if ($enabled !== null) return $enabled;

    $configPath = __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'plugins.php';
    $enabled = is_file($configPath) ? require $configPath : [];
    if (!is_array($enabled)) $enabled = [];

    return $enabled;
}

function plugin_is_enabled(string $id): bool
{
    return in_array($id, plugins_enabled(), true);
}

function plugin_load_all(): void
{
    static $loaded = false;
    if ($loaded) return;
    $loaded = true;

    foreach (plugins_enabled() as $id) {
        if (!is_string($id) || $id === '') continue;
        if (!preg_match('/^[a-z0-9-]+$/', $id)) continue;

        $bootstrap = __DIR__ . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (is_file($bootstrap)) {
            require_once $bootstrap;
        }
    }
}

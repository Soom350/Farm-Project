<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib_bootstrap.php';
require_once dirname(__DIR__) . '/lib_admin.php';

/** @var string|null $pageTitle */
$pageTitle = isset($pageTitle) ? (string)$pageTitle : 'Administration | Timbuktu Farming';
/** @var string|null $pageDescription */
$pageDescription = isset($pageDescription) ? (string)$pageDescription : 'Back-office Timbuktu Farming.';

$admin = admin_user();
$adminScript = basename((string)($_SERVER['SCRIPT_NAME'] ?? 'index.php'), '.php') ?: 'index';
$layoutPageSlug = 'admin-' . strtolower((string)preg_replace('/[^a-z0-9-]+/i', '-', $adminScript));
?>
<!DOCTYPE html>
<html lang="fr" data-page="<?= h($layoutPageSlug) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="<?= h($pageDescription) ?>">
    <?= csrf_meta_tag() ?>

    <link rel="icon" href="<?= h(app_url('assets/logo/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= h(app_url('styles/globals.css')) ?>">
    <link rel="stylesheet" href="<?= h(app_url('style/index.css')) ?>">
    <link rel="stylesheet" href="<?= h(app_url('style/pages.css')) ?>">
    <link rel="stylesheet" href="<?= h(app_url('styles/components.css')) ?>">
    <link rel="stylesheet" href="<?= h(app_url('styles/responsive.css')) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"></noscript>
    <title><?= h($pageTitle) ?></title>
</head>
<body id="page-<?= h($layoutPageSlug) ?>" class="page page--admin page--<?= h($layoutPageSlug) ?>">
<a class="skip-link" href="#admin-main">Aller au contenu admin</a>

<div class="admin-shell">
    <aside class="admin-sidebar" aria-label="Navigation administration">
        <div class="admin-sidebar__brand">
            <a class="admin-sidebar__brand-link" href="<?= h(admin_url('index.php')) ?>">
                <img src="<?= h(app_url('assets/logo/logo-icon.svg')) ?>" alt="" width="32" height="32" aria-hidden="true">
                <span>Admin TF</span>
            </a>
        </div>

        <nav class="admin-sidebar__nav">
            <ul class="admin-sidebar__list">
                <?php foreach (admin_nav_items() as $slug => $item): ?>
                    <?php $active = $adminScript === $slug || ($slug === 'orders' && $adminScript === 'order') || ($slug === 'products' && $adminScript === 'product-edit') || ($slug === 'blog' && $adminScript === 'blog-edit'); ?>
                    <li>
                        <a class="admin-sidebar__link<?= $active ? ' is-active' : '' ?>" href="<?= h((string)$item['href']) ?>"<?= $active ? ' aria-current="page"' : '' ?>>
                            <?= h((string)$item['label']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="admin-sidebar__footer">
            <?php if ($admin): ?>
                <p class="admin-sidebar__user"><?= h((string)($admin['full_name'] ?? 'Admin')) ?></p>
                <p class="admin-sidebar__email"><?= h((string)($admin['email'] ?? '')) ?></p>
            <?php endif; ?>
            <a class="btn btn--secondary btn--sm" href="<?= h(app_url('index.php')) ?>">Retour au site</a>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <p class="admin-topbar__eyebrow">Administration</p>
            <h1 class="admin-topbar__title"><?= h((string)($adminPageTitle ?? 'Tableau de bord')) ?></h1>
        </header>

        <main id="admin-main" class="admin-content" tabindex="-1">

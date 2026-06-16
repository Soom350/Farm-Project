<?php
declare(strict_types=1);
require_once __DIR__ . '/lib_bootstrap.php';
require_once __DIR__ . '/lib_auth.php';

/** @var string|null $pageTitle */
$pageTitle = isset($pageTitle) ? (string)$pageTitle : 'Timbuktu Farming';
/** @var string|null $pageDescription */
$pageDescription = isset($pageDescription) ? (string)$pageDescription : 'International order platform (demo).';

$u = auth_user();

/** @var string|null $layoutPageSlug Surcharge optionnelle du slug (lettres, chiffres, tirets) pour body.page--… */
if (!isset($layoutPageSlug) || !is_string($layoutPageSlug) || trim($layoutPageSlug) === '') {
    $script = (string)($_SERVER['SCRIPT_NAME'] ?? '');
    $base = basename($script, '.php') ?: 'app';
    $parent = basename(dirname($script));
    $layoutPageSlug = $parent === 'auth' ? 'auth-' . $base : $base;
}
$layoutPageSlug = strtolower((string)preg_replace('/[^a-z0-9-]+/i', '-', $layoutPageSlug));
$layoutPageSlug = trim($layoutPageSlug, '-') ?: 'app';

$navProducts = in_array($layoutPageSlug, ['products', 'category', 'product'], true);
$navCart = in_array($layoutPageSlug, ['cart', 'checkout'], true);
$navAccount = (bool)$u && (
    str_starts_with($layoutPageSlug, 'account')
    || in_array($layoutPageSlug, ['order', 'order-status', 'order-confirmation'], true)
);
$navLogin = !$u && in_array($layoutPageSlug, [
    'auth-login',
    'auth-forgot-password',
    'auth-reset-password',
    'auth-verify',
    'auth-verify-required',
], true);
$navSignup = !$u && $layoutPageSlug === 'auth-signup';

$navLinkClass = static function (bool $active): string {
    return $active ? 'nav-link is-active' : 'nav-link';
};
$navCurrent = static function (bool $active): string {
    return $active ? ' aria-current="page"' : '';
};
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
    <!-- Non-render-blocking icons (brief FOUC acceptable vs blocking main thread on mobile) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"></noscript>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <title><?= h($pageTitle) ?></title>
</head>
<body id="page-<?= h($layoutPageSlug) ?>" class="page page--<?= h($layoutPageSlug) ?>">
<a class="skip-link" href="#main">Aller au contenu</a>
<header class="site-header" id="site-header" data-page="<?= h($layoutPageSlug) ?>">
    <div class="container site-header__inner">
        <div class="header-left-content header-branding">
            <a class="brand brand--lockup" href="<?= h(app_url('index.php')) ?>" aria-label="Accueil Timbuktu Farming">
                <span class="brand__title">Timbuktu Farming</span>
                <span class="brand__subtitle">Deliver Season's Best</span>
            </a>
        </div>
        <nav class="header-right-content site-header__nav" aria-label="Navigation principale">
            <button class="nav-cart" type="button" data-cart-open aria-label="Ouvrir le panier"<?= $navCart ? ' aria-current="page"' : '' ?>>
                <i class="fas fa-shopping-cart" aria-hidden="true"></i>
                <span class="nav-cart__label">Panier</span>
                <span class="cart-count" data-cart-count aria-hidden="true">0</span>
            </button>
            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" aria-label="Afficher/masquer la navigation">
                <i class="fas fa-bars" aria-hidden="true"></i>
            </button>
            <ul id="site-nav" class="site-nav">
                <li class="nav-item nav-item--home"><a class="nav-link" href="<?= h(app_url('index.php')) ?>">Accueil</a></li>
                <li class="nav-item nav-item--products"><a class="<?= $navLinkClass($navProducts) ?>" href="<?= h(app_url('products.php')) ?>"<?= $navCurrent($navProducts) ?>>Produits</a></li>
                <?php if ($u): ?>
                    <li class="nav-item nav-item--account"><a class="<?= $navLinkClass($navAccount) ?>" href="<?= h(app_url('account.php')) ?>"<?= $navCurrent($navAccount) ?>><i class="fas fa-user" aria-hidden="true"></i> Compte</a></li>
                    <li class="nav-item nav-item--logout">
                        <form method="post" action="<?= h(app_url('auth/logout.php')) ?>" class="form-inline">
                            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                            <button class="btn btn--secondary" type="submit">Se deconnecter</button>
                        </form>
                    </li>
                <?php else: ?>
                    <li class="nav-item nav-item--login"><a class="<?= $navLinkClass($navLogin) ?>" href="<?= h(url_with_params(app_url('auth/login.php'), ['next' => current_url_path()])) ?>"<?= $navCurrent($navLogin) ?>><i class="fas fa-user" aria-hidden="true"></i> Connexion</a></li>
                    <li class="nav-item nav-item--signup"><a class="<?= $navLinkClass($navSignup) ?>" href="<?= h(url_with_params(app_url('auth/signup.php'), ['next' => current_url_path()])) ?>"<?= $navCurrent($navSignup) ?>>Creer un compte</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>
<main id="main" class="page-main" tabindex="-1" data-page="<?= h($layoutPageSlug) ?>">
    <div class="container">


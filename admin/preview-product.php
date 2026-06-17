<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib_admin.php';
require_once dirname(__DIR__) . '/lib_media.php';

admin_require_access('admin/preview-product.php');
if (!admin_user()) {
    redirect(url_with_params(app_url('auth/login.php'), ['next' => 'admin/preview-product.php']));
}

if (is_post() && ($_POST['action'] ?? '') === 'preview') {
    require_csrf();
    admin_preview_store('product', $_POST);
    redirect(admin_url('preview-product.php'));
}

$preview = admin_preview_get();
if (!$preview || ($preview['type'] ?? '') !== 'product') {
    http_response_code(400);
    echo 'Aucun apercu produit disponible.';
    exit;
}

$product = admin_preview_product_from_input((array)($preview['data'] ?? []));
$previewStatus = admin_product_status_label((string)($product['status'] ?? 'inactive'));
$previewAvailability = admin_availability_label((string)($product['availability'] ?? 'in_stock'));

$pageTitle = 'Apercu produit | Admin';
require dirname(__DIR__) . '/layout_top.php';
?>

<div class="preview-banner" role="status">
    <strong>Apercu admin</strong> — Ce produit n'est pas encore publie tel quel sur le site.
    Statut: <?= h($previewStatus) ?> · Disponibilite: <?= h($previewAvailability) ?>
</div>

<section class="services" aria-labelledby="preview-product-title">
    <header class="services__header">
        <p class="services__eyebrow">Apercu produit</p>
        <h1 class="services__title" id="preview-product-title"><?= h((string)$product['name']) ?></h1>
        <p class="services__lead"><?= h((string)$product['short_description']) ?></p>
    </header>

    <div class="product-detail">
        <?php if (!empty($product['image']) && media_is_allowed_path((string)$product['image'])): ?>
            <div class="product-detail__media">
                <img class="product-detail__image" src="<?= h(media_url((string)$product['image'])) ?>" alt="<?= h((string)$product['name']) ?>" width="800" height="600">
            </div>
        <?php endif; ?>

        <div class="product-detail__info">
            <p class="service-card__text">
                <strong>SKU:</strong> <?= h((string)$product['sku']) ?><br>
                <strong>Prix:</strong> <?= h(money((float)$product['price'], (string)$product['currency'])) ?> / <?= h((string)$product['unit']) ?><br>
                <strong>Stock:</strong> <?= h((string)$product['stock_qty']) ?>
            </p>
            <p class="service-card__text"><?= nl2br(h((string)$product['description'])) ?></p>
        </div>
    </div>
</section>

<div class="preview-banner preview-banner--footer">
    <a class="btn btn--secondary btn--sm" href="javascript:window.close();">Fermer l'apercu</a>
</div>

<?php require dirname(__DIR__) . '/layout_bottom.php'; ?>

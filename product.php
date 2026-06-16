<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_catalog.php';
require_once __DIR__ . '/lib_cart.php';

$sku = trim((string)($_GET['sku'] ?? ''));
$product = $sku !== '' ? catalog_product_by_sku($sku) : null;

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Produit introuvable | Timbuktu Farming';
    require __DIR__ . '/layout_top.php';
    ?>
    <section class="services">
        <article class="service-card">
            <div class="service-card__body">
                <h1 class="service-card__title">Produit introuvable</h1>
                <p class="service-card__text">Le produit demande n'existe pas (SKU: <?= h($sku) ?>).</p>
                <a class="btn btn--primary" href="products.php">Retour aux produits</a>
            </div>
        </article>
    </section>
    <?php
    require __DIR__ . '/layout_bottom.php';
    exit;
}

$pageTitle = $product['name'] . ' | Timbuktu Farming';
$pageDescription = $product['short_description'];

// Related products: share at least one category slug
$related = [];
foreach (catalog_products() as $p) {
    if ($p['sku'] === $product['sku']) continue;
    $shared = array_intersect($p['category_slugs'], $product['category_slugs']);
    if (count($shared)) $related[] = $p;
}
$related = array_slice($related, 0, 4);

require __DIR__ . '/layout_top.php';
?>

<section class="services" aria-labelledby="product-title">
    <header class="services__header" data-reveal style="--reveal-delay: 0ms;">
        <p class="services__eyebrow">Produit</p>
        <h1 class="services__title" id="product-title"><?= h($product['name']) ?></h1>
        <p class="services__lead"><?= h($product['short_description']) ?></p>
    </header>

    <div class="product-detail">
        <?php if (!empty($product['image'])): ?>
        <div class="product-detail__media">
            <img
                class="product-detail__image"
                src="<?= h(app_url((string)$product['image'])) ?>"
                alt="<?= h($product['name']) ?>"
                width="800"
                height="600"
                loading="eager"
            >
        </div>
        <?php endif; ?>
        <div class="product-detail__info">
        <article class="service-card">
            <div class="service-card__body">
                <h2 class="service-card__title">References et prix</h2>
                <p class="service-card__text">
                    <strong>SKU:</strong> <?= h($product['sku']) ?><br>
                    <strong>Prix:</strong> <?= h(money((float)$product['price'], (string)$product['currency'])) ?><?= $product['unit'] ? ' / ' . h((string)$product['unit']) : '' ?><br>
                    <strong>Disponibilite:</strong> <?= h($product['availability']) ?><?= $product['availability'] === 'in_stock' ? ' (' . h((string)$product['stock_qty']) . ')' : '' ?><br>
                    <strong>Livraison:</strong> <?= $product['shipping_eligible'] ? 'eligible' : 'non eligible' ?>
                </p>

                <form method="post" action="cart.php" class="actions actions--end">
                    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="sku" value="<?= h($product['sku']) ?>">
                    <div class="field">
                        <label for="qty">Quantite</label>
                        <input class="form-control form-control--sm" id="qty" name="qty" type="number" min="1" value="1" inputmode="numeric">
                    </div>
                    <button class="btn btn--primary" type="submit" <?= ($product['availability'] === 'out_of_stock' || $product['availability'] === 'discontinued') ? 'disabled' : '' ?>>
                        Ajouter au panier
                    </button>
                    <a class="btn btn--secondary" href="cart.php">Voir le panier</a>
                </form>
            </div>
        </article>
        </div>
    </div>

    <div class="services__grid services__grid--single">
        <article class="service-card">
            <div class="service-card__body">
                <h2 class="service-card__title">Description detaillee</h2>
                <p class="service-card__text"><?= h((string)$product['description']) ?></p>
            </div>
        </article>

        <article class="service-card">
            <div class="service-card__body">
                <h2 class="service-card__title">Specifications techniques</h2>
                <?php if (empty($product['specs'])): ?>
                    <p class="service-card__text">Aucune specification renseignee.</p>
                <?php else: ?>
                    <ul class="service-card__bullets" aria-label="Spécifications">
                        <?php foreach ($product['specs'] as $k => $v): ?>
                            <li><strong><?= h((string)$k) ?>:</strong> <?= h((string)$v) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </article>

        <article class="service-card">
            <div class="service-card__body">
                <h2 class="service-card__title">Conformite et certifications</h2>
                <?php $certs = (array)($product['compliance']['certifications'] ?? []); ?>
                <?php if (!count($certs)): ?>
                    <p class="service-card__text">Aucune certification renseignee.</p>
                <?php else: ?>
                    <ul class="service-card__checklist" aria-label="Certifications">
                        <?php foreach ($certs as $c): ?>
                            <li><?= h((string)$c) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </article>

        <article class="service-card">
            <div class="service-card__body">
                <h2 class="service-card__title">Stock et livraison</h2>
                <p class="service-card__text">
                    <strong>Statut stock:</strong> <?= h($product['availability']) ?><br>
                    <strong>Livraison internationale:</strong> <?= $product['shipping_eligible'] ? 'oui' : 'non' ?><br>
                    <strong>Note:</strong> delais et frais sont calcules au paiement selon pays/zone et option.
                </p>
            </div>
        </article>

        <article class="service-card">
            <div class="service-card__body">
                <h2 class="service-card__title">Produits associes</h2>
                <?php if (!count($related)): ?>
                    <p class="service-card__text">Aucune suggestion disponible.</p>
                <?php else: ?>
                    <div class="services__grid services__grid--catalog" aria-label="Produits associes">
                        <?php foreach ($related as $rp): ?>
                            <article
                                class="service-card"
                                data-product-id="<?= h((string)$rp['id']) ?>"
                                data-sku="<?= h((string)$rp['sku']) ?>"
                                data-name="<?= h((string)$rp['name']) ?>"
                                data-price="<?= h((string)$rp['price']) ?>"
                                data-unit="<?= h((string)($rp['unit'] ?? '')) ?>"
                                data-stock="<?= h((string)($rp['stock_qty'] ?? 0)) ?>"
                                data-availability="<?= h((string)$rp['availability']) ?>"
                                data-image="<?= h(app_url((string)($rp['image'] ?? 'assets/logo/logo-icon.svg'))) ?>"
                                data-description="<?= h((string)$rp['short_description']) ?>"
                            >
                                <div class="service-card__body">
                                    <button
                                        class="quick-view quick-view--catalog"
                                        type="button"
                                        data-quick-view
                                        aria-label="Apercu rapide <?= h((string)$rp['name']) ?>"
                                    >
                                        <i class="fas fa-eye" aria-hidden="true"></i> Apercu rapide
                                    </button>
                                    <?php if (!empty($rp['image'])): ?>
                                    <img
                                        class="catalog-product__img"
                                        src="<?= h(app_url((string)$rp['image'])) ?>"
                                        alt="<?= h((string)$rp['name']) ?>"
                                        width="400"
                                        height="280"
                                        loading="lazy"
                                    >
                                    <?php endif; ?>
                                    <h3 class="service-card__title"><?= h($rp['name']) ?></h3>
                                    <p class="service-card__subtitle"><?= h($rp['short_description']) ?></p>
                                    <p class="service-card__text">
                                        <strong>SKU:</strong> <?= h($rp['sku']) ?><br>
                                        <strong>Prix:</strong> <?= h(money((float)$rp['price'], (string)$rp['currency'])) ?><?= $rp['unit'] ? ' / ' . h((string)$rp['unit']) : '' ?>
                                    </p>
                                    <div class="actions">
                                        <a class="btn btn--secondary" href="<?= h(url_with_params('product.php', ['sku' => $rp['sku']])) ?>">Details</a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </article>
    </div>
</section>

<?php require __DIR__ . '/layout_bottom.php'; ?>


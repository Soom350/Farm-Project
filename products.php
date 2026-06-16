<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_catalog.php';
require_once __DIR__ . '/lib_cart.php';

$pageTitle = 'Produits | Timbuktu Farming';
$pageDescription = 'Catalogue produits: recherche, filtres, disponibilite.';

$filters = [
    'q' => (string)($_GET['q'] ?? ''),
    'category' => (string)($_GET['category'] ?? ''),
    'availability' => (string)($_GET['availability'] ?? ''),
    'min_price' => (string)($_GET['min_price'] ?? ''),
    'max_price' => (string)($_GET['max_price'] ?? ''),
    'sort' => (string)($_GET['sort'] ?? 'relevance'),
    'tag' => (string)($_GET['tag'] ?? ''),
];

$products = catalog_products_search($filters);
$categories = catalog_categories();

require __DIR__ . '/layout_top.php';
?>

<section class="services" aria-labelledby="products-title">
    <header class="services__header" data-reveal style="--reveal-delay: 0ms;">
        <p class="services__eyebrow">Catalogue</p>
        <h1 class="services__title" id="products-title">Produits</h1>
        <p class="services__lead">Recherche, filtres et informations essentielles (SKU, disponibilite, livraison).</p>
    </header>

    <div class="services__grid services__grid--single">
        <article class="service-card">
            <div class="service-card__body">
                <h2 class="service-card__title">Recherche et filtres</h2>

                <form method="get" action="products.php" class="form form--narrow">
                    <div class="grid grid--2">
                        <div class="field">
                            <label for="q">Recherche</label>
                            <input class="form-control" id="q" name="q" type="text" placeholder="Nom ou SKU" value="<?= h($filters['q']) ?>">
                        </div>
                        <div class="field">
                            <label for="category">Categorie</label>
                            <select class="form-control" id="category" name="category">
                                <option value="">Toutes les categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= h($cat['slug']) ?>" <?= $filters['category'] === $cat['slug'] ? 'selected' : '' ?>>
                                        <?= h($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid--3">
                        <div class="field">
                            <label for="availability">Disponibilite</label>
                            <select class="form-control" id="availability" name="availability">
                                <option value="">Toutes disponibilites</option>
                                <?php
                                $avail = [
                                    'in_stock' => 'En stock',
                                    'out_of_stock' => 'Rupture de stock',
                                    'backorder' => 'Sur commande',
                                    'preorder' => 'Precommande',
                                    'discontinued' => 'Arrete',
                                ];
                                ?>
                                <?php foreach ($avail as $k => $label): ?>
                                    <option value="<?= h($k) ?>" <?= $filters['availability'] === $k ? 'selected' : '' ?>><?= h($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="min_price">Prix min</label>
                            <input class="form-control" id="min_price" name="min_price" type="number" step="0.01" placeholder="0.00" value="<?= h($filters['min_price']) ?>">
                        </div>
                        <div class="field">
                            <label for="max_price">Prix max</label>
                            <input class="form-control" id="max_price" name="max_price" type="number" step="0.01" placeholder="0.00" value="<?= h($filters['max_price']) ?>">
                        </div>
                    </div>

                    <div class="grid grid--2">
                        <div class="field">
                            <label for="sort">Tri</label>
                            <select class="form-control" id="sort" name="sort">
                                <option value="relevance" <?= $filters['sort'] === 'relevance' ? 'selected' : '' ?>>Pertinence</option>
                                <option value="price_asc" <?= $filters['sort'] === 'price_asc' ? 'selected' : '' ?>>Prix croissant</option>
                                <option value="price_desc" <?= $filters['sort'] === 'price_desc' ? 'selected' : '' ?>>Prix decroissant</option>
                                <option value="availability" <?= $filters['sort'] === 'availability' ? 'selected' : '' ?>>Disponibilite</option>
                                <option value="new" <?= $filters['sort'] === 'new' ? 'selected' : '' ?>>Nouveautes</option>
                            </select>
                        </div>
                        <div class="actions">
                                <button type="submit" class="btn btn--primary">Appliquer</button>
                            <a class="btn btn--secondary" href="products.php">Reinitialiser</a>
                        </div>
                    </div>
                </form>
            </div>
        </article>

        <article class="service-card">
            <div class="service-card__body">
                <h2 class="service-card__title">Categories</h2>
                <div class="service-card__text wrap">
                    <?php foreach ($categories as $cat): ?>
                        <a class="btn btn--secondary" href="<?= h(url_with_params('category.php', ['slug' => $cat['slug']])) ?>">
                            <?= h($cat['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </article>

        <article class="service-card">
            <div class="service-card__body">
                <h2 class="service-card__title">Resultats (<?= count($products) ?>)</h2>
                <?php if (!count($products)): ?>
                    <p class="service-card__text">Aucun produit ne correspond a votre recherche.</p>
                <?php else: ?>
                    <div class="services__grid services__grid--catalog" aria-label="Liste des produits">
                        <?php foreach ($products as $p): ?>
                            <article
                                class="service-card"
                                data-product-id="<?= h((string)$p['id']) ?>"
                                data-sku="<?= h((string)$p['sku']) ?>"
                                data-name="<?= h((string)$p['name']) ?>"
                                data-price="<?= h((string)$p['price']) ?>"
                                data-unit="<?= h((string)($p['unit'] ?? '')) ?>"
                                data-stock="<?= h((string)($p['stock_qty'] ?? 0)) ?>"
                                data-availability="<?= h((string)$p['availability']) ?>"
                                data-image="<?= h(app_url((string)($p['image'] ?? 'assets/logo/logo-icon.svg'))) ?>"
                                data-description="<?= h((string)$p['short_description']) ?>"
                            >
                                <div class="service-card__body">
                                    <button
                                        class="quick-view quick-view--catalog"
                                        type="button"
                                        data-quick-view
                                        aria-label="Apercu rapide <?= h((string)$p['name']) ?>"
                                    >
                                        <i class="fas fa-eye" aria-hidden="true"></i> Apercu rapide
                                    </button>
                                    <?php if (!empty($p['image'])): ?>
                                    <img
                                        class="catalog-product__img"
                                        src="<?= h(app_url((string)$p['image'])) ?>"
                                        alt="<?= h((string)$p['name']) ?>"
                                        width="400"
                                        height="280"
                                        loading="lazy"
                                    >
                                    <?php endif; ?>
                                    <h3 class="service-card__title"><?= h($p['name']) ?></h3>
                                    <p class="service-card__subtitle"><?= h($p['short_description']) ?></p>
                                    <p class="service-card__text">
                                        <strong>SKU:</strong> <?= h($p['sku']) ?><br>
                                        <strong>Prix:</strong> <?= h(money((float)$p['price'], (string)$p['currency'])) ?><?= $p['unit'] ? ' / ' . h((string)$p['unit']) : '' ?><br>
                                        <?php
                                        $availabilityLabel = match ((string)$p['availability']) {
                                            'in_stock' => 'En stock',
                                            'out_of_stock' => 'Rupture de stock',
                                            'backorder' => 'Sur commande',
                                            'preorder' => 'Precommande',
                                            'discontinued' => 'Arrete',
                                            default => (string)$p['availability'],
                                        };
                                        ?>
                                        <strong>Disponibilite:</strong> <?= h($availabilityLabel) ?><?= $p['availability'] === 'in_stock' ? ' (' . h((string)$p['stock_qty']) . ')' : '' ?><br>
                                        <strong>Livraison:</strong> <?= $p['shipping_eligible'] ? 'eligible' : 'non eligible' ?>
                                    </p>
                                    <div class="actions">
                                        <a class="btn btn--secondary" href="<?= h(url_with_params('product.php', ['sku' => $p['sku']])) ?>">Details</a>
                                        <form method="post" action="cart.php" class="form-inline">
                                            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="add">
                                            <input type="hidden" name="sku" value="<?= h($p['sku']) ?>">
                                            <input type="hidden" name="qty" value="1">
                                            <button class="btn btn--primary" type="submit" <?= ($p['availability'] === 'out_of_stock' || $p['availability'] === 'discontinued') ? 'disabled' : '' ?>>
                                                Ajouter au panier
                                            </button>
                                        </form>
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


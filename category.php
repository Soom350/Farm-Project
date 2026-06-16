<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_catalog.php';

$slug = trim((string)($_GET['slug'] ?? ''));
$category = $slug !== '' ? catalog_category_by_slug($slug) : null;

if (!$category) {
    http_response_code(404);
    $pageTitle = 'Categorie introuvable | Timbuktu Farming';
    require __DIR__ . '/layout_top.php';
    ?>
    <section class="services">
        <article class="service-card">
            <div class="service-card__body">
                <h1 class="service-card__title">Categorie introuvable</h1>
                <p class="service-card__text">La categorie demandee n'existe pas (slug: <?= h($slug) ?>).</p>
                <a class="btn btn--primary" href="products.php">Retour aux produits</a>
            </div>
        </article>
    </section>
    <?php
    require __DIR__ . '/layout_bottom.php';
    exit;
}

$pageTitle = h($category['name']) . ' | Timbuktu Farming';
$pageDescription = (string)($category['description'] ?? '');

$filters = [
    'q' => (string)($_GET['q'] ?? ''),
    'category' => $category['slug'],
    'availability' => (string)($_GET['availability'] ?? ''),
    'min_price' => (string)($_GET['min_price'] ?? ''),
    'max_price' => (string)($_GET['max_price'] ?? ''),
    'sort' => (string)($_GET['sort'] ?? 'relevance'),
    'tag' => (string)($_GET['tag'] ?? ''),
];

$products = catalog_products_search($filters);
require __DIR__ . '/layout_top.php';
?>

<section class="services" aria-labelledby="cat-title">
    <header class="services__header" data-reveal style="--reveal-delay: 0ms;">
        <p class="services__eyebrow">Categorie</p>
        <h1 class="services__title" id="cat-title"><?= h($category['name']) ?></h1>
        <p class="services__lead"><?= h((string)$category['description']) ?></p>
    </header>

    <div class="services__grid services__grid--single">
        <article class="service-card">
            <div class="service-card__body">
                <h2 class="service-card__title">Metadonnees</h2>
                <p class="service-card__text">
                    <strong>Slug:</strong> <?= h($category['slug']) ?><br>
                    <strong>Parent:</strong> <?= h((string)($category['parent_id'] ?? '—')) ?><br>
                    <strong>SEO indexable:</strong> <?= !empty($category['seo']['index']) ? 'oui' : 'non' ?>
                </p>
                <div class="actions">
                    <a class="btn btn--secondary" href="products.php">Tous les produits</a>
                </div>
            </div>
        </article>

        <article class="service-card">
            <div class="service-card__body">
                <h2 class="service-card__title">Filtrer cette categorie</h2>
                <form method="get" action="category.php" class="form form--narrow">
                    <input type="hidden" name="slug" value="<?= h($category['slug']) ?>">
                    <div class="grid grid--2">
                        <div class="field">
                            <label for="q">Recherche</label>
                            <input class="form-control" id="q" name="q" type="text" placeholder="Nom ou SKU" value="<?= h($filters['q']) ?>">
                        </div>
                        <div class="field">
                            <label for="availability">Disponibilite</label>
                            <select class="form-control" id="availability" name="availability">
                                <option value="">Toutes disponibilites</option>
                                <option value="in_stock" <?= $filters['availability'] === 'in_stock' ? 'selected' : '' ?>>En stock</option>
                                <option value="out_of_stock" <?= $filters['availability'] === 'out_of_stock' ? 'selected' : '' ?>>Rupture de stock</option>
                                <option value="backorder" <?= $filters['availability'] === 'backorder' ? 'selected' : '' ?>>Sur commande</option>
                                <option value="preorder" <?= $filters['availability'] === 'preorder' ? 'selected' : '' ?>>Precommande</option>
                                <option value="discontinued" <?= $filters['availability'] === 'discontinued' ? 'selected' : '' ?>>Arrete</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid--3">
                        <div class="field">
                            <label for="min_price">Prix min</label>
                            <input class="form-control" id="min_price" name="min_price" type="number" step="0.01" placeholder="0.00" value="<?= h($filters['min_price']) ?>">
                        </div>
                        <div class="field">
                            <label for="max_price">Prix max</label>
                            <input class="form-control" id="max_price" name="max_price" type="number" step="0.01" placeholder="0.00" value="<?= h($filters['max_price']) ?>">
                        </div>
                        <div class="field">
                            <label for="sort">Tri</label>
                            <select class="form-control" id="sort" name="sort">
                                <option value="relevance" <?= $filters['sort'] === 'relevance' ? 'selected' : '' ?>>Pertinence</option>
                                <option value="price_asc" <?= $filters['sort'] === 'price_asc' ? 'selected' : '' ?>>Prix croissant</option>
                                <option value="price_desc" <?= $filters['sort'] === 'price_desc' ? 'selected' : '' ?>>Prix decroissant</option>
                                <option value="availability" <?= $filters['sort'] === 'availability' ? 'selected' : '' ?>>Disponibilite</option>
                            </select>
                        </div>
                    </div>

                    <div class="actions">
                        <button type="submit" class="btn btn--primary">Appliquer</button>
                        <a class="btn btn--secondary" href="<?= h(url_with_params('category.php', ['slug' => $category['slug']])) ?>">Reinitialiser</a>
                    </div>
                </form>
            </div>
        </article>

        <article class="service-card">
            <div class="service-card__body">
                <h2 class="service-card__title">Produits (<?= count($products) ?>)</h2>
                <?php if (!count($products)): ?>
                    <p class="service-card__text">Aucun produit dans cette categorie avec ces filtres.</p>
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
                                        <strong>Disponibilite:</strong> <?= h($p['availability']) ?>
                                    </p>
                                    <div class="actions">
                                        <a class="btn btn--secondary" href="<?= h(url_with_params('product.php', ['sku' => $p['sku']])) ?>">Details</a>
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


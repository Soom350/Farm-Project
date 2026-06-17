<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib_admin.php';

admin_require_access('admin/products.php');
if (!admin_user()) {
    redirect(url_with_params(app_url('auth/login.php'), ['next' => 'admin/products.php']));
}

$info = flash_get('info');
$errors = [];

if (is_post() && ($_POST['action'] ?? '') === 'delete') {
    require_csrf();
    $result = catalog_product_delete((string)($_POST['product_id'] ?? ''));
    if ($result['ok'] ?? false) {
        flash_set('info', 'Produit supprime.');
        redirect(admin_url('products.php'));
    }
    $errors = (array)($result['errors'] ?? ['Suppression impossible.']);
}

$products = catalog_products(true);
$q = trim((string)($_GET['q'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));
$availabilityFilter = trim((string)($_GET['availability'] ?? ''));

if ($q !== '') {
    $products = array_values(array_filter($products, static function (array $product) use ($q): bool {
        $hay = mb_strtolower(
            (string)($product['name'] ?? '') . ' ' .
            (string)($product['sku'] ?? '') . ' ' .
            (string)($product['short_description'] ?? '')
        );
        return str_contains($hay, mb_strtolower($q));
    }));
}

if ($statusFilter !== '') {
    $products = array_values(array_filter($products, static fn(array $p): bool => ($p['status'] ?? '') === $statusFilter));
}

if ($availabilityFilter !== '') {
    $products = array_values(array_filter($products, static fn(array $p): bool => ($p['availability'] ?? '') === $availabilityFilter));
}

$pageTitle = 'Produits | Admin';
$adminPageTitle = 'Produits';
require __DIR__ . '/layout_top.php';
?>

<?php if ($info): ?>
    <div class="alert"><div class="alert__title">Information</div><div><?= h($info) ?></div></div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="alert alert--error" role="alert">
        <div class="alert__title">Erreur</div>
        <ul class="alert__list"><?php foreach ($errors as $msg): ?><li><?= h((string)$msg) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="admin-panel">
    <div class="admin-panel__header">
        <h2 class="admin-panel__title">Catalogue</h2>
        <a class="btn btn--primary btn--sm" href="<?= h(admin_url('product-edit.php')) ?>">Nouveau produit</a>
    </div>

    <form method="get" action="<?= h(admin_url('products.php')) ?>" class="admin-filters">
        <div class="field">
            <label for="q">Rechercher</label>
            <input class="form-control form-control--sm" id="q" name="q" type="search" value="<?= h($q) ?>" placeholder="Nom, SKU, description">
        </div>
        <div class="field">
            <label for="status">Statut catalogue</label>
            <select class="form-control form-control--sm" id="status" name="status">
                <option value="">Tous</option>
                <?php foreach (catalog_product_status_options() as $key => $label): ?>
                    <option value="<?= h($key) ?>" <?= $statusFilter === $key ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="availability">Disponibilite</label>
            <select class="form-control form-control--sm" id="availability" name="availability">
                <option value="">Toutes</option>
                <?php foreach (catalog_availability_options() as $key => $label): ?>
                    <option value="<?= h($key) ?>" <?= $availabilityFilter === $key ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-actions">
            <button class="btn btn--primary btn--sm" type="submit">Filtrer</button>
            <a class="btn btn--secondary btn--sm" href="<?= h(admin_url('products.php')) ?>">Reinitialiser</a>
        </div>
    </form>

    <?php if (!count($products)): ?>
        <p class="admin-empty">Aucun produit trouve.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">SKU</th>
                        <th scope="col">Produit</th>
                        <th scope="col">Prix</th>
                        <th scope="col">Stock</th>
                        <th scope="col">Disponibilite</th>
                        <th scope="col">Statut</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= h((string)($product['sku'] ?? '')) ?></td>
                            <td>
                                <strong><?= h((string)($product['name'] ?? '')) ?></strong>
                                <div class="admin-table__sub"><?= h((string)($product['short_description'] ?? '')) ?></div>
                            </td>
                            <td><?= h(money((float)($product['price'] ?? 0), (string)($product['currency'] ?? 'USD'))) ?> / <?= h((string)($product['unit'] ?? '')) ?></td>
                            <td><?= h((string)($product['stock_qty'] ?? 0)) ?></td>
                            <td><span class="admin-badge"><?= h(admin_availability_label((string)($product['availability'] ?? ''))) ?></span></td>
                            <td><span class="admin-badge"><?= h(admin_product_status_label((string)($product['status'] ?? 'active'))) ?></span></td>
                            <td>
                                <div class="admin-actions">
                                    <a class="btn btn--secondary btn--sm" href="<?= h(admin_url('product-edit.php?id=' . rawurlencode((string)$product['id']))) ?>">Modifier</a>
                                    <form method="post" action="<?= h(admin_url('products.php')) ?>" onsubmit="return confirm('Supprimer ce produit ?');">
                                        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="product_id" value="<?= h((string)$product['id']) ?>">
                                        <button class="btn btn--secondary btn--sm" type="submit">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/layout_bottom.php'; ?>

<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib_admin.php';
require_once dirname(__DIR__) . '/admin/partials/media-field.php';

admin_require_access('admin/product-edit.php');
if (!admin_user()) {
    redirect(url_with_params(app_url('auth/login.php'), ['next' => 'admin/product-edit.php']));
}

$productId = trim((string)($_GET['id'] ?? ''));
$product = $productId !== '' ? catalog_product_by_id($productId) : null;
$isNew = !$product;

$values = [
    'product_id' => $productId,
    'sku' => (string)($product['sku'] ?? ''),
    'name' => (string)($product['name'] ?? ''),
    'short_description' => (string)($product['short_description'] ?? ''),
    'description' => (string)($product['description'] ?? ''),
    'price' => (string)($product['price'] ?? ''),
    'currency' => (string)($product['currency'] ?? 'USD'),
    'unit' => (string)($product['unit'] ?? 'kg'),
    'availability' => (string)($product['availability'] ?? 'in_stock'),
    'stock_qty' => (string)($product['stock_qty'] ?? '0'),
    'category_slugs' => implode(', ', (array)($product['category_slugs'] ?? [])),
    'tags' => implode(', ', (array)($product['tags'] ?? [])),
    'image' => (string)($product['image'] ?? ''),
    'weight_kg' => isset($product['weight_kg']) ? (string)$product['weight_kg'] : '',
    'shipping_eligible' => !isset($product['shipping_eligible']) || !empty($product['shipping_eligible']),
    'status' => (string)($product['status'] ?? 'active'),
    'action' => '',
];

$errors = [];

if (is_post()) {
    require_csrf();

    foreach ($values as $key => $default) {
        if ($key === 'action') continue;
        if (is_bool($default)) {
            $values[$key] = isset($_POST[$key]) && ($_POST[$key] === '1' || $_POST[$key] === 'on');
        } else {
            $values[$key] = trim((string)($_POST[$key] ?? ''));
        }
    }
    $values['product_id'] = $productId;

    if (($_POST['action'] ?? '') === 'preview') {
        require_csrf();
        admin_preview_store('product', $_POST);
        redirect(admin_url('preview-product.php'));
    }

    $result = catalog_product_save($values);
    if ($result['ok'] ?? false) {
        flash_set('info', $isNew ? 'Produit cree.' : 'Produit mis a jour.');
        redirect(admin_url('products.php'));
    }

    $errors = (array)($result['errors'] ?? ['Enregistrement impossible.']);
}

$pageTitle = ($isNew ? 'Nouveau produit' : 'Modifier produit') . ' | Admin';
$adminPageTitle = $isNew ? 'Nouveau produit' : 'Modifier produit';
require __DIR__ . '/layout_top.php';
?>

<div class="admin-actions">
    <a class="btn btn--secondary btn--sm" href="<?= h(admin_url('products.php')) ?>">Retour a la liste</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert--error" role="alert">
        <div class="alert__title">Merci de corriger les champs</div>
        <ul class="alert__list">
            <?php foreach ($errors as $msg): ?>
                <li><?= h((string)$msg) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="admin-panel">
    <form method="post" action="<?= h(admin_url('product-edit.php' . ($productId !== '' ? '?id=' . rawurlencode($productId) : ''))) ?>" class="admin-form">
        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">

        <div class="grid grid--2">
            <div class="field">
                <label for="sku">SKU</label>
                <input class="form-control" id="sku" name="sku" type="text" value="<?= h($values['sku']) ?>" required>
            </div>
            <div class="field">
                <label for="name">Nom</label>
                <input class="form-control" id="name" name="name" type="text" value="<?= h($values['name']) ?>" required>
            </div>
            <div class="field">
                <label for="price">Prix</label>
                <input class="form-control" id="price" name="price" type="number" step="0.01" min="0" value="<?= h($values['price']) ?>" required>
            </div>
            <div class="field">
                <label for="currency">Devise</label>
                <input class="form-control" id="currency" name="currency" type="text" value="<?= h($values['currency']) ?>" required>
            </div>
            <div class="field">
                <label for="unit">Unite</label>
                <input class="form-control" id="unit" name="unit" type="text" value="<?= h($values['unit']) ?>" placeholder="kg, lot...">
            </div>
            <div class="field">
                <label for="stock_qty">Stock</label>
                <input class="form-control" id="stock_qty" name="stock_qty" type="number" min="0" value="<?= h($values['stock_qty']) ?>">
            </div>
            <div class="field">
                <label for="availability">Disponibilite</label>
                <select class="form-control" id="availability" name="availability">
                    <?php foreach (catalog_availability_options() as $key => $label): ?>
                        <option value="<?= h($key) ?>" <?= $values['availability'] === $key ? 'selected' : '' ?>><?= h($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="status">Statut catalogue</label>
                <select class="form-control" id="status" name="status">
                    <?php foreach (catalog_product_status_options() as $key => $label): ?>
                        <option value="<?= h($key) ?>" <?= $values['status'] === $key ? 'selected' : '' ?>><?= h($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="weight_kg">Poids (kg)</label>
                <input class="form-control" id="weight_kg" name="weight_kg" type="number" step="0.01" min="0" value="<?= h($values['weight_kg']) ?>">
            </div>
            <?php admin_render_media_field('image', 'Image du produit', $values['image']); ?>
            <div class="field field--full">
                <label for="short_description">Description courte</label>
                <input class="form-control" id="short_description" name="short_description" type="text" value="<?= h($values['short_description']) ?>">
            </div>
            <div class="field field--full">
                <label for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4"><?= h($values['description']) ?></textarea>
            </div>
            <div class="field field--full">
                <label for="category_slugs">Categories (separees par virgule)</label>
                <input class="form-control" id="category_slugs" name="category_slugs" type="text" value="<?= h($values['category_slugs']) ?>" placeholder="fresh-produce, grains">
            </div>
            <div class="field field--full">
                <label for="tags">Tags (separes par virgule)</label>
                <input class="form-control" id="tags" name="tags" type="text" value="<?= h($values['tags']) ?>">
            </div>
            <div class="field field--checkbox">
                <label><input type="checkbox" name="shipping_eligible" value="1" <?= $values['shipping_eligible'] ? 'checked' : '' ?>> Eligible a la livraison</label>
            </div>
        </div>

        <div class="admin-actions mt-3">
            <button class="btn btn--primary" type="submit" name="action" value="save"><?= $isNew ? 'Creer le produit' : 'Enregistrer' ?></button>
            <button class="btn btn--secondary" type="submit" name="action" value="preview" formtarget="_blank">Apercu avant publication</button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/layout_bottom.php'; ?>

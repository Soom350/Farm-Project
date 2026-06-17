<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib_admin.php';

admin_require_access('admin/orders.php');
if (!admin_user()) {
    redirect(url_with_params(app_url('auth/login.php'), ['next' => 'admin/orders.php']));
}

$orderRef = trim((string)($_GET['order'] ?? ''));
$order = $orderRef !== '' ? admin_order_get_by_ref($orderRef) : null;
$errors = [];
$info = flash_get('info');

if (!$order) {
    http_response_code(404);
    $pageTitle = 'Commande introuvable | Admin';
    $adminPageTitle = 'Commande introuvable';
    require __DIR__ . '/layout_top.php';
    ?>
    <div class="admin-panel">
        <p class="admin-empty">Commande introuvable: <?= h($orderRef) ?></p>
        <a class="btn btn--secondary" href="<?= h(admin_url('orders.php')) ?>">Retour aux commandes</a>
    </div>
    <?php
    require __DIR__ . '/layout_bottom.php';
    exit;
}

if (is_post() && ($_POST['action'] ?? '') === 'update_status') {
    require_csrf();
    $newStatus = trim((string)($_POST['status'] ?? ''));
    $result = admin_order_update_status((int)$order['id'], $newStatus);
    if ($result['ok'] ?? false) {
        flash_set('info', 'Statut mis a jour.');
        redirect(admin_url('order.php?order=' . rawurlencode($orderRef)));
    }
    $errors = (array)($result['errors'] ?? ['Erreur lors de la mise a jour.']);
}

$order = admin_order_get_by_ref($orderRef) ?? $order;
$items = order_items((int)$order['id']);
$statuses = admin_order_statuses();

$pageTitle = 'Commande ' . $orderRef . ' | Admin';
$adminPageTitle = 'Commande ' . $orderRef;
require __DIR__ . '/layout_top.php';
?>

<div class="admin-actions">
    <a class="btn btn--secondary btn--sm" href="<?= h(admin_url('orders.php')) ?>">Retour a la liste</a>
</div>

<?php if ($info): ?>
    <div class="alert">
        <div class="alert__title">Information</div>
        <div><?= h($info) ?></div>
    </div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="alert alert--error" role="alert">
        <div class="alert__title">Erreur</div>
        <ul class="alert__list">
            <?php foreach ($errors as $msg): ?>
                <li><?= h((string)$msg) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="admin-grid">
    <article class="admin-panel">
        <div class="admin-panel__header">
            <h2 class="admin-panel__title">Client</h2>
            <span class="admin-badge"><?= h((string)(admin_order_statuses()[$order['status']] ?? $order['status'])) ?></span>
        </div>
        <dl class="admin-dl">
            <div><dt>Reference</dt><dd><?= h((string)$order['order_ref']) ?></dd></div>
            <div><dt>Utilisateur</dt><dd>#<?= h((string)($order['customer_user_id'] ?? '')) ?> — <?= h((string)($order['customer_name'] ?? '')) ?></dd></div>
            <div><dt>Email</dt><dd><?= h((string)($order['customer_email'] ?? '')) ?></dd></div>
            <div><dt>Telephone</dt><dd><?= h((string)($order['customer_phone'] ?? '')) ?></dd></div>
            <?php if (!empty($order['customer_company'])): ?>
                <div><dt>Entreprise</dt><dd><?= h((string)$order['customer_company']) ?></dd></div>
            <?php endif; ?>
            <div><dt>Creee le</dt><dd><?= h((string)$order['created_at']) ?></dd></div>
        </dl>
    </article>

    <article class="admin-panel">
        <div class="admin-panel__header">
            <h2 class="admin-panel__title">Livraison (d'ou / vers ou)</h2>
        </div>
        <dl class="admin-dl">
            <div><dt>Pays</dt><dd><?= h(admin_country_label((string)($order['shipping_country'] ?? ''))) ?> (<?= h((string)($order['shipping_country'] ?? '')) ?>)</dd></div>
            <div><dt>Adresse</dt><dd><?= h((string)$order['shipping_line1']) ?></dd></div>
            <?php if (!empty($order['shipping_line2'])): ?>
                <div><dt>Complement</dt><dd><?= h((string)$order['shipping_line2']) ?></dd></div>
            <?php endif; ?>
            <div><dt>Ville</dt><dd><?= h((string)$order['shipping_city']) ?><?= !empty($order['shipping_region']) ? ', ' . h((string)$order['shipping_region']) : '' ?></dd></div>
            <div><dt>Code postal</dt><dd><?= h((string)$order['shipping_postal_code']) ?></dd></div>
            <div><dt>Mode</dt><dd><?= h((string)$order['delivery_method']) ?> (<?= h((string)$order['delivery_estimate_days']) ?> jours)</dd></div>
        </dl>
    </article>

    <article class="admin-panel">
        <div class="admin-panel__header">
            <h2 class="admin-panel__title">Paiement</h2>
        </div>
        <dl class="admin-dl">
            <div><dt>Sous-total</dt><dd><?= h(money((float)$order['subtotal'], (string)$order['currency'])) ?></dd></div>
            <div><dt>Livraison</dt><dd><?= h(money((float)$order['shipping'], (string)$order['currency'])) ?></dd></div>
            <div><dt>Taxes</dt><dd><?= h(money((float)$order['taxes'], (string)$order['currency'])) ?> (<?= h((string)round((float)$order['tax_rate'] * 100, 1)) ?>%)</dd></div>
            <div><dt>Total</dt><dd><strong><?= h(money((float)$order['total'], (string)$order['currency'])) ?></strong></dd></div>
            <div><dt>Methode</dt><dd><?= h((string)$order['payment_method']) ?><?= !empty($order['payment_card_last4']) ? ' •••• ' . h((string)$order['payment_card_last4']) : '' ?></dd></div>
        </dl>
    </article>

    <article class="admin-panel">
        <div class="admin-panel__header">
            <h2 class="admin-panel__title">Mettre a jour le statut</h2>
        </div>
        <form method="post" action="<?= h(admin_url('order.php?order=' . rawurlencode($orderRef))) ?>" class="admin-filters">
            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="update_status">
            <div class="field">
                <label for="status">Statut</label>
                <select class="form-control form-control--sm" id="status" name="status" required>
                    <?php foreach ($statuses as $key => $label): ?>
                        <?php if ($key === '') continue; ?>
                        <option value="<?= h((string)$key) ?>" <?= (string)$order['status'] === (string)$key ? 'selected' : '' ?>><?= h((string)$label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn--primary btn--sm" type="submit">Enregistrer</button>
        </form>
    </article>
</div>

<div class="admin-panel">
    <div class="admin-panel__header">
        <h2 class="admin-panel__title">Produits commandes</h2>
    </div>

    <?php if (!count($items)): ?>
        <p class="admin-empty">Aucun article.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">SKU</th>
                        <th scope="col">Produit</th>
                        <th scope="col">Prix unitaire</th>
                        <th scope="col">Quantite</th>
                        <th scope="col">Total ligne</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= h((string)$item['sku']) ?></td>
                            <td><?= h((string)$item['name']) ?></td>
                            <td><?= h(money((float)$item['unit_price'], (string)$order['currency'])) ?></td>
                            <td><?= h((string)$item['qty']) ?></td>
                            <td><?= h(money((float)$item['line_total'], (string)$order['currency'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/layout_bottom.php'; ?>

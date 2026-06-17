<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib_admin.php';

admin_require_access('admin/orders.php');
if (!admin_user()) {
    redirect(url_with_params(app_url('auth/login.php'), ['next' => 'admin/orders.php']));
}

$group = trim((string)($_GET['group'] ?? 'active'));
if (!isset(admin_order_groups()[$group])) {
    $group = 'active';
}

$status = trim((string)($_GET['status'] ?? ''));
$q = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$filters = ['group' => $group, 'status' => $status, 'q' => $q];
$total = admin_orders_count($filters);
$orders = admin_orders_search($filters, $perPage, $offset);
$pageCount = $total > 0 ? (int)ceil($total / $perPage) : 1;
$statuses = admin_order_statuses();
$groupCounts = admin_orders_group_counts();

$pageTitle = 'Commandes | Admin';
$adminPageTitle = 'Commandes';
require __DIR__ . '/layout_top.php';
?>

<?php if (app_frontend_only()): ?>
    <div class="alert alert--error" role="alert">
        <div class="alert__title">Mode design</div>
        <div>La base est desactivee. Les commandes s'afficheront quand le back sera active.</div>
    </div>
<?php endif; ?>

<div class="admin-tabs" role="tablist" aria-label="Groupes de commandes">
    <?php foreach (admin_order_groups() as $key => $meta): ?>
        <?php $active = $group === $key; ?>
        <a class="admin-tabs__link<?= $active ? ' is-active' : '' ?>" href="<?= h(admin_url('orders.php?' . http_build_query(['group' => $key, 'q' => $q]))) ?>"<?= $active ? ' aria-current="page"' : '' ?>>
            <?= h((string)$meta['label']) ?>
            <span class="admin-tabs__count"><?= h((string)($groupCounts[$key] ?? 0)) ?></span>
        </a>
    <?php endforeach; ?>
</div>

<div class="admin-panel">
    <div class="admin-panel__header">
        <h2 class="admin-panel__title"><?= h((string)(admin_order_groups()[$group]['label'] ?? 'Commandes')) ?></h2>
        <p class="admin-panel__meta"><?= h((string)$total) ?> commande(s)</p>
    </div>

    <form method="get" action="<?= h(admin_url('orders.php')) ?>" class="admin-filters">
        <input type="hidden" name="group" value="<?= h($group) ?>">
        <div class="field">
            <label for="status">Statut detaille</label>
            <select class="form-control form-control--sm" id="status" name="status">
                <?php foreach ($statuses as $key => $label): ?>
                    <option value="<?= h((string)$key) ?>" <?= $status === (string)$key ? 'selected' : '' ?>><?= h((string)$label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="q">Recherche</label>
            <input class="form-control form-control--sm" id="q" name="q" type="search" value="<?= h($q) ?>" placeholder="Reference, client, email, telephone">
        </div>
        <div class="admin-actions">
            <button class="btn btn--primary btn--sm" type="submit">Appliquer</button>
            <a class="btn btn--secondary btn--sm" href="<?= h(admin_url('orders.php?group=' . rawurlencode($group))) ?>">Reinitialiser</a>
        </div>
    </form>

    <?php if (!count($orders)): ?>
        <p class="admin-empty">Aucune commande dans cette vue.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">Reference</th>
                        <th scope="col">Client</th>
                        <th scope="col">Pays (livraison)</th>
                        <th scope="col">Statut</th>
                        <th scope="col">Total</th>
                        <th scope="col">Articles</th>
                        <th scope="col">Date</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <?php $items = order_items((int)$order['id']); ?>
                        <tr>
                            <td><?= h((string)$order['order_ref']) ?></td>
                            <td>
                                <strong><?= h((string)($order['customer_name'] ?? '')) ?></strong>
                                <div class="admin-table__sub"><?= h((string)($order['customer_email'] ?? '')) ?></div>
                                <div class="admin-table__sub">User #<?= h((string)($order['customer_user_id'] ?? '')) ?></div>
                            </td>
                            <td><?= h(admin_country_label((string)($order['shipping_country'] ?? ''))) ?></td>
                            <td><span class="admin-badge"><?= h((string)(admin_order_statuses()[$order['status']] ?? $order['status'])) ?></span></td>
                            <td><?= h(money((float)$order['total'], (string)$order['currency'])) ?></td>
                            <td>
                                <?php foreach ($items as $item): ?>
                                    <div class="admin-table__sub"><?= h((string)$item['qty']) ?>x <?= h((string)$item['name']) ?></div>
                                <?php endforeach; ?>
                            </td>
                            <td><?= h((string)$order['created_at']) ?></td>
                            <td><a class="btn btn--secondary btn--sm" href="<?= h(admin_url('order.php?order=' . rawurlencode((string)$order['order_ref']))) ?>">Voir</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pageCount > 1): ?>
            <div class="admin-pagination">
                <?php if ($page > 1): ?>
                    <a class="btn btn--secondary btn--sm" href="<?= h(admin_url('orders.php?' . http_build_query(['group' => $group, 'status' => $status, 'q' => $q, 'page' => $page - 1]))) ?>">Precedent</a>
                <?php endif; ?>
                <span class="admin-pagination__label">Page <?= h((string)$page) ?> / <?= h((string)$pageCount) ?></span>
                <?php if ($page < $pageCount): ?>
                    <a class="btn btn--secondary btn--sm" href="<?= h(admin_url('orders.php?' . http_build_query(['group' => $group, 'status' => $status, 'q' => $q, 'page' => $page + 1]))) ?>">Suivant</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/layout_bottom.php'; ?>

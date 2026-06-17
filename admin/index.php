<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib_admin.php';

admin_require_access('admin/index.php');
$admin = admin_user();
if (!$admin) {
    redirect(url_with_params(app_url('auth/login.php'), ['next' => 'admin/index.php']));
}

$stats = admin_dashboard_stats();
$recentOrders = admin_orders_search([], 5, 0);

$pageTitle = 'Tableau de bord | Admin';
$adminPageTitle = 'Tableau de bord';
require __DIR__ . '/layout_top.php';
?>

<?php if (app_frontend_only()): ?>
    <div class="alert alert--error" role="alert">
        <div class="alert__title">Mode design</div>
        <div>Le back est desactive. L'interface admin est visible, mais les donnees DB ne sont pas chargees.</div>
    </div>
<?php endif; ?>

<div class="admin-stats">
    <article class="admin-stat-card">
        <p class="admin-stat-card__label">Commandes</p>
        <p class="admin-stat-card__value"><?= h((string)$stats['orders_count']) ?></p>
    </article>
    <article class="admin-stat-card">
        <p class="admin-stat-card__label">En attente</p>
        <p class="admin-stat-card__value"><?= h((string)$stats['orders_pending']) ?></p>
    </article>
    <article class="admin-stat-card">
        <p class="admin-stat-card__label">Chiffre d'affaires</p>
        <p class="admin-stat-card__value"><?= h(money((float)$stats['revenue_total'])) ?></p>
    </article>
    <article class="admin-stat-card">
        <p class="admin-stat-card__label">Produits</p>
        <p class="admin-stat-card__value"><?= h((string)$stats['products_count']) ?></p>
    </article>
    <article class="admin-stat-card">
        <p class="admin-stat-card__label">Articles blog</p>
        <p class="admin-stat-card__value"><?= h((string)$stats['blog_posts_count']) ?></p>
    </article>
</div>

<div class="admin-panel">
    <div class="admin-panel__header">
        <h2 class="admin-panel__title">Acces rapide</h2>
    </div>
    <div class="admin-actions">
        <a class="btn btn--primary" href="<?= h(admin_url('products.php')) ?>">Gerer les produits</a>
        <a class="btn btn--secondary" href="<?= h(admin_url('blog.php')) ?>">Gerer le blog</a>
        <a class="btn btn--secondary" href="<?= h(admin_url('orders.php')) ?>">Gerer les commandes</a>
    </div>
</div>

<div class="admin-panel">
    <div class="admin-panel__header">
        <h2 class="admin-panel__title">Commandes recentes</h2>
        <a class="btn btn--secondary btn--sm" href="<?= h(admin_url('orders.php')) ?>">Voir tout</a>
    </div>

    <?php if (!count($recentOrders)): ?>
        <p class="admin-empty">Aucune commande pour le moment.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">Reference</th>
                        <th scope="col">Client</th>
                        <th scope="col">Statut</th>
                        <th scope="col">Total</th>
                        <th scope="col">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td><a href="<?= h(admin_url('order.php?order=' . rawurlencode((string)$order['order_ref']))) ?>"><?= h((string)$order['order_ref']) ?></a></td>
                            <td><?= h((string)($order['customer_name'] ?? '')) ?></td>
                            <td><span class="admin-badge"><?= h((string)$order['status']) ?></span></td>
                            <td><?= h(money((float)$order['total'], (string)$order['currency'])) ?></td>
                            <td><?= h((string)$order['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/layout_bottom.php'; ?>

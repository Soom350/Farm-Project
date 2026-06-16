<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_auth.php';
require_once __DIR__ . '/lib_orders.php';
require_once __DIR__ . '/lib_account.php';

if (!app_frontend_only()) {
    auth_require_login('account-orders.php');
}
$u = auth_user();
if (!$u) {
    if (!app_frontend_only()) {
        redirect(url_with_params('auth/login.php', ['next' => 'account-orders.php']));
    }
    // Mode design
    $u = ['id' => 1, 'email' => 'demo@timbuktu-farming.test', 'full_name' => 'Utilisateur Démo'];
}

$info = flash_get('info');

$status = trim((string)($_GET['status'] ?? ''));
$q = trim((string)($_GET['q'] ?? ''));
$page = (int)($_GET['page'] ?? 1);
$perPage = 20;
$page = max(1, $page);
$offset = ($page - 1) * $perPage;

$filters = ['status' => $status, 'q' => $q];
$total = account_orders_count((int)$u['id'], $filters);
$orders = account_orders_search((int)$u['id'], $filters, $perPage, $offset);

// Fallback simple en mode design (pas de DB)
if (app_frontend_only() && !$orders) {
    $orders = [];
    $total = 0;
}

$statuses = [
    '' => 'Tous',
    'processing' => 'processing',
    'pending_payment' => 'pending_payment',
    'paid' => 'paid',
    'shipped' => 'shipped',
    'delivered' => 'delivered',
    'cancelled' => 'cancelled',
];

$pageCount = $total > 0 ? (int)ceil($total / $perPage) : 1;

$pageTitle = 'Mes commandes | Timbuktu Farming';
$pageDescription = 'Historique des commandes.';
require __DIR__ . '/layout_top.php';
?>

<section class="services" aria-labelledby="account-orders-title">
    <header class="services__header" data-reveal style="--reveal-delay: 0ms;">
        <p class="services__eyebrow">Account</p>
        <h1 class="services__title" id="account-orders-title">Commandes</h1>
        <p class="services__lead">Consultez l’historique et le statut de vos commandes.</p>
    </header>

    <div class="actions mt-2">
        <a class="btn btn--secondary" href="account.php">Vue d’ensemble</a>
        <a class="btn btn--secondary" href="account-profile.php">Profil</a>
        <a class="btn btn--secondary" href="account-addresses.php">Adresses</a>
        <a class="btn btn--primary" href="account-orders.php">Commandes</a>
    </div>

    <div class="services__grid services__grid--single mt-3">
        <article class="service-card">
            <div class="service-card__body">
                <?php if ($info): ?>
                    <div class="alert">
                        <div class="alert__title">Information</div>
                        <div><?= h($info) ?></div>
                    </div>
                <?php endif; ?>

                <h2 class="service-card__title">Filtrer</h2>
                <form method="get" action="account-orders.php" class="actions actions--end mt-2">
                    <div class="field">
                        <label for="status">Statut</label>
                        <select class="form-control form-control--sm" id="status" name="status">
                            <?php foreach ($statuses as $key => $label): ?>
                                <option value="<?= h((string)$key) ?>" <?= $status === (string)$key ? 'selected' : '' ?>><?= h((string)$label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="q">Recherche</label>
                        <input class="form-control form-control--sm" id="q" name="q" value="<?= h($q) ?>" placeholder="Référence (ex: TF-...)" />
                    </div>

                    <div class="actions mt-2">
                        <button class="btn btn--primary" type="submit">Appliquer</button>
                        <a class="btn btn--secondary" href="account-orders.php">Réinitialiser</a>
                    </div>
                </form>

                <h2 class="service-card__title mt-3">Liste</h2>
                <?php if (app_frontend_only()): ?>
                    <div class="alert alert--error" role="alert">
                        <div class="alert__title">Mode design</div>
                        <div>La base est désactivée (APP_FRONTEND_ONLY). La liste s’affichera quand le back sera activé.</div>
                    </div>
                <?php endif; ?>

                <?php if (!count($orders)): ?>
                    <p class="service-card__text">Aucune commande.</p>
                <?php else: ?>
                    <div class="stack stack--sm">
                        <?php foreach ($orders as $o): ?>
                            <article class="service-card">
                                <div class="service-card__body">
                                    <h3 class="service-card__title"><?= h((string)$o['order_ref']) ?></h3>
                                    <p class="service-card__text">
                                        <strong>Statut:</strong> <?= h((string)$o['status']) ?><br>
                                        <strong>Total:</strong> <?= h(money((float)$o['total'], (string)$o['currency'])) ?><br>
                                        <strong>Date:</strong> <?= h((string)$o['created_at']) ?>
                                    </p>
                                    <div class="actions">
                                        <a class="btn btn--secondary" href="<?= h(url_with_params('order.php', ['order' => (string)$o['order_ref']])) ?>">Détails</a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($pageCount > 1): ?>
                        <div class="actions mt-3">
                            <?php if ($page > 1): ?>
                                <a class="btn btn--secondary" href="<?= h(url_with_params('account-orders.php', ['status' => $status, 'q' => $q, 'page' => $page - 1])) ?>">Précédent</a>
                            <?php endif; ?>
                            <div class="service-card__text">Page <?= h((string)$page) ?> / <?= h((string)$pageCount) ?></div>
                            <?php if ($page < $pageCount): ?>
                                <a class="btn btn--secondary" href="<?= h(url_with_params('account-orders.php', ['status' => $status, 'q' => $q, 'page' => $page + 1])) ?>">Suivant</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </article>
    </div>
</section>

<?php require __DIR__ . '/layout_bottom.php'; ?>


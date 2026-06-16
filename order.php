<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_auth.php';
require_once __DIR__ . '/lib_orders.php';

if (!app_frontend_only()) {
    auth_require_login('account.php');
}
$u = auth_user();
if (!$u) {
    if (!app_frontend_only()) {
        redirect(url_with_params('auth/login.php', ['next' => 'account.php']));
    }
    // Mode design: utilisateur demo (sans commande reelle).
    $u = ['id' => 1];
}

$orderRef = trim((string)($_GET['order'] ?? ''));
$order = $orderRef !== '' ? order_get_for_user_by_ref((int)$u['id'], $orderRef) : null;

if (!$order) {
    http_response_code(404);
    $pageTitle = 'Commande introuvable | Timbuktu Farming';
    require __DIR__ . '/layout_top.php';
    ?>
    <section class="services">
        <article class="service-card">
            <div class="service-card__body">
                    <h1 class="service-card__title">Commande introuvable</h1>
                <p class="service-card__text">Aucune commande trouvee pour <?= h($orderRef) ?> dans votre compte.</p>
                <a class="btn btn--secondary" href="account.php">Retour au compte</a>
            </div>
        </article>
    </section>
    <?php
    require __DIR__ . '/layout_bottom.php';
    exit;
}

$items = order_items((int)$order['id']);

$pageTitle = 'Commande ' . $orderRef . ' | Timbuktu Farming';
$pageDescription = 'Details de la commande.';
require __DIR__ . '/layout_top.php';
?>

<section class="services" aria-labelledby="order-title">
    <header class="services__header" data-reveal style="--reveal-delay: 0ms;">
        <p class="services__eyebrow">Commandes</p>
        <h1 class="services__title" id="order-title"><?= h((string)$order['order_ref']) ?></h1>
        <p class="services__lead">Statut: <strong><?= h((string)$order['status']) ?></strong></p>
    </header>

    <div class="services__grid services__grid--single">
        <article class="service-card">
            <div class="service-card__body">
                <h2 class="service-card__title">Resume</h2>
                <p class="service-card__text">
                    <strong>Sous-total:</strong> <?= h(money((float)$order['subtotal'], (string)$order['currency'])) ?><br>
                    <strong>Livraison:</strong> <?= h(money((float)$order['shipping'], (string)$order['currency'])) ?><br>
                    <strong>Taxes:</strong> <?= h(money((float)$order['taxes'], (string)$order['currency'])) ?><br>
                    <strong>Total:</strong> <?= h(money((float)$order['total'], (string)$order['currency'])) ?>
                </p>
                <p class="service-card__text">
                    <strong>Livraison:</strong> <?= h((string)$order['delivery_method']) ?> (<?= h((string)$order['delivery_estimate_days']) ?> jours)<br>
                    <strong>Pays:</strong> <?= h((string)$order['shipping_country']) ?>
                </p>
            </div>
        </article>

        <article class="service-card">
            <div class="service-card__body">
                <h2 class="service-card__title">Articles</h2>
                <?php if (!count($items)): ?>
                    <p class="service-card__text">Aucun article.</p>
                <?php else: ?>
                    <div class="stack stack--sm">
                        <?php foreach ($items as $it): ?>
                            <div class="alert">
                                <div class="alert__title"><?= h((string)$it['name']) ?></div>
                                <div>SKU <?= h((string)$it['sku']) ?> — Quantite <?= h((string)$it['qty']) ?> — <?= h(money((float)$it['line_total'], (string)$order['currency'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="actions mt-2">
                    <a class="btn btn--secondary" href="account.php">Retour au compte</a>
                </div>
            </div>
        </article>
    </div>
</section>

<?php require __DIR__ . '/layout_bottom.php'; ?>


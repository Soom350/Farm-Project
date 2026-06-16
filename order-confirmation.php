<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_bootstrap.php';
require_once __DIR__ . '/lib_auth.php';
require_once __DIR__ . '/lib_orders.php';

$orderRef = trim((string)($_GET['order'] ?? ''));

auth_require_login('account.php');
$u = auth_user();
$order = ($u && $orderRef !== '') ? order_get_for_user_by_ref((int)$u['id'], $orderRef) : null;

if (!$order) {
    http_response_code(404);
    $pageTitle = 'Commande introuvable | Timbuktu Farming';
    require __DIR__ . '/layout_top.php';
    ?>
    <section class="services">
        <article class="service-card">
            <div class="service-card__body">
                <h1 class="service-card__title">Commande introuvable</h1>
                <p class="service-card__text">Reference inconnue: <?= h($orderRef) ?></p>
                <a class="btn btn--primary" href="products.php">Retour au catalogue</a>
            </div>
        </article>
    </section>
    <?php
    require __DIR__ . '/layout_bottom.php';
    exit;
}

$pageTitle = 'Confirmation | ' . $orderRef;
$pageDescription = 'Confirmation de commande et informations post-paiement.';
require __DIR__ . '/layout_top.php';
?>

<section class="services" aria-labelledby="confirm-title">
    <header class="services__header" data-reveal style="--reveal-delay: 0ms;">
        <p class="services__eyebrow">Post-paiement</p>
        <h1 class="services__title" id="confirm-title">Merci. Commande confirmee.</h1>
        <p class="services__lead">Reference: <strong><?= h((string)$order['order_ref']) ?></strong> — Statut: <strong><?= h((string)$order['status']) ?></strong></p>
    </header>

    <div class="services__grid services__grid--single">
        <article class="service-card">
            <div class="service-card__body">
                <h2 class="service-card__title">Resume de commande</h2>
                <p class="service-card__text">
                    <strong>Sous-total:</strong> <?= h(money((float)$order['subtotal'], (string)$order['currency'])) ?><br>
                    <strong>Livraison:</strong> <?= h(money((float)$order['shipping'], (string)$order['currency'])) ?><br>
                    <strong>Taxes:</strong> <?= h(money((float)$order['taxes'], (string)$order['currency'])) ?><br>
                    <strong>Total:</strong> <?= h(money((float)$order['total'], (string)$order['currency'])) ?>
                </p>

                <?php $items = order_items((int)$order['id']); ?>
                <div class="stack stack--sm">
                    <?php foreach ($items as $it): ?>
                        <div class="footer-muted">
                            <?= h((string)$it['name']) ?> — SKU <?= h((string)$it['sku']) ?> — Qté <?= h((string)$it['qty']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </article>

        <article class="service-card">
            <div class="service-card__body">
                <h2 class="service-card__title">Notifications et facture</h2>
                <p class="service-card__text">
                    <strong>Email de confirmation:</strong> requis (demo: non envoye).<br>
                    <strong>Facture:</strong> <?= !empty($order['legal']['want_invoice']) ? 'demandee' : 'non demandee' ?> (demo: non generee).<br>
                    <strong>Support:</strong> info@timbuktufarming.com
                </p>
            </div>
        </article>

        <article class="service-card">
            <div class="service-card__body">
                <h2 class="service-card__title">Suivi</h2>
                <p class="service-card__text">
                    <strong>Livraison:</strong> <?= h((string)($order['delivery_method'] ?? '')) ?> — delai estime <?= h((string)($order['delivery_estimate_days'] ?? '')) ?> jours.<br>
                    <strong>Suivi:</strong> disponible apres expedition (demo).
                </p>
                <div class="actions">
                    <a class="btn btn--primary" href="<?= h(url_with_params('order.php', ['order' => (string)$order['order_ref']])) ?>">Voir les details</a>
                    <a class="btn btn--secondary" href="products.php">Continuer les achats</a>
                </div>
            </div>
        </article>
    </div>
</section>

<?php require __DIR__ . '/layout_bottom.php'; ?>


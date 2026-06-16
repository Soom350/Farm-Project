<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_bootstrap.php';
require_once __DIR__ . '/lib_auth.php';
require_once __DIR__ . '/lib_orders.php';

$orderRef = trim((string)($_GET['order'] ?? ''));

auth_require_login('account.php');
$u = auth_user();
$order = ($u && $orderRef !== '') ? order_get_for_user_by_ref((int)$u['id'], $orderRef) : null;

$pageTitle = 'Suivi commande | Timbuktu Farming';
$pageDescription = 'Statut commande, événements, et tracking.';
require __DIR__ . '/layout_top.php';
?>

<section class="services" aria-labelledby="status-title">
    <header class="services__header" data-reveal style="--reveal-delay: 0ms;">
        <p class="services__eyebrow">Suivi</p>
        <h1 class="services__title" id="status-title">Suivi de commande</h1>
        <p class="services__lead">Consultez le statut, les evenements et les informations de suivi.</p>
    </header>

    <div class="services__grid services__grid--single">
        <article class="service-card">
            <div class="service-card__body">
                <h2 class="service-card__title">Rechercher une commande</h2>
                <form method="get" action="order-status.php" class="actions actions--end">
                    <div class="field">
                        <label for="order">Reference</label>
                        <input class="form-control form-control--sm" id="order" name="order" type="text" value="<?= h($orderRef) ?>" placeholder="TF-...." required>
                    </div>
                    <button class="btn btn--primary" type="submit">Afficher</button>
                </form>
            </div>
        </article>

        <?php if (!$order && $orderRef !== ''): ?>
            <article class="service-card">
                <div class="service-card__body">
                    <h2 class="service-card__title">Commande introuvable</h2>
                    <p class="service-card__text">Aucune commande trouvee pour <?= h($orderRef) ?> dans votre compte.</p>
                </div>
            </article>
        <?php endif; ?>

        <?php if ($order): ?>
            <article class="service-card">
                <div class="service-card__body">
                    <h2 class="service-card__title">Statut</h2>
                    <p class="service-card__text">
                        <strong>Reference:</strong> <?= h((string)$order['order_ref']) ?><br>
                        <strong>Statut:</strong> <?= h((string)$order['status']) ?><br>
                        <strong>Creee le:</strong> <?= h((string)$order['created_at']) ?>
                    </p>
                    <p class="service-card__text">
                        <strong>Suivi:</strong>
                        indisponible (demo)
                    </p>
                    <div class="actions">
                        <a class="btn btn--secondary" href="<?= h(url_with_params('order.php', ['order' => (string)$order['order_ref']])) ?>">Details</a>
                    </div>
                </div>
            </article>

            <article class="service-card">
                <div class="service-card__body">
                    <h2 class="service-card__title">Evenements</h2>
                    <p class="service-card__text">A connecter a un systeme transport + webhooks en production.</p>
                </div>
            </article>

            <article class="service-card">
                <div class="service-card__body">
                    <h2 class="service-card__title">Support</h2>
                    <p class="service-card__text">
                        Pour toute question: info@timbuktufarming.com — mentionnez votre reference commande.
                    </p>
                </div>
            </article>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/layout_bottom.php'; ?>


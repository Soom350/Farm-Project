<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_auth.php';
require_once __DIR__ . '/lib_orders.php';
require_once __DIR__ . '/lib_account.php';

if (!app_frontend_only()) {
    auth_require_login('account.php');
}
$u = auth_user();

if (!$u) {
    if (!app_frontend_only()) {
        redirect(url_with_params('auth/login.php', ['next' => 'account.php']));
    }
    // Mode design: profil démo pour travailler le design de la page.
    $u = [
        'id' => 1,
        'email' => 'demo@timbuktu-farming.test',
        'full_name' => 'Utilisateur Démo',
        'phone' => '+1 555 0100',
        'company_name' => 'Timbuktu Demo Co.',
        'email_verified_at' => null,
    ];
}

// Resend verification
if (is_post() && ($_POST['action'] ?? '') === 'resend_verification') {
    require_csrf();
    if (app_frontend_only()) {
        flash_set('info', 'Mode design: renvoi d’email désactivé pour le moment.');
    } else {
        auth_send_verification_email((int)$u['id']);
        flash_set('info', 'Email de vérification renvoyé (démo: voir data/mail.log).');
    }
    redirect('account.php');
}

$info = flash_get('info');
$stats = account_dashboard_stats((int)$u['id']);
$orders = orders_list_for_user((int)$u['id'], 5);

$pageTitle = 'Mon compte | Timbuktu Farming';
$pageDescription = 'Compte, vérification, commandes.';
require __DIR__ . '/layout_top.php';
?>

<section class="services" aria-labelledby="account-title">
    <header class="services__header" data-reveal style="--reveal-delay: 0ms;">
        <p class="services__eyebrow">Account</p>
        <h1 class="services__title" id="account-title">Mon compte</h1>
        <p class="services__lead">Profil, adresses, commandes et support.</p>
    </header>

    <div class="actions mt-2">
        <a class="btn btn--primary" href="account.php">Vue d’ensemble</a>
        <a class="btn btn--secondary" href="account-profile.php">Profil</a>
        <a class="btn btn--secondary" href="account-addresses.php">Adresses</a>
        <a class="btn btn--secondary" href="account-orders.php">Commandes</a>
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

                <h2 class="service-card__title">Résumé</h2>
                <?php if (app_frontend_only()): ?>
                    <div class="alert alert--error" role="alert">
                        <div class="alert__title">Mode design</div>
                        <div>Le back est désactivé (APP_FRONTEND_ONLY). Les stats/commandes se rempliront quand tu activeras la base.</div>
                    </div>
                <?php endif; ?>

                <p class="service-card__text">
                    <strong>Commandes:</strong> <?= h((string)($stats['orders_count'] ?? 0)) ?><br>
                    <strong>Total dépensé:</strong> <?= h(money((float)($stats['total_spent'] ?? 0.0))) ?>
                </p>

                <?php if (is_array($stats['last_order'] ?? null)): ?>
                    <?php $lo = $stats['last_order']; ?>
                    <div class="alert">
                        <div class="alert__title">Dernière commande</div>
                        <div>
                            <?= h((string)$lo['order_ref']) ?> — <strong><?= h((string)$lo['status']) ?></strong>
                            — <?= h(money((float)$lo['total'], (string)$lo['currency'])) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </article>

        <article class="service-card">
            <div class="service-card__body">
                <h2 class="service-card__title">Profil</h2>
                <p class="service-card__text">
                    <strong>Email:</strong> <?= h((string)$u['email']) ?><br>
                    <strong>Nom:</strong> <?= h((string)$u['full_name']) ?><br>
                    <strong>Téléphone:</strong> <?= h((string)($u['phone'] ?? '')) ?><br>
                    <strong>Entreprise:</strong> <?= h((string)($u['company_name'] ?? '')) ?>
                </p>
                <div class="actions">
                    <a class="btn btn--secondary" href="account-profile.php">Modifier le profil</a>
                    <a class="btn btn--secondary" href="account-addresses.php">Gérer les adresses</a>
                </div>

                <h3 class="service-card__title mt-3">Vérification email</h3>
                <?php if (auth_is_email_verified($u)): ?>
                    <div class="alert">
                        <div class="alert__title">OK</div>
                        <div>Email vérifié.</div>
                    </div>
                <?php else: ?>
                    <div class="alert alert--error" role="alert">
                        <div class="alert__title">Action requise</div>
                        <div>Votre email n’est pas vérifié. Vous ne pourrez pas finaliser une commande.</div>
                    </div>
                    <form method="post" action="account.php" class="actions mt-2">
                        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="resend_verification">
                        <button class="btn btn--primary" type="submit">Renvoyer l’email de vérification</button>
                        <a class="btn btn--secondary" href="auth/verify-required.php?next=checkout.php">Aller au checkout</a>
                    </form>
                <?php endif; ?>
            </div>
        </article>

        <article class="service-card">
            <div class="service-card__body">
                <h2 class="service-card__title">Commandes récentes</h2>
                <?php if (!count($orders)): ?>
                    <p class="service-card__text">Aucune commande.</p>
                <?php else: ?>
                    <div class="stack stack--sm">
                        <?php foreach ($orders as $o): ?>
                            <div class="alert">
                                <div class="alert__title"><?= h((string)$o['order_ref']) ?></div>
                                <div>
                                    <strong><?= h((string)$o['status']) ?></strong>
                                    — <?= h(money((float)$o['total'], (string)$o['currency'])) ?>
                                    — <?= h((string)$o['created_at']) ?>
                                </div>
                                <div class="actions mt-2">
                                    <a class="btn btn--secondary" href="<?= h(url_with_params('order.php', ['order' => (string)$o['order_ref']])) ?>">Détails</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="actions mt-2">
                    <a class="btn btn--secondary" href="account-orders.php">Voir toutes les commandes</a>
                </div>

                <h3 class="service-card__title mt-3">Support</h3>
                <p class="service-card__text">Contact: info@freshseason.com (mentionnez votre référence commande).</p>
            </div>
        </article>
    </div>
</section>

<?php require __DIR__ . '/layout_bottom.php'; ?>


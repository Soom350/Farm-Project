<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib_auth.php';

$next = safe_next_url('checkout.php');
$u = auth_user();
if (!$u) {
    if (!app_frontend_only()) {
        redirect(url_with_params('login.php', ['next' => $next]));
    }
    // Mode design: utilisateur "démo" pour rendre la page sans back.
    $u = [
        'id' => 1,
        'email' => 'demo@timbuktu-farming.test',
        'full_name' => 'Utilisateur Démo',
    ];
}

if (is_post()) {
    require_csrf();
    if (app_frontend_only()) {
        flash_set('info', 'Mode design: renvoi d’email désactivé pour le moment.');
    } else {
        auth_send_verification_email((int)$u['id']);
        flash_set('info', 'Email de vérification renvoyé (voir data/mail.log).');
    }
    redirect(url_with_params('verify-required.php', ['next' => $next]));
}

$info = flash_get('info');
$pageTitle = 'Vérification requise | Timbuktu Farming';
$pageDescription = 'Vérification email requise avant achat.';
require __DIR__ . '/../layout_top.php';
?>

<section class="services" aria-labelledby="verify-required-title">
    <header class="services__header" data-reveal style="--reveal-delay: 0ms;">
        <p class="services__eyebrow">Compte</p>
        <h1 class="services__title" id="verify-required-title">Vérification email requise</h1>
        <p class="services__lead">Avant tout achat, votre adresse email doit être vérifiée.</p>
    </header>

    <div class="services__grid services__grid--single">
        <article class="service-card">
            <div class="service-card__body">
                <?php if ($info): ?>
                    <div class="alert">
                        <div class="alert__title">Information</div>
                        <div><?= h($info) ?></div>
                    </div>
                <?php endif; ?>

                <p class="service-card__text">
                    Connecté en tant que <strong><?= h((string)$u['email']) ?></strong>.
                </p>

                <form method="post" action="<?= h(url_with_params('verify-required.php', ['next' => $next])) ?>" class="actions">
                    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="next" value="<?= h($next) ?>">
                    <button class="btn btn--primary" type="submit">Renvoyer l’email de vérification</button>
                    <a class="btn btn--secondary" href="../account.php">Aller au compte</a>
                </form>

                <p class="service-card__text mt-2">
                    Après vérification, vous serez redirigé vers: <strong><?= h($next) ?></strong>
                </p>
            </div>
        </article>
    </div>
</section>

<?php require __DIR__ . '/../layout_bottom.php'; ?>


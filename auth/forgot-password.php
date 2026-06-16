<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib_auth.php';

$next = safe_next_url('index.php');
$info = null;
$email = '';

if (is_post()) {
    require_csrf();
    $email = (string)($_POST['email'] ?? '');
    if (app_frontend_only()) {
        $info = 'Mode design: envoi de lien désactivé pour le moment.';
    } else {
        auth_request_password_reset($email);
        // Message non révélateur (anti-enum)
        $info = 'Si un compte existe pour cet email, un lien de réinitialisation a été envoyé (démo: voir data/mail.log).';
    }
}

$pageTitle = 'Mot de passe oublié | Timbuktu Farming';
$pageDescription = 'Réinitialisation mot de passe.';
require __DIR__ . '/../layout_top.php';
?>

<section class="services" aria-labelledby="fp-title">
    <header class="services__header" data-reveal style="--reveal-delay: 0ms;">
        <p class="services__eyebrow">Compte</p>
        <h1 class="services__title" id="fp-title">Mot de passe oublié</h1>
        <p class="services__lead">Recevez un lien sécurisé de réinitialisation.</p>
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

                <form method="post" action="<?= h(url_with_params('forgot-password.php', ['next' => $next])) ?>" class="form form--narrow">
                    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="next" value="<?= h($next) ?>">

                    <div class="field">
                        <label for="email">Email</label>
                        <input class="form-control" id="email" name="email" type="email" value="<?= h($email) ?>" required>
                    </div>

                    <div class="actions">
                        <button class="btn btn--primary" type="submit">Envoyer le lien</button>
                        <a class="btn btn--secondary" href="<?= h(url_with_params('login.php', ['next' => $next])) ?>">Retour connexion</a>
                    </div>
                </form>
            </div>
        </article>
    </div>
</section>

<?php require __DIR__ . '/../layout_bottom.php'; ?>


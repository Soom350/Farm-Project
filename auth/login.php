<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib_auth.php';

$errors = [];
$next = safe_next_url('checkout.php');
$email = '';

if (is_post()) {
    require_csrf();
    $email = (string)($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');

    // Mode design: on garde l'écran (UI) mais on neutralise la logique back.
    if (app_frontend_only()) {
        $errors = ['Mode design: connexion désactivée pour le moment.'];
    } else {
        $res = auth_login($email, $password, $ip);
        if ($res['ok'] ?? false) {
            // Si checkout demandé, forcer vérification email avant achat
            if (str_starts_with($next, 'checkout.php') && !auth_is_email_verified()) {
                redirect(url_with_params('verify-required.php', ['next' => $next]));
            }
            redirect($next);
        }
        $errors = (array)($res['errors'] ?? ['Connexion impossible.']);
    }
}

$pageTitle = 'Connexion | Timbuktu Farming';
$pageDescription = 'Connexion sécurisée. Compte requis pour acheter.';
require __DIR__ . '/../layout_top.php';
?>

<section class="services" aria-labelledby="login-page-title">
    <header class="services__header" data-reveal style="--reveal-delay: 0ms;">
        <p class="services__eyebrow">Compte</p>
        <h1 class="services__title" id="login-page-title">Connexion</h1>
        <p class="services__lead">Les achats nécessitent un compte.</p>
    </header>

    <div class="services__grid services__grid--single">
        <article class="service-card">
            <div class="service-card__body">
                <?php if ($errors): ?>
                    <div class="alert alert--error" role="alert">
                        <div class="alert__title">Erreur</div>
                        <ul class="alert__list">
                            <?php foreach ($errors as $e): ?>
                                <li><?= h((string)$e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= h(url_with_params('login.php', ['next' => $next])) ?>" class="form form--narrow">
                    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="next" value="<?= h($next) ?>">

                    <div class="grid grid--2">
                        <div class="field">
                            <label for="email">Email</label>
                            <input class="form-control" id="email" name="email" type="email" autocomplete="email" value="<?= h($email) ?>" required>
                        </div>
                        <div class="field">
                            <label for="password">Mot de passe</label>
                            <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
                        </div>
                    </div>

                    <div class="actions">
                        <button class="btn btn--primary" type="submit">Se connecter</button>
                        <a class="btn btn--secondary" href="<?= h(url_with_params('signup.php', ['next' => $next])) ?>">Créer un compte</a>
                        <a class="btn btn--secondary" href="<?= h(url_with_params('forgot-password.php', ['next' => $next])) ?>">Mot de passe oublié</a>
                    </div>
                </form>
            </div>
        </article>
    </div>
</section>

<?php require __DIR__ . '/../layout_bottom.php'; ?>


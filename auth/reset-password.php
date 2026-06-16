<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib_auth.php';

$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$errors = [];
$done = false;

if (is_post()) {
    require_csrf();
    $pw = (string)($_POST['password'] ?? '');
    $pw2 = (string)($_POST['password_confirm'] ?? '');
    if ($pw !== $pw2) $errors[] = 'Les mots de passe ne correspondent pas.';

    if (app_frontend_only()) {
        $errors[] = 'Mode design: réinitialisation désactivée pour le moment.';
    } elseif (!$errors) {
        $res = auth_reset_password($token, $pw);
        if ($res['ok'] ?? false) {
            $done = true;
        } else {
            $errors = (array)($res['errors'] ?? ['Erreur.']);
        }
    }
}

$pageTitle = 'Réinitialiser le mot de passe | Timbuktu Farming';
$pageDescription = 'Réinitialisation mot de passe.';
require __DIR__ . '/../layout_top.php';
?>

<section class="services" aria-labelledby="rp-title">
    <header class="services__header" data-reveal style="--reveal-delay: 0ms;">
        <p class="services__eyebrow">Compte</p>
        <h1 class="services__title" id="rp-title">Réinitialiser le mot de passe</h1>
        <p class="services__lead">Lien à usage unique.</p>
    </header>

    <div class="services__grid services__grid--single">
        <article class="service-card">
            <div class="service-card__body">
                <?php if ($done): ?>
                    <div class="alert">
                        <div class="alert__title">Succès</div>
                        <div>Mot de passe mis à jour.</div>
                    </div>
                    <div class="actions mt-2">
                        <a class="btn btn--primary" href="login.php">Se connecter</a>
                    </div>
                <?php else: ?>
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

                    <form method="post" action="<?= h(url_with_params('reset-password.php', ['token' => $token])) ?>" class="form form--narrow">
                        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="token" value="<?= h($token) ?>">

                        <div class="grid grid--2">
                            <div class="field">
                                <label for="password">Nouveau mot de passe</label>
                                <input class="form-control" id="password" name="password" type="password" autocomplete="new-password" required>
                                <div class="form-help">12+ caractères, majuscule, minuscule, chiffre, spécial.</div>
                            </div>
                            <div class="field">
                                <label for="password_confirm">Confirmer</label>
                                <input class="form-control" id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required>
                            </div>
                        </div>

                        <div class="actions">
                            <button class="btn btn--primary" type="submit">Mettre à jour</button>
                            <a class="btn btn--secondary" href="login.php">Annuler</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </article>
    </div>
</section>

<?php require __DIR__ . '/../layout_bottom.php'; ?>


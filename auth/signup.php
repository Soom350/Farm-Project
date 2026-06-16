<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib_auth.php';

$errors = [];
$next = safe_next_url('checkout.php');

$values = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'company_name' => '',
];

if (is_post()) {
    require_csrf();
    foreach ($values as $k => $v) {
        $values[$k] = trim((string)($_POST[$k] ?? ''));
    }
    // Compat: signup dialog (index.php) envoie firstName/lastName
    if ($values['full_name'] === '') {
        $fn = trim((string)($_POST['firstName'] ?? ''));
        $ln = trim((string)($_POST['lastName'] ?? ''));
        $values['full_name'] = trim($fn . ' ' . $ln);
    }
    $password = (string)($_POST['password'] ?? '');
    $password2 = (string)($_POST['password_confirm'] ?? '');

    if ($password !== $password2) {
        $errors[] = 'Les mots de passe ne correspondent pas.';
    }

    // Mode design: on garde l'écran (UI) mais on neutralise la logique back.
    if (app_frontend_only()) {
        $errors[] = 'Mode design: inscription désactivée pour le moment.';
    } elseif (!$errors) {
        $res = auth_create_user([
            'full_name' => $values['full_name'],
            'email' => $values['email'],
            'phone' => $values['phone'],
            'company_name' => $values['company_name'],
            'password' => $password,
            // Compat: signup dialog fournit adresse/city/state/zip
            'address' => (string)($_POST['address'] ?? ''),
            'city' => (string)($_POST['city'] ?? ''),
            'state' => (string)($_POST['state'] ?? ''),
            'zip' => (string)($_POST['zip'] ?? ''),
        ]);

        if ($res['ok'] ?? false) {
            // Auto-login après inscription (compte non vérifié → achat bloqué)
            $_SESSION['user_id'] = (int)$res['user_id'];
            session_regenerate_id(true);
            redirect(url_with_params('verify-required.php', ['next' => $next]));
        }
        $errors = (array)($res['errors'] ?? ['Inscription impossible.']);
    }
}

$pageTitle = 'Créer un compte | Timbuktu Farming';
$pageDescription = 'Inscription sécurisée + vérification email obligatoire avant achat.';
require __DIR__ . '/../layout_top.php';
?>

<section class="services" aria-labelledby="signup-page-title">
    <header class="services__header" data-reveal style="--reveal-delay: 0ms;">
        <p class="services__eyebrow">Compte</p>
        <h1 class="services__title" id="signup-page-title">Créer un compte</h1>
        <p class="services__lead">Vérification email requise avant le premier achat.</p>
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

                <form method="post" action="<?= h(url_with_params('signup.php', ['next' => $next])) ?>" class="form form--narrow">
                    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="next" value="<?= h($next) ?>">

                    <div class="grid grid--2">
                        <div class="field">
                            <label for="full_name">Nom complet</label>
                            <input class="form-control" id="full_name" name="full_name" type="text" value="<?= h($values['full_name']) ?>" autocomplete="name" required>
                        </div>
                        <div class="field">
                            <label for="email">Email</label>
                            <input class="form-control" id="email" name="email" type="email" value="<?= h($values['email']) ?>" autocomplete="email" required>
                        </div>
                        <div class="field">
                            <label for="phone">Téléphone</label>
                            <input class="form-control" id="phone" name="phone" type="tel" value="<?= h($values['phone']) ?>" autocomplete="tel" required>
                        </div>
                        <div class="field">
                            <label for="company_name">Société (optionnel)</label>
                            <input class="form-control" id="company_name" name="company_name" type="text" value="<?= h($values['company_name']) ?>">
                        </div>
                    </div>

                    <div class="grid grid--2">
                        <div class="field">
                            <label for="password">Mot de passe</label>
                            <input class="form-control" id="password" name="password" type="password" autocomplete="new-password" required>
                            <div class="form-help">12+ caractères, majuscule, minuscule, chiffre, spécial.</div>
                        </div>
                        <div class="field">
                            <label for="password_confirm">Confirmer</label>
                            <input class="form-control" id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required>
                        </div>
                    </div>

                    <div class="actions">
                        <button class="btn btn--primary" type="submit">Créer le compte</button>
                        <a class="btn btn--secondary" href="<?= h(url_with_params('login.php', ['next' => $next])) ?>">J’ai déjà un compte</a>
                    </div>
                </form>
            </div>
        </article>
    </div>
</section>

<?php require __DIR__ . '/../layout_bottom.php'; ?>


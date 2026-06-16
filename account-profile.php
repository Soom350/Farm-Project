<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_auth.php';
require_once __DIR__ . '/lib_account.php';

if (!app_frontend_only()) {
    auth_require_login('account-profile.php');
}
$u = auth_user();
if (!$u) {
    if (!app_frontend_only()) {
        redirect(url_with_params('auth/login.php', ['next' => 'account-profile.php']));
    }
    // Mode design: user démo pour travailler l'UI.
    $u = [
        'id' => 1,
        'email' => 'demo@timbuktu-farming.test',
        'full_name' => 'Utilisateur Démo',
        'phone' => '+1 555 0100',
        'company_name' => 'Timbuktu Demo Co.',
        'email_verified_at' => null,
    ];
}

$errorsProfile = [];
$errorsPassword = [];
$info = flash_get('info');

$profileValues = [
    'full_name' => (string)($u['full_name'] ?? ''),
    'phone' => (string)($u['phone'] ?? ''),
    'company_name' => (string)($u['company_name'] ?? ''),
];

if (is_post()) {
    require_csrf();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'update_profile') {
        $profileValues['full_name'] = trim((string)($_POST['full_name'] ?? ''));
        $profileValues['phone'] = trim((string)($_POST['phone'] ?? ''));
        $profileValues['company_name'] = trim((string)($_POST['company_name'] ?? ''));

        $res = account_update_profile((int)$u['id'], $profileValues);
        if (($res['ok'] ?? false) === true) {
            flash_set('info', 'Profil mis à jour.');
            redirect('account-profile.php');
        }
        $errorsProfile = is_array($res['errors'] ?? null) ? (array)$res['errors'] : ['Erreur inconnue.'];
    }

    if ($action === 'change_password') {
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        if ($new !== $confirm) {
            $errorsPassword['confirm_password'] = 'La confirmation ne correspond pas.';
        } else {
            $res = account_change_password((int)$u['id'], $current, $new);
            if (($res['ok'] ?? false) === true) {
                flash_set('info', 'Mot de passe mis à jour.');
                redirect('account-profile.php');
            }
            $errorsPassword = is_array($res['errors'] ?? null) ? (array)$res['errors'] : ['Erreur inconnue.'];
        }
    }
}

$pageTitle = 'Mon profil | Timbuktu Farming';
$pageDescription = 'Profil, sécurité et informations de compte.';
require __DIR__ . '/layout_top.php';
?>

<section class="services" aria-labelledby="account-profile-title">
    <header class="services__header" data-reveal style="--reveal-delay: 0ms;">
        <p class="services__eyebrow">Account</p>
        <h1 class="services__title" id="account-profile-title">Profil</h1>
        <p class="services__lead">Modifiez vos informations et votre mot de passe.</p>
    </header>

    <div class="actions mt-2">
        <a class="btn btn--secondary" href="account.php">Vue d’ensemble</a>
        <a class="btn btn--primary" href="account-profile.php">Profil</a>
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

                <h2 class="service-card__title">Informations</h2>

                <p class="service-card__text">
                    <strong>Email:</strong> <?= h((string)($u['email'] ?? '')) ?>
                    <?php if (auth_is_email_verified($u)): ?>
                        <strong>(Vérifié)</strong>
                    <?php else: ?>
                        <strong>(Non vérifié)</strong>
                    <?php endif; ?>
                </p>

                <?php if ($errorsProfile): ?>
                    <div class="alert alert--error" role="alert">
                        <div class="alert__title">Erreur</div>
                        <div>Merci de corriger les champs ci-dessous.</div>
                    </div>
                <?php endif; ?>

                <form method="post" action="account-profile.php" class="stack stack--sm mt-2">
                    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="field">
                        <label for="full_name">Nom complet</label>
                        <input class="form-control" id="full_name" name="full_name" value="<?= h($profileValues['full_name']) ?>" autocomplete="name">
                        <?php if (isset($errorsProfile['full_name'])): ?><div class="form-error"><?= h((string)$errorsProfile['full_name']) ?></div><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="phone">Téléphone</label>
                        <input class="form-control" id="phone" name="phone" value="<?= h($profileValues['phone']) ?>" autocomplete="tel">
                        <?php if (isset($errorsProfile['phone'])): ?><div class="form-error"><?= h((string)$errorsProfile['phone']) ?></div><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="company_name">Entreprise (optionnel)</label>
                        <input class="form-control" id="company_name" name="company_name" value="<?= h($profileValues['company_name']) ?>" autocomplete="organization">
                    </div>

                    <div class="actions mt-2">
                        <button class="btn btn--primary" type="submit">Enregistrer</button>
                        <a class="btn btn--secondary" href="account.php">Retour</a>
                    </div>
                </form>
            </div>
        </article>

        <article class="service-card">
            <div class="service-card__body">
                <h2 class="service-card__title">Sécurité</h2>
                <p class="service-card__text">Changez votre mot de passe.</p>

                <?php if ($errorsPassword): ?>
                    <div class="alert alert--error" role="alert">
                        <div class="alert__title">Erreur</div>
                        <div>Merci de corriger les champs ci-dessous.</div>
                    </div>
                <?php endif; ?>

                <form method="post" action="account-profile.php" class="stack stack--sm mt-2">
                    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="action" value="change_password">

                    <div class="field">
                        <label for="current_password">Mot de passe actuel</label>
                        <input class="form-control" id="current_password" name="current_password" type="password" autocomplete="current-password">
                        <?php if (isset($errorsPassword['current_password'])): ?><div class="form-error"><?= h((string)$errorsPassword['current_password']) ?></div><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="new_password">Nouveau mot de passe</label>
                        <input class="form-control" id="new_password" name="new_password" type="password" autocomplete="new-password">
                        <div class="form-help">12 caractères minimum, avec majuscule, minuscule, chiffre et caractère spécial.</div>
                        <?php if (isset($errorsPassword['new_password'])): ?><div class="form-error"><?= h((string)$errorsPassword['new_password']) ?></div><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                        <input class="form-control" id="confirm_password" name="confirm_password" type="password" autocomplete="new-password">
                        <?php if (isset($errorsPassword['confirm_password'])): ?><div class="form-error"><?= h((string)$errorsPassword['confirm_password']) ?></div><?php endif; ?>
                    </div>

                    <div class="actions mt-2">
                        <button class="btn btn--primary" type="submit">Mettre à jour</button>
                    </div>
                </form>
            </div>
        </article>
    </div>
</section>

<?php require __DIR__ . '/layout_bottom.php'; ?>


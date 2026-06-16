<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_auth.php';
require_once __DIR__ . '/lib_account.php';
require_once __DIR__ . '/lib_checkout.php';

if (!app_frontend_only()) {
    auth_require_login('account-addresses.php');
}
$u = auth_user();
if (!$u) {
    if (!app_frontend_only()) {
        redirect(url_with_params('auth/login.php', ['next' => 'account-addresses.php']));
    }
    // Mode design: user démo.
    $u = [
        'id' => 1,
        'email' => 'demo@timbuktu-farming.test',
        'full_name' => 'Utilisateur Démo',
        'phone' => '+1 555 0100',
        'company_name' => 'Timbuktu Demo Co.',
        'default_shipping_country' => 'US',
        'default_shipping_line1' => '1 Demo Street',
        'default_shipping_line2' => '',
        'default_shipping_city' => 'Demo City',
        'default_shipping_region' => '',
        'default_shipping_postal_code' => '00000',
        'default_billing_country' => 'US',
        'default_billing_line1' => '1 Demo Street',
        'default_billing_line2' => '',
        'default_billing_city' => 'Demo City',
        'default_billing_region' => '',
        'default_billing_postal_code' => '00000',
    ];
}

$countries = checkout_countries();
$errors = [];
$info = flash_get('info');

$values = [
    'shipping_country' => (string)($u['default_shipping_country'] ?? 'US'),
    'shipping_line1' => (string)($u['default_shipping_line1'] ?? ''),
    'shipping_line2' => (string)($u['default_shipping_line2'] ?? ''),
    'shipping_city' => (string)($u['default_shipping_city'] ?? ''),
    'shipping_region' => (string)($u['default_shipping_region'] ?? ''),
    'shipping_postal_code' => (string)($u['default_shipping_postal_code'] ?? ''),

    'billing_same_as_shipping' => false,
    'billing_country' => (string)($u['default_billing_country'] ?? 'US'),
    'billing_line1' => (string)($u['default_billing_line1'] ?? ''),
    'billing_line2' => (string)($u['default_billing_line2'] ?? ''),
    'billing_city' => (string)($u['default_billing_city'] ?? ''),
    'billing_region' => (string)($u['default_billing_region'] ?? ''),
    'billing_postal_code' => (string)($u['default_billing_postal_code'] ?? ''),
];

if (is_post()) {
    require_csrf();

    foreach ($values as $k => $default) {
        if (is_bool($default)) {
            $values[$k] = isset($_POST[$k]) && ($_POST[$k] === '1' || $_POST[$k] === 'on');
        } else {
            $values[$k] = trim((string)($_POST[$k] ?? ''));
        }
    }

    // Validation pays
    if (!isset($countries[$values['shipping_country']])) $errors['shipping_country'] = 'Pays invalide.';
    if (!isset($countries[$values['billing_country']])) $errors['billing_country'] = 'Pays invalide.';

    $shipAddr = [
        'country' => $values['shipping_country'],
        'line1' => $values['shipping_line1'],
        'city' => $values['shipping_city'],
        'postal_code' => $values['shipping_postal_code'],
    ];
    $errors += checkout_validate_address($shipAddr, 'shipping_');

    $billSame = (bool)$values['billing_same_as_shipping'];
    if ($billSame) {
        $values['billing_country'] = $values['shipping_country'];
        $values['billing_line1'] = $values['shipping_line1'];
        $values['billing_line2'] = $values['shipping_line2'];
        $values['billing_city'] = $values['shipping_city'];
        $values['billing_region'] = $values['shipping_region'];
        $values['billing_postal_code'] = $values['shipping_postal_code'];
    }

    $billAddr = [
        'country' => $values['billing_country'],
        'line1' => $values['billing_line1'],
        'city' => $values['billing_city'],
        'postal_code' => $values['billing_postal_code'],
    ];
    $errors += checkout_validate_address($billAddr, 'billing_');

    if (!count($errors)) {
        $res = account_update_default_addresses(
            (int)$u['id'],
            [
                'country' => $values['shipping_country'],
                'line1' => $values['shipping_line1'],
                'line2' => $values['shipping_line2'],
                'city' => $values['shipping_city'],
                'region' => $values['shipping_region'],
                'postal_code' => $values['shipping_postal_code'],
            ],
            [
                'country' => $values['billing_country'],
                'line1' => $values['billing_line1'],
                'line2' => $values['billing_line2'],
                'city' => $values['billing_city'],
                'region' => $values['billing_region'],
                'postal_code' => $values['billing_postal_code'],
            ]
        );

        if (($res['ok'] ?? false) === true) {
            flash_set('info', 'Adresses mises à jour. Elles seront pré-remplies au checkout.');
            redirect('account-addresses.php');
        }

        $errors['form'] = is_array($res['errors'] ?? null) ? implode(' ', (array)$res['errors']) : 'Erreur inconnue.';
    }
}

$pageTitle = 'Mes adresses | Timbuktu Farming';
$pageDescription = 'Adresses de livraison et facturation par défaut.';
require __DIR__ . '/layout_top.php';
?>

<section class="services" aria-labelledby="account-addresses-title">
    <header class="services__header" data-reveal style="--reveal-delay: 0ms;">
        <p class="services__eyebrow">Account</p>
        <h1 class="services__title" id="account-addresses-title">Adresses</h1>
        <p class="services__lead">Gérez vos adresses par défaut (pré-remplies au checkout).</p>
    </header>

    <div class="actions mt-2">
        <a class="btn btn--secondary" href="account.php">Vue d’ensemble</a>
        <a class="btn btn--secondary" href="account-profile.php">Profil</a>
        <a class="btn btn--primary" href="account-addresses.php">Adresses</a>
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

                <?php if ($errors): ?>
                    <div class="alert alert--error" role="alert">
                        <div class="alert__title">Erreur</div>
                        <div>Merci de corriger les champs ci-dessous.</div>
                    </div>
                <?php endif; ?>

                <form method="post" action="account-addresses.php" class="stack stack--sm">
                    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">

                    <fieldset class="form-section">
                        <legend class="service-card__title">Adresse de livraison (par défaut)</legend>

                        <div class="field">
                            <label for="shipping_country">Pays</label>
                            <select class="form-control" id="shipping_country" name="shipping_country">
                                <?php foreach ($countries as $code => $label): ?>
                                    <option value="<?= h((string)$code) ?>" <?= $values['shipping_country'] === (string)$code ? 'selected' : '' ?>>
                                        <?= h((string)$label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['shipping_country'])): ?><div class="form-error"><?= h((string)$errors['shipping_country']) ?></div><?php endif; ?>
                        </div>

                        <div class="field">
                            <label for="shipping_line1">Adresse</label>
                            <input class="form-control" id="shipping_line1" name="shipping_line1" value="<?= h($values['shipping_line1']) ?>" autocomplete="shipping address-line1">
                            <?php if (isset($errors['shipping_line1'])): ?><div class="form-error"><?= h((string)$errors['shipping_line1']) ?></div><?php endif; ?>
                        </div>

                        <div class="field">
                            <label for="shipping_line2">Complément (optionnel)</label>
                            <input class="form-control" id="shipping_line2" name="shipping_line2" value="<?= h($values['shipping_line2']) ?>" autocomplete="shipping address-line2">
                        </div>

                        <div class="field">
                            <label for="shipping_city">Ville</label>
                            <input class="form-control" id="shipping_city" name="shipping_city" value="<?= h($values['shipping_city']) ?>" autocomplete="shipping address-level2">
                            <?php if (isset($errors['shipping_city'])): ?><div class="form-error"><?= h((string)$errors['shipping_city']) ?></div><?php endif; ?>
                        </div>

                        <div class="field">
                            <label for="shipping_region">Région/État (optionnel)</label>
                            <input class="form-control" id="shipping_region" name="shipping_region" value="<?= h($values['shipping_region']) ?>" autocomplete="shipping address-level1">
                        </div>

                        <div class="field">
                            <label for="shipping_postal_code">Code postal</label>
                            <input class="form-control" id="shipping_postal_code" name="shipping_postal_code" value="<?= h($values['shipping_postal_code']) ?>" autocomplete="shipping postal-code">
                            <?php if (isset($errors['shipping_postal_code'])): ?><div class="form-error"><?= h((string)$errors['shipping_postal_code']) ?></div><?php endif; ?>
                        </div>
                    </fieldset>

                    <fieldset class="form-section">
                        <legend class="service-card__title">Adresse de facturation (par défaut)</legend>

                        <div class="field field--checkbox">
                            <label>
                                <input type="checkbox" name="billing_same_as_shipping" value="1" <?= $values['billing_same_as_shipping'] ? 'checked' : '' ?>>
                                Identique à l’adresse de livraison
                            </label>
                        </div>

                        <div class="field">
                            <label for="billing_country">Pays</label>
                            <select class="form-control" id="billing_country" name="billing_country">
                                <?php foreach ($countries as $code => $label): ?>
                                    <option value="<?= h((string)$code) ?>" <?= $values['billing_country'] === (string)$code ? 'selected' : '' ?>>
                                        <?= h((string)$label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['billing_country'])): ?><div class="form-error"><?= h((string)$errors['billing_country']) ?></div><?php endif; ?>
                        </div>

                        <div class="field">
                            <label for="billing_line1">Adresse</label>
                            <input class="form-control" id="billing_line1" name="billing_line1" value="<?= h($values['billing_line1']) ?>" autocomplete="billing address-line1">
                            <?php if (isset($errors['billing_line1'])): ?><div class="form-error"><?= h((string)$errors['billing_line1']) ?></div><?php endif; ?>
                        </div>

                        <div class="field">
                            <label for="billing_line2">Complément (optionnel)</label>
                            <input class="form-control" id="billing_line2" name="billing_line2" value="<?= h($values['billing_line2']) ?>" autocomplete="billing address-line2">
                        </div>

                        <div class="field">
                            <label for="billing_city">Ville</label>
                            <input class="form-control" id="billing_city" name="billing_city" value="<?= h($values['billing_city']) ?>" autocomplete="billing address-level2">
                            <?php if (isset($errors['billing_city'])): ?><div class="form-error"><?= h((string)$errors['billing_city']) ?></div><?php endif; ?>
                        </div>

                        <div class="field">
                            <label for="billing_region">Région/État (optionnel)</label>
                            <input class="form-control" id="billing_region" name="billing_region" value="<?= h($values['billing_region']) ?>" autocomplete="billing address-level1">
                        </div>

                        <div class="field">
                            <label for="billing_postal_code">Code postal</label>
                            <input class="form-control" id="billing_postal_code" name="billing_postal_code" value="<?= h($values['billing_postal_code']) ?>" autocomplete="billing postal-code">
                            <?php if (isset($errors['billing_postal_code'])): ?><div class="form-error"><?= h((string)$errors['billing_postal_code']) ?></div><?php endif; ?>
                        </div>
                    </fieldset>

                    <?php if (isset($errors['form'])): ?><div class="form-error"><?= h((string)$errors['form']) ?></div><?php endif; ?>

                    <div class="actions mt-2">
                        <button class="btn btn--primary" type="submit">Enregistrer</button>
                        <a class="btn btn--secondary" href="account.php">Retour</a>
                    </div>
                </form>
            </div>
        </article>
    </div>
</section>

<?php require __DIR__ . '/layout_bottom.php'; ?>


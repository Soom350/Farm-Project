<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_checkout.php';
require_once __DIR__ . '/lib_auth.php';
require_once __DIR__ . '/lib_db.php';

$next = 'checkout.php';
auth_require_login($next);
auth_require_verified_email($next);
$u = auth_user();

$cart = cart_totals();
if ($cart['qty_total'] <= 0) {
    $pageTitle = 'Paiement | Timbuktu Farming';
    require __DIR__ . '/layout_top.php';
    ?>
    <section class="services">
        <article class="service-card">
            <div class="service-card__body">
                <h1 class="service-card__title">Paiement</h1>
                <p class="service-card__text">Votre panier est vide. Ajoutez des produits avant de commander.</p>
                <a class="btn btn--primary" href="products.php">Voir les produits</a>
            </div>
        </article>
    </section>
    <?php
    require __DIR__ . '/layout_bottom.php';
    exit;
}

$countries = checkout_countries();
$shippingMethods = checkout_shipping_methods();

$errors = [];
$values = [
    // Pré-rempli depuis le compte (UX pro, sans redessiner)
    'customer_email' => (string)($u['email'] ?? ''),
    'customer_full_name' => (string)($u['full_name'] ?? ''),
    'customer_phone' => (string)($u['phone'] ?? ''),
    'company_name' => (string)($u['company_name'] ?? ''),
    'is_business' => false,
    'vat_id' => '',

    'shipping_country' => (string)($u['default_shipping_country'] ?? 'US'),
    'shipping_line1' => (string)($u['default_shipping_line1'] ?? ''),
    'shipping_line2' => (string)($u['default_shipping_line2'] ?? ''),
    'shipping_city' => (string)($u['default_shipping_city'] ?? ''),
    'shipping_region' => (string)($u['default_shipping_region'] ?? ''),
    'shipping_postal_code' => (string)($u['default_shipping_postal_code'] ?? ''),

    'billing_same_as_shipping' => true,
    'billing_country' => (string)($u['default_billing_country'] ?? 'US'),
    'billing_line1' => (string)($u['default_billing_line1'] ?? ''),
    'billing_line2' => (string)($u['default_billing_line2'] ?? ''),
    'billing_city' => (string)($u['default_billing_city'] ?? ''),
    'billing_region' => (string)($u['default_billing_region'] ?? ''),
    'billing_postal_code' => (string)($u['default_billing_postal_code'] ?? ''),

    'shipping_method' => 'standard',
    'payment_method' => 'card', // card | bank_transfer | wallet
    'card_last4' => '',

    'accept_terms' => false,
    'accept_privacy' => false,
    'want_invoice' => false,
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

    // 1) Customer info
    if ($values['customer_full_name'] === '') $errors['customer_full_name'] = 'Full name required.';
    if ($values['customer_email'] === '' || !filter_var($values['customer_email'], FILTER_VALIDATE_EMAIL)) {
        $errors['customer_email'] = 'Valid email required.';
    }
    if ($values['customer_phone'] === '') $errors['customer_phone'] = 'Phone required.';

    // 2) Addresses
    $shipAddr = [
        'country' => $values['shipping_country'],
        'line1' => $values['shipping_line1'],
        'city' => $values['shipping_city'],
        'postal_code' => $values['shipping_postal_code'],
    ];
    $errors += checkout_validate_address($shipAddr, 'shipping_');

    $billingSame = (bool)$values['billing_same_as_shipping'];
    if (!$billingSame) {
        $billAddr = [
            'country' => $values['billing_country'],
            'line1' => $values['billing_line1'],
            'city' => $values['billing_city'],
            'postal_code' => $values['billing_postal_code'],
        ];
        $errors += checkout_validate_address($billAddr, 'billing_');
    }

    // Country selection sanity
    if (!isset($countries[$values['shipping_country']])) $errors['shipping_country'] = 'Invalid country.';
    if (!$billingSame && !isset($countries[$values['billing_country']])) $errors['billing_country'] = 'Invalid country.';

    // 5) Delivery options
    if (!isset($shippingMethods[$values['shipping_method']])) {
        $errors['shipping_method'] = 'Invalid delivery option.';
    }

    // 4) Payment
    $pm = $values['payment_method'];
    if (!in_array($pm, ['card', 'bank_transfer', 'wallet'], true)) {
        $errors['payment_method'] = 'Invalid payment method.';
    }
    if ($pm === 'card') {
        if (!preg_match('/^\d{4}$/', $values['card_last4'])) {
            $errors['card_last4'] = 'Last 4 digits required.';
        }
        // Simulate a PSP refusal to test resilience
        if ($values['card_last4'] === '0000') {
            $errors['card_last4'] = 'Payment refused (simulation).';
        }
    }

    // 6) Legal
    if (!$values['accept_terms']) $errors['accept_terms'] = 'You must accept the terms and conditions.';
    if (!$values['accept_privacy']) $errors['accept_privacy'] = 'You must accept the privacy policy.';

    // B2B validation
    if ($values['is_business'] && $values['company_name'] === '') $errors['company_name'] = 'Company name required (B2B).';

    if (app_frontend_only()) {
        // Design mode: leave the validation UI, but don't create an order / payment.
        $errors['order'] = 'Design mode: order creation and payment disabled for now.';
    }

    if (!count($errors)) {
        $totals = checkout_compute_totals($values);

        $orderRef = 'TF-' . strtoupper(bin2hex(random_bytes(4))) . '-' . date('YmdHis');
        $status = 'processing';
        if ($pm === 'bank_transfer') $status = 'pending_payment';
        if ($pm === 'card' || $pm === 'wallet') $status = 'paid';

        $order = [
            'order_ref' => $orderRef,
            'created_at' => gmdate('c'),
            'status' => $status,

            'customer' => [
                'full_name' => $values['customer_full_name'],
                'email' => $values['customer_email'],
                'phone' => $values['customer_phone'],
                'company_name' => $values['company_name'],
                'is_business' => (bool)$values['is_business'],
                'vat_id' => $values['vat_id'],
            ],
            'shipping_address' => [
                'country' => $values['shipping_country'],
                'line1' => $values['shipping_line1'],
                'line2' => $values['shipping_line2'],
                'city' => $values['shipping_city'],
                'region' => $values['shipping_region'],
                'postal_code' => $values['shipping_postal_code'],
            ],
            'billing_address' => $billingSame ? 'same_as_shipping' : [
                'country' => $values['billing_country'],
                'line1' => $values['billing_line1'],
                'line2' => $values['billing_line2'],
                'city' => $values['billing_city'],
                'region' => $values['billing_region'],
                'postal_code' => $values['billing_postal_code'],
            ],
            'delivery' => [
                'method' => $values['shipping_method'],
                'estimate_days' => $shippingMethods[$values['shipping_method']]['days_min'] . '-' . $shippingMethods[$values['shipping_method']]['days_max'],
            ],
            'payment' => [
                'method' => $pm,
                'card_last4' => $pm === 'card' ? $values['card_last4'] : null,
            ],
            'legal' => [
                'accept_terms' => true,
                'accept_privacy' => true,
                'accepted_at' => gmdate('c'),
                'terms_version' => 'v1',
                'privacy_version' => 'v1',
                'want_invoice' => (bool)$values['want_invoice'],
            ],
            'items' => $totals['cart']['items'],
            'totals' => [
                'subtotal' => $totals['cart']['subtotal'],
                'shipping' => $totals['shipping'],
                'taxes' => $totals['taxes'],
                'tax_rate' => $totals['tax_rate'],
                'total' => $totals['total'],
                'currency' => $totals['currency'],
            ],
            'tracking' => [
                'carrier' => null,
                'tracking_number' => null,
                'events' => [
                    ['at' => gmdate('c'), 'code' => 'order_created', 'label' => 'Order created'],
                ],
            ],
        ];

        // Persistence DB: order linked to the account
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO orders(
                    order_ref, user_id, status, currency,
                    subtotal, shipping, taxes, tax_rate, total,
                    shipping_country, shipping_line1, shipping_line2, shipping_city, shipping_region, shipping_postal_code,
                    delivery_method, delivery_estimate_days,
                    payment_method, payment_card_last4,
                    created_at
                ) VALUES (
                    :order_ref, :user_id, :status, :currency,
                    :subtotal, :shipping, :taxes, :tax_rate, :total,
                    :sc, :sl1, :sl2, :city, :region, :pc,
                    :dm, :dd,
                    :pm, :last4,
                    :created_at
                )
            ");
            $stmt->execute([
                ':order_ref' => $orderRef,
                ':user_id' => (int)($u['id'] ?? 0),
                ':status' => $status,
                ':currency' => (string)$totals['currency'],
                ':subtotal' => (float)$totals['cart']['subtotal'],
                ':shipping' => (float)$totals['shipping'],
                ':taxes' => (float)$totals['taxes'],
                ':tax_rate' => (float)$totals['tax_rate'],
                ':total' => (float)$totals['total'],
                ':sc' => (string)$values['shipping_country'],
                ':sl1' => (string)$values['shipping_line1'],
                ':sl2' => $values['shipping_line2'] !== '' ? (string)$values['shipping_line2'] : null,
                ':city' => (string)$values['shipping_city'],
                ':region' => $values['shipping_region'] !== '' ? (string)$values['shipping_region'] : null,
                ':pc' => (string)$values['shipping_postal_code'],
                ':dm' => (string)$values['shipping_method'],
                ':dd' => (string)$order['delivery']['estimate_days'],
                ':pm' => (string)$pm,
                ':last4' => $pm === 'card' ? (string)$values['card_last4'] : null,
                ':created_at' => (string)$order['created_at'],
            ]);
            $orderId = (int)$pdo->lastInsertId();

            $stmtItem = $pdo->prepare("
                INSERT INTO order_items(order_id, sku, name, unit, unit_price, qty, line_total)
                VALUES(:oid, :sku, :name, :unit, :unit_price, :qty, :line_total)
            ");
            foreach ((array)$totals['cart']['items'] as $it) {
                $p = $it['product'] ?? null;
                if (!is_array($p)) continue;
                $stmtItem->execute([
                    ':oid' => $orderId,
                    ':sku' => (string)($p['sku'] ?? ''),
                    ':name' => (string)($p['name'] ?? ''),
                    ':unit' => (string)($p['unit'] ?? ''),
                    ':unit_price' => (float)($p['price'] ?? 0),
                    ':qty' => (int)($it['qty'] ?? 0),
                    ':line_total' => (float)($it['line_total'] ?? 0),
                ]);
            }

            // Save the default address (reuse)
            $pdo->prepare("
                UPDATE users SET
                    default_shipping_country = :sc,
                    default_shipping_line1 = :sl1,
                    default_shipping_line2 = :sl2,
                    default_shipping_city = :city,
                    default_shipping_region = :region,
                    default_shipping_postal_code = :pc,
                    updated_at = :now
                WHERE id = :uid
            ")->execute([
                ':sc' => (string)$values['shipping_country'],
                ':sl1' => (string)$values['shipping_line1'],
                ':sl2' => $values['shipping_line2'] !== '' ? (string)$values['shipping_line2'] : null,
                ':city' => (string)$values['shipping_city'],
                ':region' => $values['shipping_region'] !== '' ? (string)$values['shipping_region'] : null,
                ':pc' => (string)$values['shipping_postal_code'],
                ':now' => gmdate('c'),
                ':uid' => (int)($u['id'] ?? 0),
            ]);

            $pdo->commit();
        } catch (Throwable) {
            $pdo->rollBack();
            $errors['order'] = 'Server error when creating the order. Please try again.';
            // Re-display: return to the standard render at the bottom (with $errors).
            // NB: we don't empty the cart if the creation fails.
            // (We leave $values and $cart unchanged)
            $totalsPreview = $totals;
            // Continue to the standard render at the bottom (with $errors).
        }

        // Keep a "flash" copy for the confirmation page (without reading DB)
        if (!isset($errors['order'])) {
            // Confirmation email (demo: written in data/mail.log)
            $body = "Thank you for your order.\n\nReference: {$orderRef}\nTotal: " . money((float)$totals['total'], (string)$totals['currency']) . "\nStatus: {$status}\n";
            send_email((string)($u['email'] ?? ''), 'Order confirmation ' . $orderRef, $body);

            $_SESSION['_last_order_ref'] = $orderRef;
        }

        // In production: reserve stock / decrement according to strategy.
        if (!isset($errors['order'])) {
            cart_clear();
        }

        if (!isset($errors['order'])) {
            redirect(url_with_params('order-confirmation.php', ['order' => $orderRef]));
        }
    }
}

$totalsPreview = checkout_compute_totals($values);

$pageTitle = 'Paiement | Timbuktu Farming';
$pageDescription = 'Paiement securise: client, adresses, livraison, paiement et conformite.';
require __DIR__ . '/layout_top.php';
?>

<section class="services" aria-labelledby="checkout-title">
    <header class="services__header" data-reveal style="--reveal-delay: 0ms;">
        <p class="services__eyebrow">Paiement</p>
        <h1 class="services__title" id="checkout-title">Finaliser la commande</h1>
        <p class="services__lead">Saisissez les donnees essentielles, puis confirmez le paiement.</p>
    </header>

    <div class="services__grid services__grid--single">
        <article class="service-card">
            <div class="service-card__body">
                <h2 class="service-card__title">Resume</h2>
                <p class="service-card__text">
                    <strong>Articles:</strong> <?= h((string)$totalsPreview['cart']['qty_total']) ?><br>
                    <strong>Sous-total:</strong> <?= h(money((float)$totalsPreview['cart']['subtotal'])) ?><br>
                    <strong>Livraison (estimee):</strong> <?= h(money((float)$totalsPreview['shipping'])) ?><br>
                    <strong>Taxes (estimees):</strong> <?= h(money((float)$totalsPreview['taxes'])) ?><br>
                    <strong>Total (estime):</strong> <?= h(money((float)$totalsPreview['total'])) ?>
                </p>
            </div>
        </article>

        <article class="service-card">
            <div class="service-card__body">
                <h2 class="service-card__title">Informations et paiement</h2>

                <?php if (count($errors)): ?>
                    <div class="alert alert--error" role="alert" aria-label="Validation errors">
                        <div class="alert__title">Merci de corriger les champs en erreur</div>
                        <ul class="alert__list">
                            <?php foreach ($errors as $msg): ?>
                                <li><?= h((string)$msg) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="checkout.php" class="form">
                    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">

                    <fieldset class="form-section">
                        <legend><strong>1. Informations client</strong></legend>
                        <div class="grid grid--2">
                            <div class="field">
                                <label for="customer_full_name">Nom complet</label>
                                <input class="form-control" id="customer_full_name" name="customer_full_name" type="text" value="<?= h($values['customer_full_name']) ?>" required>
                            </div>
                            <div class="field">
                                <label for="customer_email">Email</label>
                                <input class="form-control" id="customer_email" name="customer_email" type="email" value="<?= h($values['customer_email']) ?>" required>
                            </div>
                            <div class="field">
                                <label for="customer_phone">Telephone</label>
                                <input class="form-control" id="customer_phone" name="customer_phone" type="tel" value="<?= h($values['customer_phone']) ?>" required>
                            </div>
                            <div class="field field--checkbox">
                                <label><input type="checkbox" name="is_business" value="1" <?= $values['is_business'] ? 'checked' : '' ?>> Commande entreprise (B2B)</label>
                                <label><input type="checkbox" name="want_invoice" value="1" <?= $values['want_invoice'] ? 'checked' : '' ?>> Facture</label>
                            </div>
                            <div class="field">
                                <label for="company_name">Societe (optionnel / requis si B2B)</label>
                                <input class="form-control" id="company_name" name="company_name" type="text" value="<?= h($values['company_name']) ?>">
                            </div>
                            <div class="field">
                                <label for="vat_id">Numero TVA (optionnel)</label>
                                <input class="form-control" id="vat_id" name="vat_id" type="text" value="<?= h($values['vat_id']) ?>">
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="form-section">
                        <legend><strong>2. Adresse de livraison</strong></legend>
                        <div class="grid grid--2">
                            <div class="field">
                                <label for="shipping_country">Pays</label>
                                <select class="form-control" id="shipping_country" name="shipping_country" required>
                                    <?php foreach ($countries as $code => $label): ?>
                                        <option value="<?= h($code) ?>" <?= $values['shipping_country'] === $code ? 'selected' : '' ?>><?= h($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field">
                                <label for="shipping_line1">Adresse</label>
                                <input class="form-control" id="shipping_line1" name="shipping_line1" type="text" value="<?= h($values['shipping_line1']) ?>" required>
                            </div>
                            <div class="field">
                                <label for="shipping_line2">Complement (optionnel)</label>
                                <input class="form-control" id="shipping_line2" name="shipping_line2" type="text" value="<?= h($values['shipping_line2']) ?>">
                            </div>
                            <div class="field">
                                <label for="shipping_city">Ville</label>
                                <input class="form-control" id="shipping_city" name="shipping_city" type="text" value="<?= h($values['shipping_city']) ?>" required>
                            </div>
                            <div class="field">
                                <label for="shipping_region">Region/Etat (optionnel)</label>
                                <input class="form-control" id="shipping_region" name="shipping_region" type="text" value="<?= h($values['shipping_region']) ?>">
                            </div>
                            <div class="field">
                                <label for="shipping_postal_code">Code postal</label>
                                <input class="form-control" id="shipping_postal_code" name="shipping_postal_code" type="text" value="<?= h($values['shipping_postal_code']) ?>" required>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="form-section">
                        <legend><strong>2bis. Adresse de facturation</strong></legend>
                        <div class="field field--checkbox">
                            <label><input type="checkbox" name="billing_same_as_shipping" value="1" <?= $values['billing_same_as_shipping'] ? 'checked' : '' ?>> Identique a l'adresse de livraison</label>
                        </div>
                        <div class="grid grid--2">
                            <div class="field">
                                <label for="billing_country">Pays (si different)</label>
                                <select class="form-control" id="billing_country" name="billing_country">
                                    <?php foreach ($countries as $code => $label): ?>
                                        <option value="<?= h($code) ?>" <?= $values['billing_country'] === $code ? 'selected' : '' ?>><?= h($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field">
                                <label for="billing_line1">Adresse</label>
                                <input class="form-control" id="billing_line1" name="billing_line1" type="text" value="<?= h($values['billing_line1']) ?>">
                            </div>
                            <div class="field">
                                <label for="billing_line2">Complement</label>
                                <input class="form-control" id="billing_line2" name="billing_line2" type="text" value="<?= h($values['billing_line2']) ?>">
                            </div>
                            <div class="field">
                                <label for="billing_city">Ville</label>
                                <input class="form-control" id="billing_city" name="billing_city" type="text" value="<?= h($values['billing_city']) ?>">
                            </div>
                            <div class="field">
                                <label for="billing_region">Region/Etat</label>
                                <input class="form-control" id="billing_region" name="billing_region" type="text" value="<?= h($values['billing_region']) ?>">
                            </div>
                            <div class="field">
                                <label for="billing_postal_code">Code postal</label>
                                <input class="form-control" id="billing_postal_code" name="billing_postal_code" type="text" value="<?= h($values['billing_postal_code']) ?>">
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="form-section">
                        <legend><strong>3. Options de livraison</strong></legend>
                        <div class="grid grid--2">
                            <div class="field">
                                <label for="shipping_method">Methode</label>
                                <select class="form-control" id="shipping_method" name="shipping_method" required>
                                    <?php foreach ($shippingMethods as $code => $m): ?>
                                        <option value="<?= h($code) ?>" <?= $values['shipping_method'] === $code ? 'selected' : '' ?>>
                                            <?= h($m['label']) ?> (<?= h((string)$m['days_min']) ?>–<?= h((string)$m['days_max']) ?> days)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field">
                                <label for="payment_method">Paiement</label>
                                <select class="form-control" id="payment_method" name="payment_method" required>
                                    <option value="card" <?= $values['payment_method'] === 'card' ? 'selected' : '' ?>>Carte</option>
                                    <option value="wallet" <?= $values['payment_method'] === 'wallet' ? 'selected' : '' ?>>Wallet</option>
                                    <option value="bank_transfer" <?= $values['payment_method'] === 'bank_transfer' ? 'selected' : '' ?>>Virement bancaire</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="card_last4">Carte: 4 derniers chiffres (si carte)</label>
                                <input class="form-control form-control--sm" id="card_last4" name="card_last4" type="text" inputmode="numeric" maxlength="4" value="<?= h($values['card_last4']) ?>" placeholder="1234">
                                <div class="form-help">Astuce: entrez "0000" pour simuler un refus.</div>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="form-section">
                        <legend><strong>4. Legal et conformite</strong></legend>
                        <div class="field field--checkbox">
                            <label><input type="checkbox" name="accept_terms" value="1" <?= $values['accept_terms'] ? 'checked' : '' ?>> J'accepte les conditions generales de vente</label>
                            <label><input type="checkbox" name="accept_privacy" value="1" <?= $values['accept_privacy'] ? 'checked' : '' ?>> J'ai pris connaissance de la politique de confidentialite</label>
                        </div>
                    </fieldset>

                    <div class="actions">
                        <button type="submit" class="btn btn--primary">Confirmer et payer</button>
                        <a class="btn btn--secondary" href="cart.php">Retour au panier</a>
                    </div>
                </form>
            </div>
        </article>
    </div>
</section>

<?php require __DIR__ . '/layout_bottom.php'; ?>


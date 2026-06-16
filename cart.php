<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_cart.php';

// Actions panier (server-side)
if (!app_frontend_only() && is_post()) {
    require_csrf();
    $action = (string)($_POST['action'] ?? '');
    $sku = (string)($_POST['sku'] ?? '');
    $qty = (int)($_POST['qty'] ?? 0);

    if ($action === 'add') {
        cart_add($sku, max(1, $qty));
        redirect('cart.php');
    }

    if ($action === 'update') {
        cart_update($sku, $qty);
        redirect('cart.php');
    }

    if ($action === 'remove') {
        cart_update($sku, 0);
        redirect('cart.php');
    }

    if ($action === 'clear') {
        cart_clear();
        redirect('cart.php');
    }
}

$pageTitle = 'Panier | Timbuktu Farming';
$pageDescription = 'Contenu du panier, quantites et total avant paiement.';
$totals = cart_totals();

require __DIR__ . '/layout_top.php';
?>

<section class="services" aria-labelledby="cart-title">
    <header class="services__header" data-reveal style="--reveal-delay: 0ms;">
        <p class="services__eyebrow">Panier</p>
        <h1 class="services__title" id="cart-title">Panier</h1>
        <p class="services__lead">Verifiez les quantites, la disponibilite et le total avant paiement.</p>
    </header>

    <div class="services__grid services__grid--single">
        <article class="service-card">
            <div class="service-card__body">
                <h2 class="service-card__title">Articles</h2>

                <?php if (!count($totals['items'])): ?>
                    <p class="service-card__text">Votre panier est vide.</p>
                    <a class="btn btn--primary" href="products.php">Voir les produits</a>
                <?php else: ?>
                    <div class="stack stack--sm">
                        <?php foreach ($totals['items'] as $it): ?>
                            <?php $p = $it['product']; ?>
                            <article class="service-card">
                                <div class="service-card__body">
                                    <h3 class="service-card__title"><?= h($p['name']) ?></h3>
                                    <p class="service-card__text">
                                        <strong>SKU:</strong> <?= h($p['sku']) ?><br>
                                        <strong>Prix:</strong> <?= h(money((float)$p['price'], (string)$p['currency'])) ?><?= $p['unit'] ? ' / ' . h((string)$p['unit']) : '' ?><br>
                                        <strong>Disponibilite:</strong> <?= h($p['availability']) ?>
                                    </p>
                                    <div class="actions actions--end">
                                        <form method="post" action="cart.php" class="actions actions--end form-inline">
                                            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="sku" value="<?= h($p['sku']) ?>">
                                            <div class="field">
                                                <label for="qty-<?= h($p['sku']) ?>">Quantite</label>
                                                <input class="form-control form-control--sm" id="qty-<?= h($p['sku']) ?>" name="qty" type="number" min="0" value="<?= h((string)$it['qty']) ?>" inputmode="numeric">
                                            </div>
                                            <button class="btn btn--primary" type="submit">Mettre a jour</button>
                                        </form>

                                        <form method="post" action="cart.php" class="form-inline">
                                            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="sku" value="<?= h($p['sku']) ?>">
                                            <button class="btn btn--secondary" type="submit">Retirer</button>
                                        </form>

                                        <a class="btn btn--secondary" href="<?= h(url_with_params('product.php', ['sku' => $p['sku']])) ?>">Details</a>
                                    </div>

                                    <p class="service-card__text mt-2">
                                        <strong>Total ligne:</strong> <?= h(money((float)$it['line_total'], (string)$p['currency'])) ?>
                                    </p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <div class="actions mt-3">
                        <div class="cart-total">
                            <span><strong>Sous-total</strong></span>
                            <strong><?= h(money((float)$totals['subtotal'])) ?></strong>
                        </div>

                        <form method="post" action="cart.php" class="form-inline">
                            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="action" value="clear">
                            <button class="btn btn--danger" type="submit">Vider le panier</button>
                        </form>

                        <a class="btn btn--primary" href="checkout.php">Continuer vers le paiement</a>
                    </div>
                <?php endif; ?>
            </div>
        </article>
    </div>
</section>

<?php require __DIR__ . '/layout_bottom.php'; ?>


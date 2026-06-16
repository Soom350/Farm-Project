    </div>
</main>

<!-- Product quick view modal -->
<div class="modal" id="product-modal" role="dialog" aria-modal="true" aria-labelledby="product-modal-title" hidden>
    <div class="modal__panel" id="product-modal-panel" role="document">
        <button class="icon-btn modal__close" id="product-modal-close" type="button" data-modal-close aria-label="Fermer le detail produit">
            <span aria-hidden="true">×</span>
        </button>
        <img class="dialog-brand" id="product-modal-brand" src="<?= h(app_url('assets/logo/logo-icon.svg')) ?>" alt="" aria-hidden="true" width="40" height="40" loading="lazy">

        <div class="modal__content">
            <img class="modal__image" id="product-modal-image" src="" alt="" width="1200" height="800">
            <div class="modal__info">
                <h2 class="modal__title" id="product-modal-title"></h2>
                <p class="modal__sku" id="product-modal-sku"></p>
                <p class="modal__desc" id="product-modal-desc"></p>
                <div class="modal__meta">
                    <span class="modal__price" id="product-modal-price"></span>
                    <span class="modal__stock" id="product-modal-stock"></span>
                </div>

                <div class="modal__actions">
                    <div class="qty qty--modal" role="group" aria-label="Quantite">
                        <button type="button" class="qty-btn" data-modal-qty-minus aria-label="Diminuer la quantite">-</button>
                        <input class="qty-input" id="product-modal-qty" type="number" min="1" value="1" inputmode="numeric" aria-label="Quantite">
                        <button type="button" class="qty-btn" data-modal-qty-plus aria-label="Augmenter la quantite">+</button>
                    </div>
                    <div class="modal__total">Total: <strong id="product-modal-total"></strong></div>
                    <button class="btn btn--primary" type="button" data-modal-add>Ajouter au panier</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="ui-backdrop" data-backdrop hidden></div>

<aside class="cart-drawer" id="cart" role="dialog" aria-modal="true" aria-labelledby="cart-title" hidden>
    <div class="cart-drawer__header">
        <img src="<?= h(app_url('assets/logo/logo-icon.svg')) ?>" alt="" aria-hidden="true" width="32" height="32" loading="lazy">
        <h2 class="cart-drawer__title" id="cart-title">Votre panier</h2>
        <button class="icon-btn" type="button" data-cart-close aria-label="Fermer le panier"><span aria-hidden="true">×</span></button>
    </div>
    <div class="cart-drawer__body">
        <p class="cart-empty" data-cart-empty>Votre panier est vide.</p>
        <ul class="cart-items" data-cart-items aria-label="Articles du panier"></ul>
    </div>
    <div class="cart-drawer__footer">
        <div class="cart-total"><span>Total</span><strong data-cart-total>$0.00</strong></div>
        <a class="btn btn--primary" href="<?= h(app_url('cart.php')) ?>">Voir le panier</a>
        <button class="btn btn--secondary" type="button" data-checkout>Paiement</button>
    </div>
</aside>

<div class="toast" id="toast" role="status" aria-live="polite" aria-atomic="true" hidden></div>

<?php require __DIR__ . '/footer.php'; ?>
<script src="<?= h(app_url('script.js')) ?>" defer></script>
</body>
</html>


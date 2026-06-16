<?php
declare(strict_types=1);

/** @var string|null $footerHomeHref */
$footerHomeHref = isset($footerHomeHref) && is_string($footerHomeHref) && $footerHomeHref !== ''
    ? $footerHomeHref
    : app_url('index.php');
?>
<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-top">
            <div class="footer-col footer-col--brand">
                <a class="footer-brand" href="<?= h($footerHomeHref) ?>" aria-label="Retour a l'accueil">
                    <img src="<?= h(app_url('assets/logo/footer-emblem.svg')) ?>" alt="" width="120" height="120" loading="lazy">
                </a>
            </div>

            <nav class="footer-col footer-col--links" aria-label="Informations legales">
                <a href="#" class="footer-link">Store Policy</a>
                <a href="#" class="footer-link">Shipping &amp; Returns</a>
                <a href="#" class="footer-link">FAQ</a>
            </nav>

            <div class="footer-col footer-col--newsletter" id="newsletter">
                <h3 class="footer-title">Get the Latest News &amp; Updates from Our Farm</h3>
                <form class="footer-form" action="#" method="post">
                    <div class="footer-form__field">
                        <label class="footer-form__label" for="footer-email">Email <span aria-hidden="true">*</span></label>
                        <input type="email" id="footer-email" name="email" required autocomplete="email">
                    </div>
                    <label class="footer-checkbox">
                        <input type="checkbox" name="newsletter" required>
                        <span>Yes, subscribe me to your newsletter <span aria-hidden="true">*</span></span>
                    </label>
                    <button type="submit">Join</button>
                </form>
            </div>
        </div>

        <div class="footer-social" id="follow-us" aria-label="Reseaux sociaux">
            <a href="#" class="footer-social__link">Facebook</a>
            <a href="#" class="footer-social__link">Twitter</a>
            <a href="#" class="footer-social__link">Instagram</a>
        </div>

        <div class="footer-bottom">
            <p class="footer-copy">&copy; <?= date('Y') ?> by Timbuktu Farming. Powered and secured by Wix</p>
        </div>
    </div>
</footer>

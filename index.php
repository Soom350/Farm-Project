<?php
declare(strict_types=1);
require_once __DIR__ . '/lib_bootstrap.php';
require_once __DIR__ . '/lib_auth.php';
require_once __DIR__ . '/lib_blog.php';

$homeUser = auth_user();
$homeNext = safe_next_url(current_url_path());
?>
<!DOCTYPE html>
<html lang="fr" data-page="home">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="Timbuktu Farming propose des produits agricoles frais et de saison, avec qualite controlee et livraison fiable.">
    <?= csrf_meta_tag() ?>

    <!-- Social sharing -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Timbuktu Farming — Produits frais et de saison">
    <meta property="og:description" content="Produits controles, livraison fiable et accompagnement client.">
    <meta property="og:image" content="image/divaris-shirichena-xZqfw-VXnYE-unsplash.jpg">
    <meta name="twitter:card" content="summary_large_image">

    <!-- Favicon -->
    <link rel="icon" href="assets/logo/favicon.svg" type="image/svg+xml">

    <link rel="stylesheet" href="<?= h(app_url('styles/globals.css')) ?>">
    <link rel="stylesheet" href="<?= h(app_url('style/index.css')) ?>">
    <link rel="stylesheet" href="<?= h(app_url('style/pages.css')) ?>">
    <link rel="stylesheet" href="<?= h(app_url('styles/components.css')) ?>">
    <link rel="stylesheet" href="<?= h(app_url('styles/responsive.css')) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"></noscript>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <title>Timbuktu Farming | Produits frais et de saison</title>
</head>
<body id="top" class="page page--home">
    <a class="skip-link" href="#main">Aller au contenu</a>
    <header class="site-header" id="site-header" data-page="home">
        <div class="container site-header__inner">
            <div class="header-left-content header-branding">
                <a class="brand brand--lockup" href="#top" aria-label="Timbuktu Farming home">
                    <span class="brand__title">Timbuktu Farming</span>
                    <span class="brand__subtitle">Deliver Season's Best</span>
                </a>
            </div>
            <nav class="header-right-content site-header__nav" aria-label="Main navigation">
                <button class="nav-cart" type="button" data-cart-open aria-label="Ouvrir le panier">
                    <i class="fas fa-shopping-cart" aria-hidden="true"></i>
                    <span class="nav-cart__label">Panier</span>
                    <span class="cart-count" data-cart-count aria-hidden="true">0</span>
                </button>
                <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" aria-label="Afficher/masquer la navigation">
                    <i class="fas fa-bars" aria-hidden="true"></i>
                </button>
                <ul id="site-nav" class="site-nav">
                    <li class="nav-item nav-item--home"><a class="nav-link is-active" href="#top" aria-current="page">Home</a></li>
                    <li class="nav-item nav-item--about"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item nav-item--catalog"><a class="nav-link" href="#products">Order Online</a></li>
                    <li class="nav-item nav-item--join"><a class="nav-link" href="#newsletter">Join CSA</a></li>
                    <li class="nav-item nav-item--blogs"><a class="nav-link" href="blog.php">Blog</a></li>
                    <li class="nav-item nav-item--contact"><a class="nav-link" href="#contact-us">Contact Us</a></li>
                    <li class="nav-item nav-item--account"><a class="nav-link" href="<?= h(app_url('account.php')) ?>"><i class="fas fa-user" aria-hidden="true"></i> My Account</a></li>
                    <?php if ($homeUser): ?>
                        <li class="nav-item nav-item--logout">
                            <form method="post" action="<?= h(app_url('auth/logout.php')) ?>" class="form-inline">
                                <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                                <button class="nav-link nav-link--button" type="submit">Log Out</button>
                            </form>
                        </li>
                    <?php else: ?>
                        <li class="nav-item nav-item--login"><a class="nav-link" href="<?= h(url_with_params(app_url('auth/login.php'), ['next' => $homeNext])) ?>">Log In</a></li>
                        <li class="nav-item nav-item--signup"><a class="nav-link" href="<?= h(url_with_params(app_url('auth/signup.php'), ['next' => $homeNext])) ?>">Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!--Sign up Dialog Box--->
    <div class="signup-dialog" id="signup-dialog" role="dialog" aria-modal="true" aria-labelledby="signup-title" hidden>
                    <div class="signup-dialog-content">
                        <img class="dialog-brand" src="assets/logo/logo-icon.svg" alt="" aria-hidden="true" width="44" height="44" loading="lazy">
                        <h2 id="signup-title">Creer un compte</h2>
                        <?php $flashInfo = flash_get('info'); ?>
                        <?php if ($flashInfo): ?>
                            <p class="footer-muted"><?= h($flashInfo) ?></p>
                        <?php endif; ?>
                        <form class="signup-form" action="auth/signup.php" method="post">
                            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="next" value="<?= h($homeNext) ?>">
                            <label class="sr-only" for="signup-firstName">Prenom</label>
                            <input class="form-control" id="signup-firstName" name="firstName" type="text" placeholder="Prenom" autocomplete="given-name" required>

                            <label class="sr-only" for="signup-lastName">Nom</label>
                            <input class="form-control" id="signup-lastName" name="lastName" type="text" placeholder="Nom" autocomplete="family-name" required>

                            <label class="sr-only" for="signup-email">Email</label>
                            <input class="form-control" id="signup-email" name="email" type="email" placeholder="Email" autocomplete="email" required>

                            <label class="sr-only" for="signup-phone">Telephone</label>
                            <input class="form-control" id="signup-phone" name="phone" type="tel" placeholder="Telephone" autocomplete="tel" required>

                            <label class="sr-only" for="signup-address">Adresse</label>
                            <input class="form-control" id="signup-address" name="address" type="text" placeholder="Adresse" autocomplete="street-address" required>

                            <label class="sr-only" for="signup-city">Ville</label>
                            <input class="form-control" id="signup-city" name="city" type="text" placeholder="Ville" autocomplete="address-level2" required>

                            <label class="sr-only" for="signup-state">Region</label>
                            <input class="form-control" id="signup-state" name="state" type="text" placeholder="Region" autocomplete="address-level1" required>

                            <label class="sr-only" for="signup-zip">Code postal</label>
                            <input class="form-control" id="signup-zip" name="zip" type="text" placeholder="Code postal" autocomplete="postal-code" inputmode="numeric">

                            <label class="sr-only" for="signup-password">Mot de passe</label>
                            <input class="form-control" id="signup-password" name="password" type="password" placeholder="Mot de passe" autocomplete="new-password" required>

                            <label class="sr-only" for="signup-password2">Confirmer le mot de passe</label>
                            <input class="form-control" id="signup-password2" name="password_confirm" type="password" placeholder="Confirmer le mot de passe" autocomplete="new-password" required>

                            <button type="submit" class="btn btn--primary">Valider</button>
                        </form>
                        <button type="button" class="close" data-signup-close aria-label="Fermer la fenetre"><i class="fas fa-times" aria-hidden="true"></i></button>
                    </div>
                </div>
            <!-------login Dialog Box------------>
            <div class="login-dialog" id="login-dialog" role="dialog" aria-modal="true" aria-labelledby="login-title" hidden>
                <div class="login-dialog-content">
                    <img class="dialog-brand" src="assets/logo/logo-icon.svg" alt="" aria-hidden="true" width="44" height="44" loading="lazy">
                    <h2 id="login-title">Connexion</h2>
                    <form class="login-form" action="auth/login.php" method="post">
                        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="next" value="<?= h($homeNext) ?>">
                        <label class="sr-only" for="login-email">Email</label>
                        <input class="form-control" id="login-email" name="email" type="email" placeholder="Email" autocomplete="email" required>
                        <label class="sr-only" for="login-password">Mot de passe</label>
                        <input class="form-control" id="login-password" name="password" type="password" placeholder="Mot de passe" autocomplete="current-password" required>
                        <button type="submit" class="btn btn--primary">Se connecter</button>
                        <button type="button" class="close" data-login-close aria-label="Fermer la fenetre"><i class="fas fa-times" aria-hidden="true"></i></button>
                    </form>
                </div>
            </div>
    <main id="main" class="page-main" tabindex="-1" data-page="home">
        <div class="container">
            <div class="hero-media" aria-hidden="true">
                <img src="logo_slide_img/kelly-neil-pdlC9_bgN9o-unsplash.jpg" alt="" loading="eager" decoding="async" width="1920" height="1280">
            </div>
            <div class="content">
                <div class="content-text">
                    <h1>Fresh Produce Delivery</h1>
                    <p>From Our Farm to Your Doorstep</p>
                    <a href="#products" class="btn btn--primary">Order Online</a>
                </div>
            </div>
        </div>
        <section class="farm-gallery" aria-label="Farm gallery">
            <div class="farm-gallery__inner">
                <img src="logo_slide_img/adrian-infernus-BN6iQEVN0ZQ-unsplash.jpg" alt="Farm produce table" loading="lazy" width="1200" height="800">
                <img src="logo_slide_img/josephine-baran-g4wzhY8qiMw-unsplash.jpg" alt="Farm team and produce" loading="lazy" width="1200" height="800">
            </div>
        </section>

        <div class="home-parallax">
            <div class="home-parallax__bg" aria-hidden="true">
                <img src="image/parallax-farming.jpg" alt="" loading="lazy" decoding="async" width="1920" height="1280">
            </div>
            <div class="home-parallax__content">
        <section class="content-subtext" id="about" aria-label="About Timbuktu Farming">
            <div class="content-subtext-content">
                <div class="content-subtext-text">
                    <p class="content-subtext-eyebrow">Notre engagement</p>
                    <h3>Support Sustainable Farming</h3>
                    <p>
                        Nous travaillons avec des producteurs locaux pour une agriculture durable, des recoltes
                        tracees et une chaine courte entre la ferme et votre table.
                    </p>
                    <a href="#products" class="btn btn--primary">Join Our CSA</a>
                </div>
            </div>
        </section>

        <!-- Services -->
        <section class="services services--hidden" id="services" aria-labelledby="services-title">
            <div class="services__container">
                <header class="services__header" data-reveal style="--reveal-delay: 0ms;">
                    <p class="services__eyebrow">Nos services</p>
                    <h2 class="services__title" id="services-title">Qualite, controle et livraison de bout en bout.</h2>
                    <p class="services__lead">
                        De la selection des produits a la logistique, nous assurons un suivi fiable a chaque etape.
                    </p>
                    <div class="services__actions">
                        <a class="services__cta btn" href="#contact-us">Parler a notre equipe</a>
                    </div>
                </header>

                <div class="services__grid" aria-label="Services">
                    <article class="service-card" data-reveal style="--reveal-delay: 60ms;">
                        <div class="service-card__media" aria-hidden="true">
                            <!-- Optionnel: remplacez ce bloc par une image HD (ex: <img src="image/services/product-quality.jpg" alt="" loading="lazy">) -->
                            <svg class="service-card__icon" viewBox="0 0 24 24" fill="none">
                                <path d="M12 3l7 4v6c0 4.5-3 8-7 9-4-1-7-4.5-7-9V7l7-4z" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M8.2 12.2l2.2 2.2 5.4-5.4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="service-card__body">
                            <h3 class="service-card__title">Qualite produit</h3>
                            <p class="service-card__subtitle">Excellence, fiabilite et constance.</p>
                            <p class="service-card__text" data-clamp>
                                La qualite est au coeur de notre activite. Nous selectionnons des produits fiables et travaillons avec des partenaires de confiance.
                            </p>

                            <div class="service-card__details" id="svc-details-quality" data-collapsible>
                                <ul class="service-card__bullets" aria-label="Avantages qualite">
                                    <li class="service-pill">
                                        <span class="service-pill__icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none"><path d="M7 12h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M12 7v10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                        </span>
                                        <span class="service-pill__label">Constance</span>
                                    </li>
                                    <li class="service-pill">
                                        <span class="service-pill__icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none"><path d="M4 14c2.5-5.5 6.5-8.5 16-9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M5 19c3.5-1 6.5-3.5 8-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                        </span>
                                        <span class="service-pill__label">Performance</span>
                                    </li>
                                    <li class="service-pill">
                                        <span class="service-pill__icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none"><path d="M12 2l7 4v6c0 5-3 9-7 10-4-1-7-5-7-10V6l7-4z" stroke="currentColor" stroke-width="1.6"/><path d="M12 9v5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M12 16.5h.01" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
                                        </span>
                                        <span class="service-pill__label">Securite</span>
                                    </li>
                                    <li class="service-pill">
                                        <span class="service-pill__icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none"><path d="M7 4h10v4H7V4z" stroke="currentColor" stroke-width="1.6"/><path d="M6 8h12v12H6V8z" stroke="currentColor" stroke-width="1.6"/><path d="M9 12h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M9 15.5h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                                        </span>
                                        <span class="service-pill__label">Normes</span>
                                    </li>
                                </ul>
                            </div>

                            <button class="service-card__toggle" type="button" aria-expanded="false" aria-controls="svc-details-quality">
                                Voir le detail
                            </button>
                        </div>
                    </article>

                    <article class="service-card" data-reveal style="--reveal-delay: 120ms;">
                        <div class="service-card__media" aria-hidden="true">
                            <!-- Optionnel: remplacez ce bloc par une image HD (ex: <img src="image/services/qa-process.jpg" alt="" loading="lazy">) -->
                            <svg class="service-card__icon" viewBox="0 0 24 24" fill="none">
                                <path d="M6 4h12v16H6V4z" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M8.5 8h7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                <path d="M8.5 12h7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                <path d="M8.5 16h4.2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <div class="service-card__body">
                            <h3 class="service-card__title">Processus assurance qualite</h3>
                            <p class="service-card__subtitle">Des standards eleves pour une confiance totale.</p>
                            <p class="service-card__text" data-clamp>
                                Notre processus de controle utilise plusieurs points de verification avant expedition.
                            </p>

                            <div class="service-card__details" id="svc-details-qa" data-collapsible>
                                <ol class="qa-steps" aria-label="Etapes qualite">
                                    <li class="qa-step">
                                        <span class="qa-step__badge">1</span>
                                        <div class="qa-step__content">
                                            <strong class="qa-step__title">Pre-production</strong>
                                            <span class="qa-step__text">Validation des matieres et specifications avant lancement.</span>
                                        </div>
                                    </li>
                                    <li class="qa-step">
                                        <span class="qa-step__badge">2</span>
                                        <div class="qa-step__content">
                                            <strong class="qa-step__title">En cours de production</strong>
                                            <span class="qa-step__text">Controles intermediaires pour detecter tot les ecarts.</span>
                                        </div>
                                    </li>
                                    <li class="qa-step">
                                        <span class="qa-step__badge">3</span>
                                        <div class="qa-step__content">
                                            <strong class="qa-step__title">Inspection finale</strong>
                                            <span class="qa-step__text">Verification complete avant emballage et expedition.</span>
                                        </div>
                                    </li>
                                </ol>
                            </div>

                            <button class="service-card__toggle" type="button" aria-expanded="false" aria-controls="svc-details-qa">
                                Voir le detail
                            </button>
                        </div>
                    </article>

                    <article class="service-card" data-reveal style="--reveal-delay: 180ms;">
                        <div class="service-card__media" aria-hidden="true">
                            <!-- Optionnel: remplacez ce bloc par une image HD (ex: <img src="image/services/international-delivery.jpg" alt="" loading="lazy">) -->
                            <svg class="service-card__icon" viewBox="0 0 24 24" fill="none">
                                <path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M3.5 12h17" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                <path d="M12 3c2.5 2.6 4 5.8 4 9s-1.5 6.4-4 9c-2.5-2.6-4-5.8-4-9s1.5-6.4 4-9z" stroke="currentColor" stroke-width="1.6"/>
                            </svg>
                        </div>
                        <div class="service-card__body">
                            <h3 class="service-card__title">Livraison internationale</h3>
                            <p class="service-card__subtitle">Livraison sure et efficace a l'international.</p>
                            <p class="service-card__text" data-clamp>
                                Nous proposons des solutions d'expedition globales avec emballage securise et suivi en temps reel.
                            </p>
                            <div class="service-card__details" id="svc-details-delivery" data-collapsible>
                                <ul class="service-card__checklist" aria-label="Conditions de livraison">
                                    <li>Expedition vers de nombreuses destinations</li>
                                    <li>Emballage securise pour proteger les produits</li>
                                    <li>Suivi de commande avec mises a jour en temps reel</li>
                                    <li class="muted">Des frais de douane et taxes peuvent s'appliquer</li>
                                </ul>
                            </div>

                            <button class="service-card__toggle" type="button" aria-expanded="false" aria-controls="svc-details-delivery">
                                Voir le detail
                            </button>
                        </div>
                    </article>

                    <article class="service-card" data-reveal style="--reveal-delay: 240ms;">
                        <div class="service-card__media" aria-hidden="true">
                            <!-- Optionnel: remplacez ce bloc par une image HD (ex: <img src="image/services/customer-support.jpg" alt="" loading="lazy">) -->
                            <svg class="service-card__icon" viewBox="0 0 24 24" fill="none">
                                <path d="M6.5 18.5h-.5a3 3 0 01-3-3v-2a8 8 0 0116 0v2a3 3 0 01-3 3h-.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                <path d="M9 20h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                <path d="M7 14v4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                <path d="M17 14v4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <div class="service-card__body">
                            <h3 class="service-card__title">Satisfaction client</h3>
                            <p class="service-card__subtitle">Votre satisfaction est notre priorite.</p>
                            <p class="service-card__text" data-clamp>
                                Notre equipe vous accompagne avant, pendant et apres l'achat avec des reponses rapides et claires.
                            </p>
                            <div class="service-card__details" id="svc-details-support" data-collapsible>
                                <div class="service-card__footer">
                                    <a class="service-card__link" href="#contact-us">Contacter le support <span aria-hidden="true">→</span></a>
                                </div>
                            </div>

                            <button class="service-card__toggle" type="button" aria-expanded="false" aria-controls="svc-details-support">
                                Voir le detail
                            </button>
                        </div>
                    </article>
                </div>
            </div>
        </section>
        <!-- /Services -->

        <section class="products" id="products" aria-label="Shop produce">
            <div class="products-header">
                <p class="products-eyebrow">Du champ au panier</p>
                <h2 class="products-title">Shop Season's Produce</h2>
                <p class="products-lead">Produits de saison selectionnes, disponibles en ligne chaque semaine.</p>
            </div>

            <div class="products-carousel products-carousel--grid" aria-label="Products">
                <div class="products-viewport">
                    <div class="products-track products-track--grid">
                        <div class="product" data-product-id="p1" data-name="Okra (gombo) frais" data-price="6.5" data-unit="kg" data-stock="100" data-availability="in_stock" data-image="logo_slide_img/okra-raw.jpg">
                            <button class="quick-view" type="button" data-quick-view aria-label="Apercu rapide Okra (gombo) frais">
                                <i class="fas fa-eye" aria-hidden="true"></i><span>Apercu rapide</span>
                            </button>
                            <img src="logo_slide_img/okra-raw.jpg" alt="Okra (gombo) frais" loading="lazy" width="1200" height="800">
                            <h3 class="product-name">Okra (gombo) frais</h3>
                            <p class="product-meta"><span class="product-price">$6.50</span><span class="product-unit">/kg</span></p>
                            <p class="product-stock"><span class="available-quantity">100</span>kg <span class="available-status">en stock</span></p>
                            <div class="product-actions">
                                <div class="qty" role="group" aria-label="Quantite">
                                    <button type="button" class="qty-btn" data-qty-minus aria-label="Diminuer la quantite">-</button>
                                    <input class="qty-input" type="number" min="1" value="1" inputmode="numeric" aria-label="Quantite">
                                    <button type="button" class="qty-btn" data-qty-plus aria-label="Augmenter la quantite">+</button>
                                </div>
                                <button type="button" class="btn btn--primary product-add" data-add-to-cart>Ajouter au panier</button>
                            </div>
                        </div>
                        <div class="product" data-product-id="p2" data-name="Légumes de saison (assortiment)" data-price="24" data-unit="lot" data-stock="40" data-availability="in_stock" data-image="image/divaris-shirichena-xZqfw-VXnYE-unsplash.jpg">
                            <button class="quick-view" type="button" data-quick-view aria-label="Apercu rapide Legumes de saison (assortiment)">
                                <i class="fas fa-eye" aria-hidden="true"></i><span>Apercu rapide</span>
                            </button>
                            <img src="image/divaris-shirichena-xZqfw-VXnYE-unsplash.jpg" alt="Legumes de saison (assortiment)" loading="lazy" width="1200" height="800">
                            <h3 class="product-name">Légumes de saison (assortiment)</h3>
                            <p class="product-meta"><span class="product-price">$24.00</span><span class="product-unit">/lot</span></p>
                            <p class="product-stock"><span class="available-quantity">40</span> lots <span class="available-status">en stock</span></p>
                            <div class="product-actions">
                                <div class="qty" role="group" aria-label="Quantite">
                                    <button type="button" class="qty-btn" data-qty-minus aria-label="Diminuer la quantite">-</button>
                                    <input class="qty-input" type="number" min="1" value="1" inputmode="numeric" aria-label="Quantite">
                                    <button type="button" class="qty-btn" data-qty-plus aria-label="Augmenter la quantite">+</button>
                                </div>
                                <button type="button" class="btn btn--primary product-add" data-add-to-cart>Ajouter au panier</button>
                            </div>
                        </div>
                        <div class="product" data-product-id="p3" data-name="Mil (céréale)" data-price="3.2" data-unit="kg" data-stock="0" data-availability="backorder" data-image="logo_slide_img/pexels-pixabay-54082.jpg">
                            <button class="quick-view" type="button" data-quick-view aria-label="Apercu rapide Mil (cereale)">
                                <i class="fas fa-eye" aria-hidden="true"></i><span>Apercu rapide</span>
                            </button>
                            <img src="logo_slide_img/pexels-pixabay-54082.jpg" alt="Mil (céréale)" loading="lazy" width="1200" height="800">
                            <h3 class="product-name">Mil (céréale)</h3>
                            <p class="product-meta"><span class="product-price">$3.20</span><span class="product-unit">/kg</span></p>
                            <p class="product-stock"><span class="available-quantity">—</span> <span class="available-status">sur commande</span></p>
                            <div class="product-actions">
                                <div class="qty" role="group" aria-label="Quantite">
                                    <button type="button" class="qty-btn" data-qty-minus aria-label="Diminuer la quantite">-</button>
                                    <input class="qty-input" type="number" min="1" value="1" inputmode="numeric" aria-label="Quantite">
                                    <button type="button" class="qty-btn" data-qty-plus aria-label="Augmenter la quantite">+</button>
                                </div>
                                <button type="button" class="btn btn--primary product-add" data-add-to-cart>Ajouter au panier</button>
                            </div>
                        </div>
                        <div class="product" data-product-id="p4" data-name="Produit indisponible (exemple)" data-price="10" data-unit="kg" data-stock="0" data-availability="out_of_stock" data-image="logo_slide_img/steven-weeks-DUPFowqI6oI-unsplash.jpg">
                            <button class="quick-view" type="button" data-quick-view aria-label="Apercu rapide Produit indisponible (exemple)">
                                <i class="fas fa-eye" aria-hidden="true"></i><span>Apercu rapide</span>
                            </button>
                            <img src="logo_slide_img/steven-weeks-DUPFowqI6oI-unsplash.jpg" alt="Produit indisponible (exemple)" loading="lazy" width="1200" height="800">
                            <h3 class="product-name">Produit indisponible (exemple)</h3>
                            <p class="product-meta"><span class="product-price">$10.00</span><span class="product-unit">/kg</span></p>
                            <p class="product-stock"><span class="available-quantity">0</span>kg <span class="available-status">rupture de stock</span></p>
                            <div class="product-actions">
                                <div class="qty" role="group" aria-label="Quantite">
                                    <button type="button" class="qty-btn" data-qty-minus aria-label="Diminuer la quantite">-</button>
                                    <input class="qty-input" type="number" min="1" value="1" inputmode="numeric" aria-label="Quantite">
                                    <button type="button" class="qty-btn" data-qty-plus aria-label="Augmenter la quantite">+</button>
                                </div>
                                <button type="button" class="btn btn--primary product-add" data-add-to-cart>Ajouter au panier</button>
                            </div>
                        </div>
                        <div class="product" data-product-id="p5" data-name="Mangues (lot)" data-price="18" data-unit="lot" data-stock="25" data-availability="in_stock" data-image="logo_slide_img/megan-thomas-xMh_ww8HN_Q-unsplash.jpg">
                            <button class="quick-view" type="button" data-quick-view aria-label="Apercu rapide Mangues (lot)">
                                <i class="fas fa-eye" aria-hidden="true"></i><span>Apercu rapide</span>
                            </button>
                            <img src="logo_slide_img/megan-thomas-xMh_ww8HN_Q-unsplash.jpg" alt="Mangues (lot)" loading="lazy" width="1200" height="800">
                            <h3 class="product-name">Mangues (lot)</h3>
                            <p class="product-meta"><span class="product-price">$18.00</span><span class="product-unit">/lot</span></p>
                            <p class="product-stock"><span class="available-quantity">25</span> lots <span class="available-status">en stock</span></p>
                            <div class="product-actions">
                                <div class="qty" role="group" aria-label="Quantite">
                                    <button type="button" class="qty-btn" data-qty-minus aria-label="Diminuer la quantite">-</button>
                                    <input class="qty-input" type="number" min="1" value="1" inputmode="numeric" aria-label="Quantite">
                                    <button type="button" class="qty-btn" data-qty-plus aria-label="Augmenter la quantite">+</button>
                                </div>
                                <button type="button" class="btn btn--primary product-add" data-add-to-cart>Ajouter au panier</button>
                            </div>
                        </div>
                        <div class="product" data-product-id="p6" data-name="Tomates (kg)" data-price="4.8" data-unit="kg" data-stock="60" data-availability="in_stock" data-image="logo_slide_img/pexels-ivan-torres-594557-1374651.jpg">
                            <button class="quick-view" type="button" data-quick-view aria-label="Apercu rapide Tomates (kg)">
                                <i class="fas fa-eye" aria-hidden="true"></i><span>Apercu rapide</span>
                            </button>
                            <img src="logo_slide_img/pexels-ivan-torres-594557-1374651.jpg" alt="Tomates (kg)" loading="lazy" width="1200" height="800">
                            <h3 class="product-name">Tomates (kg)</h3>
                            <p class="product-meta"><span class="product-price">$4.80</span><span class="product-unit">/kg</span></p>
                            <p class="product-stock"><span class="available-quantity">60</span>kg <span class="available-status">en stock</span></p>
                            <div class="product-actions">
                                <div class="qty" role="group" aria-label="Quantite">
                                    <button type="button" class="qty-btn" data-qty-minus aria-label="Diminuer la quantite">-</button>
                                    <input class="qty-input" type="number" min="1" value="1" inputmode="numeric" aria-label="Quantite">
                                    <button type="button" class="qty-btn" data-qty-plus aria-label="Augmenter la quantite">+</button>
                                </div>
                                <button type="button" class="btn btn--primary product-add" data-add-to-cart>Ajouter au panier</button>
                            </div>
                        </div>
                        <div class="product" data-product-id="p7" data-name="Céréales mix (kg)" data-price="5.1" data-unit="kg" data-stock="0" data-availability="preorder" data-image="logo_slide_img/pexels-livier-garcia-645743-1459331.jpg">
                            <button class="quick-view" type="button" data-quick-view aria-label="Apercu rapide Cereales mix (kg)">
                                <i class="fas fa-eye" aria-hidden="true"></i><span>Apercu rapide</span>
                            </button>
                            <img src="logo_slide_img/pexels-livier-garcia-645743-1459331.jpg" alt="Céréales mix (kg)" loading="lazy" width="1200" height="800">
                            <h3 class="product-name">Céréales mix (kg)</h3>
                            <p class="product-meta"><span class="product-price">$5.10</span><span class="product-unit">/kg</span></p>
                            <p class="product-stock"><span class="available-quantity">—</span> <span class="available-status">precommande</span></p>
                            <div class="product-actions">
                                <div class="qty" role="group" aria-label="Quantite">
                                    <button type="button" class="qty-btn" data-qty-minus aria-label="Diminuer la quantite">-</button>
                                    <input class="qty-input" type="number" min="1" value="1" inputmode="numeric" aria-label="Quantite">
                                    <button type="button" class="qty-btn" data-qty-plus aria-label="Augmenter la quantite">+</button>
                                </div>
                                <button type="button" class="btn btn--primary product-add" data-add-to-cart>Ajouter au panier</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="section-cta">
                <a class="btn btn--primary" href="<?= h(app_url('products.php')) ?>">Order Online</a>
            </div>
        </section>
            </div>
        </div>
        <!-- Product modal (accessible) -->
        <div class="modal" id="product-modal" role="dialog" aria-modal="true" aria-labelledby="product-modal-title" hidden>
            <div class="modal__panel" id="product-modal-panel" role="document">
                <button class="icon-btn modal__close" id="product-modal-close" type="button" data-modal-close aria-label="Close product details">
                    <span aria-hidden="true">x</span>
                </button>
                <img class="dialog-brand" id="product-modal-brand" src="assets/logo/logo-icon.svg" alt="" aria-hidden="true" width="40" height="40" loading="lazy">

                <div class="modal__content">
                    <img class="modal__image" id="product-modal-image" src="" alt="" width="1200" height="800">
                    <div class="modal__info">
                        <h2 class="modal__title" id="product-modal-title"></h2>
                        <p class="modal__sku" id="product-modal-sku"></p>
                        <p class="modal__desc" id="product-modal-desc">Produit agricole de qualite.</p>
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


        <!--------Blogs section------------>
        <section class="blogs blogs--wix" id="blogs" aria-label="Blog">
            <div class="blogs-header">
                <h2 class="blogs-title">From Our Blog</h2>
            </div>

            <div class="blogs-grid" aria-label="Articles recents">
                <?php foreach (array_slice(blog_posts(), 0, 3) as $post): ?>
                    <?php blog_render_card($post); ?>
                <?php endforeach; ?>
            </div>
            <div class="section-cta">
                <a href="blog.php" class="btn btn--primary">See More</a>
            </div>
        </section>

        <section class="contact-strip" id="contact-us" aria-label="Contact information">
            <div class="contact-strip__card">
                <div class="contact-strip__col">
                    <p>500 Terry Francine Street<br>San Francisco, CA 94158</p>
                    <p>info@my-domain.com<br>Tel: 123-456-7890<br>Fax: 123-456-7890</p>
                </div>
                <div class="contact-strip__col">
                    <h3>Operating Hours</h3>
                    <p>Mon - Fri: 8am - 8pm<br>Saturday: 9am - 7pm<br>Sunday: 9am - 8pm</p>
                </div>
                <div class="contact-strip__col">
                    <h3>Delivery Hours</h3>
                    <p>Mondays: 8am - 1pm<br>Wednesdays: 8am - 1pm<br>Fridays: 8am - 1pm</p>
                </div>
            </div>
        </section>
    </main>

    <!-- UI: backdrop + cart drawer + toast -->
    <div class="ui-backdrop" data-backdrop hidden></div>

    <aside class="cart-drawer" id="cart" role="dialog" aria-modal="true" aria-labelledby="cart-title" hidden>
        <div class="cart-drawer__header">
            <img src="assets/logo/logo-icon.svg" alt="" aria-hidden="true" width="32" height="32" loading="lazy">
            <h2 class="cart-drawer__title" id="cart-title">Votre panier</h2>
            <button class="icon-btn" type="button" data-cart-close aria-label="Fermer le panier"><span aria-hidden="true">×</span></button>
        </div>
        <div class="cart-drawer__body">
            <p class="cart-empty" data-cart-empty>Votre panier est vide.</p>
            <ul class="cart-items" data-cart-items aria-label="Articles du panier"></ul>
        </div>
        <div class="cart-drawer__footer">
            <div class="cart-total"><span>Total</span><strong data-cart-total>$0.00</strong></div>
            <a class="btn btn--secondary" href="cart.php">Voir le panier</a>
            <button class="btn btn--primary" type="button" data-checkout>Paiement</button>
        </div>
    </aside>

    <div class="toast" id="toast" role="status" aria-live="polite" aria-atomic="true" hidden></div>
    <?php $footerHomeHref = '#top'; require __DIR__ . '/footer.php'; ?>
    <div class="wix-side-badge" aria-hidden="true">STORE</div>
    <button class="wix-chat-fab" type="button" aria-label="Open chat">💬</button>
    <script src="script.js" defer></script>
</body>
</html>
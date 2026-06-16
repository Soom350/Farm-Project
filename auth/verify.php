<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib_auth.php';

$token = trim((string)($_GET['token'] ?? ''));
$res = auth_verify_email_with_token($token);

$pageTitle = 'Vérification email | Timbuktu Farming';
$pageDescription = 'Vérification email.';
require __DIR__ . '/../layout_top.php';
?>

<section class="services" aria-labelledby="verify-title">
    <header class="services__header" data-reveal style="--reveal-delay: 0ms;">
        <p class="services__eyebrow">Compte</p>
        <h1 class="services__title" id="verify-title">Vérification email</h1>
        <p class="services__lead">Traitement du lien de vérification.</p>
    </header>

    <div class="services__grid services__grid--single">
        <article class="service-card">
            <div class="service-card__body">
                <?php if ($res['ok'] ?? false): ?>
                    <div class="alert">
                        <div class="alert__title">Succès</div>
                        <div>Email vérifié.</div>
                    </div>
                    <div class="actions mt-2">
                        <a class="btn btn--primary" href="../checkout.php">Continuer vers le checkout</a>
                        <a class="btn btn--secondary" href="../account.php">Mon compte</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert--error" role="alert">
                        <div class="alert__title">Erreur</div>
                        <ul class="alert__list">
                            <?php foreach ((array)($res['errors'] ?? []) as $e): ?>
                                <li><?= h((string)$e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="actions mt-2">
                        <a class="btn btn--secondary" href="verify-required.php">Renvoyer un email</a>
                    </div>
                <?php endif; ?>
            </div>
        </article>
    </div>
</section>

<?php require __DIR__ . '/../layout_bottom.php'; ?>


<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_bootstrap.php';
require_once __DIR__ . '/lib_auth.php';
require_once __DIR__ . '/lib_db.php';
require_once __DIR__ . '/lib_cart.php';

if (!stripe_is_active()) {
    http_response_code(404);
    echo 'Stripe non configure.';
    exit;
}

$sessionId = trim((string)($_GET['session_id'] ?? ''));
if ($sessionId === '') {
    redirect('checkout.php');
}

$session = stripe_retrieve_checkout_session($sessionId);
if (!$session) {
    flash_set('error', 'Session Stripe invalide ou expiree.');
    redirect('checkout.php');
}

$orderRef = (string)($session['metadata']['order_ref'] ?? $session['client_reference_id'] ?? '');
$userId = (int)($session['metadata']['user_id'] ?? 0);
$u = auth_user();

if (!$u || (int)($u['id'] ?? 0) !== $userId) {
    http_response_code(403);
    echo 'Acces refuse.';
    exit;
}

if ((string)($session['payment_status'] ?? '') !== 'paid') {
    flash_set('error', 'Paiement non confirme. Merci de reessayer.');
    redirect('checkout.php');
}

stripe_mark_order_paid(
    $orderRef,
    (string)($session['id'] ?? ''),
    is_string($session['payment_intent'] ?? null) ? (string)$session['payment_intent'] : null
);
stripe_finalize_paid_order($orderRef);

redirect(url_with_params('order-confirmation.php', ['order' => $orderRef]));

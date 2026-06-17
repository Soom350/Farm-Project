<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_bootstrap.php';

http_response_code(200);
header('Content-Type: text/plain; charset=UTF-8');

if (!stripe_is_active()) {
    http_response_code(404);
    echo 'Stripe non configure.';
    exit;
}

$payload = file_get_contents('php://input');
if (!is_string($payload) || $payload === '') {
    http_response_code(400);
    echo 'Payload manquant.';
    exit;
}

$signature = (string)($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');
$event = stripe_parse_webhook($payload, $signature);
if (!$event) {
    http_response_code(400);
    echo 'Signature webhook invalide.';
    exit;
}

$type = (string)($event['type'] ?? '');
if ($type === 'checkout.session.completed') {
    $session = $event['data']['object'] ?? null;
    if (is_array($session)) {
        stripe_handle_checkout_completed($session);
    }
}

echo 'ok';

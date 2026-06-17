<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/lib_config.php';

function stripe_secret_key(): ?string
{
    $key = app_env('STRIPE_SECRET_KEY');
    return is_string($key) && $key !== '' ? $key : null;
}

function stripe_publishable_key(): ?string
{
    $key = app_env('STRIPE_PUBLISHABLE_KEY');
    return is_string($key) && $key !== '' ? $key : null;
}

function stripe_webhook_secret(): ?string
{
    $key = app_env('STRIPE_WEBHOOK_SECRET');
    return is_string($key) && $key !== '' ? $key : null;
}

function stripe_is_configured(): bool
{
    return stripe_secret_key() !== null && stripe_publishable_key() !== null;
}

function stripe_is_active(): bool
{
    return plugin_is_enabled('stripe') && stripe_is_configured();
}

/**
 * @param array<string, scalar|null> $params
 * @return array<string, mixed>|null
 */
function stripe_api_request(string $method, string $path, array $params = []): ?array
{
    $secret = stripe_secret_key();
    if ($secret === null) return null;

    $url = 'https://api.stripe.com/v1/' . ltrim($path, '/');
    $method = strtoupper($method);

    $ch = curl_init();
    if ($ch === false) return null;

    $headers = [
        'Authorization: Bearer ' . $secret,
        'Content-Type: application/x-www-form-urlencoded',
    ];

    if ($method === 'GET' && $params) {
        $url .= '?' . http_build_query($params);
    }

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => $method,
    ]);

    if ($method !== 'GET' && $params) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    }

    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!is_string($body) || $body === '') return null;
    $json = json_decode($body, true);
    if (!is_array($json)) return null;
    if ($status >= 400) return null;

    return $json;
}

/**
 * @param array<string, mixed> $order
 * @param array<string, mixed> $totals
 */
function stripe_create_checkout_session(array $order, array $totals, string $successUrl, string $cancelUrl): ?array
{
    $currency = strtolower((string)($totals['currency'] ?? 'usd'));
    $orderRef = (string)($order['order_ref'] ?? '');
    $userId = (int)($order['user_id'] ?? 0);
    $email = (string)($order['customer_email'] ?? '');

    $params = [
        'mode' => 'payment',
        'success_url' => $successUrl,
        'cancel_url' => $cancelUrl,
        'client_reference_id' => $orderRef,
        'metadata[order_ref]' => $orderRef,
        'metadata[user_id]' => (string)$userId,
    ];

    if ($email !== '') {
        $params['customer_email'] = $email;
    }

    $idx = 0;
    foreach ((array)($totals['cart']['items'] ?? []) as $item) {
        if (!is_array($item)) continue;
        $product = $item['product'] ?? null;
        if (!is_array($product)) continue;

        $name = (string)($product['name'] ?? 'Product');
        $unitPrice = (float)($product['price'] ?? 0);
        $qty = max(1, (int)($item['qty'] ?? 1));

        $params["line_items[{$idx}][price_data][currency]"] = $currency;
        $params["line_items[{$idx}][price_data][product_data][name]"] = $name;
        $params["line_items[{$idx}][price_data][unit_amount]"] = (int)round($unitPrice * 100);
        $params["line_items[{$idx}][quantity]"] = $qty;
        $idx++;
    }

    $shipping = (float)($totals['shipping'] ?? 0);
    if ($shipping > 0) {
        $params["line_items[{$idx}][price_data][currency]"] = $currency;
        $params["line_items[{$idx}][price_data][product_data][name]"] = 'Shipping';
        $params["line_items[{$idx}][price_data][unit_amount]"] = (int)round($shipping * 100);
        $params["line_items[{$idx}][quantity]"] = 1;
        $idx++;
    }

    $taxes = (float)($totals['taxes'] ?? 0);
    if ($taxes > 0) {
        $params["line_items[{$idx}][price_data][currency]"] = $currency;
        $params["line_items[{$idx}][price_data][product_data][name]"] = 'Taxes';
        $params["line_items[{$idx}][price_data][unit_amount]"] = (int)round($taxes * 100);
        $params["line_items[{$idx}][quantity]"] = 1;
    }

    return stripe_api_request('POST', 'checkout/sessions', $params);
}

function stripe_retrieve_checkout_session(string $sessionId): ?array
{
    $sessionId = trim($sessionId);
    if ($sessionId === '' || !str_starts_with($sessionId, 'cs_')) return null;

    $session = stripe_api_request('GET', 'checkout/sessions/' . rawurlencode($sessionId));
    return is_array($session) ? $session : null;
}

/**
 * @return array<string, mixed>|null
 */
function stripe_parse_webhook(string $payload, string $signatureHeader): ?array
{
    $secret = stripe_webhook_secret();
    if ($secret === null || $payload === '' || $signatureHeader === '') return null;

    $timestamp = null;
    $signatures = [];
    foreach (explode(',', $signatureHeader) as $part) {
        $part = trim($part);
        if (!str_contains($part, '=')) continue;
        [$key, $value] = explode('=', $part, 2);
        if ($key === 't') $timestamp = $value;
        if ($key === 'v1') $signatures[] = $value;
    }

    if ($timestamp === null || !$signatures) return null;
    if (abs(time() - (int)$timestamp) > 300) return null;

    $signedPayload = $timestamp . '.' . $payload;
    $expected = hash_hmac('sha256', $signedPayload, $secret);

    $valid = false;
    foreach ($signatures as $sig) {
        if (hash_equals($expected, $sig)) {
            $valid = true;
            break;
        }
    }
    if (!$valid) return null;

    $event = json_decode($payload, true);
    return is_array($event) ? $event : null;
}

function stripe_mark_order_paid(string $orderRef, ?string $sessionId = null, ?string $paymentIntentId = null): bool
{
    if (app_frontend_only()) return false;

    require_once dirname(__DIR__, 2) . '/lib_db.php';

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, status FROM orders WHERE order_ref = :ref LIMIT 1');
    $stmt->execute([':ref' => $orderRef]);
    $order = $stmt->fetch();
    if (!is_array($order)) return false;

    if ((string)($order['status'] ?? '') === 'paid') return true;

    $pdo->prepare("
        UPDATE orders SET
            status = 'paid',
            stripe_checkout_session_id = COALESCE(:sid, stripe_checkout_session_id),
            stripe_payment_intent_id = COALESCE(:pid, stripe_payment_intent_id)
        WHERE id = :id
    ")->execute([
        ':sid' => $sessionId,
        ':pid' => $paymentIntentId,
        ':id' => (int)$order['id'],
    ]);

    return true;
}

function stripe_finalize_paid_order(string $orderRef): void
{
    require_once dirname(__DIR__, 2) . '/lib_cart.php';

    cart_clear();
    $_SESSION['_last_order_ref'] = $orderRef;
}

function stripe_handle_checkout_completed(array $session): bool
{
    $orderRef = (string)($session['metadata']['order_ref'] ?? $session['client_reference_id'] ?? '');
    if ($orderRef === '') return false;
    if ((string)($session['payment_status'] ?? '') !== 'paid') return false;

    $sessionId = (string)($session['id'] ?? '');
    $paymentIntentId = is_string($session['payment_intent'] ?? null) ? (string)$session['payment_intent'] : null;

    return stripe_mark_order_paid($orderRef, $sessionId !== '' ? $sessionId : null, $paymentIntentId);
}

function stripe_payment_uses_checkout(string $paymentMethod): bool
{
    return in_array($paymentMethod, ['card', 'wallet'], true);
}

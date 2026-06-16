<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_cart.php';

function checkout_countries(): array
{
    // Minimal set (to extend).
    return [
        'US' => 'United States',
        'FR' => 'France',
        'DE' => 'Germany',
        'GB' => 'United Kingdom',
        'CA' => 'Canada',
        'ML' => 'Mali',
    ];
}

function checkout_shipping_methods(): array
{
    return [
        'standard' => [
            'label' => 'Standard',
            'days_min' => 5,
            'days_max' => 12,
        ],
        'express' => [
            'label' => 'Express',
            'days_min' => 2,
            'days_max' => 5,
        ],
    ];
}

function checkout_shipping_cost(string $country, string $method, array $cartTotals): float
{
    $country = strtoupper(trim($country));
    $method = trim($method);

    $base = 0.0;
    $subtotal = (float)($cartTotals['subtotal'] ?? 0.0);
    $qtyTotal = (int)($cartTotals['qty_total'] ?? 0);

    // Zone logic (mock): US/CA = zone A, EU/GB = zone B, others = zone C
    $zone = 'C';
    if (in_array($country, ['US', 'CA'], true)) $zone = 'A';
    if (in_array($country, ['FR', 'DE', 'GB'], true)) $zone = 'B';

    if ($zone === 'A') $base = 12.00;
    if ($zone === 'B') $base = 18.00;
    if ($zone === 'C') $base = 25.00;

    // Express surcharge
    if ($method === 'express') $base += 18.00;

    // Simple volumetric: small per-item
    $base += max(0, $qtyTotal - 1) * 1.25;

    // Free shipping threshold (example)
    if ($method === 'standard' && $subtotal >= 120.0) return 0.0;

    return round($base, 2);
}

function checkout_tax_rate(string $country, bool $isBusiness, ?string $vatId): float
{
    $country = strtoupper(trim($country));
    $vatId = $vatId ? trim($vatId) : '';

    // Mock: FR/DE => TVA 20% si B2C, 0% si B2B avec VAT ID; US => 0% (mock)
    if (in_array($country, ['FR', 'DE'], true)) {
        if ($isBusiness && $vatId !== '') return 0.0;
        return 0.20;
    }
    if ($country === 'GB') return 0.20; // mock
    if ($country === 'CA') return 0.05; // mock
    return 0.0;
}

function checkout_compute_totals(array $input): array
{
    $cart = cart_totals();

    $country = strtoupper((string)($input['shipping_country'] ?? ''));
    $method = (string)($input['shipping_method'] ?? 'standard');
    $isBusiness = (bool)($input['is_business'] ?? false);
    $vatId = (string)($input['vat_id'] ?? '');

    $shipping = checkout_shipping_cost($country, $method, $cart);
    $taxRate = checkout_tax_rate($country, $isBusiness, $vatId);

    $taxable = (float)$cart['subtotal']; // shipping tax handling: mock exclu
    $taxes = round($taxable * $taxRate, 2);
    $total = round($cart['subtotal'] + $shipping + $taxes, 2);

    return [
        'cart' => $cart,
        'shipping' => $shipping,
        'tax_rate' => $taxRate,
        'taxes' => $taxes,
        'total' => $total,
        'currency' => $_SESSION['currency'] ?? 'USD',
    ];
}

function checkout_validate_address(array $addr, string $prefix = ''): array
{
    // Validation minimale; à remplacer par validation pays/PSL + API.
    $errors = [];
    $fields = [
        'country' => 'Pays',
        'line1' => 'Adresse',
        'city' => 'Ville',
        'postal_code' => 'Code postal',
    ];
    foreach ($fields as $k => $label) {
        $v = trim((string)($addr[$k] ?? ''));
        if ($v === '') {
            $errors[$prefix . $k] = $label . ' requis.';
        }
    }
    return $errors;
}


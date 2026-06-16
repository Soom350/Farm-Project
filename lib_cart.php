<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_catalog.php';

function cart_get_raw(): array
{
    // sku => qty
    $cart = $_SESSION['cart'] ?? [];
    if (!is_array($cart)) $cart = [];

    $out = [];
    foreach ($cart as $sku => $qty) {
        if (!is_string($sku)) continue;
        $n = (int)$qty;
        if ($n <= 0) continue;
        $out[$sku] = $n;
    }
    $_SESSION['cart'] = $out;
    return $out;
}

function cart_set_raw(array $raw): void
{
    $clean = [];
    foreach ($raw as $sku => $qty) {
        if (!is_string($sku)) continue;
        $sku = trim($sku);
        if ($sku === '') continue;
        $n = (int)$qty;
        if ($n <= 0) continue;
        $clean[$sku] = $n;
    }
    $_SESSION['cart'] = $clean;
}

function cart_add(string $sku, int $qty): void
{
    $sku = trim($sku);
    if ($sku === '' || $qty <= 0) return;

    $p = catalog_product_by_sku($sku);
    if (!$p) return;

    $current = cart_get_raw();
    $existing = (int)($current[$sku] ?? 0);

    $max = 9999;
    if ($p['availability'] === 'in_stock') {
        $max = max(0, (int)$p['stock_qty']);
    } elseif ($p['availability'] === 'out_of_stock' || $p['availability'] === 'discontinued') {
        $max = 0;
    }

    $next = min($existing + $qty, $max > 0 ? $max : $existing);
    if ($next <= 0) {
        unset($current[$sku]);
    } else {
        $current[$sku] = $next;
    }
    cart_set_raw($current);
}

function cart_update(string $sku, int $qty): void
{
    $sku = trim($sku);
    $current = cart_get_raw();
    if ($sku === '') return;

    if ($qty <= 0) {
        unset($current[$sku]);
        cart_set_raw($current);
        return;
    }

    $p = catalog_product_by_sku($sku);
    if (!$p) {
        unset($current[$sku]);
        cart_set_raw($current);
        return;
    }

    $max = 9999;
    if ($p['availability'] === 'in_stock') {
        $max = max(0, (int)$p['stock_qty']);
    } elseif ($p['availability'] === 'out_of_stock' || $p['availability'] === 'discontinued') {
        $max = 0;
    }

    $current[$sku] = min($qty, $max > 0 ? $max : $qty);
    cart_set_raw($current);
}

function cart_clear(): void
{
    $_SESSION['cart'] = [];
}

function cart_items_detailed(): array
{
    $raw = cart_get_raw();
    $items = [];

    foreach ($raw as $sku => $qty) {
        $p = catalog_product_by_sku($sku);
        if (!$p) continue;
        $items[] = [
            'sku' => $sku,
            'qty' => $qty,
            'product' => $p,
            'line_total' => $p['price'] * $qty,
        ];
    }

    return $items;
}

function cart_totals(): array
{
    $items = cart_items_detailed();
    $subtotal = 0.0;
    $qtyTotal = 0;
    foreach ($items as $it) {
        $subtotal += (float)$it['line_total'];
        $qtyTotal += (int)$it['qty'];
    }

    return [
        'items' => $items,
        'qty_total' => $qtyTotal,
        'subtotal' => $subtotal,
    ];
}


<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_cart.php';

// Endpoint utilisé par le panier JS (index.php) pour synchroniser le contenu vers la session PHP.
// Requête attendue: POST JSON { items: [{ id: "p1", qty: 2 }, ...] }
// CSRF: header X-CSRF-Token (issu de <meta name="csrf-token">).

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

require_csrf();

$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody ?: '', true);
if (!is_array($data)) $data = [];

$items = $data['items'] ?? [];
if (!is_array($items)) $items = [];

$rawCart = [];
$errors = [];

foreach ($items as $i => $it) {
    if (!is_array($it)) continue;
    $id = isset($it['id']) ? trim((string)$it['id']) : '';
    $qty = isset($it['qty']) ? (int)$it['qty'] : 0;
    if ($id === '' || $qty <= 0) continue;

    $p = catalog_product_by_id($id);
    if (!$p) {
        $errors[] = "Produit inconnu: {$id}";
        continue;
    }

    // Contrôle simple disponibilité
    if (in_array($p['availability'], ['out_of_stock', 'discontinued'], true)) {
        continue;
    }

    $max = 9999;
    if ($p['availability'] === 'in_stock') {
        $max = max(0, (int)$p['stock_qty']);
    }
    $qty = min($qty, $max > 0 ? $max : $qty);
    $rawCart[$p['sku']] = $qty;
}

cart_set_raw($rawCart);

$totals = cart_totals();
$ok = empty($errors);

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'ok' => $ok,
    'errors' => $errors,
    'qty_total' => $totals['qty_total'],
    'subtotal' => $totals['subtotal'],
    'currency' => $_SESSION['currency'] ?? 'USD',
]);


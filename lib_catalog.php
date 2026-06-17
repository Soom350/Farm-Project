<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_bootstrap.php';

/**
 * Catalogue "mock" (to replace with DB).
 * Fields aligned with needs: SKU, category(s), price, availability, shipping eligibility, etc.
 */

function catalog_categories(): array
{
    return [
        [
            'id' => 'fresh-produce',
            'name' => 'Produits frais',
            'parent_id' => null,
            'slug' => 'fresh-produce',
            'description' => 'Fruits et legumes de saison.',
            'seo' => [
                'title' => 'Produits frais',
                'description' => 'Decouvrez nos fruits et legumes frais.',
                'index' => true,
            ],
            'filterable_attributes' => ['tag', 'availability', 'min_price', 'max_price'],
        ],
        [
            'id' => 'grains',
            'name' => 'Cereales',
            'parent_id' => null,
            'slug' => 'grains',
            'description' => 'Cereales et produits secs.',
            'seo' => [
                'title' => 'Cereales',
                'description' => 'Notre selection de cereales.',
                'index' => true,
            ],
            'filterable_attributes' => ['tag', 'availability', 'min_price', 'max_price'],
        ],
        [
            'id' => 'okra',
            'name' => 'Gombo',
            'parent_id' => 'fresh-produce',
            'slug' => 'okra',
            'description' => 'Gombo premium frais.',
            'seo' => [
                'title' => 'Gombo',
                'description' => 'Gombo premium avec controle qualite.',
                'index' => true,
            ],
            'filterable_attributes' => ['availability', 'min_price', 'max_price'],
        ],
    ];
}

function catalog_category_by_slug(string $slug): ?array
{
    foreach (catalog_categories() as $cat) {
        if ($cat['slug'] === $slug) return $cat;
    }
    return null;
}

function catalog_products_seed(): array
{
    // NB: images existantes dans le repo.
    return [
        [
            'id' => 'p1',
            'sku' => 'TF-P1',
            'name' => 'Okra (gombo) frais',
            'short_description' => 'Gombo frais, selection premium.',
            'description' => 'Gombo frais issu de cultures selectionnees avec controles qualite.',
            'price' => 6.50,
            'currency' => 'USD',
            'unit' => 'kg',
            'availability' => 'in_stock', // in_stock | out_of_stock | backorder | preorder | discontinued
            'stock_qty' => 100,
            'category_slugs' => ['fresh-produce', 'okra'],
            'tags' => ['saison', 'frais'],
            'shipping_eligible' => true,
            'weight_kg' => 1.0,
            'image' => 'logo_slide_img/okra-raw.jpg',
            'specs' => [
                'origin' => 'Malaysia',
                'packaging' => '1 kg',
            ],
            'compliance' => [
                'certifications' => ['Quality Control'],
            ],
        ],
        [
            'id' => 'p2',
            'sku' => 'TF-P2',
            'name' => 'Legumes de saison (assortiment)',
            'short_description' => 'Assortiment variable selon la recolte.',
            'description' => 'Assortiment de legumes de saison selon la disponibilite.',
            'price' => 24.00,
            'currency' => 'USD',
            'unit' => 'lot',
            'availability' => 'in_stock',
            'stock_qty' => 40,
            'category_slugs' => ['fresh-produce'],
            'tags' => ['saison'],
            'shipping_eligible' => true,
            'weight_kg' => 5.0,
            'image' => 'image/divaris-shirichena-xZqfw-VXnYE-unsplash.jpg',
            'specs' => [
                'weight' => '≈ 5 kg',
            ],
            'compliance' => [
                'certifications' => ['Quality Control'],
            ],
        ],
        [
            'id' => 'p3',
            'sku' => 'TF-GR-001',
            'name' => 'Mil (cereale)',
            'short_description' => 'Mil selectionne, qualite export.',
            'description' => 'Mil alimentaire en lots controles.',
            'price' => 3.20,
            'currency' => 'USD',
            'unit' => 'kg',
            'availability' => 'backorder',
            'stock_qty' => 0,
            'category_slugs' => ['grains'],
            'tags' => ['grain'],
            'shipping_eligible' => true,
            'weight_kg' => 1.0,
            'image' => 'logo_slide_img/pexels-pixabay-54082.jpg',
            'specs' => [
                'packaging' => '1 kg',
            ],
            'compliance' => [
                'certifications' => ['Quality Control'],
            ],
        ],
        [
            'id' => 'p4',
            'sku' => 'TF-P4',
            'name' => 'Produit indisponible (exemple)',
            'short_description' => 'Exemple de rupture de stock.',
            'description' => 'Exemple de produit temporairement indisponible.',
            'price' => 10.00,
            'currency' => 'USD',
            'unit' => 'kg',
            'availability' => 'out_of_stock',
            'stock_qty' => 0,
            'category_slugs' => ['fresh-produce'],
            'tags' => [],
            'shipping_eligible' => true,
            'weight_kg' => 1.0,
            'image' => 'logo_slide_img/steven-weeks-DUPFowqI6oI-unsplash.jpg',
            'specs' => [],
            'compliance' => [
                'certifications' => ['Quality Control'],
            ],
        ],
        [
            'id' => 'p5',
            'sku' => 'TF-P5',
            'name' => 'Mangues (lot)',
            'short_description' => 'Mangues selectionnees pour l\'export.',
            'description' => 'Mangues de saison avec calibration et controle qualite.',
            'price' => 18.00,
            'currency' => 'USD',
            'unit' => 'lot',
            'availability' => 'in_stock',
            'stock_qty' => 25,
            'category_slugs' => ['fresh-produce'],
            'tags' => ['season', 'fresh'],
            'shipping_eligible' => true,
            'weight_kg' => 4.0,
            'image' => 'logo_slide_img/megan-thomas-xMh_ww8HN_Q-unsplash.jpg',
            'specs' => [
                'packaging' => 'lot',
            ],
            'compliance' => [
                'certifications' => ['Quality Control'],
            ],
        ],
        [
            'id' => 'p6',
            'sku' => 'TF-P6',
            'name' => 'Tomates (kg)',
            'short_description' => 'Tomates fraiches, controlees.',
            'description' => 'Tomates triees et controlees avant expedition.',
            'price' => 4.80,
            'currency' => 'USD',
            'unit' => 'kg',
            'availability' => 'in_stock',
            'stock_qty' => 60,
            'category_slugs' => ['fresh-produce'],
            'tags' => ['fresh'],
            'shipping_eligible' => true,
            'weight_kg' => 1.0,
            'image' => 'logo_slide_img/pexels-ivan-torres-594557-1374651.jpg',
            'specs' => [
                'packaging' => '1 kg',
            ],
            'compliance' => [
                'certifications' => ['Quality Control'],
            ],
        ],
        [
            'id' => 'p7',
            'sku' => 'TF-P7',
            'name' => 'Melange de cereales (kg)',
            'short_description' => 'Melange de cereales en lot controle.',
            'description' => 'Melange de cereales issu de lots controles.',
            'price' => 5.10,
            'currency' => 'USD',
            'unit' => 'kg',
            'availability' => 'preorder',
            'stock_qty' => 0,
            'category_slugs' => ['grains'],
            'tags' => ['grain'],
            'shipping_eligible' => true,
            'weight_kg' => 1.0,
            'image' => 'logo_slide_img/pexels-livier-garcia-645743-1459331.jpg',
            'specs' => [
                'packaging' => '1 kg',
            ],
            'compliance' => [
                'certifications' => ['Quality Control'],
            ],
        ],
    ];
}

function catalog_json_decode_list(?string $json): array
{
    if ($json === null || $json === '') return [];
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function catalog_row_to_product(array $row): array
{
    return [
        'id' => (string)($row['product_id'] ?? ''),
        'sku' => (string)($row['sku'] ?? ''),
        'name' => (string)($row['name'] ?? ''),
        'short_description' => (string)($row['short_description'] ?? ''),
        'description' => (string)($row['description'] ?? ''),
        'price' => (float)($row['price'] ?? 0),
        'currency' => (string)($row['currency'] ?? 'USD'),
        'unit' => (string)($row['unit'] ?? ''),
        'availability' => (string)($row['availability'] ?? 'in_stock'),
        'stock_qty' => (int)($row['stock_qty'] ?? 0),
        'category_slugs' => catalog_json_decode_list($row['category_slugs'] ?? null),
        'tags' => catalog_json_decode_list($row['tags'] ?? null),
        'shipping_eligible' => (int)($row['shipping_eligible'] ?? 1) === 1,
        'weight_kg' => isset($row['weight_kg']) ? (float)$row['weight_kg'] : null,
        'image' => (string)($row['image'] ?? ''),
        'specs' => catalog_json_decode_list($row['specs_json'] ?? null),
        'compliance' => catalog_json_decode_list($row['compliance_json'] ?? null),
        'status' => (string)($row['status'] ?? 'active'),
        'db_id' => (int)($row['id'] ?? 0),
    ];
}

function catalog_products_from_db(bool $includeInactive = false): array
{
    require_once __DIR__ . '/lib_db.php';

    $sql = 'SELECT * FROM products';
    if (!$includeInactive) {
        $sql .= " WHERE status = 'active'";
    }
    $sql .= ' ORDER BY id ASC';

    $rows = db()->query($sql)->fetchAll();
    if (!is_array($rows)) return [];

    return array_map('catalog_row_to_product', $rows);
}

function catalog_products(bool $includeInactive = false): array
{
    if (app_frontend_only()) {
        return catalog_products_seed();
    }

    return catalog_products_from_db($includeInactive);
}

function catalog_product_by_sku(string $sku): ?array
{
    $sku = trim($sku);
    if ($sku === '') return null;

    if (!app_frontend_only()) {
        require_once __DIR__ . '/lib_db.php';
        $stmt = db()->prepare('SELECT * FROM products WHERE sku = :sku LIMIT 1');
        $stmt->execute([':sku' => $sku]);
        $row = $stmt->fetch();
        if (is_array($row)) {
            return catalog_row_to_product($row);
        }
    }

    foreach (catalog_products_seed() as $p) {
        if ($p['sku'] === $sku) return $p;
    }
    return null;
}

function catalog_product_by_id(string $id): ?array
{
    $id = trim($id);
    if ($id === '') return null;

    if (!app_frontend_only()) {
        require_once __DIR__ . '/lib_db.php';
        $stmt = db()->prepare('SELECT * FROM products WHERE product_id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (is_array($row)) {
            return catalog_row_to_product($row);
        }
    }

    foreach (catalog_products_seed() as $p) {
        if ($p['id'] === $id) return $p;
    }
    return null;
}

function catalog_availability_options(): array
{
    return [
        'in_stock' => 'En stock',
        'out_of_stock' => 'Rupture',
        'backorder' => 'Reapprovisionnement',
        'preorder' => 'Precommande',
        'discontinued' => 'Arrete',
    ];
}

function catalog_product_status_options(): array
{
    return [
        'active' => 'Actif (visible)',
        'inactive' => 'Inactif (masque)',
    ];
}

function catalog_slugify(string $value): string
{
    $value = mb_strtolower(trim($value));
    $value = (string)preg_replace('/[^a-z0-9]+/', '-', $value);
    return trim($value, '-') ?: 'product';
}

function catalog_product_save(array $input): array
{
    if (app_frontend_only()) {
        return ['ok' => false, 'errors' => ['Mode design: enregistrement produit desactive.']];
    }

    require_once __DIR__ . '/lib_db.php';

    $productId = trim((string)($input['product_id'] ?? ''));
    $sku = strtoupper(trim((string)($input['sku'] ?? '')));
    $name = trim((string)($input['name'] ?? ''));
    $shortDescription = trim((string)($input['short_description'] ?? ''));
    $description = trim((string)($input['description'] ?? ''));
    $price = (float)($input['price'] ?? 0);
    $currency = strtoupper(trim((string)($input['currency'] ?? 'USD')));
    $unit = trim((string)($input['unit'] ?? ''));
    $availability = trim((string)($input['availability'] ?? 'in_stock'));
    $stockQty = max(0, (int)($input['stock_qty'] ?? 0));
    $status = trim((string)($input['status'] ?? 'active'));
    $image = trim((string)($input['image'] ?? ''));
    $weightKg = trim((string)($input['weight_kg'] ?? ''));
    $shippingEligible = !empty($input['shipping_eligible']);

    $categorySlugs = $input['category_slugs'] ?? [];
    if (is_string($categorySlugs)) {
        $categorySlugs = array_values(array_filter(array_map('trim', explode(',', $categorySlugs))));
    }
    $tags = $input['tags'] ?? [];
    if (is_string($tags)) {
        $tags = array_values(array_filter(array_map('trim', explode(',', $tags))));
    }

    $errors = [];
    if ($sku === '') $errors['sku'] = 'SKU requis.';
    if ($name === '') $errors['name'] = 'Nom requis.';
    if ($price < 0) $errors['price'] = 'Prix invalide.';
    if (!isset(catalog_availability_options()[$availability])) $errors['availability'] = 'Disponibilite invalide.';
    if (!isset(catalog_product_status_options()[$status])) $errors['status'] = 'Statut invalide.';

    if ($errors) return ['ok' => false, 'errors' => $errors];

    $pdo = db();
    $existing = $productId !== '' ? catalog_product_by_id($productId) : null;
    if (!$existing && $productId === '') {
        $productId = 'p-' . bin2hex(random_bytes(4));
    }

    $stmtCheck = $pdo->prepare('SELECT product_id FROM products WHERE sku = :sku AND product_id != :pid LIMIT 1');
    $stmtCheck->execute([':sku' => $sku, ':pid' => $productId]);
    if ($stmtCheck->fetch()) {
        return ['ok' => false, 'errors' => ['sku' => 'Ce SKU existe deja.']];
    }

    $now = gmdate('c');
    $params = [
        ':sku' => $sku,
        ':name' => $name,
        ':short' => $shortDescription,
        ':desc' => $description,
        ':price' => $price,
        ':currency' => $currency,
        ':unit' => $unit,
        ':avail' => $availability,
        ':stock' => $stockQty,
        ':cats' => json_encode($categorySlugs, JSON_UNESCAPED_UNICODE),
        ':tags' => json_encode($tags, JSON_UNESCAPED_UNICODE),
        ':ship' => $shippingEligible ? 1 : 0,
        ':weight' => $weightKg !== '' ? (float)$weightKg : null,
        ':image' => $image,
        ':specs' => json_encode([], JSON_UNESCAPED_UNICODE),
        ':compliance' => json_encode(['certifications' => ['Quality Control']], JSON_UNESCAPED_UNICODE),
        ':status' => $status,
        ':updated' => $now,
    ];

    if ($existing) {
        $pdo->prepare("
            UPDATE products SET
                sku = :sku, name = :name, short_description = :short, description = :desc,
                price = :price, currency = :currency, unit = :unit, availability = :avail,
                stock_qty = :stock, category_slugs = :cats, tags = :tags,
                shipping_eligible = :ship, weight_kg = :weight, image = :image,
                specs_json = :specs, compliance_json = :compliance, status = :status,
                updated_at = :updated
            WHERE product_id = :pid
        ")->execute($params + [':pid' => $productId]);
    } else {
        $pdo->prepare("
            INSERT INTO products(
                product_id, sku, name, short_description, description,
                price, currency, unit, availability, stock_qty,
                category_slugs, tags, shipping_eligible, weight_kg, image,
                specs_json, compliance_json, status, created_at, updated_at
            ) VALUES (
                :pid, :sku, :name, :short, :desc,
                :price, :currency, :unit, :avail, :stock,
                :cats, :tags, :ship, :weight, :image,
                :specs, :compliance, :status, :created, :updated
            )
        ")->execute($params + [
            ':pid' => $productId,
            ':created' => $now,
        ]);
    }

    return ['ok' => true, 'product_id' => $productId];
}

function catalog_product_delete(string $productId): array
{
    if (app_frontend_only()) {
        return ['ok' => false, 'errors' => ['Mode design: suppression desactivee.']];
    }

    $productId = trim($productId);
    if ($productId === '') {
        return ['ok' => false, 'errors' => ['Produit introuvable.']];
    }

    require_once __DIR__ . '/lib_db.php';
    $stmt = db()->prepare('DELETE FROM products WHERE product_id = :pid');
    $stmt->execute([':pid' => $productId]);

    if ($stmt->rowCount() === 0) {
        return ['ok' => false, 'errors' => ['Produit introuvable.']];
    }

    return ['ok' => true];
}

function catalog_products_search(array $query): array
{
    $q = isset($query['q']) ? trim((string)$query['q']) : '';
    $category = isset($query['category']) ? trim((string)$query['category']) : '';
    $availability = isset($query['availability']) ? trim((string)$query['availability']) : '';
    $minPrice = isset($query['min_price']) ? (float)$query['min_price'] : null;
    $maxPrice = isset($query['max_price']) ? (float)$query['max_price'] : null;
    $sort = isset($query['sort']) ? trim((string)$query['sort']) : 'relevance';
    $tag = isset($query['tag']) ? trim((string)$query['tag']) : '';

    $items = catalog_products();

    $items = array_values(array_filter($items, function (array $p) use ($q, $category, $availability, $minPrice, $maxPrice, $tag) {
        if ($category !== '' && !in_array($category, $p['category_slugs'], true)) return false;
        if ($availability !== '' && $p['availability'] !== $availability) return false;
        if ($tag !== '' && !in_array($tag, $p['tags'], true)) return false;
        if ($minPrice !== null && $p['price'] < $minPrice) return false;
        if ($maxPrice !== null && $p['price'] > $maxPrice) return false;
        if ($q === '') return true;

        $hay = mb_strtolower($p['name'] . ' ' . $p['short_description'] . ' ' . $p['sku']);
        $needle = mb_strtolower($q);
        return str_contains($hay, $needle);
    }));

    // Tri
    usort($items, function (array $a, array $b) use ($sort, $q) {
        if ($sort === 'price_asc') return $a['price'] <=> $b['price'];
        if ($sort === 'price_desc') return $b['price'] <=> $a['price'];
        if ($sort === 'new') return strcmp($b['id'], $a['id']); // mock
        if ($sort === 'availability') return strcmp($a['availability'], $b['availability']);

        // relevance (mock): SKU exact > name match > else
        if ($q !== '') {
            $needle = mb_strtolower($q);
            $aScore = 0;
            $bScore = 0;
            if (mb_strtolower($a['sku']) === $needle) $aScore += 100;
            if (mb_strtolower($b['sku']) === $needle) $bScore += 100;
            if (str_contains(mb_strtolower($a['name']), $needle)) $aScore += 50;
            if (str_contains(mb_strtolower($b['name']), $needle)) $bScore += 50;
            if ($aScore !== $bScore) return $bScore <=> $aScore;
        }
        return strcmp($a['name'], $b['name']);
    });

    return $items;
}


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

function catalog_products(): array
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

function catalog_product_by_sku(string $sku): ?array
{
    $sku = trim($sku);
    foreach (catalog_products() as $p) {
        if ($p['sku'] === $sku) return $p;
    }
    return null;
}

function catalog_product_by_id(string $id): ?array
{
    $id = trim($id);
    foreach (catalog_products() as $p) {
        if ($p['id'] === $id) return $p;
    }
    return null;
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


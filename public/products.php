<?php
require_once __DIR__ . '/../app/config/config.php';
require_once BASE_PATH . '/app/helpers/functions.php';

$db = Database::connect();

if (!function_exists('lumina_catalog_array')) {
    function lumina_catalog_array(string $key): array
    {
        $value = $_GET[$key] ?? [];
        if (!is_array($value)) {
            $value = [$value];
        }

        return array_values(array_filter(array_map(static fn($item) => trim((string) $item), $value), static fn($item) => $item !== ''));
    }
}

if (!function_exists('lumina_catalog_price')) {
    function lumina_catalog_price($value): ?float
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[^0-9.]/', '', $value);
        if ($value === '') {
            return null;
        }

        return max(0, (float) $value);
    }
}

if (!function_exists('lumina_catalog_money')) {
    function lumina_catalog_money($value): string
    {
        if (function_exists('format_price')) {
            return format_price((float) $value);
        }

        return number_format((float) $value, 0, ',', '.') . 'đ';
    }
}

if (!function_exists('lumina_catalog_url')) {
    function lumina_catalog_url(array $overrides = []): string
    {
        $query = $_GET;

        foreach ($overrides as $key => $value) {
            if ($value === null) {
                unset($query[$key]);
                continue;
            }

            $query[$key] = $value;
        }

        $qs = http_build_query($query);
        return APP_URL . '/products.php' . ($qs ? '?' . $qs : '');
    }
}

if (!function_exists('lumina_catalog_image')) {
    function lumina_catalog_image(?string $path): string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return APP_URL . $path;
        }

        return APP_URL . '/' . ltrim($path, '/');
    }
}

if (!function_exists('lumina_catalog_dot_color')) {
    function lumina_catalog_dot_color(string $name): string
    {
        $lower = mb_strtolower($name, 'UTF-8');

        $map = [
            'đen' => '#151515',
            'black' => '#151515',
            'trắng' => '#f8f8f8',
            'white' => '#f8f8f8',
            'bạc' => '#c9c9c9',
            'silver' => '#c9c9c9',
            'vàng' => '#caa64b',
            'gold' => '#caa64b',
            'nâu' => '#7a4b2a',
            'brown' => '#7a4b2a',
            'xanh' => '#2f6f68',
            'green' => '#2f6f68',
            'blue' => '#1f3f77',
            'hồng' => '#f3a4b8',
            'pink' => '#f3a4b8',
            'đỏ' => '#9f403d',
            'red' => '#9f403d',
            'xám' => '#71706e',
            'gray' => '#71706e',
            'grey' => '#71706e',
        ];

        foreach ($map as $needle => $color) {
            if (str_contains($lower, $needle)) {
                return $color;
            }
        }

        $palette = ['#D4AF37', '#C0C0C0', '#1A1A1A', '#4A3728', '#2F4F4F', '#0b6f62', '#0b1c6d'];
        return $palette[abs(crc32($lower)) % count($palette)];
    }
}

$keyword = trim((string) ($_GET['q'] ?? $_GET['keyword'] ?? ''));
$categoryParam = trim((string) ($_GET['category'] ?? 'gong-kinh'));
$shapeFilters = lumina_catalog_array('shape');
$materialFilters = lumina_catalog_array('material');
$rawSubcatFilters = lumina_catalog_array('subcat');

$selectedSubcatIds = [];
$selectedSubcatSlugs = [];

foreach ($rawSubcatFilters as $rawSubcat) {
    if (ctype_digit($rawSubcat)) {
        $selectedSubcatIds[] = (int) $rawSubcat;
        continue;
    }

    $selectedSubcatSlugs[] = $rawSubcat;
}

$minPrice = lumina_catalog_price($_GET['min_price'] ?? '');
$maxPrice = lumina_catalog_price($_GET['max_price'] ?? '');
$sort = (string) ($_GET['sort'] ?? 'latest');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$category = null;

if ($categoryParam !== '') {
    if (ctype_digit($categoryParam)) {
        $catStmt = $db->prepare('SELECT * FROM categories WHERE id = :id AND is_active = 1 LIMIT 1');
        $catStmt->execute(['id' => (int) $categoryParam]);
    } else {
        $catStmt = $db->prepare('SELECT * FROM categories WHERE slug = :slug AND is_active = 1 LIMIT 1');
        $catStmt->execute(['slug' => $categoryParam]);
    }

    $category = $catStmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

if (!$category) {
    $catStmt = $db->prepare("SELECT * FROM categories WHERE slug = 'gong-kinh' AND is_active = 1 LIMIT 1");
    $catStmt->execute();
    $category = $catStmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$categoryIds = [];
$sidebarParentId = 0;

if ($category) {
    $categoryId = (int) $category['id'];
    $parentId = !empty($category['parent_id']) ? (int) $category['parent_id'] : 0;

    if ($parentId > 0) {
        $sidebarParentId = $parentId;
        $categoryIds = [$categoryId];
    } else {
        $sidebarParentId = $categoryId;
        $categoryIds = [$categoryId];

        $childStmt = $db->prepare('SELECT id FROM categories WHERE parent_id = :parent_id AND is_active = 1');
        $childStmt->execute(['parent_id' => $categoryId]);

        foreach ($childStmt->fetchAll(PDO::FETCH_ASSOC) as $child) {
            $categoryIds[] = (int) $child['id'];
        }
    }
}

$subcategories = [];

if ($sidebarParentId > 0) {
    $subcatStmt = $db->prepare(
        'SELECT c.id, c.name, c.slug, COUNT(p.id) AS products_count
         FROM categories c
         LEFT JOIN products p ON p.category_id = c.id AND p.status = "active"
         WHERE c.is_active = 1 AND c.parent_id = :parent_id
         GROUP BY c.id
         ORDER BY c.sort_order ASC, c.id ASC'
    );
    $subcatStmt->execute(['parent_id' => $sidebarParentId]);
    $subcategories = $subcatStmt->fetchAll(PDO::FETCH_ASSOC);
}

$allowedCategoryIds = $categoryIds;

if ($rawSubcatFilters !== []) {
    $validIds = [];
    foreach ($subcategories as $subcat) {
        $subcatId = (int) $subcat['id'];
        $subcatSlug = (string) $subcat['slug'];

        if (in_array($subcatId, $selectedSubcatIds, true) || in_array($subcatSlug, $selectedSubcatSlugs, true)) {
            $validIds[] = $subcatId;
        }
    }

    if ($validIds !== []) {
        $allowedCategoryIds = $validIds;
        $selectedSubcatIds = $validIds;
    }
}

$where = ['p.status = "active"'];
$params = [];

if ($allowedCategoryIds !== []) {
    $holders = [];

    foreach ($allowedCategoryIds as $idx => $catId) {
        $key = 'cat' . $idx;
        $holders[] = ':' . $key;
        $params[$key] = (int) $catId;
    }

    $where[] = 'p.category_id IN (' . implode(',', $holders) . ')';
}

if ($keyword !== '') {
    $where[] = '(p.name LIKE :keyword OR p.brand LIKE :keyword OR p.short_description LIKE :keyword OR c.name LIKE :keyword)';
    $params['keyword'] = '%' . $keyword . '%';
}

if ($shapeFilters !== []) {
    $holders = [];

    foreach ($shapeFilters as $idx => $shape) {
        $key = 'shape' . $idx;
        $holders[] = ':' . $key;
        $params[$key] = $shape;
    }

    $where[] = 'p.shape IN (' . implode(',', $holders) . ')';
}

if ($materialFilters !== []) {
    $holders = [];

    foreach ($materialFilters as $idx => $material) {
        $key = 'material' . $idx;
        $holders[] = ':' . $key;
        $params[$key] = $material;
    }

    $where[] = 'p.material IN (' . implode(',', $holders) . ')';
}

if ($minPrice !== null) {
    $where[] = 'p.default_price >= :min_price';
    $params['min_price'] = $minPrice;
}

if ($maxPrice !== null) {
    $where[] = 'p.default_price <= :max_price';
    $params['max_price'] = $maxPrice;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$orderSql = match ($sort) {
    'price_asc' => 'p.default_price ASC, p.id DESC',
    'price_desc' => 'p.default_price DESC, p.id DESC',
    'name_asc' => 'p.name ASC, p.id DESC',
    default => 'p.id DESC',
};

$countSql = "SELECT COUNT(DISTINCT p.id)
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             $whereSql";

$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalProducts = (int) $countStmt->fetchColumn();

$totalPages = max(1, (int) ceil($totalProducts / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$productSql = "SELECT
                    p.id,
                    p.name,
                    p.slug,
                    p.brand,
                    p.default_price,
                    p.compare_at_price,
                    COALESCE(NULLIF(p.thumbnail, ''), pi.image_url) AS thumbnail,
                    p.short_description,
                    p.shape,
                    p.material,
                    c.name AS category_name,
                    c.slug AS category_slug,
                    vc.variant_colors
               FROM products p
               LEFT JOIN categories c ON c.id = p.category_id
               LEFT JOIN (
                    SELECT product_id, MIN(image_url) AS image_url
                    FROM product_images
                    WHERE image_url IS NOT NULL AND image_url <> ''
                    GROUP BY product_id
               ) pi ON pi.product_id = p.id
               LEFT JOIN (
                    SELECT product_id, GROUP_CONCAT(DISTINCT color ORDER BY color SEPARATOR '||') AS variant_colors
                    FROM product_variants
                    WHERE is_active = 1 AND color IS NOT NULL AND color <> ''
                    GROUP BY product_id
               ) vc ON vc.product_id = p.id
               $whereSql
               ORDER BY $orderSql
               LIMIT :limit OFFSET :offset";

$productStmt = $db->prepare($productSql);

foreach ($params as $key => $value) {
    $productStmt->bindValue(':' . $key, $value);
}

$productStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$productStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$productStmt->execute();
$products = $productStmt->fetchAll(PDO::FETCH_ASSOC);

if (!function_exists('lumina_catalog_distinct_options')) {
    function lumina_catalog_distinct_options(PDO $db, string $column, array $categoryIds): array
    {
        if (!in_array($column, ['shape', 'material'], true)) {
            return [];
        }

        $where = ['status = "active"', "$column IS NOT NULL", "$column <> ''"];
        $params = [];

        if ($categoryIds !== []) {
            $holders = [];

            foreach ($categoryIds as $idx => $catId) {
                $key = 'catOpt' . $idx;
                $holders[] = ':' . $key;
                $params[$key] = (int) $catId;
            }

            $where[] = 'category_id IN (' . implode(',', $holders) . ')';
        }

        $sql = 'SELECT DISTINCT ' . $column . ' AS value
                FROM products
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY ' . $column . ' ASC
                LIMIT 24';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return array_values(array_filter(array_map(static fn($row) => (string) $row['value'], $stmt->fetchAll(PDO::FETCH_ASSOC))));
    }
}


if (!function_exists('lumina_catalog_price_bounds')) {
    function lumina_catalog_price_bounds(PDO $db, array $categoryIds): array
    {
        $where = ['status = "active"', 'default_price IS NOT NULL', 'default_price > 0'];
        $params = [];

        if ($categoryIds !== []) {
            $holders = [];

            foreach ($categoryIds as $idx => $catId) {
                $key = 'boundCat' . $idx;
                $holders[] = ':' . $key;
                $params[$key] = (int) $catId;
            }

            $where[] = 'category_id IN (' . implode(',', $holders) . ')';
        }

        $stmt = $db->prepare(
            'SELECT MIN(default_price) AS min_price, MAX(default_price) AS max_price
             FROM products
             WHERE ' . implode(' AND ', $where)
        );
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'min' => max(0, (float) ($row['min_price'] ?? 0)),
            'max' => max(0, (float) ($row['max_price'] ?? 0)),
        ];
    }
}

$priceBounds = lumina_catalog_price_bounds($db, $categoryIds);
$priceStep = 10000;
$priceSliderMin = 0;
$priceSliderMax = (int) (ceil(max(5000000, $priceBounds['max']) / $priceStep) * $priceStep);
$selectedMinPrice = $minPrice !== null ? (int) $minPrice : $priceSliderMin;
$selectedMaxPrice = $maxPrice !== null ? (int) $maxPrice : $priceSliderMax;

$selectedMinPrice = max($priceSliderMin, min($selectedMinPrice, $priceSliderMax));
$selectedMaxPrice = max($priceSliderMin, min($selectedMaxPrice, $priceSliderMax));

if ($selectedMinPrice > $selectedMaxPrice) {
    [$selectedMinPrice, $selectedMaxPrice] = [$selectedMaxPrice, $selectedMinPrice];
}

$shapeOptions = lumina_catalog_distinct_options($db, 'shape', $categoryIds);
$materialOptions = lumina_catalog_distinct_options($db, 'material', $categoryIds);

$categoryName = $category['name'] ?? 'Gọng kính';
$categorySlug = $category['slug'] ?? 'gong-kinh';

$defaultDescriptions = [
    'gong-kinh' => 'Khám phá bộ sưu tập gọng kính được chế tác tỉ mỉ, cân bằng giữa phong cách hiện đại, chất liệu bền nhẹ và cảm giác đeo thoải mái.',
    'kinh-mat' => 'Khám phá các mẫu kính mát tinh tế, dễ phối đồ và hỗ trợ bảo vệ mắt trong những ngày nắng.',
    'trong-kinh' => 'Lựa chọn tròng kính phù hợp với nhu cầu hằng ngày: chống ánh sáng xanh, đổi màu, siêu mỏng, chống UV và đa tròng.',
];

$description = trim((string) ($category['description'] ?? ''));
if ($description === '') {
    $description = $defaultDescriptions[$categorySlug] ?? 'Khám phá bộ sưu tập kính mắt LUMINA được chọn lọc theo danh mục.';
}

$pageTitle = $categoryName . ' - ' . APP_NAME;
$pageDescription = $description;

$productCssVersion = @filemtime(PUBLIC_PATH . '/assets/css/products-v2.css') ?: time();
$pageStyles = [
    APP_URL . '/assets/css/products-v2.css?v=' . $productCssVersion,
];

require_once BASE_PATH . '/app/views/partials/head.php';
require_once BASE_PATH . '/app/views/partials/header.php';
?>

<main class="catalog-v2">
    <section class="catalog-v2-hero">
        <div>
            <span class="catalog-v2-kicker">LUMINA Optical Atelier</span>
            <h1><?= e($categoryName) ?></h1>
            <p><?= e($description) ?></p>
        </div>
    </section>

    <section class="catalog-v2-shell">
        <aside class="catalog-v2-sidebar">
            <form method="get" action="<?= e(APP_URL) ?>/products.php" class="catalog-v2-filter">
                <input type="hidden" name="category" value="<?= e($categorySlug) ?>">

                <?php if ($keyword !== ''): ?>
                    <input type="hidden" name="q" value="<?= e($keyword) ?>">
                <?php endif; ?>

                <?php if ($subcategories): ?>
                    <div class="catalog-v2-filter-group">
                        <h2>Dòng sản phẩm</h2>
                        <div class="catalog-v2-checks">
                            <?php foreach ($subcategories as $subcat): ?>
                                <?php $subcatId = (int) $subcat['id']; ?>
                                <label class="catalog-v2-check">
                                    <input
                                        type="checkbox"
                                        name="subcat[]"
                                        value="<?= e((string) $subcatId) ?>"
                                        <?= in_array($subcatId, $selectedSubcatIds, true) ? 'checked' : '' ?>
                                        onchange="this.form.submit()"
                                    >
                                    <span><?= e($subcat['name']) ?></span>
                                    <small><?= e((string) (int) $subcat['products_count']) ?></small>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($shapeOptions): ?>
                    <div class="catalog-v2-filter-group">
                        <h2>Kiểu dáng</h2>
                        <div class="catalog-v2-checks">
                            <?php foreach ($shapeOptions as $shape): ?>
                                <label class="catalog-v2-check">
                                    <input
                                        type="checkbox"
                                        name="shape[]"
                                        value="<?= e($shape) ?>"
                                        <?= in_array($shape, $shapeFilters, true) ? 'checked' : '' ?>
                                        onchange="this.form.submit()"
                                    >
                                    <span><?= e($shape) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($materialOptions): ?>
                    <div class="catalog-v2-filter-group">
                        <h2>Chất liệu</h2>
                        <div class="catalog-v2-checks">
                            <?php foreach ($materialOptions as $material): ?>
                                <label class="catalog-v2-check">
                                    <input
                                        type="checkbox"
                                        name="material[]"
                                        value="<?= e($material) ?>"
                                        <?= in_array($material, $materialFilters, true) ? 'checked' : '' ?>
                                        onchange="this.form.submit()"
                                    >
                                    <span><?= e($material) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div
                    class="catalog-v2-filter-group catalog-v2-price-group"
                    data-price-slider
                    data-min="<?= e((string) $priceSliderMin) ?>"
                    data-max="<?= e((string) $priceSliderMax) ?>"
                    data-step="<?= e((string) $priceStep) ?>"
                >
                    <div class="catalog-v2-filter-title-row">
                        <h2>Giá</h2>
                        <i class="fi fi-rr-angle-small-up"></i>
                    </div>

                    <div class="catalog-v2-dual-range" aria-label="Bộ lọc khoảng giá">
                        <div class="catalog-v2-range-track">
                            <span class="catalog-v2-range-fill"></span>
                        </div>
                        <input
                            type="range"
                            class="catalog-v2-range catalog-v2-range-min"
                            min="<?= e((string) $priceSliderMin) ?>"
                            max="<?= e((string) $priceSliderMax) ?>"
                            step="<?= e((string) $priceStep) ?>"
                            value="<?= e((string) $selectedMinPrice) ?>"
                            aria-label="Giá thấp nhất"
                        >
                        <input
                            type="range"
                            class="catalog-v2-range catalog-v2-range-max"
                            min="<?= e((string) $priceSliderMin) ?>"
                            max="<?= e((string) $priceSliderMax) ?>"
                            step="<?= e((string) $priceStep) ?>"
                            value="<?= e((string) $selectedMaxPrice) ?>"
                            aria-label="Giá cao nhất"
                        >
                    </div>

                    <div class="catalog-v2-price-boxes">
                        <label class="catalog-v2-price-box">
                            <span>đ</span>
                            <input
                                type="number"
                                name="min_price"
                                inputmode="numeric"
                                min="<?= e((string) $priceSliderMin) ?>"
                                max="<?= e((string) $priceSliderMax) ?>"
                                step="<?= e((string) $priceStep) ?>"
                                value="<?= e((string) $selectedMinPrice) ?>"
                                aria-label="Giá từ"
                            >
                        </label>

                        <span class="catalog-v2-price-separator">~</span>

                        <label class="catalog-v2-price-box">
                            <span>đ</span>
                            <input
                                type="number"
                                name="max_price"
                                inputmode="numeric"
                                min="<?= e((string) $priceSliderMin) ?>"
                                max="<?= e((string) $priceSliderMax) ?>"
                                step="<?= e((string) $priceStep) ?>"
                                value="<?= e((string) $selectedMaxPrice) ?>"
                                aria-label="Giá đến"
                            >
                        </label>
                    </div>
                </div>

                <div class="catalog-v2-filter-actions">
                    <button type="submit">Lọc</button>
                    <a href="<?= e(APP_URL . '/products.php?category=' . urlencode($categorySlug)) ?>">Xóa lọc</a>
                </div>
            </form>
        </aside>

        <section class="catalog-v2-content">
            <div class="catalog-v2-toolbar">
                <p>Hiển thị <?= e((string) count($products)) ?> / <?= e((string) $totalProducts) ?> sản phẩm</p>

                <form method="get" action="<?= e(APP_URL) ?>/products.php" class="catalog-v2-sort">
                    <?php foreach ($_GET as $key => $value): ?>
                        <?php if ($key === 'sort' || $key === 'page') continue; ?>

                        <?php if (is_array($value)): ?>
                            <?php foreach ($value as $item): ?>
                                <input type="hidden" name="<?= e($key) ?>[]" value="<?= e((string) $item) ?>">
                            <?php endforeach; ?>
                        <?php else: ?>
                            <input type="hidden" name="<?= e($key) ?>" value="<?= e((string) $value) ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <label for="catalog-sort">Sắp xếp</label>
                    <select id="catalog-sort" name="sort" onchange="this.form.submit()">
                        <option value="latest" <?= $sort === 'latest' ? 'selected' : '' ?>>Mới nhất</option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
                        <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Tên A-Z</option>
                    </select>
                </form>
            </div>

            <?php if ($products): ?>
                <div class="catalog-v2-grid">
                    <?php foreach ($products as $product): ?>
                        <?php
                            $imageSrc = lumina_catalog_image($product['thumbnail'] ?? '');
                            $detailUrl = APP_URL . '/product-detail.php?id=' . urlencode((string) $product['id']);
                            $colors = array_values(array_filter(explode('||', (string) ($product['variant_colors'] ?? ''))));
                            if (!$colors) {
                                $colors = array_values(array_filter([(string) ($product['material'] ?? ''), (string) ($product['shape'] ?? '')]));
                            }
                        ?>
                        <article class="catalog-v2-card">
                            <a href="<?= e($detailUrl) ?>" class="catalog-v2-card-image" aria-label="<?= e($product['name']) ?>">
                                <?php if ($imageSrc !== ''): ?>
                                    <img src="<?= e($imageSrc) ?>" alt="<?= e($product['name']) ?>">
                                <?php else: ?>
                                    <div class="catalog-v2-placeholder">
                                        <i class="fi fi-rr-glasses"></i>
                                    </div>
                                <?php endif; ?>

                                <span class="catalog-v2-wishlist" aria-hidden="true">
                                    <i class="fi fi-rr-heart"></i>
                                </span>
                            </a>

                            <div class="catalog-v2-card-body">
                                <div>
                                    <h3>
                                        <a href="<?= e($detailUrl) ?>"><?= e($product['name']) ?></a>
                                    </h3>
                                    <p><?= e($product['brand'] ?: ($product['category_name'] ?? 'LUMINA')) ?></p>
                                </div>

                                <div class="catalog-v2-card-price">
                                    <strong><?= e(lumina_catalog_money($product['default_price'])) ?></strong>
                                    <?php if (!empty($product['compare_at_price']) && (float) $product['compare_at_price'] > (float) $product['default_price']): ?>
                                        <del><?= e(lumina_catalog_money($product['compare_at_price'])) ?></del>
                                    <?php endif; ?>
                                </div>

                                <?php if ($colors): ?>
                                    <div class="catalog-v2-color-row" aria-label="Màu sắc / chất liệu">
                                        <?php foreach (array_slice($colors, 0, 4) as $color): ?>
                                            <span
                                                class="catalog-v2-color-dot"
                                                title="<?= e($color) ?>"
                                                style="background: <?= e(lumina_catalog_dot_color($color)) ?>"
                                            ></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="catalog-v2-empty">
                    <i class="fi fi-rr-search"></i>
                    <h2>Không có sản phẩm phù hợp</h2>
                    <p>Thử bỏ bớt bộ lọc hoặc quay lại danh mục chính.</p>
                    <a href="<?= e(APP_URL . '/products.php?category=' . urlencode($categorySlug)) ?>">Xóa lọc</a>
                </div>
            <?php endif; ?>

            <?php if ($totalPages > 1): ?>
                <nav class="catalog-v2-pagination" aria-label="Phân trang sản phẩm">
                    <?php if ($page > 1): ?>
                        <a href="<?= e(lumina_catalog_url(['page' => $page - 1])) ?>">
                            <i class="fi fi-rr-angle-small-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php
                            if ($totalPages > 6 && $i !== 1 && $i !== $totalPages && abs($i - $page) > 1) {
                                if ($i === 2 || $i === $totalPages - 1) {
                                    echo '<span class="catalog-v2-dots">...</span>';
                                }
                                continue;
                            }
                        ?>
                        <a
                            href="<?= e(lumina_catalog_url(['page' => $i])) ?>"
                            class="<?= $i === $page ? 'is-active' : '' ?>"
                        >
                            <?= e((string) $i) ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="<?= e(lumina_catalog_url(['page' => $page + 1])) ?>">
                            <i class="fi fi-rr-angle-small-right"></i>
                        </a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </section>
    </section>
</main>


<script>
(() => {
    const sliders = document.querySelectorAll('[data-price-slider]');

    sliders.forEach((slider) => {
        const minRange = slider.querySelector('.catalog-v2-range-min');
        const maxRange = slider.querySelector('.catalog-v2-range-max');
        const minInput = slider.querySelector('input[name="min_price"]');
        const maxInput = slider.querySelector('input[name="max_price"]');
        const fill = slider.querySelector('.catalog-v2-range-fill');

        if (!minRange || !maxRange || !minInput || !maxInput || !fill) {
            return;
        }

        const minLimit = Number(slider.dataset.min || minRange.min || 0);
        const maxLimit = Number(slider.dataset.max || maxRange.max || 10000000);
        const step = Number(slider.dataset.step || minRange.step || 10000);

        const clamp = (value, min, max) => Math.min(Math.max(value, min), max);
        const valueFromInput = (input, fallback) => {
            const value = Number(String(input.value).replace(/[^0-9.]/g, ''));
            return Number.isFinite(value) ? value : fallback;
        };

        const updateFill = () => {
            const minValue = Number(minRange.value);
            const maxValue = Number(maxRange.value);
            const range = Math.max(1, maxLimit - minLimit);
            const left = ((minValue - minLimit) / range) * 100;
            const right = 100 - ((maxValue - minLimit) / range) * 100;

            fill.style.left = `${clamp(left, 0, 100)}%`;
            fill.style.right = `${clamp(right, 0, 100)}%`;
        };

        const syncFromRange = (source) => {
            let minValue = Number(minRange.value);
            let maxValue = Number(maxRange.value);

            if (minValue > maxValue - step) {
                if (source === 'min') {
                    minValue = maxValue - step;
                } else {
                    maxValue = minValue + step;
                }
            }

            minValue = clamp(minValue, minLimit, maxLimit);
            maxValue = clamp(maxValue, minLimit, maxLimit);

            minRange.value = String(minValue);
            maxRange.value = String(maxValue);
            minInput.value = String(Math.round(minValue));
            maxInput.value = String(Math.round(maxValue));
            updateFill();
        };

        const syncFromBox = (source) => {
            let minValue = valueFromInput(minInput, minLimit);
            let maxValue = valueFromInput(maxInput, maxLimit);

            minValue = clamp(minValue, minLimit, maxLimit);
            maxValue = clamp(maxValue, minLimit, maxLimit);

            if (minValue > maxValue - step) {
                if (source === 'min') {
                    minValue = maxValue - step;
                } else {
                    maxValue = minValue + step;
                }
            }

            minValue = clamp(minValue, minLimit, maxLimit);
            maxValue = clamp(maxValue, minLimit, maxLimit);

            minRange.value = String(minValue);
            maxRange.value = String(maxValue);
            minInput.value = String(Math.round(minValue));
            maxInput.value = String(Math.round(maxValue));
            updateFill();
        };

        minRange.addEventListener('input', () => syncFromRange('min'));
        maxRange.addEventListener('input', () => syncFromRange('max'));
        minInput.addEventListener('input', () => syncFromBox('min'));
        maxInput.addEventListener('input', () => syncFromBox('max'));

        syncFromRange('init');
    });
})();
</script>

<?php require_once BASE_PATH . '/app/views/partials/footer.php'; ?>

<?php
require_once dirname(__DIR__) . '/app/config/config.php';
require_once BASE_PATH . '/app/helpers/functions.php';

$db = Database::connect();

if (!function_exists('lumina_prescription_array')) {
    function lumina_prescription_array(string $key): array
    {
        $value = $_GET[$key] ?? [];

        if (!is_array($value)) {
            $value = [$value];
        }

        return array_values(array_filter(
            array_map(static fn($item) => trim((string) $item), $value),
            static fn($item) => $item !== ''
        ));
    }
}

if (!function_exists('lumina_prescription_price')) {
    function lumina_prescription_price($value): ?float
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

if (!function_exists('lumina_prescription_money')) {
    function lumina_prescription_money($value): string
    {
        if (function_exists('format_price')) {
            return format_price((float) $value);
        }

        return number_format((float) $value, 0, ',', '.') . 'đ';
    }
}

if (!function_exists('lumina_prescription_url')) {
    function lumina_prescription_url(array $overrides = []): string
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

        return APP_URL . '/prescription-start.php' . ($qs ? '?' . $qs : '');
    }
}

if (!function_exists('lumina_prescription_image')) {
    function lumina_prescription_image(?string $path): string
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

if (!function_exists('lumina_prescription_dot_color')) {
    function lumina_prescription_dot_color(string $name): string
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

if (!function_exists('lumina_prescription_distinct_options')) {
    function lumina_prescription_distinct_options(PDO $db, string $column, array $categoryIds): array
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

        return array_values(array_filter(array_map(
            static fn($row) => (string) $row['value'],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        )));
    }
}

if (!function_exists('lumina_prescription_price_bounds')) {
    function lumina_prescription_price_bounds(PDO $db, array $categoryIds): array
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

$keyword = trim((string) ($_GET['q'] ?? $_GET['keyword'] ?? ''));
$shapeFilters = lumina_prescription_array('shape');
$materialFilters = lumina_prescription_array('material');
$rawSubcatFilters = lumina_prescription_array('subcat');
$selectedSubcatIds = [];
$selectedSubcatSlugs = [];

foreach ($rawSubcatFilters as $rawSubcat) {
    if (ctype_digit($rawSubcat)) {
        $selectedSubcatIds[] = (int) $rawSubcat;
        continue;
    }

    $selectedSubcatSlugs[] = $rawSubcat;
}

$minPrice = lumina_prescription_price($_GET['min_price'] ?? '');
$maxPrice = lumina_prescription_price($_GET['max_price'] ?? '');
$sort = (string) ($_GET['sort'] ?? 'latest');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

/*
 * Trang này cố định cho flow prescription nên chỉ lấy danh mục gọng kính
 * và các danh mục con của gọng kính.
 */
$catStmt = $db->prepare("SELECT * FROM categories WHERE slug = 'gong-kinh' AND is_active = 1 LIMIT 1");
$catStmt->execute();
$category = $catStmt->fetch(PDO::FETCH_ASSOC) ?: null;

$categoryIds = [];
$sidebarParentId = 0;

if ($category) {
    $sidebarParentId = (int) $category['id'];
    $categoryIds[] = (int) $category['id'];

    $childStmt = $db->prepare('SELECT id FROM categories WHERE parent_id = :parent_id AND is_active = 1');
    $childStmt->execute(['parent_id' => (int) $category['id']]);

    foreach ($childStmt->fetchAll(PDO::FETCH_ASSOC) as $child) {
        $categoryIds[] = (int) $child['id'];
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
} else {
    // Tránh lỡ query toàn bộ sản phẩm nếu database thiếu danh mục gong-kinh.
    $where[] = '1 = 0';
}

/*
 * Nếu schema có cột is_prescription_supported thì ưu tiên sản phẩm hỗ trợ prescription.
 * Nếu schema chưa có cột này thì bỏ qua để không lỗi SQL.
 */
try {
    $columnCheck = $db->query("SHOW COLUMNS FROM products LIKE 'is_prescription_supported'");

    if ($columnCheck && $columnCheck->fetch(PDO::FETCH_ASSOC)) {
        $where[] = '(p.is_prescription_supported = 1 OR p.is_prescription_supported IS NULL)';
    }
} catch (Throwable $ignored) {
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

$priceBounds = lumina_prescription_price_bounds($db, $categoryIds);
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

$shapeOptions = lumina_prescription_distinct_options($db, 'shape', $categoryIds);
$materialOptions = lumina_prescription_distinct_options($db, 'material', $categoryIds);

$pageTitle = 'Chọn gọng làm kính - ' . APP_NAME;
$pageDescription = 'Chọn gọng kính để bắt đầu quy trình đặt kính prescription tại LUMINA.';

$productCssVersion = @filemtime(PUBLIC_PATH . '/assets/css/products-v2.css') ?: time();

$pageStyles = [
    APP_URL . '/assets/css/products-v2.css?v=' . $productCssVersion,
];

require_once BASE_PATH . '/app/views/partials/head.php';
require_once BASE_PATH . '/app/views/partials/header.php';
?>

<main class="catalog-v2 prescription-start-v2">
    <section class="prescription-flow-strip" aria-label="Quy trình đặt kính">
        <div class="prescription-flow-head">
            <a href="<?= e(APP_URL) ?>/" class="prescription-flow-back">
                <i class="fi fi-rr-arrow-left"></i>
                Quay lại trang chủ
            </a>

            <div>
                <span>Prescription Flow</span>
                <strong>Đặt kính theo đơn</strong>
            </div>
        </div>

        <div class="prescription-flow-steps">
            <div class="prescription-flow-step is-active">
                <span>1</span>
                <strong>Chọn gọng</strong>
            </div>

            <div class="prescription-flow-step">
                <span>2</span>
                <strong>Đơn kính</strong>
            </div>

            <div class="prescription-flow-step">
                <span>3</span>
                <strong>Chọn tròng</strong>
            </div>

            <div class="prescription-flow-step">
                <span>4</span>
                <strong>Thanh toán</strong>
            </div>
        </div>
    </section>

    <section class="catalog-v2-hero">
        <span class="catalog-v2-kicker">DỊCH VỤ TRÒNG KÍNH</span>
        <h1>Chọn gọng kính</h1>
        <p>
            Chọn một gọng kính yêu thích để bắt đầu đơn prescription.
            Trang này dùng lại bộ lọc và card sản phẩm của trang Gọng kính để giao diện đồng bộ.
        </p>
    </section>

    <section class="catalog-v2-shell">
        <aside class="catalog-v2-sidebar" aria-label="Bộ lọc gọng kính">
            <form class="catalog-v2-filter" action="<?= e(APP_URL) ?>/prescription-start.php" method="get" id="prescriptionCatalogFilter">
                <?php if ($keyword !== ''): ?>
                    <input type="hidden" name="q" value="<?= e($keyword) ?>">
                <?php endif; ?>

                <input type="hidden" name="sort" value="<?= e($sort) ?>">

                <?php if ($subcategories !== []): ?>
                    <section class="catalog-v2-filter-group">
                        <h2>Dòng sản phẩm</h2>

                        <div class="catalog-v2-checks">
                            <?php foreach ($subcategories as $subcat): ?>
                                <label class="catalog-v2-check">
                                    <input
                                        type="checkbox"
                                        name="subcat[]"
                                        value="<?= e((string) $subcat['id']) ?>"
                                        <?= in_array((int) $subcat['id'], $selectedSubcatIds, true) ? 'checked' : '' ?>
                                        onchange="this.form.submit()"
                                    >
                                    <span><?= e($subcat['name']) ?> <small><?= e((string) (int) $subcat['products_count']) ?></small></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($shapeOptions !== []): ?>
                    <section class="catalog-v2-filter-group">
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
                    </section>
                <?php endif; ?>

                <?php if ($materialOptions !== []): ?>
                    <section class="catalog-v2-filter-group">
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
                    </section>
                <?php endif; ?>

                <section class="catalog-v2-filter-group catalog-v2-price-group">
                    <div class="catalog-v2-filter-title-row">
                        <h2>Giá</h2>
                        <i class="fi fi-rr-angle-small-up"></i>
                    </div>

                    <div
                        class="catalog-v2-dual-range"
                        data-min="<?= e((string) $priceSliderMin) ?>"
                        data-max="<?= e((string) $priceSliderMax) ?>"
                        data-step="<?= e((string) $priceStep) ?>"
                    >
                        <div class="catalog-v2-range-track">
                            <span class="catalog-v2-range-fill"></span>
                        </div>

                        <input
                            class="catalog-v2-range catalog-v2-range-min"
                            type="range"
                            min="<?= e((string) $priceSliderMin) ?>"
                            max="<?= e((string) $priceSliderMax) ?>"
                            step="<?= e((string) $priceStep) ?>"
                            value="<?= e((string) $selectedMinPrice) ?>"
                            aria-label="Giá thấp nhất"
                        >

                        <input
                            class="catalog-v2-range catalog-v2-range-max"
                            type="range"
                            min="<?= e((string) $priceSliderMin) ?>"
                            max="<?= e((string) $priceSliderMax) ?>"
                            step="<?= e((string) $priceStep) ?>"
                            value="<?= e((string) $selectedMaxPrice) ?>"
                            aria-label="Giá cao nhất"
                        >
                    </div>

                    <div class="catalog-v2-price-boxes">
                        <label class="catalog-v2-price-box">
                            <span>₫</span>
                            <input
                                type="number"
                                name="min_price"
                                min="<?= e((string) $priceSliderMin) ?>"
                                max="<?= e((string) $priceSliderMax) ?>"
                                step="<?= e((string) $priceStep) ?>"
                                value="<?= e((string) $selectedMinPrice) ?>"
                                aria-label="Nhập giá thấp nhất"
                            >
                        </label>

                        <span class="catalog-v2-price-separator">~</span>

                        <label class="catalog-v2-price-box">
                            <span>₫</span>
                            <input
                                type="number"
                                name="max_price"
                                min="<?= e((string) $priceSliderMin) ?>"
                                max="<?= e((string) $priceSliderMax) ?>"
                                step="<?= e((string) $priceStep) ?>"
                                value="<?= e((string) $selectedMaxPrice) ?>"
                                aria-label="Nhập giá cao nhất"
                            >
                        </label>
                    </div>
                </section>

                <div class="catalog-v2-filter-actions">
                    <button type="submit">Lọc</button>
                    <a href="<?= e(APP_URL) ?>/prescription-start.php">Xóa lọc</a>
                </div>
            </form>
        </aside>

        <section class="catalog-v2-content" aria-label="Danh sách gọng kính">
            <div class="catalog-v2-toolbar">
                <p>Hiển thị <?= e((string) count($products)) ?> / <?= e((string) $totalProducts) ?> gọng kính</p>

                <form class="catalog-v2-sort" action="<?= e(APP_URL) ?>/prescription-start.php" method="get">
                    <?php foreach ($_GET as $key => $value): ?>
                        <?php if ($key === 'sort' || $key === 'page') continue; ?>

                        <?php if (is_array($value)): ?>
                            <?php foreach ($value as $subValue): ?>
                                <input type="hidden" name="<?= e((string) $key) ?>[]" value="<?= e((string) $subValue) ?>">
                            <?php endforeach; ?>
                        <?php else: ?>
                            <input type="hidden" name="<?= e((string) $key) ?>" value="<?= e((string) $value) ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <label for="prescriptionSort">Sắp xếp</label>
                    <select id="prescriptionSort" name="sort" onchange="this.form.submit()">
                        <option value="latest" <?= $sort === 'latest' ? 'selected' : '' ?>>Mới nhất</option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
                        <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Tên A-Z</option>
                    </select>
                </form>
            </div>

            <?php if ($products !== []): ?>
                <div class="catalog-v2-grid">
                    <?php foreach ($products as $product): ?>
                        <?php
                            $detailUrl = APP_URL . '/prescription-detail.php?id=' . (int) $product['id'];
                            $colors = [];

                            if (!empty($product['variant_colors'])) {
                                $colors = array_values(array_filter(explode('||', (string) $product['variant_colors'])));
                            }

                            if ($colors === []) {
                                $colors = array_filter([
                                    (string) ($product['material'] ?? ''),
                                    (string) ($product['shape'] ?? ''),
                                    (string) ($product['category_name'] ?? ''),
                                ]);
                            }

                            $colors = array_slice($colors, 0, 4);
                        ?>

                        <article class="catalog-v2-card prescription-start-card">
                            <a class="catalog-v2-card-image" href="<?= e($detailUrl) ?>" aria-label="Chọn <?= e($product['name']) ?> để làm kính">
                                <?php $imagePath = lumina_prescription_image($product['thumbnail'] ?? ''); ?>

                                <?php if ($imagePath !== ''): ?>
                                    <img src="<?= e($imagePath) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
                                <?php else: ?>
                                    <span class="catalog-v2-placeholder"><i class="fi fi-rr-glasses"></i></span>
                                <?php endif; ?>

                                <span class="catalog-v2-wishlist prescription-start-badge" aria-hidden="true">
                                    <i class="fi fi-rr-glasses"></i>
                                </span>
                            </a>

                            <div class="catalog-v2-card-body">
                                <div>
                                    <h3><a href="<?= e($detailUrl) ?>"><?= e($product['name']) ?></a></h3>
                                    <p><?= e($product['brand'] ?: ($product['category_name'] ?: 'LUMINA')) ?></p>
                                </div>

                                <div class="catalog-v2-card-price">
                                    <strong><?= e(lumina_prescription_money($product['default_price'])) ?></strong>

                                    <?php if (!empty($product['compare_at_price']) && (float) $product['compare_at_price'] > (float) $product['default_price']): ?>
                                        <del><?= e(lumina_prescription_money($product['compare_at_price'])) ?></del>
                                    <?php endif; ?>
                                </div>

                                <div class="catalog-v2-color-row" aria-label="Màu sản phẩm">
                                    <?php foreach ($colors as $colorName): ?>
                                        <span
                                            class="catalog-v2-color-dot"
                                            title="<?= e($colorName) ?>"
                                            style="background: <?= e(lumina_prescription_dot_color($colorName)) ?>"
                                        ></span>
                                    <?php endforeach; ?>
                                </div>

                                <a class="prescription-start-cta" href="<?= e($detailUrl) ?>">
                                    Chọn làm kính
                                    <i class="fi fi-rr-arrow-right"></i>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="catalog-v2-pagination" aria-label="Phân trang gọng kính">
                        <?php if ($page > 1): ?>
                            <a href="<?= e(lumina_prescription_url(['page' => $page - 1])) ?>" aria-label="Trang trước">
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

                            <a class="<?= $i === $page ? 'is-active' : '' ?>" href="<?= e(lumina_prescription_url(['page' => $i])) ?>">
                                <?= e((string) $i) ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="<?= e(lumina_prescription_url(['page' => $page + 1])) ?>" aria-label="Trang sau">
                                <i class="fi fi-rr-angle-small-right"></i>
                            </a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="catalog-v2-empty">
                    <div>
                        <i class="fi fi-rr-search"></i>
                        <h2>Không có gọng kính phù hợp</h2>
                        <p>Thử bỏ bớt bộ lọc hoặc quay lại danh sách gọng kính.</p>
                        <a href="<?= e(APP_URL) ?>/prescription-start.php">Xóa lọc</a>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </section>
</main>

<style>
    .prescription-start-v2 .prescription-flow-strip {
        width: min(1440px, calc(100% - 96px));
        margin: 0 auto;
        padding: 28px 0 6px;
    }

    .prescription-flow-head {
        margin-bottom: 26px;
        padding: 18px 22px;
        border: 1px solid #E5E7EB;
        border-radius: 24px;
        background: #fff;
        box-shadow: 0 14px 34px rgba(15, 23, 42, .05);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
    }

    .prescription-flow-back {
        min-height: 40px;
        padding: 0 14px;
        border-radius: 999px;
        border: 1px solid #E5E7EB;
        display: inline-flex;
        align-items: center;
        gap: 9px;
        color: #1B263B;
        font-weight: 900;
    }

    .prescription-flow-head div {
        text-align: right;
    }

    .prescription-flow-head span {
        display: block;
        color: #0E766B;
        font-size: 12px;
        font-weight: 900;
        letter-spacing: .14em;
        text-transform: uppercase;
    }

    .prescription-flow-head strong {
        color: #111827;
        font-size: 15px;
    }

    .prescription-flow-steps {
        position: relative;
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 18px;
        padding: 10px 0 0;
    }

    .prescription-flow-step {
        position: relative;
        display: grid;
        justify-items: center;
        gap: 9px;
        color: #94A3B8;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .prescription-flow-step::before {
        content: "";
        position: absolute;
        top: 16px;
        left: -50%;
        width: 100%;
        height: 2px;
        background: #E7ECF2;
    }

    .prescription-flow-step:first-child::before {
        display: none;
    }

    .prescription-flow-step span {
        position: relative;
        z-index: 1;
        width: 34px;
        height: 34px;
        border-radius: 999px;
        background: #EEF2F7;
        color: #94A3B8;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
    }

    .prescription-flow-step.is-active {
        color: #0E766B;
    }

    .prescription-flow-step.is-active span {
        background: #0E766B;
        color: #fff;
    }

    .prescription-start-card .prescription-start-badge {
        color: #0E766B;
    }

    .prescription-start-cta {
        width: fit-content;
        min-height: 40px;
        padding: 0 14px;
        border-radius: 999px;
        background: #0E766B;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 900;
        transition: .18s ease;
    }

    .prescription-start-cta:hover {
        background: #0A5E56;
        transform: translateY(-1px);
    }

    @media (max-width: 860px) {
        .prescription-start-v2 .prescription-flow-strip {
            width: min(100% - 40px, 1120px);
        }

        .prescription-flow-head {
            align-items: flex-start;
            flex-direction: column;
        }

        .prescription-flow-head div {
            text-align: left;
        }

        .prescription-flow-steps {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px 12px;
        }

        .prescription-flow-step:nth-child(3)::before {
            display: none;
        }
    }

    @media (max-width: 640px) {
        .prescription-start-v2 .prescription-flow-strip {
            width: min(100% - 28px, 540px);
        }
    }
</style>

<script>
    (function () {
        const wrappers = document.querySelectorAll('.catalog-v2-dual-range');

        wrappers.forEach((wrapper) => {
            const minRange = wrapper.querySelector('.catalog-v2-range-min');
            const maxRange = wrapper.querySelector('.catalog-v2-range-max');
            const fill = wrapper.querySelector('.catalog-v2-range-fill');
            const form = wrapper.closest('form');
            const minInput = form ? form.querySelector('input[name="min_price"]') : null;
            const maxInput = form ? form.querySelector('input[name="max_price"]') : null;

            const min = Number(wrapper.dataset.min || minRange.min || 0);
            const max = Number(wrapper.dataset.max || maxRange.max || 0);
            const step = Number(wrapper.dataset.step || minRange.step || 1);

            function clamp(value) {
                value = Number(value || 0);
                return Math.min(max, Math.max(min, value));
            }

            function normalizeValues(source) {
                let low = clamp(minRange.value);
                let high = clamp(maxRange.value);

                if (low > high) {
                    if (source === 'min') {
                        high = low;
                    } else {
                        low = high;
                    }
                }

                minRange.value = low;
                maxRange.value = high;

                if (minInput) minInput.value = Math.round(low);
                if (maxInput) maxInput.value = Math.round(high);

                const lowPercent = ((low - min) / (max - min || 1)) * 100;
                const highPercent = ((high - min) / (max - min || 1)) * 100;

                if (fill) {
                    fill.style.left = lowPercent + '%';
                    fill.style.right = (100 - highPercent) + '%';
                }
            }

            minRange.addEventListener('input', () => normalizeValues('min'));
            maxRange.addEventListener('input', () => normalizeValues('max'));

            if (minInput) {
                minInput.addEventListener('change', () => {
                    minRange.value = Math.round(clamp(minInput.value) / step) * step;
                    normalizeValues('min');
                });
            }

            if (maxInput) {
                maxInput.addEventListener('change', () => {
                    maxRange.value = Math.round(clamp(maxInput.value) / step) * step;
                    normalizeValues('max');
                });
            }

            normalizeValues();
        });
    })();
</script>

<?php require_once BASE_PATH . '/app/views/partials/footer.php'; ?>

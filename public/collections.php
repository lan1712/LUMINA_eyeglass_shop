<?php
require_once __DIR__ . '/../app/config/config.php';
require_once BASE_PATH . '/app/helpers/functions.php';

$db = Database::connect();

if (!function_exists('lumina_money')) {
    function lumina_money($value): string {
        return function_exists('format_price') ? format_price((float)$value) : number_format((float)$value, 0, ',', '.') . 'đ';
    }
}

if (!function_exists('lumina_img')) {
    function lumina_img(?string $path): string {
        if (!$path) return APP_URL . '/assets/images/placeholder-glasses.svg';
        if (preg_match('/^https?:\/\//i', $path)) return $path;
        return APP_URL . '/' . ltrim($path, '/');
    }
}

$cats = [];
$catStmt = $db->query("\n    SELECT c.id, c.name, c.slug, c.description, COUNT(DISTINCT p.id) AS products_count\n    FROM categories c\n    LEFT JOIN categories child ON child.parent_id = c.id AND child.is_active = 1\n    LEFT JOIN products p ON p.status = 'active' AND (p.category_id = c.id OR p.category_id = child.id)\n    WHERE c.is_active = 1 AND c.parent_id IS NULL AND c.slug IN ('gong-kinh','kinh-mat','trong-kinh')\n    GROUP BY c.id, c.name, c.slug, c.description\n    ORDER BY FIELD(c.slug, 'gong-kinh', 'kinh-mat', 'trong-kinh')\n");
foreach ($catStmt->fetchAll(PDO::FETCH_ASSOC) as $cat) {
    $cats[$cat['slug']] = $cat;
}

$fallbackCats = [
    'gong-kinh' => 'Gọng kính',
    'kinh-mat' => 'Kính mát',
    'trong-kinh' => 'Tròng kính',
];
foreach ($fallbackCats as $slug => $name) {
    $cats[$slug] = $cats[$slug] ?? ['id' => 0, 'name' => $name, 'slug' => $slug, 'description' => '', 'products_count' => 0];
}

$productStmt = $db->query("\n    SELECT p.id, p.name, p.slug, p.brand, p.default_price, p.compare_at_price, p.thumbnail,\n           p.short_description, p.shape, p.material, c.name AS category_name, c.slug AS category_slug\n    FROM products p\n    LEFT JOIN categories c ON c.id = p.category_id\n    WHERE p.status = 'active'\n    ORDER BY p.id DESC\n    LIMIT 8\n");
$products = $productStmt->fetchAll(PDO::FETCH_ASSOC);
$heroProduct = $products[0] ?? null;

$pageTitle = 'Bộ sưu tập - ' . APP_NAME;
$pageDescription = 'Khám phá bộ sưu tập gọng kính, kính mát và tròng kính tại LUMINA.';
$pageStyles = [APP_URL . '/assets/css/info-pages.css?v=1.0.0'];
require_once BASE_PATH . '/app/views/partials/head.php';
require_once BASE_PATH . '/app/views/partials/header.php';
?>

<main class="info-page collection-page">
    <section class="info-hero collection-hero">
        <div class="info-container collection-hero__grid">
            <div class="info-hero__content">
                <span class="info-eyebrow">LUMINA COLLECTIONS</span>
                <h1>Bộ sưu tập kính mắt theo phong cách của bạn.</h1>
                <p>Khám phá nhanh ba nhóm sản phẩm chính của LUMINA: gọng kính thanh lịch, kính mát nổi bật và tròng kính hỗ trợ thị lực rõ ràng hơn mỗi ngày.</p>
                <div class="info-hero__actions">
                    <a href="<?= e(APP_URL) ?>/products.php" class="info-btn info-btn--primary">Xem tất cả sản phẩm</a>
                    <a href="<?= e(APP_URL) ?>/products.php?category=gong-kinh" class="info-btn info-btn--ghost">Khám phá gọng kính</a>
                </div>
                <div class="collection-hero__stats">
                    <?php foreach (['gong-kinh','kinh-mat','trong-kinh'] as $slug): $cat = $cats[$slug]; ?>
                        <a href="<?= e(APP_URL) ?>/products.php?category=<?= e($slug) ?>" class="collection-stat">
                            <strong><?= e((string)(int)$cat['products_count']) ?></strong>
                            <span><?= e($cat['name']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="collection-hero__visual">
                <?php if ($heroProduct): ?>
                    <img src="<?= e(lumina_img($heroProduct['thumbnail'] ?? '')) ?>" alt="<?= e($heroProduct['name']) ?>">
                    <div class="collection-hero__floating-card">
                        <span>Mẫu mới</span>
                        <strong><?= e($heroProduct['name']) ?></strong>
                        <small><?= e(lumina_money($heroProduct['default_price'] ?? 0)) ?></small>
                    </div>
                <?php else: ?>
                    <div class="collection-hero__placeholder"><i class="fi fi-rr-glasses"></i></div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="info-section">
        <div class="info-container">
            <div class="info-section-head">
                <span class="info-eyebrow">SHOP BY CATEGORY</span>
                <h2>Chọn nhanh theo nhu cầu</h2>
                <p>Ba nhóm sản phẩm chính được sắp xếp để khách dễ đi từ nhu cầu đến sản phẩm phù hợp.</p>
            </div>
            <div class="collection-category-grid">
                <?php $meta = [
                    'gong-kinh' => ['icon'=>'fi fi-rr-glasses','text'=>'Gọng kim loại, acetate, oval, mắt mèo'],
                    'kinh-mat' => ['icon'=>'fi fi-rr-sunglasses','text'=>'Kính râm nam, nữ, em bé và phong cách daily'],
                    'trong-kinh' => ['icon'=>'fi fi-rr-eye','text'=>'Tròng chống ánh sáng xanh, đổi màu, cận phổ thông'],
                ]; ?>
                <?php foreach ($meta as $slug => $item): $cat = $cats[$slug]; ?>
                    <a href="<?= e(APP_URL) ?>/products.php?category=<?= e($slug) ?>" class="collection-category-card">
                        <span class="collection-category-card__icon"><i class="<?= e($item['icon']) ?>"></i></span>
                        <span class="collection-category-card__count"><?= e((string)(int)$cat['products_count']) ?> sản phẩm</span>
                        <h3><?= e($cat['name']) ?></h3>
                        <p><?= e($item['text']) ?></p>
                        <span class="collection-category-card__link">Xem bộ sưu tập <i class="fi fi-rr-arrow-right"></i></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="info-section info-section--soft">
        <div class="info-container">
            <div class="info-section-head info-section-head--split">
                <div><span class="info-eyebrow">EDITORIAL PICKS</span><h2>Bộ sưu tập theo phong cách</h2></div>
                <p>Layout editorial: ít chữ, hình lớn, CTA rõ và khoảng trắng rộng.</p>
            </div>
            <div class="collection-editorial-grid">
                <a href="<?= e(APP_URL) ?>/products.php?category=gong-kinh" class="collection-editorial-card is-dark"><span>Minimal Daily</span><h3>Gọng mảnh cho lịch học, văn phòng và sử dụng hằng ngày.</h3><em>Khám phá gọng kính</em></a>
                <a href="<?= e(APP_URL) ?>/products.php?category=kinh-mat" class="collection-editorial-card"><span>Sun Ready</span><h3>Kính mát dễ phối đồ, phù hợp đi chơi và di chuyển.</h3><em>Xem kính mát</em></a>
                <a href="<?= e(APP_URL) ?>/products.php?category=trong-kinh" class="collection-editorial-card is-gold"><span>Clear Vision</span><h3>Tròng kính cho học tập, làm việc và bảo vệ mắt trước màn hình.</h3><em>Chọn tròng kính</em></a>
            </div>
        </div>
    </section>

    <section class="info-section">
        <div class="info-container">
            <div class="info-section-head info-section-head--split">
                <div><span class="info-eyebrow">NEW ARRIVALS</span><h2>Mẫu mới tại LUMINA</h2></div>
                <a href="<?= e(APP_URL) ?>/products.php" class="info-link">Xem tất cả <i class="fi fi-rr-arrow-right"></i></a>
            </div>
            <?php if ($products): ?>
                <div class="collection-product-grid">
                    <?php foreach ($products as $product): ?>
                        <a href="<?= e(APP_URL) ?>/product-detail.php?id=<?= e((string)$product['id']) ?>" class="collection-product-card">
                            <div class="collection-product-card__image"><img src="<?= e(lumina_img($product['thumbnail'] ?? '')) ?>" alt="<?= e($product['name']) ?>"></div>
                            <div class="collection-product-card__body"><div><h3><?= e($product['name']) ?></h3><span><?= e($product['category_name'] ?? 'LUMINA') ?></span></div><strong><?= e(lumina_money($product['default_price'] ?? 0)) ?></strong></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="info-empty">Chưa có sản phẩm active để hiển thị.</div>
            <?php endif; ?>
        </div>
    </section>

    <section class="info-section info-section--compact">
        <div class="info-container"><div class="collection-cta"><div><span class="info-eyebrow">NEED HELP?</span><h2>Chưa biết chọn loại nào?</h2><p>Đi từ danh mục tổng quan trước, sau đó dùng bộ lọc theo kiểu dáng, chất liệu và khoảng giá.</p></div><a href="<?= e(APP_URL) ?>/products.php" class="info-btn info-btn--primary">Bắt đầu lọc sản phẩm</a></div></div>
    </section>
</main>

<?php require_once BASE_PATH . '/app/views/partials/footer.php'; ?>

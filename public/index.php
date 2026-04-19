<?php

require_once dirname(__DIR__) . '/app/config/config.php';
require_once BASE_PATH . '/app/helpers/functions.php';

$db = Database::connect();

$productStmt = $db->query(
    "SELECT p.id, p.name, p.slug, p.brand, p.default_price, p.compare_at_price, p.thumbnail,
            p.is_prescription_supported, p.shape, c.name AS category_name, c.category_type
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.status = 'active'
     ORDER BY p.id DESC
     LIMIT 8"
);
$products = $productStmt->fetchAll();

$categoryStmt = $db->query(
    "SELECT id, name, slug, category_type
     FROM categories
     WHERE is_active = 1 AND parent_id IS NULL
     ORDER BY sort_order ASC, id ASC
     LIMIT 6"
);
$categories = $categoryStmt->fetchAll();

$pageTitle = APP_NAME . ' - Trang chủ';
$pageDescription = 'Shop mắt kính trực tuyến với gọng kính, kính mát, tròng kính và đơn prescription.';
$headerKeyword = '';
$placeholderImage = APP_URL . '/assets/images/placeholder-glasses.png';

require_once BASE_PATH . '/app/views/partials/head.php';
require_once BASE_PATH . '/app/views/partials/header.php';
?>
<main class="page-section">
    <div class="container">
        <section class="hero">
            <div>
                <p class="eyebrow">Shop mắt kính trực tuyến</p>
                <h1>Chọn kính đẹp, đúng nhu cầu và dễ đặt mua hơn.</h1>
                <p>
                    LUMINA hỗ trợ mua kính có sẵn, đặt trước khi hết hàng và đặt làm kính theo đơn prescription.
                    Giao diện được tối ưu để bạn xem mẫu nhanh, so sánh dễ và đặt hàng thuận tiện.
                </p>
                <div class="hero-actions">
                    <a class="btn-primary" href="<?= e(APP_URL) ?>/products.php">
                        <i class="fi fi-rr-search icon icon-sm"></i>
                        Khám phá sản phẩm
                    </a>
                    <a class="btn-outline" href="<?= e(APP_URL) ?>/products.php?type=frame">
                        <i class="fi fi-rr-glasses icon icon-sm"></i>
                        Xem gọng kính
                    </a>
                </div>
            </div>

            <aside class="hero-card">
                <h3>Điểm mạnh của đồ án</h3>
                <ul>
                    <li>
                        <i class="fi fi-rr-check icon"></i>
                        Có đủ 3 luồng đơn hàng: available, pre-order và prescription.
                    </li>
                    <li>
                        <i class="fi fi-rr-check icon"></i>
                        Dễ mở rộng cho quản lý đơn, vận hành và admin về sau.
                    </li>
                    <li>
                        <i class="fi fi-rr-check icon"></i>
                        Thiết kế sáng, hiện đại, đồng bộ icon Regular Rounded.
                    </li>
                </ul>
            </aside>
        </section>

        <section>
            <div class="section-head">
                <div>
                    <h2>Danh mục nổi bật</h2>
                    <p>Bắt đầu từ nhóm sản phẩm chính đúng với đề tài của bạn.</p>
                </div>
            </div>

            <div class="category-pills">
                <?php foreach ($categories as $category): ?>
                    <a class="category-pill" href="<?= e(APP_URL) ?>/products.php?category=<?= (int) $category['id'] ?>">
                        <i class="fi fi-rr-apps icon icon-sm"></i>
                        <?= e($category['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section>
            <div class="section-head">
                <div>
                    <h2>Sản phẩm mới</h2>
                    <p>Một số mẫu đang hiển thị từ dữ liệu seed trong MySQL.</p>
                </div>
                <a class="link-inline" href="<?= e(APP_URL) ?>/products.php">Xem tất cả</a>
            </div>

            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <article class="product-card">
                        <a class="product-card-link" href="<?= e(APP_URL) ?>/product-detail.php?id=<?= (int) $product['id'] ?>">
                            <div class="product-thumb">
                                <button class="icon-btn wishlist-btn" type="button" aria-label="Yêu thích">
                                    <i class="fi fi-rr-heart icon"></i>
                                </button>
                                <img
                                    src="<?= e($product['thumbnail'] ?: $placeholderImage) ?>"
                                    alt="<?= e($product['name']) ?>"
                                    onerror="this.onerror=null;this.src='<?= e($placeholderImage) ?>';"
                                >
                            </div>

                            <div class="product-body">
                                <div class="product-meta">
                                    <span class="meta-chip">
                                        <i class="fi fi-rr-apps icon icon-sm"></i>
                                        <?= e($product['category_name']) ?>
                                    </span>
                                    <?php if (!empty($product['shape'])): ?>
                                        <span class="meta-chip"><?= e($product['shape']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <h3><?= e($product['name']) ?></h3>
                                <p><?= e($product['brand'] ?: 'LUMINA') ?></p>

                                <div class="product-flags">
                                    <?php if ((int) $product['is_prescription_supported'] === 1): ?>
                                        <span class="badge badge-primary">
                                            <i class="fi fi-rr-eye icon icon-sm"></i>
                                            Prescription
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="price-row">
                                    <span class="price-current"><?= number_format((float) $product['default_price'], 0, ',', '.') ?>₫</span>
                                    <?php if (!empty($product['compare_at_price'])): ?>
                                        <span class="price-old"><?= number_format((float) $product['compare_at_price'], 0, ',', '.') ?>₫</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</main>
<?php require_once BASE_PATH . '/app/views/partials/footer.php'; ?>

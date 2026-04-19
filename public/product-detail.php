<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/helpers/functions.php';

$db = Database::connect();
$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($productId <= 0) {
    http_response_code(404);
    exit('Sản phẩm không hợp lệ.');
}

$stmt = $db->prepare(
    "SELECT p.id, p.name, p.brand, p.default_price, p.thumbnail, p.description, p.frame_type,
            p.target_gender, c.name AS category_name
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.id = :id AND p.status = 'active'
     LIMIT 1"
);
$stmt->execute(['id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    exit('Không tìm thấy sản phẩm.');
}

$relatedStmt = $db->prepare(
    "SELECT p.id, p.name, p.brand, p.default_price, p.thumbnail
     FROM products p
     WHERE p.status = 'active' AND p.id != :id
     ORDER BY p.id DESC
     LIMIT 4"
);
$relatedStmt->execute(['id' => $productId]);
$relatedProducts = $relatedStmt->fetchAll();

$pageTitle = $product['name'];
$pageDescription = $product['name'] . ' - ' . APP_NAME;
$headerKeyword = '';
$placeholderImage = APP_URL . '/assets/images/placeholder-glasses.png';

include BASE_PATH . '/app/views/partials/head.php';
include BASE_PATH . '/app/views/partials/header.php';
?>

<main class="page-section">
    <div class="container">
        <div class="breadcrumb-row">
            <a href="<?= e(APP_URL) ?>/products.php" class="back-link">
                <i class="fi fi-rr-angle-left icon icon-sm"></i>
                Quay lại danh sách sản phẩm
            </a>
        </div>

        <section class="product-detail-layout">
            <div class="product-detail-image-card">
                <img
                    src="<?= e($product['thumbnail'] ?: $placeholderImage) ?>"
                    alt="<?= e($product['name']) ?>"
                    onerror="this.onerror=null;this.src='<?= e($placeholderImage) ?>';"
                >
            </div>

            <div class="product-detail-info-card">
                <div class="product-detail-meta">
                    <span class="pill"><?= e($product['category_name'] ?: 'Mắt kính') ?></span>
                    <?php if (!empty($product['target_gender'])): ?>
                        <span class="pill muted"><?= e($product['target_gender']) ?></span>
                    <?php endif; ?>
                </div>

                <h1 class="product-detail-title"><?= e($product['name']) ?></h1>
                <p class="product-detail-brand"><?= e($product['brand'] ?: 'LUMINA') ?></p>
                <p class="product-detail-price"><?= format_price($product['default_price']) ?></p>

                <div class="product-detail-feature-list">
                    <div class="detail-row">
                        <span class="label">Loại gọng</span>
                        <span class="value"><?= e($product['frame_type'] ?: 'Đang cập nhật') ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Danh mục</span>
                        <span class="value"><?= e($product['category_name'] ?: 'Mắt kính') ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Thương hiệu</span>
                        <span class="value"><?= e($product['brand'] ?: 'LUMINA') ?></span>
                    </div>
                </div>

                <div class="product-detail-description">
                    <h3>Mô tả sản phẩm</h3>
                    <p><?= nl2br(e($product['description'] ?: 'Sản phẩm mắt kính thời trang với thiết kế hiện đại, phù hợp sử dụng hằng ngày.')) ?></p>
                </div>

                <form action="<?= e(APP_URL) ?>/add-to-cart.php" method="post" class="add-cart-form">
                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">

                    <div class="quantity-group">
                        <label for="quantity">Số lượng</label>
                        <input type="number" id="quantity" name="quantity" min="1" max="10" value="1">
                    </div>

                    <div class="detail-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fi fi-rr-shopping-bag icon icon-sm"></i>
                            Thêm vào giỏ hàng
                        </button>
                        <a href="<?= e(APP_URL) ?>/cart.php" class="btn btn-secondary">Xem giỏ hàng</a>
                    </div>
                </form>
            </div>
        </section>

        <?php if (!empty($relatedProducts)): ?>
            <section class="related-section">
                <div class="section-heading-row">
                    <h2>Sản phẩm liên quan</h2>
                    <a href="<?= e(APP_URL) ?>/products.php">Xem thêm</a>
                </div>

                <div class="product-grid">
                    <?php foreach ($relatedProducts as $item): ?>
                        <article class="product-card">
                            <a class="product-card-link" href="<?= e(APP_URL) ?>/product-detail.php?id=<?= (int) $item['id'] ?>">
                                <div class="product-thumb">
                                    <img
                                        src="<?= e($item['thumbnail'] ?: $placeholderImage) ?>"
                                        alt="<?= e($item['name']) ?>"
                                        onerror="this.onerror=null;this.src='<?= e($placeholderImage) ?>';"
                                    >
                                </div>
                                <div class="product-info">
                                    <h3><?= e($item['name']) ?></h3>
                                    <p class="product-brand"><?= e($item['brand'] ?: 'LUMINA') ?></p>
                                    <p class="product-price"><?= format_price($item['default_price']) ?></p>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</main>

<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>

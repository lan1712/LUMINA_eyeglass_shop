<?php

require_once dirname(__DIR__) . '/app/config/config.php';
require_once BASE_PATH . '/app/helpers/functions.php';

$db = Database::connect();
$keyword = trim($_GET['keyword'] ?? '');
$categoryId = (int) ($_GET['category'] ?? 0);
$type = trim($_GET['type'] ?? '');

$categoriesStmt = $db->query(
    "SELECT id, name, slug, category_type
     FROM categories
     WHERE is_active = 1
     ORDER BY sort_order ASC, name ASC"
);
$categories = $categoriesStmt->fetchAll();

$sql = "SELECT p.id, p.name, p.slug, p.brand, p.default_price, p.compare_at_price, p.thumbnail,
               p.is_prescription_supported, p.shape, p.material,
               c.id AS category_id, c.name AS category_name, c.category_type
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE p.status = 'active'";
$params = [];

if ($keyword !== '') {
    $sql .= " AND (p.name LIKE :keyword OR p.brand LIKE :keyword OR c.name LIKE :keyword OR p.shape LIKE :keyword)";
    $params['keyword'] = '%' . $keyword . '%';
}

if ($categoryId > 0) {
    $sql .= ' AND c.id = :category_id';
    $params['category_id'] = $categoryId;
}

if ($type !== '') {
    $sql .= ' AND c.category_type = :category_type';
    $params['category_type'] = $type;
}

$sql .= ' ORDER BY p.id DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$pageTitle = 'Danh sách sản phẩm - ' . APP_NAME;
$pageDescription = 'Tìm kiếm và lọc sản phẩm kính trong hệ thống LUMINA.';
$headerKeyword = $keyword;
$placeholderImage = APP_URL . '/assets/images/placeholder-glasses.png';

require_once BASE_PATH . '/app/views/partials/head.php';
require_once BASE_PATH . '/app/views/partials/header.php';
?>
<main class="page-section">
    <div class="container">
        <div class="section-head">
            <div>
                <h1>Danh sách sản phẩm</h1>
                <p>Tìm nhanh theo tên, hãng, danh mục hoặc loại sản phẩm.</p>
            </div>
            <a class="link-inline" href="<?= e(APP_URL) ?>/index.php">Về trang chủ</a>
        </div>

        <section class="search-toolbar">
            <form class="search-filters" method="GET">
                <input
                    type="text"
                    name="keyword"
                    placeholder="Ví dụ: Oval, chống ánh sáng xanh, LUMINA..."
                    value="<?= e($keyword) ?>"
                >

                <select name="category">
                    <option value="0">Tất cả danh mục</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>>
                            <?= e($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit">
                    <i class="fi fi-rr-search icon icon-sm"></i>
                    Tìm kiếm
                </button>
            </form>

            <div class="stats-row">
                <div class="stat-card">
                    <strong><?= count($products) ?></strong>
                    <span>Kết quả hiển thị</span>
                </div>
                <div class="stat-card">
                    <strong><?= count($categories) ?></strong>
                    <span>Danh mục đang có</span>
                </div>
            </div>
        </section>

        <?php if (empty($products)): ?>
            <div class="empty-state">
                <p>Chưa tìm thấy sản phẩm phù hợp. Bạn thử đổi từ khóa hoặc bỏ bớt bộ lọc nhé.</p>
            </div>
        <?php else: ?>
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
                                    <?php if (!empty($product['material'])): ?>
                                        <span class="meta-chip"><?= e($product['material']) ?></span>
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

                                    <?php if (!empty($product['shape'])): ?>
                                        <span class="badge">
                                            <i class="fi fi-rr-glasses icon icon-sm"></i>
                                            <?= e($product['shape']) ?>
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

                        <div class="product-body">
                            <div class="card-actions">
                                <a class="btn-outline" href="<?= e(APP_URL) ?>/product-detail.php?id=<?= (int) $product['id'] ?>">
                                    <i class="fi fi-rr-eye icon icon-sm"></i>
                                    Xem chi tiết
                                </a>
                                <form method="post" action="<?= e(APP_URL) ?>/add-to-cart.php" style="flex:1;">
                                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn-primary" style="width:100%;">
                                        <i class="fi fi-rr-shopping-bag icon icon-sm"></i>
                                        Thêm giỏ hàng
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php require_once BASE_PATH . '/app/views/partials/footer.php'; ?>

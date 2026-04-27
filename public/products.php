<?php
require_once dirname(__DIR__) . '/app/config/config.php';
require_once BASE_PATH . '/app/helpers/functions.php';

$db = Database::connect();
$placeholderImage = APP_URL . '/assets/images/placeholder-glasses.png';

function lumina_catalog_img(?string $url, string $placeholder): string
{
    $url = trim((string) $url);
    if ($url === '') return $placeholder;
    if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '/')) return $url;
    return APP_URL . '/' . ltrim($url, '/');
}

$keyword = trim((string) ($_GET['keyword'] ?? ''));
$categoryParam = trim((string) ($_GET['category'] ?? ''));

$parentCategoriesStmt = $db->query(
    "SELECT c.id, c.name, c.slug, c.description, COUNT(p.id) AS products_count
     FROM categories c
     LEFT JOIN categories child ON child.parent_id = c.id AND child.is_active = 1
     LEFT JOIN products p ON p.status = 'active' AND (p.category_id = c.id OR p.category_id = child.id)
     WHERE c.is_active = 1 AND c.parent_id IS NULL
     GROUP BY c.id
     ORDER BY c.sort_order ASC, c.id ASC"
);
$parentCategories = $parentCategoriesStmt->fetchAll();

$category = null;
$categoryIds = [];
if ($categoryParam !== '') {
    if (ctype_digit($categoryParam)) {
        $catStmt = $db->prepare("SELECT * FROM categories WHERE id = :id AND is_active = 1 LIMIT 1");
        $catStmt->execute(['id' => (int) $categoryParam]);
    } else {
        $catStmt = $db->prepare("SELECT * FROM categories WHERE slug = :slug AND is_active = 1 LIMIT 1");
        $catStmt->execute(['slug' => $categoryParam]);
    }
    $category = $catStmt->fetch() ?: null;
    if ($category) {
        $categoryIds[] = (int) $category['id'];
        $childStmt = $db->prepare("SELECT id FROM categories WHERE parent_id = :parent_id AND is_active = 1");
        $childStmt->execute(['parent_id' => (int) $category['id']]);
        foreach ($childStmt->fetchAll() as $child) {
            $categoryIds[] = (int) $child['id'];
        }
    }
}

$sql = "SELECT p.id, p.name, p.slug, p.brand, p.default_price, p.compare_at_price, p.thumbnail,
               p.short_description, p.shape, p.material, c.name AS category_name, c.slug AS category_slug
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE p.status = 'active'";
$params = [];

if ($keyword !== '') {
    $sql .= " AND (p.name LIKE :keyword OR p.brand LIKE :keyword OR p.short_description LIKE :keyword OR c.name LIKE :keyword)";
    $params['keyword'] = '%' . $keyword . '%';
}
if ($categoryIds !== []) {
    $placeholders = [];
    foreach ($categoryIds as $idx => $catId) {
        $key = 'cat' . $idx;
        $placeholders[] = ':' . $key;
        $params[$key] = $catId;
    }
    $sql .= " AND p.category_id IN (" . implode(',', $placeholders) . ")";
}
$sql .= " ORDER BY p.id DESC LIMIT 120";
$productStmt = $db->prepare($sql);
$productStmt->execute($params);
$products = $productStmt->fetchAll();

$pageTitle = ($category['name'] ?? 'Bộ sưu tập') . ' - ' . APP_NAME;
$pageDescription = 'Danh sách sản phẩm kính mắt LUMINA.';
require_once BASE_PATH . '/app/views/partials/head.php';
require_once BASE_PATH . '/app/views/partials/header.php';
?>
<section class="catalog-hero">
  <div class="catalog-hero-container">
    <h1 class="catalog-hero-title"><?= e($category['name'] ?? 'Bộ Sưu Tập Kính Mắt') ?></h1>
    <p class="catalog-hero-description">
      <?= e($category['description'] ?? 'Khám phá bộ sưu tập kính mắt hoàn chỉnh của LUMINA: gọng kính, kính mát và tròng kính chuyên biệt cho nhiều nhu cầu.') ?>
    </p>
  </div>
</section>

<section class="category-cards-section">
  <div class="catalog-container">
    <h2 class="section-heading">Khám phá theo danh mục</h2>
    <div class="category-cards-grid">
      <?php foreach ($parentCategories as $parent): ?>
        <a class="category-card source-card-link" href="<?= e(APP_URL) ?>/products.php?category=<?= e($parent['slug']) ?>" style="background: linear-gradient(135deg, #1f2937 0%, #525252 100%)">
          <h3 class="category-card-name"><?= e($parent['name']) ?></h3>
          <p class="category-card-description"><?= e($parent['description'] ?: 'Lọc nhanh sản phẩm theo danh mục chính.') ?></p>
          <div class="category-card-cta">Xem <?= (int) $parent['products_count'] ?> sản phẩm
            <svg class="category-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5l7 7-7 7"></path></svg>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="product-catalog" id="products">
  <div class="catalog-container">
    <div class="catalog-header">
      <div class="catalog-title-section">
        <h2 class="catalog-title">Sản phẩm</h2>
        <p class="catalog-description">Tìm theo tên, thương hiệu hoặc chọn danh mục từ thanh điều hướng.</p>
      </div>
      <form class="newsletter-form" action="<?= e(APP_URL) ?>/products.php" method="get" style="border-color:#171717;max-width:680px;margin:0;">
        <?php if ($categoryParam !== ''): ?><input type="hidden" name="category" value="<?= e($categoryParam) ?>"><?php endif; ?>
        <input class="newsletter-input" style="color:#171717" name="keyword" value="<?= e($keyword) ?>" placeholder="Tìm gọng kính, kính mát, tròng kính...">
        <button class="newsletter-btn" style="color:#171717" type="submit">Tìm</button>
      </form>
    </div>

    <div class="products-grid">
      <?php foreach ($products as $product): ?>
        <div class="product-card">
          <a href="<?= e(APP_URL) ?>/product-detail.php?id=<?= (int) $product['id'] ?>">
            <div class="product-image-wrapper">
              <?php if (!empty($product['compare_at_price']) && (float) $product['compare_at_price'] > (float) $product['default_price']): ?>
                <div class="product-badge">Sale</div>
              <?php endif; ?>
              <img src="<?= e(lumina_catalog_img($product['thumbnail'], $placeholderImage)) ?>" alt="<?= e($product['name']) ?>" class="product-image" loading="lazy">
            </div>
            <div class="product-info source-product-info-stacked">
              <div class="product-category"><?= e($product['category_name'] ?: 'LUMINA') ?></div>
              <div class="product-name"><?= e($product['name']) ?></div>
              <div class="source-price-line">
                <div class="product-price"><?= e(format_price($product['default_price'])) ?></div>
                <?php if (!empty($product['compare_at_price']) && (float) $product['compare_at_price'] > (float) $product['default_price']): ?>
                  <span class="source-old-price"><?= e(format_price($product['compare_at_price'])) ?></span>
                <?php endif; ?>
              </div>
            </div>
          </a>
          <div class="source-product-actions">
            <a class="source-action-btn outline" href="<?= e(APP_URL) ?>/product-detail.php?id=<?= (int) $product['id'] ?>">Chi tiết</a>
            <form method="post" action="<?= e(APP_URL) ?>/add-to-cart.php">
              <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
              <input type="hidden" name="quantity" value="1">
              <button class="source-action-btn" type="submit">Thêm giỏ</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($products === []): ?>
      <div class="empty-state"><p>Không có sản phẩm phù hợp.</p></div>
    <?php endif; ?>
  </div>
</section>

<section class="cta-section">
  <div class="catalog-container">
    <h2 class="cta-title">Không tìm thấy sản phẩm mong muốn?</h2>
    <p class="cta-description">Liên hệ LUMINA để được tư vấn mẫu kính và tròng kính phù hợp.</p>
    <a class="cta-btn" href="<?= e(APP_URL) ?>/products.php">Xem tất cả sản phẩm</a>
  </div>
</section>

<?php require_once BASE_PATH . '/app/views/partials/footer.php'; ?>

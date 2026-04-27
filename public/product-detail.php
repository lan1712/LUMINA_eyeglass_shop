<?php
require_once dirname(__DIR__) . '/app/config/config.php';
require_once BASE_PATH . '/app/helpers/functions.php';

$db = Database::connect();
$placeholderImage = APP_URL . '/assets/images/placeholder-glasses.png';

function lumina_detail_img(?string $url, string $placeholder): string
{
    $url = trim((string) $url);
    if ($url === '') return $placeholder;
    if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '/')) return $url;
    return APP_URL . '/' . ltrim($url, '/');
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$slug = trim((string) ($_GET['slug'] ?? ''));

if ($id > 0) {
    $stmt = $db->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.id = :id AND p.status = 'active' LIMIT 1");
    $stmt->execute(['id' => $id]);
} else {
    $stmt = $db->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.slug = :slug AND p.status = 'active' LIMIT 1");
    $stmt->execute(['slug' => $slug]);
}
$product = $stmt->fetch();
if (!$product) {
    http_response_code(404);
    $pageTitle = 'Không tìm thấy sản phẩm';
    require_once BASE_PATH . '/app/views/partials/head.php';
    require_once BASE_PATH . '/app/views/partials/header.php';
    echo '<section class="empty-state"><p>Không tìm thấy sản phẩm.</p></section>';
    require_once BASE_PATH . '/app/views/partials/footer.php';
    exit;
}

$relatedStmt = $db->prepare("SELECT p.id, p.name, p.default_price, p.thumbnail, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.status = 'active' AND p.category_id = :category_id AND p.id <> :id ORDER BY p.id DESC LIMIT 4");
$relatedStmt->execute(['category_id' => $product['category_id'], 'id' => $product['id']]);
$related = $relatedStmt->fetchAll();

$pageTitle = $product['name'] . ' - ' . APP_NAME;
$pageDescription = $product['short_description'] ?: 'Chi tiết sản phẩm LUMINA.';
require_once BASE_PATH . '/app/views/partials/head.php';
require_once BASE_PATH . '/app/views/partials/header.php';
?>
<section class="source-detail">
  <div class="source-detail-image">
    <img src="<?= e(lumina_detail_img($product['thumbnail'], $placeholderImage)) ?>" alt="<?= e($product['name']) ?>">
  </div>
  <div class="source-detail-info">
    <div class="product-category"><?= e($product['category_name'] ?: 'LUMINA') ?></div>
    <h1 class="source-detail-title"><?= e($product['name']) ?></h1>
    <div class="source-detail-price"><?= e(format_price($product['default_price'])) ?></div>
    <?php if (!empty($product['compare_at_price']) && (float) $product['compare_at_price'] > (float) $product['default_price']): ?>
      <div class="source-old-price"><?= e(format_price($product['compare_at_price'])) ?></div>
    <?php endif; ?>
    <div class="source-detail-meta">
      <?php foreach ([$product['brand'] ?? null, $product['shape'] ?? null, $product['material'] ?? null, $product['target_gender'] ?? null] as $meta): ?>
        <?php if ($meta): ?><span class="source-meta-pill"><?= e($meta) ?></span><?php endif; ?>
      <?php endforeach; ?>
    </div>
    <p class="source-detail-desc"><?= e($product['description'] ?: ($product['short_description'] ?: 'Sản phẩm kính mắt LUMINA với thiết kế hiện đại, phù hợp nhiều phong cách sử dụng.')) ?></p>
    <form class="source-add-cart" method="post" action="<?= e(APP_URL) ?>/add-to-cart.php">
      <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
      <input type="number" name="quantity" value="1" min="1" max="10">
      <button class="source-action-btn" type="submit">Thêm vào giỏ</button>
      <a class="source-action-btn outline" href="<?= e(APP_URL) ?>/products.php">Tiếp tục xem</a>
    </form>
  </div>
</section>

<?php if ($related): ?>
<section class="products">
  <div class="products-container">
    <div class="products-header">
      <h2 class="products-title">Sản phẩm liên quan</h2>
      <a class="products-view-all" href="<?= e(APP_URL) ?>/products.php?category=<?= e($product['category_slug']) ?>">Xem thêm</a>
    </div>
    <div class="products-grid">
      <?php foreach ($related as $item): ?>
        <a class="product-card" href="<?= e(APP_URL) ?>/product-detail.php?id=<?= (int) $item['id'] ?>">
          <div class="product-image-wrapper"><img class="product-image" src="<?= e(lumina_detail_img($item['thumbnail'], $placeholderImage)) ?>" alt="<?= e($item['name']) ?>"></div>
          <div class="product-info source-product-info-stacked">
            <div class="product-category"><?= e($item['category_name']) ?></div>
            <div class="product-name"><?= e($item['name']) ?></div>
            <div class="product-price"><?= e(format_price($item['default_price'])) ?></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require_once BASE_PATH . '/app/views/partials/footer.php'; ?>

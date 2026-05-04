<?php
require_once dirname(__DIR__) . '/app/config/config.php';
require_once BASE_PATH . '/app/helpers/functions.php';

$db = Database::connect();

if (!function_exists('landing05_image_url')) {
    function landing05_image_url(?string $url): string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return APP_URL . '/assets/images/placeholder-glasses.svg';
        }
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '/')) {
            return $url;
        }
        return APP_URL . '/' . ltrim($url, '/');
    }
}

if (!function_exists('landing05_category_url')) {
    function landing05_category_url(array $category): string
    {
        return APP_URL . '/products.php?category=' . urlencode((string) ($category['slug'] ?? $category['id']));
    }
}

if (!function_exists('landing05_cart_count')) {
    function landing05_cart_count(): int
    {
        if (function_exists('cart_count')) {
            return (int) cart_count();
        }
        $count = 0;
        foreach (($_SESSION['cart'] ?? []) as $item) {
            $count += (int) ($item['quantity'] ?? $item ?? 0);
        }
        return $count;
    }
}

$productStmt = $db->query(
    "SELECT p.id, p.name, p.slug, p.brand, p.default_price, p.compare_at_price, p.thumbnail,
            p.short_description, p.frame_type, p.target_gender, p.shape, p.material, p.status,
            c.name AS category_name, c.slug AS category_slug
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.status = 'active'
     ORDER BY p.id DESC
     LIMIT 12"
);
$products = $productStmt->fetchAll();
$featuredProducts = array_slice($products, 0, 4);
$heroProduct = $products[0] ?? null;
$heroImage = landing05_image_url($heroProduct['thumbnail'] ?? '');

$categoryStmt = $db->query(
    "SELECT c.id, c.name, c.slug, c.description, COUNT(DISTINCT p.id) AS products_count,
            MIN(p.thumbnail) AS sample_thumbnail
     FROM categories c
     LEFT JOIN categories child ON child.parent_id = c.id AND child.is_active = 1
     LEFT JOIN products p ON p.status = 'active' AND (p.category_id = c.id OR p.category_id = child.id)
     WHERE c.is_active = 1 AND c.parent_id IS NULL
     GROUP BY c.id, c.name, c.slug, c.description, c.sort_order
     ORDER BY c.sort_order ASC, c.id ASC
     LIMIT 3"
);
$categories = $categoryStmt->fetchAll();

$cartCount = landing05_cart_count();
$pageTitle = APP_NAME . ' - Tầm nhìn hoàn hảo, phong cách tối giản';
$pageDescription = 'Landing page LUMINA theo layout tối giản: hero, danh mục, sản phẩm nổi bật, prescription workflow và đánh giá khách hàng.';
$pageStyles = [APP_URL . '/assets/css/landing-page.css?v=' . (@filemtime(PUBLIC_PATH . '/assets/css/landing-page.css') ?: time())];

require_once BASE_PATH . '/app/views/partials/head.php';
?>

<?php if ($message = get_flash('success')): ?>
    <div class="lp05-flash"><div><?= e($message) ?></div></div>
<?php endif; ?>

<?php require_once BASE_PATH . '/app/views/partials/header.php'; ?>

<div class="lp05-page">
    <main>
        <section class="lp05-hero">
            <div class="lp05-container lp05-hero-grid">
                <div class="lp05-hero-copy">
                    <span class="lp05-badge"><i></i>Công nghệ mới</span>
                    <h1><strong>Tầm nhìn hoàn hảo,</strong><span>phong cách tối giản.</span></h1>
                    <p>Trải nghiệm thử kính 3D ảo trực tiếp trên khuôn mặt bạn và đặt làm tròng kính theo đơn chuẩn xác chỉ với vài thao tác đơn giản.</p>
                    <div class="lp05-hero-buttons">
                        <a class="lp05-btn lp05-btn-primary" href="<?= e(APP_URL) ?>/products.php"><i class="fi fi-rr-eye"></i> Thử kính 3D Virtual</a>
                        <a class="lp05-btn lp05-btn-light" href="<?= e(APP_URL) ?>/checkout.php"><i class="fi fi-rr-document"></i> Tải đơn kính lên</a>
                    </div>
                    <div class="lp05-rating-row">
                        <div class="lp05-avatars">
                            <span>A</span><span>L</span><span>M</span><strong>+2k</strong>
                        </div>
                        <div class="lp05-stars" aria-label="Đánh giá khách hàng 4.9 trên 5">
                            <span>★★★★★</span>
                            <p><strong>4.9/5</strong> từ khách hàng</p>
                        </div>
                    </div>
                </div>

                <div class="lp05-hero-visual">
                    <div class="lp05-hero-glow"></div>
                    <img src="<?= e($heroImage) ?>" alt="<?= e($heroProduct['name'] ?? 'Gọng kính LUMINA') ?>">
                    <div class="lp05-floating-spec">
                        <span><i class="fi fi-rr-check"></i></span>
                        <div>
                            <small>Tròng kính</small>
                            <strong>Chống lóa UV400</strong>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="lp05-section lp05-category-section">
            <div class="lp05-container">
                <div class="lp05-section-head lp05-section-head-row">
                    <div>
                        <h2>Mua sắm theo danh mục</h2>
                        <p>Khám phá các bộ sưu tập phù hợp với phong cách của bạn.</p>
                    </div>
                    <a class="lp05-link-arrow" href="<?= e(APP_URL) ?>/products.php">Xem tất cả <i class="fi fi-rr-arrow-small-right"></i></a>
                </div>

                <div class="lp05-category-grid">
                    <?php
                    $fallbackCategories = [
                        ['name' => 'Gọng Titan', 'slug' => 'gong-kinh', 'description' => 'Siêu nhẹ & Bền bỉ', 'sample_thumbnail' => $heroProduct['thumbnail'] ?? ''],
                        ['name' => 'Kính Râm', 'slug' => 'kinh-mat', 'description' => 'Bảo vệ & Thời trang', 'sample_thumbnail' => $products[1]['thumbnail'] ?? ''],
                        ['name' => 'Gọng Nhựa', 'slug' => 'gong-kinh-nhua', 'description' => 'Màu sắc & Trẻ trung', 'sample_thumbnail' => $products[2]['thumbnail'] ?? ''],
                    ];
                    $displayCategories = $categories ?: $fallbackCategories;
                    foreach (array_slice($displayCategories, 0, 3) as $index => $category):
                        $thumb = landing05_image_url($category['sample_thumbnail'] ?? $products[$index]['thumbnail'] ?? '');
                    ?>
                        <a class="lp05-category-card" href="<?= e(isset($category['id']) ? landing05_category_url($category) : APP_URL . '/products.php?category=' . urlencode((string) $category['slug'])) ?>">
                            <img src="<?= e($thumb) ?>" alt="<?= e($category['name']) ?>">
                            <span><i class="fi fi-rr-arrow-up-right"></i></span>
                            <div>
                                <h3><?= e($category['name']) ?></h3>
                                <p><?= e($category['description'] ?: 'Khám phá bộ sưu tập mới.') ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="lp05-section lp05-featured-section">
            <div class="lp05-container">
                <div class="lp05-section-head lp05-featured-head">
                    <h2>Sản phẩm nổi bật</h2>
                    <div class="lp05-filter-pills" aria-label="Bộ lọc nhanh">
                        <a href="<?= e(APP_URL) ?>/products.php">Tất cả</a>
                        <a class="is-active" href="<?= e(APP_URL) ?>/products.php?category=gong-kinh">Gọng nam</a>
                        <a href="<?= e(APP_URL) ?>/products.php?category=kinh-mat">Gọng nữ</a>
                        <a href="<?= e(APP_URL) ?>/products.php?target_gender=unisex">Unisex</a>
                        <a href="<?= e(APP_URL) ?>/products.php"><i class="fi fi-rr-filter"></i> Lọc</a>
                    </div>
                </div>

                <div class="lp05-product-grid">
                    <?php foreach ($featuredProducts as $index => $product): ?>
                        <article class="lp05-product-card">
                            <a href="<?= e(APP_URL) ?>/product-detail.php?id=<?= (int) $product['id'] ?>" class="lp05-product-media">
                                <span class="lp05-product-badge lp05-product-badge-<?= (int) $index % 3 ?>">
                                    <?= $index === 1 ? 'Pre-order' : ($index === 3 ? 'Mới' : 'Có sẵn') ?>
                                </span>
                                <button type="button" aria-label="Yêu thích"><i class="fi fi-rr-heart"></i></button>
                                <img src="<?= e(landing05_image_url($product['thumbnail'] ?? '')) ?>" alt="<?= e($product['name']) ?>">
                                <span class="lp05-add-hover"><i class="fi fi-rr-shopping-cart-add"></i> Thêm vào giỏ</span>
                            </a>
                            <div class="lp05-product-info">
                                <div>
                                    <h3><a href="<?= e(APP_URL) ?>/product-detail.php?id=<?= (int) $product['id'] ?>"><?= e($product['name']) ?></a></h3>
                                    <p><?= e(($product['material'] ?: 'LUMINA') . ' • ' . ($product['shape'] ?: 'Size M')) ?></p>
                                </div>
                                <strong><?= e(format_price($product['default_price'])) ?></strong>
                            </div>
                            <div class="lp05-color-row">
                                <span class="black"></span><span class="silver"></span><span class="brown"></span><em>+2 màu</em>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="lp05-center-action">
                    <a class="lp05-btn lp05-btn-outline" href="<?= e(APP_URL) ?>/products.php">Xem tất cả sản phẩm <i class="fi fi-rr-arrow-small-right"></i></a>
                </div>
            </div>
        </section>

        <section class="lp05-section lp05-workflow-section">
            <div class="lp05-container">
                <div class="lp05-workflow-head">
                    <span>Dịch vụ tròng kính</span>
                    <h2>Quy trình đặt kính Prescription</h2>
                    <p>Sở hữu chiếc kính hoàn hảo đúng độ của bạn chỉ với 4 bước đơn giản trực tuyến, không cần đến cửa hàng.</p>
                </div>

                <div class="lp05-workflow-line" aria-hidden="true"></div>
                <div class="lp05-workflow-grid">
                    <?php
                    $steps = [
                        ['icon' => 'fi fi-rr-glasses', 'title' => 'Chọn gọng kính', 'text' => 'Tìm gọng kính yêu thích và thử trực tiếp bằng tính năng 3D Virtual Try-on.'],
                        ['icon' => 'fi fi-rr-document', 'title' => 'Gửi đơn kính', 'text' => 'Chụp ảnh đơn kính hoặc nhập thông số khúc xạ của bạn vào hệ thống.'],
                        ['icon' => 'fi fi-rr-settings', 'title' => 'Gia công tròng', 'text' => 'Chuyên gia cắt và lắp ráp tròng kính theo đúng chuẩn y tế.'],
                        ['icon' => 'fi fi-rr-box-open', 'title' => 'Nhận hàng', 'text' => 'Kính được đóng gói cẩn thận và giao tận tay trong 2–3 ngày làm việc.'],
                    ];
                    foreach ($steps as $index => $step):
                    ?>
                        <article class="lp05-step-card">
                            <div class="lp05-step-icon"><i class="<?= e($step['icon']) ?>"></i><b><?= (int) $index + 1 ?></b></div>
                            <h3><?= e($step['title']) ?></h3>
                            <p><?= e($step['text']) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="lp05-center-action">
                    <a class="lp05-btn lp05-btn-primary" href="<?= e(APP_URL) ?>/checkout.php">Bắt đầu làm kính ngay</a>
                </div>
            </div>
        </section>

        <section class="lp05-section lp05-testimonial-section">
            <div class="lp05-container lp05-testimonial-grid">
                <div class="lp05-testimonial-copy">
                    <h2>Khách hàng nói gì về chúng tôi...</h2>
                    <p>Hơn 10,000 khách hàng đã tin tưởng và sử dụng dịch vụ của Lumina.</p>
                    <div class="lp05-slider-controls"><button type="button"><i class="fi fi-rr-angle-left"></i></button><button type="button" class="is-active"><i class="fi fi-rr-angle-right"></i></button></div>
                </div>
                <article class="lp05-review-card">
                    <div class="lp05-review-photo">MT</div>
                    <div class="lp05-review-body">
                        <span>★★★★★</span>
                        <h3>Trải nghiệm tuyệt vời!</h3>
                        <p>“Tính năng thử kính 3D thực sự rất chính xác. Tôi đã chọn được chiếc gọng Titan vừa vặn hoàn hảo với khuôn mặt. Quá trình gửi đơn kính cũng rất dễ dàng và tôi nhận được kính chỉ sau 2 ngày.”</p>
                        <strong>Minh Tuấn</strong>
                        <small>Đã mua: <?= e($heroProduct['name'] ?? 'Gọng Classic Round Titan') ?></small>
                    </div>
                </article>
            </div>
        </section>
    </main>

    <footer class="lp05-footer">
        <div class="lp05-container lp05-footer-grid">
            <div class="lp05-footer-brand">
                <a class="lp05-brand lp05-brand-footer" href="<?= e(APP_URL) ?>/"><span class="lp05-brand-icon"><i class="fi fi-rr-glasses"></i></span><strong>LUMINA</strong></a>
                <p>Hệ thống bán lẻ kính mắt trực tuyến hàng đầu, mang đến trải nghiệm mua sắm hiện đại, tiện lợi với công nghệ thử kính 3D và dịch vụ cắt tròng chuyên nghiệp.</p>
                <div class="lp05-socials"><a href="#">f</a><a href="#">ig</a><a href="#">yt</a></div>
            </div>
            <div>
                <h4>Sản phẩm</h4>
                <a href="<?= e(APP_URL) ?>/products.php?category=gong-kinh">Gọng nam</a>
                <a href="<?= e(APP_URL) ?>/products.php?category=gong-kinh">Gọng nữ</a>
                <a href="<?= e(APP_URL) ?>/products.php?category=kinh-mat">Kính râm</a>
                <a href="<?= e(APP_URL) ?>/products.php?category=trong-kinh">Tròng kính</a>
                <a href="<?= e(APP_URL) ?>/products.php">Phụ kiện</a>
            </div>
            <div>
                <h4>Hỗ trợ</h4>
                <a href="<?= e(APP_URL) ?>/contact.php">Hướng dẫn đo độ</a>
                <a href="<?= e(APP_URL) ?>/contact.php">Chính sách bảo hành</a>
                <a href="<?= e(APP_URL) ?>/contact.php">Giao hàng & Đổi trả</a>
                <a href="<?= e(APP_URL) ?>/contact.php">Câu hỏi thường gặp</a>
                <a href="<?= e(APP_URL) ?>/contact.php">Liên hệ</a>
            </div>
            <div>
                <h4>Đăng ký nhận tin</h4>
                <p>Nhận ưu đãi 10% cho đơn hàng đầu tiên của bạn.</p>
                <form class="lp05-footer-form" action="#" method="post">
                    <input type="email" placeholder="Email của bạn" aria-label="Email của bạn">
                    <button type="submit"><i class="fi fi-rr-paper-plane"></i></button>
                </form>
            </div>
            <div class="lp05-footer-bottom">
                <span>© <?= date('Y') ?> Lumina Eyewear. All rights reserved.</span>
                <div><a href="#">Privacy</a><a href="#">Terms</a><a href="#">Social</a></div>
            </div>
        </div>
    </footer>
</div>
</body>
</html>

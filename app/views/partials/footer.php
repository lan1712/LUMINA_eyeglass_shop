<link rel='stylesheet' href='https://cdn-uicons.flaticon.com/4.0.0/uicons-brands/css/uicons-brands.css'>
<?php
/**
 * LUMINA Footer V2
 * Footer mới dùng chung cho storefront. Thay thế footer cũ.
 */

$currentYear = date('Y');

$isLoggedInFooter = function_exists('is_logged_in') ? is_logged_in() : !empty($_SESSION['user']);
$accountFooterHref = $isLoggedInFooter ? APP_URL . '/profile.php' : APP_URL . '/login.php';
$accountFooterLabel = $isLoggedInFooter ? 'Tài khoản của tôi' : 'Đăng nhập';
?>

<footer class="lumina-footer-v2">
    <section class="lumina-footer-v2__cta">
        <div class="lumina-footer-v2__cta-inner">
            <div>
                <span class="lumina-footer-v2__eyebrow">LUMINA EYEWEAR</span>
                <h2>Chọn kính rõ nhu cầu, đặt hàng dễ theo dõi.</h2>
                <p>
                    Khám phá gọng kính, kính mát và tròng kính theo danh mục rõ ràng,
                    hỗ trợ các luồng đặt hàng có sẵn, pre-order và prescription.
                </p>
            </div>

            <div class="lumina-footer-v2__cta-actions">
                <a href="<?= e(APP_URL) ?>/products.php" class="lumina-footer-v2__btn lumina-footer-v2__btn--light">
                    Xem sản phẩm
                </a>
                <a href="<?= e(APP_URL) ?>/collections.php" class="lumina-footer-v2__btn lumina-footer-v2__btn--outline">
                    Bộ sưu tập
                </a>
            </div>
        </div>
    </section>

    <div class="lumina-footer-v2__main">
        <div class="lumina-footer-v2__brand">
            <a href="<?= e(APP_URL) ?>/" class="lumina-footer-v2__logo" aria-label="LUMINA home">
                <span><i class="fi fi-rr-glasses"></i></span>
                LUMINA
            </a>
            <p>
                Shop mắt kính trực tuyến tập trung vào trải nghiệm chọn kính rõ ràng,
                giao diện hiện đại và quy trình đặt hàng dễ kiểm soát.
            </p>

            <div class="lumina-footer-v2__socials" aria-label="Social links">
                <a href="#" aria-label="Facebook"><i class="fi fi-brands-facebook"></i></a>
                <a href="#" aria-label="Instagram"><i class="fi fi-brands-instagram"></i></a>
                <a href="#" aria-label="Share"><i class="fi fi-rr-share"></i></a>
            </div>
        </div>

        <div class="lumina-footer-v2__links">
            <div class="lumina-footer-v2__column">
                <h3>Sản phẩm</h3>
                <a href="<?= e(APP_URL) ?>/products.php?category=gong-kinh">Gọng kính</a>
                <a href="<?= e(APP_URL) ?>/products.php?category=kinh-mat">Kính mát</a>
                <a href="<?= e(APP_URL) ?>/products.php?category=trong-kinh">Tròng kính</a>
                <a href="<?= e(APP_URL) ?>/collections.php">Bộ sưu tập</a>
            </div>

            <div class="lumina-footer-v2__column">
                <h3>Khách hàng</h3>
                <a href="<?= e(APP_URL) ?>/products.php">Danh sách sản phẩm</a>
                <a href="<?= e(APP_URL) ?>/cart.php">Giỏ hàng</a>
                <a href="<?= e(APP_URL) ?>/orders.php">Tra cứu đơn hàng</a>
                <a href="<?= e($accountFooterHref) ?>"><?= e($accountFooterLabel) ?></a>
            </div>

            <div class="lumina-footer-v2__column">
                <h3>Hỗ trợ</h3>
                <a href="<?= e(APP_URL) ?>/about.php">Về chúng tôi</a>
                <a href="<?= e(APP_URL) ?>/contact.php">Liên hệ</a>
                <a href="<?= e(APP_URL) ?>/orders.php">Theo dõi đơn</a>
                <a href="<?= e(APP_URL) ?>/products.php?category=trong-kinh">Tư vấn tròng kính</a>
            </div>

            <div class="lumina-footer-v2__newsletter">
                <h3>Nhận tin mới</h3>
                <p>Nhận thông tin về mẫu kính mới, bộ sưu tập và ưu đãi từ LUMINA.</p>

                <form action="<?= e(APP_URL) ?>/products.php" method="get" class="lumina-footer-v2__form">
                    <input type="email" name="email" placeholder="Email của bạn" aria-label="Email của bạn">
                    <button type="submit">Gửi</button>
                </form>

                <div class="lumina-footer-v2__contact">
                    <span><i class="fi fi-rr-phone-call"></i> 0123 456 789</span>
                    <span><i class="fi fi-rr-envelope"></i> support@lumina.local</span>
                    <span><i class="fi fi-rr-marker"></i> TP. Hồ Chí Minh</span>
                </div>
            </div>
        </div>
    </div>

    <div class="lumina-footer-v2__bottom">
        <p>© <?= e((string) $currentYear) ?> LUMINA. All rights reserved.</p>
        <div>
            <a href="<?= e(APP_URL) ?>/about.php">Giới thiệu</a>
            <a href="<?= e(APP_URL) ?>/contact.php">Liên hệ</a>
            <a href="<?= e(APP_URL) ?>/products.php">Sản phẩm</a>
        </div>
    </div>
</footer>
</body>
</html>

<?php
require_once __DIR__ . '/../app/config/config.php';
require_once BASE_PATH . '/app/helpers/functions.php';

$db = Database::connect();
$stats = ['products'=>0,'categories'=>0,'orders'=>0];
try {
    $stats['products'] = (int)$db->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
    $stats['categories'] = (int)$db->query("SELECT COUNT(*) FROM categories WHERE is_active = 1")->fetchColumn();
    $stats['orders'] = (int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
} catch (Throwable $exception) {}

$pageTitle = 'Về chúng tôi - ' . APP_NAME;
$pageDescription = 'Câu chuyện thương hiệu, quy trình tư vấn và cam kết trải nghiệm của LUMINA.';
$pageStyles = [APP_URL . '/assets/css/info-pages.css?v=1.0.0'];
require_once BASE_PATH . '/app/views/partials/head.php';
require_once BASE_PATH . '/app/views/partials/header.php';
?>

<main class="info-page about-page">
    <section class="info-hero about-hero">
        <div class="info-container about-hero__grid">
            <div class="info-hero__content">
                <span class="info-eyebrow">ABOUT LUMINA</span>
                <h1>Chúng tôi giúp việc chọn kính trở nên rõ ràng hơn.</h1>
                <p>LUMINA là shop mắt kính trực tuyến tập trung vào trải nghiệm đơn giản: xem mẫu nhanh, lọc đúng nhu cầu, đặt hàng rõ trạng thái và hỗ trợ đơn prescription khi cần.</p>
                <div class="info-hero__actions"><a href="<?= e(APP_URL) ?>/collections.php" class="info-btn info-btn--primary">Xem bộ sưu tập</a><a href="<?= e(APP_URL) ?>/products.php" class="info-btn info-btn--ghost">Mua sắm ngay</a></div>
            </div>
            <div class="about-hero__panel"><div class="about-hero__panel-inner"><span>Our focus</span><h2>Precision, comfort & everyday style.</h2><p>Giao diện và quy trình đặt hàng được thiết kế để khách hàng hiểu rõ sản phẩm trước khi mua.</p></div></div>
        </div>
    </section>

    <section class="info-section"><div class="info-container"><div class="about-stats-grid">
        <div class="about-stat-card"><strong><?= e((string)$stats['products']) ?>+</strong><span>Sản phẩm active</span></div>
        <div class="about-stat-card"><strong><?= e((string)$stats['categories']) ?>+</strong><span>Danh mục đang vận hành</span></div>
        <div class="about-stat-card"><strong><?= e((string)$stats['orders']) ?>+</strong><span>Đơn hàng trong hệ thống</span></div>
        <div class="about-stat-card"><strong>3</strong><span>Luồng đặt hàng chính</span></div>
    </div></div></section>

    <section class="info-section info-section--soft"><div class="info-container about-story-grid">
        <div class="about-story-card is-image"><div class="about-story-card__visual"><i class="fi fi-rr-glasses"></i></div></div>
        <div class="about-story-card"><span class="info-eyebrow">BRAND STORY</span><h2>Từ nhu cầu chọn kính nhanh đến một hệ thống mua hàng rõ ràng.</h2><p>LUMINA được xây dựng như một website bán mắt kính có đầy đủ luồng khách hàng: xem sản phẩm, thêm giỏ, đặt hàng, theo dõi lịch sử đơn và cho phép admin quản lý catalog.</p><p>Thay vì chỉ hiển thị sản phẩm, LUMINA ưu tiên cấu trúc thông tin dễ hiểu: danh mục cha/con, bộ lọc, trạng thái đơn và nội dung hỗ trợ chọn kính.</p></div>
    </div></section>

    <section class="info-section"><div class="info-container">
        <div class="info-section-head"><span class="info-eyebrow">OUR VALUES</span><h2>Cam kết trải nghiệm</h2><p>Ba nguyên tắc chính để giữ giao diện và vận hành của shop nhất quán.</p></div>
        <div class="about-value-grid">
            <div class="about-value-card"><span><i class="fi fi-rr-search-alt"></i></span><h3>Dễ tìm đúng sản phẩm</h3><p>Bộ lọc theo danh mục, kiểu dáng, chất liệu và giá giúp khách thu hẹp lựa chọn nhanh hơn.</p></div>
            <div class="about-value-card"><span><i class="fi fi-rr-eye"></i></span><h3>Thông tin rõ ràng</h3><p>Trang sản phẩm, giỏ hàng và đơn hàng được trình bày theo từng bước để giảm nhầm lẫn.</p></div>
            <div class="about-value-card"><span><i class="fi fi-rr-shield-check"></i></span><h3>Vận hành có kiểm soát</h3><p>Admin có thể quản lý sản phẩm, danh mục và trạng thái đơn để khép kín hệ thống.</p></div>
        </div>
    </div></section>

    <section class="info-section info-section--soft"><div class="info-container">
        <div class="info-section-head info-section-head--split"><div><span class="info-eyebrow">HOW IT WORKS</span><h2>Quy trình mua kính tại LUMINA</h2></div><p>Thiết kế theo hướng ít bước, rõ trạng thái và dễ quay lại kiểm tra đơn.</p></div>
        <div class="about-process-grid">
            <div class="about-process-card"><span>01</span><h3>Chọn danh mục</h3><p>Bắt đầu từ gọng kính, kính mát hoặc tròng kính.</p></div>
            <div class="about-process-card"><span>02</span><h3>Lọc theo nhu cầu</h3><p>Dùng bộ lọc kiểu dáng, chất liệu và khoảng giá.</p></div>
            <div class="about-process-card"><span>03</span><h3>Đặt hàng</h3><p>Chọn luồng có sẵn, pre-order hoặc prescription.</p></div>
            <div class="about-process-card"><span>04</span><h3>Theo dõi đơn</h3><p>Xem lịch sử và trạng thái xử lý trong tài khoản.</p></div>
        </div>
    </div></section>

    <section class="info-section"><div class="info-container"><div class="about-quote-card"><span class="info-eyebrow">LUMINA PROMISE</span><blockquote>“Một website mắt kính tốt không chỉ đẹp, mà còn phải giúp khách hiểu rõ họ đang chọn gì, vì sao phù hợp và đơn hàng đang ở trạng thái nào.”</blockquote><div class="about-quote-card__meta"><strong>LUMINA Eyewear Store</strong><span>Online eyewear shopping experience</span></div></div></div></section>

    <section class="info-section info-section--compact"><div class="info-container"><div class="collection-cta about-cta"><div><span class="info-eyebrow">START SHOPPING</span><h2>Sẵn sàng tìm mẫu kính phù hợp?</h2><p>Khám phá bộ sưu tập mới hoặc đi thẳng đến danh sách sản phẩm để lọc theo nhu cầu.</p></div><a href="<?= e(APP_URL) ?>/collections.php" class="info-btn info-btn--primary">Xem bộ sưu tập</a></div></div></section>
</main>

<?php require_once BASE_PATH . '/app/views/partials/footer.php'; ?>

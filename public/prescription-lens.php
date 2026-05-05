<?php
require_once __DIR__ . '/../app/config/config.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/helpers/prescription_flow.php';

pf_require_rx();

$lenses = pf_lens_catalog();

if (isset($_GET['select']) && isset($lenses[$_GET['select']])) {
    $key = $_GET['select'];
    $lens = $lenses[$key];

    $order =& pf_order();
    $order['lens'] = [
        'key' => $key,
        'name' => $lens['name'],
        'base_price' => (float) $lens['price'],
        'subtitle' => $lens['subtitle'],
    ];

    pf_redirect('/prescription-lens-options.php');
}

$pageTitle = 'Chọn loại tròng kính - ' . APP_NAME;
$pageStyles = [APP_URL . '/assets/css/prescription-flow.css?v=2.0.0'];
require_once BASE_PATH . '/app/views/partials/head.php';
require_once BASE_PATH . '/app/views/partials/header.php';
?>

<main class="pf-page pf-integrated-page">
    <?php pf_flow_header(3, '/prescription-method.php', 'Quay lại nhập thông số'); ?>

    <section class="pf-main">
        <div class="pf-grid checkout-layout">
            <div>
                <div class="pf-title">
                    <h1>Chọn loại tròng kính</h1>
                    <p>Dựa trên đơn kính của bạn, hãy chọn loại tròng phù hợp với nhu cầu sử dụng hằng ngày.</p>
                </div>

                <div class="pf-alert">
                    Đơn kính của bạn đã sẵn sàng để chọn tròng. Với độ cận cao, LUMINA khuyên dùng chiết suất cao để kính mỏng và nhẹ hơn.
                </div>

                <div class="pf-lens-grid">
                    <?php foreach ($lenses as $key => $lens): ?>
                        <a class="pf-lens-card <?= (pf_order()['lens']['key'] ?? '') === $key ? 'is-selected' : '' ?>" href="<?= e(APP_URL) ?>/prescription-lens.php?select=<?= e($key) ?>">
                            <div class="pf-lens-top">
                                <span><i class="<?= e($lens['icon']) ?>"></i></span>
                                <span class="pf-tag"><?= e($lens['tag']) ?></span>
                            </div>

                            <div>
                                <h3><?= e($lens['name']) ?></h3>
                                <p><?= e($lens['subtitle']) ?></p>
                                <p><?= e($lens['note']) ?></p>
                            </div>

                            <strong>Giá từ <?= e(pf_money($lens['price'])) ?></strong>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="pf-panel" style="margin-top:24px">
                    <h2>Bạn chưa chắc chắn chọn loại nào?</h2>
                    <p style="color:#64748B;line-height:1.7">Đội ngũ LUMINA có thể kiểm tra đơn kính và tư vấn loại tròng phù hợp nhất với nhu cầu của bạn.</p>
                    <div class="pf-upload-actions" style="justify-content:flex-start">
                        <a class="pf-small-btn" href="tel:0123456789"><i class="fi fi-rr-phone-call"></i> Gọi tư vấn</a>
                        <a class="pf-small-btn" href="<?= e(APP_URL) ?>/about.php"><i class="fi fi-rr-comment"></i> Chat với chuyên gia</a>
                    </div>
                </div>
            </div>

            <?php pf_summary('Chọn chiết suất & lớp phủ', '/prescription-lens-options.php', empty(pf_order()['lens'])); ?>
        </div>
    </section>
</main>

<?php require_once BASE_PATH . '/app/views/partials/footer.php'; ?>

</html>

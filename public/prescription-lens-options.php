<?php
require_once __DIR__ . '/../app/config/config.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/helpers/prescription_flow.php';

pf_require_lens();

$indexOptions = pf_index_options();
$coatingOptions = pf_coating_options();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $indexKey = $_POST['index'] ?? '1.56';
    if (!isset($indexOptions[$indexKey])) {
        $indexKey = '1.56';
    }

    $selectedCoatings = [];
    foreach (($_POST['coatings'] ?? []) as $coatingKey) {
        if (isset($coatingOptions[$coatingKey])) {
            $selectedCoatings[$coatingKey] = [
                'key' => $coatingKey,
                'name' => $coatingOptions[$coatingKey]['name'],
                'price' => (float) $coatingOptions[$coatingKey]['price'],
            ];
        }
    }

    $tint = $_POST['tint'] ?? 'clear';

    $order =& pf_order();
    $order['lens']['index_key'] = $indexKey;
    $order['lens']['index_name'] = $indexOptions[$indexKey]['name'] . ' - ' . $indexOptions[$indexKey]['label'];
    $order['lens']['index_price'] = (float) $indexOptions[$indexKey]['price'];
    $order['lens']['coatings'] = array_values($selectedCoatings);
    $order['lens']['tint'] = $tint;

    pf_redirect('/prescription-review.php');
}

$pageTitle = 'Tùy chỉnh tròng kính - ' . APP_NAME;
$pageStyles = [APP_URL . '/assets/css/prescription-flow.css?v=2.0.0'];
require_once BASE_PATH . '/app/views/partials/head.php';
require_once BASE_PATH . '/app/views/partials/header.php';
?>

<main class="pf-page pf-integrated-page">
    <?php pf_flow_header(3, '/prescription-lens.php', 'Quay lại chọn loại tròng', 'options'); ?>

    <section class="pf-main">
        <div class="pf-grid checkout-layout">
            <form method="post">
                <div class="pf-title">
                    <h1>Tùy chỉnh chi tiết tròng kính</h1>
                    <p><?= e(pf_order()['lens']['name'] ?? 'Tròng kính') ?> - tối ưu hóa theo đơn kính và nhu cầu sử dụng.</p>
                </div>

                <div class="pf-panel">
                    <div class="pf-review-head">
                        <div>
                            <h2>1. Chiết suất tròng kính</h2>
                            <p style="margin:5px 0 0;color:#64748B">Chiết suất càng cao, tròng kính càng mỏng và nhẹ.</p>
                        </div>
                        <a href="#">Hướng dẫn chọn</a>
                    </div>

                    <div class="pf-index-grid">
                        <?php foreach ($indexOptions as $key => $option): ?>
                            <label class="pf-index-card">
                                <input type="radio" name="index" value="<?= e($key) ?>" <?= $key === '1.56' ? 'checked' : '' ?>>
                                <strong><?= e($option['name']) ?> <span><?= e($option['label']) ?></span></strong>
                                <p><?= e($option['desc']) ?></p>
                                <b class="pf-price-right"><?= e($option['price'] > 0 ? '+' . pf_money($option['price']) : '+0đ') ?></b>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="pf-panel">
                    <h2>2. Lớp phủ bảo vệ</h2>
                    <div class="pf-coating-list">
                        <?php foreach ($coatingOptions as $key => $option): ?>
                            <label class="pf-coating-row">
                                <input type="checkbox" name="coatings[]" value="<?= e($key) ?>" <?= in_array($key, ['anti_reflective', 'scratch_resistant'], true) ? 'checked' : '' ?>>
                                <div>
                                    <strong><?= e($option['name']) ?></strong>
                                    <p><?= e($option['desc']) ?></p>
                                </div>
                                <b>+<?= e(pf_money($option['price'])) ?></b>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="pf-panel">
                    <h2>3. Đổi màu & nhuộm màu</h2>
                    <div class="pf-segment">
                        <label><input type="radio" name="tint" value="clear" checked><span>Trong suốt</span></label>
                        <label><input type="radio" name="tint" value="sun"><span>Đổi màu khi ra nắng</span></label>
                        <label><input type="radio" name="tint" value="sunglasses"><span>Nhuộm màu kính râm</span></label>
                    </div>
                </div>

                <button class="pf-summary-btn" type="submit">Tiếp tục xem lại đơn <i class="fi fi-rr-arrow-right"></i></button>
            </form>

            <?php pf_summary(); ?>
        </div>
    </section>
</main>

<?php require_once BASE_PATH . '/app/views/partials/footer.php'; ?>

</html>

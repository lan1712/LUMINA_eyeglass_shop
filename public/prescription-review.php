<?php
require_once __DIR__ . '/../app/config/config.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/helpers/prescription_flow.php';

pf_require_lens();

$errors = [];
$user = pf_current_user();
$order =& pf_order();

$form = $order['customer'] ?? [
    'name' => $user['full_name'] ?? '',
    'phone' => $user['phone'] ?? '',
    'email' => $user['email'] ?? '',
    'address' => '',
    'province' => 'TP. Hồ Chí Minh',
    'district' => '',
    'note' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $key => $value) {
        $form[$key] = trim($_POST[$key] ?? '');
    }

    if ($form['name'] === '') {
        $errors[] = 'Vui lòng nhập họ tên.';
    }

    if ($form['phone'] === '') {
        $errors[] = 'Vui lòng nhập số điện thoại.';
    }

    if ($form['email'] === '' || !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }

    if ($form['address'] === '') {
        $errors[] = 'Vui lòng nhập địa chỉ giao hàng.';
    }

    if (!$errors) {
        $order['customer'] = $form;
        pf_redirect('/prescription-payment.php');
    }
}

$pageTitle = 'Xác nhận đơn hàng - ' . APP_NAME;
$pageStyles = [APP_URL . '/assets/css/prescription-flow.css?v=2.0.0'];
require_once BASE_PATH . '/app/views/partials/head.php';
require_once BASE_PATH . '/app/views/partials/header.php';
?>

<main class="pf-page pf-integrated-page">
    <?php pf_flow_header(4, '/prescription-lens-options.php', 'Quay lại chi tiết tròng', 'review'); ?>

    <section class="pf-main">
        <div class="pf-payment-layout checkout-layout">
            <form method="post">
                <div class="pf-title">
                    <h1>Xác nhận đơn hàng</h1>
                    <p>Vui lòng kiểm tra lại thông tin trước khi thanh toán.</p>
                </div>

                <?php if ($errors): ?><div class="pf-error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>

                <div class="pf-review-card">
                    <div class="pf-review-head">
                        <h2>Gọng kính đã chọn</h2>
                        <a href="<?= e(APP_URL) ?>/prescription-start.php">Chỉnh sửa</a>
                    </div>
                    <div class="pf-mini-card">
                        <img src="<?= e(pf_image($order['frame']['image'] ?? '')) ?>" alt="">
                        <div>
                            <span><?= e($order['frame']['brand'] ?? 'LUMINA') ?></span>
                            <strong><?= e($order['frame']['name'] ?? '') ?></strong>
                            <small><?= e(trim(($order['frame']['color'] ?? '') . ' ' . ($order['frame']['size'] ?? ''))) ?></small>
                        </div>
                        <b><?= e(pf_money($order['frame']['price'] ?? 0)) ?></b>
                    </div>
                </div>

                <div class="pf-review-card">
                    <div class="pf-review-head">
                        <h2>Thông số đơn kính</h2>
                        <a href="<?= e(APP_URL) ?>/prescription-method.php">Sửa</a>
                    </div>

                    <?php if (($order['rx']['method'] ?? '') === 'manual'): ?>
                        <table class="pf-table">
                            <thead><tr><th>Mắt</th><th>SPH</th><th>CYL</th><th>AXIS</th><th>ADD</th></tr></thead>
                            <tbody>
                                <tr><td>Phải OD</td><td><?= e((string)($order['rx']['r_sph'] ?? '—')) ?></td><td><?= e((string)($order['rx']['r_cyl'] ?? '—')) ?></td><td><?= e((string)($order['rx']['r_axis'] ?? '—')) ?></td><td><?= e((string)($order['rx']['r_add'] ?? '—')) ?></td></tr>
                                <tr><td>Trái OS</td><td><?= e((string)($order['rx']['l_sph'] ?? '—')) ?></td><td><?= e((string)($order['rx']['l_cyl'] ?? '—')) ?></td><td><?= e((string)($order['rx']['l_axis'] ?? '—')) ?></td><td><?= e((string)($order['rx']['l_add'] ?? '—')) ?></td></tr>
                            </tbody>
                        </table>
                        <p style="color:#64748B">PD: <strong><?= e((string)($order['rx']['pd'] ?? '—')) ?> mm</strong></p>
                    <?php else: ?>
                        <p style="color:#64748B;line-height:1.7">Phương thức: <strong><?= e($order['rx']['method_label'] ?? 'Đơn kính') ?></strong></p>
                        <?php if (!empty($order['rx']['attachment_path'])): ?>
                            <p style="color:#64748B">File đơn kính: <?= e(basename($order['rx']['attachment_path'])) ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="pf-review-card">
                    <div class="pf-review-head">
                        <h2>Chi tiết tròng kính</h2>
                        <a href="<?= e(APP_URL) ?>/prescription-lens-options.php">Chỉnh sửa</a>
                    </div>

                    <table class="pf-table">
                        <tr><th>Loại tròng</th><td><?= e($order['lens']['name'] ?? '') ?></td><td><?= e(pf_money($order['lens']['base_price'] ?? 0)) ?></td></tr>
                        <tr><th>Chiết suất</th><td><?= e($order['lens']['index_name'] ?? '') ?></td><td>+<?= e(pf_money($order['lens']['index_price'] ?? 0)) ?></td></tr>
                        <?php foreach (($order['lens']['coatings'] ?? []) as $coating): ?>
                            <tr><th>Lớp phủ</th><td><?= e($coating['name']) ?></td><td>+<?= e(pf_money($coating['price'])) ?></td></tr>
                        <?php endforeach; ?>
                        <tr><th>Màu tròng</th><td><?= e($order['lens']['tint'] ?? 'clear') ?></td><td>—</td></tr>
                    </table>
                </div>

                <div class="pf-review-card">
                    <h2>Thông tin liên hệ & giao hàng</h2>

                    <div class="pf-form-grid">
                        <div class="pf-form-row">
                            <label class="pf-field"><span>Họ và tên</span><input name="name" value="<?= e($form['name']) ?>" required></label>
                            <label class="pf-field"><span>Số điện thoại</span><input name="phone" value="<?= e($form['phone']) ?>" required></label>
                        </div>

                        <label class="pf-field"><span>Email</span><input type="email" name="email" value="<?= e($form['email']) ?>" required></label>
                        <label class="pf-field"><span>Địa chỉ giao hàng</span><input name="address" value="<?= e($form['address']) ?>" required></label>

                        <div class="pf-form-row">
                            <label class="pf-field"><span>Tỉnh/Thành phố</span><input name="province" value="<?= e($form['province']) ?>"></label>
                            <label class="pf-field"><span>Quận/Huyện</span><input name="district" value="<?= e($form['district']) ?>"></label>
                        </div>

                        <label class="pf-field"><span>Ghi chú</span><textarea name="note"><?= e($form['note']) ?></textarea></label>
                    </div>
                </div>

                <button class="pf-summary-btn" type="submit">Tiếp tục thanh toán <i class="fi fi-rr-arrow-right"></i></button>
            </form>

            <?php pf_summary(); ?>
        </div>
    </section>
</main>

<?php require_once BASE_PATH . '/app/views/partials/footer.php'; ?>

</html>

<?php
require_once __DIR__ . '/../app/config/config.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/helpers/prescription_flow.php';

$code = trim($_GET['code'] ?? ($_SESSION['last_prescription_order']['code'] ?? ''));
$total = $_SESSION['last_prescription_order']['total'] ?? null;

$pageTitle = 'Đặt hàng thành công - ' . APP_NAME;
$pageStyles = [APP_URL . '/assets/css/prescription-flow.css?v=2.0.0'];
require_once BASE_PATH . '/app/views/partials/head.php';
require_once BASE_PATH . '/app/views/partials/header.php';
?>

<main class="order-success-page pf-page pf-integrated-page">
    <div class="pf-success">
        <div class="pf-success-icon"><i class="fi fi-rr-check"></i></div>
        <h1>Đặt kính thành công</h1>
        <p>
            Đơn prescription của bạn đã được ghi nhận. LUMINA sẽ kiểm tra thông số đơn kính
            trước khi chuyển sang bước cắt và lắp ráp tròng.
        </p>

        <?php if ($code): ?>
            <div class="pf-info-note" style="text-align:left">
                <strong>Mã đơn:</strong> <?= e($code) ?><br>
                <?php if ($total !== null): ?><strong>Tổng tiền:</strong> <?= e(pf_money($total)) ?><?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="pf-success-actions">
            <a class="pf-summary-btn" href="<?= e(APP_URL) ?>/orders.php">Xem đơn hàng của tôi</a>
            <a class="pf-small-btn" href="<?= e(APP_URL) ?>/">Về trang chủ</a>
        </div>
    </div>
</main>

<?php require_once BASE_PATH . '/app/views/partials/footer.php'; ?>

</html>

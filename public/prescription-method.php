<?php
require_once __DIR__ . '/../app/config/config.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/helpers/prescription_flow.php';

pf_require_frame();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = $_POST['method'] ?? 'upload';
    $allowed = ['upload', 'manual', 'saved', 'callback'];

    if (!in_array($method, $allowed, true)) {
        $method = 'upload';
    }

    $labels = [
        'upload' => 'Tải ảnh đơn kính',
        'manual' => 'Nhập tay thông số',
        'saved' => 'Dùng đơn kính đã lưu',
        'callback' => 'Yêu cầu gọi lại tư vấn',
    ];

    $order =& pf_order();
    $order['rx'] = [
        'method' => $method,
        'method_label' => $labels[$method],
    ];

    if ($method === 'manual') {
        pf_redirect('/prescription-manual.php');
    }

    if ($method === 'upload') {
        pf_redirect('/prescription-upload.php');
    }

    // Demo cho 2 phương thức còn lại: vẫn cho đi tiếp và ghi chú để admin xử lý.
    $order['rx']['note'] = $method === 'saved'
        ? 'Khách chọn dùng đơn kính đã lưu.'
        : 'Khách yêu cầu chuyên viên gọi lại lấy thông số.';
    pf_redirect('/prescription-lens.php');
}

$pageTitle = 'Cung cấp đơn kính - ' . APP_NAME;
$pageStyles = [APP_URL . '/assets/css/prescription-flow.css?v=2.0.0'];
require_once BASE_PATH . '/app/views/partials/head.php';
require_once BASE_PATH . '/app/views/partials/header.php';
?>

<main class="pf-page pf-integrated-page">
    <?php pf_flow_header(2, '/prescription-detail.php?id=' . (int) pf_order()['frame']['product_id'], 'Quay lại chi tiết gọng'); ?>

    <section class="pf-main">
        <div class="pf-grid checkout-layout">
            <form method="post">
                <div class="pf-title">
                    <h1>Cung cấp đơn kính của bạn</h1>
                    <p>Vui lòng chọn phương thức cung cấp thông số mắt để LUMINA cắt tròng chính xác hơn.</p>
                </div>

                <div class="pf-option-grid">
                    <button class="pf-option-card is-selected" type="submit" name="method" value="upload">
                        <span class="pf-option-icon"><i class="fi fi-rr-cloud-upload"></i></span>
                        <div>
                            <h3>Tải ảnh đơn kính lên</h3>
                            <p>Chụp ảnh toa kính từ bác sĩ hoặc bệnh viện mắt và tải lên hệ thống.</p>
                        </div>
                    </button>

                    <button class="pf-option-card" type="submit" name="method" value="manual">
                        <span class="pf-option-icon"><i class="fi fi-rr-keyboard"></i></span>
                        <div>
                            <h3>Nhập tay thông số</h3>
                            <p>Tự điền SPH, CYL, AXIS, ADD và PD theo đơn kính của bạn.</p>
                        </div>
                    </button>

                    <button class="pf-option-card" type="submit" name="method" value="saved">
                        <span class="pf-option-icon"><i class="fi fi-rr-id-badge"></i></span>
                        <div>
                            <h3>Dùng đơn kính đã lưu</h3>
                            <p>Sử dụng lại thông số từ lần mua trước. Yêu cầu đăng nhập.</p>
                        </div>
                    </button>

                    <button class="pf-option-card" type="submit" name="method" value="callback">
                        <span class="pf-option-icon"><i class="fi fi-rr-headset"></i></span>
                        <div>
                            <h3>Yêu cầu gọi lại tư vấn</h3>
                            <p>Để lại số điện thoại, chuyên viên sẽ hỗ trợ bạn lấy thông số.</p>
                        </div>
                    </button>
                </div>

                <div class="pf-info-note">
                    <strong>Lưu ý:</strong> Đơn kính nên được đo trong vòng 6 tháng gần nhất. Nếu có thông số PD, hãy nhập hoặc chụp rõ phần PD.
                </div>
            </form>

            <?php pf_summary('Tiếp tục', '', false); ?>
        </div>
    </section>
</main>

<?php require_once BASE_PATH . '/app/views/partials/footer.php'; ?>

</html>

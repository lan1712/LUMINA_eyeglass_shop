<?php
require_once __DIR__ . '/../app/config/config.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/helpers/prescription_flow.php';

pf_require_frame();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadPath = '';

    if (!empty($_FILES['prescription_file']['name']) && $_FILES['prescription_file']['error'] === UPLOAD_ERR_OK) {
        $allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];
        $ext = strtolower(pathinfo($_FILES['prescription_file']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt, true)) {
            $errors[] = 'File đơn kính chỉ hỗ trợ JPG, PNG hoặc PDF.';
        } elseif ($_FILES['prescription_file']['size'] > 5 * 1024 * 1024) {
            $errors[] = 'File đơn kính không được vượt quá 5MB.';
        } else {
            $uploadDir = PUBLIC_PATH . '/uploads/prescriptions';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $fileName = 'rx_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $target = $uploadDir . '/' . $fileName;

            if (move_uploaded_file($_FILES['prescription_file']['tmp_name'], $target)) {
                $uploadPath = 'uploads/prescriptions/' . $fileName;
            } else {
                $errors[] = 'Không thể tải file lên. Vui lòng thử lại.';
            }
        }
    } else {
        $errors[] = 'Vui lòng chọn file đơn kính.';
    }

    if (!$errors) {
        $order =& pf_order();
        $order['rx'] = array_merge($order['rx'] ?? [], [
            'method' => 'upload',
            'method_label' => 'Tải ảnh đơn kính',
            'attachment_path' => $uploadPath,
            'exam_date' => trim($_POST['exam_date'] ?? ''),
            'has_pd' => !empty($_POST['has_pd']),
            'note' => trim($_POST['note'] ?? ''),
        ]);

        pf_redirect('/prescription-lens.php');
    }
}

$pageTitle = 'Tải ảnh đơn kính - ' . APP_NAME;
$pageStyles = [APP_URL . '/assets/css/prescription-flow.css?v=2.0.0'];
require_once BASE_PATH . '/app/views/partials/head.php';
require_once BASE_PATH . '/app/views/partials/header.php';
?>

<main class="pf-page pf-integrated-page">
    <?php pf_flow_header(2, '/prescription-method.php', 'Quay lại phương thức nhập'); ?>

    <section class="pf-main">
        <div class="pf-grid checkout-layout">
            <form method="post" enctype="multipart/form-data">
                <div class="pf-title">
                    <h1>Tải ảnh đơn kính lên</h1>
                    <p>Chụp ảnh toa kính từ bác sĩ hoặc bệnh viện mắt. LUMINA sẽ đọc và xác nhận thông số trước khi cắt tròng.</p>
                </div>

                <?php if ($errors): ?><div class="pf-error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>

                <div class="pf-panel">
                    <div class="pf-review-head">
                        <h2>Tải tệp lên</h2>
                        <span style="color:#64748B;font-weight:800">Hỗ trợ JPG, PNG, PDF tối đa 5MB</span>
                    </div>

                    <label class="pf-upload-box">
                        <input type="file" name="prescription_file" accept=".jpg,.jpeg,.png,.pdf" required>
                        <div>
                            <i class="fi fi-rr-cloud-upload"></i>
                            <h3>Kéo thả tệp vào đây</h3>
                            <p>hoặc nhấp để chọn tệp từ thiết bị của bạn</p>
                            <div class="pf-upload-actions">
                                <span class="pf-small-btn"><i class="fi fi-rr-folder"></i> Chọn tệp</span>
                                <span class="pf-small-btn is-primary"><i class="fi fi-rr-camera"></i> Chụp ảnh</span>
                            </div>
                        </div>
                    </label>
                </div>

                <div class="pf-panel">
                    <h2>Thông tin bổ sung</h2>
                    <div class="pf-form-grid">
                        <div class="pf-form-row">
                            <label class="pf-field">
                                <span>Ngày đo khám nếu có trên đơn</span>
                                <input type="date" name="exam_date">
                            </label>

                            <label class="pf-check" style="align-self:end;margin-bottom:12px">
                                <input type="checkbox" name="has_pd" value="1" checked>
                                Có thông số PD
                            </label>
                        </div>

                        <label class="pf-field">
                            <span>Ghi chú thêm</span>
                            <textarea name="note" placeholder="Ví dụ: Mắt phải của tôi bị loạn thị nặng hơn..."></textarea>
                        </label>
                    </div>
                </div>

                <button class="pf-summary-btn" type="submit">Xác nhận & tiếp tục <i class="fi fi-rr-arrow-right"></i></button>
            </form>

            <?php pf_summary(); ?>
        </div>
    </section>
</main>

<?php require_once BASE_PATH . '/app/views/partials/footer.php'; ?>

</html>

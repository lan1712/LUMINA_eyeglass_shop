<?php
require_once __DIR__ . '/../app/config/config.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/helpers/prescription_flow.php';

pf_require_lens();

$order =& pf_order();
if (empty($order['customer'])) {
    pf_redirect('/prescription-review.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shippingMethod = $_POST['shipping_method'] ?? 'standard';
    $paymentMethod = $_POST['payment_method'] ?? 'bank_transfer';

    if (!in_array($shippingMethod, ['standard', 'express'], true)) {
        $shippingMethod = 'standard';
    }

    if (!in_array($paymentMethod, ['cod', 'bank_transfer', 'momo', 'vnpay'], true)) {
        $paymentMethod = 'bank_transfer';
    }

    $order['payment'] = [
        'shipping_method' => $shippingMethod,
        'payment_method' => $paymentMethod,
    ];

    $user = pf_current_user();

    if (!$user) {
        $_SESSION['intended_redirect'] = '/prescription-payment.php';
        pf_redirect('/login.php');
    }

    if (empty($order['frame']['variant_id'])) {
        $errors[] = 'Sản phẩm này chưa có biến thể trong database nên chưa thể tạo order_items. Vui lòng thêm product_variant cho sản phẩm.';
    }

    if (!$errors) {
        $db = pf_db();

        try {
            $db->beginTransaction();

            $prescriptionId = null;
            $rx = $order['rx'] ?? [];

            $prescriptionStmt = $db->prepare("
                INSERT INTO prescriptions (
                    user_id,
                    prescription_name,
                    right_sphere,
                    left_sphere,
                    right_cylinder,
                    left_cylinder,
                    right_axis,
                    left_axis,
                    right_addition,
                    left_addition,
                    pd_distance,
                    note,
                    attachment_path,
                    verification_status,
                    created_at,
                    updated_at
                ) VALUES (
                    :user_id,
                    :prescription_name,
                    :right_sphere,
                    :left_sphere,
                    :right_cylinder,
                    :left_cylinder,
                    :right_axis,
                    :left_axis,
                    :right_addition,
                    :left_addition,
                    :pd_distance,
                    :note,
                    :attachment_path,
                    'pending',
                    NOW(),
                    NOW()
                )
            ");

            $prescriptionStmt->execute([
                ':user_id' => (int) $user['id'],
                ':prescription_name' => 'Đơn kính LUMINA ' . date('d/m/Y H:i'),
                ':right_sphere' => $rx['r_sph'] ?? null,
                ':left_sphere' => $rx['l_sph'] ?? null,
                ':right_cylinder' => $rx['r_cyl'] ?? null,
                ':left_cylinder' => $rx['l_cyl'] ?? null,
                ':right_axis' => $rx['r_axis'] ?? null,
                ':left_axis' => $rx['l_axis'] ?? null,
                ':right_addition' => $rx['r_add'] ?? null,
                ':left_addition' => $rx['l_add'] ?? null,
                ':pd_distance' => $rx['pd'] ?? null,
                ':note' => trim(($rx['method_label'] ?? '') . "\n" . ($rx['note'] ?? '')),
                ':attachment_path' => $rx['attachment_path'] ?? null,
            ]);

            $prescriptionId = (int) $db->lastInsertId();

            $subtotal = (float) ($order['frame']['price'] ?? 0);
            $lensTotal = pf_lens_total();
            $shippingFee = pf_shipping_fee();
            $total = $subtotal + $lensTotal + $shippingFee;
            $orderCode = 'LM' . date('ymdHis') . random_int(10, 99);

            $customer = $order['customer'];

            $orderStmt = $db->prepare("
                INSERT INTO orders (
                    user_id,
                    order_code,
                    order_type,
                    status,
                    customer_name,
                    customer_email,
                    customer_phone,
                    shipping_address_line,
                    shipping_district,
                    shipping_province,
                    note,
                    subtotal,
                    lens_total,
                    shipping_fee,
                    discount_amount,
                    total_amount,
                    payment_method,
                    payment_status,
                    prescription_id,
                    created_at,
                    updated_at
                ) VALUES (
                    :user_id,
                    :order_code,
                    'prescription',
                    'checking_prescription',
                    :customer_name,
                    :customer_email,
                    :customer_phone,
                    :shipping_address_line,
                    :shipping_district,
                    :shipping_province,
                    :note,
                    :subtotal,
                    :lens_total,
                    :shipping_fee,
                    0,
                    :total_amount,
                    :payment_method,
                    'unpaid',
                    :prescription_id,
                    NOW(),
                    NOW()
                )
            ");

            $orderStmt->execute([
                ':user_id' => (int) $user['id'],
                ':order_code' => $orderCode,
                ':customer_name' => $customer['name'],
                ':customer_email' => $customer['email'],
                ':customer_phone' => $customer['phone'],
                ':shipping_address_line' => $customer['address'],
                ':shipping_district' => $customer['district'] ?? '',
                ':shipping_province' => $customer['province'] ?? '',
                ':note' => $customer['note'] ?? '',
                ':subtotal' => $subtotal,
                ':lens_total' => $lensTotal,
                ':shipping_fee' => $shippingFee,
                ':total_amount' => $total,
                ':payment_method' => $paymentMethod,
                ':prescription_id' => $prescriptionId,
            ]);

            $orderId = (int) $db->lastInsertId();

            $itemStmt = $db->prepare("
                INSERT INTO order_items (
                    order_id,
                    product_variant_id,
                    lens_option_id,
                    product_name,
                    variant_sku,
                    variant_snapshot,
                    lens_snapshot,
                    quantity,
                    unit_price,
                    lens_price,
                    line_total,
                    created_at,
                    updated_at
                ) VALUES (
                    :order_id,
                    :product_variant_id,
                    NULL,
                    :product_name,
                    :variant_sku,
                    :variant_snapshot,
                    :lens_snapshot,
                    1,
                    :unit_price,
                    :lens_price,
                    :line_total,
                    NOW(),
                    NOW()
                )
            ");

            $itemStmt->execute([
                ':order_id' => $orderId,
                ':product_variant_id' => (int) $order['frame']['variant_id'],
                ':product_name' => $order['frame']['name'],
                ':variant_sku' => $order['frame']['variant_sku'] ?: ('PRX-' . $order['frame']['variant_id']),
                ':variant_snapshot' => json_encode($order['frame'], JSON_UNESCAPED_UNICODE),
                ':lens_snapshot' => json_encode(['rx' => $order['rx'], 'lens' => $order['lens']], JSON_UNESCAPED_UNICODE),
                ':unit_price' => $subtotal,
                ':lens_price' => $lensTotal,
                ':line_total' => $subtotal + $lensTotal,
            ]);

            try {
                $logStmt = $db->prepare("
                    INSERT INTO order_status_logs (order_id, changed_by, old_status, new_status, note, created_at)
                    VALUES (:order_id, NULL, NULL, 'checking_prescription', 'Khách tạo đơn prescription online', NOW())
                ");
                $logStmt->execute([':order_id' => $orderId]);
            } catch (Throwable $ignored) {
                // Bỏ qua nếu schema log chưa có created_at hoặc khác cấu trúc.
            }

            $db->commit();

            $_SESSION['last_prescription_order'] = [
                'id' => $orderId,
                'code' => $orderCode,
                'total' => $total,
            ];

            pf_reset_order();
            pf_redirect('/prescription-success.php?code=' . urlencode($orderCode));
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            $errors[] = 'Không thể tạo đơn hàng: ' . $exception->getMessage();
        }
    }
}

$pageTitle = 'Thanh toán đơn kính - ' . APP_NAME;
$pageStyles = [APP_URL . '/assets/css/prescription-flow.css?v=2.0.0'];
require_once BASE_PATH . '/app/views/partials/head.php';
require_once BASE_PATH . '/app/views/partials/header.php';
?>

<main class="pf-page pf-integrated-page">
    <?php pf_flow_header(4, '/prescription-review.php', 'Quay lại xem lại đơn'); ?>

    <section class="pf-main">
        <div class="pf-payment-layout checkout-layout">
            <form method="post">
                <div class="pf-title">
                    <h1>Thanh toán & đặt hàng</h1>
                    <p>Hoàn tất thông tin giao hàng và chọn phương thức thanh toán.</p>
                </div>

                <?php if ($errors): ?><div class="pf-error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>

                <div class="pf-panel">
                    <h2>Địa chỉ giao hàng</h2>
                    <div class="pf-option-grid">
                        <label class="pf-payment-option">
                            <input type="radio" checked>
                            <div>
                                <strong>Địa chỉ đã nhập</strong>
                                <p><?= e($order['customer']['name'] ?? '') ?> - <?= e($order['customer']['phone'] ?? '') ?><br><?= e($order['customer']['address'] ?? '') ?></p>
                            </div>
                        </label>

                        <div class="pf-payment-option">
                            <div><strong>Thêm địa chỉ mới</strong><p>Có thể bổ sung sau khi mở rộng tính năng.</p></div>
                        </div>
                    </div>
                </div>

                <div class="pf-panel">
                    <h2>Phương thức giao hàng</h2>
                    <div class="pf-payment-list">
                        <label class="pf-payment-option">
                            <input type="radio" name="shipping_method" value="standard" checked>
                            <div><strong>Giao hàng tiêu chuẩn</strong><p>Dự kiến 2-3 ngày làm việc sau khi hoàn thiện kính.</p></div>
                            <b>Miễn phí</b>
                        </label>

                        <label class="pf-payment-option">
                            <input type="radio" name="shipping_method" value="express">
                            <div><strong>Giao hàng hỏa tốc</strong><p>Trong ngày, áp dụng nội thành TP.HCM.</p></div>
                            <b>40.000đ</b>
                        </label>
                    </div>
                </div>

                <div class="pf-panel">
                    <h2>Phương thức thanh toán</h2>
                    <div class="pf-payment-list">
                        <label class="pf-payment-option">
                            <input type="radio" name="payment_method" value="momo">
                            <div><strong>Ví MoMo</strong><p>Quét mã QR qua ứng dụng MoMo.</p></div>
                        </label>

                        <label class="pf-payment-option">
                            <input type="radio" name="payment_method" value="vnpay">
                            <div><strong>VNPAY-QR</strong><p>Thanh toán qua ứng dụng ngân hàng.</p></div>
                        </label>

                        <label class="pf-payment-option">
                            <input type="radio" name="payment_method" value="bank_transfer" checked>
                            <div><strong>Chuyển khoản ngân hàng</strong><p>Thông tin tài khoản sẽ hiển thị sau khi đặt hàng.</p></div>
                        </label>

                        <label class="pf-payment-option">
                            <input type="radio" name="payment_method" value="cod">
                            <div><strong>Thanh toán khi nhận hàng COD</strong><p>Có thể yêu cầu đặt cọc với đơn kính có độ cao.</p></div>
                        </label>
                    </div>
                </div>

                <button class="pf-summary-btn" type="submit">
                    Thanh toán / Đặt hàng <i class="fi fi-rr-arrow-right"></i>
                </button>
            </form>

            <?php pf_summary(); ?>
        </div>
    </section>
</main>

<?php require_once BASE_PATH . '/app/views/partials/footer.php'; ?>

</html>

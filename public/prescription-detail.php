<?php
require_once __DIR__ . '/../app/config/config.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/helpers/prescription_flow.php';

$id = (int) ($_GET['id'] ?? 0);
$product = $id > 0 ? pf_load_product($id) : null;

if (!$product) {
    pf_redirect('/prescription-start.php');
}

/*
 * Trong flow prescription:
 * Khách bấm "Chọn làm kính" từ prescription-start.php nghĩa là đã có ý định chọn gọng.
 * Vì vậy khi vào trang chi tiết, ta lưu gọng vào session luôn để bước sau dùng tiếp.
 */
pf_set_frame($product);

$pageTitle = $product['name'] . ' - Chọn gọng làm kính';
$pageDescription = $product['short_description'] ?: 'Xác nhận gọng kính để tiếp tục quy trình đặt kính theo đơn tại LUMINA.';
$pageStyles = [
    APP_URL . '/assets/css/prescription-flow.css?v=' . (@filemtime(PUBLIC_PATH . '/assets/css/prescription-flow.css') ?: time()),
];

require_once BASE_PATH . '/app/views/partials/head.php';
require_once BASE_PATH . '/app/views/partials/header.php';
?>

<main class="page-section pf-integrated-page prescription-detail-intent">
    <div class="container">
        <?php pf_flow_header(1, '/prescription-start.php', 'Chọn gọng khác'); ?>

        <div class="product-detail-layout pf-detail-layout">
            <section class="product-detail-image-card">
                <img
                    src="<?= e(pf_image($product['image_url'] ?? $product['thumbnail'] ?? '')) ?>"
                    alt="<?= e($product['name']) ?>"
                >
            </section>

            <section class="product-detail-info-card">
                <div class="product-detail-meta">
                    <span class="badge badge-primary">Đã chọn cho đơn kính</span>
                    <span class="badge"><?= e($product['brand'] ?? 'LUMINA') ?></span>

                    <?php if (!empty($product['category_name'])): ?>
                        <span class="badge"><?= e($product['category_name']) ?></span>
                    <?php endif; ?>
                </div>

                <h1 class="product-detail-title"><?= e($product['name']) ?></h1>

                <?php if (!empty($product['variant_sku'])): ?>
                    <p class="product-detail-brand">Mã sản phẩm: <?= e($product['variant_sku']) ?></p>
                <?php elseif (!empty($product['slug'])): ?>
                    <p class="product-detail-brand">Mã sản phẩm: <?= e(strtoupper($product['slug'])) ?></p>
                <?php endif; ?>

                <p class="product-detail-price">
                    <?= e(pf_money($product['sale_price'] ?? $product['default_price'] ?? 0)) ?>
                </p>

                <div class="product-detail-feature-list">
                    <div class="detail-row">
                        <span class="label">Kiểu dáng</span>
                        <strong><?= e($product['shape'] ?: '—') ?></strong>
                    </div>

                    <div class="detail-row">
                        <span class="label">Chất liệu</span>
                        <strong><?= e($product['material'] ?: '—') ?></strong>
                    </div>

                    <div class="detail-row">
                        <span class="label">Màu sắc</span>
                        <strong><?= e($product['variant_color'] ?: '—') ?></strong>
                    </div>

                    <div class="detail-row">
                        <span class="label">Size</span>
                        <strong><?= e($product['variant_size'] ?: '—') ?></strong>
                    </div>
                </div>

                <div class="product-detail-description prescription-next-box">
                    <h2>Tiếp theo bạn sẽ làm gì?</h2>
                    <p>
                        Gọng này đã được lưu vào đơn kính tạm thời. Ở bước tiếp theo, bạn sẽ cung cấp đơn kính
                        bằng cách tải ảnh đơn hoặc nhập thông số, sau đó chọn loại tròng, lớp phủ và hoàn tất đặt hàng.
                    </p>
                </div>

                <div class="detail-actions prescription-detail-actions">
                    <a href="<?= e(APP_URL) ?>/prescription-method.php" class="btn-primary">
                        Tiếp tục nhập đơn kính
                        <i class="fi fi-rr-arrow-right"></i>
                    </a>

                    <a href="<?= e(APP_URL) ?>/prescription-start.php" class="btn-outline">
                        Chọn gọng khác
                    </a>
                </div>
            </section>
        </div>

        <section class="prescription-detail-summary">
            <?php pf_summary('Tiếp tục nhập đơn kính', '/prescription-method.php'); ?>
        </section>
    </div>
</main>

<style>
    .prescription-detail-intent .product-detail-layout {
        margin-top: 28px;
    }

    .prescription-detail-intent .product-detail-info-card {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .prescription-detail-intent .product-detail-title {
        margin-bottom: 0;
    }

    .prescription-next-box {
        padding-top: 8px;
    }

    .prescription-next-box h2 {
        margin: 0 0 12px;
        font-size: 24px;
        color: #111827;
    }

    .prescription-detail-actions {
        margin-top: 4px;
    }

    .prescription-detail-actions .btn-primary,
    .prescription-detail-actions .btn-outline {
        min-height: 48px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .prescription-detail-summary {
        display: none;
    }

    @media (max-width: 980px) {
        .prescription-detail-summary {
            display: block;
            margin-top: 24px;
        }

        .prescription-detail-summary .pf-summary {
            position: static;
        }
    }
</style>

<?php require_once BASE_PATH . '/app/views/partials/footer.php'; ?>

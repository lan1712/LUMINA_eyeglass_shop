<?php
/**
 * LUMINA Prescription Flow Helper
 *
 * Flow:
 * prescription-start.php
 * prescription-detail.php?id=...
 * prescription-method.php
 * prescription-upload.php | prescription-manual.php
 * prescription-lens.php
 * prescription-lens-options.php
 * prescription-review.php
 * prescription-payment.php
 * prescription-success.php
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!function_exists('pf_db')) {
    function pf_db(): PDO
    {
        return Database::connect();
    }
}

if (!function_exists('pf_money')) {
    function pf_money($value): string
    {
        if (function_exists('format_price')) {
            return format_price((float) $value);
        }

        return number_format((float) $value, 0, ',', '.') . 'đ';
    }
}

if (!function_exists('pf_image')) {
    function pf_image(?string $path): string
    {
        if (!$path) {
            return APP_URL . '/assets/images/placeholder-glasses.svg';
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        return APP_URL . '/' . ltrim($path, '/');
    }
}

if (!function_exists('pf_order')) {
    function &pf_order(): array
    {
        if (!isset($_SESSION['prescription_order']) || !is_array($_SESSION['prescription_order'])) {
            $_SESSION['prescription_order'] = [];
        }

        return $_SESSION['prescription_order'];
    }
}

if (!function_exists('pf_reset_order')) {
    function pf_reset_order(): void
    {
        unset($_SESSION['prescription_order']);
    }
}

if (!function_exists('pf_redirect')) {
    function pf_redirect(string $path): void
    {
        if (function_exists('redirect_to')) {
            redirect_to($path);
        }

        header('Location: ' . APP_URL . $path);
        exit;
    }
}

if (!function_exists('pf_current_user')) {
    function pf_current_user(): ?array
    {
        if (function_exists('auth_user')) {
            return auth_user();
        }

        if (function_exists('current_user')) {
            return current_user();
        }

        return $_SESSION['auth_user'] ?? $_SESSION['user'] ?? null;
    }
}

if (!function_exists('pf_require_frame')) {
    function pf_require_frame(): void
    {
        $order = pf_order();

        if (empty($order['frame']['product_id'])) {
            pf_redirect('/prescription-start.php');
        }
    }
}

if (!function_exists('pf_require_rx')) {
    function pf_require_rx(): void
    {
        pf_require_frame();

        $order = pf_order();

        if (empty($order['rx']['method'])) {
            pf_redirect('/prescription-method.php');
        }
    }
}

if (!function_exists('pf_require_lens')) {
    function pf_require_lens(): void
    {
        pf_require_rx();

        $order = pf_order();

        if (empty($order['lens']['key'])) {
            pf_redirect('/prescription-lens.php');
        }
    }
}

if (!function_exists('pf_load_product')) {
    function pf_load_product(int $id): ?array
    {
        $db = pf_db();

        $sql = "
            SELECT
                p.id,
                p.name,
                p.slug,
                p.brand,
                p.short_description,
                p.description,
                p.material,
                p.shape,
                p.target_gender,
                p.thumbnail,
                p.default_price,
                c.name AS category_name,
                c.slug AS category_slug,
                v.id AS variant_id,
                v.sku AS variant_sku,
                v.color AS variant_color,
                v.size_label AS variant_size,
                v.price AS variant_price,
                v.stock_quantity,
                v.image_override,
                COALESCE(v.image_override, p.thumbnail, pi.image_url) AS image_url,
                COALESCE(v.price, p.default_price, 0) AS sale_price
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN product_variants v
                ON v.id = (
                    SELECT vv.id
                    FROM product_variants vv
                    WHERE vv.product_id = p.id
                      AND vv.is_active = 1
                    ORDER BY vv.stock_quantity DESC, vv.price ASC, vv.id ASC
                    LIMIT 1
                )
            LEFT JOIN product_images pi
                ON pi.id = (
                    SELECT pii.id
                    FROM product_images pii
                    WHERE pii.product_id = p.id
                    ORDER BY pii.is_primary DESC, pii.sort_order ASC, pii.id ASC
                    LIMIT 1
                )
            WHERE p.id = :id
              AND p.status = 'active'
            LIMIT 1
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        return $product ?: null;
    }
}

if (!function_exists('pf_frame_query')) {
    function pf_frame_query(array $filters = []): array
    {
        $db = pf_db();
        $where = ["p.status = 'active'"];
        $params = [];

        // Ưu tiên gọng kính và sản phẩm hỗ trợ prescription; nếu DB chưa set flag thì vẫn lấy gọng/kính mát.
        $where[] = "(
            p.is_prescription_supported = 1
            OR c.slug IN ('gong-kinh', 'kinh-mat')
            OR parent.slug IN ('gong-kinh', 'kinh-mat')
        )";

        if (!empty($filters['q'])) {
            $where[] = "(p.name LIKE :q OR p.brand LIKE :q OR p.short_description LIKE :q)";
            $params[':q'] = '%' . $filters['q'] . '%';
        }

        if (!empty($filters['material'])) {
            $where[] = "p.material LIKE :material";
            $params[':material'] = '%' . $filters['material'] . '%';
        }

        $sql = "
            SELECT
                p.id,
                p.name,
                p.slug,
                p.brand,
                p.short_description,
                p.material,
                p.shape,
                p.thumbnail,
                p.default_price,
                c.name AS category_name,
                v.id AS variant_id,
                v.sku AS variant_sku,
                v.color AS variant_color,
                v.size_label AS variant_size,
                v.stock_quantity,
                COALESCE(v.price, p.default_price, 0) AS sale_price,
                COALESCE(v.image_override, p.thumbnail, pi.image_url) AS image_url
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN categories parent ON parent.id = c.parent_id
            LEFT JOIN product_variants v
                ON v.id = (
                    SELECT vv.id
                    FROM product_variants vv
                    WHERE vv.product_id = p.id
                      AND vv.is_active = 1
                    ORDER BY vv.stock_quantity DESC, vv.price ASC, vv.id ASC
                    LIMIT 1
                )
            LEFT JOIN product_images pi
                ON pi.id = (
                    SELECT pii.id
                    FROM product_images pii
                    WHERE pii.product_id = p.id
                    ORDER BY pii.is_primary DESC, pii.sort_order ASC, pii.id ASC
                    LIMIT 1
                )
            WHERE " . implode(' AND ', $where) . "
            ORDER BY p.id DESC
            LIMIT 12
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('pf_set_frame')) {
    function pf_set_frame(array $product): void
    {
        $order =& pf_order();

        $order['frame'] = [
            'product_id' => (int) $product['id'],
            'variant_id' => $product['variant_id'] ? (int) $product['variant_id'] : null,
            'variant_sku' => (string) ($product['variant_sku'] ?? ('PRX-' . $product['id'])),
            'name' => (string) $product['name'],
            'brand' => (string) ($product['brand'] ?? 'LUMINA'),
            'price' => (float) ($product['sale_price'] ?? $product['default_price'] ?? 0),
            'image' => (string) ($product['image_url'] ?? $product['thumbnail'] ?? ''),
            'color' => (string) ($product['variant_color'] ?? ''),
            'size' => (string) ($product['variant_size'] ?? ''),
            'material' => (string) ($product['material'] ?? ''),
            'shape' => (string) ($product['shape'] ?? ''),
        ];
    }
}

if (!function_exists('pf_lens_catalog')) {
    function pf_lens_catalog(): array
    {
        return [
            'single_vision' => [
                'name' => 'Đơn tròng',
                'subtitle' => 'Nhìn xa hoặc nhìn gần',
                'price' => 450000,
                'icon' => 'fi fi-rr-eye',
                'tag' => 'Cơ bản',
                'note' => 'Phù hợp với phần lớn đơn kính cận/viễn/loạn thị.',
            ],
            'blue_light' => [
                'name' => 'Chống ánh sáng xanh',
                'subtitle' => 'Bảo vệ mắt khi dùng màn hình',
                'price' => 850000,
                'icon' => 'fi fi-rr-laptop',
                'tag' => 'Phổ biến',
                'note' => 'Phù hợp người dùng máy tính, điện thoại nhiều.',
            ],
            'photochromic' => [
                'name' => 'Đổi màu',
                'subtitle' => 'Tự đổi màu khi ra nắng',
                'price' => 1250000,
                'icon' => 'fi fi-rr-sun',
                'tag' => 'Ngoài trời',
                'note' => 'Phù hợp khi di chuyển trong nhà - ngoài trời.',
            ],
            'progressive' => [
                'name' => 'Đa tròng',
                'subtitle' => 'Nhìn xa, trung gian và gần',
                'price' => 2500000,
                'icon' => 'fi fi-rr-glasses',
                'tag' => 'Cao cấp',
                'note' => 'Phù hợp người lớn tuổi hoặc cần nhiều vùng nhìn.',
            ],
        ];
    }
}

if (!function_exists('pf_index_options')) {
    function pf_index_options(): array
    {
        return [
            '1.56' => ['name' => '1.56', 'label' => 'Tiêu chuẩn', 'price' => 0, 'desc' => 'Độ dày tiêu chuẩn, phù hợp độ cận nhẹ.'],
            '1.60' => ['name' => '1.60', 'label' => 'Mỏng', 'price' => 450000, 'desc' => 'Mỏng hơn khoảng 20%, chống va đập tốt hơn.'],
            '1.67' => ['name' => '1.67', 'label' => 'Siêu mỏng', 'price' => 950000, 'desc' => 'Mỏng hơn khoảng 30%, phù hợp độ cận vừa đến cao.'],
            '1.74' => ['name' => '1.74', 'label' => 'Cực mỏng', 'price' => 1850000, 'desc' => 'Mỏng nhất có thể, dành cho độ cận rất cao.'],
        ];
    }
}

if (!function_exists('pf_coating_options')) {
    function pf_coating_options(): array
    {
        return [
            'anti_reflective' => ['name' => 'Chống lóa', 'price' => 150000, 'desc' => 'Giảm phản chiếu, nhìn rõ hơn vào ban đêm.'],
            'scratch_resistant' => ['name' => 'Chống trầy xước', 'price' => 150000, 'desc' => 'Lớp phủ cứng bảo vệ bề mặt tròng kính.'],
            'blue_light_filter' => ['name' => 'Lọc ánh sáng xanh', 'price' => 350000, 'desc' => 'Giảm mỏi mắt khi dùng máy tính, điện thoại.'],
        ];
    }
}

if (!function_exists('pf_lens_total')) {
    function pf_lens_total(): float
    {
        $order = pf_order();
        $lens = $order['lens'] ?? [];
        $total = (float) ($lens['base_price'] ?? 0);
        $total += (float) ($lens['index_price'] ?? 0);

        foreach (($lens['coatings'] ?? []) as $coating) {
            $total += (float) ($coating['price'] ?? 0);
        }

        return $total;
    }
}

if (!function_exists('pf_subtotal')) {
    function pf_subtotal(): float
    {
        $order = pf_order();
        return (float) ($order['frame']['price'] ?? 0) + pf_lens_total();
    }
}

if (!function_exists('pf_shipping_fee')) {
    function pf_shipping_fee(): float
    {
        $order = pf_order();
        $shipping = $order['payment']['shipping_method'] ?? 'standard';

        return $shipping === 'express' ? 40000 : 0;
    }
}

if (!function_exists('pf_grand_total')) {
    function pf_grand_total(): float
    {
        return pf_subtotal() + pf_shipping_fee();
    }
}

if (!function_exists('pf_stepper')) {
    function pf_stepper(int $step, string $subStep = ''): void
    {
        $labels = [
            1 => 'Chọn gọng',
            2 => 'Đơn kính',
            3 => $subStep === 'options' ? 'Chi tiết tròng' : 'Chọn tròng',
            4 => $subStep === 'review' ? 'Xem lại' : 'Thanh toán',
        ];

        echo '<div class="pf-stepper">';
        foreach ($labels as $num => $label) {
            $state = $num < $step ? 'is-done' : ($num === $step ? 'is-active' : '');
            echo '<div class="pf-step ' . e($state) . '">';
            echo '<span>' . ($num < $step ? '<i class="fi fi-rr-check"></i>' : e((string) $num)) . '</span>';
            echo '<strong>' . e($label) . '</strong>';
            echo '</div>';
        }
        echo '</div>';
    }
}

if (!function_exists('pf_flow_header')) {
    function pf_flow_header(int $step, string $backUrl = '/', string $backText = 'Quay lại', string $subStep = ''): void
    {
        ?>
        <div class="pf-flow-wrap">
            <div class="pf-flow-top">
                <a class="pf-flow-back" href="<?= e(APP_URL . $backUrl) ?>">
                    <i class="fi fi-rr-arrow-left"></i>
                    <?= e($backText) ?>
                </a>

                <div class="pf-flow-title">
                    <span>Prescription Flow</span>
                    <strong>Đặt kính theo đơn</strong>
                </div>
            </div>

            <?php pf_stepper($step, $subStep); ?>
        </div>
        <?php
    }
}

if (!function_exists('pf_summary')) {
    function pf_summary(string $buttonText = '', string $buttonHref = '', bool $disabled = false): void
    {
        $order = pf_order();
        $frame = $order['frame'] ?? null;
        $rx = $order['rx'] ?? null;
        $lens = $order['lens'] ?? null;
        ?>
        <aside class="pf-summary">
            <div class="pf-summary-head">
                <span><i class="fi fi-rr-shopping-cart"></i></span>
                <h3>Tóm tắt đơn hàng</h3>
            </div>

            <div class="pf-summary-section">
                <div class="pf-summary-title">
                    <strong>1. Gọng kính</strong>
                    <?php if ($frame): ?><a href="<?= e(APP_URL) ?>/prescription-start.php">Thay đổi</a><?php endif; ?>
                </div>

                <?php if ($frame): ?>
                    <div class="pf-mini-card">
                        <img src="<?= e(pf_image($frame['image'] ?? '')) ?>" alt="<?= e($frame['name']) ?>">
                        <div>
                            <span><?= e($frame['brand'] ?? 'LUMINA') ?></span>
                            <strong><?= e($frame['name']) ?></strong>
                            <small><?= e(trim(($frame['color'] ?? '') . ' ' . ($frame['size'] ?? ''))) ?></small>
                        </div>
                        <b><?= e(pf_money($frame['price'] ?? 0)) ?></b>
                    </div>
                <?php else: ?>
                    <div class="pf-empty-line">Sẽ chọn ở bước này</div>
                <?php endif; ?>
            </div>

            <div class="pf-summary-section">
                <div class="pf-summary-title">
                    <strong>2. Đơn kính</strong>
                    <?php if ($rx): ?><a href="<?= e(APP_URL) ?>/prescription-method.php">Sửa</a><?php endif; ?>
                </div>

                <?php if ($rx): ?>
                    <div class="pf-mini-info">
                        <strong><?= e($rx['method_label'] ?? 'Đã nhập đơn kính') ?></strong>
                        <?php if (!empty($rx['pd'])): ?><span>PD: <?= e((string) $rx['pd']) ?>mm</span><?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="pf-empty-line">Sẽ chọn ở bước sau</div>
                <?php endif; ?>
            </div>

            <div class="pf-summary-section">
                <div class="pf-summary-title">
                    <strong>3. Tròng kính</strong>
                    <?php if ($lens): ?><a href="<?= e(APP_URL) ?>/prescription-lens.php">Sửa</a><?php endif; ?>
                </div>

                <?php if ($lens): ?>
                    <div class="pf-mini-info">
                        <strong><?= e($lens['name'] ?? 'Tròng kính') ?></strong>
                        <span><?= e($lens['index_name'] ?? 'Chưa chọn chiết suất') ?></span>
                    </div>
                <?php else: ?>
                    <div class="pf-empty-line is-locked"><i class="fi fi-rr-lock"></i> Sẽ chọn ở bước sau</div>
                <?php endif; ?>
            </div>

            <div class="pf-total-row">
                <span>Tạm tính</span>
                <strong><?= e(pf_money(pf_subtotal())) ?></strong>
            </div>

            <?php if ($buttonText): ?>
                <?php if ($buttonHref): ?>
                    <a class="pf-summary-btn <?= $disabled ? 'is-disabled' : '' ?>" href="<?= e(APP_URL . $buttonHref) ?>">
                        <?= e($buttonText) ?> <i class="fi fi-rr-arrow-right"></i>
                    </a>
                <?php else: ?>
                    <button class="pf-summary-btn" type="submit" <?= $disabled ? 'disabled' : '' ?>>
                        <?= e($buttonText) ?> <i class="fi fi-rr-arrow-right"></i>
                    </button>
                <?php endif; ?>
            <?php endif; ?>

            <small class="pf-safe"><i class="fi fi-rr-shield-check"></i> Thanh toán bảo mật</small>
        </aside>
        <?php
    }
}

if (!function_exists('pf_safe_decimal')) {
    function pf_safe_decimal($value): ?float
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }
}

if (!function_exists('pf_safe_int')) {
    function pf_safe_int($value): ?int
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }
}

<?php
require_once __DIR__ . '/../app/config/config.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/helpers/prescription_flow.php';

pf_require_frame();

$errors = [];

$form = [
    'lens_type' => 'single_vision',
    'r_sph' => '',
    'r_cyl' => '',
    'r_axis' => '',
    'r_add' => '',
    'l_sph' => '',
    'l_cyl' => '',
    'l_axis' => '',
    'l_add' => '',
    'pd_type' => 'single',
    'pd' => '',
    'pd_right' => '',
    'pd_left' => '',
    'note' => '',
];

if (!function_exists('pf_manual_has_value')) {
    function pf_manual_has_value($value): bool
    {
        return trim((string) $value) !== '';
    }
}

if (!function_exists('pf_manual_decimal')) {
    function pf_manual_decimal(mixed $value, string $label, array &$errors, bool $required = false, ?float $min = null, ?float $max = null): ?float
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            if ($required) {
                $errors[] = $label . ' không được để trống.';
            }

            return null;
        }

        $normalized = str_replace(',', '.', str_replace(' ', '', $raw));

        if (!preg_match('/^[+-]?(?:\d+(?:\.\d+)?|\.\d+)$/', $normalized)) {
            $errors[] = $label . ' phải là số hợp lệ, ví dụ -2.50 hoặc +1.25.';
            return null;
        }

        $number = round((float) $normalized, 2);

        if ($min !== null && $number < $min) {
            $errors[] = $label . ' không được nhỏ hơn ' . $min . '.';
        }

        if ($max !== null && $number > $max) {
            $errors[] = $label . ' không được lớn hơn ' . $max . '.';
        }

        return $number;
    }
}

if (!function_exists('pf_manual_axis')) {
    function pf_manual_axis($value, string $label, array &$errors, bool $required = false): ?int
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            if ($required) {
                $errors[] = $label . ' không được để trống khi có CYL.';
            }

            return null;
        }

        if (!preg_match('/^\d{1,3}$/', $raw)) {
            $errors[] = $label . ' phải là số nguyên từ 1 đến 180.';
            return null;
        }

        $axis = (int) $raw;

        if ($axis < 1 || $axis > 180) {
            $errors[] = $label . ' phải nằm trong khoảng 1 đến 180.';
        }

        return $axis;
    }
}

if (!function_exists('pf_manual_abs_non_zero')) {
    function pf_manual_abs_non_zero(?float $value): bool
    {
        return $value !== null && abs($value) > 0.00001;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $key => $defaultValue) {
        $form[$key] = trim((string) ($_POST[$key] ?? $defaultValue));
    }

    if (!in_array($form['lens_type'], ['single_vision', 'progressive', 'bifocal'], true)) {
        $form['lens_type'] = 'single_vision';
    }

    if (!in_array($form['pd_type'], ['single', 'dual'], true)) {
        $form['pd_type'] = 'single';
    }

    /*
     * Business validation:
     * - SPH bắt buộc cho cả hai mắt; nếu mắt không độ thì nhập 0.00.
     * - CYL và AXIS phải đi cùng nhau nếu có loạn thị.
     * - AXIS là số nguyên từ 1 đến 180.
     * - ADD chỉ bắt buộc với progressive/bifocal.
     * - Single PD dùng một số; Dual PD dùng PD phải + PD trái.
     */
    $rSph = pf_manual_decimal($form['r_sph'], 'SPH mắt phải OD', $errors, true, -30, 30);
    $lSph = pf_manual_decimal($form['l_sph'], 'SPH mắt trái OS', $errors, true, -30, 30);

    $rCyl = pf_manual_decimal($form['r_cyl'], 'CYL mắt phải OD', $errors, false, -10, 10);
    $lCyl = pf_manual_decimal($form['l_cyl'], 'CYL mắt trái OS', $errors, false, -10, 10);

    $rAxisRequired = pf_manual_abs_non_zero($rCyl);
    $lAxisRequired = pf_manual_abs_non_zero($lCyl);

    $rAxis = pf_manual_axis($form['r_axis'], 'AXIS mắt phải OD', $errors, $rAxisRequired);
    $lAxis = pf_manual_axis($form['l_axis'], 'AXIS mắt trái OS', $errors, $lAxisRequired);

    if (!$rAxisRequired && pf_manual_has_value($form['r_axis'])) {
        $errors[] = 'AXIS mắt phải OD chỉ nhập khi có CYL mắt phải.';
    }

    if (!$lAxisRequired && pf_manual_has_value($form['l_axis'])) {
        $errors[] = 'AXIS mắt trái OS chỉ nhập khi có CYL mắt trái.';
    }

    $addRequired = in_array($form['lens_type'], ['progressive', 'bifocal'], true);
    $rAdd = pf_manual_decimal($form['r_add'], 'ADD mắt phải OD', $errors, $addRequired, 0.50, 4.00);
    $lAdd = pf_manual_decimal($form['l_add'], 'ADD mắt trái OS', $errors, $addRequired, 0.50, 4.00);

    if (!$addRequired) {
        // Với đơn tròng, ADD không bắt buộc. Nếu nhập thì vẫn lưu lại để admin kiểm tra.
        $rAdd = pf_manual_has_value($form['r_add'])
            ? pf_manual_decimal($form['r_add'], 'ADD mắt phải OD', $errors, false, 0.50, 4.00)
            : null;

        $lAdd = pf_manual_has_value($form['l_add'])
            ? pf_manual_decimal($form['l_add'], 'ADD mắt trái OS', $errors, false, 0.50, 4.00)
            : null;
    }

    $pdSingle = null;
    $pdRight = null;
    $pdLeft = null;
    $pdTotal = null;
    $pdDisplay = '';

    if ($form['pd_type'] === 'single') {
        $pdSingle = pf_manual_decimal($form['pd'], 'Single PD', $errors, true, 40, 80);
        $pdTotal = $pdSingle;
        $pdDisplay = $pdSingle !== null ? number_format($pdSingle, 1, '.', '') . ' mm' : '';
    } else {
        $pdRight = pf_manual_decimal($form['pd_right'], 'PD mắt phải OD', $errors, true, 20, 40);
        $pdLeft = pf_manual_decimal($form['pd_left'], 'PD mắt trái OS', $errors, true, 20, 40);

        if ($pdRight !== null && $pdLeft !== null) {
            $pdTotal = round($pdRight + $pdLeft, 2);

            if ($pdTotal < 40 || $pdTotal > 80) {
                $errors[] = 'Tổng Dual PD phải nằm trong khoảng 40 đến 80 mm.';
            }

            $pdDisplay = number_format($pdRight, 1, '.', '') . '/' . number_format($pdLeft, 1, '.', '') . ' mm';
        }
    }

    if (!$errors) {
        $lensTypeLabels = [
            'single_vision' => 'Đơn tròng',
            'progressive' => 'Đa tròng',
            'bifocal' => 'Hai tròng',
        ];

        $order =& pf_order();
        $order['rx'] = [
            'method' => 'manual',
            'method_label' => 'Nhập tay thông số',
            'lens_type' => $form['lens_type'],
            'lens_type_label' => $lensTypeLabels[$form['lens_type']] ?? 'Đơn tròng',

            'r_sph' => $rSph,
            'r_cyl' => $rCyl,
            'r_axis' => $rAxis,
            'r_add' => $rAdd,

            'l_sph' => $lSph,
            'l_cyl' => $lCyl,
            'l_axis' => $lAxis,
            'l_add' => $lAdd,

            'pd_type' => $form['pd_type'],
            'pd' => $pdTotal,
            'pd_single' => $pdSingle,
            'pd_right' => $pdRight,
            'pd_left' => $pdLeft,
            'pd_display' => $pdDisplay,

            'note' => $form['note'],
        ];

        pf_redirect('/prescription-lens.php');
    }
}

$pageTitle = 'Nhập thông số đơn kính - ' . APP_NAME;
$pageStyles = [APP_URL . '/assets/css/prescription-flow.css?v=2.0.1'];
require_once BASE_PATH . '/app/views/partials/head.php';
require_once BASE_PATH . '/app/views/partials/header.php';
?>

<main class="pf-page pf-integrated-page">
    <section class="pf-main">
        <?php pf_flow_header(2, '/prescription-method.php', 'Quay lại phương thức nhập'); ?>

        <div class="pf-grid checkout-layout">
            <form method="post" novalidate>
                <div class="pf-title">
                    <h1>Nhập thông số đơn kính</h1>
                    <p>
                        Nhập đúng dấu cộng/trừ theo đơn kính. Nếu mắt không có độ, hãy nhập 0.00 thay vì bỏ trống.
                    </p>
                </div>

                <?php if ($errors): ?>
                    <div class="pf-error">
                        <?php foreach ($errors as $error): ?>
                            <p><?= e($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="pf-panel">
                    <h2>1. Loại thấu kính</h2>

                    <div class="pf-segment">
                        <label>
                            <input type="radio" name="lens_type" value="single_vision" <?= $form['lens_type'] === 'single_vision' ? 'checked' : '' ?>>
                            <span>Đơn tròng</span>
                        </label>

                        <label>
                            <input type="radio" name="lens_type" value="progressive" <?= $form['lens_type'] === 'progressive' ? 'checked' : '' ?>>
                            <span>Đa tròng</span>
                        </label>

                        <label>
                            <input type="radio" name="lens_type" value="bifocal" <?= $form['lens_type'] === 'bifocal' ? 'checked' : '' ?>>
                            <span>Hai tròng</span>
                        </label>
                    </div>

                    <p class="pf-helper-text">
                        ADD chỉ bắt buộc khi chọn Đa tròng hoặc Hai tròng. Với Đơn tròng, ADD có thể để trống.
                    </p>
                </div>

                <div class="pf-panel">
                    <div class="pf-review-head">
                        <div>
                            <h2>2. Thông số kỹ thuật</h2>
                            <p class="pf-helper-text no-margin">
                                CYL và AXIS phải đi cùng nhau. Nếu không có loạn thị, để CYL và AXIS trống.
                            </p>
                        </div>
                    </div>

                    <div class="pf-rx-table-wrap">
                        <table class="pf-table pf-rx-table">
                            <thead>
                                <tr>
                                    <th>Mắt</th>
                                    <th>SPH <small>Bắt buộc</small></th>
                                    <th>CYL</th>
                                    <th>AXIS</th>
                                    <th>ADD</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Mắt phải OD</strong></td>
                                    <td>
                                        <input name="r_sph" value="<?= e($form['r_sph']) ?>" placeholder="-2.50 hoặc +1.25" class="pf-table-input" inputmode="decimal" required>
                                    </td>
                                    <td>
                                        <input name="r_cyl" value="<?= e($form['r_cyl']) ?>" placeholder="-0.50" class="pf-table-input" inputmode="decimal">
                                    </td>
                                    <td>
                                        <input name="r_axis" value="<?= e($form['r_axis']) ?>" placeholder="1-180" class="pf-table-input" inputmode="numeric">
                                    </td>
                                    <td>
                                        <input name="r_add" value="<?= e($form['r_add']) ?>" placeholder="+1.00" class="pf-table-input" inputmode="decimal">
                                    </td>
                                </tr>

                                <tr>
                                    <td><strong>Mắt trái OS</strong></td>
                                    <td>
                                        <input name="l_sph" value="<?= e($form['l_sph']) ?>" placeholder="-2.25 hoặc 0.00" class="pf-table-input" inputmode="decimal" required>
                                    </td>
                                    <td>
                                        <input name="l_cyl" value="<?= e($form['l_cyl']) ?>" placeholder="-0.75" class="pf-table-input" inputmode="decimal">
                                    </td>
                                    <td>
                                        <input name="l_axis" value="<?= e($form['l_axis']) ?>" placeholder="1-180" class="pf-table-input" inputmode="numeric">
                                    </td>
                                    <td>
                                        <input name="l_add" value="<?= e($form['l_add']) ?>" placeholder="+1.00" class="pf-table-input" inputmode="decimal">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="pf-info-note">
                        <strong>Ví dụ:</strong> Nếu đơn kính ghi OD -2.50 -0.50 x 180, hãy nhập SPH = -2.50, CYL = -0.50, AXIS = 180.
                    </div>
                </div>

                <div class="pf-panel">
                    <h2>3. Thông số PD</h2>

                    <div class="pf-segment pf-pd-type">
                        <label>
                            <input type="radio" name="pd_type" value="single" <?= $form['pd_type'] === 'single' ? 'checked' : '' ?>>
                            <span>Single PD</span>
                        </label>

                        <label>
                            <input type="radio" name="pd_type" value="dual" <?= $form['pd_type'] === 'dual' ? 'checked' : '' ?>>
                            <span>Dual PD</span>
                        </label>
                    </div>

                    <div class="pf-pd-area">
                        <div class="pf-pd-single">
                            <label class="pf-field">
                                <span>Single PD</span>
                                <input type="text" name="pd" value="<?= e($form['pd']) ?>" placeholder="Ví dụ: 62" inputmode="decimal">
                            </label>
                        </div>

                        <div class="pf-pd-dual">
                            <div class="pf-form-row">
                                <label class="pf-field">
                                    <span>PD mắt phải OD</span>
                                    <input type="text" name="pd_right" value="<?= e($form['pd_right']) ?>" placeholder="Ví dụ: 32" inputmode="decimal">
                                </label>

                                <label class="pf-field">
                                    <span>PD mắt trái OS</span>
                                    <input type="text" name="pd_left" value="<?= e($form['pd_left']) ?>" placeholder="Ví dụ: 30" inputmode="decimal">
                                </label>
                            </div>
                        </div>
                    </div>

                    <p class="pf-helper-text">
                        Single PD là một số tổng, ví dụ 62. Dual PD gồm hai số, ví dụ 32/30, trong đó số đầu là mắt phải OD và số sau là mắt trái OS.
                    </p>
                </div>

                <div class="pf-panel">
                    <label class="pf-field">
                        <span>Ghi chú thêm</span>
                        <textarea name="note" placeholder="Ví dụ: Tôi muốn shop kiểm tra lại thông số loạn thị trước khi cắt tròng."><?= e($form['note']) ?></textarea>
                    </label>
                </div>

                <button class="pf-summary-btn" type="submit">
                    Lưu thông số & tiếp tục
                    <i class="fi fi-rr-arrow-right"></i>
                </button>
            </form>

            <?php pf_summary(); ?>
        </div>
    </section>
</main>

<?php require_once BASE_PATH . '/app/views/partials/footer.php'; ?>

<style>
    .pf-helper-text {
        margin: 14px 0 0;
        color: #64748B;
        line-height: 1.65;
        font-size: 14px;
    }

    .pf-helper-text.no-margin {
        margin: 5px 0 0;
    }

    .pf-rx-table-wrap {
        overflow-x: auto;
    }

    .pf-rx-table {
        min-width: 760px;
    }

    .pf-rx-table th small {
        display: block;
        margin-top: 3px;
        color: #0F8B67;
        font-size: 10px;
    }

    .pf-table-input {
        width: 100%;
        min-height: 42px;
        border: 1px solid #E5E7EB;
        border-radius: 12px;
        background: #F8FAFC;
        padding: 0 10px;
        color: #111827;
        outline: 0;
    }

    .pf-table-input:focus {
        border-color: #0F8B67;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(15, 139, 103, .08);
    }

    .pf-pd-dual {
        display: none;
    }

    body:has(input[name="pd_type"][value="dual"]:checked) .pf-pd-single {
        display: none;
    }

    body:has(input[name="pd_type"][value="dual"]:checked) .pf-pd-dual {
        display: block;
    }

    @supports not selector(:has(*)) {
        .pf-pd-dual {
            display: block;
        }
    }

    .pf-error p {
        margin: 0;
    }

    .pf-error p + p {
        margin-top: 6px;
    }
</style>

<script>
    (function () {
        const radios = document.querySelectorAll('input[name="pd_type"]');
        const single = document.querySelector('.pf-pd-single');
        const dual = document.querySelector('.pf-pd-dual');

        function syncPdMode() {
            const checked = document.querySelector('input[name="pd_type"]:checked');
            const isDual = checked && checked.value === 'dual';

            if (single) single.style.display = isDual ? 'none' : 'block';
            if (dual) dual.style.display = isDual ? 'block' : 'none';
        }

        radios.forEach((radio) => radio.addEventListener('change', syncPdMode));
        syncPdMode();
    })();
</script>

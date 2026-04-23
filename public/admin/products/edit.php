<?php
require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/helpers/functions.php';

$db = Database::connect();

$productId = (int) ($_GET['id'] ?? 0);
$isEdit = $productId > 0;

$product = [
    'id' => 0,
    'category_id' => 0,
    'name' => '',
    'slug' => '',
    'brand' => 'LUMINA',
    'short_description' => '',
    'description' => '',
    'frame_type' => '',
    'target_gender' => 'unisex',
    'material' => '',
    'shape' => '',
    'default_price' => 0,
    'compare_at_price' => '',
    'thumbnail' => '',
    'is_prescription_supported' => 0,
    'has_3d_model' => 0,
    'status' => 'draft',
];

if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $productId]);
    $found = $stmt->fetch();

    if (!$found) {
        flash_set('error', 'Không tìm thấy sản phẩm cần chỉnh sửa.');
        header('Location: ' . APP_URL . '/admin/products/index.php');
        exit;
    }

    $product = array_merge($product, $found);
}

$oldInput = flash_get('product_form_old');
$formErrors = flash_get('product_form_errors', []);
if (is_array($oldInput) && $oldInput) {
    $product = array_merge($product, $oldInput);
}

$categoriesStmt = $db->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
$categories = $categoriesStmt->fetchAll();

$pageTitle = $isEdit ? 'Sửa sản phẩm' : 'Tạo sản phẩm';
$pageDescription = $isEdit ? 'Cập nhật thông tin sản phẩm trong catalog.' : 'Tạo mới sản phẩm trong catalog.';
$adminPageTitle = $isEdit ? 'Sửa sản phẩm' : 'Tạo sản phẩm';
$adminPageSubtitle = $isEdit ? 'Điều chỉnh thông tin hiển thị, giá bán và trạng thái sản phẩm.' : 'Thêm nhanh một sản phẩm mới vào catalog.';
$adminPrimaryAction = [
    'href' => APP_URL . '/admin/products/index.php',
    'label' => 'Về danh sách',
    'icon' => 'fi fi-rr-arrow-left',
];
$adminSecondaryAction = [
    'href' => APP_URL . '/admin',
    'label' => 'Dashboard',
    'icon' => 'fi fi-rr-apps',
];

require BASE_PATH . '/app/views/partials/admin-head.php';
?>
<div class="admin-shell">
    <?php require BASE_PATH . '/app/views/partials/admin-sidebar.php'; ?>

    <main class="admin-main">
        <?php require BASE_PATH . '/app/views/partials/admin-topbar.php'; ?>

        <div class="admin-dashboard">
            <?php if ($formErrors): ?>
                <div class="alert warning">
                    <strong>Vui lòng kiểm tra lại thông tin:</strong>
                    <ul class="form-error-list">
                        <?php foreach ($formErrors as $error): ?>
                            <li><?= e((string) $error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <section class="admin-panel">
                <div class="admin-panel-head compact">
                    <div>
                        <span class="admin-panel-kicker">PRODUCT FORM</span>
                        <h2><?= $isEdit ? 'Thông tin sản phẩm' : 'Tạo sản phẩm mới' ?></h2>
                        <p>Điền các trường cơ bản để quản lý catalog và hiển thị storefront.</p>
                    </div>
                </div>

                <form method="post" action="<?= e(APP_URL) ?>/admin/products/save.php" class="form-grid two-cols">
                    <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">

                    <div class="form-field">
                        <label for="category_id">Danh mục</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Chọn danh mục</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category['id'] ?>" <?= (int) $product['category_id'] === (int) $category['id'] ? 'selected' : '' ?>>
                                    <?= e((string) $category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="brand">Thương hiệu</label>
                        <input id="brand" type="text" name="brand" value="<?= e((string) $product['brand']) ?>" placeholder="LUMINA">
                    </div>

                    <div class="form-field full-width">
                        <label for="name">Tên sản phẩm</label>
                        <input id="name" type="text" name="name" value="<?= e((string) $product['name']) ?>" required>
                    </div>

                    <div class="form-field">
                        <label for="slug">Slug</label>
                        <input id="slug" type="text" name="slug" value="<?= e((string) $product['slug']) ?>" placeholder="de-trong-he-thong-tu-tao-neu-de-trong">
                    </div>

                    <div class="form-field">
                        <label for="thumbnail">Ảnh đại diện (URL)</label>
                        <input id="thumbnail" type="text" name="thumbnail" value="<?= e((string) $product['thumbnail']) ?>" placeholder="https://...">
                    </div>

                    <div class="form-field full-width">
                        <label for="short_description">Mô tả ngắn</label>
                        <textarea id="short_description" name="short_description" rows="3"><?= e((string) $product['short_description']) ?></textarea>
                    </div>

                    <div class="form-field full-width">
                        <label for="description">Mô tả chi tiết</label>
                        <textarea id="description" name="description" rows="6"><?= e((string) $product['description']) ?></textarea>
                    </div>

                    <div class="form-field">
                        <label for="frame_type">Loại gọng / frame type</label>
                        <input id="frame_type" type="text" name="frame_type" value="<?= e((string) $product['frame_type']) ?>" placeholder="full-rim, half-rim...">
                    </div>

                    <div class="form-field">
                        <label for="target_gender">Đối tượng</label>
                        <select id="target_gender" name="target_gender">
                            <option value="unisex" <?= (string) $product['target_gender'] === 'unisex' ? 'selected' : '' ?>>Unisex</option>
                            <option value="male" <?= (string) $product['target_gender'] === 'male' ? 'selected' : '' ?>>Nam</option>
                            <option value="female" <?= (string) $product['target_gender'] === 'female' ? 'selected' : '' ?>>Nữ</option>
                            <option value="kids" <?= (string) $product['target_gender'] === 'kids' ? 'selected' : '' ?>>Trẻ em</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="material">Chất liệu</label>
                        <input id="material" type="text" name="material" value="<?= e((string) $product['material']) ?>" placeholder="Nhựa, kim loại, acetate...">
                    </div>

                    <div class="form-field">
                        <label for="shape">Dáng kính</label>
                        <input id="shape" type="text" name="shape" value="<?= e((string) $product['shape']) ?>" placeholder="Oval, vuông, mắt mèo...">
                    </div>

                    <div class="form-field">
                        <label for="default_price">Giá bán</label>
                        <input id="default_price" type="number" min="0" step="1000" name="default_price" value="<?= e((string) $product['default_price']) ?>" required>
                    </div>

                    <div class="form-field">
                        <label for="compare_at_price">Giá gốc</label>
                        <input id="compare_at_price" type="number" min="0" step="1000" name="compare_at_price" value="<?= e((string) $product['compare_at_price']) ?>">
                    </div>

                    <div class="form-field">
                        <label for="status">Trạng thái</label>
                        <select id="status" name="status">
                            <option value="draft" <?= (string) $product['status'] === 'draft' ? 'selected' : '' ?>>Nháp</option>
                            <option value="active" <?= (string) $product['status'] === 'active' ? 'selected' : '' ?>>Đang bán</option>
                            <option value="inactive" <?= (string) $product['status'] === 'inactive' ? 'selected' : '' ?>>Tạm ẩn</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label>&nbsp;</label>
                        <div class="admin-checkbox-group">
                            <label class="admin-check">
                                <input type="checkbox" name="is_prescription_supported" value="1" <?= (int) $product['is_prescription_supported'] === 1 ? 'checked' : '' ?>>
                                <span>Hỗ trợ prescription</span>
                            </label>

                            <label class="admin-check">
                                <input type="checkbox" name="has_3d_model" value="1" <?= (int) $product['has_3d_model'] === 1 ? 'checked' : '' ?>>
                                <span>Có model 3D</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-field full-width form-field-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fi fi-rr-disk icon"></i>
                            <span><?= $isEdit ? 'Lưu thay đổi' : 'Tạo sản phẩm' ?></span>
                        </button>

                        <a href="<?= e(APP_URL) ?>/admin/products/index.php" class="btn btn-secondary">
                            <i class="fi fi-rr-cross icon"></i>
                            <span>Hủy</span>
                        </a>
                    </div>
                </form>
            </section>
        </div>
    </main>
</div>
</body>
</html>

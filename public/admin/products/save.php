<?php
require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/helpers/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_URL . '/admin/products/index.php');
    exit;
}

$db = Database::connect();
$id = (int) ($_POST['id'] ?? 0);

$data = [
    'id' => $id,
    'category_id' => (int) ($_POST['category_id'] ?? 0),
    'name' => trim((string) ($_POST['name'] ?? '')),
    'slug' => trim((string) ($_POST['slug'] ?? '')),
    'brand' => trim((string) ($_POST['brand'] ?? 'LUMINA')),
    'short_description' => trim((string) ($_POST['short_description'] ?? '')),
    'description' => trim((string) ($_POST['description'] ?? '')),
    'frame_type' => trim((string) ($_POST['frame_type'] ?? '')),
    'target_gender' => trim((string) ($_POST['target_gender'] ?? 'unisex')),
    'material' => trim((string) ($_POST['material'] ?? '')),
    'shape' => trim((string) ($_POST['shape'] ?? '')),
    'default_price' => (float) ($_POST['default_price'] ?? 0),
    'compare_at_price' => trim((string) ($_POST['compare_at_price'] ?? '')),
    'thumbnail' => trim((string) ($_POST['thumbnail'] ?? '')),
    'is_prescription_supported' => isset($_POST['is_prescription_supported']) ? 1 : 0,
    'has_3d_model' => isset($_POST['has_3d_model']) ? 1 : 0,
    'status' => trim((string) ($_POST['status'] ?? 'draft')),
];

$errors = [];

if ($data['category_id'] <= 0) {
    $errors[] = 'Vui lòng chọn danh mục.';
}

if ($data['name'] === '') {
    $errors[] = 'Tên sản phẩm không được để trống.';
}

if (!in_array($data['target_gender'], ['male', 'female', 'unisex', 'kids'], true)) {
    $errors[] = 'Giá trị đối tượng không hợp lệ.';
}

if (!in_array($data['status'], ['draft', 'active', 'inactive'], true)) {
    $errors[] = 'Trạng thái sản phẩm không hợp lệ.';
}

if ($data['default_price'] < 0) {
    $errors[] = 'Giá bán không hợp lệ.';
}

if ($data['compare_at_price'] !== '' && (float) $data['compare_at_price'] < 0) {
    $errors[] = 'Giá gốc không hợp lệ.';
}

$generatedSlug = slugify($data['slug'] !== '' ? $data['slug'] : $data['name']);
$data['slug'] = $generatedSlug;

$slugCheckSql = "SELECT id FROM products WHERE slug = :slug";
$slugParams = ['slug' => $data['slug']];
if ($id > 0) {
    $slugCheckSql .= " AND id <> :id";
    $slugParams['id'] = $id;
}
$slugStmt = $db->prepare($slugCheckSql);
$slugStmt->execute($slugParams);

if ($slugStmt->fetchColumn()) {
    $errors[] = 'Slug đã tồn tại. Hãy đổi tên hoặc nhập slug khác.';
}

if ($errors) {
    flash_set('product_form_old', $data);
    flash_set('product_form_errors', $errors);
    $redirect = APP_URL . '/admin/products/edit.php' . ($id > 0 ? '?id=' . $id : '');
    header('Location: ' . $redirect);
    exit;
}

$compareAtPrice = $data['compare_at_price'] === '' ? null : (float) $data['compare_at_price'];

try {
    if ($id > 0) {
        $stmt = $db->prepare(
            "UPDATE products
             SET category_id = :category_id,
                 name = :name,
                 slug = :slug,
                 brand = :brand,
                 short_description = :short_description,
                 description = :description,
                 frame_type = :frame_type,
                 target_gender = :target_gender,
                 material = :material,
                 shape = :shape,
                 default_price = :default_price,
                 compare_at_price = :compare_at_price,
                 thumbnail = :thumbnail,
                 is_prescription_supported = :is_prescription_supported,
                 has_3d_model = :has_3d_model,
                 status = :status,
                 updated_at = NOW()
             WHERE id = :id"
        );

        $stmt->execute([
            'id' => $id,
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'slug' => $data['slug'],
            'brand' => $data['brand'] !== '' ? $data['brand'] : null,
            'short_description' => $data['short_description'] !== '' ? $data['short_description'] : null,
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'frame_type' => $data['frame_type'] !== '' ? $data['frame_type'] : null,
            'target_gender' => $data['target_gender'],
            'material' => $data['material'] !== '' ? $data['material'] : null,
            'shape' => $data['shape'] !== '' ? $data['shape'] : null,
            'default_price' => $data['default_price'],
            'compare_at_price' => $compareAtPrice,
            'thumbnail' => $data['thumbnail'] !== '' ? $data['thumbnail'] : null,
            'is_prescription_supported' => $data['is_prescription_supported'],
            'has_3d_model' => $data['has_3d_model'],
            'status' => $data['status'],
        ]);

        flash_set('success', 'Đã cập nhật sản phẩm thành công.');
    } else {
        $stmt = $db->prepare(
            "INSERT INTO products (
                category_id,
                name,
                slug,
                brand,
                short_description,
                description,
                frame_type,
                target_gender,
                material,
                shape,
                default_price,
                compare_at_price,
                thumbnail,
                is_prescription_supported,
                has_3d_model,
                status,
                created_at,
                updated_at
            ) VALUES (
                :category_id,
                :name,
                :slug,
                :brand,
                :short_description,
                :description,
                :frame_type,
                :target_gender,
                :material,
                :shape,
                :default_price,
                :compare_at_price,
                :thumbnail,
                :is_prescription_supported,
                :has_3d_model,
                :status,
                NOW(),
                NOW()
            )"
        );

        $stmt->execute([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'slug' => $data['slug'],
            'brand' => $data['brand'] !== '' ? $data['brand'] : null,
            'short_description' => $data['short_description'] !== '' ? $data['short_description'] : null,
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'frame_type' => $data['frame_type'] !== '' ? $data['frame_type'] : null,
            'target_gender' => $data['target_gender'],
            'material' => $data['material'] !== '' ? $data['material'] : null,
            'shape' => $data['shape'] !== '' ? $data['shape'] : null,
            'default_price' => $data['default_price'],
            'compare_at_price' => $compareAtPrice,
            'thumbnail' => $data['thumbnail'] !== '' ? $data['thumbnail'] : null,
            'is_prescription_supported' => $data['is_prescription_supported'],
            'has_3d_model' => $data['has_3d_model'],
            'status' => $data['status'],
        ]);

        flash_set('success', 'Đã tạo sản phẩm mới thành công.');
    }

    clear_old_input();
    header('Location: ' . APP_URL . '/admin/products/index.php');
    exit;
} catch (Throwable $exception) {
    flash_set('product_form_old', $data);
    flash_set('product_form_errors', ['Lỗi hệ thống: ' . $exception->getMessage()]);
    $redirect = APP_URL . '/admin/products/edit.php' . ($id > 0 ? '?id=' . $id : '');
    header('Location: ' . $redirect);
    exit;
}

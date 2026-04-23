<?php
require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/helpers/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_URL . '/admin/products/index.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    flash_set('error', 'Thiếu ID sản phẩm.');
    header('Location: ' . APP_URL . '/admin/products/index.php');
    exit;
}

$db = Database::connect();

$stmt = $db->prepare("SELECT status FROM products WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $id]);
$currentStatus = $stmt->fetchColumn();

if (!$currentStatus) {
    flash_set('error', 'Không tìm thấy sản phẩm.');
    header('Location: ' . APP_URL . '/admin/products/index.php');
    exit;
}

$newStatus = $currentStatus === 'active' ? 'inactive' : 'active';

$updateStmt = $db->prepare("UPDATE products SET status = :status, updated_at = NOW() WHERE id = :id");
$updateStmt->execute([
    'status' => $newStatus,
    'id' => $id,
]);

flash_set('success', 'Đã cập nhật trạng thái sản phẩm.');
header('Location: ' . APP_URL . '/admin/products/index.php');
exit;

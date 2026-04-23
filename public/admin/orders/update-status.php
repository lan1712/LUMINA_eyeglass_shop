<?php
require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/helpers/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_URL . '/admin/orders/index.php');
    exit;
}

$orderId = max(0, (int) ($_POST['order_id'] ?? 0));
$newStatus = trim((string) ($_POST['status'] ?? ''));
$note = trim((string) ($_POST['note'] ?? ''));

$allowedStatuses = [
    'pending', 'awaiting_stock', 'checking_prescription', 'confirmed', 'processing',
    'lens_processing', 'shipping', 'completed', 'cancelled', 'refunded',
];

if ($orderId <= 0 || !in_array($newStatus, $allowedStatuses, true)) {
    header('Location: ' . APP_URL . '/admin/orders/index.php?error=' . urlencode('Dữ liệu cập nhật không hợp lệ.'));
    exit;
}

$db = Database::connect();

try {
    $db->beginTransaction();

    $orderStmt = $db->prepare('SELECT id, status, handled_by FROM orders WHERE id = :id LIMIT 1');
    $orderStmt->execute(['id' => $orderId]);
    $order = $orderStmt->fetch();

    if (!$order) {
        throw new RuntimeException('Không tìm thấy đơn hàng cần cập nhật.');
    }

    $oldStatus = (string) $order['status'];
    $handledBy = $order['handled_by'] !== null ? (int) $order['handled_by'] : first_staff_user_id($db);

    $fields = [
        'status = :status',
        'internal_note = CASE
            WHEN :note IS NULL OR :note = "" THEN internal_note
            WHEN internal_note IS NULL OR internal_note = "" THEN :note
            ELSE CONCAT(internal_note, "\n", :note)
         END',
        'handled_by = :handled_by',
    ];

    if ($newStatus === 'confirmed') {
        $fields[] = 'confirmed_at = NOW()';
    }
    if ($newStatus === 'shipping') {
        $fields[] = 'shipped_at = NOW()';
    }
    if ($newStatus === 'completed') {
        $fields[] = 'completed_at = NOW()';
    }
    if ($newStatus === 'cancelled') {
        $fields[] = 'cancelled_at = NOW()';
    }

    $updateSql = 'UPDATE orders SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = :id';
    $updateStmt = $db->prepare($updateSql);
    $updateStmt->execute([
        'status' => $newStatus,
        'note' => $note !== '' ? $note : null,
        'handled_by' => $handledBy,
        'id' => $orderId,
    ]);

    $logStmt = $db->prepare(
        'INSERT INTO order_status_logs (order_id, changed_by, old_status, new_status, note)
         VALUES (:order_id, :changed_by, :old_status, :new_status, :note)'
    );
    $logStmt->execute([
        'order_id' => $orderId,
        'changed_by' => $handledBy,
        'old_status' => $oldStatus,
        'new_status' => $newStatus,
        'note' => $note !== '' ? $note : 'Cập nhật trạng thái từ admin.',
    ]);

    $db->commit();

    header('Location: ' . APP_URL . '/admin/orders/detail.php?id=' . $orderId . '&updated=1');
    exit;
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    header('Location: ' . APP_URL . '/admin/orders/detail.php?id=' . $orderId . '&error=' . urlencode($exception->getMessage()));
    exit;
}

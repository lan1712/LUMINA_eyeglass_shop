<?php
require_once __DIR__ . '/../app/config/config.php';
require_once BASE_PATH . '/app/helpers/functions.php';
if (file_exists(BASE_PATH . '/app/middleware/auth.php')) require_once BASE_PATH . '/app/middleware/auth.php';

if (function_exists('auth_only')) auth_only();
elseif (function_exists('require_login')) require_login();
elseif (empty($_SESSION['user'])) { header('Location: ' . APP_URL . '/login.php'); exit; }

$db = Database::connect();

function lumina_account_money($value): string { return function_exists('format_price') ? format_price((float) $value) : number_format((float) $value, 0, ',', '.') . 'đ'; }
function lumina_account_status_label(?string $status): string { return function_exists('order_status_label') ? order_status_label((string) $status) : ([ 'pending'=>'Chờ xác nhận','awaiting_stock'=>'Chờ nhập hàng','checking_prescription'=>'Kiểm tra đơn kính','confirmed'=>'Đã xác nhận','processing'=>'Đang xử lý','shipping'=>'Đang giao','completed'=>'Hoàn tất','cancelled'=>'Đã hủy' ][$status] ?? ucfirst((string) $status)); }
function lumina_account_order_type(?string $type): string { return ['available'=>'Có sẵn','preorder'=>'Pre-order','prescription'=>'Prescription'][$type] ?? ucfirst((string) $type); }
function lumina_account_date(?string $date): string { if (!$date) return '—'; try { return (new DateTime($date, new DateTimeZone('Asia/Ho_Chi_Minh')))->format('d/m/Y H:i'); } catch (Throwable $e) { return $date; } }

$sessionUser = function_exists('auth_user') ? auth_user() : ($_SESSION['auth_user'] ?? null);
if (!$sessionUser) { function_exists('redirect_to') ? redirect_to('/login.php') : header('Location: ' . APP_URL . '/login.php'); exit; }

$userStmt = $db->prepare('SELECT u.*, r.name AS role_name FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE u.id = :id LIMIT 1');
$userStmt->execute(['id' => $sessionUser['id']]);
$user = $userStmt->fetch() ?: $sessionUser;

$orderSummaryStmt = $db->prepare('SELECT COUNT(*) AS total_orders, COALESCE(SUM(total_amount), 0) AS total_spent FROM orders WHERE user_id = :user_id');
$orderSummaryStmt->execute(['user_id' => $user['id']]);
$orderSummary = $orderSummaryStmt->fetch() ?: ['total_orders' => 0, 'total_spent' => 0];

$recentOrdersStmt = $db->prepare('SELECT id, order_code, order_type, status, total_amount, created_at FROM orders WHERE user_id = :user_id ORDER BY id DESC LIMIT 5');
$recentOrdersStmt->execute(['user_id' => $user['id']]);
$recentOrders = $recentOrdersStmt->fetchAll();

$pageTitle = 'Tài khoản của tôi - ' . APP_NAME;
$pageDescription = 'Quản lý tài khoản LUMINA và theo dõi đơn hàng gần đây.';
$pageStyles = [APP_URL . '/assets/css/account-v2.css?v=1.0.0'];
require_once BASE_PATH . '/app/views/partials/head.php';
require_once BASE_PATH . '/app/views/partials/header.php';
?>
<main class="profile-v2-page">
    <section class="profile-v2-hero">
        <div class="profile-v2-container profile-v2-hero__grid">
            <div>
                <span class="profile-v2-eyebrow">MY ACCOUNT</span>
                <h1>Xin chào, <?= e($user['full_name'] ?? 'khách hàng') ?></h1>
                <p>Quản lý thông tin tài khoản, xem nhanh thống kê mua hàng và theo dõi các đơn kính gần đây.</p>
                <div class="profile-v2-actions">
                    <a href="<?= e(APP_URL) ?>/orders.php" class="profile-v2-btn profile-v2-btn--primary"><i class="fi fi-rr-receipt"></i> Đơn hàng của tôi</a>
                    <a href="<?= e(APP_URL) ?>/logout.php" class="profile-v2-btn profile-v2-btn--ghost"><i class="fi fi-rr-sign-out-alt"></i> Đăng xuất</a>
                </div>
            </div>
            <div class="profile-v2-summary-card"><span>Tổng chi tiêu</span><strong><?= e(lumina_account_money($orderSummary['total_spent'] ?? 0)) ?></strong><p><?= e((string) (int) ($orderSummary['total_orders'] ?? 0)) ?> đơn hàng đã tạo trong hệ thống.</p></div>
        </div>
    </section>
    <section class="profile-v2-section">
        <div class="profile-v2-container profile-v2-layout">
            <div class="profile-v2-main">
                <?php
                if (function_exists('get_flash')) {
                    $flashSuccessMessages = get_flash('success');
                    if (!is_iterable($flashSuccessMessages)) {
                        $flashSuccessMessages = $flashSuccessMessages === null ? [] : [$flashSuccessMessages];
                    }
                    foreach ($flashSuccessMessages as $message):
                ?>
                        <div class="profile-v2-alert profile-v2-alert--success"><?= e($message) ?></div>
                <?php endforeach; }
                ?>
                <div class="profile-v2-card">
                    <div class="profile-v2-card-head"><span class="profile-v2-eyebrow">PROFILE</span><h2>Thông tin tài khoản</h2></div>
                    <div class="profile-v2-info-grid">
                        <div class="profile-v2-info-row"><span>Vai trò</span><strong><?= e(($user['role_name'] ?? '') === 'admin' ? 'Quản trị viên' : 'Khách hàng') ?></strong></div>
                        <div class="profile-v2-info-row"><span>Email</span><strong><?= e($user['email'] ?? '—') ?></strong></div>
                        <div class="profile-v2-info-row"><span>Số điện thoại</span><strong><?= e($user['phone'] ?? '—') ?></strong></div>
                        <div class="profile-v2-info-row"><span>Ngày tạo</span><strong><?= e(lumina_account_date($user['created_at'] ?? null)) ?></strong></div>
                    </div>
                </div>
                <div class="profile-v2-card">
                    <div class="profile-v2-card-head profile-v2-card-head--split"><div><span class="profile-v2-eyebrow">RECENT ORDERS</span><h2>Đơn hàng gần đây</h2></div><a href="<?= e(APP_URL) ?>/orders.php" class="profile-v2-link">Xem tất cả <i class="fi fi-rr-arrow-right"></i></a></div>
                    <?php if (!$recentOrders): ?>
                        <div class="profile-v2-empty"><i class="fi fi-rr-shopping-bag"></i><h3>Bạn chưa có đơn hàng nào</h3><p>Hãy khám phá sản phẩm và tạo đơn hàng đầu tiên tại LUMINA.</p><a href="<?= e(APP_URL) ?>/products.php" class="profile-v2-btn profile-v2-btn--primary">Mua sắm ngay</a></div>
                    <?php else: ?>
                        <div class="profile-v2-order-list">
                            <?php foreach ($recentOrders as $order): ?>
                                <a href="<?= e(APP_URL) ?>/order-detail.php?id=<?= e((string) $order['id']) ?>" class="profile-v2-order-item">
                                    <div><strong><?= e($order['order_code']) ?></strong><span><?= e(lumina_account_order_type($order['order_type'] ?? '')) ?> · <?= e(lumina_account_date($order['created_at'] ?? null)) ?></span></div>
                                    <div class="profile-v2-order-meta"><span class="profile-v2-status profile-v2-status--<?= e($order['status'] ?? 'default') ?>"><?= e(lumina_account_status_label($order['status'] ?? '')) ?></span><strong><?= e(lumina_account_money($order['total_amount'] ?? 0)) ?></strong></div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <aside class="profile-v2-side">
                <div class="profile-v2-side-card"><span class="profile-v2-eyebrow">SUMMARY</span><h2>Tóm tắt mua hàng</h2><div class="profile-v2-mini-stats"><div><strong><?= e((string) (int) ($orderSummary['total_orders'] ?? 0)) ?></strong><span>Tổng đơn hàng</span></div><div><strong><?= e(lumina_account_money($orderSummary['total_spent'] ?? 0)) ?></strong><span>Tổng chi tiêu</span></div></div></div>
                <div class="profile-v2-side-card profile-v2-help-card"><i class="fi fi-rr-headset"></i><h3>Cần hỗ trợ?</h3><p>Liên hệ LUMINA để được hỗ trợ kiểm tra đơn hàng hoặc tư vấn chọn kính.</p><a href="<?= e(APP_URL) ?>/about.php">Về chúng tôi</a></div>
            </aside>
        </div>
    </section>
</main>
<?php require_once BASE_PATH . '/app/views/partials/footer.php'; ?>

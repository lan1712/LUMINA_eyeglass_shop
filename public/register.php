<?php
require_once __DIR__ . '/../app/config/config.php';
require_once BASE_PATH . '/app/helpers/functions.php';
if (file_exists(BASE_PATH . '/app/middleware/auth.php')) require_once BASE_PATH . '/app/middleware/auth.php';

$db = Database::connect();
$errors = [];
$form = ['full_name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $key => $value) $form[$key] = trim($_POST[$key] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if ($form['full_name'] === '') $errors[] = 'Vui lòng nhập họ tên.';
    if ($form['email'] === '' || !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
    if ($password === '' || strlen($password) < 6) $errors[] = 'Mật khẩu phải từ 6 ký tự.';
    if ($password !== $passwordConfirm) $errors[] = 'Mật khẩu nhập lại không khớp.';

    if (!$errors) {
        $exists = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $exists->execute(['email' => $form['email']]);
        if ($exists->fetch()) $errors[] = 'Email này đã được sử dụng.';
    }

    if (!$errors) {
        $roleStmt = $db->query("SELECT id, name FROM roles WHERE name = 'customer' LIMIT 1");
        $role = $roleStmt->fetch();
        if (!$role) { $db->exec("INSERT INTO roles (name) VALUES ('customer')"); $role = ['id' => (int) $db->lastInsertId(), 'name' => 'customer']; }
        $stmt = $db->prepare('INSERT INTO users (role_id, full_name, email, phone, password_hash, created_at, updated_at) VALUES (:role_id, :full_name, :email, :phone, :password_hash, NOW(), NOW())');
        $stmt->execute([':role_id' => (int) $role['id'], ':full_name' => $form['full_name'], ':email' => $form['email'], ':phone' => $form['phone'], ':password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
        $user = ['id' => (int) $db->lastInsertId(), 'role_id' => (int) $role['id'], 'role_name' => 'customer', 'full_name' => $form['full_name'], 'email' => $form['email'], 'phone' => $form['phone']];
        function_exists('login_user') ? login_user($user) : $_SESSION['user'] = $user;
        if (function_exists('add_flash')) add_flash('success', 'Tạo tài khoản thành công.');
        function_exists('redirect_to') ? redirect_to('/profile.php') : header('Location: ' . APP_URL . '/profile.php');
        exit;
    }
}

$pageTitle = 'Đăng ký - ' . APP_NAME;
$pageDescription = 'Tạo tài khoản LUMINA để lưu đơn kính và theo dõi lịch sử mua hàng.';
$pageStyles = [APP_URL . '/assets/css/account-v2.css?v=1.0.0'];
require_once BASE_PATH . '/app/views/partials/head.php';
?>
<main class="auth-v2-page">
    <section class="auth-v2-visual">
        <a href="<?= e(APP_URL) ?>/" class="auth-v2-logo"><i class="fi fi-rr-glasses"></i><span>LUMINA</span></a>
        <div class="auth-v2-benefit-card">
            <span class="auth-v2-chip"><i class="fi fi-rr-user-add"></i> Thành viên mới</span>
            <h1>Tạo tài khoản để quản lý đơn kính dễ hơn</h1>
            <div class="auth-v2-benefit-list">
                <div class="auth-v2-benefit-item"><span><i class="fi fi-rr-receipt"></i></span><div><h3>Lịch sử đơn hàng</h3><p>Xem lại mã đơn, trạng thái và tổng tiền của các đơn đã đặt.</p></div></div>
                <div class="auth-v2-benefit-item"><span><i class="fi fi-rr-eye"></i></span><div><h3>Hỗ trợ chọn kính</h3><p>Lưu thông tin cơ bản để đặt hàng nhanh hơn ở những lần sau.</p></div></div>
                <div class="auth-v2-benefit-item"><span><i class="fi fi-rr-bell"></i></span><div><h3>Cập nhật trạng thái</h3><p>Theo dõi quá trình xác nhận, xử lý, vận chuyển và hoàn tất đơn hàng.</p></div></div>
            </div>
        </div>
    </section>
    <section class="auth-v2-panel">
        <div class="auth-v2-card auth-v2-card--register">
            <div class="auth-v2-card-head"><h2>Tạo tài khoản</h2><p>Đăng ký để theo dõi đơn hàng và quản lý thông tin cá nhân</p></div>
            <div class="auth-v2-tabs"><a href="<?= e(APP_URL) ?>/login.php">Đăng nhập</a><a class="is-active" href="<?= e(APP_URL) ?>/register.php">Đăng ký</a></div>
            <?php if ($errors): ?><div class="auth-v2-alert auth-v2-alert--error"><?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?></div><?php endif; ?>
            <form method="post" class="auth-v2-form" autocomplete="on">
                <label class="auth-v2-field"><span>Họ tên</span><div class="auth-v2-input"><i class="fi fi-rr-user"></i><input type="text" name="full_name" value="<?= e($form['full_name']) ?>" placeholder="Nguyễn Văn A" required></div></label>
                <label class="auth-v2-field"><span>Email</span><div class="auth-v2-input"><i class="fi fi-rr-envelope"></i><input type="email" name="email" value="<?= e($form['email']) ?>" placeholder="you@example.com" required></div></label>
                <label class="auth-v2-field"><span>Số điện thoại</span><div class="auth-v2-input"><i class="fi fi-rr-phone-call"></i><input type="text" name="phone" value="<?= e($form['phone']) ?>" placeholder="0901234567"></div></label>
                <div class="auth-v2-two-cols">
                    <label class="auth-v2-field"><span>Mật khẩu</span><div class="auth-v2-input"><i class="fi fi-rr-lock"></i><input type="password" name="password" placeholder="Tối thiểu 6 ký tự" required></div></label>
                    <label class="auth-v2-field"><span>Nhập lại</span><div class="auth-v2-input"><i class="fi fi-rr-lock"></i><input type="password" name="password_confirm" placeholder="Nhập lại mật khẩu" required></div></label>
                </div>
                <button type="submit" class="auth-v2-submit">Tạo tài khoản <i class="fi fi-rr-arrow-right"></i></button>
            </form>
            <div class="auth-v2-divider"><span>Hoặc</span></div>
            <a href="<?= e(APP_URL) ?>/products.php" class="auth-v2-guest"><i class="fi fi-rr-user"></i> Tiếp tục với tư cách Khách</a>
            <p class="auth-v2-note">Đã có tài khoản? <a href="<?= e(APP_URL) ?>/login.php">Đăng nhập ngay</a></p>
        </div>
    </section>
</main>
</body>
</html>

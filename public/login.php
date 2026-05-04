<?php
require_once __DIR__ . '/../app/config/config.php';
require_once BASE_PATH . '/app/helpers/functions.php';
if (file_exists(BASE_PATH . '/app/middleware/auth.php')) require_once BASE_PATH . '/app/middleware/auth.php';

$db = Database::connect();
$errors = [];
$form = ['email' => ''];

function lumina_auth_is_admin_role(?string $roleName): bool
{
    return function_exists('is_admin_role') ? is_admin_role($roleName) : $roleName === 'admin';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['email'] = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($form['email'] === '') $errors[] = 'Vui lòng nhập email.';
    if ($password === '') $errors[] = 'Vui lòng nhập mật khẩu.';

    if (!$errors) {
        $stmt = $db->prepare('SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.email = :email LIMIT 1');
        $stmt->execute(['email' => $form['email']]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Email hoặc mật khẩu không đúng.';
        } else {
            function_exists('login_user') ? login_user($user) : $_SESSION['user'] = $user;
            if (function_exists('add_flash')) add_flash('success', 'Đăng nhập thành công.');

            $fallback = lumina_auth_is_admin_role($user['role_name'] ?? '') ? '/admin/' : '/profile.php';
            $path = function_exists('intended_redirect_path') ? intended_redirect_path($fallback) : $fallback;
            if (!lumina_auth_is_admin_role($user['role_name'] ?? '') && str_starts_with($path, '/admin')) $path = '/profile.php';

            function_exists('redirect_to') ? redirect_to($path) : header('Location: ' . APP_URL . $path);
            exit;
        }
    }
}

$pageTitle = 'Đăng nhập - ' . APP_NAME;
$pageDescription = 'Đăng nhập tài khoản LUMINA để theo dõi đơn hàng và quản lý lịch sử mua kính.';
$pageStyles = [APP_URL . '/assets/css/account-v2.css?v=1.0.0'];
require_once BASE_PATH . '/app/views/partials/head.php';
?>
<main class="auth-v2-page">
    <section class="auth-v2-visual">
        <a href="<?= e(APP_URL) ?>/" class="auth-v2-logo"><i class="fi fi-rr-glasses"></i><span>LUMINA</span></a>
        <div class="auth-v2-benefit-card">
            <span class="auth-v2-chip"><i class="fi fi-rr-star"></i> Lợi ích thành viên</span>
            <h1>Trải nghiệm mua sắm thông minh hơn</h1>
            <div class="auth-v2-benefit-list">
                <div class="auth-v2-benefit-item"><span><i class="fi fi-rr-document-signed"></i></span><div><h3>Lưu trữ đơn kính</h3><p>Quản lý nhiều đơn kính cho bản thân và gia đình, dễ dàng tái đặt hàng.</p></div></div>
                <div class="auth-v2-benefit-item"><span><i class="fi fi-rr-truck-side"></i></span><div><h3>Theo dõi đơn hàng</h3><p>Cập nhật trạng thái gia công và giao hàng theo thời gian thực.</p></div></div>
                <div class="auth-v2-benefit-item"><span><i class="fi fi-rr-gift"></i></span><div><h3>Ưu đãi độc quyền</h3><p>Nhận thông báo sớm về mẫu kính mới và chương trình khuyến mãi.</p></div></div>
            </div>
        </div>
    </section>
    <section class="auth-v2-panel">
        <div class="auth-v2-card">
            <div class="auth-v2-card-head"><h2>Chào mừng trở lại</h2><p>Đăng nhập để tiếp tục quá trình đặt kính</p></div>
            <div class="auth-v2-tabs"><a class="is-active" href="<?= e(APP_URL) ?>/login.php">Đăng nhập</a><a href="<?= e(APP_URL) ?>/register.php">Đăng ký</a></div>
            <?php if ($errors): ?><div class="auth-v2-alert auth-v2-alert--error"><?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?></div><?php endif; ?>
            <form method="post" class="auth-v2-form" autocomplete="on">
                <label class="auth-v2-field"><span>Email hoặc số điện thoại</span><div class="auth-v2-input"><i class="fi fi-rr-envelope"></i><input type="email" name="email" value="<?= e($form['email']) ?>" placeholder="john.doe@example.com" required></div></label>
                <label class="auth-v2-field"><span>Mật khẩu <a href="#" class="auth-v2-small-link">Quên mật khẩu?</a></span><div class="auth-v2-input"><i class="fi fi-rr-lock"></i><input type="password" name="password" placeholder="••••••••••" required><i class="fi fi-rr-eye-crossed auth-v2-input-trailing"></i></div></label>
                <label class="auth-v2-check"><input type="checkbox" name="remember" value="1"><span>Ghi nhớ đăng nhập</span></label>
                <button type="submit" class="auth-v2-submit">Đăng nhập <i class="fi fi-rr-arrow-right"></i></button>
            </form>
            <div class="auth-v2-divider"><span>Hoặc</span></div>
            <button type="button" class="auth-v2-social" disabled title="Demo UI, chưa tích hợp Google OAuth"><strong>G</strong> Tiếp tục với Google</button>
            <a href="<?= e(APP_URL) ?>/products.php" class="auth-v2-guest"><i class="fi fi-rr-user"></i> Tiếp tục với tư cách Khách</a>
            <p class="auth-v2-note">Bạn vẫn có thể lưu đơn kính sau khi hoàn tất thanh toán.</p>
        </div>
    </section>
</main>
</body>
</html>

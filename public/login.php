<?php
require_once dirname(__DIR__) . '/app/config/config.php';
require_once BASE_PATH . '/app/helpers/functions.php';

$pageTitle = 'Đăng nhập - ' . APP_NAME;
$pageDescription = 'Trang đăng nhập tạm thời cho đồ án LUMINA.';
$headerKeyword = '';

require_once BASE_PATH . '/app/views/partials/head.php';
require_once BASE_PATH . '/app/views/partials/header.php';
?>
<main class="simple-page">
    <div class="container">
        <section class="auth-wrap">
            <h1>Đăng nhập</h1>
            <p>Trang này đang là placeholder. Bước tiếp theo mình có thể làm đăng ký, đăng nhập và phân quyền.</p>

            <form class="auth-form" action="#" method="post">
                <input type="email" placeholder="Email">
                <input type="password" placeholder="Mật khẩu">
                <button type="submit">Đăng nhập</button>
            </form>
        </section>
    </div>
</main>
<?php require_once BASE_PATH . '/app/views/partials/footer.php'; ?>

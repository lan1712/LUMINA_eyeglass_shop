<?php
require_once BASE_PATH . '/app/helpers/functions.php';
$user = auth_user();
?>
<header class="site-header">
    <div class="container">
        <div class="header-wrap">
            <a class="brand-mark" href="<?= e(APP_URL) ?>/">
                <span class="brand-icon"><i class="fi fi-rr-glasses icon"></i></span>
                <span class="brand-text">
                    <strong>LUMINA</strong>
                    <small>Eyewear Store</small>
                </span>
            </a>

            <form class="header-search" action="<?= e(APP_URL) ?>/products.php" method="get">
                <span class="search-icon"><i class="fi fi-rr-search icon icon-md"></i></span>
                <input type="text" name="keyword" placeholder="Tìm gọng kính, kính mát, tròng kính...">
                <button type="submit" class="search-submit">Tìm</button>
            </form>

            <div class="header-actions">
                <?php if ($user): ?>
                    <?php if (is_admin_user()): ?>
                        <a class="icon-btn" href="<?= e(APP_URL) ?>/admin/" title="Admin">
                            <i class="fi fi-rr-apps icon icon-md"></i>
                        </a>
                    <?php endif; ?>

                    <a class="icon-btn" href="<?= e(APP_URL) ?>/profile.php" title="Tài khoản">
                        <i class="fi fi-rr-user icon icon-md"></i>
                    </a>
                <?php else: ?>
                    <a class="icon-btn" href="<?= e(APP_URL) ?>/login.php" title="Đăng nhập">
                        <i class="fi fi-rr-user icon icon-md"></i>
                    </a>
                <?php endif; ?>

                <a class="icon-btn" href="<?= e(APP_URL) ?>/cart.php" title="Giỏ hàng">
                    <i class="fi fi-rr-shopping-bag icon icon-md"></i>
                    <?php if (cart_count() > 0): ?>
                        <span class="cart-badge"><?= cart_count() ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>

        <div class="nav-row">
            <nav class="main-nav">
                <a href="<?= e(APP_URL) ?>/" class="<?= is_active_nav('/') ? 'active' : '' ?>">Trang chủ</a>
                <a href="<?= e(APP_URL) ?>/products.php" class="<?= is_active_nav('/products.php') ? 'active' : '' ?>">Sản phẩm</a>
                <a href="<?= e(APP_URL) ?>/products.php?category=1">Gọng kính</a>
                <a href="<?= e(APP_URL) ?>/products.php?category=2">Kính mát</a>
                <a href="<?= e(APP_URL) ?>/products.php?category=3">Tròng kính</a>
                <a href="<?= e(APP_URL) ?>/orders.php" class="<?= is_active_nav('/orders.php', true) || is_active_nav('/order-detail.php', true) ? 'active' : '' ?>">Đơn hàng</a>

                <?php if ($user): ?>
                    <a href="<?= e(APP_URL) ?>/profile.php" class="<?= is_active_nav('/profile.php') ? 'active' : '' ?>">Tài khoản</a>
                    <a href="<?= e(APP_URL) ?>/logout.php">Đăng xuất</a>
                <?php else: ?>
                    <a href="<?= e(APP_URL) ?>/login.php" class="<?= is_active_nav('/login.php') ? 'active' : '' ?>">Đăng nhập</a>
                    <a href="<?= e(APP_URL) ?>/register.php" class="<?= is_active_nav('/register.php') ? 'active' : '' ?>">Đăng ký</a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</header>

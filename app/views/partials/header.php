<?php
require_once BASE_PATH . '/app/helpers/functions.php';

$user = auth_user();
$currentCategory = isset($_GET['category']) ? (string) $_GET['category'] : '';
?>
<header class="site-header lumina-navbar">
    <div class="container nav-container">
        <div class="nav-topbar">
            <a class="brand-mark nav-brand" href="<?= e(APP_URL) ?>/">
                <span class="brand-icon"><i class="fi fi-rr-glasses icon"></i></span>
                <span class="brand-text">
                    <strong>LUMINA</strong>
                    <small>Eyewear Store</small>
                </span>
            </a>

            <form class="header-search nav-search" action="<?= e(APP_URL) ?>/products.php" method="get">
                <span class="search-icon"><i class="fi fi-rr-search icon icon-md"></i></span>
                <input
                    type="text"
                    name="keyword"
                    placeholder="Tìm gọng kính, kính mát, tròng kính..."
                    value="<?= e($_GET['keyword'] ?? '') ?>"
                >
                <button type="submit" class="search-submit">Tìm</button>
            </form>

            <div class="header-actions nav-actions">
                <?php if ($user && is_admin_user()): ?>
                    <a class="icon-btn" href="<?= e(APP_URL) ?>/admin/" title="Admin">
                        <i class="fi fi-rr-apps icon icon-md"></i>
                    </a>
                <?php endif; ?>

                <?php if ($user): ?>
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

        <div class="nav-row nav-menu-wrap nav-simple-wrap">
            <nav class="main-nav nav-menu nav-simple-menu" aria-label="Điều hướng chính">
                <a href="<?= e(APP_URL) ?>/" class="nav-link <?= is_active_nav('/') || is_active_nav('/index.php') ? 'active' : '' ?>">
                    <i class="fi fi-rr-home icon icon-sm"></i>
                    <span>Trang chủ</span>
                </a>

                <a href="<?= e(APP_URL) ?>/products.php" class="nav-link <?= is_active_nav('/products.php') && $currentCategory === '' ? 'active' : '' ?>">
                    <i class="fi fi-rr-shop icon icon-sm"></i>
                    <span>Sản phẩm</span>
                </a>

                <a href="<?= e(APP_URL) ?>/products.php?category=1" class="nav-link <?= $currentCategory === '1' || $currentCategory === 'gong-kinh' ? 'active' : '' ?>">
                    <i class="fi fi-rr-glasses icon icon-sm"></i>
                    <span>Gọng kính</span>
                </a>

                <a href="<?= e(APP_URL) ?>/products.php?category=2" class="nav-link <?= $currentCategory === '2' || $currentCategory === 'kinh-mat' ? 'active' : '' ?>">
                    <i class="fi fi-rr-sunglasses icon icon-sm"></i>
                    <span>Kính mát</span>
                </a>

                <a href="<?= e(APP_URL) ?>/products.php?category=3" class="nav-link <?= $currentCategory === '3' || $currentCategory === 'trong-kinh' ? 'active' : '' ?>">
                    <i class="fi fi-rr-eye icon icon-sm"></i>
                    <span>Tròng kính</span>
                </a>

                <a href="<?= e(APP_URL) ?>/orders.php" class="nav-link <?= is_active_nav('/orders.php', true) || is_active_nav('/order-detail.php', true) ? 'active' : '' ?>">
                    <i class="fi fi-rr-receipt icon icon-sm"></i>
                    <span>Đơn hàng</span>
                </a>

                <?php if ($user): ?>
                    <a href="<?= e(APP_URL) ?>/profile.php" class="nav-link <?= is_active_nav('/profile.php') ? 'active' : '' ?>">
                        <i class="fi fi-rr-user icon icon-sm"></i>
                        <span>Tài khoản</span>
                    </a>
                    <a href="<?= e(APP_URL) ?>/logout.php" class="nav-link nav-logout">
                        <i class="fi fi-rr-sign-out-alt icon icon-sm"></i>
                        <span>Đăng xuất</span>
                    </a>
                <?php else: ?>
                    <a href="<?= e(APP_URL) ?>/login.php" class="nav-link <?= is_active_nav('/login.php') ? 'active' : '' ?>">
                        <i class="fi fi-rr-sign-in-alt icon icon-sm"></i>
                        <span>Đăng nhập</span>
                    </a>
                    <a href="<?= e(APP_URL) ?>/register.php" class="nav-link <?= is_active_nav('/register.php') ? 'active' : '' ?>">
                        <i class="fi fi-rr-user-add icon icon-sm"></i>
                        <span>Đăng ký</span>
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</header>
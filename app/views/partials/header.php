<?php
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$cartCount = function_exists('cart_count') ? cart_count() : 0;
?>
<header class="site-header">
    <div class="container header-wrap">
        <a href="<?= e(APP_URL) ?>/index.php" class="brand-mark" aria-label="LUMINA Trang chủ">
            <span class="brand-icon"><i class="fi fi-rr-glasses icon"></i></span>
            <span class="brand-text">
                <strong>LUMINA</strong>
                <small>Eyewear Store</small>
            </span>
        </a>

        <form class="header-search" method="GET" action="<?= e(APP_URL) ?>/products.php">
            <span class="search-icon"><i class="fi fi-rr-search icon"></i></span>
            <input
                type="text"
                name="keyword"
                placeholder="Tìm gọng kính, kính mát, tròng kính..."
                value="<?= e($headerKeyword ?? '') ?>"
            >
            <button type="submit" class="search-submit">Tìm</button>
        </form>

        <div class="header-actions">
            <a href="<?= e(APP_URL) ?>/login.php" class="icon-btn" title="Tài khoản" aria-label="Tài khoản">
                <i class="fi fi-rr-user icon icon-md"></i>
            </a>
            <a href="<?= e(APP_URL) ?>/cart.php" class="icon-btn cart-btn" title="Giỏ hàng" aria-label="Giỏ hàng">
                <i class="fi fi-rr-shopping-bag icon icon-md"></i>
                <?php if ($cartCount > 0): ?>
                    <span class="cart-badge"><?= $cartCount ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>

    <div class="container nav-row">
        <nav class="main-nav">
            <a class="<?= $currentPage === 'index.php' ? 'active' : '' ?>" href="<?= e(APP_URL) ?>/index.php">
                <i class="fi fi-rr-home icon icon-sm"></i>
                Trang chủ
            </a>
            <a class="<?= $currentPage === 'products.php' ? 'active' : '' ?>" href="<?= e(APP_URL) ?>/products.php">
                <i class="fi fi-rr-apps icon icon-sm"></i>
                Sản phẩm
            </a>
            <a href="<?= e(APP_URL) ?>/products.php?type=frame">
                <i class="fi fi-rr-glasses icon icon-sm"></i>
                Gọng kính
            </a>
            <a href="<?= e(APP_URL) ?>/products.php?type=sunglasses">
                <i class="fi fi-rr-sun icon icon-sm"></i>
                Kính mát
            </a>
            <a href="<?= e(APP_URL) ?>/products.php?type=lens">
                <i class="fi fi-rr-eye icon icon-sm"></i>
                Tròng kính
            </a>
        </nav>
    </div>
</header>

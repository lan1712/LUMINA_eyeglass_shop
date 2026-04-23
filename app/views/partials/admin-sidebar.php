<?php
$adminCurrentPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '';

if (!function_exists('admin_nav_active')) {
    function admin_nav_active(string $needle, string $currentPath): bool
    {
        return $needle !== '' && str_contains($currentPath, $needle);
    }
}
?>
<aside class="admin-sidebar">
    <div class="admin-sidebar-brand">
        <a href="<?= e(APP_URL) ?>/admin" class="brand-mark">
            <span class="brand-icon"><i class="fi fi-rr-glasses icon"></i></span>
            <span class="brand-text">
                <strong>LUMINA Admin</strong>
                <small>Eyewear Control Center</small>
            </span>
        </a>
    </div>

    <nav class="admin-nav">
        <a class="admin-nav-link <?= $adminCurrentPath === '/admin' || $adminCurrentPath === '/admin/' || $adminCurrentPath === '/admin/index.php' ? 'is-active' : '' ?>" href="<?= e(APP_URL) ?>/admin">
            <i class="fi fi-rr-apps icon"></i>
            <span>Dashboard</span>
        </a>

        <a class="admin-nav-link <?= admin_nav_active('/admin/orders', $adminCurrentPath) ? 'is-active' : '' ?>" href="<?= e(APP_URL) ?>/admin/orders/index.php">
            <i class="fi fi-rr-shopping-bag icon"></i>
            <span>Đơn hàng</span>
        </a>

        <a class="admin-nav-link <?= admin_nav_active('/admin/products', $adminCurrentPath) ? 'is-active' : '' ?>" href="<?= e(APP_URL) ?>/admin/products/index.php">
            <i class="fi fi-rr-box-open icon"></i>
            <span>Sản phẩm</span>
        </a>

        <a class="admin-nav-link" href="<?= e(APP_URL) ?>/products.php">
            <i class="fi fi-rr-apps icon"></i>
            <span>Danh mục</span>
        </a>

        <a class="admin-nav-link" href="<?= e(APP_URL) ?>/">
            <i class="fi fi-rr-home icon"></i>
            <span>Storefront</span>
        </a>
    </nav>

    <div class="admin-sidebar-foot">
        <div class="admin-support-card">
            <div class="admin-support-icon"><i class="fi fi-rr-headset icon"></i></div>
            <div>
                <strong>Catalog control</strong>
                <p>Quản lý sản phẩm, giá bán, trạng thái hiển thị và cập nhật catalog ngay trong một nơi.</p>
            </div>
        </div>
    </div>
</aside>

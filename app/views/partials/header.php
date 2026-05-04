<?php
/**
 * LUMINA global storefront header.
 * Header trắng theo design mới, dùng chung cho toàn bộ trang public.
 *
 * File này thay thế hoàn toàn header cũ. Các trang public chỉ cần:
 * require_once BASE_PATH . '/app/views/partials/header.php';
 */

$navParents = [];
$navChildrenByParent = [];

try {
    $db = Database::connect();

    $stmt = $db->query("
        SELECT id, parent_id, name, slug
        FROM categories
        WHERE is_active = 1
        ORDER BY sort_order ASC, id ASC
    ");

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $cat) {
        $parentId = $cat['parent_id'] ?? null;

        if ($parentId === null || $parentId === '' || (int) $parentId === 0) {
            $navParents[(int) $cat['id']] = $cat;
            continue;
        }

        $navChildrenByParent[(int) $parentId][] = $cat;
    }
} catch (Throwable $exception) {
    $navParents = [];
    $navChildrenByParent = [];
}

if (!function_exists('lumina_header_parent_by_slug')) {
    function lumina_header_parent_by_slug(array $parents, string $slug): ?array
    {
        foreach ($parents as $parent) {
            if (($parent['slug'] ?? '') === $slug) {
                return $parent;
            }
        }

        return null;
    }
}

if (!function_exists('lumina_header_category_url')) {
    function lumina_header_category_url(string $slug): string
    {
        return APP_URL . '/products.php?category=' . urlencode($slug);
    }
}

if (!function_exists('lumina_header_child_url')) {
    function lumina_header_child_url(string $parentSlug, array $child): string
    {
        return APP_URL
            . '/products.php?category=' . urlencode($parentSlug)
            . '&subcat[]=' . urlencode((string) ($child['slug'] ?? $child['id']));
    }
}

if (!function_exists('lumina_header_is_active')) {
    function lumina_header_is_active(string $slug): bool
    {
        $current = $_GET['category'] ?? '';
        return $current === $slug;
    }
}

$fallbackMenus = [
    'gong-kinh' => [
        'label' => 'Gọng kính',
        'children' => [
            ['name' => 'Gọng kính kim loại', 'slug' => 'gong-kinh-kim-loai'],
            ['name' => 'Gọng kính oval', 'slug' => 'gong-kinh-oval'],
            ['name' => 'Gọng kính mắt mèo', 'slug' => 'gong-kinh-mat-meo'],
            ['name' => 'Gọng kính nhựa', 'slug' => 'gong-kinh-nhua'],
            ['name' => 'Gọng kính nửa viền', 'slug' => 'gong-kinh-nua-vien'],
            ['name' => 'Gọng nhựa phối kim loại', 'slug' => 'gong-nhua-phoi-kim-loai'],
            ['name' => 'Kính đổi màu', 'slug' => 'kinh-doi-mau'],
        ],
    ],
    'kinh-mat' => [
        'label' => 'Kính mát',
        'children' => [
            ['name' => 'Kính mắt nam', 'slug' => 'kinh-mat-nam'],
            ['name' => 'Kính mắt nữ', 'slug' => 'kinh-mat-nu'],
            ['name' => 'Kính mắt em bé', 'slug' => 'kinh-mat-em-be'],
        ],
    ],
    'trong-kinh' => [
        'label' => 'Tròng kính',
        'children' => [
            ['name' => 'Tròng siêu mỏng', 'slug' => 'trong-sieu-mong'],
            ['name' => 'Tròng chống ánh sáng xanh', 'slug' => 'trong-chong-anh-sang-xanh'],
            ['name' => 'Tròng đổi màu', 'slug' => 'trong-doi-mau'],
            ['name' => 'Tròng cận phổ thông', 'slug' => 'trong-can-pho-thong'],
            ['name' => 'Tròng chống tia UV', 'slug' => 'trong-chong-tia-uv'],
            ['name' => 'Tròng kính đa tròng', 'slug' => 'trong-kinh-da-trong'],
            ['name' => 'Tròng râm cận', 'slug' => 'trong-ram-can'],
            ['name' => 'Tròng Kính Phát Sáng', 'slug' => 'trong-kinh-phat-sang'],
        ],
    ],
];

$menus = [];

foreach (['gong-kinh', 'kinh-mat', 'trong-kinh'] as $slug) {
    $parent = lumina_header_parent_by_slug($navParents, $slug);
    $fallback = $fallbackMenus[$slug];

    $children = [];

    if ($parent && isset($navChildrenByParent[(int) $parent['id']])) {
        $children = $navChildrenByParent[(int) $parent['id']];
    }

    if (!$parent) {
        $parent = [
            'id' => 0,
            'name' => $fallback['label'],
            'slug' => $slug,
        ];
    }

    // Chuẩn hóa tên menu đúng như design mới.
    $parent['name'] = $fallback['label'];

    if (empty($children)) {
        $children = $fallback['children'];
    }

    $menus[] = [
        'slug' => $slug,
        'parent' => $parent,
        'children' => $children,
    ];
}

$cartCount = 0;

if (function_exists('cart_count')) {
    $cartCount = (int) cart_count();
} elseif (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int) ($item['quantity'] ?? $item ?? 0);
    }
}

$isLoggedIn = function_exists('is_logged_in') ? is_logged_in() : !empty($_SESSION['user']);
$isAdminUser = function_exists('is_admin') ? is_admin() : false;
$accountHref = $isLoggedIn ? APP_URL . '/profile.php' : APP_URL . '/login.php';
if ($isAdminUser) {
    $accountHref = APP_URL . '/admin/';
}
?>

<header class="lumina-global-header">
    <div class="lumina-global-header__inner">
        <a class="lumina-global-header__brand" href="<?= e(APP_URL) ?>/" aria-label="Trang chủ LUMINA">
            <span class="lumina-global-header__brand-icon">
                <i class="fi fi-rr-glasses"></i>
            </span>
            <span>LUMINA</span>
        </a>

        <nav class="lumina-global-header__nav" aria-label="Danh mục chính">
            <?php foreach ($menus as $menu): ?>
                <?php
                    $slug = $menu['slug'];
                    $parent = $menu['parent'];
                    $isActive = lumina_header_is_active($slug);
                ?>
                <div class="lumina-global-header__item">
                    <a
                        class="lumina-global-header__link <?= $isActive ? 'is-active' : '' ?>"
                        href="<?= e(lumina_header_category_url($slug)) ?>"
                    >
                        <?= e($parent['name']) ?>
                        <i class="fi fi-rr-angle-small-down"></i>
                    </a>

                    <div class="lumina-global-header__dropdown">
                        <?php foreach ($menu['children'] as $child): ?>
                            <a href="<?= e(lumina_header_child_url($slug, $child)) ?>">
                                <?= e($child['name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <a class="lumina-global-header__link no-dropdown" href="<?= e(APP_URL) ?>/collections.php">Bộ sưu tập</a>
            <a class="lumina-global-header__link no-dropdown" href="<?= e(APP_URL) ?>/about.php">Về chúng tôi</a>
        </nav>

        <div class="lumina-global-header__actions">
            <form class="lumina-global-header__search" action="<?= e(APP_URL) ?>/products.php" method="get">
                <i class="fi fi-rr-search"></i>
                <input
                    type="search"
                    name="q"
                    value="<?= e($_GET['q'] ?? '') ?>"
                    placeholder="Tìm kiếm..."
                    aria-label="Tìm kiếm sản phẩm"
                >
            </form>

            <a class="lumina-global-header__icon" href="<?= e(APP_URL) ?>/cart.php" aria-label="Giỏ hàng">
                <i class="fi fi-rr-shopping-cart"></i>
                <?php if ($cartCount > 0): ?>
                    <span><?= e((string) $cartCount) ?></span>
                <?php endif; ?>
            </a>

            <a class="lumina-global-header__icon" href="<?= e($accountHref) ?>" aria-label="Tài khoản">
                <i class="fi fi-rr-user"></i>
            </a>
        </div>
    </div>
</header>

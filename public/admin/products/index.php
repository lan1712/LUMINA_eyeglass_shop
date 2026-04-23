<?php
require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/helpers/functions.php';

$db = Database::connect();

$keyword = trim((string) ($_GET['keyword'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$categoryId = (int) ($_GET['category_id'] ?? 0);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($keyword !== '') {
    $where[] = "(p.name LIKE :keyword OR p.slug LIKE :keyword OR p.brand LIKE :keyword)";
    $params['keyword'] = '%' . $keyword . '%';
}

if ($status !== '' && in_array($status, ['active', 'inactive', 'draft'], true)) {
    $where[] = "p.status = :status";
    $params['status'] = $status;
}

if ($categoryId > 0) {
    $where[] = "p.category_id = :category_id";
    $params['category_id'] = $categoryId;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $db->prepare(
    "SELECT COUNT(*)
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     {$whereSql}"
);
$countStmt->execute($params);
$totalProducts = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalProducts / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$listSql = "SELECT
                p.id,
                p.name,
                p.slug,
                p.brand,
                p.default_price,
                p.compare_at_price,
                p.thumbnail,
                p.target_gender,
                p.material,
                p.shape,
                p.status,
                p.is_prescription_supported,
                p.has_3d_model,
                p.updated_at,
                c.name AS category_name
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            {$whereSql}
            ORDER BY p.updated_at DESC, p.id DESC
            LIMIT {$perPage} OFFSET {$offset}";
$listStmt = $db->prepare($listSql);
$listStmt->execute($params);
$products = $listStmt->fetchAll();

$summaryStmt = $db->query(
    "SELECT
        COUNT(*) AS total_products,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_products,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) AS inactive_products,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS draft_products
     FROM products"
);
$summary = $summaryStmt->fetch() ?: [
    'total_products' => 0,
    'active_products' => 0,
    'inactive_products' => 0,
    'draft_products' => 0,
];

$categoriesStmt = $db->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
$categories = $categoriesStmt->fetchAll();

$pageTitle = 'Admin Products';
$pageDescription = 'Quản lý danh mục sản phẩm của ' . APP_NAME;
$adminPageTitle = 'Quản lý sản phẩm';
$adminPageSubtitle = 'Tìm kiếm, cập nhật giá bán và trạng thái catalog cho shop mắt kính.';
$adminPrimaryAction = [
    'href' => APP_URL . '/admin/products/edit.php',
    'label' => 'Tạo sản phẩm',
    'icon' => 'fi fi-rr-plus',
];
$adminSecondaryAction = [
    'href' => APP_URL . '/admin/orders/index.php',
    'label' => 'Xem đơn hàng',
    'icon' => 'fi fi-rr-shopping-bag',
];

$flashSuccess = flash_get('success');
$flashError = flash_get('error');

require BASE_PATH . '/app/views/partials/admin-head.php';
?>
<div class="admin-shell">
    <?php require BASE_PATH . '/app/views/partials/admin-sidebar.php'; ?>

    <main class="admin-main">
        <?php require BASE_PATH . '/app/views/partials/admin-topbar.php'; ?>

        <div class="admin-dashboard">
            <?php if ($flashSuccess): ?>
                <div class="alert success"><?= e((string) $flashSuccess) ?></div>
            <?php endif; ?>

            <?php if ($flashError): ?>
                <div class="alert warning"><?= e((string) $flashError) ?></div>
            <?php endif; ?>

            <section class="admin-kpi-grid compact-grid">
                <article class="admin-kpi-card compact accent-purple">
                    <span class="admin-kpi-icon"><i class="fi fi-rr-box-open icon"></i></span>
                    <div>
                        <span>Tổng sản phẩm</span>
                        <strong><?= (int) $summary['total_products'] ?></strong>
                        <small>Tất cả sản phẩm đang có trong catalog.</small>
                    </div>
                </article>

                <article class="admin-kpi-card compact accent-green">
                    <span class="admin-kpi-icon"><i class="fi fi-rr-badge-check icon"></i></span>
                    <div>
                        <span>Đang bán</span>
                        <strong><?= (int) $summary['active_products'] ?></strong>
                        <small>Sản phẩm hiển thị công khai trên storefront.</small>
                    </div>
                </article>

                <article class="admin-kpi-card compact accent-amber">
                    <span class="admin-kpi-icon"><i class="fi fi-rr-eye-crossed icon"></i></span>
                    <div>
                        <span>Tạm ẩn</span>
                        <strong><?= (int) $summary['inactive_products'] ?></strong>
                        <small>Sản phẩm đã ẩn khỏi danh sách bán.</small>
                    </div>
                </article>

                <article class="admin-kpi-card compact accent-blue">
                    <span class="admin-kpi-icon"><i class="fi fi-rr-document icon"></i></span>
                    <div>
                        <span>Bản nháp</span>
                        <strong><?= (int) $summary['draft_products'] ?></strong>
                        <small>Sản phẩm đang ở trạng thái chuẩn bị.</small>
                    </div>
                </article>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-head compact">
                    <div>
                        <span class="admin-panel-kicker">CATALOG FILTERS</span>
                        <h2>Sản phẩm và bộ lọc quản trị</h2>
                        <p>Lọc theo tên, trạng thái và danh mục để chỉnh nhanh catalog.</p>
                    </div>
                </div>

                <form method="get" class="form-grid two-cols admin-product-filter-grid">
                    <div class="form-field full-width">
                        <label for="keyword">Từ khóa</label>
                        <input id="keyword" type="text" name="keyword" value="<?= e($keyword) ?>" placeholder="Tên sản phẩm, slug, thương hiệu...">
                    </div>

                    <div class="form-field">
                        <label for="status">Trạng thái</label>
                        <select id="status" name="status">
                            <option value="">Tất cả trạng thái</option>
                            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Đang bán</option>
                            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Tạm ẩn</option>
                            <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Nháp</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="category_id">Danh mục</label>
                        <select id="category_id" name="category_id">
                            <option value="0">Tất cả danh mục</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category['id'] ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>>
                                    <?= e((string) $category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field full-width form-field-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fi fi-rr-search icon"></i>
                            <span>Lọc sản phẩm</span>
                        </button>
                        <a class="btn btn-secondary" href="<?= e(APP_URL) ?>/admin/products/index.php">
                            <i class="fi fi-rr-rotate-right icon"></i>
                            <span>Đặt lại</span>
                        </a>
                    </div>
                </form>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-head compact">
                    <div>
                        <span class="admin-panel-kicker">PRODUCT TABLE</span>
                        <h2>Danh sách sản phẩm</h2>
                        <p><?= $totalProducts ?> sản phẩm phù hợp bộ lọc hiện tại.</p>
                    </div>
                </div>

                <div class="admin-table-wrap">
                    <table class="admin-table admin-table-dashboard">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Danh mục</th>
                                <th>Giá</th>
                                <th>Thuộc tính</th>
                                <th>Trạng thái</th>
                                <th>Cập nhật</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$products): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-mini-card">Không tìm thấy sản phẩm phù hợp.</div>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <div class="admin-product-cell">
                                        <img
                                            class="admin-product-thumb"
                                            src="<?= e((string) ($product['thumbnail'] ?: APP_URL . '/assets/images/placeholder-glasses.jpg')) ?>"
                                            alt="<?= e((string) $product['name']) ?>"
                                            onerror="this.onerror=null;this.src='<?= e(APP_URL) ?>/assets/images/placeholder-glasses.jpg';"
                                        >
                                        <div class="admin-product-meta">
                                            <strong><?= e((string) $product['name']) ?></strong>
                                            <span class="muted-small"><?= e((string) ($product['brand'] ?: 'LUMINA')) ?></span>
                                            <span class="muted-small"><?= e((string) $product['slug']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?= e((string) ($product['category_name'] ?: '—')) ?></td>
                                <td>
                                    <strong><?= format_price((float) $product['default_price']) ?></strong>
                                    <?php if (!empty($product['compare_at_price'])): ?>
                                        <span class="muted-small"><?= format_price((float) $product['compare_at_price']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="admin-inline-tags">
                                        <span class="meta-chip"><?= e(gender_label((string) $product['target_gender'])) ?></span>
                                        <span class="meta-chip"><?= e((string) ($product['material'] ?: '—')) ?></span>
                                        <?php if (!empty($product['shape'])): ?>
                                            <span class="meta-chip"><?= e((string) $product['shape']) ?></span>
                                        <?php endif; ?>
                                        <?php if ((int) $product['is_prescription_supported'] === 1): ?>
                                            <span class="badge badge-primary">Prescription</span>
                                        <?php endif; ?>
                                        <?php if ((int) $product['has_3d_model'] === 1): ?>
                                            <span class="badge">3D</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-pill <?= e(product_status_class((string) $product['status'])) ?>">
                                        <?= e(product_status_label((string) $product['status'])) ?>
                                    </span>
                                </td>
                                <td><?= e(date('d/m/Y H:i', strtotime((string) $product['updated_at']))) ?></td>
                                <td>
                                    <div class="admin-row-actions">
                                        <a class="btn btn-secondary btn-sm" href="<?= e(APP_URL) ?>/admin/products/edit.php?id=<?= (int) $product['id'] ?>">
                                            <i class="fi fi-rr-edit icon"></i>
                                            <span>Sửa</span>
                                        </a>

                                        <form method="post" action="<?= e(APP_URL) ?>/admin/products/toggle-status.php" onsubmit="return confirm('Đổi trạng thái sản phẩm này?');">
                                            <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                                            <button type="submit" class="btn btn-outline btn-sm">
                                                <i class="fi fi-rr-exchange icon"></i>
                                                <span><?= (string) $product['status'] === 'active' ? 'Ẩn' : 'Bật' ?></span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="admin-pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php
                            $query = $_GET;
                            $query['page'] = $i;
                            $url = APP_URL . '/admin/products/index.php?' . http_build_query($query);
                            ?>
                            <a class="pagination-link <?= $i === $page ? 'is-active' : '' ?>" href="<?= e($url) ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</div>
</body>
</html>

<?php
/**
 * GLAIMAGAIN - Admin Products Catalog
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();

// Search & Filter
$search = trim($_GET['search'] ?? '');
$categoryFilter = (int)($_GET['category_id'] ?? 0);
$statusFilter = trim($_GET['status'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(p.name LIKE ? OR p.sku LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($categoryFilter > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $categoryFilter;
}
if (!empty($statusFilter)) {
    $where[] = "p.status = ?";
    $params[] = $statusFilter;
}

$whereSql = implode(" AND ", $where);

// Fetch Products with Images and Category
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name,
           (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order ASC LIMIT 1) AS primary_image,
           (SELECT COUNT(*) FROM product_variants pv WHERE pv.product_id = p.id) AS variant_count,
           (SELECT COUNT(*) FROM product_images pi WHERE pi.product_id = p.id) AS total_images
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE {$whereSql}
    ORDER BY p.id DESC
");
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = getActiveCategories();

$adminHeaderHeading = 'Product Catalog';
$adminTitle = 'Products | GLAIMAGAIN Admin';
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h5 class="fw-bold text-emerald mb-1">Garment Catalog (<?= count($products) ?>)</h5>
        <p class="text-muted small mb-0">Manage silhouettes, variants, bespoke pricing, and stock levels.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>admin/inventory/index.php" class="btn btn-outline-dark btn-sm d-flex align-items-center gap-1">
            <i class="fas fa-boxes"></i> Inventory Matrix
        </a>
        <a href="<?= BASE_URL ?>admin/products/add.php" class="btn btn-admin-gold btn-sm d-flex align-items-center gap-1">
            <i class="fas fa-plus"></i> ADD PRODUCT
        </a>
    </div>
</div>

<!-- Search & Filters Bar -->
<div class="admin-card p-3 mb-4">
    <form method="GET" action="<?= BASE_URL ?>admin/products/index.php" class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search by garment title or SKU..." value="<?= e($search) ?>">
            </div>
        </div>
        <div class="col-md-3">
            <select name="category_id" class="form-select form-select-sm">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $categoryFilter == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-admin-primary btn-sm flex-grow-1">Filter</button>
            <a href="<?= BASE_URL ?>admin/products/index.php" class="btn btn-outline-secondary btn-sm" title="Clear Filters"><i class="fas fa-times"></i></a>
        </div>
    </form>
</div>

<!-- Products Table -->
<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th style="width: 70px;">Image</th>
                    <th>Product &amp; SKU</th>
                    <th>Category</th>
                    <th>Pricing &amp; Discount</th>
                    <th>Stock</th>
                    <th>Badges</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No garments found matching criteria.</td></tr>
                <?php else: ?>
                    <?php foreach ($products as $p): 
                        $discount = calculateDiscountPercent((float)$p['original_price'], (float)$p['selling_price']);
                    ?>
                        <tr>
                            <td>
                                <a href="<?= BASE_URL ?>admin/products/images.php?id=<?= $p['id'] ?>" title="Manage Gallery">
                                    <img src="<?= getProductImageUrl($p['primary_image']) ?>" alt="<?= e($p['name']) ?>" style="width: 48px; height: 58px; object-fit: cover; border-radius: 3px; border: 1px solid #E2E8F0;">
                                </a>
                            </td>
                            <td>
                                <strong class="text-emerald fs-6 d-block"><?= e($p['name']) ?></strong>
                                <small class="text-muted"><i class="fas fa-barcode me-1 text-gold"></i><?= e($p['sku']) ?> &bull; <?= (int)$p['variant_count'] ?> variants</small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= e($p['category_name']) ?></span>
                            </td>
                            <td>
                                <div>
                                    <strong class="text-emerald"><?= formatPrice($p['selling_price']) ?></strong>
                                    <?php if ($p['original_price'] > $p['selling_price']): ?>
                                        <small class="text-muted text-decoration-line-through ms-1"><?= formatPrice($p['original_price']) ?></small>
                                    <?php endif; ?>
                                </div>
                                <?php if ($discount > 0): ?>
                                    <span class="badge bg-gold text-dark fw-bold" style="font-size: 10.5px;"><?= $discount ?>% OFF</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((int)$p['stock'] <= 0): ?>
                                    <span class="badge bg-danger">OUT OF STOCK</span>
                                <?php elseif ((int)$p['stock'] <= 5): ?>
                                    <span class="badge bg-warning text-dark"><?= (int)$p['stock'] ?> (Low)</span>
                                <?php else: ?>
                                    <span class="badge bg-success"><?= (int)$p['stock'] ?> Units</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p['is_featured']): ?>
                                    <span class="badge bg-emerald text-gold" style="font-size: 10px;">Featured</span>
                                <?php endif; ?>
                                <?php if ($p['is_new_arrival']): ?>
                                    <span class="badge bg-info text-dark" style="font-size: 10px;">New</span>
                                <?php endif; ?>
                                <?php if ($p['is_best_seller']): ?>
                                    <span class="badge bg-gold text-dark" style="font-size: 10px;">Icon</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-status badge-status-<?= e($p['status']) ?>">
                                    <?= strtoupper(e($p['status'])) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <a href="<?= BASE_URL ?>product.php?slug=<?= urlencode($p['slug']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm py-1 px-2" title="Preview on Boutique">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>admin/products/images.php?id=<?= $p['id'] ?>" class="btn btn-outline-secondary btn-sm py-1 px-2" title="Manage Images (<?= (int)$p['total_images'] ?>)">
                                        <i class="fas fa-images text-gold"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>admin/products/edit.php?id=<?= $p['id'] ?>" class="btn btn-outline-dark btn-sm py-1 px-2" title="Edit Garment">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>admin/products/delete.php?id=<?= $p['id'] ?>&csrf_token=<?= getCsrfToken() ?>" class="btn btn-outline-danger btn-sm py-1 px-2 btn-confirm-delete" data-confirm-message="Deactivate or delete this garment from catalog?" title="Delete Garment">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/admin-footer.php'; ?>

<?php
/**
 * GLAIMAGAIN - Admin Variant-Level Inventory Matrix
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();

// Quick stock update action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_stock') {
    requireCsrfToken();
    $variantId = (int)($_POST['variant_id'] ?? 0);
    $productId = (int)($_POST['product_id'] ?? 0);
    $newStock = max(0, (int)($_POST['stock'] ?? 0));

    if ($variantId > 0) {
        $pdo->prepare("UPDATE product_variants SET stock = ?, updated_at = NOW() WHERE id = ?")->execute([$newStock, $variantId]);
        
        // Synchronize main product stock sum
        $totalVarStock = (int)$pdo->query("SELECT COALESCE(SUM(stock), 0) FROM product_variants WHERE product_id = {$productId}")->fetchColumn();
        $pdo->prepare("UPDATE products SET stock = ?, updated_at = NOW() WHERE id = ?")->execute([$totalVarStock, $productId]);
        
        setFlashMessage('success', 'Variant stock updated successfully.');
    } elseif ($productId > 0) {
        $pdo->prepare("UPDATE products SET stock = ?, updated_at = NOW() WHERE id = ?")->execute([$newStock, $productId]);
        setFlashMessage('success', 'Base product stock updated.');
    }
    header('Location: ' . BASE_URL . 'admin/inventory/index.php');
    exit;
}

// Search & Filter
$search = trim($_GET['search'] ?? '');
$stockFilter = trim($_GET['stock_filter'] ?? '');

$where = ["p.status = 'active'"];
$params = [];

if (!empty($search)) {
    $where[] = "(p.name LIKE ? OR pv.sku LIKE ? OR p.sku LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($stockFilter === 'out_of_stock') {
    $where[] = "COALESCE(pv.stock, p.stock) <= 0";
} elseif ($stockFilter === 'low_stock') {
    $where[] = "COALESCE(pv.stock, p.stock) > 0 AND COALESCE(pv.stock, p.stock) <= 5";
}

$whereSql = implode(" AND ", $where);

// Fetch Variants Matrix
$stmt = $pdo->prepare("
    SELECT 
        p.id AS product_id,
        p.name AS product_name,
        p.sku AS base_sku,
        p.stock AS base_stock,
        c.name AS category_name,
        pv.id AS variant_id,
        pv.sku AS variant_sku,
        pv.size AS variant_size,
        pv.color AS variant_color,
        pv.stock AS variant_stock,
        (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order ASC LIMIT 1) AS primary_image
    FROM products p
    JOIN categories c ON p.category_id = c.id
    LEFT JOIN product_variants pv ON pv.product_id = p.id
    WHERE {$whereSql}
    ORDER BY p.id DESC, pv.size ASC
");
$stmt->execute($params);
$inventoryItems = $stmt->fetchAll();

$adminHeaderHeading = 'Inventory Management Matrix';
$adminTitle = 'Inventory | GLAIMAGAIN Admin';
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h5 class="fw-bold text-emerald mb-1">Variant-Aware Inventory Matrix</h5>
        <p class="text-muted small mb-0">Track exact real-time garment stock by Size and Color to prevent overselling.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>admin/products/index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Product Catalog
        </a>
    </div>
</div>

<!-- Search & Filter Bar -->
<div class="admin-card p-3 mb-4">
    <form method="GET" action="<?= BASE_URL ?>admin/inventory/index.php" class="row g-2 align-items-center">
        <div class="col-md-6">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search by garment title, variant SKU..." value="<?= e($search) ?>">
            </div>
        </div>
        <div class="col-md-4">
            <select name="stock_filter" class="form-select form-select-sm">
                <option value="">All Stock Levels</option>
                <option value="low_stock" <?= $stockFilter === 'low_stock' ? 'selected' : '' ?>>Low Stock (&le; 5 units)</option>
                <option value="out_of_stock" <?= $stockFilter === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock (0 units)</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-admin-primary btn-sm flex-grow-1">Filter</button>
            <a href="<?= BASE_URL ?>admin/inventory/index.php" class="btn btn-outline-secondary btn-sm" title="Clear Filters"><i class="fas fa-times"></i></a>
        </div>
    </form>
</div>

<!-- Inventory Matrix Table -->
<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th style="width: 60px;">Image</th>
                    <th>Garment &amp; Category</th>
                    <th>Variant SKU</th>
                    <th>Size</th>
                    <th>Color</th>
                    <th>Current Stock</th>
                    <th>Stock Health</th>
                    <th class="text-end">Quick Adjust</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($inventoryItems)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No inventory records match filters.</td></tr>
                <?php else: ?>
                    <?php foreach ($inventoryItems as $item): 
                        $effectiveStock = $item['variant_id'] ? (int)$item['variant_stock'] : (int)$item['base_stock'];
                        $sku = $item['variant_sku'] ?: $item['base_sku'];
                    ?>
                        <tr>
                            <td>
                                <img src="<?= getProductImageUrl($item['primary_image']) ?>" alt="" style="width: 44px; height: 52px; object-fit: cover; border-radius: 2px;">
                            </td>
                            <td>
                                <strong class="text-emerald fs-6 d-block"><?= e($item['product_name']) ?></strong>
                                <span class="badge bg-light text-dark border" style="font-size: 10px;"><?= e($item['category_name']) ?></span>
                            </td>
                            <td>
                                <code class="text-emerald"><?= e($sku) ?></code>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= e($item['variant_size'] ?: 'Standard') ?></span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= e($item['variant_color'] ?: 'Standard') ?></span>
                            </td>
                            <td>
                                <strong class="fs-6"><?= $effectiveStock ?></strong> Units
                            </td>
                            <td>
                                <?php if ($effectiveStock <= 0): ?>
                                    <span class="badge bg-danger">OUT OF STOCK</span>
                                <?php elseif ($effectiveStock <= 5): ?>
                                    <span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i>LOW STOCK</span>
                                <?php else: ?>
                                    <span class="badge bg-success">HEALTHY</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <form method="POST" action="<?= BASE_URL ?>admin/inventory/index.php" class="d-inline-flex align-items-center gap-1">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="update_stock">
                                    <input type="hidden" name="variant_id" value="<?= (int)$item['variant_id'] ?>">
                                    <input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>">
                                    <input type="number" name="stock" value="<?= $effectiveStock ?>" min="0" class="form-control form-control-sm text-center" style="width: 75px;">
                                    <button type="submit" class="btn btn-outline-dark btn-sm py-1 px-2" title="Save Stock">
                                        <i class="fas fa-check text-gold"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/admin-footer.php'; ?>

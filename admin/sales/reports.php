<?php
/**
 * GLAIMAGAIN - Admin Sales Breakdown Reports
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();

// Top Selling Products
$topProducts = $pdo->query("
    SELECT 
        oi.product_name,
        oi.sku,
        COUNT(*) AS order_count,
        SUM(oi.quantity) AS total_units_sold,
        SUM(oi.subtotal) AS gross_revenue
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE o.payment_status = 'paid'
    GROUP BY oi.product_name, oi.sku
    ORDER BY total_units_sold DESC
    LIMIT 10
")->fetchAll();

// Category Revenue Distribution
$categorySales = $pdo->query("
    SELECT 
        c.name AS category_name,
        COUNT(DISTINCT o.id) AS total_orders,
        SUM(oi.quantity) AS total_units,
        SUM(oi.subtotal) AS category_revenue
    FROM categories c
    JOIN products p ON p.category_id = c.id
    JOIN order_items oi ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.payment_status = 'paid'
    GROUP BY c.id, c.name
    ORDER BY category_revenue DESC
")->fetchAll();

$adminHeaderHeading = 'Item & Category Performance Reports';
$adminTitle = 'Item Performance | GLAIMAGAIN Admin';
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold text-emerald mb-1">Garment Performance &amp; Collection Analytics</h5>
        <p class="text-muted small mb-0">Detailed breakdown of top grossing pieces and category volumes.</p>
    </div>
    <a href="<?= BASE_URL ?>admin/sales/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Sales Dashboard
    </a>
</div>

<div class="row g-4">
    <!-- Top Products Table -->
    <div class="col-lg-7">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fas fa-crown text-gold me-2"></i>Top Grossing Silhouettes</h5>
            </div>
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                        <tr>
                            <th>Garment &amp; SKU</th>
                            <th>Orders</th>
                            <th>Units Sold</th>
                            <th class="text-end">Gross Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topProducts)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">No historical paid sales recorded yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($topProducts as $tp): ?>
                                <tr>
                                    <td>
                                        <strong class="text-emerald fs-6"><?= e($tp['product_name']) ?></strong><br>
                                        <small class="text-muted">SKU: <?= e($tp['sku']) ?></small>
                                    </td>
                                    <td><?= (int)$tp['order_count'] ?></td>
                                    <td><span class="badge bg-emerald text-gold fw-bold"><?= (int)$tp['total_units_sold'] ?> units</span></td>
                                    <td class="text-end fw-bold text-gold"><?= formatPrice($tp['gross_revenue']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Category Distribution -->
    <div class="col-lg-5">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fas fa-layer-group text-gold me-2"></i>Collection Revenue</h5>
            </div>
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                        <tr>
                            <th>Collection</th>
                            <th>Units</th>
                            <th class="text-end">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categorySales)): ?>
                            <tr><td colspan="3" class="text-center py-4 text-muted">No category data available.</td></tr>
                        <?php else: ?>
                            <?php foreach ($categorySales as $cs): ?>
                                <tr>
                                    <td><strong class="text-emerald"><?= e($cs['category_name']) ?></strong></td>
                                    <td><?= (int)$cs['total_units'] ?></td>
                                    <td class="text-end fw-bold"><?= formatPrice($cs['category_revenue']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/admin-footer.php'; ?>

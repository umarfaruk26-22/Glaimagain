<?php
/**
 * GLAIMAGAIN - Admin Executive Dashboard
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();

// 1. KPI Aggregations
$totalSales = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'paid'")->fetchColumn();
$totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
$pendingOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status IN ('pending', 'processing')")->fetchColumn();
$lowStockCount = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock <= 5 AND status = 'active'")->fetchColumn();

// 2. Recent Orders
$recentOrders = $pdo->query("
    SELECT o.*, u.username, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.id DESC LIMIT 6
")->fetchAll();

// 3. Low Stock Garments
$lowStockProducts = $pdo->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.stock <= 8 AND p.status = 'active'
    ORDER BY p.stock ASC LIMIT 5
")->fetchAll();

// 4. Sales Trends (Last 7 Days) for Chart.js
$salesLast7Days = [];
$daysLabels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $daysLabels[] = date('D (M d)', strtotime($date));
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'paid' AND DATE(created_at) = ?");
    $stmt->execute([$date]);
    $salesLast7Days[] = (float)$stmt->fetchColumn();
}

// 5. Order Status Breakdown for Doughnut Chart
$statusCounts = [
    'confirmed' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'confirmed'")->fetchColumn(),
    'processing' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'processing'")->fetchColumn(),
    'shipped' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'shipped'")->fetchColumn(),
    'delivered' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'delivered'")->fetchColumn(),
    'cancelled' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'cancelled'")->fetchColumn()
];

$adminHeaderHeading = 'Executive Dashboard';
$adminTitle = 'Executive Dashboard | GLAIMAGAIN Admin';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<!-- KPI Stat Cards Grid -->
<div class="row g-4 mb-4">
    <!-- Card 1: Total Sales -->
    <div class="col-xl-4 col-md-6">
        <div class="stat-card gold-accent">
            <div class="stat-card-header">
                <span class="stat-card-title">Verified Revenue</span>
                <div class="stat-card-icon"><i class="fas fa-coins"></i></div>
            </div>
            <div class="stat-card-value"><?= formatPrice($totalSales) ?></div>
            <div class="stat-card-desc"><i class="fas fa-check-circle text-success me-1"></i> From verified Razorpay transactions</div>
        </div>
    </div>

    <!-- Card 2: Total Orders -->
    <div class="col-xl-4 col-md-6">
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Total Orders</span>
                <div class="stat-card-icon"><i class="fas fa-shopping-bag"></i></div>
            </div>
            <div class="stat-card-value"><?= number_format($totalOrders) ?></div>
            <div class="stat-card-desc"><span class="badge bg-warning text-dark me-1"><?= $pendingOrders ?> pending</span> in pipeline</div>
        </div>
    </div>

    <!-- Card 3: Registered Patrons -->
    <div class="col-xl-4 col-md-6">
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Registered Patrons</span>
                <div class="stat-card-icon"><i class="fas fa-users"></i></div>
            </div>
            <div class="stat-card-value"><?= number_format($totalUsers) ?></div>
            <div class="stat-card-desc"><i class="fas fa-user-check text-gold me-1"></i> Active boutique accounts</div>
        </div>
    </div>

    <!-- Card 4: Active Garments -->
    <div class="col-xl-4 col-md-6">
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Active Products</span>
                <div class="stat-card-icon"><i class="fas fa-tshirt"></i></div>
            </div>
            <div class="stat-card-value"><?= number_format($totalProducts) ?></div>
            <div class="stat-card-desc">Catalog silhouettes online</div>
        </div>
    </div>

    <!-- Card 5: Low Stock Alerts -->
    <div class="col-xl-4 col-md-6">
        <div class="stat-card gold-accent">
            <div class="stat-card-header">
                <span class="stat-card-title">Low Stock Alert</span>
                <div class="stat-card-icon"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
            <div class="stat-card-value"><?= number_format($lowStockCount) ?></div>
            <div class="stat-card-desc"><span class="text-danger">&le; 5 units remaining</span></div>
        </div>
    </div>

    <!-- Card 6: Pending Processing -->
    <div class="col-xl-4 col-md-6">
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Unfulfilled Orders</span>
                <div class="stat-card-icon"><i class="fas fa-clock"></i></div>
            </div>
            <div class="stat-card-value"><?= number_format($pendingOrders) ?></div>
            <div class="stat-card-desc">Requires packing / dispatch</div>
        </div>
    </div>
</div>

<!-- Chart.js Analytics Visualizations -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fas fa-chart-line text-gold me-2"></i>Sales Velocity (Last 7 Days)</h5>
                <span class="small text-muted">Daily INR Volume</span>
            </div>
            <div class="p-4">
                <canvas id="salesTrendsChart" height="120"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fas fa-chart-pie text-gold me-2"></i>Order Pipeline Status</h5>
            </div>
            <div class="p-4 text-center">
                <canvas id="orderStatusChart" height="240"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Tables: Recent Commissions & Low Stock Matrix -->
<div class="row g-4">
    <!-- Recent Orders Table -->
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fas fa-receipt text-gold me-2"></i>Recent Patron Orders</h5>
                <a href="<?= BASE_URL ?>admin/orders/index.php" class="btn btn-admin-gold btn-sm">View All Orders</a>
            </div>
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Patron</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentOrders)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No orders recorded yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentOrders as $ro): ?>
                                <tr>
                                    <td>
                                        <strong class="text-emerald"><?= e($ro['order_number']) ?></strong><br>
                                        <small class="text-muted"><?= date('M d, H:i', strtotime($ro['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <div><strong><?= e($ro['shipping_full_name']) ?></strong></div>
                                        <small class="text-muted">@<?= e($ro['username']) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= formatPrice($ro['total_amount']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-status badge-status-<?= e($ro['payment_status']) ?>">
                                            <?= strtoupper(e($ro['payment_status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-status badge-status-<?= e($ro['order_status']) ?>">
                                            <?= strtoupper(e($ro['order_status'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= BASE_URL ?>admin/orders/view.php?id=<?= $ro['id'] ?>" class="btn btn-outline-dark btn-sm py-1 px-2" style="font-size: 12px;">
                                            Inspect <i class="fas fa-arrow-right text-gold ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Low Stock Matrix -->
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fas fa-boxes text-danger me-2"></i>Critical Stock</h5>
                <a href="<?= BASE_URL ?>admin/inventory/index.php" class="small text-gold text-decoration-underline">Manage</a>
            </div>
            <div class="p-3">
                <?php if (empty($lowStockProducts)): ?>
                    <div class="text-center py-4 text-muted small">
                        <i class="fas fa-check-circle text-success fs-3 mb-2"></i>
                        <div>All garment inventory healthy.</div>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($lowStockProducts as $lp): ?>
                            <div class="p-3 border rounded bg-light d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold text-emerald small text-truncate" style="max-width: 170px;"><?= e($lp['name']) ?></div>
                                    <span class="badge bg-secondary" style="font-size: 10px;"><?= e($lp['category_name']) ?></span>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-danger fs-6"><?= (int)$lp['stock'] ?> Left</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php 
$adminExtraScripts = '
<script>
    // 1. Sales Velocity Line Chart
    const ctxSales = document.getElementById("salesTrendsChart").getContext("2d");
    new Chart(ctxSales, {
        type: "line",
        data: {
            labels: ' . json_encode($daysLabels) . ',
            datasets: [{
                label: "Revenue (INR)",
                data: ' . json_encode($salesLast7Days) . ',
                borderColor: "#013C26",
                backgroundColor: "rgba(185, 144, 54, 0.15)",
                pointBackgroundColor: "#B99036",
                pointBorderColor: "#013C26",
                pointRadius: 5,
                fill: true,
                tension: 0.35,
                borderWidth: 2.5
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) { return "₹" + value.toLocaleString(); }
                    }
                }
            }
        }
    });

    // 2. Order Status Breakdown Doughnut Chart
    const ctxStatus = document.getElementById("orderStatusChart").getContext("2d");
    new Chart(ctxStatus, {
        type: "doughnut",
        data: {
            labels: ["Confirmed", "Processing", "Shipped", "Delivered", "Cancelled"],
            datasets: [{
                data: [' . implode(',', array_values($statusCounts)) . '],
                backgroundColor: ["#4338CA", "#A16207", "#2563EB", "#15803D", "#B91C1C"]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: "bottom" }
            }
        }
    });
</script>
';
require_once __DIR__ . '/../includes/admin-footer.php'; 
?>

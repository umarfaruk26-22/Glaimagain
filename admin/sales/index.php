<?php
/**
 * GLAIMAGAIN - Admin Sales & Financials Dashboard
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();

// 1. Date Range Filtering
$startDate = trim($_GET['start_date'] ?? date('Y-m-01'));
$endDate = trim($_GET['end_date'] ?? date('Y-m-d'));

// 2. High-Level Metrics
$todaySales = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'paid' AND DATE(created_at) = CURDATE()")->fetchColumn();
$weekSales = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'paid' AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)")->fetchColumn();
$monthSales = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'paid' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();
$yearSales = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'paid' AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();
$lifetimeRevenue = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'paid'")->fetchColumn();

// 3. Filtered Date Range Metrics
$filterStmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_orders,
        COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) AS paid_orders,
        COUNT(CASE WHEN order_status = 'cancelled' THEN 1 END) AS cancelled_orders,
        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) AS net_revenue,
        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN discount_amount ELSE 0 END), 0) AS total_discounts,
        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN shipping_fee ELSE 0 END), 0) AS total_shipping
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$filterStmt->execute([$startDate, $endDate]);
$rangeMetrics = $filterStmt->fetch();

// 4. Daily Breakdown for Selected Date Range
$dailyStmt = $pdo->prepare("
    SELECT 
        DATE(created_at) AS order_date,
        COUNT(*) AS orders_count,
        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) AS day_revenue
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY order_date ASC
");
$dailyStmt->execute([$startDate, $endDate]);
$dailyRecords = $dailyStmt->fetchAll();

$chartLabels = [];
$chartRevenue = [];
foreach ($dailyRecords as $dr) {
    $chartLabels[] = date('M d', strtotime($dr['order_date']));
    $chartRevenue[] = (float)$dr['day_revenue'];
}

$adminHeaderHeading = 'Sales & Financial Reports';
$adminTitle = 'Sales Reports | GLAIMAGAIN Admin';
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<!-- KPI Stat Cards -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card gold-accent">
            <div class="stat-card-header">
                <span class="stat-card-title">Today's Revenue</span>
                <div class="stat-card-icon"><i class="fas fa-calendar-day"></i></div>
            </div>
            <div class="stat-card-value"><?= formatPrice($todaySales) ?></div>
            <div class="stat-card-desc"><?= date('F d, Y') ?></div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">This Week</span>
                <div class="stat-card-icon"><i class="fas fa-calendar-week"></i></div>
            </div>
            <div class="stat-card-value"><?= formatPrice($weekSales) ?></div>
            <div class="stat-card-desc">Current calendar week</div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card gold-accent">
            <div class="stat-card-header">
                <span class="stat-card-title">This Month</span>
                <div class="stat-card-icon"><i class="fas fa-calendar-alt"></i></div>
            </div>
            <div class="stat-card-value"><?= formatPrice($monthSales) ?></div>
            <div class="stat-card-desc"><?= date('F Y') ?></div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Lifetime Revenue</span>
                <div class="stat-card-icon"><i class="fas fa-vault"></i></div>
            </div>
            <div class="stat-card-value"><?= formatPrice($lifetimeRevenue) ?></div>
            <div class="stat-card-desc">All verified orders</div>
        </div>
    </div>
</div>

<!-- Date Range Filter Card -->
<div class="admin-card p-4 mb-4">
    <form method="GET" action="<?= BASE_URL ?>admin/sales/index.php" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label small fw-bold text-emerald text-uppercase">Start Date</label>
            <input type="date" name="start_date" class="form-control" value="<?= e($startDate) ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-bold text-emerald text-uppercase">End Date</label>
            <input type="date" name="end_date" class="form-control" value="<?= e($endDate) ?>" required>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-admin-gold flex-grow-1">
                <i class="fas fa-filter me-1"></i> APPLY DATE RANGE
            </button>
            <a href="<?= BASE_URL ?>admin/sales/reports.php" class="btn btn-outline-dark">
                <i class="fas fa-file-invoice me-1"></i> Detailed Breakdown
            </a>
        </div>
    </form>
</div>

<!-- Selected Period Highlights -->
<div class="row g-4 mb-4">
    <div class="col-md-3 col-6">
        <div class="p-3 bg-white border rounded shadow-sm text-center">
            <div class="text-muted small text-uppercase">Range Orders</div>
            <h4 class="fw-bold text-emerald mt-1 mb-0"><?= (int)$rangeMetrics['total_orders'] ?></h4>
            <small class="text-success"><?= (int)$rangeMetrics['paid_orders'] ?> verified paid</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="p-3 bg-white border rounded shadow-sm text-center">
            <div class="text-muted small text-uppercase">Range Net Revenue</div>
            <h4 class="fw-bold text-gold mt-1 mb-0"><?= formatPrice($rangeMetrics['net_revenue']) ?></h4>
            <small class="text-muted">Delivered/Confirmed</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="p-3 bg-white border rounded shadow-sm text-center">
            <div class="text-muted small text-uppercase">Promotions Absorbed</div>
            <h4 class="fw-bold text-danger mt-1 mb-0"><?= formatPrice($rangeMetrics['total_discounts']) ?></h4>
            <small class="text-muted">Total patron savings</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="p-3 bg-white border rounded shadow-sm text-center">
            <div class="text-muted small text-uppercase">Shipping Collected</div>
            <h4 class="fw-bold text-emerald mt-1 mb-0"><?= formatPrice($rangeMetrics['total_shipping']) ?></h4>
            <small class="text-muted">Express logistics</small>
        </div>
    </div>
</div>

<!-- Chart & Daily Table -->
<div class="row g-4">
    <div class="col-lg-12">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fas fa-chart-area text-gold me-2"></i>Daily Revenue Curve (<?= date('M d', strtotime($startDate)) ?> &ndash; <?= date('M d', strtotime($endDate)) ?>)</h5>
            </div>
            <div class="p-4">
                <canvas id="rangeSalesChart" height="90"></canvas>
            </div>
        </div>
    </div>
</div>

<?php 
$adminExtraScripts = '
<script>
    const ctx = document.getElementById("rangeSalesChart").getContext("2d");
    new Chart(ctx, {
        type: "bar",
        data: {
            labels: ' . json_encode($chartLabels) . ',
            datasets: [{
                label: "Revenue (INR)",
                data: ' . json_encode($chartRevenue) . ',
                backgroundColor: "rgba(1, 60, 38, 0.85)",
                borderColor: "#B99036",
                borderWidth: 1.5,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(v) { return "₹" + v.toLocaleString(); }
                    }
                }
            }
        }
    });
</script>
';
require_once __DIR__ . '/../../includes/admin-footer.php'; 
?>

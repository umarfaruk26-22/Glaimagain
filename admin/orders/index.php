<?php
/**
 * GLAIMAGAIN - Admin Orders Pipeline
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();

// Search & Filter
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$paymentFilter = trim($_GET['payment_status'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(o.order_number LIKE ? OR o.shipping_full_name LIKE ? OR o.shipping_mobile LIKE ? OR u.email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($statusFilter)) {
    $where[] = "o.order_status = ?";
    $params[] = $statusFilter;
}

if (!empty($paymentFilter)) {
    $where[] = "o.payment_status = ?";
    $params[] = $paymentFilter;
}

$whereSql = implode(" AND ", $where);

// Fetch orders with item count
$stmt = $pdo->prepare("
    SELECT o.*, u.username, u.email,
           (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS total_items
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE {$whereSql}
    ORDER BY o.id DESC
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

$adminHeaderHeading = 'Orders Pipeline';
$adminTitle = 'Orders | GLAIMAGAIN Admin';
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h5 class="fw-bold text-emerald mb-1">Patron Orders &amp; Fulfillment Pipeline (<?= count($orders) ?>)</h5>
        <p class="text-muted small mb-0">Track order processing, dispatch status, and verified Razorpay payments.</p>
    </div>
</div>

<!-- Search & Filters Bar -->
<div class="admin-card p-3 mb-4">
    <form method="GET" action="<?= BASE_URL ?>admin/orders/index.php" class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search order #, customer name, mobile..." value="<?= e($search) ?>">
            </div>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select form-select-sm">
                <option value="">All Order Statuses</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : '' ?>>Processing</option>
                <option value="packed" <?= $statusFilter === 'packed' ? 'selected' : '' ?>>Packed</option>
                <option value="shipped" <?= $statusFilter === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="payment_status" class="form-select form-select-sm">
                <option value="">All Payments</option>
                <option value="paid" <?= $paymentFilter === 'paid' ? 'selected' : '' ?>>Paid (Verified)</option>
                <option value="pending" <?= $paymentFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="failed" <?= $paymentFilter === 'failed' ? 'selected' : '' ?>>Failed</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-admin-primary btn-sm flex-grow-1">Filter</button>
            <a href="<?= BASE_URL ?>admin/orders/index.php" class="btn btn-outline-secondary btn-sm" title="Clear Filters"><i class="fas fa-times"></i></a>
        </div>
    </form>
</div>

<!-- Orders Table -->
<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Date</th>
                    <th>Patron Particulars</th>
                    <th>Items</th>
                    <th>Total Amount</th>
                    <th>Payment</th>
                    <th>Order Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No orders match filter query.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td>
                                <strong class="text-emerald fs-6"><?= e($o['order_number']) ?></strong>
                            </td>
                            <td>
                                <div class="small"><?= date('M d, Y', strtotime($o['created_at'])) ?></div>
                                <small class="text-muted"><?= date('h:i A', strtotime($o['created_at'])) ?></small>
                            </td>
                            <td>
                                <strong class="d-block"><?= e($o['shipping_full_name']) ?></strong>
                                <small class="text-muted"><i class="fas fa-phone-alt me-1 text-gold"></i><?= e($o['shipping_mobile']) ?> &bull; <?= e($o['shipping_city']) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= (int)$o['total_items'] ?> item(s)</span>
                            </td>
                            <td>
                                <strong class="text-emerald"><?= formatPrice($o['total_amount']) ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-status badge-status-<?= e($o['payment_status']) ?>">
                                    <?= strtoupper(e($o['payment_status'])) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-status badge-status-<?= e($o['order_status']) ?>">
                                    <?= strtoupper(e($o['order_status'])) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>admin/orders/view.php?id=<?= $o['id'] ?>" class="btn btn-outline-dark btn-sm py-1 px-3" style="font-size: 12px;">
                                    Inspect &amp; Manage <i class="fas fa-arrow-right ms-1 text-gold"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/admin-footer.php'; ?>

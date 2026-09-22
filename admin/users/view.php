<?php
/**
 * GLAIMAGAIN - Admin User Profile & Order History Inspector
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();
$userId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    setFlashMessage('danger', 'Patron not found.');
    header('Location: ' . BASE_URL . 'admin/users/index.php');
    exit;
}

// Fetch user addresses
$addrStmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
$addrStmt->execute([$userId]);
$addresses = $addrStmt->fetchAll();

// Fetch user orders
$orderStmt = $pdo->prepare("
    SELECT o.*,
           (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS total_items
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.id DESC
");
$orderStmt->execute([$userId]);
$orders = $orderStmt->fetchAll();

$totalSpent = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE user_id = {$userId} AND payment_status = 'paid'")->fetchColumn();

$adminHeaderHeading = 'Patron Portfolio: ' . e($user['first_name'] . ' ' . $user['last_name']);
$adminTitle = 'Patron Profile | GLAIMAGAIN Admin';
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold text-emerald mb-1"><?= e($user['first_name'] . ' ' . $user['last_name']) ?> (@<?= e($user['username']) ?>)</h5>
        <span class="text-muted small">Registered on <?= date('F d, Y', strtotime($user['created_at'])) ?></span>
    </div>
    <a href="<?= BASE_URL ?>admin/users/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back to Patrons
    </a>
</div>

<div class="row g-4">
    <!-- Left Column: Patron Overview & Addresses -->
    <div class="col-lg-4">
        <div class="admin-card p-4 mb-4">
            <div class="text-center py-3 border-bottom mb-3">
                <div class="action-btn bg-emerald text-gold rounded-circle mx-auto d-flex align-items-center justify-content-center mb-2" style="width: 60px; height: 60px; font-size: 24px;">
                    <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
                </div>
                <h5 class="fw-bold text-emerald mb-0"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></h5>
                <span class="badge badge-status badge-status-<?= e($user['status']) ?> mt-1"><?= strtoupper(e($user['status'])) ?></span>
            </div>

            <div class="small mb-3">
                <div class="mb-2"><strong>Email:</strong> <br><?= e($user['email']) ?></div>
                <div class="mb-2"><strong>Mobile:</strong> <br><?= e($user['mobile']) ?></div>
                <div class="mb-2"><strong>Lifetime Spend:</strong> <br><strong class="text-gold fs-6"><?= formatPrice($totalSpent) ?></strong></div>
                <div><strong>Total Commissions:</strong> <br><?= count($orders) ?> order(s)</div>
            </div>

            <hr>
            <a href="<?= BASE_URL ?>admin/users/edit.php?id=<?= $user['id'] ?>" class="btn btn-admin-gold btn-sm w-100">
                <i class="fas fa-edit me-1"></i> Edit Patron Status
            </a>
        </div>

        <!-- Saved Addresses -->
        <div class="admin-card p-4">
            <h6 class="fw-bold text-emerald text-uppercase letter-spacing-1 mb-3">Saved Addresses (<?= count($addresses) ?>)</h6>
            <?php if (empty($addresses)): ?>
                <div class="text-muted small">No saved addresses.</div>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($addresses as $addr): ?>
                        <div class="p-3 border rounded bg-light small">
                            <div class="d-flex justify-content-between mb-1">
                                <strong class="text-emerald"><?= e($addr['full_name']) ?></strong>
                                <span class="badge <?= $addr['is_default'] ? 'bg-gold text-dark' : 'bg-secondary' ?>" style="font-size: 9px;"><?= strtoupper(e($addr['address_type'])) ?></span>
                            </div>
                            <div class="text-muted"><?= e($addr['address_line_1']) ?>, <?= e($addr['city']) ?> - <?= e($addr['pincode']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column: Order Commission History -->
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fas fa-box-open text-gold me-2"></i>Patron Order History (<?= count($orders) ?>)</h5>
            </div>
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No orders placed by this patron.</td></tr>
                        <?php else: ?>
                            <?php foreach ($orders as $ord): ?>
                                <tr>
                                    <td><strong class="text-emerald"><?= e($ord['order_number']) ?></strong></td>
                                    <td class="small text-muted"><?= date('M d, Y', strtotime($ord['created_at'])) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= (int)$ord['total_items'] ?> item(s)</span></td>
                                    <td><strong><?= formatPrice($ord['total_amount']) ?></strong></td>
                                    <td>
                                        <span class="badge badge-status badge-status-<?= e($ord['payment_status']) ?>">
                                            <?= strtoupper(e($ord['payment_status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-status badge-status-<?= e($ord['order_status']) ?>">
                                            <?= strtoupper(e($ord['order_status'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= BASE_URL ?>admin/orders/view.php?id=<?= $ord['id'] ?>" class="btn btn-outline-dark btn-sm py-1 px-2" style="font-size: 11px;">
                                            Inspect <i class="fas fa-arrow-right ms-1 text-gold"></i>
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
</div>

<?php require_once __DIR__ . '/../../includes/admin-footer.php'; ?>

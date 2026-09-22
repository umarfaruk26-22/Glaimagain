<?php
/**
 * GLAIMAGAIN - Admin Users / Patrons Management
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();

// Search & Filter
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR u.mobile LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($statusFilter)) {
    $where[] = "u.status = ?";
    $params[] = $statusFilter;
}

$whereSql = implode(" AND ", $where);

// Fetch users with order count and total spend
$stmt = $pdo->prepare("
    SELECT u.*,
           (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS total_orders,
           (SELECT COALESCE(SUM(total_amount), 0) FROM orders o WHERE o.user_id = u.id AND o.payment_status = 'paid') AS total_spent
    FROM users u
    WHERE {$whereSql}
    ORDER BY u.id DESC
");
$stmt->execute($params);
$users = $stmt->fetchAll();

$adminHeaderHeading = 'Patron Management';
$adminTitle = 'Users | GLAIMAGAIN Admin';
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h5 class="fw-bold text-emerald mb-1">Registered Boutique Patrons (<?= count($users) ?>)</h5>
        <p class="text-muted small mb-0">Inspect clientele portfolios, commission histories, and membership status.</p>
    </div>
</div>

<!-- Search & Filter Bar -->
<div class="admin-card p-3 mb-4">
    <form method="GET" action="<?= BASE_URL ?>admin/users/index.php" class="row g-2 align-items-center">
        <div class="col-md-7">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search by name, username, email, or mobile..." value="<?= e($search) ?>">
            </div>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="blocked" <?= $statusFilter === 'blocked' ? 'selected' : '' ?>>Blocked</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-admin-primary btn-sm flex-grow-1">Filter</button>
            <a href="<?= BASE_URL ?>admin/users/index.php" class="btn btn-outline-secondary btn-sm" title="Clear Filters"><i class="fas fa-times"></i></a>
        </div>
    </form>
</div>

<!-- Users Table -->
<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Patron Name</th>
                    <th>Username &amp; Email</th>
                    <th>Mobile</th>
                    <th>Orders</th>
                    <th>Total Spend</th>
                    <th>Registered</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="9" class="text-center py-4 text-muted">No patrons found.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>#<?= $u['id'] ?></td>
                            <td>
                                <strong class="text-emerald fs-6"><?= e($u['first_name'] . ' ' . $u['last_name']) ?></strong>
                            </td>
                            <td>
                                <div class="small fw-bold">@<?= e($u['username']) ?></div>
                                <small class="text-muted"><?= e($u['email']) ?></small>
                            </td>
                            <td class="small"><?= e($u['mobile']) ?></td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= (int)$u['total_orders'] ?> orders</span>
                            </td>
                            <td>
                                <strong><?= formatPrice($u['total_spent']) ?></strong>
                            </td>
                            <td class="small text-muted">
                                <?= date('M d, Y', strtotime($u['created_at'])) ?>
                            </td>
                            <td>
                                <span class="badge badge-status badge-status-<?= e($u['status']) ?>">
                                    <?= strtoupper(e($u['status'])) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <a href="<?= BASE_URL ?>admin/users/view.php?id=<?= $u['id'] ?>" class="btn btn-outline-dark btn-sm py-1 px-2" title="Inspect Patron Portfolio">
                                        <i class="fas fa-id-card"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>admin/users/edit.php?id=<?= $u['id'] ?>" class="btn btn-outline-secondary btn-sm py-1 px-2" title="Edit Status">
                                        <i class="fas fa-user-edit"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>admin/users/delete.php?id=<?= $u['id'] ?>&csrf_token=<?= getCsrfToken() ?>" class="btn btn-outline-danger btn-sm py-1 px-2 btn-confirm-delete" data-confirm-message="<?= $u['status'] === 'active' ? 'Block this patron?' : 'Unblock this patron?' ?>" title="<?= $u['status'] === 'active' ? 'Block Patron' : 'Unblock Patron' ?>">
                                        <i class="fas <?= $u['status'] === 'active' ? 'fa-ban' : 'fa-check' ?>"></i>
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

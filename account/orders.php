<?php
/**
 * GLAIMAGAIN - Customer Order History
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireUserLogin();
$currentUser = getCurrentUser();
$pdo = getDb();

// Fetch orders
$stmt = $pdo->prepare("
    SELECT o.*,
           (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS total_items
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.id DESC
");
$stmt->execute([$currentUser['id']]);
$orders = $stmt->fetchAll();

$pageTitle = 'My Orders | GLAIMAGAIN';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="bg-emerald text-white py-4 border-bottom border-gold-subtle">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <span class="text-gold fw-bold letter-spacing-3 small text-uppercase">Patron Dashboard</span>
            <h1 class="h3 fw-bold text-white mb-0">ORDER HISTORY</h1>
        </div>
        <a href="<?= BASE_URL ?>shop.php" class="btn btn-luxury-gold btn-sm">
            <i class="fas fa-shopping-bag me-1"></i> SHOP NEW RELEASES
        </a>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">
        <!-- Account Sidebar Navigation -->
        <div class="col-lg-3">
            <div class="p-3 bg-white border border-gold-subtle rounded shadow-sm">
                <div class="text-center py-3 border-bottom mb-3">
                    <div class="action-btn bg-emerald text-gold rounded-circle mx-auto d-flex align-items-center justify-content-center mb-2" style="width: 54px; height: 54px; font-size: 22px;">
                        <?= strtoupper(substr($currentUser['first_name'], 0, 1)) ?>
                    </div>
                    <h6 class="fw-bold text-emerald mb-0"><?= e($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?></h6>
                    <span class="text-muted small">@<?= e($currentUser['username']) ?></span>
                </div>
                <div class="d-flex flex-column gap-1">
                    <a href="<?= BASE_URL ?>account/profile.php" class="p-2 rounded text-decoration-none text-muted">
                        <i class="fas fa-id-card me-2"></i> Profile Details
                    </a>
                    <a href="<?= BASE_URL ?>account/orders.php" class="p-2 rounded text-decoration-none fw-bold bg-offwhite text-emerald border-start border-3 border-gold">
                        <i class="fas fa-box-open text-gold me-2"></i> Order History
                    </a>
                    <a href="<?= BASE_URL ?>account/addresses.php" class="p-2 rounded text-decoration-none text-muted">
                        <i class="fas fa-map-marker-alt me-2"></i> Delivery Addresses
                    </a>
                    <a href="<?= BASE_URL ?>account/change-password.php" class="p-2 rounded text-decoration-none text-muted">
                        <i class="fas fa-lock me-2"></i> Security &amp; Password
                    </a>
                    <hr class="my-2 border-secondary">
                    <a href="<?= BASE_URL ?>logout.php" class="p-2 rounded text-decoration-none text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Sign Out
                    </a>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="col-lg-9">
            <div class="p-4 p-md-5 bg-white border border-gold-subtle rounded shadow-sm">
                <h4 class="fw-bold text-emerald mb-1">YOUR COMMISSIONS &amp; ORDERS</h4>
                <p class="text-muted small mb-4">Review tracking status, past invoices, and delivery histories.</p>

                <?php if (empty($orders)): ?>
                    <div class="text-center py-5 bg-offwhite border rounded p-4">
                        <i class="fas fa-box-open text-gold fs-1 mb-2"></i>
                        <h5 class="text-emerald fw-bold">No Orders Placed Yet</h5>
                        <p class="text-muted small mb-3">Explore our latest luxury drops and commission your first piece.</p>
                        <a href="<?= BASE_URL ?>shop.php" class="btn btn-luxury-primary btn-sm">EXPLORE COLLECTIONS</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr class="small text-uppercase">
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th>Total Amount</th>
                                    <th>Payment</th>
                                    <th>Order Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $ord): ?>
                                    <tr>
                                        <td>
                                            <strong class="text-emerald"><?= e($ord['order_number']) ?></strong>
                                        </td>
                                        <td class="small text-muted">
                                            <?= date('M d, Y', strtotime($ord['created_at'])) ?>
                                        </td>
                                        <td class="small">
                                            <?= (int)$ord['total_items'] ?> item(s)
                                        </td>
                                        <td>
                                            <strong><?= formatPrice($ord['total_amount']) ?></strong>
                                        </td>
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
                                            <a href="<?= BASE_URL ?>account/order-details.php?id=<?= $ord['id'] ?>" class="btn btn-outline-dark btn-sm py-1 px-3" style="font-size: 12px;">
                                                Details <i class="fas fa-arrow-right ms-1 text-gold"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

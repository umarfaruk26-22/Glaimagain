<?php
/**
 * GLAIMAGAIN - Order Success Confirmation Page
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

requireUserLogin();
$currentUser = getCurrentUser();
$orderNumber = trim($_GET['order'] ?? '');
$pdo = getDb();

$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? AND user_id = ? LIMIT 1");
$stmt->execute([$orderNumber, $currentUser['id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ' . BASE_URL . 'account/orders.php');
    exit;
}

// Fetch items
$itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$itemStmt->execute([$order['id']]);
$items = $itemStmt->fetchAll();

$pageTitle = 'Order Placed Successfully | GLAIMAGAIN';
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-5 bg-offwhite min-vh-75 d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-9">
                <div class="p-4 p-md-5 bg-white border border-gold-subtle rounded shadow-lg text-center">
                    <div class="action-btn bg-emerald text-gold rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px; font-size: 30px; border: 2px solid #B99036;">
                        <i class="fas fa-check"></i>
                    </div>

                    <span class="text-gold fw-bold letter-spacing-3 small text-uppercase">Payment Verified &amp; Confirmed</span>
                    <h2 class="fw-bold text-emerald mt-1 mb-2 font-serif">ORDER PLACED SUCCESSFULLY</h2>
                    <p class="text-muted small max-w-500 mx-auto mb-4">
                        Thank you for your commission, <strong><?= e($currentUser['first_name']) ?></strong>. Our master tailors have received your order and are preparing your luxury garments.
                    </p>

                    <!-- Order Summary Box -->
                    <div class="p-4 bg-offwhite rounded border text-start mb-4">
                        <div class="d-flex justify-content-between pb-2 mb-2 border-bottom small">
                            <span class="text-muted">Order Number</span>
                            <strong class="text-emerald"><?= e($order['order_number']) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between pb-2 mb-2 border-bottom small">
                            <span class="text-muted">Payment Status</span>
                            <span class="badge bg-emerald text-gold fw-bold text-uppercase"><i class="fas fa-shield-alt me-1"></i><?= strtoupper(e($order['payment_status'])) ?></span>
                        </div>
                        <div class="d-flex justify-content-between pb-2 mb-2 border-bottom small">
                            <span class="text-muted">Total Amount</span>
                            <strong class="text-gold fs-6"><?= formatPrice($order['total_amount']) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">Delivery Address</span>
                            <span class="text-end text-truncate" style="max-width: 250px;">
                                <?= e($order['shipping_full_name']) ?>, <?= e($order['shipping_city']) ?> (<?= e($order['shipping_pincode']) ?>)
                            </span>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
                        <a href="<?= BASE_URL ?>account/order-details.php?id=<?= $order['id'] ?>" class="btn btn-luxury-primary">
                            <i class="fas fa-receipt me-1"></i> VIEW ORDER INVOICE
                        </a>
                        <a href="<?= BASE_URL ?>shop.php" class="btn btn-luxury-outline">
                            CONTINUE SHOPPING
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

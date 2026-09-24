<?php
/**
 * GLAIMAGAIN - Order Details & Invoice View
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireUserLogin();
$currentUser = getCurrentUser();
$orderId = (int)($_GET['id'] ?? 0);
$pdo = getDb();

if ($orderId <= 0) {
    header('Location: ' . BASE_URL . 'account/orders.php');
    exit;
}

// 1. Fetch Order and Verify Customer Ownership
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order || (int)$order['user_id'] !== (int)$currentUser['id']) {
    setFlashMessage('danger', 'Access restricted. You do not have permission to inspect this order.');
    header('Location: ' . BASE_URL . 'account/orders.php');
    exit;
}

// 2. Fetch Order Items Snapshot
$itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC");
$itemStmt->execute([$order['id']]);
$orderItems = $itemStmt->fetchAll();

// 3. Fetch Payment Record
$payStmt = $pdo->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1");
$payStmt->execute([$order['id']]);
$payment = $payStmt->fetch();

$pageTitle = 'Order ' . e($order['order_number']) . ' | GLAIMAGAIN';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="bg-emerald text-white py-4 border-bottom border-gold-subtle d-print-none">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <span class="text-gold fw-bold letter-spacing-3 small text-uppercase">Commission Details</span>
            <h1 class="h3 fw-bold text-white mb-0">ORDER #<?= e($order['order_number']) ?></h1>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-luxury-gold btn-sm me-2">
                <i class="fas fa-print me-1"></i> Print Invoice
            </button>
            <a href="<?= BASE_URL ?>account/orders.php" class="btn btn-outline-light btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back to Orders
            </a>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">
        <!-- Invoice Card -->
        <div class="col-12">
            <div class="p-4 p-md-5 bg-white border border-gold-subtle rounded shadow-sm">
                <!-- Invoice Header -->
                <div class="row align-items-center pb-4 mb-4 border-bottom g-3">
                    <div class="col-md-6">
                        <img src="<?= BASE_URL ?>assets/images/glaimagain-logo.png" alt="GLAIMAGAIN" style="height: 52px; width: auto; object-fit: contain;" class="mb-2">
                        <p class="text-muted small mb-0">GLAIMAGAIN Flagship House &bull; <?= e(getSetting('store_address', 'Mumbai, India')) ?></p>
                        <p class="text-muted small mb-0">Concierge: <?= e(getSetting('contact_phone', '+91 98765 43210')) ?> | <?= e(getSetting('contact_email', 'concierge@glaimagain.com')) ?></p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h4 class="fw-bold text-emerald mb-1">OFFICIAL INVOICE</h4>
                        <div class="small"><strong>Order Ref:</strong> <?= e($order['order_number']) ?></div>
                        <div class="small"><strong>Date:</strong> <?= date('F d, Y - h:i A', strtotime($order['created_at'])) ?></div>
                        <div class="mt-2">
                            <span class="badge badge-status badge-status-<?= e($order['payment_status']) ?> me-1">Payment: <?= strtoupper(e($order['payment_status'])) ?></span>
                            <span class="badge badge-status badge-status-<?= e($order['order_status']) ?>">Status: <?= strtoupper(e($order['order_status'])) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Recipient & Payment Details -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <h6 class="fw-bold text-emerald text-uppercase letter-spacing-1 mb-2">Delivery Address</h6>
                        <div class="p-3 bg-offwhite rounded border small">
                            <strong class="d-block text-emerald"><?= e($order['shipping_full_name']) ?></strong>
                            <span><i class="fas fa-phone-alt text-gold me-1"></i> <?= e($order['shipping_mobile']) ?></span><br>
                            <?= e($order['shipping_address_1']) ?><?= !empty($order['shipping_address_2']) ? ', ' . e($order['shipping_address_2']) : '' ?><br>
                            <?= e($order['shipping_city']) ?>, <?= e($order['shipping_state']) ?> - <?= e($order['shipping_pincode']) ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold text-emerald text-uppercase letter-spacing-1 mb-2">Payment Details</h6>
                        <div class="p-3 bg-offwhite rounded border small">
                            <div><strong>Payment Gateway:</strong> <?= ucfirst(e($order['payment_method'])) ?></div>
                            <?php if (!empty($order['razorpay_payment_id'])): ?>
                                <div><strong>Razorpay Payment ID:</strong> <code class="text-emerald"><?= e($order['razorpay_payment_id']) ?></code></div>
                            <?php endif; ?>
                            <?php if (!empty($order['razorpay_order_id'])): ?>
                                <div><strong>Razorpay Order ID:</strong> <code class="text-muted"><?= e($order['razorpay_order_id']) ?></code></div>
                            <?php endif; ?>
                            <div><strong>Payment Verified:</strong> <?= $order['payment_status'] === 'paid' ? '<span class="text-success fw-bold"><i class="fas fa-shield-alt text-gold me-1"></i>Yes (HMAC-SHA256 Signature Verified)</span>' : '<span class="text-danger">Pending</span>' ?></div>
                        </div>
                    </div>
                </div>

                <!-- Items Snapshot Table -->
                <h6 class="fw-bold text-emerald text-uppercase letter-spacing-1 mb-3">Garment Items</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Item Particulars</th>
                                <th>SKU</th>
                                <th>Size</th>
                                <th>Color</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderItems as $it): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <?php if (!empty($it['product_image'])): ?>
                                                <img src="<?= getProductImageUrl($it['product_image']) ?>" alt="<?= e($it['product_name']) ?>" style="width: 44px; height: 52px; object-fit: cover; border-radius: 2px;">
                                            <?php endif; ?>
                                            <div>
                                                <strong class="text-emerald"><?= e($it['product_name']) ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="small text-muted"><?= e($it['sku']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= e($it['size']) ?></span></td>
                                    <td><span class="badge bg-light text-dark border"><?= e($it['color']) ?></span></td>
                                    <td class="text-end"><?= formatPrice($it['unit_price']) ?></td>
                                    <td class="text-center"><?= (int)$it['quantity'] ?></td>
                                    <td class="text-end fw-bold"><?= formatPrice($it['subtotal']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Order Totals -->
                <div class="row justify-content-end">
                    <div class="col-md-5">
                        <div class="p-3 bg-offwhite rounded border">
                            <div class="d-flex justify-content-between mb-2 small">
                                <span>Subtotal</span>
                                <strong><?= formatPrice($order['subtotal']) ?></strong>
                            </div>
                            <?php if ((float)$order['discount_amount'] > 0): ?>
                                <div class="d-flex justify-content-between mb-2 small text-success">
                                    <span>Discount Savings</span>
                                    <strong>-<?= formatPrice($order['discount_amount']) ?></strong>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between mb-2 small">
                                <span>Shipping Fee</span>
                                <strong><?= (float)$order['shipping_fee'] > 0 ? formatPrice($order['shipping_fee']) : '<span class="text-success">COMPLIMENTARY</span>' ?></strong>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between fs-6 fw-bold text-emerald">
                                <span>Total Paid</span>
                                <span class="text-gold"><?= formatPrice($order['total_amount']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($order['notes'])): ?>
                    <div class="mt-4 p-3 bg-light rounded small">
                        <strong>Order Notes:</strong> <?= e($order['notes']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

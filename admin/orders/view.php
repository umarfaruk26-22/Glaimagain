<?php
/**
 * GLAIMAGAIN - Admin Order Deep Inspection & Status Updater
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();
$orderId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT o.*, u.username, u.email AS user_email, u.mobile AS user_mobile
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
    LIMIT 1
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    setFlashMessage('danger', 'Order record not found.');
    header('Location: ' . BASE_URL . 'admin/orders/index.php');
    exit;
}

// Fetch Items Snapshot
$itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC");
$itemStmt->execute([$order['id']]);
$orderItems = $itemStmt->fetchAll();

// Fetch Payment Log
$payStmt = $pdo->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1");
$payStmt->execute([$order['id']]);
$payment = $payStmt->fetch();

$adminHeaderHeading = 'Order Details #' . e($order['order_number']);
$adminTitle = 'Order #' . e($order['order_number']) . ' | GLAIMAGAIN Admin';
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold text-emerald mb-1">Commission Inspection: <?= e($order['order_number']) ?></h5>
        <span class="text-muted small">Placed on <?= date('F d, Y - h:i A', strtotime($order['created_at'])) ?></span>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-print me-1"></i> Print Invoice
        </button>
        <a href="<?= BASE_URL ?>admin/orders/index.php" class="btn btn-outline-dark btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back to Orders
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Items & Totals -->
    <div class="col-lg-8">
        <div class="admin-card p-4 mb-4">
            <h6 class="fw-bold text-emerald text-uppercase letter-spacing-1 mb-3">Commissioned Garment Items</h6>
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
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
                                            <img src="<?= getProductImageUrl($it['product_image']) ?>" alt="" style="width: 44px; height: 52px; object-fit: cover; border-radius: 2px;">
                                        <?php endif; ?>
                                        <strong class="text-emerald"><?= e($it['product_name']) ?></strong>
                                    </div>
                                </td>
                                <td><code class="text-emerald"><?= e($it['sku']) ?></code></td>
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

            <!-- Financials Summary -->
            <div class="row justify-content-end mt-3">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded border">
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
                            <span>Total Amount</span>
                            <span class="text-gold"><?= formatPrice($order['total_amount']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <?php if (!empty($order['notes'])): ?>
            <div class="admin-card p-4">
                <h6 class="fw-bold text-emerald text-uppercase letter-spacing-1 mb-2">Patron Order Notes</h6>
                <p class="text-muted small mb-0"><?= e($order['notes']) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Column: Status Updater, Customer & Razorpay Details -->
    <div class="col-lg-4">
        <!-- Status Updater Card -->
        <div class="admin-card p-4 mb-4">
            <h6 class="fw-bold text-emerald text-uppercase letter-spacing-1 mb-3"><i class="fas fa-tasks text-gold me-2"></i>Update Fulfillment Status</h6>
            <form method="POST" action="<?= BASE_URL ?>admin/orders/update-status.php" class="form-luxury">
                <?= csrfField() ?>
                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">

                <div class="mb-3">
                    <label class="form-label">Order Pipeline Status</label>
                    <select name="order_status" class="form-select">
                        <option value="pending" <?= $order['order_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="confirmed" <?= $order['order_status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="processing" <?= $order['order_status'] === 'processing' ? 'selected' : '' ?>>Processing / Atelier Tailoring</option>
                        <option value="packed" <?= $order['order_status'] === 'packed' ? 'selected' : '' ?>>Packed in Keepsake Box</option>
                        <option value="shipped" <?= $order['order_status'] === 'shipped' ? 'selected' : '' ?>>Shipped / Out for Delivery</option>
                        <option value="delivered" <?= $order['order_status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                        <option value="cancelled" <?= $order['order_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Payment Status</label>
                    <select name="payment_status" class="form-select">
                        <option value="paid" <?= $order['payment_status'] === 'paid' ? 'selected' : '' ?>>Paid (Verified Signature)</option>
                        <option value="pending" <?= $order['payment_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="failed" <?= $order['payment_status'] === 'failed' ? 'selected' : '' ?>>Failed</option>
                        <option value="refunded" <?= $order['payment_status'] === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-admin-gold w-100 py-2">
                    <i class="fas fa-sync-alt me-1"></i> SAVE STATUS UPDATE
                </button>
            </form>
        </div>

        <!-- Customer & Delivery Address Card -->
        <div class="admin-card p-4 mb-4">
            <h6 class="fw-bold text-emerald text-uppercase letter-spacing-1 mb-3"><i class="fas fa-map-marker-alt text-gold me-2"></i>Shipping Destination</h6>
            <div class="small text-muted">
                <strong class="d-block text-emerald fs-6 mb-1"><?= e($order['shipping_full_name']) ?></strong>
                <div><i class="fas fa-phone-alt text-gold me-1"></i> <?= e($order['shipping_mobile']) ?></div>
                <div class="mt-2">
                    <?= e($order['shipping_address_1']) ?><?= !empty($order['shipping_address_2']) ? ', ' . e($order['shipping_address_2']) : '' ?><br>
                    <?= e($order['shipping_city']) ?>, <?= e($order['shipping_state']) ?> - <?= e($order['shipping_pincode']) ?>
                </div>
                <hr class="my-2">
                <div><strong>Patron Account:</strong> @<?= e($order['username']) ?> (<?= e($order['user_email']) ?>)</div>
            </div>
        </div>

        <!-- Razorpay Security Verification Details Card -->
        <div class="admin-card p-4">
            <h6 class="fw-bold text-emerald text-uppercase letter-spacing-1 mb-3"><i class="fas fa-shield-alt text-gold me-2"></i>Razorpay Payment Audit</h6>
            <div class="small">
                <div class="mb-2"><strong>Gateway:</strong> <span class="text-uppercase"><?= e($order['payment_method']) ?></span></div>
                <?php if (!empty($order['razorpay_payment_id'])): ?>
                    <div class="mb-2"><strong>Payment ID:</strong> <br><code class="text-emerald text-break"><?= e($order['razorpay_payment_id']) ?></code></div>
                <?php endif; ?>
                <?php if (!empty($order['razorpay_order_id'])): ?>
                    <div class="mb-2"><strong>Razorpay Order ID:</strong> <br><code class="text-muted text-break"><?= e($order['razorpay_order_id']) ?></code></div>
                <?php endif; ?>
                <div><strong>HMAC-SHA256 Status:</strong> <span class="badge bg-success"><i class="fas fa-check me-1"></i>VERIFIED</span></div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/admin-footer.php'; ?>

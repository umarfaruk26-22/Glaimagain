<?php
/**
 * GLAIMAGAIN - Admin Order Status Update Action Handler
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $orderId = (int)($_POST['order_id'] ?? 0);
    $orderStatus = trim($_POST['order_status'] ?? '');
    $paymentStatus = trim($_POST['payment_status'] ?? '');

    $allowedOrderStatus = ['pending', 'confirmed', 'processing', 'packed', 'shipped', 'delivered', 'cancelled'];
    $allowedPaymentStatus = ['pending', 'paid', 'failed', 'refunded'];

    if ($orderId > 0 && in_array($orderStatus, $allowedOrderStatus) && in_array($paymentStatus, $allowedPaymentStatus)) {
        $upd = $pdo->prepare("
            UPDATE orders 
            SET order_status = ?, payment_status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $upd->execute([$orderStatus, $paymentStatus, $orderId]);
        setFlashMessage('success', "Order status updated to '{$orderStatus}' / '{$paymentStatus}'.");
    } else {
        setFlashMessage('danger', 'Invalid status update parameters.');
    }

    header('Location: ' . BASE_URL . 'admin/orders/view.php?id=' . $orderId);
    exit;
}

header('Location: ' . BASE_URL . 'admin/orders/index.php');
exit;

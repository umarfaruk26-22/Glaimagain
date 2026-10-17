<?php
/**
 * GLAIMAGAIN - Admin Delete Promo Code
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();

$id = (int)($_GET['id'] ?? 0);
$token = $_GET['csrf_token'] ?? ($_POST['csrf_token'] ?? '');

if (!verifyCsrfToken($token)) {
    setFlashMessage('danger', 'Invalid security token.');
    header('Location: ' . BASE_URL . 'admin/coupons');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$coupon = $stmt->fetch();

if (!$coupon) {
    setFlashMessage('danger', 'Promo code not found.');
    header('Location: ' . BASE_URL . 'admin/coupons');
    exit;
}

// Delete coupon
$del = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
$del->execute([$id]);

setFlashMessage('success', "Promo code '{$coupon['code']}' was successfully deleted.");
header('Location: ' . BASE_URL . 'admin/coupons');
exit;

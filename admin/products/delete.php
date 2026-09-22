<?php
/**
 * GLAIMAGAIN - Admin Safe Product Delete / Deactivation
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();
$productId = (int)($_GET['id'] ?? 0);

if (!verifyCsrfToken($_GET['csrf_token'] ?? '')) {
    setFlashMessage('danger', 'Security verification failed.');
    header('Location: ' . BASE_URL . 'admin/products');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if ($product) {
    // Check if product was referenced in historical orders
    $orderItemCount = (int)$pdo->query("SELECT COUNT(*) FROM order_items WHERE product_id = {$productId}")->fetchColumn();

    if ($orderItemCount > 0) {
        // Soft deactivate to preserve patron order history integrity
        $pdo->prepare("UPDATE products SET status = 'inactive' WHERE id = ?")->execute([$productId]);
        setFlashMessage('warning', "Garment '{$product['name']}' has {$orderItemCount} historical orders and was DEACTIVATED instead of deleted to protect order audit integrity.");
    } else {
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$productId]);
        setFlashMessage('success', "Garment '{$product['name']}' has been removed from catalog.");
    }
}

header('Location: ' . BASE_URL . 'admin/products');
exit;

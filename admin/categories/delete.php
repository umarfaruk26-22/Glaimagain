<?php
/**
 * GLAIMAGAIN - Admin Safe Category Delete / Deactivation
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();
$categoryId = (int)($_GET['id'] ?? 0);

if (!verifyCsrfToken($_GET['csrf_token'] ?? '')) {
    setFlashMessage('danger', 'Security verification failed.');
    header('Location: ' . BASE_URL . 'admin/categories/index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ? LIMIT 1");
$stmt->execute([$categoryId]);
$category = $stmt->fetch();

if ($category) {
    // Check if products exist in this category
    $prodCount = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE category_id = {$categoryId}")->fetchColumn();

    if ($prodCount > 0) {
        // Soft delete / deactivate to preserve historical and product integrity
        $pdo->prepare("UPDATE categories SET status = 'inactive' WHERE id = ?")->execute([$categoryId]);
        setFlashMessage('warning', "Category '{$category['name']}' contains {$prodCount} products and was marked as INACTIVE instead of permanently deleted to preserve catalog integrity.");
    } else {
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$categoryId]);
        setFlashMessage('success', "Category '{$category['name']}' has been deleted.");
    }
}

header('Location: ' . BASE_URL . 'admin/categories/index.php');
exit;

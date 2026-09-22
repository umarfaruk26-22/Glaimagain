<?php
/**
 * GLAIMAGAIN - Admin Patron Block / Deactivate Toggle
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();
$userId = (int)($_GET['id'] ?? 0);

if (!verifyCsrfToken($_GET['csrf_token'] ?? '')) {
    setFlashMessage('danger', 'Security verification failed.');
    header('Location: ' . BASE_URL . 'admin/users/index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($user) {
    $newStatus = ($user['status'] === 'active') ? 'blocked' : 'active';
    $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$newStatus, $userId]);

    $msg = ($newStatus === 'blocked') ? "Patron @{$user['username']} has been blocked." : "Patron @{$user['username']} has been unblocked.";
    setFlashMessage('info', $msg);
}

header('Location: ' . BASE_URL . 'admin/users/index.php');
exit;

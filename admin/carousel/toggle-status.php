<?php
/**
 * GLAIMAGAIN - Toggle Carousel Slide Status
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();

$slideId = (int)($_GET['id'] ?? 0);
$token = $_GET['csrf_token'] ?? '';

if (!verifyCsrfToken($token)) {
    setFlashMessage('error', 'Security check failed. Please try again.');
    header('Location: ' . BASE_URL . 'admin/carousel/index.php');
    exit;
}

$pdo = getDb();
$slide = getCarouselSlide($slideId);

if ($slide) {
    $newStatus = ($slide['status'] === 'active') ? 'inactive' : 'active';
    $stmt = $pdo->prepare("UPDATE carousel_slides SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$newStatus, $slideId]);

    setFlashMessage('success', 'Slide #' . $slideId . ' is now ' . $newStatus . '.');
} else {
    setFlashMessage('error', 'Slide not found.');
}

header('Location: ' . BASE_URL . 'admin/carousel/index.php');
exit;

<?php
/**
 * GLAIMAGAIN - Delete Carousel Slide
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
    // Unlink uploaded files if stored in uploads directory
    if (!empty($slide['image_path']) && strpos($slide['image_path'], 'assets/uploads/') === 0) {
        $fullPath = ROOT_PATH . $slide['image_path'];
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }

    if (!empty($slide['mobile_image_path']) && strpos($slide['mobile_image_path'], 'assets/uploads/') === 0) {
        $fullMobPath = ROOT_PATH . $slide['mobile_image_path'];
        if (file_exists($fullMobPath)) {
            @unlink($fullMobPath);
        }
    }

    $stmt = $pdo->prepare("DELETE FROM carousel_slides WHERE id = ?");
    $stmt->execute([$slideId]);

    setFlashMessage('success', 'Carousel slide #' . $slideId . ' deleted successfully.');
} else {
    setFlashMessage('error', 'Slide not found.');
}

header('Location: ' . BASE_URL . 'admin/carousel/index.php');
exit;

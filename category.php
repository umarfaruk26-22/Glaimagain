<?php
/**
 * GLAIMAGAIN - Category Router Redirect
 */
require_once __DIR__ . '/config/config.php';

$slug = trim($_GET['slug'] ?? '');
if (!empty($slug)) {
    header('Location: ' . BASE_URL . 'shop.php?category=' . urlencode($slug));
    exit;
}

header('Location: ' . BASE_URL . 'shop.php');
exit;

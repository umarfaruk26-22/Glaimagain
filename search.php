<?php
/**
 * GLAIMAGAIN - Global Search Results
 */
require_once __DIR__ . '/config/config.php';

$query = trim($_GET['q'] ?? '');
if (!empty($query)) {
    header('Location: ' . BASE_URL . 'shop.php?q=' . urlencode($query));
    exit;
}

header('Location: ' . BASE_URL . 'shop.php');
exit;

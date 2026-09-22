<?php
/**
 * GLAIMAGAIN - API: Get Cart Count
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';

$cartDetails = getCartDetails();

echo json_encode([
    'success' => true,
    'count' => $cartDetails['total_quantity'] ?? 0,
    'subtotal' => formatPrice($cartDetails['subtotal'] ?? 0),
    'grand_total' => formatPrice($cartDetails['grand_total'] ?? 0)
]);

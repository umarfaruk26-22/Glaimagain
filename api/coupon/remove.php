<?php
/**
 * GLAIMAGAIN - API: Remove Promo Code
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

removeCouponFromSession();
$updatedCart = getCartDetails();

echo json_encode([
    'success'         => true,
    'message'         => 'Promo code removed.',
    'grand_total'     => $updatedCart['grand_total'],
    'formatted_total' => formatPrice($updatedCart['grand_total'])
]);

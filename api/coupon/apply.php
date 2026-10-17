<?php
/**
 * GLAIMAGAIN - API: Apply Promo Code
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?: $_POST;
$code = trim($data['code'] ?? '');

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid promo code.']);
    exit;
}

$cart = getCartDetails();
$res = validateCoupon($code, (float)$cart['subtotal']);

if ($res['success']) {
    $_SESSION['applied_coupon'] = $res['coupon'];
    $updatedCart = getCartDetails();
    echo json_encode([
        'success'         => true,
        'message'         => "Promo code '{$res['coupon']['code']}' applied successfully!",
        'coupon_code'     => $res['coupon']['code'],
        'discount_type'   => $res['coupon']['discount_type'],
        'discount_value'  => (float)$res['coupon']['discount_value'],
        'discount_amount' => $updatedCart['coupon_discount'],
        'grand_total'     => $updatedCart['grand_total'],
        'formatted_total' => formatPrice($updatedCart['grand_total'])
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => $res['message']
    ]);
}

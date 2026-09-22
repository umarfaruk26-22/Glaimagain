<?php
/**
 * GLAIMAGAIN - API: Remove Item from Cart
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?: $_POST;

$cartItemId = (int)($data['cart_item_id'] ?? 0);
$pdo = getDb();
$cartId = getOrCreateCartId();

$del = $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND cart_id = ?");
$del->execute([$cartItemId, $cartId]);

$cartDetails = getCartDetails();

echo json_encode([
    'success' => true,
    'message' => 'Item removed from shopping bag.',
    'cart_count' => $cartDetails['total_quantity'],
    'subtotal' => formatPrice($cartDetails['subtotal']),
    'grand_total' => formatPrice($cartDetails['grand_total'])
]);

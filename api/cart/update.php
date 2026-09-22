<?php
/**
 * GLAIMAGAIN - API: Update Cart Item Quantity
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
$quantity = max(1, (int)($data['quantity'] ?? 1));

if ($cartItemId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item identifier.']);
    exit;
}

$pdo = getDb();
$cartId = getOrCreateCartId();

// Verify item belongs to this cart
$stmt = $pdo->prepare("
    SELECT ci.*, p.stock AS base_stock, pv.stock AS variant_stock
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    LEFT JOIN product_variants pv ON ci.variant_id = pv.id
    WHERE ci.id = ? AND ci.cart_id = ?
    LIMIT 1
");
$stmt->execute([$cartItemId, $cartId]);
$item = $stmt->fetch();

if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Item not found in shopping bag.']);
    exit;
}

$availableStock = ($item['variant_id'] !== null && isset($item['variant_stock'])) 
    ? (int)$item['variant_stock'] 
    : (int)$item['base_stock'];

if ($quantity > $availableStock) {
    $quantity = $availableStock;
    $message = "Maximum available quantity for this piece is {$availableStock}.";
} else {
    $message = 'Quantity updated.';
}

$upd = $pdo->prepare("UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ?");
$upd->execute([$quantity, $cartItemId]);

$cartDetails = getCartDetails();

echo json_encode([
    'success' => true,
    'message' => $message,
    'quantity' => $quantity,
    'cart_count' => $cartDetails['total_quantity'],
    'subtotal' => formatPrice($cartDetails['subtotal']),
    'grand_total' => formatPrice($cartDetails['grand_total'])
]);

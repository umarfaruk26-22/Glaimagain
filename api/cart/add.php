<?php
/**
 * GLAIMAGAIN - API: Add Item to Cart
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?: $_POST;

$productId = (int)($data['product_id'] ?? 0);
$size = trim($data['size'] ?? '');
$color = trim($data['color'] ?? '');
$quantity = max(1, (int)($data['quantity'] ?? 1));

if ($productId <= 0 || empty($size) || empty($color)) {
    echo json_encode(['success' => false, 'message' => 'Please select both Size and Color for this garment.']);
    exit;
}

$pdo = getDb();

// 1. Verify Product exists and is active
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active' LIMIT 1");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'The requested garment is unavailable.']);
    exit;
}

// 2. Locate Variant if available
$varStmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? AND size = ? AND color = ? AND status = 'active' LIMIT 1");
$varStmt->execute([$productId, $size, $color]);
$variant = $varStmt->fetch();

$availableStock = $variant ? (int)$variant['stock'] : (int)$product['stock'];
if ($availableStock < $quantity) {
    echo json_encode(['success' => false, 'message' => "Insufficient stock. Only {$availableStock} unit(s) available in {$size} / {$color}."]);
    exit;
}

$cartId = getOrCreateCartId();
$variantId = $variant ? (int)$variant['id'] : null;

// 3. Check if identical item already in cart
$checkCart = $pdo->prepare("
    SELECT id, quantity FROM cart_items
    WHERE cart_id = ? AND product_id = ? AND size = ? AND color = ?
    LIMIT 1
");
$checkCart->execute([$cartId, $productId, $size, $color]);
$existingCartItem = $checkCart->fetch();

if ($existingCartItem) {
    $newQty = (int)$existingCartItem['quantity'] + $quantity;
    if ($newQty > $availableStock) {
        $newQty = $availableStock;
    }
    $upd = $pdo->prepare("UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ?");
    $upd->execute([$newQty, $existingCartItem['id']]);
} else {
    $ins = $pdo->prepare("
        INSERT INTO cart_items (cart_id, product_id, variant_id, size, color, quantity)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $ins->execute([$cartId, $productId, $variantId, $size, $color, $quantity]);
}

$cartDetails = getCartDetails();

echo json_encode([
    'success' => true,
    'message' => 'Garment added to your shopping bag.',
    'cart_count' => $cartDetails['total_quantity'],
    'cart_subtotal' => formatPrice($cartDetails['subtotal'])
]);

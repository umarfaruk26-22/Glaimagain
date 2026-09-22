<?php
/**
 * GLAIMAGAIN - API: Save Delivery Address
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!isUserLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please sign in to save addresses.']);
    exit;
}

$currentUser = getCurrentUser();
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?: $_POST;

$fullName = trim($data['full_name'] ?? '');
$mobile = trim($data['mobile'] ?? '');
$addr1 = trim($data['address_line_1'] ?? '');
$addr2 = trim($data['address_line_2'] ?? '');
$area = trim($data['area'] ?? '');
$city = trim($data['city'] ?? '');
$state = trim($data['state'] ?? '');
$pincode = trim($data['pincode'] ?? '');
$addressType = in_array($data['address_type'] ?? '', ['home', 'office', 'other']) ? $data['address_type'] : 'home';
$isDefault = !empty($data['is_default']) ? 1 : 0;

if (empty($fullName) || empty($mobile) || empty($addr1) || empty($city) || empty($state) || empty($pincode)) {
    echo json_encode(['success' => false, 'message' => 'Please complete all required address fields.']);
    exit;
}

$pdo = getDb();

// Check if this is the first address for the user; if so, make it default
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM user_addresses WHERE user_id = ?");
$countStmt->execute([$currentUser['id']]);
$hasAddresses = (int)$countStmt->fetchColumn();

if ($hasAddresses === 0) {
    $isDefault = 1;
}

if ($isDefault) {
    $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$currentUser['id']]);
}

$stmt = $pdo->prepare("
    INSERT INTO user_addresses (user_id, full_name, mobile, address_line_1, address_line_2, area, city, state, pincode, address_type, is_default)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    $currentUser['id'],
    $fullName,
    $mobile,
    $addr1,
    $addr2,
    $area,
    $city,
    $state,
    $pincode,
    $addressType,
    $isDefault
]);

$newAddressId = (int)$pdo->lastInsertId();

// Fetch created record
$fetchStmt = $pdo->prepare("SELECT * FROM user_addresses WHERE id = ? LIMIT 1");
$fetchStmt->execute([$newAddressId]);
$newAddress = $fetchStmt->fetch();

echo json_encode([
    'success' => true,
    'message' => 'Address saved successfully.',
    'address' => $newAddress
]);

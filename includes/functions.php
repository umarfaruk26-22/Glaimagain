<?php
/**
 * GLAIMAGAIN - Global Helper Functions
 */

/**
 * Escape HTML special characters for XSS prevention
 */
function e(?string $string): string {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Format numerical amount to Indian Rupee format
 */
function formatPrice($amount): string {
    $sym = defined('SITE_CURRENCY') ? SITE_CURRENCY : '₹';
    return $sym . number_format((float)$amount, 2);
}

/**
 * Calculate discount percentage automatically
 */
function calculateDiscountPercent(float $originalPrice, float $sellingPrice): int {
    if ($originalPrice <= 0 || $sellingPrice >= $originalPrice) {
        return 0;
    }
    return (int)round((($originalPrice - $sellingPrice) / $originalPrice) * 100);
}

/**
 * Convert string to URL-safe slug
 */
function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

/**
 * Set flash alert message in session
 */
function setFlashMessage(string $type, string $message): void {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Retrieve and clear flash alert messages
 */
function getFlashMessages(): array {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Render flash messages as luxury alert banners
 */
function renderFlashMessages(): string {
    $messages = getFlashMessages();
    if (empty($messages)) {
        return '';
    }

    $html = '<div class="container my-3">';
    foreach ($messages as $msg) {
        $type = $msg['type'] === 'error' ? 'danger' : $msg['type'];
        $icon = $type === 'success' ? 'fa-check-circle' : ($type === 'danger' ? 'fa-exclamation-circle' : 'fa-info-circle');
        $html .= sprintf(
            '<div class="alert alert-%s alert-dismissible fade show luxury-alert d-flex align-items-center" role="alert">
                <i class="fas %s me-2 text-gold"></i>
                <div class="flex-grow-1">%s</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>',
            e($type),
            $icon,
            e($msg['message'])
        );
    }
    $html .= '</div>';
    return $html;
}

/**
 * Retrieve a setting from the settings table
 */
function getSetting(string $key, ?string $default = null): ?string {
    static $settingsCache = null;

    if ($settingsCache === null) {
        $settingsCache = [];
        try {
            $pdo = getDb();
            $stmt = $pdo->query("SELECT `setting_key`, `setting_value` FROM `settings`");
            while ($row = $stmt->fetch()) {
                $settingsCache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            error_log("Settings error: " . $e->getMessage());
        }
    }

    return $settingsCache[$key] ?? $default;
}

/**
 * Retrieve all active categories
 */
function getActiveCategories(): array {
    try {
        $pdo = getDb();
        $stmt = $pdo->query("SELECT * FROM `categories` WHERE `status` = 'active' ORDER BY `sort_order` ASC, `name` ASC");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error fetching categories: " . $e->getMessage());
        return [];
    }
}

/**
 * Resolve product image URL with fallback to placeholder
 */
function getProductImageUrl(?string $imagePath): string {
    if (!empty($imagePath)) {
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            return $imagePath;
        }
        $fullPath = ROOT_PATH . ltrim($imagePath, '/\\');
        if (file_exists($fullPath)) {
            return BASE_URL . ltrim(str_replace('\\', '/', $imagePath), '/');
        }
    }
    return BASE_URL . 'assets/images/product-tshirt-emerald-1.jpg';
}

/**
 * Resolve category image URL
 */
function getCategoryImageUrl(?string $imagePath): string {
    if (!empty($imagePath)) {
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            return $imagePath;
        }
        $fullPath = ROOT_PATH . ltrim($imagePath, '/\\');
        if (file_exists($fullPath)) {
            return BASE_URL . ltrim(str_replace('\\', '/', $imagePath), '/');
        }
    }
    return BASE_URL . 'assets/images/category-tshirts.jpg';
}

/**
 * Generate unique Order Number (e.g. GLA-20260920-ABCD)
 */
function generateOrderNumber(): string {
    return 'GLA-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
}

/**
 * Get or create Cart ID for current visitor (User or Guest Session)
 */
function getOrCreateCartId(): int {
    $pdo = getDb();
    $userId = $_SESSION['user_id'] ?? null;
    $sessionId = session_id();

    if ($userId) {
        // Find cart by user_id
        $stmt = $pdo->prepare("SELECT `id` FROM `carts` WHERE `user_id` = ? LIMIT 1");
        $stmt->execute([$userId]);
        $cart = $stmt->fetch();

        if ($cart) {
            return (int)$cart['id'];
        }

        // Check if there is an unattached session cart to claim
        $stmt = $pdo->prepare("SELECT `id` FROM `carts` WHERE `session_id` = ? AND `user_id` IS NULL LIMIT 1");
        $stmt->execute([$sessionId]);
        $sessionCart = $stmt->fetch();

        if ($sessionCart) {
            $stmt = $pdo->prepare("UPDATE `carts` SET `user_id` = ? WHERE `id` = ?");
            $stmt->execute([$userId, $sessionCart['id']]);
            return (int)$sessionCart['id'];
        }

        // Create new user cart
        $stmt = $pdo->prepare("INSERT INTO `carts` (`user_id`, `session_id`) VALUES (?, ?)");
        $stmt->execute([$userId, $sessionId]);
        return (int)$pdo->lastInsertId();
    } else {
        // Guest cart by session_id
        $stmt = $pdo->prepare("SELECT `id` FROM `carts` WHERE `session_id` = ? AND `user_id` IS NULL LIMIT 1");
        $stmt->execute([$sessionId]);
        $cart = $stmt->fetch();

        if ($cart) {
            return (int)$cart['id'];
        }

        $stmt = $pdo->prepare("INSERT INTO `carts` (`user_id`, `session_id`) VALUES (NULL, ?)");
        $stmt->execute([$sessionId]);
        return (int)$pdo->lastInsertId();
    }
}

/**
 * Calculate accurate cart summary strictly from DB data
 */
function getCartDetails(): array {
    $pdo = getDb();
    $cartId = getOrCreateCartId();

    $stmt = $pdo->prepare("
        SELECT 
            ci.id AS cart_item_id,
            ci.cart_id,
            ci.product_id,
            ci.variant_id,
            ci.size,
            ci.color,
            ci.quantity,
            p.name AS product_name,
            p.slug AS product_slug,
            p.sku AS product_sku,
            p.original_price,
            p.selling_price,
            p.stock AS base_stock,
            p.status AS product_status,
            pv.sku AS variant_sku,
            pv.stock AS variant_stock,
            pv.additional_price,
            (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order ASC LIMIT 1) AS image_path
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        LEFT JOIN product_variants pv ON ci.variant_id = pv.id
        WHERE ci.cart_id = ?
        ORDER BY ci.id DESC
    ");
    $stmt->execute([$cartId]);
    $rawItems = $stmt->fetchAll();

    $items = [];
    $subtotal = 0.00;
    $totalOriginal = 0.00;
    $totalQuantity = 0;
    $hasOutOfStock = false;

    foreach ($rawItems as $item) {
        $unitPrice = (float)$item['selling_price'] + (float)($item['additional_price'] ?? 0);
        $originalUnitPrice = (float)$item['original_price'] + (float)($item['additional_price'] ?? 0);
        $quantity = (int)$item['quantity'];

        // Determine stock
        $availableStock = ($item['variant_id'] !== null && isset($item['variant_stock'])) 
            ? (int)$item['variant_stock'] 
            : (int)$item['base_stock'];

        $isAvailable = ($item['product_status'] === 'active' && $availableStock >= $quantity);
        if (!$isAvailable) {
            $hasOutOfStock = true;
        }

        $itemSubtotal = $unitPrice * $quantity;
        $subtotal += $itemSubtotal;
        $totalOriginal += ($originalUnitPrice * $quantity);
        $totalQuantity += $quantity;

        $items[] = [
            'cart_item_id'   => (int)$item['cart_item_id'],
            'product_id'     => (int)$item['product_id'],
            'variant_id'     => $item['variant_id'] ? (int)$item['variant_id'] : null,
            'name'           => $item['product_name'],
            'slug'           => $item['product_slug'],
            'sku'            => $item['variant_sku'] ?: $item['product_sku'],
            'size'           => $item['size'],
            'color'          => $item['color'],
            'quantity'       => $quantity,
            'unit_price'     => $unitPrice,
            'original_price' => $originalUnitPrice,
            'subtotal'       => $itemSubtotal,
            'image'          => getProductImageUrl($item['image_path']),
            'available_stock'=> $availableStock,
            'is_available'   => $isAvailable
        ];
    }

    $discountAmount = max(0.00, $totalOriginal - $subtotal);
    $freeShippingThreshold = (float)getSetting('free_shipping_threshold', (string)FREE_SHIPPING_THRESHOLD);
    $standardShippingFee = (float)getSetting('shipping_fee', (string)DEFAULT_SHIPPING_FEE);

    $shippingFee = ($subtotal >= $freeShippingThreshold || $subtotal == 0) ? 0.00 : $standardShippingFee;
    $grandTotal = $subtotal + $shippingFee;

    return [
        'cart_id'          => $cartId,
        'items'            => $items,
        'total_quantity'   => $totalQuantity,
        'original_total'   => $totalOriginal,
        'subtotal'         => $subtotal,
        'discount_amount'  => $discountAmount,
        'shipping_fee'     => $shippingFee,
        'grand_total'      => $grandTotal,
        'has_out_of_stock' => $hasOutOfStock
    ];
}

/**
 * Handle secure image upload
 *
 * @param array $file $_FILES['input_name']
 * @param string $subDir 'products', 'categories', or 'banners'
 * @return array ['success' => bool, 'path' => string, 'error' => string]
 */
function handleImageUpload(array $file, string $subDir = 'products'): array {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Invalid file parameter.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server maximum upload size.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form maximum upload size.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.'
        ];
        return ['success' => false, 'error' => $errors[$file['error']] ?? 'Unknown upload error.'];
    }

    // Limit maximum file size (5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File size exceeds maximum 5MB limit.'];
    }

    // Allowed MIME and extensions
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif'
    ];

    if (!array_key_exists($mimeType, $allowedMimes)) {
        return ['success' => false, 'error' => 'Invalid image format. Only JPG, PNG, WEBP, and GIF are allowed.'];
    }

    $ext = $allowedMimes[$mimeType];
    $targetDirectory = ROOT_PATH . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $subDir . DIRECTORY_SEPARATOR;

    if (!is_dir($targetDirectory)) {
        mkdir($targetDirectory, 0755, true);
    }

    // Generate cryptographically safe random filename
    $filename = bin2hex(random_bytes(16)) . '_' . time() . '.' . $ext;
    $targetPath = $targetDirectory . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'error' => 'Failed to save uploaded image.'];
    }

    $relativeDbPath = 'assets/uploads/' . $subDir . '/' . $filename;
    return ['success' => true, 'path' => $relativeDbPath, 'filename' => $filename];
}

/**
 * Dispatch customer inquiry to the Python FastAPI microservice for Gmail delivery
 */
function sendFastApiContactEmail(string $name, string $email, string $mobile, string $subject, string $message): bool {
    try {
        $payload = json_encode([
            'name'    => $name,
            'email'   => $email,
            'mobile'  => $mobile,
            'subject' => $subject,
            'message' => $message
        ]);

        $ch = curl_init('http://127.0.0.1:8001/api/contact/send-inquiry');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 3,
            CURLOPT_CONNECTTIMEOUT => 2
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode === 200);
    } catch (\Throwable $t) {
        error_log('FastAPI Mailer Error: ' . $t->getMessage());
        return false;
    }
}

/**
 * Dispatch newsletter subscription alert to FastAPI microservice
 */
function sendFastApiNewsletter(string $email): bool {
    try {
        $payload = json_encode(['email' => $email]);

        $ch = curl_init('http://127.0.0.1:8001/api/newsletter/subscribe');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 3,
            CURLOPT_CONNECTTIMEOUT => 2
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode === 200);
    } catch (\Throwable $t) {
        error_log('FastAPI Newsletter Error: ' . $t->getMessage());
        return false;
    }
}


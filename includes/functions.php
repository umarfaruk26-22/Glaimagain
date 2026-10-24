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

    // Calculate promo code / coupon discount if applied
    $appliedCoupon = $_SESSION['applied_coupon'] ?? null;
    $couponDiscount = 0.00;
    $couponError = null;

    if ($appliedCoupon && $subtotal > 0) {
        $couponCheck = validateCoupon($appliedCoupon['code'], $subtotal);
        if ($couponCheck['success']) {
            $couponDiscount = $couponCheck['discount'];
            $_SESSION['applied_coupon'] = $couponCheck['coupon']; // refresh coupon details
        } else {
            $couponError = $couponCheck['message'];
        }
    }

    $discountAmount = max(0.00, $totalOriginal - $subtotal);
    $freeShippingThreshold = (float)getSetting('free_shipping_threshold', (string)FREE_SHIPPING_THRESHOLD);
    $standardShippingFee = (float)getSetting('shipping_fee', (string)DEFAULT_SHIPPING_FEE);

    $finalSubtotal = max(0.00, $subtotal - $couponDiscount);
    $shippingFee = ($finalSubtotal >= $freeShippingThreshold || $subtotal == 0) ? 0.00 : $standardShippingFee;
    $grandTotal = $finalSubtotal + $shippingFee;

    return [
        'cart_id'          => $cartId,
        'items'            => $items,
        'total_quantity'   => $totalQuantity,
        'original_total'   => $totalOriginal,
        'subtotal'         => $subtotal,
        'discount_amount'  => $discountAmount,
        'coupon_code'      => $appliedCoupon['code'] ?? null,
        'coupon_discount'  => $couponDiscount,
        'coupon_error'     => $couponError,
        'coupon_details'   => $appliedCoupon,
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

/**
 * One-time automated store cleanup migration
 * Resets products, reviews, and trims categories to exactly 1 on first deployment
 */
function runStoreCleanupMigration(): void {
    static $executed = false;
    if ($executed) return;
    $executed = true;

    try {
        $pdo = getDb();
        $stmt = $pdo->prepare("SELECT `setting_value` FROM `settings` WHERE `setting_key` = 'migration_clean_store_v1' LIMIT 1");
        $stmt->execute();
        $done = $stmt->fetchColumn();

        if ($done !== 'completed') {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            $pdo->exec("TRUNCATE TABLE `cart_items`;");
            $pdo->exec("TRUNCATE TABLE `reviews`;");
            $pdo->exec("UPDATE `order_items` SET `product_id` = NULL, `variant_id` = NULL;");
            $pdo->exec("TRUNCATE TABLE `product_images`;");
            $pdo->exec("TRUNCATE TABLE `product_variants`;");
            $pdo->exec("TRUNCATE TABLE `products`;");
            
            $firstCat = $pdo->query("SELECT id FROM categories ORDER BY id ASC LIMIT 1")->fetch();
            if ($firstCat) {
                $firstId = (int)$firstCat['id'];
                $pdo->exec("DELETE FROM `categories` WHERE `id` != {$firstId};");
            }
            
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

            $saveStmt = $pdo->prepare("INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) 
                VALUES ('migration_clean_store_v1', 'completed', 'system') 
                ON DUPLICATE KEY UPDATE `setting_value` = 'completed'");
            $saveStmt->execute();
        }
    } catch (\Throwable $e) {
        error_log("Store cleanup migration error: " . $e->getMessage());
    }
}

// Trigger migration check automatically on startup
runStoreCleanupMigration();

/**
 * Validate a promo code against current cart subtotal
 */
function validateCoupon(string $code, float $cartSubtotal = 0.0): array {
    $code = strtoupper(trim($code));
    if (empty($code)) {
        return ['success' => false, 'message' => 'Please enter a promo code.'];
    }

    try {
        $pdo = getDb();
        $stmt = $pdo->prepare("SELECT * FROM `coupons` WHERE UPPER(`code`) = ? LIMIT 1");
        $stmt->execute([$code]);
        $coupon = $stmt->fetch();

        if (!$coupon) {
            return ['success' => false, 'message' => 'Invalid promo code.'];
        }

        if ($coupon['status'] !== 'active') {
            return ['success' => false, 'message' => 'This promo code is currently inactive.'];
        }

        // Validity dates check
        $now = date('Y-m-d H:i:s');
        if (!empty($coupon['valid_from']) && $now < $coupon['valid_from']) {
            return ['success' => false, 'message' => 'This promo code is not active yet.'];
        }
        if (!empty($coupon['valid_until']) && $now > $coupon['valid_until']) {
            return ['success' => false, 'message' => 'This promo code has expired.'];
        }

        // Usage limit check
        if ((int)$coupon['usage_limit'] > 0 && (int)$coupon['used_count'] >= (int)$coupon['usage_limit']) {
            return ['success' => false, 'message' => 'This promo code usage limit has been reached.'];
        }

        // Minimum order check
        $minOrder = (float)$coupon['min_order_amount'];
        if ($cartSubtotal > 0 && $cartSubtotal < $minOrder) {
            return [
                'success' => false,
                'message' => 'Minimum bag value of ' . formatPrice($minOrder) . ' required for this promo code.',
                'coupon'  => $coupon
            ];
        }

        // Calculate discount
        $discountValue = (float)$coupon['discount_value'];
        $discountAmount = 0.00;

        if ($coupon['discount_type'] === 'percentage') {
            $discountAmount = ($cartSubtotal * $discountValue) / 100.0;
            if (!empty($coupon['max_discount_amount']) && (float)$coupon['max_discount_amount'] > 0) {
                $discountAmount = min($discountAmount, (float)$coupon['max_discount_amount']);
            }
        } else {
            // Fixed amount discount
            $discountAmount = min($cartSubtotal > 0 ? $cartSubtotal : $discountValue, $discountValue);
        }

        return [
            'success'  => true,
            'message'  => 'Promo code applied successfully!',
            'coupon'   => $coupon,
            'discount' => round($discountAmount, 2)
        ];
    } catch (\Throwable $e) {
        error_log("Coupon validation error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error verifying promo code.'];
    }
}

/**
 * Apply promo code to user session
 */
function applyCouponToSession(string $code): array {
    $cart = getCartDetails();
    $res = validateCoupon($code, (float)$cart['subtotal']);
    if ($res['success']) {
        $_SESSION['applied_coupon'] = $res['coupon'];
    }
    return $res;
}

/**
 * Remove promo code from user session
 */
function removeCouponFromSession(): void {
    unset($_SESSION['applied_coupon']);
}

/**
 * Automated migration for coupons table and exclusive offer homepage banner
 */
function runCouponAndBannerMigration(): void {
    static $executed = false;
    if ($executed) return;
    $executed = true;

    try {
        $pdo = getDb();

        // 1. Create coupons table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `coupons` (
              `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              `code` VARCHAR(50) NOT NULL UNIQUE,
              `description` VARCHAR(255) DEFAULT NULL,
              `discount_type` ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
              `discount_value` DECIMAL(10,2) NOT NULL,
              `min_order_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
              `max_discount_amount` DECIMAL(10,2) DEFAULT NULL,
              `valid_from` DATETIME DEFAULT CURRENT_TIMESTAMP,
              `valid_until` DATETIME DEFAULT NULL,
              `usage_limit` INT UNSIGNED DEFAULT 0,
              `used_count` INT UNSIGNED NOT NULL DEFAULT 0,
              `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              INDEX `idx_coupon_code` (`code`),
              INDEX `idx_coupon_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 2. Add columns to homepage_sections if not exist
        $cols = $pdo->query("SHOW COLUMNS FROM `homepage_sections` LIKE 'badge_text'")->fetch();
        if (!$cols) {
            $pdo->exec("ALTER TABLE `homepage_sections` ADD COLUMN `badge_text` VARCHAR(150) DEFAULT 'TIME-LIMITED PROMOTION' AFTER `title`");
        }
        $colsPromo = $pdo->query("SHOW COLUMNS FROM `homepage_sections` LIKE 'promo_code'")->fetch();
        if (!$colsPromo) {
            $pdo->exec("ALTER TABLE `homepage_sections` ADD COLUMN `promo_code` VARCHAR(50) DEFAULT 'GLAIM40' AFTER `button_link`");
        }
        $colsExp = $pdo->query("SHOW COLUMNS FROM `homepage_sections` LIKE 'expiry_date'")->fetch();
        if (!$colsExp) {
            $pdo->exec("ALTER TABLE `homepage_sections` ADD COLUMN `expiry_date` DATETIME DEFAULT NULL AFTER `promo_code`");
        }

        // Add coupon_code to orders table if not exist
        $colsOrder = $pdo->query("SHOW COLUMNS FROM `orders` LIKE 'coupon_code'")->fetch();
        if (!$colsOrder) {
            $pdo->exec("ALTER TABLE `orders` ADD COLUMN `coupon_code` VARCHAR(50) DEFAULT NULL AFTER `discount_amount`");
        }

        // 3. Ensure exclusive_offer row exists
        $stmt = $pdo->prepare("SELECT id FROM `homepage_sections` WHERE `section_key` = 'exclusive_offer' LIMIT 1");
        $stmt->execute();
        if (!$stmt->fetch()) {
            $pdo->exec("
                INSERT INTO `homepage_sections` 
                (`section_key`, `title`, `badge_text`, `subtitle`, `content`, `button_text`, `button_link`, `promo_code`, `expiry_date`, `image_path`, `is_active`, `sort_order`)
                VALUES (
                    'exclusive_offer',
                    'Exclusive Offer',
                    'TIME-LIMITED PROMOTION',
                    'Enjoy up to 40% OFF our newest luxury drops and bespoke tailoring pieces.',
                    'Discover the key to a stylish upgrade with our exclusive deal: Enjoy up to 40% OFF our newest luxury drops and bespoke tailoring pieces.',
                    'CLAIM EXCLUSIVE OFFER',
                    'shop.php',
                    'GLAIM40',
                    DATE_ADD(NOW(), INTERVAL 7 DAY),
                    'assets/images/exclusive-model.jpg',
                    1,
                    4
                )
            ");
        }

        // 4. Ensure default GLAIM40 coupon exists
        $stmtC = $pdo->prepare("SELECT id FROM `coupons` WHERE `code` = 'GLAIM40' LIMIT 1");
        $stmtC->execute();
        if (!$stmtC->fetch()) {
            $pdo->exec("
                INSERT INTO `coupons` (`code`, `description`, `discount_type`, `discount_value`, `min_order_amount`, `valid_from`, `valid_until`, `usage_limit`, `status`)
                VALUES ('GLAIM40', 'Exclusive Offer 40% OFF Luxury Garments', 'percentage', 40.00, 0.00, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1000, 'active')
            ");
        }
    } catch (\Throwable $e) {
        error_log("Coupon/Banner migration error: " . $e->getMessage());
    }
}

// Trigger Coupon and Banner Migration automatically on startup
runCouponAndBannerMigration();

/**
 * Automated migration for hero carousel slides table and initial default slides
 */
function runCarouselMigration(): void {
    static $executed = false;
    if ($executed) return;
    $executed = true;

    try {
        $pdo = getDb();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `carousel_slides` (
              `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              `title` VARCHAR(255) DEFAULT NULL,
              `subtitle` VARCHAR(255) DEFAULT NULL,
              `description` TEXT DEFAULT NULL,
              `button_text` VARCHAR(100) DEFAULT 'START SHOPPING',
              `button_link` VARCHAR(255) DEFAULT 'shop.php',
              `image_path` VARCHAR(255) NOT NULL,
              `mobile_image_path` VARCHAR(255) DEFAULT NULL,
              `text_position` ENUM('left', 'center', 'right') NOT NULL DEFAULT 'left',
              `text_color` ENUM('light', 'dark') NOT NULL DEFAULT 'light',
              `sort_order` INT NOT NULL DEFAULT 0,
              `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              INDEX `idx_carousel_status_sort` (`status`, `sort_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Seed initial slides if table is empty
        $count = (int)$pdo->query("SELECT COUNT(*) FROM `carousel_slides`")->fetchColumn();
        if ($count === 0) {
            $stmt = $pdo->prepare("
                INSERT INTO `carousel_slides` 
                (`title`, `subtitle`, `description`, `button_text`, `button_link`, `image_path`, `mobile_image_path`, `text_position`, `text_color`, `sort_order`, `status`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            // Slide 1: The user's exact Autograph collection requested
            $stmt->execute([
                'Explore and Define Your Unique Style!',
                'AUTOGRAPH COLLECTION 2026',
                'Browse our thoughtfully curated assortment of fashionable attire and bespoke silhouettes, designed to match your individual presence.',
                'START SHOPPING',
                'shop.php',
                'assets/images/carousel-slide-1.jpg',
                'assets/images/carousel-slide-1-mobile.jpg',
                'left',
                'light',
                1,
                'active'
            ]);

            // Slide 2: Haute Couture emerald & gold coats
            $stmt->execute([
                'Bespoke Tailoring & Haute Outerwear',
                'TIMELESS LUXURY APPAREL',
                'Handcrafted silhouettes in deep emerald velvet and intricate bullion embroidery. Designed for discerning tastemakers.',
                'EXPLORE COLLECTION',
                'shop.php',
                'assets/images/carousel-slide-2.jpg',
                null,
                'left',
                'light',
                2,
                'active'
            ]);

            // Slide 3: Limited Edition Capsule
            $stmt->execute([
                'The Emerald & Gold Capsule',
                'LIMITED EDITION 2026',
                'Experience 280 GSM heavyweight combed cottons, bespoke Italian cuts, and pure 24K gold accents.',
                'SHOP THE CAPSULE',
                'shop.php',
                'assets/images/promo-capsule.jpg',
                null,
                'left',
                'light',
                3,
                'active'
            ]);

            // Slide 4: Autumn/Winter Haute Drop
            $stmt->execute([
                'Autumn / Winter 2026 Haute Drop',
                'NEW ARRIVALS 2026',
                'Discover statement outerwear, 120s Egyptian cotton shirts, and Japanese selvedge denim cut to sovereign perfection.',
                'SHOP NEW ARRIVALS',
                'shop.php?sort=newest',
                'assets/images/hero-banner.jpg',
                null,
                'left',
                'light',
                4,
                'active'
            ]);
        }
    } catch (\Throwable $e) {
        error_log('Carousel migration error: ' . $e->getMessage());
    }
}

// Trigger Carousel Migration automatically on startup
runCarouselMigration();

/**
 * Automatically ensure sample categories, products, images, variants, and reviews are populated
 */
function ensureSampleStoreData(): void {
    static $executed = false;
    if ($executed) return;
    $executed = true;

    try {
        $pdo = getDb();
        $prodCount = (int)$pdo->query("SELECT COUNT(*) FROM `products`")->fetchColumn();
        if ($prodCount > 0) {
            return;
        }

        // 1. Seed / ensure categories
        $categories = [
            ['name' => 'Men', 'slug' => 'men', 'description' => 'Sophisticated, tailored menswear designed for modern elegance.', 'image' => 'assets/images/category-men.jpg', 'is_featured' => 1, 'sort_order' => 1],
            ['name' => 'Tailored Jackets', 'slug' => 'tailored-jackets', 'description' => 'Structured blazers, emerald overcoats, and statement outerwear.', 'image' => 'assets/images/category-jackets.jpg', 'is_featured' => 1, 'sort_order' => 2],
            ['name' => 'Luxury T-Shirts', 'slug' => 'luxury-t-shirts', 'description' => 'Heavyweight 280 GSM combed cotton oversized and signature t-shirts.', 'image' => 'assets/images/category-tshirts.jpg', 'is_featured' => 1, 'sort_order' => 3],
            ['name' => 'Premium Shirts', 'slug' => 'premium-shirts', 'description' => 'Egyptian cotton bespoke dress shirts and luxury linen resort shirts.', 'image' => 'assets/images/category-shirts.jpg', 'is_featured' => 1, 'sort_order' => 4],
            ['name' => 'Designer Hoodies', 'slug' => 'designer-hoodies', 'description' => 'Ultra-soft fleece oversized luxury streetwear hoodies with gold embroidery.', 'image' => 'assets/images/category-hoodies.jpg', 'is_featured' => 1, 'sort_order' => 5],
            ['name' => 'Jeans & Trousers', 'slug' => 'jeans-trousers', 'description' => 'Selvedge denim and pleated trousers with impeccable drape.', 'image' => 'assets/images/category-jeans.jpg', 'is_featured' => 1, 'sort_order' => 6],
            ['name' => 'Luxury Accessories', 'slug' => 'accessories', 'description' => 'Artisan leather goods, signature scarves, and gold-finish cufflinks.', 'image' => 'assets/images/category-accessories.jpg', 'is_featured' => 1, 'sort_order' => 7]
        ];

        $catStmt = $pdo->prepare("
            INSERT INTO `categories` (`name`, `slug`, `description`, `image`, `status`, `is_featured`, `sort_order`)
            VALUES (?, ?, ?, ?, 'active', ?, ?)
            ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`), `image` = VALUES(`image`), `status` = 'active', `is_featured` = VALUES(`is_featured`), `sort_order` = VALUES(`sort_order`)
        ");
        foreach ($categories as $cat) {
            $catStmt->execute([$cat['name'], $cat['slug'], $cat['description'], $cat['image'], $cat['is_featured'], $cat['sort_order']]);
        }

        // Build slug -> id map
        $catMap = [];
        $allCats = $pdo->query("SELECT id, slug FROM categories")->fetchAll();
        foreach ($allCats as $c) {
            $catMap[$c['slug']] = (int)$c['id'];
        }

        // 2. Seed 6 Flagship luxury products (1 per category)
        $products = [
            [
                'category_slug' => 'tailored-jackets',
                'name' => 'Monogram Gold Crest Velvet Tuxedo Blazer',
                'slug' => 'monogram-gold-crest-velvet-tuxedo-blazer',
                'sku' => 'GLA-JKT-001',
                'short_description' => 'Deep emerald silk-blend velvet blazer accented with handcrafted gold bullion embroidery.',
                'detailed_description' => '<p>Command any evening in this statement tuxedo blazer. Cut from plush silk-cotton velvet in our signature deep emerald hue, featuring a shawl lapel lined with satin and antique gold crest hand-embroidery on the pocket.</p><ul><li>Silk-Cotton Micro-Velvet exterior with duchess satin lapels</li><li>Cupro luxury gold monogram jacquard lining</li><li>Tailored slim silhouette with double back vents</li><li>Dry clean only</li></ul>',
                'original_price' => 14999.00,
                'selling_price' => 8999.00,
                'stock' => 20,
                'sizes' => '38,40,42,44',
                'colors' => 'Emerald Green,Obsidian Black',
                'is_featured' => 1,
                'is_new_arrival' => 1,
                'is_best_seller' => 1,
                'images' => [
                    'assets/images/product-jacket-velvet-1.jpg',
                    'assets/images/product-jacket-velvet-2.jpg'
                ]
            ],
            [
                'category_slug' => 'luxury-t-shirts',
                'name' => 'Signature Emerald Oversized Heavyweight Tee',
                'slug' => 'signature-emerald-oversized-heavyweight-tee',
                'sku' => 'GLA-TSH-002',
                'short_description' => 'Crafted from 280 GSM luxury combed cotton with signature gold silicone branding.',
                'detailed_description' => '<p>Redefine casual luxury with the GLAIMAGAIN Signature Emerald Oversized Tee. Cut from custom-milled 280 GSM organic combed cotton, this heavyweight t-shirt features a relaxed boxy drape, reinforced ribbed collar, and high-density gold micro-embroidery across the chest.</p><ul><li>100% Combed Compact Organic Cotton (280 GSM)</li><li>Pre-shrunk and silicone-washed for extreme softness</li><li>Embossed metallic gold logo accent on nape and chest</li><li>Handcrafted in limited batches</li></ul>',
                'original_price' => 2999.00,
                'selling_price' => 1799.00,
                'stock' => 50,
                'sizes' => 'S,M,L,XL,XXL',
                'colors' => 'Emerald Green,Midnight Black,Ivory White',
                'is_featured' => 1,
                'is_new_arrival' => 1,
                'is_best_seller' => 1,
                'images' => [
                    'assets/images/product-tshirt-emerald-1.jpg',
                    'assets/images/product-tshirt-emerald-2.jpg',
                    'assets/images/product-tshirt-emerald-3.jpg'
                ]
            ],
            [
                'category_slug' => 'premium-shirts',
                'name' => 'Royal Oxford French Cuff Dress Shirt',
                'slug' => 'royal-oxford-french-cuff-dress-shirt',
                'sku' => 'GLA-SHT-003',
                'short_description' => 'Two-ply Egyptian Giza cotton tailored dress shirt with mother-of-pearl buttons.',
                'detailed_description' => '<p>The quintessential luxury dress shirt. Woven from superfine two-ply 120s Egyptian Giza cotton, this shirt offers an exquisite lustrous finish and structured cutaway collar. Completed with genuine Australian mother-of-pearl buttons and convertible French cuffs.</p><ul><li>100% Egyptian Giza Cotton (120s Two-Ply)</li><li>Hand-sewn mother-of-pearl buttons</li><li>Stiffened collar stays included</li><li>Wrinkle-resistant luxury weave</li></ul>',
                'original_price' => 4999.00,
                'selling_price' => 3299.00,
                'stock' => 35,
                'sizes' => '38,40,42,44,46',
                'colors' => 'Crisp White,Midnight Navy,Champagne Gold',
                'is_featured' => 1,
                'is_new_arrival' => 1,
                'is_best_seller' => 1,
                'images' => [
                    'assets/images/product-shirt-white-1.jpg',
                    'assets/images/product-shirt-white-2.jpg'
                ]
            ],
            [
                'category_slug' => 'designer-hoodies',
                'name' => 'Heritage 450 GSM French Terry Gold Crest Hoodie',
                'slug' => 'heritage-450-gsm-french-terry-gold-crest-hoodie',
                'sku' => 'GLA-HDY-004',
                'short_description' => 'Super-heavy 450 GSM unbrushed loopback cotton hoodie with engraved gold aglets.',
                'detailed_description' => '<p>Engineered for unmatched comfort and architectural drape. Built with bespoke 450 GSM Japanese loopback French terry, custom heavyweight dual-layer hood, and 24K gold-plated drawstring aglets.</p><ul><li>450 GSM 100% Loopback French Terry Cotton</li><li>24K antique gold electroplated drawstring aglets</li><li>Hidden side seam kangaroo pockets</li><li>Preshrunk double-ribbed side gussets</li></ul>',
                'original_price' => 5499.00,
                'selling_price' => 3499.00,
                'stock' => 40,
                'sizes' => 'S,M,L,XL,XXL',
                'colors' => 'Deep Emerald,Midnight Black,Desert Sand',
                'is_featured' => 1,
                'is_new_arrival' => 0,
                'is_best_seller' => 1,
                'images' => [
                    'assets/images/product-hoodie-emerald-1.jpg',
                    'assets/images/product-hoodie-emerald-2.jpg'
                ]
            ],
            [
                'category_slug' => 'jeans-trousers',
                'name' => 'Selvedge Raw Indigo Tapered Denim',
                'slug' => 'selvedge-raw-indigo-tapered-denim',
                'sku' => 'GLA-JNS-005',
                'short_description' => '14.5 oz Kurabo Japanese raw shuttle-loom selvedge denim with gold selvedge ID line.',
                'detailed_description' => '<p>Woven on antique shuttle looms in Okayama, Japan, using 100% long-staple cotton dipped 16 times in natural indigo. Features our exclusive gold thread selvedge ticker along the outseam.</p><ul><li>14.5 oz Japanese Raw Indigo Selvedge Denim</li><li>Gold-line selvedge ticker</li><li>Solid brass custom stamped doughnut buttons</li><li>Embossed saddle leather back patch</li></ul>',
                'original_price' => 6999.00,
                'selling_price' => 4499.00,
                'stock' => 30,
                'sizes' => '30,32,34,36,38',
                'colors' => 'Raw Indigo,Washed Obsidian',
                'is_featured' => 1,
                'is_new_arrival' => 1,
                'is_best_seller' => 1,
                'images' => [
                    'assets/images/product-jeans-indigo-1.jpg',
                    'assets/images/product-jeans-indigo-2.jpg'
                ]
            ],
            [
                'category_slug' => 'accessories',
                'name' => 'Handcrafted Full-Grain Leather Gold Buckle Belt',
                'slug' => 'handcrafted-full-grain-leather-gold-buckle-belt',
                'sku' => 'GLA-ACC-006',
                'short_description' => 'Italian vegetable-tanned bridle leather belt with solid brass gold-polished buckle.',
                'detailed_description' => '<p>Hand-cut and burnished by master leather artisans. Crafted from premium 4mm Tuscan vegetable-tanned full-grain leather that patinas beautifully with age. Finished with a heavy brass buckle dipped in 24K gold lacquer.</p><ul><li>Full-grain Tuscan vegetable-tanned leather</li><li>Solid forged brass buckle with mirror gold finish</li><li>Beveled and hand-waxed edges</li><li>Presented in luxury emerald velvet dust bag</li></ul>',
                'original_price' => 3499.00,
                'selling_price' => 2199.00,
                'stock' => 45,
                'sizes' => '32,34,36,38,40',
                'colors' => 'Onyx Black,Cognac Brown,Emerald Green',
                'is_featured' => 1,
                'is_new_arrival' => 0,
                'is_best_seller' => 1,
                'images' => [
                    'assets/images/product-belt-black-1.jpg',
                    'assets/images/product-belt-black-2.jpg'
                ]
            ]
        ];

        $prodStmt = $pdo->prepare("
            INSERT INTO `products` (
                `category_id`, `name`, `slug`, `sku`, `short_description`, `detailed_description`,
                `original_price`, `selling_price`, `stock`, `sizes`, `colors`, `status`,
                `is_featured`, `is_new_arrival`, `is_best_seller`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, ?)
        ");

        $imgStmt = $pdo->prepare("
            INSERT INTO `product_images` (`product_id`, `image_path`, `is_primary`, `sort_order`)
            VALUES (?, ?, ?, ?)
        ");

        $varStmt = $pdo->prepare("
            INSERT INTO `product_variants` (`product_id`, `size`, `color`, `sku`, `stock`, `additional_price`, `status`)
            VALUES (?, ?, ?, ?, ?, 0.00, 'active')
        ");

        $reviewStmt = $pdo->prepare("
            INSERT INTO `reviews` (`product_id`, `user_id`, `rating`, `title`, `comment`, `status`)
            VALUES (?, ?, ?, ?, ?, 'approved')
        ");

        $firstUser = $pdo->query("SELECT id FROM users ORDER BY id ASC LIMIT 1")->fetch();
        $userId = $firstUser ? (int)$firstUser['id'] : 1;

        foreach ($products as $p) {
            $catId = $catMap[$p['category_slug']] ?? 1;
            $prodStmt->execute([
                $catId,
                $p['name'],
                $p['slug'],
                $p['sku'],
                $p['short_description'],
                $p['detailed_description'],
                $p['original_price'],
                $p['selling_price'],
                $p['stock'],
                $p['sizes'],
                $p['colors'],
                $p['is_featured'],
                $p['is_new_arrival'],
                $p['is_best_seller']
            ]);
            $productId = $pdo->lastInsertId();

            // Images
            foreach ($p['images'] as $idx => $img) {
                $imgStmt->execute([$productId, $img, ($idx === 0 ? 1 : 0), $idx]);
            }

            // Variants
            $sizeList = explode(',', $p['sizes']);
            $colorList = explode(',', $p['colors']);
            foreach ($sizeList as $s) {
                $s = trim($s);
                foreach ($colorList as $c) {
                    $c = trim($c);
                    $varSku = $p['sku'] . '-' . strtoupper(substr($s, 0, 3)) . '-' . strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $c), 0, 3));
                    $varStmt->execute([$productId, $s, $c, $varSku, 10]);
                }
            }

            // Sample Review
            $reviewStmt->execute([
                $productId,
                $userId,
                5,
                'Exquisite craftsmanship and bespoke fit',
                'The fabric weight, precision stitching, and rich emerald/gold finish are exceptional. Truly feels like haute couture luxury.'
            ]);
        }
    } catch (\Throwable $e) {
        error_log('ensureSampleStoreData error: ' . $e->getMessage());
    }
}

// Trigger Sample Store Data seeder automatically
ensureSampleStoreData();

/**
 * Fetch all carousel slides
 */
function getCarouselSlides(bool $onlyActive = true): array {
    $pdo = getDb();
    if ($onlyActive) {
        $stmt = $pdo->query("SELECT * FROM `carousel_slides` WHERE `status` = 'active' ORDER BY `sort_order` ASC, `id` ASC");
    } else {
        $stmt = $pdo->query("SELECT * FROM `carousel_slides` ORDER BY `sort_order` ASC, `id` ASC");
    }
    return $stmt->fetchAll();
}

/**
 * Fetch a single carousel slide by ID
 */
function getCarouselSlide(int $id): ?array {
    $pdo = getDb();
    $stmt = $pdo->prepare("SELECT * FROM `carousel_slides` WHERE `id` = ? LIMIT 1");
    $stmt->execute([$id]);
    $slide = $stmt->fetch();
    return $slide ?: null;
}



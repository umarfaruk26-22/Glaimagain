<?php
/**
 * GLAIMAGAIN - Store Cleanup Migration Endpoint
 * Purpose: One-time or manual store reset.
 * Clears: All products, variants, images, reviews, cart items.
 * Retains: Exactly 1 Category (ID 1), all admin users, user accounts, and settings.
 */
require_once __DIR__ . '/config/config.php';

$isCli = (php_sapi_name() === 'cli');
$providedKey = $_GET['key'] ?? '';
$secretKey = 'glaimagain_reset_clean_2026';

if (!$isCli && $providedKey !== $secretKey && !isAdminLoggedIn()) {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized access. Provide valid ?key= parameter or login as staff.']));
}

try {
    $pdo = getDb();

    // 1. Disable FK checks for clean truncation
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // 2. Clear user-side cart items
    $pdo->exec("TRUNCATE TABLE `cart_items`;");

    // 3. Clear user reviews
    $pdo->exec("TRUNCATE TABLE `reviews`;");

    // 4. Decouple past order items from product records so orders history remains intact
    $pdo->exec("UPDATE `order_items` SET `product_id` = NULL, `variant_id` = NULL;");

    // 5. Clear product images & variants
    $pdo->exec("TRUNCATE TABLE `product_images`;");
    $pdo->exec("TRUNCATE TABLE `product_variants`;");

    // 6. Clear products
    $pdo->exec("TRUNCATE TABLE `products`;");

    // 7. Retain only ONE category (the first category, id=1)
    $firstCat = $pdo->query("SELECT id FROM categories ORDER BY id ASC LIMIT 1")->fetch();
    if ($firstCat) {
        $firstId = (int)$firstCat['id'];
        $pdo->exec("DELETE FROM `categories` WHERE `id` != {$firstId};");
    }

    // 8. Re-enable foreign key constraints
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // 9. Record migration in settings table
    $stmt = $pdo->prepare("INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) 
        VALUES ('migration_clean_store_v1', 'completed', 'system') 
        ON DUPLICATE KEY UPDATE `setting_value` = 'completed'");
    $stmt->execute();

    $remainingCategories = $pdo->query("SELECT id, name, slug FROM categories")->fetchAll();
    $counts = [
        'status' => 'success',
        'message' => 'Store successfully cleaned. All products, reviews, and extra categories removed.',
        'products_count' => (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
        'reviews_count' => (int)$pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn(),
        'categories_count' => (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
        'categories' => $remainingCategories
    ];

    if ($isCli) {
        echo "====================================================\n";
        echo " GLAIMAGAIN STORE CLEANUP COMPLETED\n";
        echo " Products: " . $counts['products_count'] . "\n";
        echo " Reviews: " . $counts['reviews_count'] . "\n";
        echo " Categories: " . $counts['categories_count'] . "\n";
        echo " Category Details: " . json_encode($counts['categories']) . "\n";
        echo "====================================================\n";
    } else {
        header('Content-Type: application/json');
        echo json_encode($counts, JSON_PRETTY_PRINT);
    }
} catch (Throwable $e) {
    if ($isCli) {
        echo "Error during cleanup: " . $e->getMessage() . "\n";
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

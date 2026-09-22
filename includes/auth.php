<?php
/**
 * GLAIMAGAIN - User Authentication Helper
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Check if a customer user is currently logged in
 */
function isUserLoggedIn(): bool {
    return !empty($_SESSION['user_id']) && is_numeric($_SESSION['user_id']);
}

/**
 * Get current logged in user record
 */
function getCurrentUser(): ?array {
    if (!isUserLoggedIn()) {
        return null;
    }

    static $cachedUser = null;
    if ($cachedUser !== null) {
        return $cachedUser;
    }

    try {
        $pdo = getDb();
        $stmt = $pdo->prepare("SELECT `id`, `first_name`, `last_name`, `username`, `email`, `mobile`, `status`, `created_at` FROM `users` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if ($user && $user['status'] === 'active') {
            $cachedUser = $user;
            return $user;
        } else {
            // Inactive / blocked user session termination
            logoutUser();
            return null;
        }
    } catch (Exception $e) {
        error_log("Error fetching user: " . $e->getMessage());
        return null;
    }
}

/**
 * Enforce customer authentication (redirect to login if not authenticated)
 */
function requireUserLogin(?string $redirectUrl = null): void {
    if (!isUserLoggedIn()) {
        $intended = $redirectUrl ?: ($_SERVER['REQUEST_URI'] ?? BASE_URL);
        $_SESSION['intended_url'] = $intended;
        setFlashMessage('warning', 'Please sign in to your GLAIMAGAIN account to continue.');
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

/**
 * Log in customer user and regenerate session securely
 */
function loginUser(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_username'] = $user['username'];

    // Transfer guest cart to user cart
    $pdo = getDb();
    $sessionId = session_id();
    
    // Check if user already has an active cart
    $stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user['id']]);
    $userCart = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT id FROM carts WHERE session_id = ? AND (user_id IS NULL OR user_id = ?) LIMIT 1");
    $stmt->execute([$sessionId, $user['id']]);
    $sessionCart = $stmt->fetch();

    if ($userCart && $sessionCart && $userCart['id'] != $sessionCart['id']) {
        // Merge session cart items into user cart
        $stmt = $pdo->prepare("
            INSERT INTO cart_items (cart_id, product_id, variant_id, size, color, quantity)
            SELECT ?, product_id, variant_id, size, color, quantity FROM cart_items WHERE cart_id = ?
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
        ");
        $stmt->execute([$userCart['id'], $sessionCart['id']]);
        
        // Remove old session cart
        $pdo->prepare("DELETE FROM carts WHERE id = ?")->execute([$sessionCart['id']]);
    } elseif ($sessionCart && !$userCart) {
        // Associate session cart with user
        $stmt = $pdo->prepare("UPDATE carts SET user_id = ? WHERE id = ?");
        $stmt->execute([$user['id'], $sessionCart['id']]);
    }
}

/**
 * Log out customer user
 */
function logoutUser(): void {
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    unset($_SESSION['user_email']);
    unset($_SESSION['user_username']);
}

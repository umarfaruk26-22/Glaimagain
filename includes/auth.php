<?php
/**
 * GLAIMAGAIN - User Authentication Helper
 */

require_once __DIR__ . '/../config/config.php';

if (!defined('AUTH_REMEMBER_SECRET')) {
    define('AUTH_REMEMBER_SECRET', 'glaimagain_secret_patron_auth_key_2026');
}

/**
 * Check if a customer user is currently logged in (via Session or Persistent Cookie)
 */
function isUserLoggedIn(): bool {
    if (!empty($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
        return true;
    }

    // Auto-restore login from persistent 30-day cookie
    if (!empty($_COOKIE['glaim_remember_user'])) {
        $parts = explode(':', $_COOKIE['glaim_remember_user'], 2);
        if (count($parts) === 2) {
            $userId = (int)$parts[0];
            $sig = $parts[1];

            if ($userId > 0) {
                try {
                    $pdo = getDb();
                    $stmt = $pdo->prepare("SELECT `id`, `first_name`, `last_name`, `username`, `email`, `password`, `status` FROM `users` WHERE `id` = ? LIMIT 1");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();

                    if ($user && $user['status'] === 'active') {
                        $expectedSig = hash_hmac('sha256', $user['id'] . ':' . $user['password'], AUTH_REMEMBER_SECRET);
                        if (hash_equals($expectedSig, $sig)) {
                            // Seamlessly restore session state
                            $_SESSION['user_id'] = (int)$user['id'];
                            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                            $_SESSION['user_email'] = $user['email'];
                            $_SESSION['user_username'] = $user['username'];
                            return true;
                        }
                    }
                } catch (Exception $e) {
                    error_log("Cookie remember auth error: " . $e->getMessage());
                }
            }
        }
    }

    return false;
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
function loginUser(array $user, bool $remember = true): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_username'] = $user['username'];

    // Set persistent remember cookie (30 days)
    if ($remember) {
        $sig = hash_hmac('sha256', $user['id'] . ':' . $user['password'], AUTH_REMEMBER_SECRET);
        $cookieVal = $user['id'] . ':' . $sig;
        $expire = time() + (30 * 86400); // 30 days
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        setcookie('glaim_remember_user', $cookieVal, [
            'expires' => $expire,
            'path' => '/',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

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
 * Log out customer user and clear persistent cookies
 */
function logoutUser(): void {
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    unset($_SESSION['user_email']);
    unset($_SESSION['user_username']);

    // Clear persistent remember cookie
    if (isset($_COOKIE['glaim_remember_user'])) {
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        setcookie('glaim_remember_user', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        unset($_COOKIE['glaim_remember_user']);
    }
}

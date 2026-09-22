<?php
/**
 * GLAIMAGAIN - Admin Authentication Middleware
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Check if admin is currently authenticated
 */
function isAdminLoggedIn(): bool {
    return !empty($_SESSION['admin_id']) && is_numeric($_SESSION['admin_id']);
}

/**
 * Get current logged in admin
 */
function getCurrentAdmin(): ?array {
    if (!isAdminLoggedIn()) {
        return null;
    }

    static $cachedAdmin = null;
    if ($cachedAdmin !== null) {
        return $cachedAdmin;
    }

    try {
        $pdo = getDb();
        $stmt = $pdo->prepare("SELECT `id`, `username`, `email`, `name`, `status`, `created_at` FROM `admins` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();

        if ($admin && $admin['status'] === 'active') {
            $cachedAdmin = $admin;
            return $admin;
        } else {
            logoutAdmin();
            return null;
        }
    } catch (Exception $e) {
        error_log("Admin auth error: " . $e->getMessage());
        return null;
    }
}

/**
 * Enforce admin authentication guard for all admin pages
 */
function requireAdminLogin(): void {
    if (!isAdminLoggedIn() || !getCurrentAdmin()) {
        setFlashMessage('danger', 'Access restricted. Please log in to access the GLAIMAGAIN Admin Panel.');
        header('Location: ' . BASE_URL . 'admin/login.php');
        exit;
    }
}

/**
 * Log in admin
 */
function loginAdmin(array $admin): void {
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int)$admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_name'] = $admin['name'] ?? $admin['username'];
    $_SESSION['admin_email'] = $admin['email'];
}

/**
 * Log out admin
 */
function logoutAdmin(): void {
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['admin_name']);
    unset($_SESSION['admin_email']);
}

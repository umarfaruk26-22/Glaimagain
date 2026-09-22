<?php
/**
 * GLAIMAGAIN - Global Configuration File
 * Brand: GLAIMAGAIN (FASHION BEYOND TODAY)
 */

if (session_status() === PHP_SESSION_NONE) {
    // Session security settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Set Default Timezone
date_default_timezone_set('Asia/Kolkata');

// Application Details
define('APP_NAME', 'GLAIMAGAIN');
define('APP_TAGLINE', 'FASHION BEYOND TODAY');
define('APP_VERSION', '1.0.0');

// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'glaimagain_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Dynamic BASE_URL detection (Agnostic to XAMPP folder or root domain)
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Determine the base path from project root
    $scriptDir = str_replace('\\', '/', dirname(__DIR__));
    $docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));
    
    if (!empty($docRoot) && strpos($scriptDir, $docRoot) === 0) {
        $subDir = substr($scriptDir, strlen($docRoot));
        $baseUrl = $protocol . $host . rtrim($subDir, '/') . '/';
    } else {
        // Fallback detection based on SCRIPT_NAME
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $parts = explode('/', trim($scriptName, '/'));
        if (count($parts) > 1 && in_array(strtolower($parts[0]), ['glaimagain', 'glainagain'])) {
            $baseUrl = $protocol . $host . '/' . $parts[0] . '/';
        } else {
            $baseUrl = $protocol . $host . '/';
        }
    }
    define('BASE_URL', rtrim($baseUrl, '/') . '/');
}

// Absolute Paths
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('CONFIG_PATH', ROOT_PATH . 'config' . DIRECTORY_SEPARATOR);
define('INCLUDES_PATH', ROOT_PATH . 'includes' . DIRECTORY_SEPARATOR);
define('ASSETS_PATH', ROOT_PATH . 'assets' . DIRECTORY_SEPARATOR);
define('UPLOADS_PATH', ROOT_PATH . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);

// Store Defaults
// Custom constant to prevent collision with PHP intl CURRENCY_SYMBOL int(262145)
define('SITE_CURRENCY', '₹');
define('DEFAULT_SHIPPING_FEE', 99.00);
define('FREE_SHIPPING_THRESHOLD', 1999.00);

// Include Core Dependencies
require_once CONFIG_PATH . 'database.php';
require_once CONFIG_PATH . 'razorpay.php';
require_once INCLUDES_PATH . 'csrf.php';
require_once INCLUDES_PATH . 'functions.php';

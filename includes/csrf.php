<?php
/**
 * GLAIMAGAIN - CSRF Protection Helper
 */

/**
 * Generate or retrieve the CSRF token for the current session
 *
 * @return string
 */
function getCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Generate a hidden HTML input containing the CSRF token
 *
 * @return string
 */
function csrfField(): string {
    $token = htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Verify incoming CSRF token from POST or Header
 *
 * @param string|null $token
 * @return bool
 */
function verifyCsrfToken(?string $token = null): bool {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }

    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Validate CSRF token or terminate request with 403
 */
function requireCsrfToken(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrfToken()) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'CSRF security token verification failed. Please refresh.']);
                exit;
            }
            die('<div style="font-family:sans-serif;padding:40px;text-align:center;background:#013C26;color:#FFF;">
                <h2 style="color:#B99036;">Security Alert (CSRF Error)</h2>
                <p>Invalid or expired session token. Please go back, refresh the page, and try again.</p>
                <a href="' . BASE_URL . '" style="color:#B99036;text-decoration:underline;">Return to Homepage</a>
            </div>');
        }
    }
}

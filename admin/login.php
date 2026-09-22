<?php
/**
 * GLAIMAGAIN - Admin Portal Authentication
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

if (isAdminLoggedIn()) {
    header('Location: ' . BASE_URL . 'admin/dashboard');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $identity = trim($_POST['identity'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identity) || empty($password)) {
        $error = 'Please enter both administrator identity and password.';
    } else {
        $pdo = getDb();
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE (username = ? OR email = ?) LIMIT 1");
        $stmt->execute([$identity, $identity]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            if ($admin['status'] === 'inactive') {
                $error = 'This administrator account is currently suspended.';
            } else {
                loginAdmin($admin);
                setFlashMessage('success', 'Welcome back to GLAIMAGAIN Command Suite, ' . ($admin['name'] ?? $admin['username']) . '.');
                header('Location: ' . BASE_URL . 'admin/dashboard');
                exit;
            }
        } else {
            $error = 'Invalid administrator credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Authentication | GLAIMAGAIN</title>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>assets/images/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css?v=<?= time() ?>">
</head>
<body class="admin-login-body">
    <div class="admin-login-card">
        <div class="admin-login-header">
            <div class="d-inline-block bg-white p-2 px-3 rounded-3 shadow-sm mb-2">
                <img src="<?= BASE_URL ?>assets/images/glaimagain-logo.png" alt="GLAIMAGAIN" style="height: 48px; max-width: 240px; object-fit: contain;">
            </div>
            <div class="text-gold letter-spacing-3 small mt-1 fw-bold">COMMAND SUITE ACCESS</div>
        </div>

        <div class="p-4 p-md-5">
            <?php if ($error): ?>
                <div class="alert alert-danger small py-2 mb-4 d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-2"></i> <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>admin/login">
                <?= csrfField() ?>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase text-muted">Admin Username or Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-user-shield text-muted"></i></span>
                        <input type="text" name="identity" class="form-control" placeholder="admin" value="<?= isset($_POST['identity']) ? e($_POST['identity']) : '' ?>" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-uppercase text-muted">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-admin-gold w-100 py-3">
                    <i class="fas fa-sign-in-alt me-2"></i> AUTHENTICATE &amp; ENTER
                </button>
            </form>

            <div class="mt-4 pt-3 border-top text-center">
                <a href="<?= BASE_URL ?>" class="text-muted small text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i> Return to Client Boutique
                </a>
            </div>
        </div>
    </div>
</body>
</html>

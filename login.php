<?php
/**
 * GLAIMAGAIN - Customer Sign In Page
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isUserLoggedIn()) {
    header('Location: ' . BASE_URL . 'account/profile.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $identity = trim($_POST['identity'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identity) || empty($password)) {
        $error = 'Please enter both your Username/Email and Password.';
    } else {
        $pdo = getDb();
        $stmt = $pdo->prepare("
            SELECT * FROM users
            WHERE (username = ? OR email = ?)
            LIMIT 1
        ");
        $stmt->execute([$identity, $identity]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'blocked') {
                $error = 'Your account has been suspended. Please contact concierge.';
            } else {
                loginUser($user);
                setFlashMessage('success', 'Welcome back, ' . $user['first_name'] . '.');

                $redirect = $_SESSION['intended_url'] ?? (BASE_URL . 'account/profile.php');
                unset($_SESSION['intended_url']);
                header('Location: ' . $redirect);
                exit;
            }
        } else {
            $error = 'Invalid credentials provided. Please check your username/email and password.';
        }
    }
}

$pageTitle = 'Client Sign In | GLAIMAGAIN';
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-5 bg-offwhite min-vh-75 d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                <div class="p-4 p-md-5 bg-white border border-gold-subtle rounded shadow-lg">
                    <div class="text-center mb-4">
                        <img src="<?= BASE_URL ?>assets/images/glaimagain-logo.jpg" alt="GLAIMAGAIN" style="height: 52px; border-radius: 8px;" class="mb-2 shadow-sm">
                        <h4 class="fw-bold text-emerald mt-2">CLIENT SIGN IN</h4>
                        <p class="text-muted small">Access your bespoke orders, wishlist, and saved addresses.</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger small py-2 d-flex align-items-center mb-4">
                            <i class="fas fa-exclamation-circle me-2"></i> <?= e($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= BASE_URL ?>login.php" class="form-luxury">
                        <?= csrfField() ?>
                        <div class="mb-3">
                            <label class="form-label">Username or Email</label>
                            <input type="text" name="identity" class="form-control" placeholder="Enter username or email" value="<?= isset($_POST['identity']) ? e($_POST['identity']) : '' ?>" required autofocus>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="form-label mb-0">Password</label>
                                <a href="<?= BASE_URL ?>contact.php?subject=Password+Reset+Inquiry" class="text-gold small text-decoration-underline">Need Help?</a>
                            </div>
                            <input type="password" name="password" class="form-control mt-1" placeholder="••••••••" required>
                        </div>

                        <button type="submit" class="btn btn-luxury-primary w-100 py-3 mt-3">
                            <i class="fas fa-sign-in-alt me-2"></i> SIGN IN
                        </button>
                    </form>

                    <div class="text-center mt-4 pt-3 border-top">
                        <span class="text-muted small">New to GLAIMAGAIN?</span>
                        <a href="<?= BASE_URL ?>register.php" class="text-gold fw-bold small ms-1 text-decoration-underline">Create an Account</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

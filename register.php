<?php
/**
 * GLAIMAGAIN - Customer Registration Page
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isUserLoggedIn()) {
    header('Location: ' . BASE_URL . 'account/profile.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $username = strtolower(trim($_POST['username'] ?? ''));
    $email = strtolower(trim($_POST['email'] ?? ''));
    $mobile = trim($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validations
    if (empty($firstName) || empty($lastName)) {
        $errors[] = 'First Name and Last Name are required.';
    }

    if (empty($username) || !preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $errors[] = 'Username must be 3-30 characters long and contain only letters, numbers, and underscores.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($mobile) || strlen($mobile) < 7) {
        $errors[] = 'Please enter a valid mobile number.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters in length.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $pdo = getDb();

        // 1. Check Username Uniqueness
        $checkUserStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $checkUserStmt->execute([$username]);
        if ($checkUserStmt->fetch()) {
            $errors[] = 'Username already exists. Please choose another username.';
        }

        // 2. Check Email Uniqueness
        $checkEmailStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $checkEmailStmt->execute([$email]);
        if ($checkEmailStmt->fetch()) {
            $errors[] = 'Email is already registered. Please sign in or use a different email.';
        }

        if (empty($errors)) {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("
                INSERT INTO users (first_name, last_name, username, email, mobile, password, status)
                VALUES (?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([$firstName, $lastName, $username, $email, $mobile, $hashedPassword]);
            $newUserId = (int)$pdo->lastInsertId();

            // Log user in automatically
            $newUser = [
                'id' => $newUserId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'username' => $username,
                'email' => $email,
                'mobile' => $mobile
            ];
            loginUser($newUser);

            setFlashMessage('success', 'Your GLAIMAGAIN account has been created successfully. Welcome!');
            header('Location: ' . BASE_URL . 'account/profile.php');
            exit;
        }
    }
}

$pageTitle = 'Create an Account | GLAIMAGAIN';
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-5 bg-offwhite min-vh-75 d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="p-4 p-md-5 bg-white border border-gold-subtle rounded shadow-lg">
                    <div class="text-center mb-4">
                        <img src="<?= BASE_URL ?>assets/images/glaimagain-logo.png" alt="GLAIMAGAIN" style="height: 52px; width: auto; object-fit: contain;" class="mb-2">
                        <h4 class="fw-bold text-emerald mt-2">CREATE PATRON ACCOUNT</h4>
                        <p class="text-muted small">Join GLAIMAGAIN for personalized tailoring and priority drops.</p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger small py-2 mb-4">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= e($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= BASE_URL ?>register.php" class="form-luxury">
                        <?= csrfField() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name <span class="text-gold">*</span></label>
                                <input type="text" name="first_name" class="form-control" value="<?= isset($_POST['first_name']) ? e($_POST['first_name']) : '' ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name <span class="text-gold">*</span></label>
                                <input type="text" name="last_name" class="form-control" value="<?= isset($_POST['last_name']) ? e($_POST['last_name']) : '' ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Username <span class="text-gold">*</span></label>
                                <input type="text" name="username" class="form-control" placeholder="e.g. alexander_g" value="<?= isset($_POST['username']) ? e($_POST['username']) : '' ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mobile Number <span class="text-gold">*</span></label>
                                <input type="tel" name="mobile" class="form-control" placeholder="+91 98765 43210" value="<?= isset($_POST['mobile']) ? e($_POST['mobile']) : '' ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Email Address <span class="text-gold">*</span></label>
                                <input type="email" name="email" class="form-control" placeholder="name@domain.com" value="<?= isset($_POST['email']) ? e($_POST['email']) : '' ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password <span class="text-gold">*</span></label>
                                <input type="password" name="password" class="form-control" placeholder="Min 6 characters" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirm Password <span class="text-gold">*</span></label>
                                <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-luxury-primary w-100 py-3">
                                    <i class="fas fa-user-plus me-2"></i> COMPLETE REGISTRATION
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="text-center mt-4 pt-3 border-top">
                        <span class="text-muted small">Already hold a GLAIMAGAIN account?</span>
                        <a href="<?= BASE_URL ?>login.php" class="text-gold fw-bold small ms-1 text-decoration-underline">Sign In</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php
/**
 * GLAIMAGAIN - Account Profile Management
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireUserLogin();
$currentUser = getCurrentUser();
$pdo = getDb();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (empty($firstName) || empty($lastName) || empty($email)) {
        $errors[] = 'First Name, Last Name, and Email are required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Check email uniqueness if changed
    if ($email !== strtolower($currentUser['email'])) {
        $chk = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        $chk->execute([$email, $currentUser['id']]);
        if ($chk->fetch()) {
            $errors[] = 'This email address is already in use by another patron.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET first_name = ?, last_name = ?, mobile = ?, email = ?
            WHERE id = ?
        ");
        $stmt->execute([$firstName, $lastName, $mobile, $email, $currentUser['id']]);

        // Refresh session
        $_SESSION['user_name'] = $firstName . ' ' . $lastName;
        $_SESSION['user_email'] = $email;

        setFlashMessage('success', 'Your profile details have been updated successfully.');
        header('Location: ' . BASE_URL . 'account/profile.php');
        exit;
    }
}

$pageTitle = 'My Profile | GLAIMAGAIN';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="bg-emerald text-white py-4 border-bottom border-gold-subtle">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <span class="text-gold fw-bold letter-spacing-3 small text-uppercase">Patron Dashboard</span>
            <h1 class="h3 fw-bold text-white mb-0">MY ACCOUNT</h1>
        </div>
        <div class="text-end">
            <span class="small text-white-50">Member Since: <?= date('F Y', strtotime($currentUser['created_at'])) ?></span>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">
        <!-- Account Sidebar Navigation -->
        <div class="col-lg-3">
            <div class="p-3 bg-white border border-gold-subtle rounded shadow-sm">
                <div class="text-center py-3 border-bottom mb-3">
                    <div class="action-btn bg-emerald text-gold rounded-circle mx-auto d-flex align-items-center justify-content-center mb-2" style="width: 54px; height: 54px; font-size: 22px;">
                        <?= strtoupper(substr($currentUser['first_name'], 0, 1)) ?>
                    </div>
                    <h6 class="fw-bold text-emerald mb-0"><?= e($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?></h6>
                    <span class="text-muted small">@<?= e($currentUser['username']) ?></span>
                </div>
                <div class="d-flex flex-column gap-1">
                    <a href="<?= BASE_URL ?>account/profile.php" class="p-2 rounded text-decoration-none fw-bold bg-offwhite text-emerald border-start border-3 border-gold">
                        <i class="fas fa-id-card text-gold me-2"></i> Profile Details
                    </a>
                    <a href="<?= BASE_URL ?>account/orders.php" class="p-2 rounded text-decoration-none text-muted">
                        <i class="fas fa-box-open me-2"></i> Order History
                    </a>
                    <a href="<?= BASE_URL ?>account/addresses.php" class="p-2 rounded text-decoration-none text-muted">
                        <i class="fas fa-map-marker-alt me-2"></i> Delivery Addresses
                    </a>
                    <a href="<?= BASE_URL ?>account/change-password.php" class="p-2 rounded text-decoration-none text-muted">
                        <i class="fas fa-lock me-2"></i> Security &amp; Password
                    </a>
                    <hr class="my-2 border-secondary">
                    <a href="<?= BASE_URL ?>logout.php" class="p-2 rounded text-decoration-none text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Sign Out
                    </a>
                </div>
            </div>
        </div>

        <!-- Account Profile Form -->
        <div class="col-lg-9">
            <div class="p-4 p-md-5 bg-white border border-gold-subtle rounded shadow-sm">
                <h4 class="fw-bold text-emerald mb-2">PERSONAL PARTICULARS</h4>
                <p class="text-muted small mb-4">Manage your personal identification and correspondence channels.</p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger small py-2 mb-4">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= BASE_URL ?>account/profile.php" class="form-luxury">
                    <?= csrfField() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" value="<?= e($currentUser['first_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" value="<?= e($currentUser['last_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Username (Immutable)</label>
                            <input type="text" class="form-control bg-light" value="<?= e($currentUser['username']) ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile Number</label>
                            <input type="tel" name="mobile" class="form-control" value="<?= e($currentUser['mobile']) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?= e($currentUser['email']) ?>" required>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-luxury-primary">
                                <i class="fas fa-save me-2"></i> UPDATE PROFILE DETAILS
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

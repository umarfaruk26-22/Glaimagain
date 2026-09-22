<?php
/**
 * GLAIMAGAIN - Change Password Page
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireUserLogin();
$currentUser = getCurrentUser();
$pdo = getDb();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Fetch user hash
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$currentUser['id']]);
    $userRow = $stmt->fetch();

    if (!password_verify($currentPassword, $userRow['password'])) {
        $errors[] = 'Current password is incorrect.';
    }

    if (strlen($newPassword) < 6) {
        $errors[] = 'New password must be at least 6 characters in length.';
    }

    if ($newPassword !== $confirmPassword) {
        $errors[] = 'New password confirmation does not match.';
    }

    if (empty($errors)) {
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $upd->execute([$newHash, $currentUser['id']]);

        setFlashMessage('success', 'Your password has been successfully updated.');
        header('Location: ' . BASE_URL . 'account/change-password.php');
        exit;
    }
}

$pageTitle = 'Security & Password | GLAIMAGAIN';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="bg-emerald text-white py-4 border-bottom border-gold-subtle">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <span class="text-gold fw-bold letter-spacing-3 small text-uppercase">Patron Dashboard</span>
            <h1 class="h3 fw-bold text-white mb-0">SECURITY &amp; PASSWORD</h1>
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
                    <a href="<?= BASE_URL ?>account/profile.php" class="p-2 rounded text-decoration-none text-muted">
                        <i class="fas fa-id-card me-2"></i> Profile Details
                    </a>
                    <a href="<?= BASE_URL ?>account/orders.php" class="p-2 rounded text-decoration-none text-muted">
                        <i class="fas fa-box-open me-2"></i> Order History
                    </a>
                    <a href="<?= BASE_URL ?>account/addresses.php" class="p-2 rounded text-decoration-none text-muted">
                        <i class="fas fa-map-marker-alt me-2"></i> Delivery Addresses
                    </a>
                    <a href="<?= BASE_URL ?>account/change-password.php" class="p-2 rounded text-decoration-none fw-bold bg-offwhite text-emerald border-start border-3 border-gold">
                        <i class="fas fa-lock text-gold me-2"></i> Security &amp; Password
                    </a>
                    <hr class="my-2 border-secondary">
                    <a href="<?= BASE_URL ?>logout.php" class="p-2 rounded text-decoration-none text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Sign Out
                    </a>
                </div>
            </div>
        </div>

        <!-- Change Password Form -->
        <div class="col-lg-9">
            <div class="p-4 p-md-5 bg-white border border-gold-subtle rounded shadow-sm">
                <h4 class="fw-bold text-emerald mb-2">UPDATE ACCOUNT PASSWORD</h4>
                <p class="text-muted small mb-4">Protect your GLAIMAGAIN profile by maintaining strong authentication credentials.</p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger small py-2 mb-4">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= BASE_URL ?>account/change-password.php" class="form-luxury max-w-500">
                    <?= csrfField() ?>
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Minimum 6 characters" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password" required>
                    </div>

                    <button type="submit" class="btn btn-luxury-primary w-100 py-3">
                        <i class="fas fa-shield-alt me-2"></i> SAVE NEW PASSWORD
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

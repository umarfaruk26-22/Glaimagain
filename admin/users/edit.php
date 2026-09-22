<?php
/**
 * GLAIMAGAIN - Admin Edit User / Patron Status
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();
$userId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    setFlashMessage('danger', 'Patron not found.');
    header('Location: ' . BASE_URL . 'admin/users/index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['active', 'blocked']) ? $_POST['status'] : 'active';

    if (empty($firstName) || empty($lastName)) {
        $errors[] = 'First Name and Last Name are required.';
    }

    if (empty($errors)) {
        $upd = $pdo->prepare("
            UPDATE users 
            SET first_name = ?, last_name = ?, mobile = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $upd->execute([$firstName, $lastName, $mobile, $status, $user['id']]);

        setFlashMessage('success', "Patron account @{$user['username']} updated.");
        header('Location: ' . BASE_URL . 'admin/users/view.php?id=' . $user['id']);
        exit;
    }
}

$adminHeaderHeading = 'Edit Patron: ' . e($user['username']);
$adminTitle = 'Edit Patron | GLAIMAGAIN Admin';
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="admin-card p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold text-emerald mb-0">Modify Patron Account Status</h5>
                <a href="<?= BASE_URL ?>admin/users/index.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Back to Patrons
                </a>
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

            <form method="POST" action="<?= BASE_URL ?>admin/users/edit.php?id=<?= $user['id'] ?>" class="form-luxury">
                <?= csrfField() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control" value="<?= e($user['first_name']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control" value="<?= e($user['last_name']) ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control bg-light" value="<?= e($user['username']) ?>" disabled>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control bg-light" value="<?= e($user['email']) ?>" disabled>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Mobile Number</label>
                        <input type="tel" name="mobile" class="form-control" value="<?= e($user['mobile']) ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Account Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active (Can Login & Order)</option>
                            <option value="blocked" <?= $user['status'] === 'blocked' ? 'selected' : '' ?>>Blocked (Access Restricted)</option>
                        </select>
                    </div>

                    <div class="col-12 mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-admin-primary w-100 py-2">
                            <i class="fas fa-save me-2"></i> UPDATE PATRON ACCOUNT
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/admin-footer.php'; ?>

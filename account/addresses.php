<?php
/**
 * GLAIMAGAIN - Delivery Addresses Management
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireUserLogin();
$currentUser = getCurrentUser();
$pdo = getDb();

// Handle Actions: Add, Set Default, Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $fullName = trim($_POST['full_name'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $addr1 = trim($_POST['address_line_1'] ?? '');
        $addr2 = trim($_POST['address_line_2'] ?? '');
        $area = trim($_POST['area'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $pincode = trim($_POST['pincode'] ?? '');
        $type = in_array($_POST['address_type'] ?? '', ['home', 'office', 'other']) ? $_POST['address_type'] : 'home';
        $isDefault = !empty($_POST['is_default']) ? 1 : 0;

        if (empty($fullName) || empty($mobile) || empty($addr1) || empty($city) || empty($state) || empty($pincode)) {
            setFlashMessage('danger', 'Please complete all required address fields.');
        } else {
            if ($isDefault) {
                $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$currentUser['id']]);
            }

            $stmt = $pdo->prepare("
                INSERT INTO user_addresses (user_id, full_name, mobile, address_line_1, address_line_2, area, city, state, pincode, address_type, is_default)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$currentUser['id'], $fullName, $mobile, $addr1, $addr2, $area, $city, $state, $pincode, $type, $isDefault]);
            setFlashMessage('success', 'New delivery address has been saved.');
            header('Location: ' . BASE_URL . 'account/addresses.php');
            exit;
        }
    } elseif ($action === 'set_default') {
        $addressId = (int)($_POST['address_id'] ?? 0);
        $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$currentUser['id']]);
        $pdo->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?")->execute([$addressId, $currentUser['id']]);
        setFlashMessage('success', 'Default delivery address updated.');
        header('Location: ' . BASE_URL . 'account/addresses.php');
        exit;
    } elseif ($action === 'delete') {
        $addressId = (int)($_POST['address_id'] ?? 0);
        $pdo->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?")->execute([$addressId, $currentUser['id']]);
        setFlashMessage('info', 'Address removed.');
        header('Location: ' . BASE_URL . 'account/addresses.php');
        exit;
    }
}

// Fetch user addresses
$stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
$stmt->execute([$currentUser['id']]);
$addresses = $stmt->fetchAll();

$pageTitle = 'Saved Addresses | GLAIMAGAIN';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="bg-emerald text-white py-4 border-bottom border-gold-subtle">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <span class="text-gold fw-bold letter-spacing-3 small text-uppercase">Patron Dashboard</span>
            <h1 class="h3 fw-bold text-white mb-0">SAVED ADDRESSES</h1>
        </div>
        <button class="btn btn-luxury-gold btn-sm" data-bs-toggle="modal" data-bs-target="#addAddressModal">
            <i class="fas fa-plus me-1"></i> ADD ADDRESS
        </button>
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
                    <a href="<?= BASE_URL ?>account/addresses.php" class="p-2 rounded text-decoration-none fw-bold bg-offwhite text-emerald border-start border-3 border-gold">
                        <i class="fas fa-map-marker-alt text-gold me-2"></i> Delivery Addresses
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

        <!-- Address List -->
        <div class="col-lg-9">
            <div class="p-4 p-md-5 bg-white border border-gold-subtle rounded shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold text-emerald mb-1">REGISTERED DESTINATIONS</h4>
                        <p class="text-muted small mb-0">Your addresses for seamless checkout and express dispatch.</p>
                    </div>
                    <button class="btn btn-luxury-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                        <i class="fas fa-plus me-1"></i> Add New Address
                    </button>
                </div>

                <?php if (empty($addresses)): ?>
                    <div class="text-center py-5 bg-offwhite border rounded p-4">
                        <i class="fas fa-map-marker-alt text-gold fs-1 mb-2"></i>
                        <h5 class="text-emerald fw-bold">No Saved Addresses</h5>
                        <p class="text-muted small mb-3">Please add a delivery address to accelerate your checkout experience.</p>
                        <button class="btn btn-luxury-gold btn-sm" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                            + ADD YOUR FIRST ADDRESS
                        </button>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($addresses as $addr): ?>
                            <div class="col-md-6">
                                <div class="p-4 border <?= $addr['is_default'] ? 'border-gold bg-offwhite shadow-sm' : 'border-light-gray bg-white' ?> rounded position-relative h-100 d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge <?= $addr['is_default'] ? 'bg-emerald text-gold' : 'bg-secondary' ?> text-uppercase small">
                                            <?= e($addr['address_type']) ?>
                                        </span>
                                        <?php if ($addr['is_default']): ?>
                                            <span class="badge bg-gold text-dark fw-bold small"><i class="fas fa-check me-1"></i>DEFAULT</span>
                                        <?php endif; ?>
                                    </div>

                                    <h6 class="fw-bold text-emerald mb-1"><?= e($addr['full_name']) ?></h6>
                                    <p class="text-muted small mb-2"><i class="fas fa-phone-alt text-gold me-1"></i> <?= e($addr['mobile']) ?></p>
                                    <p class="text-muted small mb-3 flex-grow-1">
                                        <?= e($addr['address_line_1']) ?><?= !empty($addr['address_line_2']) ? ', ' . e($addr['address_line_2']) : '' ?><br>
                                        <?= !empty($addr['area']) ? e($addr['area']) . ', ' : '' ?><?= e($addr['city']) ?>, <?= e($addr['state']) ?> - <?= e($addr['pincode']) ?>
                                    </p>

                                    <div class="d-flex gap-2 pt-2 border-top">
                                        <?php if (!$addr['is_default']): ?>
                                            <form method="POST" action="<?= BASE_URL ?>account/addresses.php" class="d-inline">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="set_default">
                                                <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                                                <button type="submit" class="btn btn-outline-secondary btn-sm py-1 px-2" style="font-size: 11.5px;">Set Default</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" action="<?= BASE_URL ?>account/addresses.php" class="d-inline ms-auto">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2 btn-confirm-delete" data-confirm-message="Delete this address?" style="font-size: 11.5px;">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add New Address -->
<div class="modal fade" id="addAddressModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-gold shadow-lg" style="border: 2px solid #B99036;">
            <div class="modal-header bg-emerald text-white">
                <h5 class="modal-title font-serif"><i class="fas fa-map-marker-alt text-gold me-2"></i> ADD DELIVERY ADDRESS</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST" action="<?= BASE_URL ?>account/addresses.php" class="form-luxury">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name <span class="text-gold">*</span></label>
                            <input type="text" name="full_name" class="form-control" value="<?= e($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile Number <span class="text-gold">*</span></label>
                            <input type="tel" name="mobile" class="form-control" value="<?= e($currentUser['mobile']) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address Line 1 (Flat, House No, Building) <span class="text-gold">*</span></label>
                            <input type="text" name="address_line_1" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address Line 2 (Street, Landmark)</label>
                            <input type="text" name="address_line_2" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">City <span class="text-gold">*</span></label>
                            <input type="text" name="city" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">State <span class="text-gold">*</span></label>
                            <input type="text" name="state" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pincode <span class="text-gold">*</span></label>
                            <input type="text" name="pincode" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Address Type</label>
                            <select name="address_type" class="form-select">
                                <option value="home">Home (All Day Delivery)</option>
                                <option value="office">Office (10 AM - 6 PM)</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-center mt-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="defCheck" checked>
                                <label class="form-check-label small" for="defCheck">
                                    Set as default shipping address
                                </label>
                            </div>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-luxury-primary w-100 py-3">
                                SAVE DELIVERY ADDRESS
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

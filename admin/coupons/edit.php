<?php
/**
 * GLAIMAGAIN - Admin Edit Promo Code
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();
$errors = [];

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$coupon = $stmt->fetch();

if (!$coupon) {
    setFlashMessage('danger', 'Promo code not found.');
    header('Location: ' . BASE_URL . 'admin/coupons');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $code = strtoupper(trim($_POST['code'] ?? ''));
    $description = trim($_POST['description'] ?? '');
    $discountType = in_array($_POST['discount_type'] ?? '', ['percentage', 'fixed']) ? $_POST['discount_type'] : 'percentage';
    $discountValue = (float)($_POST['discount_value'] ?? 0);
    $minOrderAmount = (float)($_POST['min_order_amount'] ?? 0);
    $maxDiscountAmount = !empty($_POST['max_discount_amount']) ? (float)$_POST['max_discount_amount'] : null;
    $usageLimit = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
    $validFrom = !empty($_POST['valid_from']) ? date('Y-m-d H:i:s', strtotime($_POST['valid_from'])) : null;
    $validUntil = !empty($_POST['valid_until']) ? date('Y-m-d H:i:s', strtotime($_POST['valid_until'])) : null;
    $status = isset($_POST['is_active']) ? 'active' : 'inactive';

    // Validation
    if (empty($code)) {
        $errors[] = 'Promo code name is required.';
    } elseif (!preg_match('/^[A-Z0-9_-]{3,30}$/', $code)) {
        $errors[] = 'Promo code must be 3-30 characters alphanumeric (letters, numbers, underscores, dashes only).';
    }

    if ($discountValue <= 0) {
        $errors[] = 'Discount value must be greater than zero.';
    }

    if ($discountType === 'percentage' && $discountValue > 100) {
        $errors[] = 'Percentage discount cannot exceed 100%.';
    }

    if (!empty($validFrom) && !empty($validUntil) && strtotime($validUntil) < strtotime($validFrom)) {
        $errors[] = 'Validity end date cannot be earlier than start date.';
    }

    // Check code uniqueness excluding current id
    $chk = $pdo->prepare("SELECT id FROM coupons WHERE code = ? AND id != ? LIMIT 1");
    $chk->execute([$code, $id]);
    if ($chk->fetch()) {
        $errors[] = "Another promo code with name '{$code}' already exists. Please choose a unique code.";
    }

    if (empty($errors)) {
        $upd = $pdo->prepare("
            UPDATE coupons 
            SET code = ?, description = ?, discount_type = ?, discount_value = ?, min_order_amount = ?, max_discount_amount = ?, usage_limit = ?, valid_from = ?, valid_until = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $upd->execute([
            $code, 
            $description, 
            $discountType, 
            $discountValue, 
            $minOrderAmount, 
            $maxDiscountAmount, 
            $usageLimit, 
            $validFrom, 
            $validUntil, 
            $status,
            $id
        ]);

        setFlashMessage('success', "Promo code '{$code}' updated successfully!");
        header('Location: ' . BASE_URL . 'admin/coupons');
        exit;
    }
    // Repopulate form with submitted values
    $coupon = array_merge($coupon, $_POST);
}

$adminHeaderHeading = 'Edit Promo Code: ' . $coupon['code'];
$adminTitle = 'Edit Promo Code | GLAIMAGAIN Admin';
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="mb-4 d-flex justify-content-between align-items-center">
    <a href="<?= BASE_URL ?>admin/coupons" class="btn btn-outline-dark btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back to Promo Codes
    </a>
    <div class="text-muted small">
        <i class="fas fa-shopping-bag text-gold me-1"></i> Redeemed: <strong><?= (int)$coupon['used_count'] ?> times</strong>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger shadow-sm">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="admin-card p-4 p-md-5">
    <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <div class="stat-icon-circle bg-mint text-forest fs-4">
                <i class="fas fa-edit"></i>
            </div>
            <div>
                <h5 class="fw-bold text-emerald mb-1">Update Promo Code &bull; <?= e($coupon['code']) ?></h5>
                <p class="text-muted small mb-0">Modify discounts, customer validity thresholds, and status.</p>
            </div>
        </div>
        <span class="badge <?= (($coupon['status'] ?? '') === 'active') ? 'bg-success' : 'bg-secondary' ?> fs-6 px-3 py-2">
            <?= (($coupon['status'] ?? '') === 'active') ? 'ACTIVE IN STORE' : 'INACTIVE' ?>
        </span>
    </div>

    <form method="POST" action="<?= BASE_URL ?>admin/coupons/edit.php?id=<?= $coupon['id'] ?>" class="form-luxury">
        <?= csrfField() ?>

        <div class="row g-4">
            <!-- 1. Code & Type -->
            <div class="col-md-6">
                <label class="form-label fw-bold">Promo Code <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-ticket-alt text-gold"></i></span>
                    <input type="text" name="code" class="form-control text-uppercase fw-bold letter-spacing-1" placeholder="e.g. GLAIM40" value="<?= e($coupon['code'] ?? '') ?>" required>
                </div>
                <div class="form-text small">Uppercase alphanumeric identifier for checkout &amp; banners.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Discount Type <span class="text-danger">*</span></label>
                <select name="discount_type" id="discountTypeSelect" class="form-select" onchange="updateDiscountLabels()">
                    <option value="percentage" <?= ($coupon['discount_type'] === 'percentage') ? 'selected' : '' ?>>Percentage Discount (% OFF)</option>
                    <option value="fixed" <?= ($coupon['discount_type'] === 'fixed') ? 'selected' : '' ?>>Fixed Amount Discount (₹ FLAT OFF)</option>
                </select>
                <div class="form-text small">Select whether the discount is a % of the subtotal or a fixed cash amount.</div>
            </div>

            <!-- 2. Value & Max Discount -->
            <div class="col-md-6">
                <label class="form-label fw-bold" id="discountValueLabel">Discount Rate (%) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text" id="discountValueAddon"><i class="fas fa-percent"></i></span>
                    <input type="number" step="0.01" min="0.01" name="discount_value" class="form-control" placeholder="e.g. 40" value="<?= (float)($coupon['discount_value'] ?? 0) ?>" required>
                </div>
                <div class="form-text small" id="discountValueHint">Enter discount percentage (e.g. 40 for 40% OFF).</div>
            </div>

            <div class="col-md-6" id="maxDiscountWrap">
                <label class="form-label fw-bold">Maximum Discount Cap (₹)</label>
                <div class="input-group">
                    <span class="input-group-text">₹</span>
                    <input type="number" step="0.01" min="0" name="max_discount_amount" class="form-control" placeholder="e.g. 2000 (Optional)" value="<?= e($coupon['max_discount_amount'] ?? '') ?>">
                </div>
                <div class="form-text small">Maximum savings limit for percentage discounts. Leave blank for unlimited.</div>
            </div>

            <!-- 3. Minimum Order Spend & Usage Limit -->
            <div class="col-md-6">
                <label class="form-label fw-bold">Minimum Order Spend (₹)</label>
                <div class="input-group">
                    <span class="input-group-text">₹</span>
                    <input type="number" step="0.01" min="0" name="min_order_amount" class="form-control" placeholder="0" value="<?= (float)($coupon['min_order_amount'] ?? 0) ?>">
                </div>
                <div class="form-text small">Minimum cart subtotal required to activate this code (0 for no minimum).</div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Overall Redemptions Limit</label>
                <input type="number" min="1" name="usage_limit" class="form-control" placeholder="e.g. 500 (Optional)" value="<?= e($coupon['usage_limit'] ?? '') ?>">
                <div class="form-text small">Total times this promo code can be redeemed across all patrons (Current: <?= (int)$coupon['used_count'] ?>).</div>
            </div>

            <!-- 4. Validity Dates -->
            <div class="col-md-6">
                <label class="form-label fw-bold"><i class="fas fa-calendar-alt text-gold me-1"></i> Valid From (Start Date &amp; Time)</label>
                <?php 
                $validFromVal = '';
                if (!empty($coupon['valid_from'])) {
                    $validFromVal = date('Y-m-d\TH:i', strtotime($coupon['valid_from']));
                }
                ?>
                <input type="datetime-local" name="valid_from" class="form-control" value="<?= $validFromVal ?>">
                <div class="form-text small">When this offer becomes usable. Leave blank for immediate validity.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold"><i class="fas fa-hourglass-end text-gold me-1"></i> Valid Until (Expiry Date &amp; Time)</label>
                <?php 
                $validUntilVal = '';
                if (!empty($coupon['valid_until'])) {
                    $validUntilVal = date('Y-m-d\TH:i', strtotime($coupon['valid_until']));
                }
                ?>
                <input type="datetime-local" name="valid_until" class="form-control" value="<?= $validUntilVal ?>">
                <div class="form-text small">When this coupon expires. Leave blank for lifetime validity.</div>
            </div>

            <!-- 5. Description & Active -->
            <div class="col-12">
                <label class="form-label fw-bold">Campaign Description / Internal Notes</label>
                <textarea name="description" rows="2" class="form-control" placeholder="e.g. Special 40% OFF Festive Promotion"><?= e($coupon['description'] ?? '') ?></textarea>
            </div>

            <div class="col-12">
                <div class="form-check form-switch fs-5">
                    <input class="form-check-input" type="checkbox" name="is_active" id="isActiveSwitch" value="1" <?= (($coupon['status'] ?? '') === 'active') ? 'checked' : '' ?>>
                    <label class="form-check-label fs-6 fw-bold text-forest" for="isActiveSwitch">Promo Code is Active</label>
                </div>
            </div>
        </div>

        <div class="mt-5 border-top pt-4 d-flex justify-content-between align-items-center">
            <div>
                <button type="submit" class="btn btn-admin-gold py-3 px-5 fs-6 shadow-sm">
                    <i class="fas fa-save me-2"></i> UPDATE PROMO CODE
                </button>
                <a href="<?= BASE_URL ?>admin/coupons" class="btn btn-outline-secondary py-3 px-4 ms-2">Cancel</a>
            </div>
            <a href="<?= BASE_URL ?>admin/coupons/delete.php?id=<?= $coupon['id'] ?>&csrf_token=<?= getCsrfToken() ?>" class="btn btn-outline-danger py-2 px-3 btn-confirm-delete" data-confirm-message="Are you sure you want to delete promo code '<?= e($coupon['code']) ?>'?">
                <i class="fas fa-trash-alt me-1"></i> Delete Code
            </a>
        </div>
    </form>
</div>

<script>
function updateDiscountLabels() {
    const sel = document.getElementById('discountTypeSelect');
    const label = document.getElementById('discountValueLabel');
    const addon = document.getElementById('discountValueAddon');
    const hint = document.getElementById('discountValueHint');
    const maxWrap = document.getElementById('maxDiscountWrap');

    if (sel.value === 'fixed') {
        label.innerHTML = 'Discount Cash Amount (₹) <span class="text-danger">*</span>';
        addon.innerHTML = '₹';
        hint.textContent = 'Enter fixed flat amount to deduct from total (e.g. 500 for ₹500 FLAT OFF).';
        maxWrap.style.display = 'none';
    } else {
        label.innerHTML = 'Discount Rate (%) <span class="text-danger">*</span>';
        addon.innerHTML = '<i class="fas fa-percent"></i>';
        hint.textContent = 'Enter discount percentage (e.g. 40 for 40% OFF).';
        maxWrap.style.display = 'block';
    }
}
document.addEventListener('DOMContentLoaded', updateDiscountLabels);
</script>

<?php require_once __DIR__ . '/../../includes/admin-footer.php'; ?>

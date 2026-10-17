<?php
/**
 * GLAIMAGAIN - Admin Create New Promo Code
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();
$errors = [];

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

    // Check code uniqueness
    $chk = $pdo->prepare("SELECT id FROM coupons WHERE code = ? LIMIT 1");
    $chk->execute([$code]);
    if ($chk->fetch()) {
        $errors[] = "Promo code '{$code}' already exists. Please choose a unique code.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO coupons 
            (code, description, discount_type, discount_value, min_order_amount, max_discount_amount, usage_limit, used_count, valid_from, valid_until, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $code, 
            $description, 
            $discountType, 
            $discountValue, 
            $minOrderAmount, 
            $maxDiscountAmount, 
            $usageLimit, 
            $validFrom, 
            $validUntil, 
            $status
        ]);

        setFlashMessage('success', "Promo code '{$code}' created successfully!");
        header('Location: ' . BASE_URL . 'admin/coupons');
        exit;
    }
}

$adminHeaderHeading = 'Create New Promo Code';
$adminTitle = 'Create Promo Code | GLAIMAGAIN Admin';
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="mb-4">
    <a href="<?= BASE_URL ?>admin/coupons" class="btn btn-outline-dark btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back to Promo Codes
    </a>
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
    <div class="d-flex align-items-center gap-3 mb-4 border-bottom pb-3">
        <div class="stat-icon-circle bg-mint text-forest fs-4">
            <i class="fas fa-tag"></i>
        </div>
        <div>
            <h5 class="fw-bold text-emerald mb-1">Create Promo Code / Discount Offer</h5>
            <p class="text-muted small mb-0">Define discount percentages, customer order thresholds, and time-limited validity windows.</p>
        </div>
    </div>

    <form method="POST" action="<?= BASE_URL ?>admin/coupons/add.php" class="form-luxury">
        <?= csrfField() ?>

        <div class="row g-4">
            <!-- 1. Code & Type -->
            <div class="col-md-6">
                <label class="form-label fw-bold">Promo Code <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-ticket-alt text-gold"></i></span>
                    <input type="text" name="code" class="form-control text-uppercase fw-bold letter-spacing-1" placeholder="e.g. GLAIM40, FESTIVE25" value="<?= e($_POST['code'] ?? '') ?>" required>
                </div>
                <div class="form-text small">Customers enter this code at checkout or claim it from the homepage banner.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Discount Type <span class="text-danger">*</span></label>
                <select name="discount_type" id="discountTypeSelect" class="form-select" onchange="updateDiscountLabels()">
                    <option value="percentage" <?= (($_POST['discount_type'] ?? '') === 'percentage') ? 'selected' : '' ?>>Percentage Discount (% OFF)</option>
                    <option value="fixed" <?= (($_POST['discount_type'] ?? '') === 'fixed') ? 'selected' : '' ?>>Fixed Amount Discount (₹ FLAT OFF)</option>
                </select>
                <div class="form-text small">Select whether the discount is a % of the subtotal or a fixed cash amount.</div>
            </div>

            <!-- 2. Value & Max Discount -->
            <div class="col-md-6">
                <label class="form-label fw-bold" id="discountValueLabel">Discount Rate (%) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text" id="discountValueAddon"><i class="fas fa-percent"></i></span>
                    <input type="number" step="0.01" min="0.01" name="discount_value" class="form-control" placeholder="e.g. 40" value="<?= e($_POST['discount_value'] ?? '') ?>" required>
                </div>
                <div class="form-text small" id="discountValueHint">Enter discount percentage (e.g. 40 for 40% OFF).</div>
            </div>

            <div class="col-md-6" id="maxDiscountWrap">
                <label class="form-label fw-bold">Maximum Discount Cap (₹)</label>
                <div class="input-group">
                    <span class="input-group-text">₹</span>
                    <input type="number" step="0.01" min="0" name="max_discount_amount" class="form-control" placeholder="e.g. 2000 (Optional)" value="<?= e($_POST['max_discount_amount'] ?? '') ?>">
                </div>
                <div class="form-text small">Maximum savings limit for percentage discounts. Leave blank for unlimited.</div>
            </div>

            <!-- 3. Minimum Order Spend & Usage Limit -->
            <div class="col-md-6">
                <label class="form-label fw-bold">Minimum Order Spend (₹)</label>
                <div class="input-group">
                    <span class="input-group-text">₹</span>
                    <input type="number" step="0.01" min="0" name="min_order_amount" class="form-control" placeholder="0" value="<?= e($_POST['min_order_amount'] ?? '0') ?>">
                </div>
                <div class="form-text small">Minimum cart subtotal required to activate this code (0 for no minimum).</div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Overall Redemptions Limit</label>
                <input type="number" min="1" name="usage_limit" class="form-control" placeholder="e.g. 500 (Optional)" value="<?= e($_POST['usage_limit'] ?? '') ?>">
                <div class="form-text small">Total times this promo code can be redeemed across all patrons. Leave empty for unlimited.</div>
            </div>

            <!-- 4. Validity Dates -->
            <div class="col-md-6">
                <label class="form-label fw-bold"><i class="fas fa-calendar-alt text-gold me-1"></i> Valid From (Start Date &amp; Time)</label>
                <input type="datetime-local" name="valid_from" class="form-control" value="<?= e($_POST['valid_from'] ?? '') ?>">
                <div class="form-text small">When this offer becomes usable. Leave blank for immediate validity.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold"><i class="fas fa-hourglass-end text-gold me-1"></i> Valid Until (Expiry Date &amp; Time)</label>
                <input type="datetime-local" name="valid_until" class="form-control" value="<?= e($_POST['valid_until'] ?? '') ?>">
                <div class="form-text small">When this coupon expires. Leave blank for lifetime validity.</div>
            </div>

            <!-- 5. Description & Active -->
            <div class="col-12">
                <label class="form-label fw-bold">Campaign Description / Internal Notes</label>
                <textarea name="description" rows="2" class="form-control" placeholder="e.g. Special 40% OFF Festive Promotion for homepage exclusive offer banner"><?= e($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="col-12">
                <div class="form-check form-switch fs-5">
                    <input class="form-check-input" type="checkbox" name="is_active" id="isActiveSwitch" value="1" <?= (!isset($_POST['code']) || !empty($_POST['is_active'])) ? 'checked' : '' ?>>
                    <label class="form-check-label fs-6 fw-bold text-forest" for="isActiveSwitch">Activate Promo Code Immediately</label>
                </div>
            </div>
        </div>

        <div class="mt-5 border-top pt-4">
            <button type="submit" class="btn btn-admin-gold py-3 px-5 fs-6 shadow-sm">
                <i class="fas fa-check-circle me-2"></i> SAVE &amp; PUBLISH PROMO CODE
            </button>
            <a href="<?= BASE_URL ?>admin/coupons" class="btn btn-outline-secondary py-3 px-4 ms-2">Cancel</a>
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

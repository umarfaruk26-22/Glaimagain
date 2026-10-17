<?php
/**
 * GLAIMAGAIN - Admin Promo Codes & Offers Management
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();

// Fetch coupons
$stmt = $pdo->query("SELECT * FROM coupons ORDER BY id DESC");
$coupons = $stmt->fetchAll();

// Statistics
$totalCoupons = count($coupons);
$activeCoupons = 0;
$totalRedeemed = 0;
$percentCoupons = 0;

foreach ($coupons as $c) {
    if (($c['status'] ?? '') === 'active') $activeCoupons++;
    $totalRedeemed += (int)$c['used_count'];
    if ($c['discount_type'] === 'percentage') $percentCoupons++;
}

$adminHeaderHeading = 'Promo Codes & Exclusive Offers';
$adminTitle = 'Coupons & Offers | GLAIMAGAIN Admin';
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<!-- Metric Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small text-uppercase letter-spacing-1">Active Offers</span>
                    <h3 class="fw-bold text-forest mb-0 mt-1"><?= $activeCoupons ?> / <?= $totalCoupons ?></h3>
                </div>
                <div class="stat-icon-circle bg-mint text-forest">
                    <i class="fas fa-ticket-alt"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small text-uppercase letter-spacing-1">Times Redeemed</span>
                    <h3 class="fw-bold text-forest mb-0 mt-1"><?= number_format($totalRedeemed) ?></h3>
                </div>
                <div class="stat-icon-circle bg-mint text-forest">
                    <i class="fas fa-shopping-bag"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small text-uppercase letter-spacing-1">% Off Campaigns</span>
                    <h3 class="fw-bold text-forest mb-0 mt-1"><?= $percentCoupons ?></h3>
                </div>
                <div class="stat-icon-circle bg-mint text-forest">
                    <i class="fas fa-percentage"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small text-uppercase letter-spacing-1">Homepage Offer</span>
                    <h3 class="fw-bold text-gold mb-0 mt-1 fs-5">Banner Linked</h3>
                </div>
                <div class="stat-icon-circle bg-mint text-forest">
                    <i class="fas fa-star"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold text-emerald mb-1">Promo Codes Matrix (<?= $totalCoupons ?>)</h5>
        <p class="text-muted small mb-0">Create, edit discount percentages, set expiry dates, and manage promo code limits.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>admin/homepage" class="btn btn-outline-dark btn-sm">
            <i class="fas fa-image me-1"></i> Configure Homepage Banner
        </a>
        <a href="<?= BASE_URL ?>admin/coupons/add.php" class="btn btn-admin-gold">
            <i class="fas fa-plus me-1"></i> CREATE PROMO CODE
        </a>
    </div>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>Promo Code</th>
                    <th>Discount Rate</th>
                    <th>Min. Spend</th>
                    <th>Validity Window</th>
                    <th>Usage / Redemptions</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($coupons)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-ticket-alt fs-1 text-muted mb-2 d-block"></i>
                            No promo codes created yet. Click "CREATE PROMO CODE" above to begin.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($coupons as $cp): 
                        $isExpired = false;
                        if (!empty($cp['valid_until']) && strtotime($cp['valid_until']) < time()) {
                            $isExpired = true;
                        }
                    ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-forest text-gold px-3 py-2 rounded-2 fs-6 font-monospace letter-spacing-1 shadow-sm">
                                        <?= e($cp['code']) ?>
                                    </span>
                                    <button type="button" class="btn btn-sm btn-link text-muted p-0" title="Copy code" onclick="navigator.clipboard.writeText('<?= e($cp['code']) ?>'); this.innerHTML='<i class=\'fas fa-check text-success\'></i>'; setTimeout(() => this.innerHTML='<i class=\'fas fa-copy\'></i>', 1500);">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                <?php if (!empty($cp['description'])): ?>
                                    <div class="small text-muted mt-1 text-truncate" style="max-width: 250px;"><?= e($cp['description']) ?></div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($cp['discount_type'] === 'percentage'): ?>
                                    <span class="fw-bold text-forest fs-6"><?= (float)$cp['discount_value'] ?>% OFF</span>
                                    <?php if (!empty($cp['max_discount_amount']) && $cp['max_discount_amount'] > 0): ?>
                                        <div class="small text-muted">Up to <?= formatPrice($cp['max_discount_amount']) ?></div>
                                    <?php else: ?>
                                        <div class="small text-muted">Uncapped</div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="fw-bold text-forest fs-6"><?= formatPrice($cp['discount_value']) ?> FLAT</span>
                                    <div class="small text-muted">Fixed Discount</div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($cp['min_order_amount'] > 0): ?>
                                    <strong><?= formatPrice($cp['min_order_amount']) ?></strong>
                                <?php else: ?>
                                    <span class="text-muted small">No minimum</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if (empty($cp['valid_from']) && empty($cp['valid_until'])): ?>
                                    <span class="badge bg-light text-dark border">Lifetime / Always Valid</span>
                                <?php else: ?>
                                    <div class="small">
                                        <?php if (!empty($cp['valid_from'])): ?>
                                            <div>From: <strong><?= date('d M Y, h:i A', strtotime($cp['valid_from'])) ?></strong></div>
                                        <?php endif; ?>
                                        <?php if (!empty($cp['valid_until'])): ?>
                                            <div>Until: <strong><?= date('d M Y, h:i A', strtotime($cp['valid_until'])) ?></strong></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isExpired): ?>
                                        <span class="badge bg-danger text-white mt-1">EXPIRED</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div><strong><?= (int)$cp['used_count'] ?></strong> redeemed</div>
                                <div class="small text-muted">
                                    Limit: <?= !empty($cp['usage_limit']) ? (int)$cp['usage_limit'] . ' times' : 'Unlimited' ?>
                                </div>
                            </td>

                            <td>
                                <?php if ($isExpired): ?>
                                    <span class="badge bg-secondary">EXPIRED</span>
                                <?php elseif (($cp['status'] ?? '') === 'active'): ?>
                                    <span class="badge bg-success">ACTIVE</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">INACTIVE</span>
                                <?php endif; ?>
                            </td>

                            <td class="text-end">
                                <div class="btn-group">
                                    <a href="<?= BASE_URL ?>admin/coupons/edit.php?id=<?= $cp['id'] ?>" class="btn btn-outline-dark btn-sm py-1 px-2" title="Edit Promo Code">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>admin/coupons/delete.php?id=<?= $cp['id'] ?>&csrf_token=<?= getCsrfToken() ?>" class="btn btn-outline-danger btn-sm py-1 px-2 btn-confirm-delete" data-confirm-message="Are you sure you want to delete promo code '<?= e($cp['code']) ?>'?" title="Delete Promo Code">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/admin-footer.php'; ?>

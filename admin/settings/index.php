<?php
/**
 * GLAIMAGAIN - Admin Website Settings Manager
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $settings = [
        'site_name' => trim($_POST['site_name'] ?? 'GLAIMAGAIN'),
        'site_tagline' => trim($_POST['site_tagline'] ?? 'FASHION BEYOND TODAY'),
        'contact_email' => trim($_POST['contact_email'] ?? 'concierge@glaimagain.com'),
        'contact_phone' => trim($_POST['contact_phone'] ?? '+91 98765 43210'),
        'contact_whatsapp' => trim($_POST['contact_whatsapp'] ?? '+91 98765 43210'),
        'store_address' => trim($_POST['store_address'] ?? 'GLAIMAGAIN Flagship House, Level 4, Luxury Pavilion, Mumbai, Maharashtra 400050, India'),
        'instagram_url' => trim($_POST['instagram_url'] ?? ''),
        'facebook_url' => trim($_POST['facebook_url'] ?? ''),
        'google_business_url' => trim($_POST['google_business_url'] ?? ''),
        'working_hours' => trim($_POST['working_hours'] ?? 'Mon - Sat: 10:00 AM - 08:00 PM IST'),
        'shipping_fee' => number_format(max(0, (float)($_POST['shipping_fee'] ?? 99)), 2, '.', ''),
        'free_shipping_threshold' => number_format(max(0, (float)($_POST['free_shipping_threshold'] ?? 1999)), 2, '.', ''),
        'meta_description' => trim($_POST['meta_description'] ?? ''),
        'meta_keywords' => trim($_POST['meta_keywords'] ?? '')
    ];

    $stmt = $pdo->prepare("
        INSERT INTO settings (setting_key, setting_value, setting_group)
        VALUES (?, ?, 'general')
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
    ");

    foreach ($settings as $k => $v) {
        $stmt->execute([$k, $v]);
    }

    setFlashMessage('success', 'Store configuration & branding parameters updated successfully.');
    header('Location: ' . BASE_URL . 'admin/settings/index.php');
    exit;
}

$adminHeaderHeading = 'Store & Brand Settings';
$adminTitle = 'Store Settings | GLAIMAGAIN Admin';
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold text-emerald mb-1">Store Parameters &amp; Brand Configuration</h5>
        <p class="text-muted small mb-0">Manage global concierge details, shipping rates, and social channels without modifying code.</p>
    </div>
</div>

<form method="POST" action="<?= BASE_URL ?>admin/settings/index.php" class="form-luxury">
    <?= csrfField() ?>

    <div class="row g-4">
        <!-- 1. Brand Identity -->
        <div class="col-lg-6">
            <div class="admin-card p-4 p-md-5 h-100">
                <h5 class="fw-bold text-emerald text-uppercase letter-spacing-1 mb-3"><i class="fas fa-crown text-gold me-2"></i>Brand Identity</h5>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Brand Name <span class="text-gold">*</span></label>
                        <input type="text" name="site_name" class="form-control" value="<?= e(getSetting('site_name', 'GLAIMAGAIN')) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Official Tagline <span class="text-gold">*</span></label>
                        <input type="text" name="site_tagline" class="form-control" value="<?= e(getSetting('site_tagline', 'FASHION BEYOND TODAY')) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">SEO Meta Description</label>
                        <textarea name="meta_description" rows="3" class="form-control"><?= e(getSetting('meta_description')) ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">SEO Keywords</label>
                        <input type="text" name="meta_keywords" class="form-control" value="<?= e(getSetting('meta_keywords')) ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Logistics & Shipping Rules -->
        <div class="col-lg-6">
            <div class="admin-card p-4 p-md-5 h-100">
                <h5 class="fw-bold text-emerald text-uppercase letter-spacing-1 mb-3"><i class="fas fa-truck text-gold me-2"></i>Logistics &amp; Shipping Rates</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Standard Shipping Fee (₹) <span class="text-gold">*</span></label>
                        <input type="number" step="0.01" name="shipping_fee" class="form-control" value="<?= e(getSetting('shipping_fee', '99.00')) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Free Shipping Threshold (₹) <span class="text-gold">*</span></label>
                        <input type="number" step="0.01" name="free_shipping_threshold" class="form-control" value="<?= e(getSetting('free_shipping_threshold', '1999.00')) ?>" required>
                    </div>
                    <div class="col-12">
                        <div class="p-3 bg-offwhite border rounded small text-muted">
                            <i class="fas fa-info-circle text-gold me-1"></i> Orders equal to or exceeding the Free Shipping Threshold automatically qualify for complimentary express dispatch.
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Working / Concierge Hours</label>
                        <input type="text" name="working_hours" class="form-control" value="<?= e(getSetting('working_hours', 'Mon - Sat: 10:00 AM - 08:00 PM IST')) ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Contact Channels & Flagship Address -->
        <div class="col-lg-12">
            <div class="admin-card p-4 p-md-5">
                <h5 class="fw-bold text-emerald text-uppercase letter-spacing-1 mb-3"><i class="fas fa-address-book text-gold me-2"></i>Concierge &amp; Flagship Contact Particulars</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Official Email <span class="text-gold">*</span></label>
                        <input type="email" name="contact_email" class="form-control" value="<?= e(getSetting('contact_email', 'concierge@glaimagain.com')) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phone Hotline <span class="text-gold">*</span></label>
                        <input type="text" name="contact_phone" class="form-control" value="<?= e(getSetting('contact_phone', '+91 98765 43210')) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">WhatsApp Concierge Number <span class="text-gold">*</span></label>
                        <input type="text" name="contact_whatsapp" class="form-control" value="<?= e(getSetting('contact_whatsapp', '+91 98765 43210')) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Flagship Store &amp; Headquarters Address</label>
                        <textarea name="store_address" rows="2" class="form-control" required><?= e(getSetting('store_address')) ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Instagram URL</label>
                        <input type="url" name="instagram_url" class="form-control" value="<?= e(getSetting('instagram_url', 'https://instagram.com/glaimagain')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Facebook URL</label>
                        <input type="url" name="facebook_url" class="form-control" value="<?= e(getSetting('facebook_url', 'https://facebook.com/glaimagain')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Google Business Map URL</label>
                        <input type="url" name="google_business_url" class="form-control" value="<?= e(getSetting('google_business_url', '')) ?>">
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-admin-gold py-3 px-5">
                        <i class="fas fa-save me-2"></i> SAVE STORE CONFIGURATION
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/admin-footer.php'; ?>

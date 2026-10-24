<?php
/**
 * GLAIMAGAIN - Admin Dynamic Homepage CMS Manager
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    // 1. Hero Section
    $heroTitle = trim($_POST['hero_title'] ?? '');
    $heroSubtitle = trim($_POST['hero_subtitle'] ?? '');
    $heroContent = trim($_POST['hero_content'] ?? '');
    $heroBtnText = trim($_POST['hero_btn_text'] ?? '');
    $heroBtnLink = trim($_POST['hero_btn_link'] ?? '');

    $updHero = $pdo->prepare("
        UPDATE homepage_sections 
        SET title = ?, subtitle = ?, content = ?, button_text = ?, button_link = ?, updated_at = NOW()
        WHERE section_key = 'hero'
    ");
    $updHero->execute([$heroTitle, $heroSubtitle, $heroContent, $heroBtnText, $heroBtnLink]);

    // Handle Hero Banner Upload if provided
    if (!empty($_FILES['hero_image']['name'])) {
        $uploadRes = handleImageUpload($_FILES['hero_image'], 'banners');
        if ($uploadRes['success']) {
            $pdo->prepare("UPDATE homepage_sections SET image_path = ? WHERE section_key = 'hero'")->execute([$uploadRes['path']]);
        }
    }

    // 2. Brand Story Section
    $storyTitle = trim($_POST['story_title'] ?? '');
    $storySubtitle = trim($_POST['story_subtitle'] ?? '');
    $storyContent = trim($_POST['story_content'] ?? '');
    $storyBtnText = trim($_POST['story_btn_text'] ?? '');
    $storyBtnLink = trim($_POST['story_btn_link'] ?? '');

    $updStory = $pdo->prepare("
        UPDATE homepage_sections 
        SET title = ?, subtitle = ?, content = ?, button_text = ?, button_link = ?, updated_at = NOW()
        WHERE section_key = 'brand_story'
    ");
    $updStory->execute([$storyTitle, $storySubtitle, $storyContent, $storyBtnText, $storyBtnLink]);

    // 3. Promotional Banner
    $promoTitle = trim($_POST['promo_title'] ?? '');
    $promoSubtitle = trim($_POST['promo_subtitle'] ?? '');
    $promoContent = trim($_POST['promo_content'] ?? '');
    $promoBtnText = trim($_POST['promo_btn_text'] ?? '');
    $promoBtnLink = trim($_POST['promo_btn_link'] ?? '');

    $updPromo = $pdo->prepare("
        UPDATE homepage_sections 
        SET title = ?, subtitle = ?, content = ?, button_text = ?, button_link = ?, updated_at = NOW()
        WHERE section_key = 'promo_banner'
    ");
    $updPromo->execute([$promoTitle, $promoSubtitle, $promoContent, $promoBtnText, $promoBtnLink]);

    // 4. Exclusive Offer Section (Mint Split Banner with Promo Claim & Countdown)
    $exTitle = trim($_POST['exclusive_title'] ?? 'Exclusive Offer');
    $exBadge = trim($_POST['exclusive_badge'] ?? 'TIME-LIMITED PROMOTION');
    $exContent = trim($_POST['exclusive_content'] ?? '');
    $exBtnText = trim($_POST['exclusive_btn_text'] ?? 'CLAIM EXCLUSIVE OFFER');
    $exBtnLink = trim($_POST['exclusive_btn_link'] ?? 'shop.php');
    $exPromo = strtoupper(trim($_POST['exclusive_promo_code'] ?? 'GLAIM40'));
    $exExpiry = !empty($_POST['exclusive_expiry']) ? date('Y-m-d H:i:s', strtotime($_POST['exclusive_expiry'])) : null;
    $exActive = isset($_POST['exclusive_is_active']) ? 1 : 0;

    $chkEx = $pdo->prepare("SELECT id FROM homepage_sections WHERE section_key = 'exclusive_offer' LIMIT 1");
    $chkEx->execute();
    if ($chkEx->fetch()) {
        $updEx = $pdo->prepare("
            UPDATE homepage_sections 
            SET title = ?, badge_text = ?, subtitle = ?, content = ?, button_text = ?, button_link = ?, promo_code = ?, expiry_date = ?, is_active = ?, updated_at = NOW()
            WHERE section_key = 'exclusive_offer'
        ");
        $updEx->execute([$exTitle, $exBadge, $exBadge, $exContent, $exBtnText, $exBtnLink, $exPromo, $exExpiry, $exActive]);
    } else {
        $insEx = $pdo->prepare("
            INSERT INTO homepage_sections 
            (section_key, title, badge_text, subtitle, content, button_text, button_link, promo_code, expiry_date, is_active, updated_at)
            VALUES ('exclusive_offer', ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $insEx->execute([$exTitle, $exBadge, $exBadge, $exContent, $exBtnText, $exBtnLink, $exPromo, $exExpiry, $exActive]);
    }

    if (!empty($_FILES['exclusive_image']['name'])) {
        $uploadRes = handleImageUpload($_FILES['exclusive_image'], 'banners');
        if ($uploadRes['success']) {
            $pdo->prepare("UPDATE homepage_sections SET image_path = ? WHERE section_key = 'exclusive_offer'")->execute([$uploadRes['path']]);
        }
    }

    setFlashMessage('success', 'Homepage content & visual banners updated successfully.');
    header('Location: ' . BASE_URL . 'admin/homepage/index.php');
    exit;
}

// Fetch sections
$sectionsStmt = $pdo->query("SELECT * FROM homepage_sections");
$sections = [];
while ($row = $sectionsStmt->fetch()) {
    $sections[$row['section_key']] = $row;
}

$hero = $sections['hero'] ?? [];
$story = $sections['brand_story'] ?? [];
$promo = $sections['promo_banner'] ?? [];
$exclusive = $sections['exclusive_offer'] ?? [
    'title' => 'Exclusive Offer',
    'badge_text' => 'TIME-LIMITED PROMOTION',
    'content' => 'Discover the key to a stylish upgrade with our exclusive deal: Enjoy up to 40% OFF our newest luxury drops and bespoke tailoring pieces.',
    'button_text' => 'CLAIM EXCLUSIVE OFFER',
    'button_link' => 'shop.php',
    'promo_code' => 'GLAIM40',
    'expiry_date' => date('Y-m-d H:i:s', strtotime('+5 days')),
    'is_active' => 1
];

// Fetch active coupons for dropdown
$availableCoupons = $pdo->query("SELECT * FROM coupons ORDER BY (status = 'active') DESC, id DESC")->fetchAll();

$adminHeaderHeading = 'Homepage CMS & Visual Banners';
$adminTitle = 'Homepage CMS | GLAIMAGAIN Admin';
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold text-emerald mb-1">Homepage Visual Architecture &amp; Copy CMS</h5>
        <p class="text-muted small mb-0">Customize hero banners, exclusive offer promotion banner, collection narratives, and promotional announcements.</p>
    </div>
    <a href="<?= BASE_URL ?>" target="_blank" class="btn btn-outline-dark btn-sm">
        <i class="fas fa-external-link-alt me-1"></i> Preview Live Homepage
    </a>
</div>

<!-- Top Hero Carousel Slides Callout -->
<div class="admin-card p-4 p-md-5 mb-4 border border-2" style="border-color: #013C26 !important; background: linear-gradient(135deg, #013C26 0%, #081F15 100%); color: #FFFFFF; border-radius: 12px; box-shadow: 0 10px 30px rgba(1, 60, 38, 0.15);">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <span class="badge bg-gold text-dark px-3 py-1 rounded-pill small mb-2 fw-bold"><i class="fas fa-sparkles me-1"></i> FLAGSHIP HERO CAROUSEL</span>
            <h4 class="fw-bold text-white mb-2"><i class="fas fa-images text-gold me-2"></i>Multi-Slide Hero Carousel &amp; Mobile Banners</h4>
            <p class="text-white-50 mb-0 small" style="max-width: 650px;">
                The homepage hero section is powered by an interactive multi-slide Carousel with smooth touch swiping on mobile. Add, edit, or remove photos, headlines, custom mobile crops, and button actions.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>admin/carousel/index.php" class="btn btn-warning fw-bold text-dark px-4 py-2 shadow-sm">
                <i class="fas fa-images me-2"></i> Open Carousel Manager
            </a>
            <a href="<?= BASE_URL ?>admin/carousel/add.php" class="btn btn-outline-light px-3 py-2">
                <i class="fas fa-plus me-1"></i> Add Slide
            </a>
        </div>
    </div>
</div>

<form method="POST" action="<?= BASE_URL ?>admin/homepage/index.php" enctype="multipart/form-data" class="form-luxury">
    <?= csrfField() ?>

    <!-- 1. Fallback Hero Section Copy -->
    <div class="admin-card p-4 p-md-5 mb-4">
        <h5 class="fw-bold text-emerald text-uppercase letter-spacing-1 mb-3"><i class="fas fa-image text-gold me-2"></i>1. Brand Tagline &amp; Meta Headline</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Headline Title</label>
                <input type="text" name="hero_title" class="form-control" value="<?= e($hero['title'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Subtitle / Season Tagline</label>
                <input type="text" name="hero_subtitle" class="form-control" value="<?= e($hero['subtitle'] ?? '') ?>" required>
            </div>
            <div class="col-12">
                <label class="form-label">Hero Introduction Text</label>
                <textarea name="hero_content" rows="2" class="form-control"><?= e($hero['content'] ?? '') ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">CTA Button Text</label>
                <input type="text" name="hero_btn_text" class="form-control" value="<?= e($hero['button_text'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">CTA Button Link</label>
                <input type="text" name="hero_btn_link" class="form-control" value="<?= e($hero['button_link'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Upload New Hero Background Image</label>
                <input type="file" name="hero_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                <div class="form-text small">Recommended: 1920x900 px.</div>
            </div>
        </div>
    </div>

    <!-- 2. Brand Story CMS -->
    <div class="admin-card p-4 p-md-5 mb-4">
        <h5 class="fw-bold text-emerald text-uppercase letter-spacing-1 mb-3"><i class="fas fa-crown text-gold me-2"></i>2. Brand Philosophy &amp; Atelier Story</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Story Headline</label>
                <input type="text" name="story_title" class="form-control" value="<?= e($story['title'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Story Subtitle</label>
                <input type="text" name="story_subtitle" class="form-control" value="<?= e($story['subtitle'] ?? '') ?>" required>
            </div>
            <div class="col-12">
                <label class="form-label">Story Narrative Content</label>
                <textarea name="story_content" rows="4" class="form-control"><?= e($story['content'] ?? '') ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Button Text</label>
                <input type="text" name="story_btn_text" class="form-control" value="<?= e($story['button_text'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Button Link</label>
                <input type="text" name="story_btn_link" class="form-control" value="<?= e($story['button_link'] ?? '') ?>">
            </div>
        </div>
    </div>

    <!-- 3. Promotional Banner CMS -->
    <div class="admin-card p-4 p-md-5 mb-4">
        <h5 class="fw-bold text-emerald text-uppercase letter-spacing-1 mb-3"><i class="fas fa-gem text-gold me-2"></i>3. Promotional Capsule Banner</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Banner Title</label>
                <input type="text" name="promo_title" class="form-control" value="<?= e($promo['title'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Banner Subtitle</label>
                <input type="text" name="promo_subtitle" class="form-control" value="<?= e($promo['subtitle'] ?? '') ?>" required>
            </div>
            <div class="col-12">
                <label class="form-label">Promotional Announcement Copy</label>
                <textarea name="promo_content" rows="2" class="form-control"><?= e($promo['content'] ?? '') ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Button Text</label>
                <input type="text" name="promo_btn_text" class="form-control" value="<?= e($promo['button_text'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Button Link</label>
                <input type="text" name="promo_btn_link" class="form-control" value="<?= e($promo['button_link'] ?? '') ?>">
            </div>
        </div>
    </div>

    <!-- 4. Exclusive Offer Banner & Countdown CMS -->
    <div class="admin-card p-4 p-md-5 mb-4 border border-2" style="border-color: #013C26 !important; background: #fafdfb;">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <span class="badge bg-forest text-gold px-3 py-1 rounded-pill small mb-1"><i class="fas fa-star me-1"></i> FLAGSHIP SECTION</span>
                <h5 class="fw-bold text-emerald text-uppercase letter-spacing-1 mb-0"><i class="fas fa-percentage text-gold me-2"></i>4. Exclusive Offer Banner &amp; Countdown Timer (Mint Split)</h5>
            </div>
            <div class="form-check form-switch fs-5">
                <input class="form-check-input" type="checkbox" name="exclusive_is_active" id="exclusiveIsActive" value="1" <?= (!empty($exclusive['is_active'])) ? 'checked' : '' ?>>
                <label class="form-check-label fs-6 fw-bold text-forest" for="exclusiveIsActive">Display on Homepage</label>
            </div>
        </div>
        <p class="text-muted small mb-4">Edit the headline, discount badge, promo code that gets automatically claimed when users click "CLAIM EXCLUSIVE OFFER", and the live ticking countdown expiry date.</p>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">Headline Title</label>
                <input type="text" name="exclusive_title" class="form-control" value="<?= e($exclusive['title'] ?? 'Exclusive Offer') ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Badge Text (Above Title)</label>
                <input type="text" name="exclusive_badge" class="form-control" value="<?= e($exclusive['badge_text'] ?? 'TIME-LIMITED PROMOTION') ?>" placeholder="e.g. TIME-LIMITED PROMOTION or FESTIVE COUTURE">
            </div>

            <div class="col-12">
                <label class="form-label fw-bold">Offer Description / Subtext</label>
                <textarea name="exclusive_content" rows="3" class="form-control" placeholder="Describe the exclusive offer..."><?= e($exclusive['content'] ?? '') ?></textarea>
                <div class="form-text small">Tip: Mention the discount percentage (e.g. 40% OFF) or special occasion details here.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold text-forest"><i class="fas fa-ticket-alt text-gold me-1"></i> Target Promo Code (Claimed on Click)</label>
                <div class="input-group">
                    <input type="text" name="exclusive_promo_code" id="exclusivePromoInput" class="form-control text-uppercase fw-bold letter-spacing-1" value="<?= e($exclusive['promo_code'] ?? 'GLAIM40') ?>" placeholder="GLAIM40" required>
                    <?php if (!empty($availableCoupons)): ?>
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Select Active</button>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <?php foreach ($availableCoupons as $cp): ?>
                                <li>
                                    <a class="dropdown-item d-flex justify-content-between align-items-center" href="javascript:void(0)" onclick="document.getElementById('exclusivePromoInput').value='<?= e($cp['code']) ?>';">
                                        <strong><?= e($cp['code']) ?></strong>
                                        <span class="badge bg-mint text-forest ms-2">
                                            <?= $cp['discount_type'] === 'percentage' ? (float)$cp['discount_value'] . '% OFF' : '₹' . (float)$cp['discount_value'] . ' OFF' ?>
                                        </span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <div class="form-text small">
                    When a visitor clicks the claim button, this code will be automatically applied to their session.
                    <a href="<?= BASE_URL ?>admin/coupons" class="text-decoration-none ms-1 fw-bold text-forest"><i class="fas fa-external-link-alt"></i> Manage Promo Codes</a>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold"><i class="fas fa-clock text-gold me-1"></i> Offer Expiry Date &amp; Time (Countdown Timer)</label>
                <?php 
                $expiryVal = '';
                if (!empty($exclusive['expiry_date'])) {
                    $expiryVal = date('Y-m-d\TH:i', strtotime($exclusive['expiry_date']));
                }
                ?>
                <input type="datetime-local" name="exclusive_expiry" class="form-control" value="<?= $expiryVal ?>">
                <div class="form-text small">The homepage countdown timer (Days, Hours, Min, Sec) automatically counts down to this exact moment.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Button CTA Text</label>
                <input type="text" name="exclusive_btn_text" class="form-control" value="<?= e($exclusive['button_text'] ?? 'CLAIM EXCLUSIVE OFFER') ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Button Target Destination</label>
                <input type="text" name="exclusive_btn_link" class="form-control" value="<?= e($exclusive['button_link'] ?? 'shop.php') ?>" placeholder="shop.php">
                <div class="form-text small">Where the user is guided after claiming (e.g. shop.php or cart.php).</div>
            </div>

            <div class="col-md-12">
                <label class="form-label fw-bold">Banner Model Image (Left Column)</label>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <?php 
                    $currentExImg = !empty($exclusive['image_path']) ? BASE_URL . $exclusive['image_path'] : BASE_URL . 'assets/images/exclusive-model.jpg';
                    ?>
                    <img src="<?= $currentExImg ?>" alt="Current Exclusive Model" style="height: 100px; width: 100px; object-fit: cover; border-radius: 12px; border: 2px solid #B99036; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                    <div class="flex-grow-1">
                        <input type="file" name="exclusive_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                        <div class="form-text small">Upload high-resolution editorial model or dress showcase image. (Current: <?= e($exclusive['image_path'] ?? 'assets/images/exclusive-model.jpg') ?>)</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-5">
        <button type="submit" class="btn btn-admin-gold py-3 px-5 fs-6 shadow-sm">
            <i class="fas fa-save me-2"></i> SAVE ALL HOMEPAGE CMS CHANGES
        </button>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/admin-footer.php'; ?>

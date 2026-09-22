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

$adminHeaderHeading = 'Homepage CMS & Visual Banners';
$adminTitle = 'Homepage CMS | GLAIMAGAIN Admin';
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold text-emerald mb-1">Homepage Visual Architecture &amp; Copy CMS</h5>
        <p class="text-muted small mb-0">Customize hero banners, collection narratives, and promotional announcements.</p>
    </div>
    <a href="<?= BASE_URL ?>" target="_blank" class="btn btn-outline-dark btn-sm">
        <i class="fas fa-external-link-alt me-1"></i> Preview Live Homepage
    </a>
</div>

<form method="POST" action="<?= BASE_URL ?>admin/homepage/index.php" enctype="multipart/form-data" class="form-luxury">
    <?= csrfField() ?>

    <!-- 1. Hero Banner CMS -->
    <div class="admin-card p-4 p-md-5 mb-4">
        <h5 class="fw-bold text-emerald text-uppercase letter-spacing-1 mb-3"><i class="fas fa-image text-gold me-2"></i>1. Hero Banner &amp; Headline Section</h5>
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

    <div class="mb-5">
        <button type="submit" class="btn btn-admin-gold py-3 px-5 fs-6">
            <i class="fas fa-save me-2"></i> SAVE ALL HOMEPAGE CMS CHANGES
        </button>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/admin-footer.php'; ?>

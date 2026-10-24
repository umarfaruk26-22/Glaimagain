<?php
/**
 * GLAIMAGAIN - Add New Carousel Slide
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();

// Suggest next sort order
$nextSortOrder = (int)$pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM carousel_slides")->fetchColumn();

$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    requireCsrfToken();

    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $buttonText = trim($_POST['button_text'] ?? 'START SHOPPING');
    $buttonLink = trim($_POST['button_link'] ?? 'shop.php');
    $textPosition = in_array($_POST['text_position'] ?? '', ['left', 'center', 'right']) ? $_POST['text_position'] : 'left';
    $textColor = in_array($_POST['text_color'] ?? '', ['light', 'dark']) ? $_POST['text_color'] : 'light';
    $sortOrder = (int)($_POST['sort_order'] ?? $nextSortOrder);
    $status = isset($_POST['status']) ? 'active' : 'inactive';

    // 1. Process Desktop Banner Image (Required)
    $imagePath = '';
    if (empty($_FILES['image']['name'])) {
        $errors[] = 'A desktop banner image is required for the carousel slide.';
    } else {
        $uploadRes = handleImageUpload($_FILES['image'], 'carousel');
        if ($uploadRes['success']) {
            $imagePath = $uploadRes['path'];
        } else {
            $errors[] = 'Desktop Image Error: ' . $uploadRes['error'];
        }
    }

    // 2. Process Mobile Image (Optional)
    $mobileImagePath = null;
    if (!empty($_FILES['mobile_image']['name'])) {
        $uploadMob = handleImageUpload($_FILES['mobile_image'], 'carousel');
        if ($uploadMob['success']) {
            $mobileImagePath = $uploadMob['path'];
        } else {
            $errors[] = 'Mobile Image Error: ' . $uploadMob['error'];
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO carousel_slides 
            (title, subtitle, description, button_text, button_link, image_path, mobile_image_path, text_position, text_color, sort_order, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $title ?: null,
            $subtitle ?: null,
            $description ?: null,
            $buttonText ?: null,
            $buttonLink ?: null,
            $imagePath,
            $mobileImagePath,
            $textPosition,
            $textColor,
            $sortOrder,
            $status
        ]);

        setFlashMessage('success', 'New carousel slide published successfully.');
        header('Location: ' . BASE_URL . 'admin/carousel/index.php');
        exit;
    }
}

$adminHeaderHeading = 'Add New Carousel Slide';
$adminTitle = 'Add Slide | GLAIMAGAIN Admin';
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-9 col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold text-emerald mb-1">Create New Rotating Banner</h5>
                <p class="text-muted small mb-0">Upload high-resolution photography and configure promotional copy.</p>
            </div>
            <a href="<?= BASE_URL ?>admin/carousel/index.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back to Slides
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Please correct the following:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>admin/carousel/add.php" enctype="multipart/form-data" class="form-luxury">
            <?= csrfField() ?>

            <!-- Slide Imagery Card -->
            <div class="admin-card p-4 p-md-5 mb-4">
                <h5 class="fw-bold text-emerald text-uppercase letter-spacing-1 mb-4">
                    <i class="fas fa-image text-gold me-2"></i>1. Slide Visual Assets
                </h5>

                <div class="row g-4">
                    <!-- Desktop Banner Upload -->
                    <div class="col-md-7">
                        <label class="form-label fw-bold">
                            Desktop Banner Image <span class="text-danger">*</span>
                        </label>
                        <input type="file" name="image" id="desktopImageInput" class="form-control" accept="image/jpeg,image/png,image/webp" required>
                        <div class="form-text small mt-1">
                            <i class="fas fa-info-circle me-1"></i> Recommended: <strong>1920 &times; 800 px</strong> or 1600 &times; 750 px (JPG, PNG, WEBP, max 5MB).
                        </div>
                        <div class="mt-3">
                            <div class="border rounded bg-light p-2 text-center" style="min-height: 160px; display: flex; align-items: center; justify-content: center; overflow: hidden;" id="desktopPreviewContainer">
                                <span class="text-muted small" id="desktopPreviewPlaceholder">
                                    <i class="fas fa-cloud-upload-alt fa-2x d-block mb-1 opacity-50"></i>
                                    Desktop preview will appear here
                                </span>
                                <img src="" alt="Desktop Preview" id="desktopPreviewImg" class="img-fluid rounded d-none" style="max-height: 220px; object-fit: cover;">
                            </div>
                        </div>
                    </div>

                    <!-- Mobile Crop Upload (Optional) -->
                    <div class="col-md-5">
                        <label class="form-label fw-bold">
                            Custom Mobile Image <span class="badge bg-secondary-subtle text-secondary small">Optional</span>
                        </label>
                        <input type="file" name="mobile_image" id="mobileImageInput" class="form-control" accept="image/jpeg,image/png,image/webp">
                        <div class="form-text small mt-1">
                            Recommended: <strong>800 &times; 1000 px</strong>. If omitted, the desktop image automatically scales for mobile screens.
                        </div>
                        <div class="mt-3">
                            <div class="border rounded bg-light p-2 text-center" style="min-height: 160px; display: flex; align-items: center; justify-content: center; overflow: hidden;" id="mobilePreviewContainer">
                                <span class="text-muted small" id="mobilePreviewPlaceholder">
                                    <i class="fas fa-mobile-alt fa-2x d-block mb-1 opacity-50"></i>
                                    Mobile preview will appear here
                                </span>
                                <img src="" alt="Mobile Preview" id="mobilePreviewImg" class="img-fluid rounded d-none" style="max-height: 220px; object-fit: cover;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slide Content Card -->
            <div class="admin-card p-4 p-md-5 mb-4">
                <h5 class="fw-bold text-emerald text-uppercase letter-spacing-1 mb-4">
                    <i class="fas fa-font text-gold me-2"></i>2. Promotional Headline &amp; Call to Action
                </h5>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Season Badge / Tagline</label>
                        <input type="text" name="subtitle" class="form-control" placeholder="e.g. AUTOGRAPH COLLECTION 2026" value="<?= e($_POST['subtitle'] ?? '') ?>">
                        <div class="form-text small">Appears as an elegant gold-accented badge above the title.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Headline Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Explore and Define Your Unique Style!" value="<?= e($_POST['title'] ?? '') ?>">
                        <div class="form-text small">Main large luxury headline. Leave blank for a pure graphic banner.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Description / Story Copy</label>
                        <textarea name="description" rows="3" class="form-control" placeholder="Browse our thoughtfully curated assortment of fashionable attire and bespoke silhouettes..."><?= e($_POST['description'] ?? '') ?></textarea>
                        <div class="form-text small">Short promotional paragraph displayed underneath the title.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Button CTA Label</label>
                        <input type="text" name="button_text" class="form-control" placeholder="e.g. START SHOPPING" value="<?= e($_POST['button_text'] ?? 'START SHOPPING') ?>">
                        <div class="form-text small">Label on the luxury pill button. Leave blank to disable button.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Button Destination URL</label>
                        <input type="text" name="button_link" class="form-control" placeholder="e.g. shop.php or shop.php?category=suits" value="<?= e($_POST['button_link'] ?? 'shop.php') ?>">
                        <div class="form-text small">URL or relative path where the button leads.</div>
                    </div>
                </div>
            </div>

            <!-- Layout & Sequence Settings -->
            <div class="admin-card p-4 p-md-5 mb-4">
                <h5 class="fw-bold text-emerald text-uppercase letter-spacing-1 mb-4">
                    <i class="fas fa-sliders-h text-gold me-2"></i>3. Display Sequence &amp; Visibility
                </h5>

                <div class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Text Position</label>
                        <select name="text_position" class="form-select">
                            <option value="left" selected>Left Aligned (Recommended)</option>
                            <option value="center">Centered</option>
                            <option value="right">Right Aligned</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Typography Theme</label>
                        <select name="text_color" class="form-select">
                            <option value="light" selected>Light / White &amp; Gold (for dark banners)</option>
                            <option value="dark">Dark / Forest Green (for bright banners)</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Sort Order Sequence</label>
                        <input type="number" name="sort_order" class="form-control" value="<?= e((string)$nextSortOrder) ?>" min="0" step="1">
                        <div class="form-text small">Lower numbers appear first in the carousel.</div>
                    </div>

                    <div class="col-12 mt-3 pt-3 border-top">
                        <div class="form-check form-switch fs-5">
                            <input class="form-check-input" type="checkbox" name="status" id="slideStatus" value="active" checked>
                            <label class="form-check-label fs-6 fw-bold text-forest" for="slideStatus">
                                Active on Live Homepage Carousel
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Live Real-Time Banner & Text Overlay Preview -->
            <div class="admin-card p-4 p-md-5 mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h5 class="fw-bold text-emerald text-uppercase letter-spacing-1 mb-0">
                        <i class="fas fa-eye text-gold me-2"></i>4. Live Slide Text Overlay Preview
                    </h5>
                    <span class="badge bg-mint text-forest px-3 py-1 rounded-pill small">
                        <i class="fas fa-sync-alt fa-spin me-1 text-gold"></i> Live Real-Time Preview
                    </span>
                </div>
                <p class="text-muted small mb-3">
                    As you type headlines, change alignment, or choose images above, watch this preview render exactly how it will appear to visitors on the live storefront:
                </p>

                <div class="position-relative rounded overflow-hidden shadow-lg" id="liveSlideBox" style="min-height: 290px; background: #012819 url('<?= BASE_URL ?>assets/images/hero-banner.jpg') center/cover no-repeat; transition: all 0.3s ease;">
                    <div id="liveOverlay" style="position: absolute; inset: 0; background: linear-gradient(90deg, rgba(1, 38, 24, 0.94) 0%, rgba(1, 38, 24, 0.6) 45%, rgba(1, 38, 24, 0.15) 100%); pointer-events: none;"></div>
                    <div class="position-relative h-100 p-4 p-md-5 d-flex flex-column justify-content-center" id="liveContentWrap" style="z-index: 2; min-height: 290px;">
                        <div id="liveBadgeWrap" class="mb-2">
                            <span class="badge" id="liveBadge" style="background: rgba(1, 38, 24, 0.85); color: #D4AF37; border: 1px solid rgba(212, 175, 55, 0.6); font-size: 11px; letter-spacing: 1.5px; padding: 6px 14px; text-transform: uppercase;">
                                <i class="fas fa-sparkles me-1 text-gold"></i> <span id="liveBadgeText">AUTOGRAPH COLLECTION 2026</span>
                            </span>
                        </div>
                        <h2 id="liveTitle" style="font-family: 'Playfair Display', Georgia, serif; color: #FFFFFF; font-size: clamp(1.4rem, 3.2vw, 2.1rem); line-height: 1.2; font-weight: 700; margin-bottom: 12px; max-width: 620px;">
                            Explore and Define Your Unique Style!
                        </h2>
                        <p id="liveDesc" style="color: rgba(255, 255, 255, 0.82); font-size: 0.92rem; line-height: 1.5; margin-bottom: 18px; max-width: 540px;">
                            Browse our thoughtfully curated assortment of fashionable attire and bespoke silhouettes.
                        </p>
                        <div id="liveBtnWrap">
                            <span class="btn btn-sm text-uppercase fw-bold" style="background: #D4AF37; color: #012819; padding: 9px 24px; border-radius: 50px; letter-spacing: 1px; font-size: 11.5px; display: inline-flex; align-items: center; gap: 8px;">
                                <span id="liveBtnText">START SHOPPING</span> <i class="fas fa-arrow-right"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex justify-content-end gap-3 mb-5">
                <a href="<?= BASE_URL ?>admin/carousel/index.php" class="btn btn-outline-secondary px-4">Cancel</a>
                <button type="submit" class="btn btn-primary px-5 py-2 fw-bold">
                    <i class="fas fa-save me-2"></i> Save &amp; Publish Slide
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Live Preview Elements
const desktopInput = document.getElementById('desktopImageInput');
const mobileInput = document.getElementById('mobileImageInput');
const liveSlideBox = document.getElementById('liveSlideBox');
const liveOverlay = document.getElementById('liveOverlay');
const liveContentWrap = document.getElementById('liveContentWrap');

const subtitleInput = document.querySelector('input[name="subtitle"]');
const titleInput = document.querySelector('input[name="title"]');
const descInput = document.querySelector('textarea[name="description"]');
const btnTextInput = document.querySelector('input[name="button_text"]');
const textPosSelect = document.querySelector('select[name="text_position"]');
const textColorSelect = document.querySelector('select[name="text_color"]');

const liveBadgeWrap = document.getElementById('liveBadgeWrap');
const liveBadgeText = document.getElementById('liveBadgeText');
const liveTitle = document.getElementById('liveTitle');
const liveDesc = document.getElementById('liveDesc');
const liveBtnWrap = document.getElementById('liveBtnWrap');
const liveBtnText = document.getElementById('liveBtnText');

function updateLivePreview() {
    // Badge / Subtitle
    const subVal = subtitleInput ? subtitleInput.value.trim() : '';
    if (subVal) {
        liveBadgeWrap.style.display = 'block';
        liveBadgeText.textContent = subVal;
    } else {
        liveBadgeWrap.style.display = 'none';
    }

    // Title
    const titleVal = titleInput ? titleInput.value.trim() : '';
    if (titleVal) {
        liveTitle.style.display = 'block';
        liveTitle.textContent = titleVal;
    } else {
        liveTitle.style.display = 'none';
    }

    // Description
    const descVal = descInput ? descInput.value.trim() : '';
    if (descVal) {
        liveDesc.style.display = 'block';
        liveDesc.textContent = descVal;
    } else {
        liveDesc.style.display = 'none';
    }

    // Button
    const btnVal = btnTextInput ? btnTextInput.value.trim() : '';
    if (btnVal) {
        liveBtnWrap.style.display = 'block';
        liveBtnText.textContent = btnVal;
    } else {
        liveBtnWrap.style.display = 'none';
    }

    // Text Position
    const pos = textPosSelect ? textPosSelect.value : 'left';
    if (pos === 'center') {
        liveContentWrap.className = 'position-relative h-100 p-4 p-md-5 d-flex flex-column justify-content-center text-center align-items-center mx-auto';
        liveOverlay.style.background = 'linear-gradient(180deg, rgba(1, 38, 24, 0.2) 0%, rgba(1, 38, 24, 0.72) 45%, rgba(1, 38, 24, 0.94) 100%)';
    } else if (pos === 'right') {
        liveContentWrap.className = 'position-relative h-100 p-4 p-md-5 d-flex flex-column justify-content-center text-end align-items-end ms-auto';
        liveOverlay.style.background = 'linear-gradient(270deg, rgba(1, 38, 24, 0.94) 0%, rgba(1, 38, 24, 0.6) 45%, rgba(1, 38, 24, 0.15) 100%)';
    } else {
        liveContentWrap.className = 'position-relative h-100 p-4 p-md-5 d-flex flex-column justify-content-center text-start align-items-start';
        liveOverlay.style.background = 'linear-gradient(90deg, rgba(1, 38, 24, 0.94) 0%, rgba(1, 38, 24, 0.6) 45%, rgba(1, 38, 24, 0.15) 100%)';
    }

    // Text Color
    const col = textColorSelect ? textColorSelect.value : 'light';
    if (col === 'dark') {
        liveTitle.style.color = '#012819';
        liveDesc.style.color = '#1A3F30';
        liveOverlay.style.background = 'linear-gradient(90deg, rgba(255, 255, 255, 0.92) 0%, rgba(255, 255, 255, 0.6) 45%, rgba(255, 255, 255, 0.15) 100%)';
    } else {
        liveTitle.style.color = '#FFFFFF';
        liveDesc.style.color = 'rgba(255, 255, 255, 0.82)';
    }
}

// Attach event listeners
[subtitleInput, titleInput, descInput, btnTextInput].forEach(el => {
    if (el) el.addEventListener('input', updateLivePreview);
});
[textPosSelect, textColorSelect].forEach(el => {
    if (el) el.addEventListener('change', updateLivePreview);
});

// Image file readers
desktopInput?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            const img = document.getElementById('desktopPreviewImg');
            const placeholder = document.getElementById('desktopPreviewPlaceholder');
            if (img && placeholder) {
                img.src = evt.target.result;
                img.classList.remove('d-none');
                placeholder.classList.add('d-none');
            }
            if (liveSlideBox) {
                liveSlideBox.style.backgroundImage = 'url(' + evt.target.result + ')';
            }
        };
        reader.readAsDataURL(file);
    }
});

mobileInput?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            const img = document.getElementById('mobilePreviewImg');
            const placeholder = document.getElementById('mobilePreviewPlaceholder');
            if (img && placeholder) {
                img.src = evt.target.result;
                img.classList.remove('d-none');
                placeholder.classList.add('d-none');
            }
        };
        reader.readAsDataURL(file);
    }
});

updateLivePreview();
</script>

<?php require_once __DIR__ . '/../../includes/admin-footer.php'; ?>

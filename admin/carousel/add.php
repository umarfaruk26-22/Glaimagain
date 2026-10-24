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
// Image preview scripts
document.getElementById('desktopImageInput')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            const img = document.getElementById('desktopPreviewImg');
            const placeholder = document.getElementById('desktopPreviewPlaceholder');
            img.src = evt.target.result;
            img.classList.remove('d-none');
            placeholder.classList.add('d-none');
        };
        reader.readAsDataURL(file);
    }
});

document.getElementById('mobileImageInput')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            const img = document.getElementById('mobilePreviewImg');
            const placeholder = document.getElementById('mobilePreviewPlaceholder');
            img.src = evt.target.result;
            img.classList.remove('d-none');
            placeholder.classList.add('d-none');
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/admin-footer.php'; ?>

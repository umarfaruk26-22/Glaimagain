<?php
/**
 * GLAIMAGAIN - Batch / Multi-Photo Carousel Slide Creator
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();

// Suggest starting sort order
$maxOrder = (int)$pdo->query("SELECT COALESCE(MAX(sort_order), 0) FROM carousel_slides")->fetchColumn();

$errors = [];
$successCount = 0;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    requireCsrfToken();

    $slidesData = $_POST['slides'] ?? [];
    $filesData = $_FILES['slides'] ?? null;

    if (empty($slidesData) || !is_array($slidesData)) {
        $errors[] = 'No slide items were submitted.';
    } else {
        $currentOrder = $maxOrder;

        foreach ($slidesData as $idx => $sData) {
            $hasDesktopFile = !empty($filesData['name'][$idx]['image']);

            // Skip completely empty rows
            if (!$hasDesktopFile && empty($sData['title']) && empty($sData['subtitle'])) {
                continue;
            }

            if (!$hasDesktopFile) {
                $errors[] = "Slide #" . ($idx + 1) . ": Desktop banner image is required.";
                continue;
            }

            // Extract single file struct for desktop image
            $desktopFile = [
                'name'     => $filesData['name'][$idx]['image'] ?? '',
                'type'     => $filesData['type'][$idx]['image'] ?? '',
                'tmp_name' => $filesData['tmp_name'][$idx]['image'] ?? '',
                'error'    => $filesData['error'][$idx]['image'] ?? 4,
                'size'     => $filesData['size'][$idx]['image'] ?? 0,
            ];

            $uploadRes = handleImageUpload($desktopFile, 'carousel');
            if (!$uploadRes['success']) {
                $errors[] = "Slide #" . ($idx + 1) . " Image Error: " . $uploadRes['error'];
                continue;
            }
            $imagePath = $uploadRes['path'];

            // Optional mobile image
            $mobileImagePath = null;
            if (!empty($filesData['name'][$idx]['mobile_image'])) {
                $mobileFile = [
                    'name'     => $filesData['name'][$idx]['mobile_image'] ?? '',
                    'type'     => $filesData['type'][$idx]['mobile_image'] ?? '',
                    'tmp_name' => $filesData['tmp_name'][$idx]['mobile_image'] ?? '',
                    'error'    => $filesData['error'][$idx]['mobile_image'] ?? 4,
                    'size'     => $filesData['size'][$idx]['mobile_image'] ?? 0,
                ];
                $uploadMob = handleImageUpload($mobileFile, 'carousel');
                if ($uploadMob['success']) {
                    $mobileImagePath = $uploadMob['path'];
                }
            }

            $currentOrder++;
            $title = trim($sData['title'] ?? '');
            $subtitle = trim($sData['subtitle'] ?? '');
            $description = trim($sData['description'] ?? '');
            $buttonText = trim($sData['button_text'] ?? 'START SHOPPING');
            $buttonLink = trim($sData['button_link'] ?? 'shop.php');
            $textPosition = in_array($sData['text_position'] ?? '', ['left', 'center', 'right']) ? $sData['text_position'] : 'left';
            $textColor = in_array($sData['text_color'] ?? '', ['light', 'dark']) ? $sData['text_color'] : 'light';
            $sortOrder = isset($sData['sort_order']) && $sData['sort_order'] !== '' ? (int)$sData['sort_order'] : $currentOrder;
            $status = isset($sData['status']) && $sData['status'] === 'active' ? 'active' : 'inactive';

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
            $successCount++;
        }

        if ($successCount > 0 && empty($errors)) {
            setFlashMessage('success', "Successfully published {$successCount} new carousel slides to the homepage.");
            header('Location: ' . BASE_URL . 'admin/carousel/index.php');
            exit;
        } elseif ($successCount > 0 && !empty($errors)) {
            setFlashMessage('warning', "Published {$successCount} slides, but some items had errors.");
        }
    }
}

$adminHeaderHeading = 'Multi-Photo Carousel Slide Creator';
$adminTitle = 'Batch Add Slides | GLAIMAGAIN Admin';
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-10 col-lg-11">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h5 class="fw-bold text-emerald mb-1">Batch / Multi-Photo Carousel Creator</h5>
                <p class="text-muted small mb-0">Upload multiple photos and configure custom headlines, buttons, and layouts for each slide simultaneously.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>admin/carousel/index.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Back to Slides
                </a>
                <a href="<?= BASE_URL ?>admin/carousel/add.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> Single Slide Mode
                </a>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Please review the following:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>admin/carousel/batch-add.php" enctype="multipart/form-data" id="batchSlidesForm">
            <?= csrfField() ?>

            <div id="slidesContainer">
                <!-- Slide Item #1 -->
                <div class="admin-card p-4 p-md-5 mb-4 slide-batch-item position-relative border-start border-4 border-emerald" data-slide-index="0">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <span class="badge bg-forest text-gold px-3 py-2 fs-6 rounded-pill">
                            <i class="fas fa-image me-1"></i> Slide #<span class="slide-num">1</span>
                        </span>
                        <div class="form-check form-switch fs-6 mb-0">
                            <input class="form-check-input" type="checkbox" name="slides[0][status]" value="active" id="status_0" checked>
                            <label class="form-check-label fw-bold text-forest small" for="status_0">Active</label>
                        </div>
                    </div>

                    <div class="row g-4">
                        <!-- Imagery -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Desktop Banner Image <span class="text-danger">*</span></label>
                            <input type="file" name="slides[0][image]" class="form-control desktop-file-input" accept="image/jpeg,image/png,image/webp" required>
                            <div class="form-text small">Recommended: 1920 &times; 800 px (Landscape)</div>
                            <div class="mt-2 preview-box rounded border bg-light p-2 text-center" style="min-height: 120px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                <span class="text-muted small preview-placeholder"><i class="fas fa-image me-1"></i> Preview appears on select</span>
                                <img src="" class="img-fluid rounded d-none preview-img" style="max-height: 140px; object-fit: cover;">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Mobile Image <span class="badge bg-secondary-subtle text-secondary small">Optional</span></label>
                            <input type="file" name="slides[0][mobile_image]" class="form-control mobile-file-input" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text small">Recommended: 800 &times; 1000 px (Portrait crop)</div>
                            <div class="mt-2 preview-box rounded border bg-light p-2 text-center" style="min-height: 120px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                <span class="text-muted small preview-mob-placeholder"><i class="fas fa-mobile-alt me-1"></i> Mobile preview</span>
                                <img src="" class="img-fluid rounded d-none preview-mob-img" style="max-height: 140px; object-fit: cover;">
                            </div>
                        </div>

                        <!-- Text Overlay Configuration -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Season Badge / Tagline</label>
                            <input type="text" name="slides[0][subtitle]" class="form-control" placeholder="e.g. AUTOGRAPH COLLECTION 2026">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Headline Title</label>
                            <input type="text" name="slides[0][title]" class="form-control" placeholder="e.g. Explore and Define Your Unique Style!">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">Description / Story Copy</label>
                            <textarea name="slides[0][description]" rows="2" class="form-control" placeholder="Short luxury story displayed beneath title..."></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Button CTA Text</label>
                            <input type="text" name="slides[0][button_text]" class="form-control" value="START SHOPPING">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Button Destination URL</label>
                            <input type="text" name="slides[0][button_link]" class="form-control" value="shop.php">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Text Position</label>
                            <select name="slides[0][text_position]" class="form-select">
                                <option value="left" selected>Left Aligned</option>
                                <option value="center">Centered</option>
                                <option value="right">Right Aligned</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Typography Theme</label>
                            <select name="slides[0][text_color]" class="form-select">
                                <option value="light" selected>Light (White &amp; Gold)</option>
                                <option value="dark">Dark (Forest Green)</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Sort Order</label>
                            <input type="number" name="slides[0][sort_order]" class="form-control" value="<?= $maxOrder + 1 ?>" min="0">
                        </div>
                    </div>
                </div>

                <!-- Slide Item #2 -->
                <div class="admin-card p-4 p-md-5 mb-4 slide-batch-item position-relative border-start border-4 border-gold" data-slide-index="1">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <span class="badge bg-forest text-gold px-3 py-2 fs-6 rounded-pill">
                            <i class="fas fa-image me-1"></i> Slide #<span class="slide-num">2</span>
                        </span>
                        <div class="d-flex align-items-center gap-3">
                            <div class="form-check form-switch fs-6 mb-0">
                                <input class="form-check-input" type="checkbox" name="slides[1][status]" value="active" id="status_1" checked>
                                <label class="form-check-label fw-bold text-forest small" for="status_1">Active</label>
                            </div>
                            <button type="button" class="btn btn-outline-danger btn-sm btn-remove-slide" title="Remove this slide">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <div class="row g-4">
                        <!-- Imagery -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Desktop Banner Image <span class="badge bg-secondary-subtle text-secondary small">Optional if empty</span></label>
                            <input type="file" name="slides[1][image]" class="form-control desktop-file-input" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text small">Recommended: 1920 &times; 800 px (Landscape)</div>
                            <div class="mt-2 preview-box rounded border bg-light p-2 text-center" style="min-height: 120px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                <span class="text-muted small preview-placeholder"><i class="fas fa-image me-1"></i> Preview appears on select</span>
                                <img src="" class="img-fluid rounded d-none preview-img" style="max-height: 140px; object-fit: cover;">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Mobile Image <span class="badge bg-secondary-subtle text-secondary small">Optional</span></label>
                            <input type="file" name="slides[1][mobile_image]" class="form-control mobile-file-input" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text small">Recommended: 800 &times; 1000 px (Portrait crop)</div>
                            <div class="mt-2 preview-box rounded border bg-light p-2 text-center" style="min-height: 120px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                <span class="text-muted small preview-mob-placeholder"><i class="fas fa-mobile-alt me-1"></i> Mobile preview</span>
                                <img src="" class="img-fluid rounded d-none preview-mob-img" style="max-height: 140px; object-fit: cover;">
                            </div>
                        </div>

                        <!-- Text Overlay Configuration -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Season Badge / Tagline</label>
                            <input type="text" name="slides[1][subtitle]" class="form-control" placeholder="e.g. TIMELESS LUXURY APPAREL">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Headline Title</label>
                            <input type="text" name="slides[1][title]" class="form-control" placeholder="e.g. Tailored Jackets & Premium Outerwear">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">Description / Story Copy</label>
                            <textarea name="slides[1][description]" rows="2" class="form-control" placeholder="Short luxury story displayed beneath title..."></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Button CTA Text</label>
                            <input type="text" name="slides[1][button_text]" class="form-control" value="START SHOPPING">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Button Destination URL</label>
                            <input type="text" name="slides[1][button_link]" class="form-control" value="shop.php">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Text Position</label>
                            <select name="slides[1][text_position]" class="form-select">
                                <option value="left" selected>Left Aligned</option>
                                <option value="center">Centered</option>
                                <option value="right">Right Aligned</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Typography Theme</label>
                            <select name="slides[1][text_color]" class="form-select">
                                <option value="light" selected>Light (White &amp; Gold)</option>
                                <option value="dark">Dark (Forest Green)</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Sort Order</label>
                            <input type="number" name="slides[1][sort_order]" class="form-control" value="<?= $maxOrder + 2 ?>" min="0">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dynamic Add Slot Button -->
            <div class="text-center mb-4">
                <button type="button" class="btn btn-outline-forest px-4 py-2" id="btnAddMoreSlides">
                    <i class="fas fa-plus-circle me-2 text-gold"></i> Add Another Slide Slot
                </button>
            </div>

            <!-- Bottom Save Bar -->
            <div class="admin-card p-4 d-flex flex-wrap justify-content-between align-items-center gap-3 mb-5">
                <div>
                    <span class="fw-bold text-forest">Ready to upload all configured carousel slides?</span>
                    <p class="text-muted small mb-0">All valid slides with images will be saved and immediately activated on the homepage carousel with 3-second auto-scroll.</p>
                </div>
                <div class="d-flex gap-3">
                    <a href="<?= BASE_URL ?>admin/carousel/index.php" class="btn btn-outline-secondary px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold">
                        <i class="fas fa-layer-group me-2"></i> Save &amp; Publish All Slides
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
let slideIndexCounter = 2;

function attachFilePreviewListeners(scope) {
    scope.querySelectorAll('.desktop-file-input').forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const card = input.closest('.slide-batch-item');
            const previewImg = card.querySelector('.preview-img');
            const placeholder = card.querySelector('.preview-placeholder');
            if (file && previewImg && placeholder) {
                const reader = new FileReader();
                reader.onload = function(evt) {
                    previewImg.src = evt.target.result;
                    previewImg.classList.remove('d-none');
                    placeholder.classList.add('d-none');
                };
                reader.readAsDataURL(file);
            }
        });
    });

    scope.querySelectorAll('.mobile-file-input').forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const card = input.closest('.slide-batch-item');
            const previewMobImg = card.querySelector('.preview-mob-img');
            const placeholderMob = card.querySelector('.preview-mob-placeholder');
            if (file && previewMobImg && placeholderMob) {
                const reader = new FileReader();
                reader.onload = function(evt) {
                    previewMobImg.src = evt.target.result;
                    previewMobImg.classList.remove('d-none');
                    placeholderMob.classList.add('d-none');
                };
                reader.readAsDataURL(file);
            }
        });
    });

    scope.querySelectorAll('.btn-remove-slide').forEach(btn => {
        btn.addEventListener('click', function() {
            const card = btn.closest('.slide-batch-item');
            if (document.querySelectorAll('.slide-batch-item').length > 1) {
                card.remove();
                renumberSlides();
            } else {
                alert('At least one slide item must remain.');
            }
        });
    });
}

function renumberSlides() {
    const items = document.querySelectorAll('.slide-batch-item');
    items.forEach((item, idx) => {
        const numSpan = item.querySelector('.slide-num');
        if (numSpan) numSpan.textContent = idx + 1;
    });
}

document.getElementById('btnAddMoreSlides')?.addEventListener('click', function() {
    const container = document.getElementById('slidesContainer');
    const newIdx = slideIndexCounter++;
    const totalCurrent = document.querySelectorAll('.slide-batch-item').length + 1;
    const defaultSort = <?= $maxOrder ?> + totalCurrent;

    const html = `
    <div class="admin-card p-4 p-md-5 mb-4 slide-batch-item position-relative border-start border-4 border-emerald" data-slide-index="${newIdx}">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <span class="badge bg-forest text-gold px-3 py-2 fs-6 rounded-pill">
                <i class="fas fa-image me-1"></i> Slide #<span class="slide-num">${totalCurrent}</span>
            </span>
            <div class="d-flex align-items-center gap-3">
                <div class="form-check form-switch fs-6 mb-0">
                    <input class="form-check-input" type="checkbox" name="slides[${newIdx}][status]" value="active" id="status_${newIdx}" checked>
                    <label class="form-check-label fw-bold text-forest small" for="status_${newIdx}">Active</label>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm btn-remove-slide" title="Remove this slide">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <label class="form-label fw-bold">Desktop Banner Image <span class="text-danger">*</span></label>
                <input type="file" name="slides[${newIdx}][image]" class="form-control desktop-file-input" accept="image/jpeg,image/png,image/webp" required>
                <div class="form-text small">Recommended: 1920 &times; 800 px (Landscape)</div>
                <div class="mt-2 preview-box rounded border bg-light p-2 text-center" style="min-height: 120px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                    <span class="text-muted small preview-placeholder"><i class="fas fa-image me-1"></i> Preview appears on select</span>
                    <img src="" class="img-fluid rounded d-none preview-img" style="max-height: 140px; object-fit: cover;">
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Mobile Image <span class="badge bg-secondary-subtle text-secondary small">Optional</span></label>
                <input type="file" name="slides[${newIdx}][mobile_image]" class="form-control mobile-file-input" accept="image/jpeg,image/png,image/webp">
                <div class="form-text small">Recommended: 800 &times; 1000 px (Portrait crop)</div>
                <div class="mt-2 preview-box rounded border bg-light p-2 text-center" style="min-height: 120px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                    <span class="text-muted small preview-mob-placeholder"><i class="fas fa-mobile-alt me-1"></i> Mobile preview</span>
                    <img src="" class="img-fluid rounded d-none preview-mob-img" style="max-height: 140px; object-fit: cover;">
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Season Badge / Tagline</label>
                <input type="text" name="slides[${newIdx}][subtitle]" class="form-control" placeholder="e.g. LUXURY CAPSULE 2026">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Headline Title</label>
                <input type="text" name="slides[${newIdx}][title]" class="form-control" placeholder="e.g. Modern Craftsmanship Redefined">
            </div>

            <div class="col-12">
                <label class="form-label fw-bold">Description / Story Copy</label>
                <textarea name="slides[${newIdx}][description]" rows="2" class="form-control" placeholder="Short luxury story displayed beneath title..."></textarea>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Button CTA Text</label>
                <input type="text" name="slides[${newIdx}][button_text]" class="form-control" value="START SHOPPING">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Button Destination URL</label>
                <input type="text" name="slides[${newIdx}][button_link]" class="form-control" value="shop.php">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold">Text Position</label>
                <select name="slides[${newIdx}][text_position]" class="form-select">
                    <option value="left" selected>Left Aligned</option>
                    <option value="center">Centered</option>
                    <option value="right">Right Aligned</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold">Typography Theme</label>
                <select name="slides[${newIdx}][text_color]" class="form-select">
                    <option value="light" selected>Light (White &amp; Gold)</option>
                    <option value="dark">Dark (Forest Green)</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold">Sort Order</label>
                <input type="number" name="slides[${newIdx}][sort_order]" class="form-control" value="${defaultSort}" min="0">
            </div>
        </div>
    </div>
    `;

    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = html.trim();
    const newCard = tempDiv.firstChild;
    container.appendChild(newCard);
    attachFilePreviewListeners(newCard);
});

// Initial attachment
attachFilePreviewListeners(document);
</script>

<?php require_once __DIR__ . '/../../includes/admin-footer.php'; ?>

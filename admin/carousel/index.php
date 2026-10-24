<?php
/**
 * GLAIMAGAIN - Admin Hero Carousel & Slides Management
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();

// Fetch all slides ordered by sort_order
$slides = getCarouselSlides(false);

$totalSlides = count($slides);
$activeSlides = 0;
foreach ($slides as $s) {
    if ($s['status'] === 'active') {
        $activeSlides++;
    }
}

$adminHeaderHeading = 'Hero Carousel & Banner Slides';
$adminTitle = 'Hero Carousel Slides | GLAIMAGAIN Admin';
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<!-- Metric Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small text-uppercase letter-spacing-1">Active Slides</span>
                    <h3 class="fw-bold text-forest mb-0 mt-1"><?= $activeSlides ?> / <?= $totalSlides ?></h3>
                </div>
                <div class="stat-icon-circle bg-mint text-forest">
                    <i class="fas fa-images"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small text-uppercase letter-spacing-1">Slider Speed</span>
                    <h3 class="fw-bold text-forest mb-0 mt-1">5.0s</h3>
                </div>
                <div class="stat-icon-circle bg-mint text-forest">
                    <i class="fas fa-stopwatch"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small text-uppercase letter-spacing-1">Mobile Optimized</span>
                    <h3 class="fw-bold text-forest mb-0 mt-1">Touch Ready</h3>
                </div>
                <div class="stat-icon-circle bg-mint text-forest">
                    <i class="fas fa-mobile-alt"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small text-uppercase letter-spacing-1">Live Homepage</span>
                    <a href="<?= BASE_URL ?>" target="_blank" class="d-block text-decoration-none fw-bold text-emerald mt-1">
                        View Live <i class="fas fa-external-link-alt ms-1 small"></i>
                    </a>
                </div>
                <div class="stat-icon-circle bg-mint text-forest">
                    <i class="fas fa-eye"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Header Action Row -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h5 class="fw-bold text-emerald mb-1">Interactive Homepage Carousel Slides</h5>
        <p class="text-muted small mb-0">Manage rotating luxury hero banners, headlines, call-to-actions, and mobile-specific images.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>admin/homepage" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Homepage CMS
        </a>
        <a href="<?= BASE_URL ?>admin/carousel/add.php" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Add New Slide
        </a>
    </div>
</div>

<!-- Table / Cards List -->
<div class="admin-card">
    <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
        <span class="fw-bold text-forest small text-uppercase letter-spacing-1">
            <i class="fas fa-layer-group text-gold me-2"></i>Carousel Slides Sequence (<?= count($slides) ?>)
        </span>
        <span class="text-muted small">Slides auto-rotate every 5 seconds on the live homepage</span>
    </div>

    <?php if (empty($slides)): ?>
        <div class="text-center py-5">
            <i class="fas fa-images fa-3x text-muted mb-3 d-block opacity-50"></i>
            <h6 class="fw-bold text-dark">No Carousel Slides Configured</h6>
            <p class="text-muted small mb-3">Add your first rotating banner to display on the homepage.</p>
            <a href="<?= BASE_URL ?>admin/carousel/add.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i> Add First Slide
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 70px;">Order</th>
                        <th style="width: 140px;">Slide Preview</th>
                        <th>Headline &amp; Content</th>
                        <th>Button / CTA</th>
                        <th style="width: 130px;">Mobile Crop</th>
                        <th style="width: 110px;">Status</th>
                        <th style="width: 160px;" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($slides as $slide): ?>
                        <tr>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1 fw-bold">
                                    #<?= (int)$slide['sort_order'] ?>
                                </span>
                            </td>
                            <td>
                                <div class="position-relative rounded overflow-hidden shadow-sm" style="width: 130px; height: 68px; background: #012819;">
                                    <img src="<?= BASE_URL . e($slide['image_path']) ?>" alt="Slide" class="w-100 h-100" style="object-fit: cover;">
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($slide['subtitle'])): ?>
                                    <span class="badge bg-mint text-forest px-2 py-1 rounded-pill small mb-1">
                                        <i class="fas fa-sparkles me-1 text-gold"></i> <?= e($slide['subtitle']) ?>
                                    </span>
                                <?php endif; ?>
                                <h6 class="fw-bold text-forest mb-1">
                                    <?= !empty($slide['title']) ? e($slide['title']) : '<span class="text-muted fst-italic">No headline (Visual banner)</span>' ?>
                                </h6>
                                <?php if (!empty($slide['description'])): ?>
                                    <p class="text-muted small mb-0 text-truncate" style="max-width: 320px;">
                                        <?= e($slide['description']) ?>
                                    </p>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($slide['button_text'])): ?>
                                    <span class="badge bg-forest text-gold px-2 py-1 mb-1 d-inline-block">
                                        <?= e($slide['button_text']) ?>
                                    </span>
                                    <br>
                                    <small class="text-muted text-truncate d-inline-block" style="max-width: 160px;">
                                        <i class="fas fa-link me-1"></i> <?= e($slide['button_link']) ?>
                                    </small>
                                <?php else: ?>
                                    <span class="text-muted small">None</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($slide['mobile_image_path'])): ?>
                                    <div class="d-flex align-items-center gap-1 text-success small fw-semibold">
                                        <i class="fas fa-check-circle"></i> Custom
                                    </div>
                                    <small class="text-muted">Vertical Crop</small>
                                <?php else: ?>
                                    <div class="d-flex align-items-center gap-1 text-muted small">
                                        <i class="fas fa-sync-alt"></i> Auto-Scaled
                                    </div>
                                    <small class="text-muted">From Desktop</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= BASE_URL ?>admin/carousel/toggle-status.php?id=<?= $slide['id'] ?>&csrf_token=<?= urlencode(getCsrfToken()) ?>" 
                                   class="text-decoration-none" title="Click to toggle status">
                                    <?php if ($slide['status'] === 'active'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                                            <i class="fas fa-check me-1"></i> Active
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary px-2 py-1">
                                            <i class="fas fa-pause me-1"></i> Inactive
                                        </span>
                                    <?php endif; ?>
                                </a>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>admin/carousel/edit.php?id=<?= $slide['id'] ?>" class="btn btn-outline-secondary" title="Edit Slide">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>admin/carousel/delete.php?id=<?= $slide['id'] ?>&csrf_token=<?= urlencode(getCsrfToken()) ?>" 
                                       class="btn btn-outline-danger" 
                                       onclick="return confirm('Are you sure you want to delete this carousel slide?');" 
                                       title="Delete Slide">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Helpful Guide Section -->
<div class="admin-card p-4 mt-4">
    <h6 class="fw-bold text-forest mb-2"><i class="fas fa-lightbulb text-gold me-2"></i>Pro Tips for Best Carousel Visuals</h6>
    <div class="row g-3 small text-muted">
        <div class="col-md-4">
            <strong class="text-dark d-block mb-1"><i class="fas fa-desktop text-emerald me-1"></i> Desktop Banner Size</strong>
            Recommended resolution is <strong>1920 &times; 800 px</strong> (or 1600 &times; 750 px). Keep the focal subject (model or suit) slightly toward the center or right so left-aligned headlines never overlap key fashion elements.
        </div>
        <div class="col-md-4">
            <strong class="text-dark d-block mb-1"><i class="fas fa-mobile-alt text-emerald me-1"></i> Mobile Responsive Experience</strong>
            On smartphones, the carousel automatically switches to a mobile-friendly view with touch swipe support. If you want a tailored vertical shot, you can upload a custom <strong>800 &times; 1000 px</strong> mobile image!
        </div>
        <div class="col-md-4">
            <strong class="text-dark d-block mb-1"><i class="fas fa-sliders-h text-emerald me-1"></i> Reordering &amp; Speed</strong>
            Use the <strong>Sort Order</strong> field (1, 2, 3...) to control which banner appears first. Slides automatically transition smoothly every 5 seconds.
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/admin-footer.php'; ?>

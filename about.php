<?php
/**
 * GLAIMAGAIN - About Us & Heritage Page
 */
require_once __DIR__ . '/config/config.php';

$pageTitle = 'The Heritage & Atelier Story | GLAIMAGAIN';
$metaDescription = 'Discover the legacy of GLAIMAGAIN — Fashion Beyond Today. Our commitment to uncompromising fabrics, bespoke tailoring, and timeless elegance.';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Hero Banner -->
<div class="bg-emerald text-white py-5 border-bottom border-gold-subtle">
    <div class="container text-center py-4">
        <span class="text-gold fw-bold letter-spacing-4 small text-uppercase">The Atelier</span>
        <h1 class="display-4 fw-bold text-white mt-1">FASHION BEYOND TODAY</h1>
        <div class="gold-divider"><i class="fas fa-crown"></i></div>
        <p class="lead text-white-50 max-w-600 mx-auto">Where haute couture tailoring intersects with visionary modern luxury.</p>
    </div>
</div>

<div class="container py-5 my-3">
    <!-- Section 1: Brand Genesis -->
    <div class="row align-items-center g-5 mb-5 pb-4">
        <div class="col-lg-6">
            <span class="text-gold fw-bold letter-spacing-3 small text-uppercase">Our Genesis</span>
            <h2 class="display-6 fw-bold text-emerald mt-2 mb-3">CONCEIVED FOR DISTINCTION</h2>
            <div class="gold-divider ms-0"><i class="fas fa-gem"></i></div>
            <p class="text-muted" style="line-height: 1.8;">
                GLAIMAGAIN was established upon a singular philosophy: that clothing should not merely reflect fleeting micro-trends, but command timeless authority.
            </p>
            <p class="text-muted" style="line-height: 1.8;">
                From our custom-milled 280+ GSM combed organic cottons to hand-tailored silk velvet tuxedo lapels, every garment is an architectural masterpiece designed for patrons who demand unmatched sartorial elegance.
            </p>
        </div>
        <div class="col-lg-6">
            <img src="<?= BASE_URL ?>assets/images/brand-story.jpg" alt="GLAIMAGAIN Craftsmanship" class="img-fluid rounded border-gold-subtle shadow-lg" style="border: 2px solid #B99036;">
        </div>
    </div>

    <!-- Section 2: Core Pillars -->
    <div class="row g-4 text-center my-5 py-5 bg-offwhite rounded border border-gold-subtle">
        <div class="col-md-4">
            <div class="p-3">
                <i class="fas fa-feather-alt text-gold fs-1 mb-3"></i>
                <h4 class="text-emerald fw-bold">BESPOKE MATERIALS</h4>
                <p class="text-muted small mb-0">We curate 120s Egyptian Giza cottons, raw Japanese selvedge denim, and genuine mother-of-pearl buttons.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3">
                <i class="fas fa-cut text-gold fs-1 mb-3"></i>
                <h4 class="text-emerald fw-bold">TAILORED CUTS</h4>
                <p class="text-muted small mb-0">Every drape and seam is engineered for optimal posture, luxury weight distribution, and comfortable movement.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3">
                <i class="fas fa-crown text-gold fs-1 mb-3"></i>
                <h4 class="text-emerald fw-bold">LIMITED EDITIONS</h4>
                <p class="text-muted small mb-0">We reject fast fashion mass production. Every capsule is crafted in strictly limited artisan batches.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

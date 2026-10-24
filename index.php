<?php
/**
 * GLAIMAGAIN - Homepage (Rozelio Luxury Layout)
 * Brand: GLAIMAGAIN (FASHION BEYOND TODAY)
 */
require_once __DIR__ . '/config/config.php';

$pageTitle = 'GLAIMAGAIN | Fashion Beyond Today — Luxury Clothing & Haute Couture';
$metaDescription = 'Explore and define your unique style with GLAIMAGAIN. Heavyweight luxury tees, tailored blazers, bespoke shirts, and Japanese denim.';

$pdo = getDb();

// Fetch Best Sellers
$bestSellersStmt = $pdo->query("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug,
           (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order ASC LIMIT 1) AS primary_image
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.status = 'active'
    ORDER BY p.is_best_seller DESC, p.views_count DESC, p.id DESC LIMIT 8
");
$bestSellers = $bestSellersStmt->fetchAll();

// Fetch Our Products (Catalog pieces)
$catalogStmt = $pdo->query("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug,
           (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order ASC LIMIT 1) AS primary_image
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.status = 'active'
    ORDER BY p.id DESC LIMIT 8
");
$allProducts = $catalogStmt->fetchAll();

// Fetch Featured Categories
$featuredCategories = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order ASC LIMIT 6")->fetchAll();

// Fetch Client Reviews
$reviewsStmt = $pdo->query("
    SELECT r.*, u.first_name, u.last_name, p.name AS product_name
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN products p ON r.product_id = p.id
    WHERE r.status = 'approved'
    ORDER BY r.id DESC LIMIT 3
");
$clientReviews = $reviewsStmt->fetchAll();

// Fetch Carousel Slides (Active & ordered)
$carouselSlides = getCarouselSlides(true);

// Fetch Exclusive Offer Section (CMS)
$exOfferStmt = $pdo->prepare("SELECT * FROM homepage_sections WHERE section_key = 'exclusive_offer' LIMIT 1");
$exOfferStmt->execute();
$exclusiveOffer = $exOfferStmt->fetch();

require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. Hero Dynamic Carousel Section (Responsive Luxury Swiper) -->
<?php if (!empty($carouselSlides)): ?>
<section class="hero-carousel-section">
    <div class="swiper heroSwiper">
        <div class="swiper-wrapper">
            <?php foreach ($carouselSlides as $slideIndex => $slide): ?>
                <?php
                $hasBtn = !empty($slide['button_text']) && !empty($slide['button_link']);
                $alignClass = 'text-start align-items-start';
                $justifyCol = 'justify-content-start';
                if (($slide['text_position'] ?? 'left') === 'center') {
                    $alignClass = 'text-center align-items-center mx-auto';
                    $justifyCol = 'justify-content-center';
                } elseif (($slide['text_position'] ?? 'left') === 'right') {
                    $alignClass = 'text-end align-items-end ms-auto';
                    $justifyCol = 'justify-content-end';
                }
                $isDarkText = ($slide['text_color'] ?? 'light') === 'dark';
                ?>
                <div class="swiper-slide hero-carousel-slide <?= $isDarkText ? 'slide-theme-dark' : 'slide-theme-light' ?>">
                    <!-- Background Picture (Responsive Desktop & Mobile Sources) -->
                    <div class="hero-slide-bg-wrap">
                        <picture>
                            <?php if (!empty($slide['mobile_image_path'])): ?>
                                <source media="(max-width: 767.98px)" srcset="<?= BASE_URL . e($slide['mobile_image_path']) ?>">
                            <?php endif; ?>
                            <img src="<?= BASE_URL . e($slide['image_path']) ?>" 
                                 alt="<?= e($slide['title'] ?? 'GLAIMAGAIN Luxury Style') ?>" 
                                 class="hero-slide-bg-img"
                                 loading="<?= $slideIndex === 0 ? 'eager' : 'lazy' ?>"
                                 <?= $slideIndex === 0 ? 'fetchpriority="high"' : '' ?>>
                        </picture>
                        <!-- Dynamic Gradient Overlay for Ultimate Text Legibility -->
                        <div class="hero-slide-overlay <?= 'overlay-' . ($slide['text_position'] ?? 'left') ?>"></div>
                    </div>

                    <!-- Slide Content Layer -->
                    <div class="container hero-slide-content-wrap">
                        <div class="row align-items-center <?= $justifyCol ?>">
                            <div class="col-xl-7 col-lg-8 col-md-10 col-12">
                                <div class="hero-slide-content d-flex flex-column <?= $alignClass ?>">
                                    <?php if (!empty($slide['subtitle'])): ?>
                                        <span class="hero-slide-badge mb-2 mb-md-3">
                                            <i class="fas fa-sparkles text-gold me-1"></i> <?= e($slide['subtitle']) ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($slide['title'])): ?>
                                        <h1 class="hero-slide-title mb-2 mb-md-3">
                                            <?= nl2br(e($slide['title'])) ?>
                                        </h1>
                                    <?php endif; ?>

                                    <?php if (!empty($slide['description'])): ?>
                                        <p class="hero-slide-desc mb-3 mb-md-4">
                                            <?= e($slide['description']) ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php if ($hasBtn): ?>
                                        <div class="hero-slide-actions mt-1">
                                            <a href="<?= BASE_URL . e($slide['button_link']) ?>" class="btn-hero-cta">
                                                <span><?= e($slide['button_text']) ?></span>
                                                <i class="fas fa-arrow-right"></i>
                                            </a>
                                        </div>
                                    <?php elseif (!empty($slide['button_link'])): ?>
                                        <a href="<?= BASE_URL . e($slide['button_link']) ?>" class="stretched-link" aria-label="Explore Banner"></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (count($carouselSlides) > 1): ?>
            <!-- Swiper Luxury Arrows -->
            <button type="button" class="hero-swiper-btn hero-swiper-prev" aria-label="Previous Slide">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button type="button" class="hero-swiper-btn hero-swiper-next" aria-label="Next Slide">
                <i class="fas fa-chevron-right"></i>
            </button>

            <!-- Swiper Pagination Indicators -->
            <div class="hero-swiper-pagination"></div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($bestSellers)): ?>
<!-- 2. "Best Selling 🔥" Section (Dark Forest Green Container with 3s Auto-Scroll) -->
<section class="container">
    <div class="best-selling-banner-section">
        <div class="best-selling-header">
            <div>
                <h2 class="best-selling-title">Best Selling 🔥</h2>
                <p class="best-selling-desc">Dive into the latest trends with our handpicked top-selling fashion pieces</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn-scroll-arrow" id="btnBestSellerPrev" aria-label="Previous Best Seller">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button type="button" class="btn-scroll-arrow" id="btnBestSellerNext" aria-label="Next Best Seller">
                    <i class="fas fa-chevron-right"></i>
                </button>
                <a href="<?= BASE_URL ?>shop.php?sort=best_selling" class="btn-pill-white text-nowrap d-none d-sm-inline-flex">
                    <span>See All</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>

        <div class="best-sellers-scroll-track" id="bestSellersScrollTrack">
            <?php foreach ($bestSellers as $bs): ?>
                <div class="best-seller-col">
                    <a href="<?= BASE_URL ?>product.php?slug=<?= urlencode($bs['slug']) ?>" class="best-seller-card">
                        <div class="best-seller-img-wrap">
                            <img src="<?= getProductImageUrl($bs['primary_image']) ?>" alt="<?= e($bs['name']) ?>" loading="lazy">
                        </div>
                        <div class="best-seller-info">
                            <h6><?= e($bs['name']) ?></h6>
                            <div class="best-seller-meta">
                                <strong><?= formatPrice($bs['selling_price']) ?></strong>
                                <span>|</span>
                                <span class="rating-pill">5.0 ★</span>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- 3. "Our Products" Catalog Section -->
<section class="our-products-section">
    <div class="container">
        <div class="our-products-header">
            <h2 class="our-products-title">Our Products</h2>

            <!-- Filter Tabs -->
            <div class="filter-tabs-pills">
                <a href="<?= BASE_URL ?>shop.php" class="filter-tab-btn active">ALL</a>
                <a href="<?= BASE_URL ?>shop.php?sort=best_selling" class="filter-tab-btn">HOT</a>
                <a href="<?= BASE_URL ?>shop.php?sort=newest" class="filter-tab-btn">NEW ARRIVALS</a>
                <?php foreach ($featuredCategories as $fc): ?>
                    <a href="<?= BASE_URL ?>shop.php?category=<?= urlencode($fc['slug']) ?>" class="filter-tab-btn">
                        <?= strtoupper(e($fc['name'])) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Product Cards Grid -->
        <div class="row g-4">
            <?php if (!empty($allProducts)): ?>
                <?php foreach ($allProducts as $p): 
                    $discount = calculateDiscountPercent((float)$p['original_price'], (float)$p['selling_price']);
                ?>
                    <div class="col-lg-3 col-md-4 col-6">
                        <div class="product-card-clean">
                            <div class="product-card-clean-img-wrap">
                                <?php if ($discount > 0): ?>
                                    <span class="badge-discount-pill"><?= $discount ?>% OFF</span>
                                <?php endif; ?>
                                <a href="<?= BASE_URL ?>product.php?slug=<?= urlencode($p['slug']) ?>">
                                    <img src="<?= getProductImageUrl($p['primary_image']) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
                                </a>
                            </div>
                            <div class="product-card-clean-body">
                                <h6 class="product-card-clean-title">
                                    <a href="<?= BASE_URL ?>product.php?slug=<?= urlencode($p['slug']) ?>"><?= e($p['name']) ?></a>
                                </h6>
                                <div class="product-card-clean-meta">
                                    <div>
                                        <span class="product-card-clean-price"><?= formatPrice($p['selling_price']) ?></span>
                                        <?php if ((float)$p['original_price'] > (float)$p['selling_price']): ?>
                                            <span class="product-card-clean-original-price"><?= formatPrice($p['original_price']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="rating-pill text-gold small">5.0 ★</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="p-5 bg-mint-light rounded-4 border border-mint">
                        <i class="fas fa-sparkles fs-2 text-gold mb-3"></i>
                        <h4 class="text-forest fw-bold">New Luxury Drops Coming Soon</h4>
                        <p class="text-muted small max-w-500 mx-auto mb-0">Our bespoke garments and seasonal releases are currently being prepared. Check back shortly for our exclusive drop.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($allProducts)): ?>
        <div class="text-center mt-5">
            <a href="<?= BASE_URL ?>shop.php" class="btn-pill-forest">
                <span>EXPLORE ALL COLLECTIONS</span>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($exclusiveOffer) && !empty($exclusiveOffer['is_active'])): 
    $exImage = !empty($exclusiveOffer['image_path']) ? BASE_URL . $exclusiveOffer['image_path'] : BASE_URL . 'assets/images/exclusive-model.jpg';
    $exTitle = $exclusiveOffer['title'] ?: 'Exclusive Offer';
    $exBadge = $exclusiveOffer['badge_text'] ?: 'TIME-LIMITED PROMOTION';
    $exDesc = $exclusiveOffer['content'] ?: 'Discover the key to a stylish upgrade with our exclusive deal: Enjoy up to 40% OFF our newest luxury drops and bespoke tailoring pieces.';
    $exBtnText = $exclusiveOffer['button_text'] ?: 'CLAIM EXCLUSIVE OFFER';
    $exBtnLink = !empty($exclusiveOffer['button_link']) ? BASE_URL . ltrim($exclusiveOffer['button_link'], '/') : BASE_URL . 'shop.php';
    $exPromo = !empty($exclusiveOffer['promo_code']) ? strtoupper($exclusiveOffer['promo_code']) : 'GLAIM40';
    $exExpiryIso = !empty($exclusiveOffer['expiry_date']) ? date('c', strtotime($exclusiveOffer['expiry_date'])) : '';
?>
<!-- 4. "Exclusive Offer" Section (Mint Split Banner) -->
<section class="container">
    <div class="exclusive-offer-banner">
        <div class="row align-items-center g-5">
            <!-- Left Model Image -->
            <div class="col-lg-6 text-center">
                <div class="exclusive-model-wrap">
                    <img src="<?= e($exImage) ?>" alt="<?= e($exTitle) ?>" class="img-fluid exclusive-model-img">
                </div>
            </div>

            <!-- Right Offer Content & Countdown -->
            <div class="col-lg-6">
                <span class="badge bg-white text-forest fw-bold px-3 py-2 rounded-pill small mb-3 letter-spacing-1 shadow-sm">
                    <i class="fas fa-tag text-gold me-1"></i> <?= e($exBadge) ?>
                </span>
                <h2 class="exclusive-offer-title"><?= e($exTitle) ?></h2>
                <div class="exclusive-offer-desc mb-4">
                    <?= nl2br(e($exDesc)) ?>
                </div>

                <!-- Live Countdown Grid -->
                <div class="countdown-grid" id="countdownGrid" data-expiry="<?= e($exExpiryIso) ?>">
                    <div class="countdown-box">
                        <div class="countdown-num" id="cdDays">00</div>
                        <div class="countdown-label">Days</div>
                    </div>
                    <div class="countdown-box">
                        <div class="countdown-num" id="cdHours">00</div>
                        <div class="countdown-label">Hours</div>
                    </div>
                    <div class="countdown-box">
                        <div class="countdown-num" id="cdMins">00</div>
                        <div class="countdown-label">Min</div>
                    </div>
                    <div class="countdown-box">
                        <div class="countdown-num" id="cdSecs">00</div>
                        <div class="countdown-label">Sec</div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <button type="button" 
                            id="btnClaimOffer" 
                            class="btn-pill-forest border-0" 
                            data-coupon="<?= e($exPromo) ?>" 
                            data-destination="<?= e($exBtnLink) ?>"
                            style="cursor: pointer;">
                        <span id="claimBtnLabel"><?= e($exBtnText) ?></span>
                        <i class="fas fa-arrow-right" id="claimBtnIcon"></i>
                    </button>
                    <div class="small text-forest fw-semibold d-inline-flex align-items-center gap-2 bg-white px-3 py-2 rounded-pill shadow-sm border border-mint">
                        <i class="fas fa-ticket-alt text-gold"></i>
                        <span>Code: <strong class="text-forest letter-spacing-1 font-monospace"><?= e($exPromo) ?></strong></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- 5. Client Testimonials -->
<?php if (!empty($clientReviews)): ?>
<section class="py-5 bg-white reviews-section">
    <div class="container">
        <div class="text-center mb-5 reviews-section-header">
            <span class="text-gold fw-bold letter-spacing-2 small text-uppercase">Client Reviews</span>
            <h2 class="display-6 fw-bold text-forest mt-1">What Our Patrons Say</h2>
        </div>

        <div class="row g-3 g-md-4">
            <?php foreach ($clientReviews as $rev): ?>
                <div class="col-lg-4 col-md-6 col-12">
                    <div class="p-3 p-md-4 bg-mint-light rounded-4 border border-mint h-100 d-flex flex-column review-card-item">
                        <div class="text-gold mb-1 star-rating-row" style="font-size: 13px;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?= $i <= (int)$rev['rating'] ? '' : 'text-muted' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <h6 class="fw-bold text-forest mb-1 review-card-title">&ldquo;<?= e($rev['title']) ?>&rdquo;</h6>
                        <p class="text-muted small mb-2 flex-grow-1 review-card-comment">&ldquo;<?= e($rev['comment']) ?>&rdquo;</p>
                        <div class="border-top border-mint pt-2 mt-auto">
                            <strong class="small text-forest d-block review-author"><?= e($rev['first_name'] . ' ' . $rev['last_name']) ?></strong>
                            <span class="text-muted review-meta" style="font-size: 10.5px;"><i class="fas fa-check-circle text-forest me-1"></i>Verified Patron &bull; <?= e($rev['product_name']) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Exclusive Offer Claim Success Modal -->
<div class="modal fade" id="exclusiveOfferModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg text-center p-4" style="background: #FFFFFF;">
            <div class="modal-body p-2">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 72px; height: 72px; background: rgba(1, 60, 38, 0.08); color: #013C26;">
                    <i class="fas fa-crown fa-2x text-gold"></i>
                </div>
                <span class="badge bg-mint text-forest fw-bold px-3 py-1 rounded-pill small mb-2 letter-spacing-1">OFFER ACTIVATED</span>
                <h4 class="fw-bold text-forest mb-2">Exclusive Deal Claimed!</h4>
                <p class="text-muted small mb-3">
                    Promo code <strong class="text-forest bg-offwhite px-2 py-1 rounded border border-mint font-monospace" id="claimedPromoCode">GLAIM40</strong> has been applied to your session &amp; copied to your clipboard!
                </p>
                <div class="p-3 bg-mint-light rounded-3 mb-4 text-forest small text-start d-flex align-items-center gap-2">
                    <i class="fas fa-check-circle text-gold fs-5"></i>
                    <div>
                        <strong class="d-block text-forest">Instant Savings Active</strong>
                        <span id="claimedDiscountDesc">Your discount will be automatically deducted at checkout.</span>
                    </div>
                </div>
                <div class="d-grid gap-2">
                    <a href="<?= BASE_URL ?>shop.php" class="btn btn-forest py-2 fw-bold" style="border-radius: 9999px;">
                        <i class="fas fa-tshirt me-1"></i> EXPLORE LUXURY DROPS
                    </a>
                    <a href="<?= BASE_URL ?>cart.php" class="btn btn-outline-dark py-2 fw-bold" style="border-radius: 9999px;">
                        <i class="fas fa-shopping-bag me-1"></i> VIEW SHOPPING BAG
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Dynamic countdown timer for Exclusive Offer
(function() {
    const grid = document.getElementById('countdownGrid');
    if (!grid) return;

    const expiryStr = grid.getAttribute('data-expiry');
    let targetTime = expiryStr ? new Date(expiryStr).getTime() : 0;
    
    // Fallback: If no valid target date or expired in CMS, provide rolling 5 days
    if (!targetTime || isNaN(targetTime)) {
        targetTime = Date.now() + (5 * 86400 + 12 * 3600 + 32 * 60) * 1000;
    }

    function updateTimer() {
        const now = Date.now();
        let diff = Math.max(0, targetTime - now);

        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const secs = Math.floor((diff % (1000 * 60)) / 1000);

        const dEl = document.getElementById('cdDays');
        const hEl = document.getElementById('cdHours');
        const mEl = document.getElementById('cdMins');
        const sEl = document.getElementById('cdSecs');

        if (dEl) dEl.textContent = String(days).padStart(2, '0');
        if (hEl) hEl.textContent = String(hours).padStart(2, '0');
        if (mEl) mEl.textContent = String(mins).padStart(2, '0');
        if (sEl) sEl.textContent = String(secs).padStart(2, '0');
    }

    updateTimer();
    setInterval(updateTimer, 1000);
})();

// Claim Exclusive Offer Promo Button Logic
document.addEventListener('DOMContentLoaded', function() {
    const claimBtn = document.getElementById('btnClaimOffer');
    if (!claimBtn) return;

    claimBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const code = this.getAttribute('data-coupon');
        const dest = this.getAttribute('data-destination') || '<?= BASE_URL ?>shop.php';
        const label = document.getElementById('claimBtnLabel');
        const icon = document.getElementById('claimBtnIcon');

        // Copy promo code to clipboard
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(code).catch(() => {});
        }

        // Show loading state
        claimBtn.disabled = true;
        if (label) label.textContent = 'CLAIMING OFFER...';
        if (icon) icon.className = 'fas fa-spinner fa-spin';

        // Apply via AJAX
        fetch('<?= BASE_URL ?>api/coupon/apply.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code: code })
        })
        .then(res => res.json())
        .then(data => {
            claimBtn.disabled = false;
            if (label) label.textContent = 'OFFER CLAIMED (' + code + ')';
            if (icon) icon.className = 'fas fa-check-circle text-gold';
            claimBtn.style.backgroundColor = '#01281A';

            // Show Claim Modal
            const modalEl = document.getElementById('exclusiveOfferModal');
            if (modalEl && typeof bootstrap !== 'undefined') {
                const codeSpan = document.getElementById('claimedPromoCode');
                const descSpan = document.getElementById('claimedDiscountDesc');
                if (codeSpan) codeSpan.textContent = code;
                if (descSpan && data.discount_formatted) {
                    descSpan.textContent = 'Get ' + data.discount_formatted + ' instantly on your purchase.';
                }
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            } else {
                alert('Offer Claimed! Promo code ' + code + ' applied to your session.');
                window.location.href = dest;
            }
        })
        .catch(() => {
            claimBtn.disabled = false;
            if (label) label.textContent = 'CLAIM EXCLUSIVE OFFER';
            if (icon) icon.className = 'fas fa-arrow-right';
            window.location.href = dest;
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

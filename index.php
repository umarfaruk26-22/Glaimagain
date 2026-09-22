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

require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. Hero Section (Rozelio Split Style) -->
<section class="hero-rozelio-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <!-- Left Hero Content -->
            <div class="col-lg-6">
                <span class="badge bg-mint text-forest fw-bold px-3 py-2 rounded-pill small mb-3 letter-spacing-1">
                    <i class="fas fa-sparkles me-1 text-gold"></i> AUTOGRAPH COLLECTION 2026
                </span>
                <h1 class="hero-rozelio-title">
                    Explore and<br>Define Your<br>Unique Style!
                </h1>
                <p class="hero-rozelio-subtitle">
                    Browse our thoughtfully curated assortment of fashionable attire and bespoke silhouettes, designed to match your individual presence.
                </p>
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <a href="<?= BASE_URL ?>shop.php" class="btn-pill-forest">
                        <span>START SHOPPING</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    
                </div>
            </div>

            <!-- Right Hero Model Card with Decorative Dot Matrices -->
            <div class="col-lg-6 text-center">
                <div class="hero-model-card-wrap">
                    <!-- Top Right Dot Matrix -->
                    <div class="dot-matrix dot-matrix-top-right">
                        <?php for ($i = 0; $i < 25; $i++): ?><span></span><?php endfor; ?>
                    </div>

                    <!-- Bottom Left Dot Matrix -->
                    <div class="dot-matrix dot-matrix-bottom-left">
                        <?php for ($i = 0; $i < 25; $i++): ?><span></span><?php endfor; ?>
                    </div>

                    <!-- Main Rounded Model Portrait -->
                    <div class="hero-model-card">
                        <img src="<?= BASE_URL ?>assets/images/hero-model.jpg" alt="GLAIMAGAIN Style Model">
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

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
        </div>

        <div class="text-center mt-5">
            <a href="<?= BASE_URL ?>shop.php" class="btn-pill-forest">
                <span>EXPLORE ALL COLLECTIONS</span>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- 4. "Exclusive Offer" Section (Mint Split Banner) -->
<section class="container">
    <div class="exclusive-offer-banner">
        <div class="row align-items-center g-5">
            <!-- Left Model Image -->
            <div class="col-lg-6 text-center">
                <div class="exclusive-model-wrap">
                    <img src="<?= BASE_URL ?>assets/images/exclusive-model.jpg" alt="Exclusive Offer Model" class="img-fluid exclusive-model-img">
                </div>
            </div>

            <!-- Right Offer Content & Countdown -->
            <div class="col-lg-6">
                <span class="badge bg-white text-forest fw-bold px-3 py-2 rounded-pill small mb-3 letter-spacing-1 shadow-sm">
                    <i class="fas fa-tag text-gold me-1"></i> TIME-LIMITED PROMOTION
                </span>
                <h2 class="exclusive-offer-title">Exclusive Offer</h2>
                <p class="exclusive-offer-desc">
                    Discover the key to a stylish upgrade with our exclusive deal: Enjoy up to <strong>40% OFF</strong> our newest luxury drops and bespoke tailoring pieces.
                </p>

                <!-- Live Countdown Grid -->
                <div class="countdown-grid" id="countdownGrid">
                    <div class="countdown-box">
                        <div class="countdown-num" id="cdDays">05</div>
                        <div class="countdown-label">Days</div>
                    </div>
                    <div class="countdown-box">
                        <div class="countdown-num" id="cdHours">12</div>
                        <div class="countdown-label">Hours</div>
                    </div>
                    <div class="countdown-box">
                        <div class="countdown-num" id="cdMins">32</div>
                        <div class="countdown-label">Min</div>
                    </div>
                    <div class="countdown-box">
                        <div class="countdown-num" id="cdSecs">45</div>
                        <div class="countdown-label">Sec</div>
                    </div>
                </div>

                <a href="<?= BASE_URL ?>shop.php" class="btn-pill-forest">
                    <span>CLAIM EXCLUSIVE OFFER</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</section>

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

<script>
// Dynamic countdown ticker for Exclusive Offer
(function() {
    let days = 5, hours = 12, mins = 32, secs = 45;
    setInterval(() => {
        secs--;
        if (secs < 0) { secs = 59; mins--; }
        if (mins < 0) { mins = 59; hours--; }
        if (hours < 0) { hours = 23; days--; }
        if (days < 0) { days = 0; hours = 0; mins = 0; secs = 0; }
        
        const dEl = document.getElementById('cdDays');
        const hEl = document.getElementById('cdHours');
        const mEl = document.getElementById('cdMins');
        const sEl = document.getElementById('cdSecs');
        if (dEl) dEl.textContent = String(days).padStart(2, '0');
        if (hEl) hEl.textContent = String(hours).padStart(2, '0');
        if (mEl) mEl.textContent = String(mins).padStart(2, '0');
        if (sEl) sEl.textContent = String(secs).padStart(2, '0');
    }, 1000);
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

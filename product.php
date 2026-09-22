<?php
/**
 * GLAIMAGAIN - Luxury Product Details Page
 */
require_once __DIR__ . '/config/config.php';

$pdo = getDb();
$slug = trim($_GET['slug'] ?? '');

if (empty($slug)) {
    header('Location: ' . BASE_URL . 'shop.php');
    exit;
}

// Fetch Product with Category
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.slug = ? AND p.status = 'active'
    LIMIT 1
");
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    setFlashMessage('danger', 'The requested garment could not be found.');
    header('Location: ' . BASE_URL . 'shop.php');
    exit;
}

// Increment View Count
$pdo->prepare("UPDATE products SET views_count = views_count + 1 WHERE id = ?")->execute([$product['id']]);

// Fetch Product Images (with color tagging)
$imgStmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC, id ASC");
$imgStmt->execute([$product['id']]);
$productImages = $imgStmt->fetchAll();

// Build map of Color -> Image URL
$colorImageMap = [];
foreach ($productImages as $img) {
    if (!empty($img['color']) && !isset($colorImageMap[trim($img['color'])])) {
        $colorImageMap[trim($img['color'])] = getProductImageUrl($img['image_path']);
    }
}


if (empty($productImages)) {
    $productImages = [['image_path' => 'assets/images/placeholder-product.svg', 'is_primary' => 1]];
}

// Fetch Variants (Size & Color stock breakdown)
$varStmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? AND status = 'active' ORDER BY size ASC");
$varStmt->execute([$product['id']]);
$variants = $varStmt->fetchAll();

// Sizes and Colors lists
$availableSizes = array_filter(array_map('trim', explode(',', $product['sizes'] ?? '')));
$availableColors = array_filter(array_map('trim', explode(',', $product['colors'] ?? '')));

// Handle Review Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_POST['is_ajax']) && $_POST['is_ajax'] === '1');

    if (!verifyCsrfToken()) {
        if ($isAjax) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Security token expired. Please reload the page.']);
            exit;
        }
        setFlashMessage('danger', 'Security token expired. Please reload the page and try again.');
        header('Location: ' . BASE_URL . 'product/' . urlencode($slug) . '#reviews');
        exit;
    }

    if (!isUserLoggedIn()) {
        if ($isAjax) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Please sign in to your GLAIMAGAIN account to submit a review.']);
            exit;
        }
        setFlashMessage('warning', 'Please sign in to your GLAIMAGAIN account to submit a review.');
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }

    $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    $reviewTitle = trim($_POST['title'] ?? '');
    $reviewComment = trim($_POST['comment'] ?? '');
    $currentUser = getCurrentUser();

    if (!empty($reviewTitle) && !empty($reviewComment)) {
        $insReview = $pdo->prepare("
            INSERT INTO reviews (product_id, user_id, rating, title, comment, status)
            VALUES (?, ?, ?, ?, ?, 'approved')
        ");
        $insReview->execute([$product['id'], $currentUser['id'], $rating, $reviewTitle, $reviewComment]);

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Thank you for your review. It has been published.',
                'review' => [
                    'rating' => $rating,
                    'title' => htmlspecialchars($reviewTitle, ENT_QUOTES, 'UTF-8'),
                    'comment' => htmlspecialchars($reviewComment, ENT_QUOTES, 'UTF-8'),
                    'author' => htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name'], ENT_QUOTES, 'UTF-8'),
                    'date' => date('M d, Y')
                ]
            ]);
            exit;
        }

        setFlashMessage('success', 'Thank you for your review. It has been published.');
        header('Location: ' . BASE_URL . 'product/' . urlencode($slug) . '#reviews');
        exit;
    } else {
        if ($isAjax) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Please provide both a title and comments for your review.']);
            exit;
        }
        setFlashMessage('danger', 'Please provide both a title and comments for your review.');
        header('Location: ' . BASE_URL . 'product/' . urlencode($slug) . '#reviews');
        exit;
    }
}

// Fetch Approved Reviews
$revStmt = $pdo->prepare("
    SELECT r.*, u.first_name, u.last_name
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.product_id = ? AND r.status = 'approved'
    ORDER BY r.id DESC
");
$revStmt->execute([$product['id']]);
$reviews = $revStmt->fetchAll();

$avgRating = 5.0;
if (!empty($reviews)) {
    $totalStars = array_sum(array_column($reviews, 'rating'));
    $avgRating = round($totalStars / count($reviews), 1);
}

// Fetch Related Products
$relStmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug,
           (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order ASC LIMIT 1) AS primary_image
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.category_id = ? AND p.id != ? AND p.status = 'active'
    ORDER BY p.id DESC LIMIT 4
");
$relStmt->execute([$product['category_id'], $product['id']]);
$relatedProducts = $relStmt->fetchAll();

$discount = calculateDiscountPercent((float)$product['original_price'], (float)$product['selling_price']);
$pageTitle = e($product['name']) . ' | GLAIMAGAIN';
$metaDescription = e($product['short_description'] ?: 'Luxury garment by GLAIMAGAIN');

require_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-offwhite py-3 border-bottom">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>" class="text-muted">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>shop.php" class="text-muted">Shop</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>shop.php?category=<?= urlencode($product['category_slug']) ?>" class="text-muted"><?= e($product['category_name']) ?></a></li>
                <li class="breadcrumb-item active text-emerald fw-bold" aria-current="page"><?= e($product['name']) ?></li>
            </ol>
        </nav>
    </div>
</div>

<!-- Product Details Section -->
<div class="container py-5">
    <div class="row g-5">
        <!-- 1. Left Column: Multi-Image Interactive Gallery -->
        <div class="col-lg-6">
            <div class="product-gallery-main">
                <img id="mainProductImage" src="<?= getProductImageUrl($productImages[0]['image_path']) ?>" alt="<?= e($product['name']) ?>">
                <?php if ($discount > 0): ?>
                    <span class="badge-discount"><?= $discount ?>% OFF</span>
                <?php endif; ?>
            </div>

            <?php if (count($productImages) > 1): ?>
                <div class="product-thumbnails">
                    <?php foreach ($productImages as $idx => $img): ?>
                        <div class="product-thumb-item <?= $idx === 0 ? 'active' : '' ?>" onclick="switchProductImage('<?= getProductImageUrl($img['image_path']) ?>', this)">
                            <img src="<?= getProductImageUrl($img['image_path']) ?>" alt="Thumbnail <?= $idx + 1 ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 2. Right Column: Product Information & Purchase Form -->
        <div class="col-lg-6">
            <div class="ps-lg-3">
                <div class="product-detail-sku"><?= e($product['category_name']) ?> &bull; SKU: <?= e($product['sku']) ?></div>
                <h1 class="product-detail-title"><?= e($product['name']) ?></h1>

                <!-- Rating & Reviews Bar -->
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="text-gold" style="font-size: 14px;">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?= $i <= round($avgRating) ? '' : 'text-muted' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="small text-muted">(<?= count($reviews) ?> client reviews)</span>
                    <span class="text-muted small">|</span>
                    <a href="#reviews" class="small text-gold text-decoration-underline">Read Reviews</a>
                </div>

                <!-- Price & Discount -->
                <div class="product-detail-price">
                    <span class="current"><?= formatPrice($product['selling_price']) ?></span>
                    <?php if ((float)$product['original_price'] > (float)$product['selling_price']): ?>
                        <span class="original"><?= formatPrice($product['original_price']) ?></span>
                        <span class="discount-pill"><?= $discount ?>% OFF</span>
                    <?php endif; ?>
                </div>

                <p class="text-muted mb-4" style="line-height: 1.7;"><?= nl2br(e($product['short_description'])) ?></p>

                <!-- Add to Bag Form -->
                <form id="addToCartForm" class="mb-4">
                    <?= csrfField() ?>
                    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">

                    <!-- Size Picker -->
                    <div class="variant-selector-group">
                        <div class="variant-label">
                            <span>Select Size</span>
                            <a href="#sizeGuideModal" data-bs-toggle="modal" class="text-gold small text-decoration-underline" style="text-transform: none;"><i class="fas fa-ruler me-1"></i>Size Guide</a>
                        </div>
                        <div class="variant-options">
                            <?php foreach ($availableSizes as $idx => $size): ?>
                                <label class="variant-btn <?= $idx === 0 ? 'active' : '' ?>">
                                    <input type="radio" name="size" value="<?= e($size) ?>" <?= $idx === 0 ? 'checked' : '' ?> class="d-none">
                                    <?= e($size) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Color Picker -->
                    <div class="variant-selector-group">
                        <div class="variant-label d-flex justify-content-between align-items-center">
                            <span>Select Color: <strong id="selectedColorDisplay" class="text-emerald"><?= e($availableColors[0] ?? '') ?></strong></span>
                            <span class="small text-muted" style="font-size: 11px;">Click color to preview garment</span>
                        </div>
                        <div class="variant-options">
                            <?php foreach ($availableColors as $idx => $color): 
                                $cName = trim($color);
                                $cImg = $colorImageMap[$cName] ?? ($productImages[$idx]['image_path'] ?? null ? getProductImageUrl($productImages[$idx]['image_path']) : '');
                            ?>
                                <label class="variant-btn <?= $idx === 0 ? 'active' : '' ?>" 
                                       data-color="<?= e($cName) ?>" 
                                       data-image="<?= e($cImg) ?>" 
                                       onclick="selectProductColor(this, '<?= e($cName) ?>', '<?= e($cImg) ?>')">
                                    <input type="radio" name="color" value="<?= e($cName) ?>" <?= $idx === 0 ? 'checked' : '' ?> class="d-none">
                                    <?= e($cName) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Quantity & Stock Status -->
                    <div class="row align-items-center g-3 mb-4">
                        <div class="col-auto">
                            <label class="variant-label mb-2 d-block">Quantity</label>
                            <div class="quantity-control">
                                <button type="button" class="quantity-btn" onclick="decreaseQty()"><i class="fas fa-minus" style="font-size: 11px;"></i></button>
                                <input type="text" name="quantity" id="productQuantity" class="quantity-input" value="1" data-min="1" data-max="<?= max(1, (int)$product['stock']) ?>" readonly>
                                <button type="button" class="quantity-btn" onclick="increaseQty()"><i class="fas fa-plus" style="font-size: 11px;"></i></button>
                            </div>
                        </div>
                        <div class="col">
                            <label class="variant-label mb-2 d-block">&nbsp;</label>
                            <?php if ((int)$product['stock'] > 0): ?>
                                <div class="text-success small fw-bold">
                                    <i class="fas fa-check-circle text-gold me-1"></i> In Stock (<?= (int)$product['stock'] ?> units ready for bespoke dispatch)
                                </div>
                            <?php else: ?>
                                <div class="text-danger small fw-bold">
                                    <i class="fas fa-times-circle me-1"></i> Currently Out of Stock
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex flex-column flex-sm-row gap-3">
                        <button type="submit" class="btn btn-luxury-primary flex-grow-1" <?= (int)$product['stock'] <= 0 ? 'disabled' : '' ?>>
                            <i class="fas fa-shopping-bag"></i> ADD TO SHOPPING BAG
                        </button>
                        <button type="button" class="btn btn-luxury-gold flex-grow-1" onclick="buyNowDirect()" <?= (int)$product['stock'] <= 0 ? 'disabled' : '' ?>>
                            <i class="fas fa-bolt"></i> BUY NOW
                        </button>
                    </div>
                </form>

                <!-- Value Highlights Accordion -->
                <div class="accordion accordion-flush border-top pt-3" id="productAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold text-emerald" type="button" data-bs-toggle="collapse" data-bs-target="#accDescription">
                                <i class="fas fa-feather-alt text-gold me-2"></i> Craftsmanship &amp; Details
                            </button>
                        </h2>
                        <div id="accDescription" class="accordion-collapse collapse" data-bs-parent="#productAccordion">
                            <div class="accordion-body small text-muted">
                                <?= $product['detailed_description'] ?: nl2br(e($product['short_description'])) ?>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold text-emerald" type="button" data-bs-toggle="collapse" data-bs-target="#accShipping">
                                <i class="fas fa-shipping-fast text-gold me-2"></i> Delivery &amp; Returns
                            </button>
                        </h2>
                        <div id="accShipping" class="accordion-collapse collapse" data-bs-parent="#productAccordion">
                            <div class="accordion-body small text-muted">
                                <p>• Complimentary express shipping on all orders exceeding <?= formatPrice(getSetting('free_shipping_threshold', (string)FREE_SHIPPING_THRESHOLD)) ?>.</p>
                                <p>• Hand-packed in signature emerald velvet keepsake boxes with gold monogram tissue.</p>
                                <p>• 7-day hassle-free size exchange and returns policy.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- Ends Product Main Row -->

    <!-- Client Reviews Section (Inside Central Luxury Container for Desktop & Mobile) -->
    <div class="mt-5 pt-5 border-top" id="reviews">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-7">
                <h3 class="fw-bold text-emerald mb-4">CLIENT REVIEWS (<?= count($reviews) ?>)</h3>
                <?php if (empty($reviews)): ?>
                    <div class="p-4 bg-offwhite rounded border text-muted small">
                        No reviews yet for this garment. Be the first to share your experience with fellow patrons.
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($reviews as $r): ?>
                            <div class="p-4 bg-white border border-gold-subtle rounded shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="text-gold">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?= $i <= (int)$r['rating'] ? '' : 'text-muted' ?>" style="font-size: 12px;"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-muted small"><?= date('M d, Y', strtotime($r['created_at'])) ?></span>
                                </div>
                                <h6 class="fw-bold text-emerald mb-1"><?= e($r['title']) ?></h6>
                                <p class="text-muted small mb-2"><?= e($r['comment']) ?></p>
                                <div class="small text-muted">
                                    <i class="fas fa-user-check text-gold me-1"></i><?= e($r['first_name'] . ' ' . $r['last_name']) ?> <span class="badge bg-emerald text-gold ms-1">Verified Patron</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-5">
                <div class="p-4 bg-offwhite border border-gold-subtle rounded shadow-sm">
                    <h5 class="fw-bold text-emerald mb-3">LEAVE A REVIEW</h5>
                    <?php if (isUserLoggedIn()): ?>
                        <form method="POST" action="<?= BASE_URL ?>product/<?= urlencode($slug) ?>#reviews" id="reviewForm">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="submit_review">

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-emerald">Rating</label>
                                <select name="rating" class="form-select form-select-sm" required>
                                    <option value="5">★★★★★ (5 Stars - Bespoke Excellence)</option>
                                    <option value="4">★★★★☆ (4 Stars - High Quality)</option>
                                    <option value="3">★★★☆☆ (3 Stars - Average)</option>
                                    <option value="2">★★☆☆☆ (2 Stars - Below Expectation)</option>
                                    <option value="1">★☆☆☆☆ (1 Star - Poor)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-emerald">Review Headline</label>
                                <input type="text" name="title" class="form-control form-control-sm" placeholder="e.g. Impeccable drape & fabric" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-emerald">Comments</label>
                                <textarea name="comment" rows="3" class="form-control form-control-sm" placeholder="Describe the fit, tactile feel, and finishing..." required></textarea>
                            </div>

                            <button type="submit" class="btn btn-luxury-primary w-100 py-2">
                                SUBMIT VERIFIED REVIEW
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-lock text-gold fs-3 mb-2"></i>
                            <p class="text-muted small mb-3">Please sign in to your GLAIMAGAIN account to submit a client review.</p>
                            <a href="<?= BASE_URL ?>login.php" class="btn btn-luxury-primary btn-sm">SIGN IN</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div> <!-- Ends #reviews -->

    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
        <div class="mt-5 pt-5 border-top">
            <div class="text-center mb-5">
                <span class="text-gold fw-bold letter-spacing-3 small text-uppercase">Complete the Look</span>
                <h3 class="display-6 fw-bold text-emerald mt-1">COMPLEMENTARY PIECES</h3>
                <div class="gold-divider"><i class="fas fa-crown"></i></div>
            </div>

            <div class="row g-4">
                <?php foreach ($relatedProducts as $rel): 
                    $relDiscount = calculateDiscountPercent((float)$rel['original_price'], (float)$rel['selling_price']);
                ?>
                    <div class="col-lg-3 col-md-6 col-6">
                        <div class="product-card">
                            <div class="product-card-img-wrap">
                                <?php if ($relDiscount > 0): ?>
                                    <span class="badge-discount"><?= $relDiscount ?>% OFF</span>
                                <?php endif; ?>
                                <a href="<?= BASE_URL ?>product.php?slug=<?= urlencode($rel['slug']) ?>">
                                    <img src="<?= getProductImageUrl($rel['primary_image']) ?>" alt="<?= e($rel['name']) ?>" class="product-card-img" loading="lazy">
                                </a>
                            </div>
                            <div class="product-card-body">
                                <div class="product-card-category"><?= e($rel['category_name']) ?></div>
                                <h3 class="product-card-title">
                                    <a href="<?= BASE_URL ?>product.php?slug=<?= urlencode($rel['slug']) ?>"><?= e($rel['name']) ?></a>
                                </h3>
                                <div class="product-card-price">
                                    <span class="price-current"><?= formatPrice($rel['selling_price']) ?></span>
                                </div>
                                <div class="product-card-action">
                                    <a href="<?= BASE_URL ?>product.php?slug=<?= urlencode($rel['slug']) ?>" class="btn btn-luxury-primary">
                                        <i class="fas fa-eye me-1"></i> VIEW
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Size Guide Modal -->
<div class="modal fade" id="sizeGuideModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-gold shadow-lg" style="border: 2px solid #B99036;">
            <div class="modal-header bg-emerald text-white">
                <h5 class="modal-title font-serif"><i class="fas fa-ruler text-gold me-2"></i> GLAIMAGAIN SIZE GUIDE</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <table class="table table-bordered text-center align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Size</th>
                            <th>Chest (Inches)</th>
                            <th>Shoulder (Inches)</th>
                            <th>Length (Inches)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><strong>S</strong></td><td>38 - 40</td><td>18.5</td><td>28.5</td></tr>
                        <tr><td><strong>M</strong></td><td>40 - 42</td><td>19.5</td><td>29.5</td></tr>
                        <tr><td><strong>L</strong></td><td>42 - 44</td><td>20.5</td><td>30.5</td></tr>
                        <tr><td><strong>XL</strong></td><td>44 - 46</td><td>21.5</td><td>31.5</td></tr>
                        <tr><td><strong>XXL</strong></td><td>46 - 48</td><td>22.5</td><td>32.5</td></tr>
                    </tbody>
                </table>
                <p class="small text-muted mb-0"><i class="fas fa-info-circle text-gold me-1"></i> Measurements are for standard relaxed garment fit. If you prefer a tailored fit, choose one size down.</p>
            </div>
        </div>
    </div>
</div>

<script>

// Dynamic Color Switcher: updates selection and swaps main image with smooth fade
function selectProductColor(labelEl, colorName, imageUrl) {
    document.querySelectorAll('.variant-selector-group .variant-btn input[name="color"]').forEach(input => {
        input.closest('.variant-btn').classList.remove('active');
    });
    labelEl.classList.add('active');
    const radio = labelEl.querySelector('input');
    if (radio) radio.checked = true;

    const displayEl = document.getElementById('selectedColorDisplay');
    if (displayEl) displayEl.textContent = colorName;

    if (imageUrl) {
        const mainImg = document.getElementById('mainProductImage');
        if (mainImg) {
            mainImg.style.transition = 'opacity 0.2s ease';
            mainImg.style.opacity = '0.3';
            setTimeout(() => {
                mainImg.src = imageUrl;
                mainImg.style.opacity = '1';
            }, 180);
        }

        // Also highlight matching thumbnail if exists
        document.querySelectorAll('.product-thumb-item img').forEach(tImg => {
            if (tImg.src === imageUrl) {
                document.querySelectorAll('.product-thumb-item').forEach(el => el.classList.remove('active'));
                tImg.closest('.product-thumb-item').classList.add('active');
            }
        });
    }
}

function switchProductImage(src, thumbElement) {
    document.getElementById('mainProductImage').src = src;
    document.querySelectorAll('.product-thumb-item').forEach(el => el.classList.remove('active'));
    thumbElement.classList.add('active');
}

function increaseQty() {
    const input = document.getElementById('productQuantity');
    const max = parseInt(input.getAttribute('data-max') || input.getAttribute('max') || '99', 10);
    let val = parseInt(input.value, 10) || 1;
    if (val < max) {
        input.value = val + 1;
    }
}

function decreaseQty() {
    const input = document.getElementById('productQuantity');
    let val = parseInt(input.value, 10) || 1;
    if (val > 1) {
        input.value = val - 1;
    }
}

// Handle radio option visual highlighting
document.querySelectorAll('.variant-btn input').forEach(radio => {
    radio.addEventListener('change', (e) => {
        const parent = radio.closest('.variant-options');
        parent.querySelectorAll('.variant-btn').forEach(btn => btn.classList.remove('active'));
        radio.closest('.variant-btn').classList.add('active');
    });
});

async function buyNowDirect() {
    const form = document.getElementById('addToCartForm');
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.click();
    setTimeout(() => {
        window.location.href = "<?= BASE_URL ?>checkout.php";
    }, 600);
}

// AJAX Review Form Handling (Zero page reload / redirection glitches on mobile)
const reviewForm = document.getElementById('reviewForm');
if (reviewForm) {
    reviewForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const submitBtn = reviewForm.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> SUBMITTING...';

        const formData = new FormData(reviewForm);
        formData.append('is_ajax', '1');

        try {
            const resp = await fetch(reviewForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await resp.json();

            if (data.success) {
                if (typeof showToast === 'function') {
                    showToast(data.message, 'success');
                } else {
                    alert(data.message);
                }
                reviewForm.reset();

                // Dynamically append new review card
                const reviewsContainer = document.querySelector('#reviews .d-flex.flex-column');
                if (reviewsContainer && data.review) {
                    let starsHtml = '';
                    for (let i = 1; i <= 5; i++) {
                        starsHtml += `<i class="fas fa-star ${i <= data.review.rating ? '' : 'text-muted'}" style="font-size: 12px;"></i> `;
                    }
                    const newCard = document.createElement('div');
                    newCard.className = 'p-4 bg-white border border-gold-subtle rounded shadow-sm mb-3 animate__animated animate__fadeIn';
                    newCard.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="text-gold">${starsHtml}</div>
                            <span class="text-muted small">${data.review.date}</span>
                        </div>
                        <h6 class="fw-bold text-emerald mb-1">${data.review.title}</h6>
                        <p class="text-muted small mb-2">${data.review.comment}</p>
                        <div class="small text-muted">
                            <i class="fas fa-user-check text-gold me-1"></i>${data.review.author} <span class="badge bg-emerald text-gold ms-1">Verified Patron</span>
                        </div>
                    `;
                    reviewsContainer.prepend(newCard);
                }
            } else {
                if (typeof showToast === 'function') {
                    showToast(data.message || 'Error submitting review.', 'error');
                } else {
                    alert(data.message || 'Error submitting review.');
                }
            }
        } catch (err) {
            console.error('Review submit error:', err);
            // Fallback to normal submit if network error occurs
            reviewForm.submit();
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

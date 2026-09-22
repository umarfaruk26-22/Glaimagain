<?php
/**
 * GLAIMAGAIN - Shop Catalog Page (Rozelio Modern Clean Layout)
 */
require_once __DIR__ . '/config/config.php';

$pdo = getDb();

// 1. Capture Filter Parameters
$categorySlug = trim($_GET['category'] ?? '');
$searchQuery = trim($_GET['q'] ?? '');
$minPrice = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : 50000;
$selectedSize = trim($_GET['size'] ?? '');
$selectedColor = trim($_GET['color'] ?? '');
$inStockOnly = !empty($_GET['in_stock']);
$sortBy = trim($_GET['sort'] ?? 'newest');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

// 2. Build Query
$whereClauses = ["p.status = 'active'"];
$params = [];

$currentCategory = null;
if (!empty($categorySlug)) {
    $catStmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ? AND status = 'active' LIMIT 1");
    $catStmt->execute([$categorySlug]);
    $currentCategory = $catStmt->fetch();

    if ($currentCategory) {
        $whereClauses[] = "p.category_id = ?";
        $params[] = $currentCategory['id'];
    }
}

if (!empty($searchQuery)) {
    $whereClauses[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.short_description LIKE ? OR c.name LIKE ?)";
    $term = "%{$searchQuery}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if ($minPrice > 0) {
    $whereClauses[] = "p.selling_price >= ?";
    $params[] = $minPrice;
}
if ($maxPrice < 50000) {
    $whereClauses[] = "p.selling_price <= ?";
    $params[] = $maxPrice;
}
if (!empty($selectedSize)) {
    $whereClauses[] = "FIND_IN_SET(?, p.sizes) > 0";
    $params[] = $selectedSize;
}
if (!empty($selectedColor)) {
    $whereClauses[] = "p.colors LIKE ?";
    $params[] = "%{$selectedColor}%";
}
if ($inStockOnly) {
    $whereClauses[] = "p.stock > 0";
}

$orderBy = "p.id DESC";
switch ($sortBy) {
    case 'price_low':
        $orderBy = "p.selling_price ASC";
        break;
    case 'price_high':
        $orderBy = "p.selling_price DESC";
        break;
    case 'best_selling':
        $orderBy = "p.is_best_seller DESC, p.id DESC";
        break;
    case 'popular':
        $orderBy = "p.views_count DESC, p.id DESC";
        break;
    case 'newest':
    default:
        $orderBy = "p.id DESC";
        break;
}

$whereSql = implode(" AND ", $whereClauses);

// Count
$countSql = "SELECT COUNT(DISTINCT p.id) FROM products p JOIN categories c ON p.category_id = c.id WHERE {$whereSql}";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalProducts = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $limit);

// Fetch
$productsSql = "
    SELECT p.*, c.name AS category_name, c.slug AS category_slug,
           (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order ASC LIMIT 1) AS primary_image
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE {$whereSql}
    ORDER BY {$orderBy}
    LIMIT {$limit} OFFSET {$offset}
";
$prodStmt = $pdo->prepare($productsSql);
$prodStmt->execute($params);
$products = $prodStmt->fetchAll();

$allCategories = getActiveCategories();

$pageTitle = ($currentCategory ? e($currentCategory['name']) . ' Collection | ' : '') . 'Shop All — GLAIMAGAIN';
$metaDescription = $currentCategory['description'] ?? 'Browse the complete GLAIMAGAIN luxury collection.';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Shop Header Banner (Rozelio Clean Style) -->
<div class="bg-mint-light py-5 border-bottom border-mint">
    <div class="container text-center">
        <span class="badge bg-mint text-forest fw-bold px-3 py-2 rounded-pill small mb-2 letter-spacing-1">
            <?= $currentCategory ? 'COLLECTION' : 'ALL SILHOUETTES' ?>
        </span>
        <h1 class="display-5 fw-bold text-forest mt-1"><?= $currentCategory ? e($currentCategory['name']) : 'Our Products' ?></h1>
        <?php if ($currentCategory && !empty($currentCategory['description'])): ?>
            <p class="text-muted max-w-600 mx-auto small"><?= e($currentCategory['description']) ?></p>
        <?php endif; ?>

        <!-- Category Pill Bar -->
        <div class="filter-tabs-pills mt-4 mb-0">
            <a href="<?= BASE_URL ?>shop.php" class="filter-tab-btn <?= empty($categorySlug) ? 'active' : '' ?>">ALL</a>
            <?php foreach ($allCategories as $cat): ?>
                <a href="<?= BASE_URL ?>shop.php?category=<?= urlencode($cat['slug']) ?>" class="filter-tab-btn <?= ($categorySlug === $cat['slug']) ? 'active' : '' ?>">
                    <?= strtoupper(e($cat['name'])) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">
        <!-- 1. Filters Sidebar -->
        <div class="col-lg-3">
            <button class="btn btn-pill-outline-forest w-100 mb-3 d-lg-none justify-content-center py-2" type="button" data-bs-toggle="collapse" data-bs-target="#filterSidebarCollapse" aria-expanded="false" aria-controls="filterSidebarCollapse">
                <i class="fas fa-sliders-h me-2"></i>Filter &amp; Refine Garments
            </button>

            <div class="collapse d-lg-block" id="filterSidebarCollapse">
                <div class="p-4 bg-white border border-mint rounded-4 shadow-sm mb-4 mb-lg-0">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-forest mb-0"><i class="fas fa-sliders-h text-gold me-2"></i>Filter Products</h6>
                        <a href="<?= BASE_URL ?>shop.php" class="text-forest small text-decoration-underline">Reset</a>
                    </div>
                    <hr class="border-mint">

                    <form method="GET" action="<?= BASE_URL ?>shop.php">
                        <?php if (!empty($searchQuery)): ?>
                            <input type="hidden" name="q" value="<?= e($searchQuery) ?>">
                        <?php endif; ?>

                        <?php if (!empty($categorySlug)): ?>
                            <input type="hidden" name="category" value="<?= e($categorySlug) ?>">
                        <?php endif; ?>

                        <!-- Price Range -->
                        <div class="mb-4">
                            <label class="fw-bold small text-forest text-uppercase letter-spacing-1 mb-2 d-block">Price Range</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" name="min_price" class="form-control form-control-sm rounded-pill" placeholder="Min ₹" value="<?= $minPrice > 0 ? e($minPrice) : '' ?>">
                                </div>
                                <div class="col-6">
                                    <input type="number" name="max_price" class="form-control form-control-sm rounded-pill" placeholder="Max ₹" value="<?= $maxPrice < 50000 ? e($maxPrice) : '' ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Size Filter -->
                        <div class="mb-4">
                            <label class="fw-bold small text-forest text-uppercase letter-spacing-1 mb-2 d-block">Size</label>
                            <select name="size" class="form-select form-select-sm rounded-pill">
                                <option value="">All Sizes</option>
                                <?php foreach (['S', 'M', 'L', 'XL', 'XXL', '38', '40', '42', '44', '30', '32', '34', '36'] as $sz): ?>
                                    <option value="<?= $sz ?>" <?= $selectedSize === $sz ? 'selected' : '' ?>><?= $sz ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Color Filter -->
                        <div class="mb-4">
                            <label class="fw-bold small text-forest text-uppercase letter-spacing-1 mb-2 d-block">Color</label>
                            <select name="color" class="form-select form-select-sm rounded-pill">
                                <option value="">All Colors</option>
                                <?php foreach (['Emerald', 'Gold', 'Black', 'White', 'Navy', 'Indigo', 'Cognac'] as $clr): ?>
                                    <option value="<?= $clr ?>" <?= $selectedColor === $clr ? 'selected' : '' ?>><?= $clr ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Stock Status -->
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="in_stock" value="1" id="inStockCheck" <?= $inStockOnly ? 'checked' : '' ?>>
                            <label class="form-check-label small text-muted" for="inStockCheck">
                                In Stock Only
                            </label>
                        </div>

                        <button type="submit" class="btn-pill-forest w-100 py-2 justify-content-center">
                            APPLY FILTERS
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- 2. Product Grid & Controls -->
        <div class="col-lg-9">
            <!-- Top Controls -->
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center pb-3 mb-4 border-bottom border-mint">
                <div class="text-muted small mb-2 mb-sm-0">
                    Showing <strong class="text-forest"><?= count($products) ?></strong> of <strong class="text-forest"><?= $totalProducts ?></strong> items
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label class="small text-muted text-nowrap">Sort By:</label>
                    <select class="form-select form-select-sm rounded-pill border-mint" onchange="location = this.value;">
                        <?php
                        $sortUrl = function($type) use ($categorySlug, $searchQuery, $minPrice, $maxPrice, $selectedSize, $selectedColor, $inStockOnly) {
                            $q = http_build_query(array_filter([
                                'category' => $categorySlug,
                                'q' => $searchQuery,
                                'min_price' => $minPrice ?: null,
                                'max_price' => $maxPrice < 50000 ? $maxPrice : null,
                                'size' => $selectedSize,
                                'color' => $selectedColor,
                                'in_stock' => $inStockOnly ? 1 : null,
                                'sort' => $type
                            ]));
                            return BASE_URL . 'shop.php?' . $q;
                        };
                        ?>
                        <option value="<?= $sortUrl('newest') ?>" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Newest First</option>
                        <option value="<?= $sortUrl('price_low') ?>" <?= $sortBy === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="<?= $sortUrl('price_high') ?>" <?= $sortBy === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="<?= $sortUrl('best_selling') ?>" <?= $sortBy === 'best_selling' ? 'selected' : '' ?>>Best Selling</option>
                        <option value="<?= $sortUrl('popular') ?>" <?= $sortBy === 'popular' ? 'selected' : '' ?>>Popularity</option>
                    </select>
                </div>
            </div>

            <!-- Products Grid (Rozelio Style) -->
            <?php if (empty($products)): ?>
                <div class="text-center py-5 my-5 bg-mint-light border border-mint rounded-4 p-5">
                    <i class="fas fa-tshirt text-forest fs-1 mb-3"></i>
                    <h4 class="text-forest fw-bold">No Products Found</h4>
                    <p class="text-muted small max-w-500 mx-auto mb-4">No garments matched your selected filter criteria. Try adjusting your filters.</p>
                    <a href="<?= BASE_URL ?>shop.php" class="btn-pill-forest">BROWSE ALL</a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($products as $p): 
                        $discount = calculateDiscountPercent((float)$p['original_price'], (float)$p['selling_price']);
                    ?>
                        <div class="col-md-4 col-6">
                            <div class="product-card-clean">
                                <div class="product-card-clean-img-wrap">
                                    <?php if ($discount > 0): ?>
                                        <span class="badge-discount-pill"><?= $discount ?>% OFF</span>
                                    <?php endif; ?>
                                    <?php if ((int)$p['stock'] <= 0): ?>
                                        <div class="badge-out-of-stock" style="border-radius: 10px 10px 0 0;">OUT OF STOCK</div>
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

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-5 d-flex justify-content-center">
                        <ul class="pagination gap-1">
                            <?php for ($i = 1; $i <= $totalPages; $i++): 
                                $pageQuery = array_merge($_GET, ['page' => $i]);
                            ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link rounded-circle d-flex align-items-center justify-content-center <?= $i === $page ? 'bg-forest text-white border-forest' : 'text-forest' ?>" style="width: 40px; height: 40px;" href="<?= BASE_URL ?>shop.php?<?= http_build_query($pageQuery) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

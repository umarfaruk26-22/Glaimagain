<?php
/**
 * GLAIMAGAIN - Admin Edit Product
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();
$productId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    setFlashMessage('danger', 'Garment not found.');
    header('Location: ' . BASE_URL . 'admin/products');
    exit;
}

$categories = getActiveCategories();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $name = trim($_POST['name'] ?? '');
    $slug = slugify(trim($_POST['slug'] ?? '') ?: $name);
    $sku = strtoupper(trim($_POST['sku'] ?? ''));
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $shortDesc = trim($_POST['short_description'] ?? '');
    $detailedDesc = trim($_POST['detailed_description'] ?? '');
    $originalPrice = (float)($_POST['original_price'] ?? 0);
    $sellingPrice = (float)($_POST['selling_price'] ?? 0);
    $stock = max(0, (int)($_POST['stock'] ?? 0));
    $sizes = trim($_POST['sizes'] ?? 'S,M,L,XL');
    $colors = trim($_POST['colors'] ?? 'Emerald Green,Midnight Black');
    $isFeatured = !empty($_POST['is_featured']) ? 1 : 0;
    $isNewArrival = !empty($_POST['is_new_arrival']) ? 1 : 0;
    $isBestSeller = !empty($_POST['is_best_seller']) ? 1 : 0;
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

    if (empty($name) || empty($sku) || $categoryId <= 0) {
        $errors[] = 'Product Name, SKU, and Category are required.';
    }

    if ($originalPrice <= 0 || $sellingPrice <= 0) {
        $errors[] = 'Original Price and Selling Price must be positive.';
    }

    if ($sellingPrice > $originalPrice) {
        $errors[] = 'Selling Price cannot exceed Original Price.';
    }

    // SKU uniqueness check
    $chkSku = $pdo->prepare("SELECT id FROM products WHERE sku = ? AND id != ? LIMIT 1");
    $chkSku->execute([$sku, $product['id']]);
    if ($chkSku->fetch()) {
        $errors[] = 'SKU already assigned to another garment.';
    }

    // Slug uniqueness check
    $chkSlug = $pdo->prepare("SELECT id FROM products WHERE slug = ? AND id != ? LIMIT 1");
    $chkSlug->execute([$slug, $product['id']]);
    if ($chkSlug->fetch()) {
        $slug = $slug . '-' . time();
    }

    if (empty($errors)) {
        $upd = $pdo->prepare("
            UPDATE products 
            SET category_id = ?, name = ?, slug = ?, sku = ?, short_description = ?, detailed_description = ?,
                original_price = ?, selling_price = ?, stock = ?, sizes = ?, colors = ?, status = ?,
                is_featured = ?, is_new_arrival = ?, is_best_seller = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $upd->execute([
            $categoryId, $name, $slug, $sku, $shortDesc, $detailedDesc,
            $originalPrice, $sellingPrice, $stock, $sizes, $colors, $status,
            $isFeatured, $isNewArrival, $isBestSeller, $product['id']
        ]);

        setFlashMessage('success', "Garment '{$name}' updated successfully.");
        header('Location: ' . BASE_URL . 'admin/products');
        exit;
    }
}

$adminHeaderHeading = 'Edit Product: ' . e($product['name']);
$adminTitle = 'Edit Product | GLAIMAGAIN Admin';
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="admin-card p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold text-emerald mb-0">Modify Garment Details</h5>
                    <small class="text-muted">SKU: <?= e($product['sku']) ?></small>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL ?>admin/products/images.php?id=<?= $product['id'] ?>" class="btn btn-outline-dark btn-sm">
                        <i class="fas fa-images text-gold me-1"></i> Manage Images
                    </a>
                    <a href="<?= BASE_URL ?>admin/products" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back to Products
                    </a>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger small py-2 mb-4">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>admin/products/edit?id=<?= $product['id'] ?>" class="form-luxury">
                <?= csrfField() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Garment Name <span class="text-gold">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= e($product['name']) ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">SKU <span class="text-gold">*</span></label>
                        <input type="text" name="sku" class="form-control" value="<?= e($product['sku']) ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Category <span class="text-gold">*</span></label>
                        <select name="category_id" class="form-select" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">URL Slug</label>
                        <input type="text" name="slug" class="form-control" value="<?= e($product['slug']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Active (Visible in Store)</option>
                            <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden)</option>
                        </select>
                    </div>

                    <!-- Pricing & Live Discount Engine -->
                    <div class="col-12 p-3 bg-offwhite border rounded my-3">
                        <h6 class="fw-bold text-emerald text-uppercase letter-spacing-1 mb-3"><i class="fas fa-tag text-gold me-2"></i>Pricing &amp; Discount Configuration</h6>
                        <div class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <label class="form-label">Original Price (₹) <span class="text-gold">*</span></label>
                                <input type="number" step="0.01" name="original_price" id="original_price" class="form-control" value="<?= e($product['original_price']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Selling Price (₹) <span class="text-gold">*</span></label>
                                <input type="number" step="0.01" name="selling_price" id="selling_price" class="form-control" value="<?= e($product['selling_price']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label d-block">Live Calculated Discount</label>
                                <div id="discount_preview" class="pt-1"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Stock & Variants -->
                    <div class="col-md-4">
                        <label class="form-label">Stock Units <span class="text-gold">*</span></label>
                        <input type="number" name="stock" class="form-control" value="<?= (int)$product['stock'] ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Sizes (Comma-separated)</label>
                        <input type="text" name="sizes" class="form-control" value="<?= e($product['sizes']) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Colors (Comma-separated)</label>
                        <input type="text" name="colors" class="form-control" value="<?= e($product['colors']) ?>">
                    </div>

                    <!-- Descriptions -->
                    <div class="col-12">
                        <label class="form-label">Short Description</label>
                        <textarea name="short_description" rows="2" class="form-control"><?= e($product['short_description']) ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Detailed Description &amp; Fabric Specs (HTML)</label>
                        <textarea name="detailed_description" rows="5" class="form-control"><?= e($product['detailed_description']) ?></textarea>
                    </div>

                    <!-- Badges -->
                    <div class="col-12 pt-2">
                        <div class="d-flex flex-wrap gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="featCheck" <?= $product['is_featured'] ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="featCheck">Featured in Spotlight</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_new_arrival" value="1" id="newCheck" <?= $product['is_new_arrival'] ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="newCheck">Mark as New Arrival</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_best_seller" value="1" id="bestCheck" <?= $product['is_best_seller'] ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="bestCheck">Mark as Icon / Best Seller</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-admin-primary">
                            <i class="fas fa-save me-2"></i> UPDATE GARMENT
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/admin-footer.php'; ?>

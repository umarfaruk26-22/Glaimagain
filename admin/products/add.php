<?php
/**
 * GLAIMAGAIN - Admin Add Product
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();
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
    if (!empty($_POST['color_names'])) {
        $parsedColors = array_filter(array_map('trim', $_POST['color_names']));
        if (!empty($parsedColors)) {
            $colors = implode(',', $parsedColors);
        }
    }
    $isFeatured = !empty($_POST['is_featured']) ? 1 : 0;
    $isNewArrival = !empty($_POST['is_new_arrival']) ? 1 : 0;
    $isBestSeller = !empty($_POST['is_best_seller']) ? 1 : 0;
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

    // Validation
    if (empty($name) || empty($sku) || $categoryId <= 0) {
        $errors[] = 'Product Name, SKU, and Category are required.';
    }

    if ($originalPrice <= 0 || $sellingPrice <= 0) {
        $errors[] = 'Original Price and Selling Price must be positive values.';
    }

    if ($sellingPrice > $originalPrice) {
        $errors[] = 'Selling Price cannot exceed Original Price.';
    }

    // SKU uniqueness
    $chkSku = $pdo->prepare("SELECT id FROM products WHERE sku = ? LIMIT 1");
    $chkSku->execute([$sku]);
    if ($chkSku->fetch()) {
        $errors[] = 'SKU already exists. Please assign a unique SKU.';
    }

    // Slug uniqueness
    $chkSlug = $pdo->prepare("SELECT id FROM products WHERE slug = ? LIMIT 1");
    $chkSlug->execute([$slug]);
    if ($chkSlug->fetch()) {
        $slug = $slug . '-' . time();
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO products (
                    category_id, name, slug, sku, short_description, detailed_description,
                    original_price, selling_price, stock, sizes, colors, status,
                    is_featured, is_new_arrival, is_best_seller
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $categoryId, $name, $slug, $sku, $shortDesc, $detailedDesc,
                $originalPrice, $sellingPrice, $stock, $sizes, $colors, $status,
                $isFeatured, $isNewArrival, $isBestSeller
            ]);
            $productId = (int)$pdo->lastInsertId();

                        // 1. Handle Color-Specific Photo Uploads
            $hasPrimary = false;
            if (!empty($_POST['color_names'])) {
                foreach ($_POST['color_names'] as $idx => $cName) {
                    $cName = trim($cName);
                    if (empty($cName)) continue;

                    if (isset($_FILES['color_images']['name'][$idx]) && $_FILES['color_images']['error'][$idx] === UPLOAD_ERR_OK) {
                        $singleFile = [
                            'name'     => $_FILES['color_images']['name'][$idx],
                            'type'     => $_FILES['color_images']['type'][$idx],
                            'tmp_name' => $_FILES['color_images']['tmp_name'][$idx],
                            'error'    => $_FILES['color_images']['error'][$idx],
                            'size'     => $_FILES['color_images']['size'][$idx],
                        ];
                        $upload = handleImageUpload($singleFile, 'products');
                        if ($upload['success']) {
                            $isPrimary = (!$hasPrimary) ? 1 : 0;
                            $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary, color, sort_order) VALUES (?, ?, ?, ?, ?)")
                                ->execute([$productId, $upload['path'], $isPrimary, $cName, $idx]);
                            $hasPrimary = true;
                        }
                    }
                }
            }

            // 2. Handle Additional General Gallery Images
            if (!empty($_FILES['product_images']['name'][0])) {
                $fileCount = count($_FILES['product_images']['name']);
                for ($i = 0; $i < $fileCount; $i++) {
                    if ($_FILES['product_images']['error'][$i] === UPLOAD_ERR_OK) {
                        $singleFile = [
                            'name'     => $_FILES['product_images']['name'][$i],
                            'type'     => $_FILES['product_images']['type'][$i],
                            'tmp_name' => $_FILES['product_images']['tmp_name'][$i],
                            'error'    => $_FILES['product_images']['error'][$i],
                            'size'     => $_FILES['product_images']['size'][$i],
                        ];
                        $upload = handleImageUpload($singleFile, 'products');
                        if ($upload['success']) {
                            $isPrimary = (!$hasPrimary) ? 1 : 0;
                            $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary, color, sort_order) VALUES (?, ?, ?, NULL, ?)")
                                ->execute([$productId, $upload['path'], $isPrimary, 10 + $i]);
                            $hasPrimary = true;
                        }
                    }
                }
            }

            // Fallback placeholder image if none uploaded
            if (!$hasPrimary) {
                $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary, sort_order) VALUES (?, 'assets/images/placeholder-product.svg', 1, 0)")
                    ->execute([$productId]);
            }

            // Generate Variants Matrix automatically from sizes and colors
            $sizeArray = array_filter(array_map('trim', explode(',', $sizes)));
            $colorArray = array_filter(array_map('trim', explode(',', $colors)));
            $varIns = $pdo->prepare("INSERT INTO product_variants (product_id, size, color, sku, stock, additional_price, status) VALUES (?, ?, ?, ?, ?, 0.00, 'active')");

            $stockPerVariant = (int)ceil($stock / max(1, count($sizeArray) * count($colorArray)));
            foreach ($sizeArray as $s) {
                foreach ($colorArray as $c) {
                    $vSku = $sku . '-' . strtoupper(substr($s, 0, 3)) . '-' . strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $c), 0, 3));
                    $varIns->execute([$productId, $s, $c, $vSku, $stockPerVariant]);
                }
            }

            $pdo->commit();

            setFlashMessage('success', "Garment '{$name}' added to catalog successfully.");
            header('Location: ' . BASE_URL . 'admin/products');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Database Error: ' . $e->getMessage();
        }
    }
}

$adminHeaderHeading = 'Add New Product';
$adminTitle = 'Add Product | GLAIMAGAIN Admin';
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="admin-card p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold text-emerald mb-0">Commission New Garment to Catalog</h5>
                <a href="<?= BASE_URL ?>admin/products" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Back to Products
                </a>
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

            <form method="POST" action="<?= BASE_URL ?>admin/products/add" enctype="multipart/form-data" class="form-luxury">
                <?= csrfField() ?>
                <div class="row g-3">
                    <!-- Basic Info -->
                    <div class="col-md-6">
                        <label class="form-label">Garment Name <span class="text-gold">*</span></label>
                        <input type="text" name="name" id="product_name" class="form-control" placeholder="e.g. Signature Emerald Heavyweight Tee" value="<?= isset($_POST['name']) ? e($_POST['name']) : '' ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Unique SKU <span class="text-gold">*</span></label>
                        <input type="text" name="sku" class="form-control" placeholder="e.g. GLA-TSH-008" value="<?= isset($_POST['sku']) ? e($_POST['sku']) : '' ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Category <span class="text-gold">*</span></label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Select Category...</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">URL Slug (Auto-generated if left blank)</label>
                        <input type="text" name="slug" id="product_slug" class="form-control" placeholder="signature-emerald-heavyweight-tee" value="<?= isset($_POST['slug']) ? e($_POST['slug']) : '' ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active">Active (Visible in Store)</option>
                            <option value="inactive">Inactive (Hidden)</option>
                        </select>
                    </div>

                    <!-- Pricing & Live Discount Engine -->
                    <div class="col-12 p-3 bg-offwhite border rounded my-3">
                        <h6 class="fw-bold text-emerald text-uppercase letter-spacing-1 mb-3"><i class="fas fa-tag text-gold me-2"></i>Pricing &amp; Discount Configuration</h6>
                        <div class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <label class="form-label">Original Price (₹) <span class="text-gold">*</span></label>
                                <input type="number" step="0.01" name="original_price" id="original_price" class="form-control" placeholder="2499.00" value="<?= isset($_POST['original_price']) ? e($_POST['original_price']) : '' ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Selling Price (₹) <span class="text-gold">*</span></label>
                                <input type="number" step="0.01" name="selling_price" id="selling_price" class="form-control" placeholder="1499.00" value="<?= isset($_POST['selling_price']) ? e($_POST['selling_price']) : '' ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label d-block">Automatic Discount Preview</label>
                                <div id="discount_preview" class="pt-1">
                                    <span class="text-muted small">Enter prices to calculate live discount</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stock & Variants -->
                    <div class="col-md-4">
                        <label class="form-label">Total Base Stock <span class="text-gold">*</span></label>
                        <input type="number" name="stock" class="form-control" placeholder="50" value="<?= isset($_POST['stock']) ? e($_POST['stock']) : '30' ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Sizes (Comma-separated)</label>
                        <input type="text" name="sizes" class="form-control" value="<?= isset($_POST['sizes']) ? e($_POST['sizes']) : 'S,M,L,XL,XXL' ?>">
                        <div class="form-text small">e.g. S,M,L,XL or 38,40,42,44</div>
                    </div>

                    <!-- Hidden/Synced colors input for compatibility -->
                    <input type="hidden" name="colors" id="hiddenColorsInput" value="<?= isset($_POST['colors']) ? e($_POST['colors']) : 'Emerald Green,Midnight Black' ?>">

                    <!-- Dynamic Colors & Color-Specific Photos Section -->
                    <div class="col-12">
                        <div class="p-3 bg-offwhite rounded border border-gold-subtle">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <label class="form-label fw-bold text-emerald mb-0">
                                        <i class="fas fa-palette text-gold me-2"></i>Product Colors &amp; Dedicated Photos
                                    </label>
                                    <div class="small text-muted">Add each garment color with its matching photo. Customers will see this exact photo when selecting that color!</div>
                                </div>
                                <button type="button" class="btn btn-luxury-primary btn-sm" onclick="addColorRow()">
                                    <i class="fas fa-plus me-1"></i> Add Another Color
                                </button>
                            </div>

                            <div id="colorRowsContainer" class="d-flex flex-column gap-3 mt-3">
                                <!-- Default Color 1 -->
                                <div class="color-row p-3 bg-white rounded border d-flex flex-wrap align-items-center gap-3">
                                    <div style="flex: 1; min-width: 180px;">
                                        <label class="small fw-bold text-emerald mb-1 d-block">Color Name</label>
                                        <input type="text" name="color_names[]" class="form-control form-control-sm color-name-input" placeholder="e.g. Emerald Green" value="Emerald Green" required>
                                    </div>
                                    <div style="flex: 2; min-width: 240px;">
                                        <label class="small fw-bold text-emerald mb-1 d-block">Color Photo (Displays when user clicks this color)</label>
                                        <input type="file" name="color_images[]" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                                    </div>
                                    <div class="pt-3">
                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeColorRow(this)" title="Delete Color">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Default Color 2 -->
                                <div class="color-row p-3 bg-white rounded border d-flex flex-wrap align-items-center gap-3">
                                    <div style="flex: 1; min-width: 180px;">
                                        <label class="small fw-bold text-emerald mb-1 d-block">Color Name</label>
                                        <input type="text" name="color_names[]" class="form-control form-control-sm color-name-input" placeholder="e.g. Midnight Black" value="Midnight Black" required>
                                    </div>
                                    <div style="flex: 2; min-width: 240px;">
                                        <label class="small fw-bold text-emerald mb-1 d-block">Color Photo (Displays when user clicks this color)</label>
                                        <input type="file" name="color_images[]" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                                    </div>
                                    <div class="pt-3">
                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeColorRow(this)" title="Delete Color">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Descriptions -->
                    <div class="col-12">
                        <label class="form-label">Short Description (Catalog Summary)</label>
                        <textarea name="short_description" rows="2" class="form-control" placeholder="Crafted from 280 GSM luxury organic combed cotton with gold branding..."><?= isset($_POST['short_description']) ? e($_POST['short_description']) : '' ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Detailed Description &amp; Fabric Specs (HTML supported)</label>
                        <textarea name="detailed_description" rows="5" class="form-control" placeholder="<p>Detailed tailoring breakdown, care instructions, and fabric weights...</p>"><?= isset($_POST['detailed_description']) ? e($_POST['detailed_description']) : '' ?></textarea>
                    </div>

                    <!-- Multiple Image Upload -->
                    <div class="col-12">
                        <label class="form-label">Upload Product Images (Select multiple)</label>
                        <input type="file" name="product_images[]" class="form-control" multiple accept="image/jpeg,image/png,image/webp">
                        <div class="form-text small">The first uploaded image will automatically serve as the primary storefront image.</div>
                        <div id="imagePreviewContainer" class="row mt-2"></div>
                    </div>

                    <!-- Badges -->
                    <div class="col-12 pt-2">
                        <div class="d-flex flex-wrap gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="featCheck">
                                <label class="form-check-label small" for="featCheck">Featured in Spotlight</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_new_arrival" value="1" id="newCheck" checked>
                                <label class="form-check-label small" for="newCheck">Mark as New Arrival</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_best_seller" value="1" id="bestCheck">
                                <label class="form-check-label small" for="bestCheck">Mark as Icon / Best Seller</label>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="col-12 mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-admin-primary">
                            <i class="fas fa-save me-2"></i> PUBLISH GARMENT TO CATALOG
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('product_name')?.addEventListener('input', function() {
    const slugInput = document.getElementById('product_slug');
    if (slugInput && !slugInput.dataset.touched) {
        slugInput.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
    }
});
document.getElementById('product_slug')?.addEventListener('input', function() {
    this.dataset.touched = "true";
});
</script>

<script>
function addColorRow(colorName = '') {
    const container = document.getElementById('colorRowsContainer');
    const row = document.createElement('div');
    row.className = 'color-row p-3 bg-white rounded border d-flex flex-wrap align-items-center gap-3 animate__animated animate__fadeIn';
    row.innerHTML = `
        <div style="flex: 1; min-width: 180px;">
            <label class="small fw-bold text-emerald mb-1 d-block">Color Name</label>
            <input type="text" name="color_names[]" class="form-control form-control-sm color-name-input" placeholder="e.g. Ivory White" value="${colorName}" required>
        </div>
        <div style="flex: 2; min-width: 240px;">
            <label class="small fw-bold text-emerald mb-1 d-block">Color Photo (Displays when user clicks this color)</label>
            <input type="file" name="color_images[]" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
        </div>
        <div class="pt-3">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeColorRow(this)" title="Delete Color">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>
    `;
    container.appendChild(row);
}

function removeColorRow(btn) {
    const rows = document.querySelectorAll('#colorRowsContainer .color-row');
    if (rows.length > 1) {
        btn.closest('.color-row').remove();
    } else {
        alert('At least one color is required.');
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/admin-footer.php'; ?>

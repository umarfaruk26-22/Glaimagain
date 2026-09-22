<?php
/**
 * GLAIMAGAIN - Admin Add Category
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $name = trim($_POST['name'] ?? '');
    $slug = slugify(trim($_POST['slug'] ?? '') ?: $name);
    $description = trim($_POST['description'] ?? '');
    $isFeatured = !empty($_POST['is_featured']) ? 1 : 0;
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

    if (empty($name)) {
        $errors[] = 'Category Name is required.';
    }

    // Check slug uniqueness
    $chk = $pdo->prepare("SELECT id FROM categories WHERE slug = ? LIMIT 1");
    $chk->execute([$slug]);
    if ($chk->fetch()) {
        $errors[] = 'A category with this URL slug already exists. Please choose another name or slug.';
    }

    // Image Upload
    $imagePath = 'assets/images/placeholder-category.svg';
    if (!empty($_FILES['image']['name'])) {
        $uploadRes = handleImageUpload($_FILES['image'], 'categories');
        if ($uploadRes['success']) {
            $imagePath = $uploadRes['path'];
        } else {
            $errors[] = 'Image Upload Failed: ' . $uploadRes['error'];
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO categories (name, slug, description, image, is_featured, sort_order, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $slug, $description, $imagePath, $isFeatured, $sortOrder, $status]);

        setFlashMessage('success', "Category '{$name}' created successfully.");
        header('Location: ' . BASE_URL . 'admin/categories');
        exit;
    }
}

$adminHeaderHeading = 'Add New Category';
$adminTitle = 'Add Category | GLAIMAGAIN Admin';
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="admin-card p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold text-emerald mb-0">Create New Collection / Category</h5>
                <a href="<?= BASE_URL ?>admin/categories" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Back to Categories
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

            <form method="POST" action="<?= BASE_URL ?>admin/categories/add" enctype="multipart/form-data" class="form-luxury">
                <?= csrfField() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Category Name <span class="text-gold">*</span></label>
                        <input type="text" name="name" id="category_name" class="form-control" placeholder="e.g. Luxury T-Shirts" value="<?= isset($_POST['name']) ? e($_POST['name']) : '' ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">URL Slug (Auto-generated if left blank)</label>
                        <input type="text" name="slug" id="category_slug" class="form-control" placeholder="e.g. luxury-t-shirts" value="<?= isset($_POST['slug']) ? e($_POST['slug']) : '' ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description / Slogan</label>
                        <textarea name="description" rows="3" class="form-control" placeholder="Brief introduction to this collection..."><?= isset($_POST['description']) ? e($_POST['description']) : '' ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Category Banner Image</label>
                        <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                        <div class="form-text small">Recommended size: 700x900 px. Formats: JPG, PNG, WEBP.</div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="0">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="isFeaturedCheck" checked>
                            <label class="form-check-label small" for="isFeaturedCheck">
                                Feature this category on Homepage
                            </label>
                        </div>
                    </div>

                    <div class="col-12 mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-admin-primary">
                            <i class="fas fa-save me-2"></i> CREATE CATEGORY
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-generate slug from name
document.getElementById('category_name')?.addEventListener('input', function() {
    const slugInput = document.getElementById('category_slug');
    if (slugInput && !slugInput.dataset.touched) {
        slugInput.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
    }
});
document.getElementById('category_slug')?.addEventListener('input', function() {
    this.dataset.touched = "true";
});
</script>

<?php require_once __DIR__ . '/../../includes/admin-footer.php'; ?>

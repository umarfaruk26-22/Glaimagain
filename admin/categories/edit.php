<?php
/**
 * GLAIMAGAIN - Admin Edit Category
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();
$categoryId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ? LIMIT 1");
$stmt->execute([$categoryId]);
$category = $stmt->fetch();

if (!$category) {
    setFlashMessage('danger', 'Category not found.');
    header('Location: ' . BASE_URL . 'admin/categories');
    exit;
}

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
    $chk = $pdo->prepare("SELECT id FROM categories WHERE slug = ? AND id != ? LIMIT 1");
    $chk->execute([$slug, $category['id']]);
    if ($chk->fetch()) {
        $errors[] = 'A category with this URL slug already exists. Please choose another name or slug.';
    }

    $imagePath = $category['image'];
    if (!empty($_FILES['image']['name'])) {
        $uploadRes = handleImageUpload($_FILES['image'], 'categories');
        if ($uploadRes['success']) {
            $imagePath = $uploadRes['path'];
        } else {
            $errors[] = 'Image Upload Failed: ' . $uploadRes['error'];
        }
    }

    if (empty($errors)) {
        $upd = $pdo->prepare("
            UPDATE categories 
            SET name = ?, slug = ?, description = ?, image = ?, is_featured = ?, sort_order = ?, status = ?
            WHERE id = ?
        ");
        $upd->execute([$name, $slug, $description, $imagePath, $isFeatured, $sortOrder, $status, $category['id']]);

        setFlashMessage('success', "Category '{$name}' updated successfully.");
        header('Location: ' . BASE_URL . 'admin/categories');
        exit;
    }
}

$adminHeaderHeading = 'Edit Category: ' . e($category['name']);
$adminTitle = 'Edit Category | GLAIMAGAIN Admin';
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="admin-card p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold text-emerald mb-0">Modify Category Particulars</h5>
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

            <form method="POST" action="<?= BASE_URL ?>admin/categories/edit?id=<?= $category['id'] ?>" enctype="multipart/form-data" class="form-luxury">
                <?= csrfField() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Category Name <span class="text-gold">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= e($category['name']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">URL Slug</label>
                        <input type="text" name="slug" class="form-control" value="<?= e($category['slug']) ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description / Slogan</label>
                        <textarea name="description" rows="3" class="form-control"><?= e($category['description']) ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Category Banner Image</label>
                        <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                        <div class="form-text small">Upload only if replacing existing banner.</div>
                    </div>

                    <div class="col-md-6 d-flex align-items-center">
                        <div class="p-2 border rounded bg-light d-flex align-items-center gap-3">
                            <img src="<?= getCategoryImageUrl($category['image']) ?>" alt="Current Banner" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                            <span class="small text-muted">Current Image Preview</span>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="<?= (int)$category['sort_order'] ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= $category['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $category['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="col-md-4 d-flex align-items-center mt-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="isFeaturedCheck" <?= $category['is_featured'] ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="isFeaturedCheck">
                                Feature on Homepage
                            </label>
                        </div>
                    </div>

                    <div class="col-12 mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-admin-primary">
                            <i class="fas fa-save me-2"></i> UPDATE CATEGORY
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/admin-footer.php'; ?>

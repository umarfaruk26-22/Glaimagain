<?php
/**
 * GLAIMAGAIN - Admin Category Management
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();

// Fetch categories with product counts
$stmt = $pdo->query("
    SELECT c.*,
           (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
    FROM categories c
    ORDER BY c.sort_order ASC, c.id DESC
");
$categories = $stmt->fetchAll();

$adminHeaderHeading = 'Category Management';
$adminTitle = 'Categories | GLAIMAGAIN Admin';
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold text-emerald mb-1">Boutique Categories (<?= count($categories) ?>)</h5>
        <p class="text-muted small mb-0">Organize and curate luxury garment collections.</p>
    </div>
    <a href="<?= BASE_URL ?>admin/categories/add.php" class="btn btn-admin-gold">
        <i class="fas fa-plus me-1"></i> ADD CATEGORY
    </a>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th style="width: 80px;">Image</th>
                    <th>Category Name</th>
                    <th>Slug</th>
                    <th>Garments</th>
                    <th>Featured</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No categories defined yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td>
                                <img src="<?= getCategoryImageUrl($cat['image']) ?>" alt="<?= e($cat['name']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #E2E8F0;">
                            </td>
                            <td>
                                <strong class="text-emerald fs-6"><?= e($cat['name']) ?></strong>
                                <?php if (!empty($cat['description'])): ?>
                                    <div class="small text-muted text-truncate" style="max-width: 250px;"><?= e($cat['description']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code><?= e($cat['slug']) ?></code>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= (int)$cat['product_count'] ?> products</span>
                            </td>
                            <td>
                                <?= $cat['is_featured'] ? '<span class="badge bg-gold text-dark fw-bold">Featured</span>' : '<span class="text-muted small">Standard</span>' ?>
                            </td>
                            <td>
                                <span class="badge badge-status badge-status-<?= e($cat['status']) ?>">
                                    <?= strtoupper(e($cat['status'])) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <a href="<?= BASE_URL ?>admin/categories/edit.php?id=<?= $cat['id'] ?>" class="btn btn-outline-dark btn-sm py-1 px-2" title="Edit Category">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>admin/categories/delete.php?id=<?= $cat['id'] ?>&csrf_token=<?= getCsrfToken() ?>" class="btn btn-outline-danger btn-sm py-1 px-2 btn-confirm-delete" data-confirm-message="Are you sure you want to delete or deactivate this category?" title="Delete Category">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/admin-footer.php'; ?>

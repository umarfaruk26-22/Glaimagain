<?php
/**
 * GLAIMAGAIN - Admin Product Images Gallery Manager
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
    header('Location: ' . BASE_URL . 'admin/products/index.php');
    exit;
}

// Handle Actions (Upload, Set Primary, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_color') {
        $imageId = (int)($_POST['image_id'] ?? 0);
        $color = trim($_POST['color'] ?? '');
        $pdo->prepare("UPDATE product_images SET color = ? WHERE id = ? AND product_id = ?")
            ->execute([$color ?: null, $imageId, $productId]);
        setFlashMessage('success', 'Image color association updated.');
        header('Location: ' . BASE_URL . 'admin/products/images.php?id=' . $productId);
        exit;
    } elseif ($action === 'upload') {
        if (!empty($_FILES['images']['name'][0])) {
            $fileCount = count($_FILES['images']['name']);
            $uploadedCount = 0;
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $singleFile = [
                        'name'     => $_FILES['images']['name'][$i],
                        'type'     => $_FILES['images']['type'][$i],
                        'tmp_name' => $_FILES['images']['tmp_name'][$i],
                        'error'    => $_FILES['images']['error'][$i],
                        'size'     => $_FILES['images']['size'][$i],
                    ];
                    $upload = handleImageUpload($singleFile, 'products');
                    if ($upload['success']) {
                        $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary, sort_order) VALUES (?, ?, 0, 10)")
                            ->execute([$productId, $upload['path']]);
                        $uploadedCount++;
                    }
                }
            }
            setFlashMessage('success', "{$uploadedCount} image(s) uploaded successfully.");
        }
        header('Location: ' . BASE_URL . 'admin/products/images.php?id=' . $productId);
        exit;
    } elseif ($action === 'set_primary') {
        $imageId = (int)($_POST['image_id'] ?? 0);
        $pdo->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?")->execute([$productId]);
        $pdo->prepare("UPDATE product_images SET is_primary = 1 WHERE id = ? AND product_id = ?")->execute([$imageId, $productId]);
        setFlashMessage('success', 'Primary storefront image updated.');
        header('Location: ' . BASE_URL . 'admin/products/images.php?id=' . $productId);
        exit;
    } elseif ($action === 'delete') {
        $imageId = (int)($_POST['image_id'] ?? 0);
        $pdo->prepare("DELETE FROM product_images WHERE id = ? AND product_id = ?")->execute([$imageId, $productId]);
        setFlashMessage('info', 'Image removed from gallery.');
        header('Location: ' . BASE_URL . 'admin/products/images.php?id=' . $productId);
        exit;
    }
}

// Fetch current images
$imgStmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC, id ASC");
$imgStmt->execute([$productId]);
$images = $imgStmt->fetchAll();

$adminHeaderHeading = 'Gallery Manager: ' . e($product['name']);
$adminTitle = 'Product Images | GLAIMAGAIN Admin';
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold text-emerald mb-1">Image Gallery: <?= e($product['name']) ?></h5>
        <small class="text-muted">SKU: <?= e($product['sku']) ?> &bull; <?= count($images) ?> image(s) registered</small>
    </div>
    <a href="<?= BASE_URL ?>admin/products/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back to Products
    </a>
</div>

<!-- Upload New Images Card -->
<div class="admin-card p-4 mb-4">
    <h6 class="fw-bold text-emerald text-uppercase letter-spacing-1 mb-3"><i class="fas fa-upload text-gold me-2"></i>Upload Additional Visuals</h6>
    <form method="POST" action="<?= BASE_URL ?>admin/products/images.php?id=<?= $productId ?>" enctype="multipart/form-data">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="upload">
        <div class="row g-2 align-items-center">
            <div class="col-md-9">
                <input type="file" name="images[]" class="form-control" multiple accept="image/jpeg,image/png,image/webp" required>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-admin-gold w-100">
                    <i class="fas fa-cloud-upload-alt me-1"></i> Upload Visuals
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Existing Gallery Grid -->
<div class="admin-card p-4">
    <h6 class="fw-bold text-emerald text-uppercase letter-spacing-1 mb-3">Current Studio Images</h6>

    <?php if (empty($images)): ?>
        <div class="text-center py-4 text-muted small">No images uploaded for this garment.</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($images as $img): ?>
                <div class="col-lg-3 col-md-4 col-6">
                    <div class="p-2 border rounded <?= $img['is_primary'] ? 'border-gold bg-offwhite shadow-sm' : 'border-light' ?> text-center position-relative">
                        <img src="<?= getProductImageUrl($img['image_path']) ?>" alt="Gallery Image" class="img-fluid rounded mb-2" style="height: 220px; width: 100%; object-fit: cover;">

                        <!-- Color Association -->
                        <form method="POST" action="<?= BASE_URL ?>admin/products/images.php?id=<?= $productId ?>" class="my-2">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="update_color">
                            <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white" style="font-size: 11px;"><i class="fas fa-palette text-gold"></i></span>
                                <input type="text" name="color" class="form-control form-control-sm" placeholder="Assign Color..." value="<?= e($img['color'] ?? '') ?>" style="font-size: 11px;">
                                <button type="submit" class="btn btn-outline-secondary btn-sm" title="Save Color"><i class="fas fa-check"></i></button>
                            </div>
                        </form>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <?php if ($img['is_primary']): ?>
                                <span class="badge bg-gold text-dark fw-bold small"><i class="fas fa-star me-1"></i>PRIMARY</span>
                            <?php else: ?>
                                <form method="POST" action="<?= BASE_URL ?>admin/products/images.php?id=<?= $productId ?>" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="set_primary">
                                    <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                                    <button type="submit" class="btn btn-outline-secondary btn-sm py-1 px-2" style="font-size: 11px;">Set Primary</button>
                                </form>
                            <?php endif; ?>

                            <form method="POST" action="<?= BASE_URL ?>admin/products/images.php?id=<?= $productId ?>" class="d-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2 btn-confirm-delete" data-confirm-message="Delete this image?" style="font-size: 11px;">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/admin-footer.php'; ?>

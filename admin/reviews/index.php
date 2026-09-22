<?php
/**
 * GLAIMAGAIN - Admin Client Reviews Management (Full CRUD & Visibility Controls)
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();

// Handle Actions (Add, Edit, Approve, Hide, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $authorName = trim($_POST['author_name'] ?? '');
        $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $title = trim($_POST['title'] ?? '');
        $comment = trim($_POST['comment'] ?? '');
        $status = in_array($_POST['status'] ?? '', ['approved', 'pending', 'hidden']) ? $_POST['status'] : 'approved';

        if ($productId > 0 && !empty($authorName) && !empty($title) && !empty($comment)) {
            $stmt = $pdo->prepare("
                INSERT INTO reviews (product_id, user_id, guest_name, rating, title, comment, status, created_at)
                VALUES (?, NULL, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$productId, $authorName, $rating, $title, $comment, $status]);
            setFlashMessage('success', "New review for garment added successfully.");
        } else {
            setFlashMessage('danger', 'Please complete all required fields to add a review.');
        }
        header('Location: ' . BASE_URL . 'admin/reviews');
        exit;
    } elseif ($action === 'edit') {
        $reviewId = (int)($_POST['review_id'] ?? 0);
        $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $title = trim($_POST['title'] ?? '');
        $comment = trim($_POST['comment'] ?? '');
        $status = in_array($_POST['status'] ?? '', ['approved', 'pending', 'hidden']) ? $_POST['status'] : 'approved';

        if ($reviewId > 0 && !empty($title) && !empty($comment)) {
            $stmt = $pdo->prepare("
                UPDATE reviews 
                SET rating = ?, title = ?, comment = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$rating, $title, $comment, $status, $reviewId]);
            setFlashMessage('success', 'Review updated successfully.');
        } else {
            setFlashMessage('danger', 'Please provide a valid title and comments.');
        }
        header('Location: ' . BASE_URL . 'admin/reviews');
        exit;
    } elseif ($action === 'approve') {
        $reviewId = (int)($_POST['review_id'] ?? 0);
        if ($reviewId > 0) {
            $pdo->prepare("UPDATE reviews SET status = 'approved', updated_at = NOW() WHERE id = ?")->execute([$reviewId]);
            setFlashMessage('success', 'Review approved and made LIVE on boutique storefront.');
        }
        header('Location: ' . BASE_URL . 'admin/reviews');
        exit;
    } elseif ($action === 'hide') {
        $reviewId = (int)($_POST['review_id'] ?? 0);
        if ($reviewId > 0) {
            $pdo->prepare("UPDATE reviews SET status = 'hidden', updated_at = NOW() WHERE id = ?")->execute([$reviewId]);
            setFlashMessage('warning', 'Review hidden from boutique storefront.');
        }
        header('Location: ' . BASE_URL . 'admin/reviews');
        exit;
    } elseif ($action === 'delete') {
        $reviewId = (int)($_POST['review_id'] ?? 0);
        if ($reviewId > 0) {
            $pdo->prepare("DELETE FROM reviews WHERE id = ?")->execute([$reviewId]);
            setFlashMessage('info', 'Review deleted permanently.');
        }
        header('Location: ' . BASE_URL . 'admin/reviews');
        exit;
    }
}

// Active Filter
$statusFilter = trim($_GET['status'] ?? 'all');
$whereClause = "";
$params = [];

if (in_array($statusFilter, ['approved', 'pending', 'hidden'])) {
    $whereClause = "WHERE r.status = ?";
    $params[] = $statusFilter;
}

// Fetch Reviews with product and author details
$stmt = $pdo->prepare("
    SELECT r.*, 
           p.name AS product_name, p.slug AS product_slug,
           COALESCE(NULLIF(TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))), ''), r.guest_name, 'Patron') AS author_name,
           u.email AS user_email, r.guest_email
    FROM reviews r
    JOIN products p ON r.product_id = p.id
    LEFT JOIN users u ON r.user_id = u.id
    {$whereClause}
    ORDER BY r.id DESC
");
$stmt->execute($params);
$reviews = $stmt->fetchAll();

// Fetch products list for the Add Review modal
$productsList = $pdo->query("SELECT id, name FROM products WHERE status = 'active' ORDER BY name ASC")->fetchAll();

// Overall Stats
$totalReviews = (int)$pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
$approvedReviews = (int)$pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'approved'")->fetchColumn();
$hiddenReviews = (int)$pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'hidden'")->fetchColumn();
$avgRating = (float)$pdo->query("SELECT AVG(rating) FROM reviews WHERE status = 'approved'")->fetchColumn() ?: 5.0;

$adminHeaderHeading = 'Client Reviews & Testimonials Management';
$adminTitle = 'Reviews Management | GLAIMAGAIN Admin';
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h5 class="fw-bold text-emerald mb-1">Patron Feedback &amp; Reviews</h5>
        <p class="text-muted small mb-0">Control which client reviews appear live on the storefront. Add, edit, or delete ratings.</p>
    </div>
    <button type="button" class="btn btn-luxury-primary" data-bs-toggle="modal" data-bs-target="#modalAddReview">
        <i class="fas fa-plus me-1"></i> Add New Review
    </button>
</div>

<!-- Stat Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="admin-card p-3">
            <div class="text-muted small text-uppercase">Total Reviews</div>
            <div class="fs-4 fw-bold text-emerald mt-1"><?= $totalReviews ?></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="admin-card p-3">
            <div class="text-muted small text-uppercase">Live on Storefront</div>
            <div class="fs-4 fw-bold text-success mt-1"><?= $approvedReviews ?></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="admin-card p-3">
            <div class="text-muted small text-uppercase">Hidden / Inactive</div>
            <div class="fs-4 fw-bold text-muted mt-1"><?= $hiddenReviews ?></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="admin-card p-3">
            <div class="text-muted small text-uppercase">Storefront Avg Rating</div>
            <div class="fs-4 fw-bold text-gold mt-1"><?= number_format($avgRating, 1) ?> ★</div>
        </div>
    </div>
</div>

<!-- Filter Tabs -->
<div class="d-flex gap-2 mb-3">
    <a href="<?= BASE_URL ?>admin/reviews" class="btn btn-sm <?= $statusFilter === 'all' ? 'btn-luxury-primary' : 'btn-outline-secondary' ?>">
        All (<?= $totalReviews ?>)
    </a>
    <a href="<?= BASE_URL ?>admin/reviews?status=approved" class="btn btn-sm <?= $statusFilter === 'approved' ? 'btn-luxury-primary' : 'btn-outline-secondary' ?>">
        Live / Approved (<?= $approvedReviews ?>)
    </a>
    <a href="<?= BASE_URL ?>admin/reviews?status=hidden" class="btn btn-sm <?= $statusFilter === 'hidden' ? 'btn-luxury-primary' : 'btn-outline-secondary' ?>">
        Hidden (<?= $hiddenReviews ?>)
    </a>
</div>

<!-- Reviews Table -->
<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Garment</th>
                    <th>Author</th>
                    <th>Rating</th>
                    <th>Headline &amp; Content</th>
                    <th>Storefront Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reviews)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No client reviews found matching this filter.</td></tr>
                <?php else: ?>
                    <?php foreach ($reviews as $rev): ?>
                        <tr>
                            <td class="small text-muted text-nowrap"><?= date('M d, Y', strtotime($rev['created_at'])) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>product/<?= urlencode($rev['product_slug']) ?>" target="_blank" class="fw-bold text-emerald text-decoration-none">
                                    <?= e($rev['product_name']) ?> <i class="fas fa-external-link-alt text-muted ms-1" style="font-size: 10px;"></i>
                                </a>
                            </td>
                            <td>
                                <div class="small fw-bold"><?= e($rev['author_name']) ?></div>
                                <small class="text-muted"><?= e($rev['user_email'] ?: ($rev['guest_email'] ?: 'Guest Patron')) ?></small>
                            </td>
                            <td>
                                <div class="text-gold" style="font-size: 13px;">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?= $i <= (int)$rev['rating'] ? '' : 'text-muted' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </td>
                            <td style="max-width: 300px;">
                                <strong class="text-emerald d-block small mb-1">&ldquo;<?= e($rev['title']) ?>&rdquo;</strong>
                                <small class="text-muted d-block text-truncate"><?= e($rev['comment']) ?></small>
                            </td>
                            <td>
                                <?php if ($rev['status'] === 'approved'): ?>
                                    <span class="badge bg-success small"><i class="fas fa-eye me-1"></i>LIVE ON SITE</span>
                                <?php elseif ($rev['status'] === 'hidden'): ?>
                                    <span class="badge bg-secondary small"><i class="fas fa-eye-slash me-1"></i>HIDDEN</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark small">PENDING</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <!-- 1-Click Visibility Toggle -->
                                <?php if ($rev['status'] === 'approved'): ?>
                                    <form method="POST" action="<?= BASE_URL ?>admin/reviews" class="d-inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="hide">
                                        <input type="hidden" name="review_id" value="<?= $rev['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Hide from user side">
                                            <i class="fas fa-eye-slash"></i> Hide
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="<?= BASE_URL ?>admin/reviews" class="d-inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="review_id" value="<?= $rev['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Show on user side">
                                            <i class="fas fa-check"></i> Make Live
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <!-- Edit Button -->
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-1" onclick="openEditReviewModal(<?= htmlspecialchars(json_encode($rev), ENT_QUOTES, 'UTF-8') ?>)" title="Edit Review">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <!-- Delete Button -->
                                <form method="POST" action="<?= BASE_URL ?>admin/reviews" class="d-inline ms-1" onsubmit="return confirm('Permanently delete this review?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="review_id" value="<?= $rev['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Review">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add New Review -->
<div class="modal fade" id="modalAddReview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-gold">
            <div class="modal-header bg-emerald text-white">
                <h5 class="modal-title font-serif"><i class="fas fa-plus text-gold me-2"></i>Add Garment Review</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= BASE_URL ?>admin/reviews">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-body p-4 form-luxury">
                    <div class="mb-3">
                        <label class="form-label">Garment / Product <span class="text-gold">*</span></label>
                        <select name="product_id" class="form-select" required>
                            <option value="">Select a garment...</option>
                            <?php foreach ($productsList as $pr): ?>
                                <option value="<?= $pr['id'] ?>"><?= e($pr['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Client / Patron Name <span class="text-gold">*</span></label>
                        <input type="text" name="author_name" class="form-control" placeholder="e.g. Vikramaditya Singhania" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Star Rating</label>
                        <select name="rating" class="form-select" required>
                            <option value="5" selected>★★★★★ (5 Stars - Exceptional)</option>
                            <option value="4">★★★★☆ (4 Stars - High Quality)</option>
                            <option value="3">★★★☆☆ (3 Stars - Satisfactory)</option>
                            <option value="2">★★☆☆☆ (2 Stars)</option>
                            <option value="1">★☆☆☆☆ (1 Star)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Review Headline <span class="text-gold">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Exquisite hand-feel and immaculate drape" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Review Comments <span class="text-gold">*</span></label>
                        <textarea name="comment" rows="3" class="form-control" placeholder="Describe the fit, silhouette, tactile feel..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Visibility Status</label>
                        <select name="status" class="form-select">
                            <option value="approved" selected>Approved (Immediately Live on Storefront)</option>
                            <option value="hidden">Hidden</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-luxury-primary">Save &amp; Publish Review</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Review -->
<div class="modal fade" id="modalEditReview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-gold">
            <div class="modal-header bg-emerald text-white">
                <h5 class="modal-title font-serif"><i class="fas fa-edit text-gold me-2"></i>Edit Client Review</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= BASE_URL ?>admin/reviews">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="review_id" id="edit_review_id">
                <div class="modal-body p-4 form-luxury">
                    <div class="mb-3">
                        <label class="form-label text-muted small">Garment</label>
                        <input type="text" id="edit_product_name" class="form-control bg-light" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small">Author</label>
                        <input type="text" id="edit_author_name" class="form-control bg-light" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Star Rating</label>
                        <select name="rating" id="edit_rating" class="form-select" required>
                            <option value="5">★★★★★ (5 Stars)</option>
                            <option value="4">★★★★☆ (4 Stars)</option>
                            <option value="3">★★★☆☆ (3 Stars)</option>
                            <option value="2">★★☆☆☆ (2 Stars)</option>
                            <option value="1">★☆☆☆☆ (1 Star)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Review Headline <span class="text-gold">*</span></label>
                        <input type="text" name="title" id="edit_title" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Review Comments <span class="text-gold">*</span></label>
                        <textarea name="comment" id="edit_comment" rows="3" class="form-control" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Visibility Status</label>
                        <select name="status" id="edit_status" class="form-select">
                            <option value="approved">Approved (Live on Storefront)</option>
                            <option value="hidden">Hidden</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-luxury-primary">Update Review</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditReviewModal(rev) {
    document.getElementById('edit_review_id').value = rev.id;
    document.getElementById('edit_product_name').value = rev.product_name || '';
    document.getElementById('edit_author_name').value = rev.author_name || '';
    document.getElementById('edit_rating').value = rev.rating;
    document.getElementById('edit_title').value = rev.title;
    document.getElementById('edit_comment').value = rev.comment;
    document.getElementById('edit_status').value = rev.status;

    const modal = new bootstrap.Modal(document.getElementById('modalEditReview'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../../includes/admin-footer.php'; ?>

<?php
/**
 * GLAIMAGAIN - Admin Client Reviews Moderation
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();

// Handle Actions (Approve, Hide, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $reviewId = (int)($_POST['review_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($reviewId > 0) {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE reviews SET status = 'approved', updated_at = NOW() WHERE id = ?")->execute([$reviewId]);
            setFlashMessage('success', 'Review approved and published.');
        } elseif ($action === 'hide') {
            $pdo->prepare("UPDATE reviews SET status = 'hidden', updated_at = NOW() WHERE id = ?")->execute([$reviewId]);
            setFlashMessage('warning', 'Review hidden from boutique storefront.');
        } elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM reviews WHERE id = ?")->execute([$reviewId]);
            setFlashMessage('info', 'Review deleted.');
        }
    }
    header('Location: ' . BASE_URL . 'admin/reviews/index.php');
    exit;
}

// Fetch reviews with Product and User details
$stmt = $pdo->query("
    SELECT r.*, p.name AS product_name, p.slug AS product_slug, u.first_name, u.last_name, u.username
    FROM reviews r
    JOIN products p ON r.product_id = p.id
    JOIN users u ON r.user_id = u.id
    ORDER BY r.id DESC
");
$reviews = $stmt->fetchAll();

$adminHeaderHeading = 'Client Reviews Moderation';
$adminTitle = 'Reviews | GLAIMAGAIN Admin';
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold text-emerald mb-1">Patron Feedback &amp; Reviews (<?= count($reviews) ?>)</h5>
        <p class="text-muted small mb-0">Moderate ratings, client testimonials, and product satisfaction.</p>
    </div>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Garment</th>
                    <th>Patron</th>
                    <th>Rating</th>
                    <th>Review Content</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reviews)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No client reviews submitted yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($reviews as $rev): ?>
                        <tr>
                            <td class="small text-muted"><?= date('M d, Y', strtotime($rev['created_at'])) ?></td>
                            <td>
                                <strong class="text-emerald d-block"><?= e($rev['product_name']) ?></strong>
                            </td>
                            <td>
                                <div class="small fw-bold"><?= e($rev['first_name'] . ' ' . $rev['last_name']) ?></div>
                                <small class="text-muted">@<?= e($rev['username']) ?></small>
                            </td>
                            <td>
                                <div class="text-gold" style="font-size: 12px;">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?= $i <= (int)$rev['rating'] ? '' : 'text-muted' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </td>
                            <td style="max-width: 320px;">
                                <strong class="text-emerald d-block small mb-1">&ldquo;<?= e($rev['title']) ?>&rdquo;</strong>
                                <small class="text-muted"><?= e($rev['comment']) ?></small>
                            </td>
                            <td>
                                <span class="badge badge-status badge-status-<?= e($rev['status']) ?>">
                                    <?= strtoupper(e($rev['status'])) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <?php if ($rev['status'] !== 'approved'): ?>
                                        <form method="POST" action="<?= BASE_URL ?>admin/reviews/index.php" class="d-inline">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="review_id" value="<?= $rev['id'] ?>">
                                            <button type="submit" class="btn btn-outline-success btn-sm py-1 px-2" title="Approve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($rev['status'] !== 'hidden'): ?>
                                        <form method="POST" action="<?= BASE_URL ?>admin/reviews/index.php" class="d-inline">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="hide">
                                            <input type="hidden" name="review_id" value="<?= $rev['id'] ?>">
                                            <button type="submit" class="btn btn-outline-warning btn-sm py-1 px-2" title="Hide">
                                                <i class="fas fa-eye-slash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" action="<?= BASE_URL ?>admin/reviews/index.php" class="d-inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="review_id" value="<?= $rev['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2 btn-confirm-delete" data-confirm-message="Delete this review?">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
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

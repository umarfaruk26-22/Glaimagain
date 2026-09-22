<?php
/**
 * GLAIMAGAIN - Admin Concierge Inbox / Contact Enquiries
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

requireAdminLogin();
$pdo = getDb();

// Handle Actions (Mark Read, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $msgId = (int)($_POST['msg_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($msgId > 0) {
        if ($action === 'mark_read') {
            $pdo->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?")->execute([$msgId]);
            setFlashMessage('success', 'Inquiry marked as read.');
        } elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM contact_messages WHERE id = ?")->execute([$msgId]);
            setFlashMessage('info', 'Inquiry removed.');
        }
    }
    header('Location: ' . BASE_URL . 'admin/enquiries/index.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM contact_messages ORDER BY is_read ASC, id DESC");
$enquiries = $stmt->fetchAll();

$adminHeaderHeading = 'Concierge Inbox';
$adminTitle = 'Concierge Inbox | GLAIMAGAIN Admin';
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold text-emerald mb-1">Client Inquiries &amp; Correspondences (<?= count($enquiries) ?>)</h5>
        <p class="text-muted small mb-0">Manage concierge requests, bespoke fitting consultations, and VIP inquiries.</p>
    </div>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>Received</th>
                    <th>Sender Particulars</th>
                    <th>Subject</th>
                    <th>Message Details</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($enquiries)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">Inbox is completely clear. No unread correspondences.</td></tr>
                <?php else: ?>
                    <?php foreach ($enquiries as $msg): ?>
                        <tr class="<?= $msg['is_read'] ? '' : 'table-warning' ?>">
                            <td class="small text-muted"><?= date('M d, Y', strtotime($msg['created_at'])) ?><br><small><?= date('h:i A', strtotime($msg['created_at'])) ?></small></td>
                            <td>
                                <strong class="text-emerald d-block"><?= e($msg['name']) ?></strong>
                                <small class="text-muted"><i class="fas fa-envelope me-1 text-gold"></i><?= e($msg['email']) ?></small>
                                <?php if (!empty($msg['mobile'])): ?>
                                    <div class="small text-muted"><i class="fas fa-phone me-1 text-gold"></i><?= e($msg['mobile']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= e($msg['subject']) ?></span>
                            </td>
                            <td style="max-width: 350px;">
                                <div class="small text-muted" style="line-height: 1.5;"><?= nl2br(e($msg['message'])) ?></div>
                            </td>
                            <td>
                                <?= $msg['is_read'] ? '<span class="badge bg-light text-muted border">Read</span>' : '<span class="badge bg-warning text-dark fw-bold"><i class="fas fa-envelope me-1"></i>UNREAD</span>' ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <?php if (!$msg['is_read']): ?>
                                        <form method="POST" action="<?= BASE_URL ?>admin/enquiries/index.php" class="d-inline">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="mark_read">
                                            <input type="hidden" name="msg_id" value="<?= $msg['id'] ?>">
                                            <button type="submit" class="btn btn-outline-success btn-sm py-1 px-2" title="Mark as Read">
                                                <i class="fas fa-check-double"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="mailto:<?= e($msg['email']) ?>?subject=Re: <?= urlencode($msg['subject']) ?>" class="btn btn-outline-dark btn-sm py-1 px-2" title="Reply via Email">
                                        <i class="fas fa-reply"></i>
                                    </a>
                                    <form method="POST" action="<?= BASE_URL ?>admin/enquiries/index.php" class="d-inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="msg_id" value="<?= $msg['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2 btn-confirm-delete" data-confirm-message="Delete this inquiry?">
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

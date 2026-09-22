<?php
/**
 * GLAIMAGAIN - Payment Failed Page
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

requireUserLogin();
$orderNumber = trim($_GET['order'] ?? '');
$errorMessage = trim($_GET['error'] ?? 'Payment was cancelled or rejected by your financial institution.');

$pageTitle = 'Payment Failed | GLAIMAGAIN';
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-5 bg-offwhite min-vh-75 d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="p-4 p-md-5 bg-white border border-danger rounded shadow-lg text-center">
                    <div class="action-btn bg-danger text-white rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px; font-size: 30px;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>

                    <span class="text-danger fw-bold letter-spacing-3 small text-uppercase">Transaction Unsuccessful</span>
                    <h2 class="fw-bold text-emerald mt-1 mb-2 font-serif">PAYMENT FAILED</h2>
                    <p class="text-muted small max-w-500 mx-auto mb-4">
                        <?= e($errorMessage) ?>
                    </p>

                    <?php if (!empty($orderNumber)): ?>
                        <div class="p-3 bg-light rounded small mb-4">
                            <strong>Reference Order #:</strong> <?= e($orderNumber) ?>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
                        <a href="<?= BASE_URL ?>checkout.php" class="btn btn-luxury-gold">
                            <i class="fas fa-redo me-1"></i> RETRY PAYMENT
                        </a>
                        <a href="<?= BASE_URL ?>cart.php" class="btn btn-luxury-outline">
                            RETURN TO BAG
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

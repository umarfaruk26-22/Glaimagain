<?php
/**
 * GLAIMAGAIN - Admin Header Template
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

requireAdminLogin();
$currentAdmin = getCurrentAdmin();
$adminTitle = $adminTitle ?? 'Admin Portal — GLAIMAGAIN';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($adminTitle) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>assets/images/favicon.svg">

    <!-- CSS Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css?v=<?= time() ?>">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        window.GLAIMAGAIN_BASE_URL = "<?= BASE_URL ?>";
    </script>
</head>
<body class="admin-body">

    <?php require_once __DIR__ . '/admin-sidebar.php'; ?>

    <div class="admin-wrapper">
        <!-- Top Admin Header -->
        <header class="admin-header">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-outline-secondary d-lg-none" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="admin-header-title">
                    <h4><?= e($adminHeaderHeading ?? 'Dashboard Overview') ?></h4>
                </div>
            </div>

            <div class="admin-header-user">
                <a href="<?= BASE_URL ?>" target="_blank" class="btn btn-outline-dark btn-sm d-none d-sm-inline-flex align-items-center gap-1" title="View Public Website">
                    <i class="fas fa-external-link-alt"></i> View Boutique
                </a>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center gap-2 text-decoration-none text-dark dropdown-toggle" data-bs-toggle="dropdown">
                        <div class="admin-avatar">
                            <?= strtoupper(substr($currentAdmin['username'], 0, 1)) ?>
                        </div>
                        <span class="fw-bold small d-none d-md-inline"><?= e($currentAdmin['username']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" style="min-width: 200px;">
                        <li><h6 class="dropdown-header">Administrator</h6></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>admin/settings"><i class="fas fa-cog"></i>System Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>admin/logout"><i class="fas fa-sign-out-alt"></i>Sign Out</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Admin Content Body -->
        <main class="admin-content">
            <?= renderFlashMessages() ?>

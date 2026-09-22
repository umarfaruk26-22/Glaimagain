<?php
/**
 * GLAIMAGAIN - Modern Luxury Customer Header Template (Rozelio Inspired)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

$currentUser = getCurrentUser();
$cartDetails = getCartDetails();
$cartCount = $cartDetails['total_quantity'] ?? 0;
$activeCategories = getActiveCategories();
$pageTitle = $pageTitle ?? getSetting('site_name', 'GLAIMAGAIN') . ' | ' . getSetting('site_tagline', 'FASHION BEYOND TODAY');
$metaDescription = $metaDescription ?? getSetting('meta_description', 'GLAIMAGAIN - Fashion Beyond Today. Discover handcrafted luxury apparel.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <link rel="icon" type="image/jpeg" href="<?= BASE_URL ?>assets/images/glaimagain-logo.jpg">

    <!-- CSS Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css?v=<?= time() ?>">

    <script>
        window.GLAIMAGAIN_BASE_URL = "<?= BASE_URL ?>";
    </script>
</head>
<body>

    <!-- 1. Top Announcement Bar -->
    <div class="top-announcement">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-none d-md-block">
                <i class="fas fa-crown text-gold me-2"></i>
                <span>COMPLIMENTARY EXPRESS SHIPPING ON ORDERS ABOVE <?= formatPrice(getSetting('free_shipping_threshold', (string)FREE_SHIPPING_THRESHOLD)) ?></span>
            </div>
            <div class="mx-auto mx-md-0 text-center text-md-end">
                <span><i class="fas fa-gem text-gold me-1"></i> <?= e(getSetting('site_tagline', 'FASHION BEYOND TODAY')) ?></span>
            </div>
        </div>
    </div>

    <!-- 2. Clean Luxury Navigation (Rozelio Style) -->
    <header class="main-header-clean">
        <div class="container">
            <nav class="navbar navbar-expand-lg p-0">
                <!-- Mobile Menu Toggler (Left on Mobile) -->
                <button class="navbar-toggler border-0 me-2" type="button" id="btnMobileNavToggle" aria-label="Open Navigation Menu">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Brand Logo (Centered on Mobile, Left on Desktop) -->
                <a class="navbar-brand py-2 d-flex align-items-center" href="<?= BASE_URL ?>">
                    <img src="<?= BASE_URL ?>assets/images/glaimagain-logo.jpg" alt="GLAIMAGAIN" class="brand-logo-img rounded">
                </a>

                <!-- Desktop Navigation Menu Links (Hidden on Mobile) -->
                <div class="collapse navbar-collapse d-none d-lg-flex" id="navbarMain">
                    <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link-modern <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>">HOME</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link-modern <?= (basename($_SERVER['PHP_SELF']) == 'shop.php' && empty($_GET['category'])) ? 'active' : '' ?>" href="<?= BASE_URL ?>shop.php">SHOP</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link-modern <?= (isset($_GET['category']) && $_GET['category'] == 'men') ? 'active' : '' ?>" href="<?= BASE_URL ?>shop.php?category=men">MEN</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link-modern <?= (isset($_GET['category']) && $_GET['category'] == 'women') ? 'active' : '' ?>" href="<?= BASE_URL ?>shop.php?category=women">WOMEN</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link-modern dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                COLLECTIONS
                            </a>
                            <ul class="dropdown-menu">
                                <li><h6 class="dropdown-header">Boutique Categories</h6></li>
                                <?php foreach ($activeCategories as $cat): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?= BASE_URL ?>shop.php?category=<?= urlencode($cat['slug']) ?>">
                                            <i class="fas fa-chevron-right"></i><?= e($cat['name']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link-modern <?= (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>about.php">ABOUT</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link-modern <?= (basename($_SERVER['PHP_SELF']) == 'contact.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>contact.php">CONTACT</a>
                        </li>
                    </ul>
                </div>

                <!-- Right Action Buttons (Search, Bag, Login - Right on both Mobile & Desktop) -->
                <div class="header-right-actions d-flex align-items-center gap-2">
                    <!-- Search Trigger -->
                    <a href="#" class="action-icon-btn btn-search-trigger" title="Search Garments" aria-label="Search">
                        <i class="fas fa-search"></i>
                    </a>

                    <!-- Shopping Bag -->
                    <a href="<?= BASE_URL ?>cart.php" class="action-icon-btn" title="Shopping Bag" aria-label="Bag">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="badge-cart" style="<?= $cartCount > 0 ? '' : 'display:none;' ?>"><?= $cartCount ?></span>
                    </a>

                    <!-- User Account / Login Pill -->
                    <?php if ($currentUser): ?>
                        <div class="dropdown">
                            <a href="#" class="btn-nav-login dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle"></i> <span class="d-none d-sm-inline"><?= e($currentUser['first_name']) ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" style="min-width: 220px;">
                                <li class="px-3 py-2 border-bottom mb-1" style="background: rgba(1, 60, 38, 0.04); border-radius: 8px;">
                                    <div class="small text-forest fw-bold"><?= e($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?></div>
                                    <div class="small text-muted text-truncate">@<?= e($currentUser['username']) ?></div>
                                </li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>account/profile.php"><i class="fas fa-id-card"></i>My Profile</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>account/orders.php"><i class="fas fa-box-open"></i>My Orders</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>account/addresses.php"><i class="fas fa-map-marker-alt"></i>Saved Addresses</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>account/change-password.php"><i class="fas fa-lock"></i>Security</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout.php"><i class="fas fa-sign-out-alt"></i>Sign Out</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>login.php" class="btn-nav-login" title="Sign In">
                            <i class="fas fa-user d-inline d-sm-none"></i>
                            <span class="d-none d-sm-inline">LOGIN</span>
                        </a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <!-- Buttery-Smooth Mobile Side Navigation Drawer (Hardware-Accelerated, Zero Lag) -->
    <div class="mobile-nav-backdrop" id="mobileNavBackdrop"></div>
    <div class="mobile-nav-drawer" id="mobileNavDrawer" aria-hidden="true">
        <div class="mobile-nav-header">
            <div class="d-flex align-items-center gap-2">
                <img src="<?= BASE_URL ?>assets/images/glaimagain-logo.jpg" alt="GLAIMAGAIN" class="mobile-drawer-logo rounded">
                <div>
                    <div class="mobile-drawer-brand-title">GLAIMAGAIN</div>
                    <div class="mobile-drawer-brand-sub">FASHION BEYOND TODAY</div>
                </div>
            </div>
            <button type="button" class="mobile-nav-close-btn" id="btnMobileNavClose" aria-label="Close Menu">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="mobile-nav-body">
            <div class="mobile-nav-list">
                <a href="<?= BASE_URL ?>" class="mobile-nav-item <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : '' ?>">
                    <span><i class="fas fa-home me-2 text-gold"></i> HOME</span>
                    <i class="fas fa-chevron-right arrow-icon"></i>
                </a>
                <a href="<?= BASE_URL ?>shop.php" class="mobile-nav-item <?= (basename($_SERVER['PHP_SELF']) == 'shop.php' && empty($_GET['category'])) ? 'active' : '' ?>">
                    <span><i class="fas fa-tshirt me-2 text-gold"></i> ALL APPAREL</span>
                    <i class="fas fa-chevron-right arrow-icon"></i>
                </a>
                <a href="<?= BASE_URL ?>shop.php?category=men" class="mobile-nav-item <?= (isset($_GET['category']) && $_GET['category'] == 'men') ? 'active' : '' ?>">
                    <span><i class="fas fa-male me-2 text-gold"></i> MEN'S COLLECTION</span>
                    <i class="fas fa-chevron-right arrow-icon"></i>
                </a>
                <a href="<?= BASE_URL ?>shop.php?category=women" class="mobile-nav-item <?= (isset($_GET['category']) && $_GET['category'] == 'women') ? 'active' : '' ?>">
                    <span><i class="fas fa-female me-2 text-gold"></i> WOMEN'S COLLECTION</span>
                    <i class="fas fa-chevron-right arrow-icon"></i>
                </a>

                <!-- Collapsible Boutique Categories inside drawer -->
                <div class="mobile-nav-group">
                    <button class="mobile-nav-item mobile-nav-accordion-btn" type="button" data-bs-toggle="collapse" data-bs-target="#drawerCategories" aria-expanded="false">
                        <span><i class="fas fa-crown me-2 text-gold"></i> BOUTIQUE CATEGORIES</span>
                        <i class="fas fa-chevron-down arrow-icon"></i>
                    </button>
                    <div class="collapse" id="drawerCategories">
                        <div class="mobile-subnav-list">
                            <?php foreach ($activeCategories as $cat): ?>
                                <a href="<?= BASE_URL ?>shop.php?category=<?= urlencode($cat['slug']) ?>" class="mobile-subnav-item">
                                    <i class="fas fa-sparkles me-2 text-gold-subtle"></i> <?= e($cat['name']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <a href="<?= BASE_URL ?>about.php" class="mobile-nav-item <?= (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'active' : '' ?>">
                    <span><i class="fas fa-gem me-2 text-gold"></i> MAISON &amp; HERITAGE</span>
                    <i class="fas fa-chevron-right arrow-icon"></i>
                </a>
                <a href="<?= BASE_URL ?>contact.php" class="mobile-nav-item <?= (basename($_SERVER['PHP_SELF']) == 'contact.php') ? 'active' : '' ?>">
                    <span><i class="fas fa-concierge-bell me-2 text-gold"></i> CONCIERGE</span>
                    <i class="fas fa-chevron-right arrow-icon"></i>
                </a>
            </div>
        </div>

        <div class="mobile-nav-footer">
            <?php if ($currentUser): ?>
                <div class="mobile-user-card">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="fas fa-user-circle fs-4 text-gold"></i>
                        <div>
                            <div class="fw-bold text-forest small"><?= e($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?></div>
                            <div class="text-muted" style="font-size: 11px;">@<?= e($currentUser['username']) ?></div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?= BASE_URL ?>account/profile.php" class="btn btn-sm btn-luxury-primary flex-fill" style="padding: 6px 10px; font-size: 11px;">Account</a>
                        <a href="<?= BASE_URL ?>logout.php" class="btn btn-sm btn-outline-danger flex-fill" style="padding: 6px 10px; font-size: 11px;">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="d-grid gap-2">
                    <a href="<?= BASE_URL ?>login.php" class="btn btn-luxury-primary py-2 text-center" style="font-size: 12.5px;">
                        <i class="fas fa-sign-in-alt me-2"></i> PATRON LOGIN
                    </a>
                    <a href="<?= BASE_URL ?>register.php" class="btn btn-outline-luxury py-2 text-center" style="font-size: 12.5px;">
                        <i class="fas fa-user-plus me-2"></i> CREATE ACCOUNT
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 3. Global Fullscreen Live Search Overlay -->
    <div class="search-overlay" id="searchOverlay">
        <div class="search-box-wrap">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <span class="text-gold letter-spacing-3 small fw-bold">SEARCH THE GLAIMAGAIN COLLECTION</span>
                <button type="button" class="btn btn-link text-white btn-search-close p-0" style="font-size: 24px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="<?= BASE_URL ?>search.php" method="GET">
                <div class="position-relative">
                    <input type="text" name="q" class="search-input-luxury" placeholder="Search by name, SKU, or category..." autocomplete="off" required>
                    <button type="submit" class="btn text-gold position-absolute end-0 top-50 translate-middle-y fs-4 border-0 bg-transparent">
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </form>
            <div class="mt-4 text-white-50 small">
                <span class="text-gold me-2">Popular:</span>
                <a href="<?= BASE_URL ?>search.php?q=Emerald" class="text-white text-decoration-underline me-3">Emerald Heavyweight Tee</a>
                <a href="<?= BASE_URL ?>search.php?q=Velvet" class="text-white text-decoration-underline me-3">Velvet Tuxedo</a>
                <a href="<?= BASE_URL ?>search.php?q=Denim" class="text-white text-decoration-underline">Selvedge Denim</a>
            </div>
        </div>
    </div>

    <!-- Flash Alert Banners Container -->
    <?= renderFlashMessages() ?>

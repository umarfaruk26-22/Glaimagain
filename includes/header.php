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
                <!-- Mobile Menu Button -->
                <button class="navbar-toggler border-0 me-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Brand Logo (Official GLAIMAGAIN Logo) -->
                <a class="navbar-brand py-2 me-4 d-flex align-items-center" href="<?= BASE_URL ?>">
                    <img src="<?= BASE_URL ?>assets/images/glaimagain-logo.jpg" alt="GLAIMAGAIN" class="brand-logo-img rounded">
                </a>

                <!-- Navigation Menu Links -->
                <div class="collapse navbar-collapse" id="navbarMain">
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

                <!-- Right Action Buttons (Search, Bag, Login) -->
                <div class="d-flex align-items-center gap-2">
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
                                <i class="fas fa-user-circle"></i> <?= e($currentUser['first_name']) ?>
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
                        <a href="<?= BASE_URL ?>login.php" class="btn-nav-login">
                            LOGIN
                        </a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

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

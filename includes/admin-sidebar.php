<?php
/**
 * GLAIMAGAIN - Admin Sidebar Navigation
 */
$currentUri = $_SERVER['REQUEST_URI'] ?? '';
?>
<aside class="admin-sidebar">
    <div class="admin-sidebar-brand">
        <a href="<?= BASE_URL ?>admin/dashboard" class="d-flex align-items-center justify-content-center">
            <img src="<?= BASE_URL ?>assets/images/glaimagain-logo.png" alt="GLAIMAGAIN Admin" style="height: 42px; max-width: 100%; object-fit: contain; background: #FFFFFF; padding: 6px 14px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
        </a>
    </div>

    <ul class="admin-nav">
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>admin/dashboard" class="admin-nav-link <?= (strpos($currentUri, 'dashboard') !== false || substr($currentUri, -6) === '/admin') ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>admin/categories" class="admin-nav-link <?= strpos($currentUri, '/categories') !== false ? 'active' : '' ?>">
                <i class="fas fa-layer-group"></i>
                <span>Categories</span>
            </a>
        </li>

        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>admin/products" class="admin-nav-link <?= strpos($currentUri, '/products') !== false ? 'active' : '' ?>">
                <i class="fas fa-tshirt"></i>
                <span>Products</span>
            </a>
        </li>

        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>admin/inventory" class="admin-nav-link <?= strpos($currentUri, '/inventory') !== false ? 'active' : '' ?>">
                <i class="fas fa-boxes"></i>
                <span>Inventory Matrix</span>
            </a>
        </li>

        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>admin/orders" class="admin-nav-link <?= strpos($currentUri, '/orders') !== false ? 'active' : '' ?>">
                <i class="fas fa-shopping-bag"></i>
                <span>Orders Pipeline</span>
            </a>
        </li>

        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>admin/users" class="admin-nav-link <?= strpos($currentUri, '/users') !== false ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Patrons / Users</span>
            </a>
        </li>

        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>admin/sales" class="admin-nav-link <?= strpos($currentUri, '/sales') !== false ? 'active' : '' ?>">
                <i class="fas fa-coins"></i>
                <span>Sales &amp; Revenue</span>
            </a>
        </li>

        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>admin/reviews" class="admin-nav-link <?= strpos($currentUri, '/reviews') !== false ? 'active' : '' ?>">
                <i class="fas fa-star"></i>
                <span>Client Reviews</span>
            </a>
        </li>

        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>admin/enquiries" class="admin-nav-link <?= strpos($currentUri, '/enquiries') !== false ? 'active' : '' ?>">
                <i class="fas fa-envelope-open-text"></i>
                <span>Concierge Inbox</span>
            </a>
        </li>

        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>admin/homepage" class="admin-nav-link <?= strpos($currentUri, '/homepage') !== false ? 'active' : '' ?>">
                <i class="fas fa-image"></i>
                <span>Homepage CMS</span>
            </a>
        </li>

        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>admin/settings" class="admin-nav-link <?= strpos($currentUri, '/settings') !== false ? 'active' : '' ?>">
                <i class="fas fa-sliders-h"></i>
                <span>Store Settings</span>
            </a>
        </li>

        <li class="admin-nav-item mt-4 pt-3 border-top border-secondary">
            <a href="<?= BASE_URL ?>admin/logout" class="admin-nav-link text-danger">
                <i class="fas fa-sign-out-alt text-danger"></i>
                <span>Sign Out</span>
            </a>
        </li>
    </ul>
</aside>

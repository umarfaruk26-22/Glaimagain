<?php
/**
 * GLAIMAGAIN - Modern Customer Footer Template
 */
?>
    <!-- Modern Luxury Footer -->
    <footer class="site-footer-modern">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="d-inline-block bg-white p-2 px-3 rounded mb-3 shadow-sm" style="max-width: 220px;">
                        <img src="<?= BASE_URL ?>assets/images/glaimagain-logo.png" alt="GLAIMAGAIN" style="height: 38px; width: auto; object-fit: contain;">
                    </div>
                    <p class="text-white-50 small pe-lg-4" style="line-height: 1.7;">
                        <?= e(getSetting('site_name', 'GLAIMAGAIN')) ?> — <?= e(getSetting('site_tagline', 'FASHION BEYOND TODAY')) ?>. Crafting luxury tailored garments, heavyweight combed cotton tees, and bespoke modern silhouettes.
                    </p>
                    <div class="mt-4">
                        <a href="<?= e(getSetting('instagram_url', '#')) ?>" class="footer-social-btn" title="Instagram" target="_blank" rel="noopener"><i class="fab fa-instagram"></i></a>
                        <a href="<?= e(getSetting('facebook_url', '#')) ?>" class="footer-social-btn" title="Facebook" target="_blank" rel="noopener"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', getSetting('contact_whatsapp', '919876543210')) ?>" class="footer-social-btn" title="WhatsApp" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>

                <!-- Column 2: Navigation -->
                <div class="col-lg-2 col-md-6 col-6">
                    <h5>Navigation</h5>
                    <ul>
                        <li><a href="<?= BASE_URL ?>">Home</a></li>
                        <li><a href="<?= BASE_URL ?>shop.php">All Products</a></li>
                        <li><a href="<?= BASE_URL ?>shop.php?category=men">Men's Wardrobe</a></li>
                        <li><a href="<?= BASE_URL ?>shop.php?category=women">Women's Atelier</a></li>
                        <li><a href="<?= BASE_URL ?>about.php">About GLAIMAGAIN</a></li>
                        <li><a href="<?= BASE_URL ?>contact.php">Contact Concierge</a></li>
                    </ul>
                </div>

                <!-- Column 3: Customer Care -->
                <div class="col-lg-2 col-md-6 col-6">
                    <h5>Client Care</h5>
                    <ul>
                        <li><a href="<?= BASE_URL ?>account/orders.php">Order Tracking</a></li>
                        <li><a href="<?= BASE_URL ?>account/addresses.php">Shipping Addresses</a></li>
                        <li><a href="<?= BASE_URL ?>contact.php">Size Consultations</a></li>
                        <li><a href="<?= BASE_URL ?>about.php">Shipping &amp; Returns</a></li>
                        <li><a href="<?= BASE_URL ?>admin/login.php" class="text-white-50"><i class="fas fa-lock me-1"></i>Staff Access</a></li>
                    </ul>
                </div>

                <!-- Column 4: Newsletter -->
                <div class="col-lg-4 col-md-6">
                    <h5>Join VIP Circle</h5>
                    <p class="text-white-50 small mb-3">
                        Receive private invitations to limited capsule drops and bespoke seasonal releases.
                    </p>
                    <form action="<?= BASE_URL ?>contact.php" method="POST" class="mb-4">
                        <?= csrfField() ?>
                        <div class="input-group">
                            <input type="email" name="newsletter_email" class="form-control bg-white text-dark border-0" placeholder="Enter your email address" required style="font-size: 13px; border-radius: 9999px 0 0 9999px; padding: 12px 18px;">
                            <button class="btn btn-pill-gold" type="submit" style="border-radius: 0 9999px 9999px 0; padding: 12px 20px; font-size: 12px;">JOIN</button>
                        </div>
                    </form>
                    <div class="small text-white-50">
                        <i class="fas fa-map-marker-alt text-gold me-2"></i><?= e(getSetting('store_address', 'Mumbai, India')) ?>
                    </div>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 mt-5 pt-4 border-top border-secondary">
                <div class="small text-white-50">
                    &copy; <?= date('Y') ?> <strong class="text-white"><?= e(getSetting('site_name', 'GLAIMAGAIN')) ?></strong>. All Rights Reserved. <?= e(getSetting('site_tagline', 'FASHION BEYOND TODAY')) ?>.
                </div>
                <div class="d-flex align-items-center gap-3 text-white-50 small">
                    <span><i class="fas fa-shield-alt text-gold me-1"></i> 256-Bit SSL Encrypted Checkout</span>
                    <span>|</span>
                    <span><i class="fas fa-credit-card text-gold me-1"></i> Razorpay Verified</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript Dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/main.js?v=<?= time() ?>"></script>
    <script src="<?= BASE_URL ?>assets/js/cart.js?v=<?= time() ?>"></script>
    <?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>

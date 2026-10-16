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
                    <div class="d-inline-block bg-white p-2 px-3 rounded mb-3 shadow-sm" style="max-width: 240px;">
                        <img src="<?= BASE_URL ?>assets/images/glaimagain-logo.png" alt="GLAIMAGAIN" style="height: 38px; width: auto; object-fit: contain;">
                    </div>
                    <p class="text-white-50 small pe-lg-4" style="line-height: 1.7;">
                        <?= e(getSetting('site_name', 'GLAIMAGAIN')) ?> — <?= e(getSetting('site_tagline', 'FASHION BEYOND TODAY')) ?>. Crafting luxury tailored garments, heavyweight combed cotton tees, and bespoke modern silhouettes.
                    </p>
                    <div class="mt-4">
                        <a href="<?= e(getSetting('instagram_url', '#')) ?>" class="footer-social-btn" title="Instagram" target="_blank" rel="noopener"><i class="fab fa-instagram"></i></a>
                        <a href="<?= e(getSetting('facebook_url', '#')) ?>" class="footer-social-btn" title="Facebook" target="_blank" rel="noopener"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://wa.me/<?= defined('WHATSAPP_ORDER_NUMBER') ? WHATSAPP_ORDER_NUMBER : '919033321151' ?>" class="footer-social-btn" title="WhatsApp Concierge" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i></a>
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
                    <span><i class="fas fa-shield-alt text-gold me-1"></i> 100% Genuine Luxury Guarantee</span>
                    <span>|</span>
                    <span><i class="fab fa-whatsapp text-success me-1"></i> WhatsApp Concierge Ordering</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Floating WhatsApp Concierge Widget -->
    <a href="https://api.whatsapp.com/send?phone=<?= defined('WHATSAPP_ORDER_NUMBER') ? WHATSAPP_ORDER_NUMBER : '919033321151' ?>&text=<?= urlencode('Hello GLAIMAGAIN Concierge! I have an inquiry regarding your luxury collection.') ?>" class="floating-whatsapp-widget" target="_blank" rel="noopener" title="Chat with GLAIMAGAIN on WhatsApp">
        <i class="fab fa-whatsapp fs-5"></i>
        <span>Order on WhatsApp</span>
    </a>

    <!-- Luxury Cookie Consent Banner -->
    <div id="glaimCookieBanner" class="cookie-consent-bar" style="display: none;">
        <div class="container">
            <div class="cookie-consent-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="cookie-icon-wrap">
                        <i class="fas fa-cookie-bite"></i>
                    </div>
                    <div class="cookie-content">
                        <h6 class="cookie-title">We Value Your Privacy &amp; Luxury Experience</h6>
                        <p class="cookie-text">
                            GLAIMAGAIN uses cookies to maintain your bespoke shopping bag, keep your authentication preferences saved, and ensure an elevated shopping journey. Do you accept cookies?
                        </p>
                    </div>
                </div>
                <div class="cookie-actions">
                    <button type="button" class="btn btn-cookie-reject" onclick="handleCookieConsent('rejected')">
                        <i class="fas fa-times me-1"></i> Reject
                    </button>
                    <button type="button" class="btn btn-cookie-accept" onclick="handleCookieConsent('accepted')">
                        <i class="fas fa-check me-1"></i> Accept Cookies
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/main.js?v=<?= time() ?>"></script>
    <script src="<?= BASE_URL ?>assets/js/cart.js?v=<?= time() ?>"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        function getCookie(name) {
            const matches = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + '=([^;]*)'));
            return matches ? decodeURIComponent(matches[1]) : null;
        }

        const consent = localStorage.getItem('glaimagain_cookie_consent') || getCookie('glaim_cookie_consent');
        const banner = document.getElementById('glaimCookieBanner');

        // Show banner for new visitors after slight delay
        if (!consent && banner) {
            setTimeout(function() {
                banner.style.display = 'block';
                setTimeout(function() {
                    banner.classList.add('show');
                }, 50);
            }, 1000);
        }
    });

    function handleCookieConsent(choice) {
        const banner = document.getElementById('glaimCookieBanner');
        if (banner) {
            banner.classList.remove('show');
            setTimeout(function() {
                banner.style.display = 'none';
            }, 400);
        }

        localStorage.setItem('glaimagain_cookie_consent', choice);
        const maxAge = choice === 'accepted' ? (365 * 86400) : (30 * 86400);
        document.cookie = 'glaim_cookie_consent=' + encodeURIComponent(choice) + '; max-age=' + maxAge + '; path=/; SameSite=Lax';
    }
    </script>

    <?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>

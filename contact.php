<?php
/**
 * GLAIMAGAIN - Concierge & Contact Page
 */
require_once __DIR__ . '/config/config.php';

$pdo = getDb();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    // Handle Newsletter form or full Contact form
    if (isset($_POST['newsletter_email'])) {
        $email = filter_var(trim($_POST['newsletter_email']), FILTER_VALIDATE_EMAIL);
        if ($email) {
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES ('VIP Subscriber', ?, 'Newsletter Subscription', 'User subscribed to VIP newsletter.')");
            $stmt->execute([$email]);
            
            // Dispatch via FastAPI Microservice to umarfaruksuratwala@gmail.com
            sendFastApiNewsletter($email);

            setFlashMessage('success', 'Welcome to the GLAIMAGAIN Private Circle. You are now subscribed.');
        } else {
            setFlashMessage('danger', 'Please provide a valid email address.');
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL));
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $mobile = trim($_POST['mobile'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || !$email || empty($subject) || empty($message)) {
        setFlashMessage('danger', 'Please complete all required fields with valid details.');
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO contact_messages (name, email, mobile, subject, message)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $email, $mobile, $subject, $message]);

        // Dispatch via FastAPI Microservice to umarfaruksuratwala@gmail.com
        sendFastApiContactEmail($name, $email, $mobile, $subject, $message);

        setFlashMessage('success', 'Your inquiry has been received. Our customer support team will respond within 24 business hours.');
        header('Location: ' . BASE_URL . 'contact.php');
        exit;
    }
}

$pageTitle = 'Contact Us & Customer Support | GLAIMAGAIN';
$metaDescription = 'Contact GLAIMAGAIN Customer Support for sizing advice, order inquiries, and shopping assistance.';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Hero Banner -->
<div class="bg-emerald text-white py-5 border-bottom border-gold-subtle">
    <div class="container text-center py-4">
        <span class="text-gold fw-bold letter-spacing-4 small text-uppercase">Customer Support</span>
        <h1 class="display-4 fw-bold text-white mt-1">CONTACT US</h1>
        <div class="gold-divider"><i class="fas fa-gem"></i></div>
        <p class="lead text-white-50 max-w-600 mx-auto">We are here to help you with size consultations, order tracking, and general inquiries.</p>
    </div>
</div>

<div class="container py-5 my-3">
    <div class="row g-5">
        <!-- 1. Left Column: Contact Form -->
        <div class="col-lg-7">
            <div class="p-4 p-md-5 bg-white border border-gold-subtle rounded shadow-sm">
                <h3 class="fw-bold text-emerald mb-2">SEND A MESSAGE</h3>
                <p class="text-muted small mb-4">Please complete the form below and our customer support team will be in touch promptly.</p>

                <form method="POST" action="<?= BASE_URL ?>contact.php" class="form-luxury">
                    <?= csrfField() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Your Name <span class="text-gold">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="Enter your full name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address <span class="text-gold">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="name@domain.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile Number</label>
                            <input type="tel" name="mobile" class="form-control" placeholder="+91 98765 43210">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Subject <span class="text-gold">*</span></label>
                            <select name="subject" class="form-select" required>
                                <option value="">Select Inquiry Type...</option>
                                <option value="Sizing & Fit Advice">Sizing &amp; Fit Advice</option>
                                <option value="Order Tracking & Status">Order Tracking &amp; Status</option>
                                <option value="Returns & Exchanges">Returns &amp; Exchanges</option>
                                <option value="Bulk / Corporate Orders">Bulk / Corporate Orders</option>
                                <option value="Press & Collaborations">Press &amp; Collaborations</option>
                                <option value="Other Inquiries">Other Inquiries</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Message <span class="text-gold">*</span></label>
                            <textarea name="message" rows="5" class="form-control" placeholder="How can our support team help you today?" required></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-luxury-primary w-100 py-3 mt-2">
                                <i class="fas fa-paper-plane me-2"></i> SEND MESSAGE
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. Right Column: Flagship Details -->
        <div class="col-lg-5">
            <div class="p-4 p-md-5 bg-offwhite border border-gold-subtle rounded shadow-sm h-100">
                <h4 class="fw-bold text-emerald mb-4">OUR STORE &amp; OFFICE</h4>

                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="action-btn bg-emerald text-gold rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="fas fa-map-marker-alt fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-emerald mb-1">STORE &amp; OFFICE ADDRESS</h6>
                        <p class="text-muted small mb-0"><?= e(getSetting('store_address', 'Level 4, Luxury Pavilion, Mumbai, Maharashtra 400050, India')) ?></p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="action-btn bg-emerald text-gold rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="fas fa-phone-alt fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-emerald mb-1">CUSTOMER SUPPORT PHONE</h6>
                        <p class="text-muted small mb-0"><?= e(getSetting('contact_phone', '+91 90333 21151')) ?></p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="action-btn bg-emerald text-gold rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="fas fa-envelope fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-emerald mb-1">SUPPORT EMAIL</h6>
                        <p class="text-muted small mb-0"><?= e(getSetting('contact_email', 'support@glaimagain.com')) ?></p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="action-btn bg-emerald text-gold rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="fas fa-clock fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-emerald mb-1">STORE / WORKING HOURS</h6>
                        <p class="text-muted small mb-0"><?= e(getSetting('working_hours', 'Mon - Sat: 10:00 AM - 08:00 PM IST')) ?></p>
                    </div>
                </div>

                <hr class="border-gold-subtle my-4">

                <h6 class="fw-bold text-emerald mb-3">INSTANT WHATSAPP SUPPORT</h6>
                <a href="https://wa.me/<?= defined('WHATSAPP_ORDER_NUMBER') ? WHATSAPP_ORDER_NUMBER : '919033321151' ?>" target="_blank" rel="noopener" class="btn btn-whatsapp-order w-100">
                    <i class="fab fa-whatsapp me-2"></i> CHAT WITH SUPPORT
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

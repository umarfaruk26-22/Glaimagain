<?php
/**
 * GLAIMAGAIN - Secure Checkout Page
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// Enforce Login for Checkout
requireUserLogin(BASE_URL . 'checkout.php');

$currentUser = getCurrentUser();
$cart = getCartDetails();

// Redirect to cart if empty
if (empty($cart['items'])) {
    setFlashMessage('warning', 'Your shopping bag is empty. Please add items before checkout.');
    header('Location: ' . BASE_URL . 'shop.php');
    exit;
}

// Check for out of stock items
if ($cart['has_out_of_stock']) {
    setFlashMessage('danger', 'Some items in your shopping bag are out of stock or have limited availability. Please review your bag.');
    header('Location: ' . BASE_URL . 'cart.php');
    exit;
}

$pdo = getDb();

// Fetch saved addresses
$addrStmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
$addrStmt->execute([$currentUser['id']]);
$savedAddresses = $addrStmt->fetchAll();

$pageTitle = 'Secure Checkout | GLAIMAGAIN';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Banner -->
<div class="bg-emerald text-white py-4 border-bottom border-gold-subtle">
    <div class="container text-center">
        <span class="text-gold fw-bold letter-spacing-3 small text-uppercase">Encrypted Payment Gateway</span>
        <h1 class="h3 fw-bold text-white mb-0">SECURE CHECKOUT</h1>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4 g-lg-5 checkout-container-mobile">
        <!-- 1. Left Column: Address Selection & Notes -->
        <div class="col-lg-7 checkout-address-card-mobile">
            <div class="p-4 p-md-5 bg-white border border-gold-subtle rounded shadow-sm mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold text-emerald mb-0">1. DELIVERY DESTINATION</h4>
                    <span class="small text-muted"><i class="fas fa-user-check text-gold me-1"></i> Patron: <?= e($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?></span>
                </div>

                <form id="checkoutForm">
                    <?= csrfField() ?>

                    <div id="savedAddressesSection" class="<?= empty($savedAddresses) ? 'd-none' : '' ?>">
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label small fw-bold text-emerald text-uppercase letter-spacing-1 mb-0">Delivery Address</label>
                                <span class="badge bg-mint text-forest small"><i class="fas fa-check-circle me-1"></i> Saved to Account</span>
                            </div>
                            <div class="d-flex flex-column gap-3" id="savedAddressesContainer">
                                <?php if (!empty($savedAddresses)): ?>
                                    <?php foreach ($savedAddresses as $idx => $addr): ?>
                                        <label class="address-select-card p-3 border rounded <?= ($addr['is_default'] || $idx === 0) ? 'selected border-gold bg-offwhite' : 'border-light-gray' ?> d-flex align-items-start gap-3 cursor-pointer">
                                            <input type="radio" name="selected_address_id" value="<?= $addr['id'] ?>" <?= ($addr['is_default'] || $idx === 0) ? 'checked' : '' ?> class="mt-1">
                                            <div class="flex-grow-1 small">
                                                <div class="d-flex justify-content-between">
                                                    <strong class="text-emerald fs-6"><?= e($addr['full_name']) ?></strong>
                                                    <span class="badge <?= $addr['is_default'] ? 'bg-gold text-dark' : 'bg-secondary' ?> text-uppercase" style="font-size: 10px;">
                                                        <?= e($addr['address_type']) ?>
                                                    </span>
                                                </div>
                                                <div class="text-muted"><i class="fas fa-phone-alt text-gold me-1"></i> <?= e($addr['mobile']) ?></div>
                                                <div class="text-muted mt-1">
                                                    <?= e($addr['address_line_1']) ?><?= !empty($addr['address_line_2']) ? ', ' . e($addr['address_line_2']) : '' ?><br>
                                                    <?= !empty($addr['area']) ? e($addr['area']) . ', ' : '' ?><?= e($addr['city']) ?>, <?= e($addr['state']) ?> - <?= e($addr['pincode']) ?>
                                                </div>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <!-- Option for Alternate / New Address -->
                                <label class="address-select-card p-3 border rounded border-light-gray d-flex align-items-center gap-3 cursor-pointer">
                                    <input type="radio" name="selected_address_id" value="new" id="newAddressRadio" <?= empty($savedAddresses) ? 'checked' : '' ?>>
                                    <div class="fw-bold text-emerald small">
                                        <i class="fas fa-plus-circle text-gold me-2"></i> Deliver to a New / Alternate Address
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- New Address Inline Form Container -->
                    <div id="newAddressFields" class="<?= empty($savedAddresses) ? '' : 'd-none' ?> p-4 bg-offwhite border rounded mb-4 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-emerald text-uppercase letter-spacing-1 mb-0">Delivery Address Particulars</h6>
                            <span class="badge bg-gold text-dark small"><i class="fas fa-bolt me-1"></i> Auto-Save Enabled</span>
                        </div>
                        <div class="row g-3 form-luxury">
                            <div class="col-md-6">
                                <label class="form-label">Full Name <span class="text-gold">*</span></label>
                                <input type="text" id="new_full_name" class="form-control" value="<?= e($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?>" placeholder="Recipient Full Name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mobile Number <span class="text-gold">*</span></label>
                                <input type="tel" id="new_mobile" class="form-control" value="<?= e($currentUser['mobile']) ?>" placeholder="10-digit mobile number" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address Line 1 <span class="text-gold">*</span></label>
                                <input type="text" id="new_address_1" class="form-control" placeholder="Flat / House No., Building Name, Street" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address Line 2 (Landmark / Area)</label>
                                <input type="text" id="new_address_2" class="form-control" placeholder="Near landmark, sector, or colony">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">City <span class="text-gold">*</span></label>
                                <input type="text" id="new_city" class="form-control" placeholder="City" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">State <span class="text-gold">*</span></label>
                                <input type="text" id="new_state" class="form-control" placeholder="State" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Pincode <span class="text-gold">*</span></label>
                                <input type="text" id="new_pincode" class="form-control" placeholder="Pincode" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Address Type</label>
                                <select id="new_address_type" class="form-select">
                                    <option value="home">Home (All-day delivery)</option>
                                    <option value="office">Office (Delivery between 10 AM - 6 PM)</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-12 mt-3">
                                <div class="p-2 px-3 bg-white rounded border d-flex align-items-center justify-content-between">
                                    <div class="small text-muted">
                                        <i class="fas fa-check-circle text-success me-1"></i> Address will automatically save to your profile for future 1-click orders.
                                    </div>
                                    <button type="button" id="btnSaveAddressAjax" class="btn btn-sm btn-luxury-primary">
                                        <i class="fas fa-save me-1"></i> Save &amp; Select
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bespoke Tailoring & Concierge Notes -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-emerald text-uppercase letter-spacing-1">Order / Bespoke Packaging Notes (Optional)</label>
                        <textarea id="order_notes" rows="2" class="form-control" placeholder="Special delivery instructions, gift wrapping note, etc."></textarea>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. Right Column: Live Calculated Summary & Razorpay Trigger -->
        <div class="col-lg-5 checkout-summary-card-mobile">
            <div class="p-4 p-md-5 bg-white border border-gold-subtle rounded shadow-sm sticky-top" style="top: 100px;">
                <h5 class="cart-summary-title">2. COMMISSION SUMMARY</h5>

                <!-- Items list snapshot -->
                <div class="d-flex flex-column gap-3 mb-4 pb-3 border-bottom" style="max-height: 280px; overflow-y: auto;">
                    <?php foreach ($cart['items'] as $item): ?>
                        <div class="d-flex align-items-center gap-3">
                            <img src="<?= $item['image'] ?>" alt="<?= e($item['name']) ?>" style="width: 50px; height: 60px; object-fit: cover; border-radius: 2px;">
                            <div class="flex-grow-1 small">
                                <div class="fw-bold text-emerald text-truncate" style="max-width: 200px;"><?= e($item['name']) ?></div>
                                <div class="text-muted"><?= e($item['size']) ?> / <?= e($item['color']) ?> &bull; Qty: <?= $item['quantity'] ?></div>
                            </div>
                            <div class="fw-bold text-emerald small">
                                <?= formatPrice($item['subtotal']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-row">
                    <span>Subtotal</span>
                    <strong><?= formatPrice($cart['subtotal']) ?></strong>
                </div>

                <?php if ($cart['discount_amount'] > 0): ?>
                    <div class="summary-row text-success">
                        <span>Promotional Savings</span>
                        <strong>-<?= formatPrice($cart['discount_amount']) ?></strong>
                    </div>
                <?php endif; ?>

                <div class="summary-row">
                    <span>Express Dispatch</span>
                    <span>
                        <?php if ($cart['shipping_fee'] == 0): ?>
                            <strong class="text-success"><i class="fas fa-crown text-gold me-1"></i>COMPLIMENTARY</strong>
                        <?php else: ?>
                            <strong><?= formatPrice($cart['shipping_fee']) ?></strong>
                        <?php endif; ?>
                    </span>
                </div>

                <div class="summary-row total">
                    <span>Total Amount</span>
                    <span class="text-gold fs-5"><?= formatPrice($cart['grand_total']) ?></span>
                </div>

                <!-- Pay Button -->
                <button type="button" id="btnPayNow" class="btn btn-luxury-gold w-100 py-3 mt-4 btn-pay-mobile-cta">
                    <i class="fas fa-lock me-2"></i> PAY <?= formatPrice($cart['grand_total']) ?> WITH RAZORPAY
                </button>

                <div class="mt-4 pt-3 border-top text-center text-muted small">
                    <div class="d-flex justify-content-center align-items-center gap-2 mb-2">
                        <i class="fas fa-shield-alt text-gold"></i>
                        <span>Razorpay Instant 3D-Secure Authentication</span>
                    </div>
                    <div>Credit Card, Debit Card, UPI, Netbanking, &amp; Wallets Supported</div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Mobile Sticky Bottom Pay Bar (Amazon/Myntra Style for 1-Tap Mobile Payment) -->
<div class="checkout-mobile-sticky-bar d-lg-none">
    <div class="container d-flex align-items-center justify-content-between p-0">
        <div>
            <span class="d-block text-muted" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Grand Total</span>
            <strong class="text-forest fs-5"><?= formatPrice($cart['grand_total']) ?></strong>
        </div>
        <button type="button" class="btn btn-luxury-gold px-4 py-2" onclick="document.getElementById('btnPayNow').click();" style="border-radius: 9999px; font-weight: 700; font-size: 13px;">
            <i class="fas fa-lock me-1"></i> PAY NOW
        </button>
    </div>
</div>

<script>
// Toggle new address form when radio is clicked
document.querySelectorAll('input[name="selected_address_id"]').forEach(radio => {
    radio.addEventListener('change', () => {
        const newFields = document.getElementById('newAddressFields');
        if (radio.value === 'new') {
            newFields.classList.remove('d-none');
        } else {
            newFields.classList.add('d-none');
        }
    });
});
</script>

<?php 
$extraScripts = '<script src="https://checkout.razorpay.com/v1/checkout.js"></script><script src="' . BASE_URL . 'assets/js/checkout.js?v=' . time() . '"></script>';
require_once __DIR__ . '/includes/footer.php'; 
?>

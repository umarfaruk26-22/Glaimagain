<?php
/**
 * GLAIMAGAIN - Shopping Bag Page
 */
require_once __DIR__ . '/config/config.php';

$cart = getCartDetails();
$pageTitle = 'Shopping Bag (' . $cart['total_quantity'] . ') | GLAIMAGAIN';
$metaDescription = 'Review your selected luxury pieces in your GLAIMAGAIN shopping bag.';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Banner -->
<div class="bg-emerald text-white py-4 border-bottom border-gold-subtle">
    <div class="container text-center">
        <span class="text-gold fw-bold letter-spacing-3 small text-uppercase">Your Selection</span>
        <h1 class="h2 fw-bold text-white mb-0">SHOPPING BAG</h1>
    </div>
</div>

<div class="container py-5">
    <?php if (empty($cart['items'])): ?>
        <div class="text-center py-5 my-5 bg-offwhite border rounded p-5">
            <i class="fas fa-shopping-bag text-gold fs-1 mb-3"></i>
            <h3 class="text-emerald fw-bold mb-2">Your Shopping Bag is Empty</h3>
            <p class="text-muted small max-w-500 mx-auto mb-4">Discover our signature heavyweight tees, tailored velvet blazers, and Egyptian cotton shirts.</p>
            <a href="<?= BASE_URL ?>shop.php" class="btn btn-luxury-primary">
                <i class="fas fa-arrow-left me-2"></i> EXPLORE THE COLLECTION
            </a>
        </div>
    <?php else: ?>
        <div class="row g-5">
            <!-- 1. Cart Items -->
            <div class="col-lg-8">
                <!-- Desktop Table View -->
                <div class="table-responsive d-none d-md-block">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Garment Item</th>
                                <th class="text-center">Price</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-end">Subtotal</th>
                                <th class="text-center"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart['items'] as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <a href="<?= BASE_URL ?>product.php?slug=<?= urlencode($item['slug']) ?>">
                                                <img src="<?= $item['image'] ?>" alt="<?= e($item['name']) ?>" style="width: 70px; height: 85px; object-fit: cover; border-radius: 6px; border: 1px solid #E5E7EB;">
                                            </a>
                                            <div>
                                                <h6 class="fw-bold text-emerald mb-1">
                                                    <a href="<?= BASE_URL ?>product.php?slug=<?= urlencode($item['slug']) ?>"><?= e($item['name']) ?></a>
                                                </h6>
                                                <div class="small text-muted mb-1">
                                                    <span class="badge bg-light text-dark border me-1">Size: <?= e($item['size']) ?></span>
                                                    <span class="badge bg-light text-dark border">Color: <?= e($item['color']) ?></span>
                                                </div>
                                                <div class="small text-muted">SKU: <?= e($item['sku']) ?></div>
                                                <?php if (!$item['is_available']): ?>
                                                    <div class="badge bg-danger text-white mt-1">Out of Stock / Limited</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <strong class="text-emerald"><?= formatPrice($item['unit_price']) ?></strong>
                                        <?php if ($item['original_price'] > $item['unit_price']): ?>
                                            <div class="small text-muted text-decoration-line-through"><?= formatPrice($item['original_price']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="quantity-control mx-auto">
                                            <button type="button" class="quantity-btn cart-qty-btn btn-minus" data-cart-item-id="<?= $item['cart_item_id'] ?>">
                                                <i class="fas fa-minus" style="font-size: 10px;"></i>
                                            </button>
                                            <input type="text" class="quantity-input" value="<?= $item['quantity'] ?>" readonly>
                                            <button type="button" class="quantity-btn cart-qty-btn btn-plus" data-cart-item-id="<?= $item['cart_item_id'] ?>">
                                                <i class="fas fa-plus" style="font-size: 10px;"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-emerald fs-6"><?= formatPrice($item['subtotal']) ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-link text-danger p-0 btn-remove-cart-item" data-cart-item-id="<?= $item['cart_item_id'] ?>" title="Remove Item">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card List View (Phones) -->
                <div class="d-md-none mb-3">
                    <?php foreach ($cart['items'] as $item): ?>
                        <div class="p-3 bg-white border border-mint rounded-3 mb-3 shadow-sm position-relative">
                            <button type="button" class="btn btn-link text-danger p-0 btn-remove-cart-item position-absolute" style="top: 12px; right: 12px;" data-cart-item-id="<?= $item['cart_item_id'] ?>" title="Remove Item">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                            <div class="d-flex gap-3">
                                <a href="<?= BASE_URL ?>product.php?slug=<?= urlencode($item['slug']) ?>" class="flex-shrink-0">
                                    <img src="<?= $item['image'] ?>" alt="<?= e($item['name']) ?>" style="width: 75px; height: 95px; object-fit: cover; border-radius: 8px; border: 1px solid #E5E7EB;">
                                </a>
                                <div class="flex-grow-1 pe-3">
                                    <h6 class="fw-bold text-emerald mb-1 font-serif" style="font-size: 14px; line-height: 1.3;">
                                        <a href="<?= BASE_URL ?>product.php?slug=<?= urlencode($item['slug']) ?>"><?= e($item['name']) ?></a>
                                    </h6>
                                    <div class="small text-muted mb-2">
                                        <span class="badge bg-mint-light text-forest border border-mint me-1">Size: <?= e($item['size']) ?></span>
                                        <span class="badge bg-mint-light text-forest border border-mint">Color: <?= e($item['color']) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <div class="quantity-control" style="padding: 2px 4px;">
                                            <button type="button" class="quantity-btn cart-qty-btn btn-minus" style="width: 28px; height: 28px;" data-cart-item-id="<?= $item['cart_item_id'] ?>">
                                                <i class="fas fa-minus" style="font-size: 9px;"></i>
                                            </button>
                                            <input type="text" class="quantity-input" style="width: 32px; font-size: 13px;" value="<?= $item['quantity'] ?>" readonly>
                                            <button type="button" class="quantity-btn cart-qty-btn btn-plus" style="width: 28px; height: 28px;" data-cart-item-id="<?= $item['cart_item_id'] ?>">
                                                <i class="fas fa-plus" style="font-size: 9px;"></i>
                                            </button>
                                        </div>
                                        <strong class="text-emerald" style="font-size: 15px;"><?= formatPrice($item['subtotal']) ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                    <a href="<?= BASE_URL ?>shop.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Continue Shopping
                    </a>
                </div>
            </div>

            <!-- 2. Order Summary Card -->
            <div class="col-lg-4">
                <div class="cart-summary-card">
                    <h5 class="cart-summary-title">BAG SUMMARY</h5>

                    <div class="summary-row">
                        <span>Bag Subtotal (<?= $cart['total_quantity'] ?> items)</span>
                        <strong><?= formatPrice($cart['subtotal']) ?></strong>
                    </div>

                    <?php if ($cart['discount_amount'] > 0): ?>
                        <div class="summary-row text-success">
                            <span>Promotional Savings</span>
                            <strong>-<?= formatPrice($cart['discount_amount']) ?></strong>
                        </div>
                    <?php endif; ?>

                    <div class="summary-row">
                        <span>Express Delivery</span>
                        <span>
                            <?php if ($cart['shipping_fee'] == 0): ?>
                                <strong class="text-success"><i class="fas fa-crown text-gold me-1"></i>COMPLIMENTARY</strong>
                            <?php else: ?>
                                <strong><?= formatPrice($cart['shipping_fee']) ?></strong>
                            <?php endif; ?>
                        </span>
                    </div>

                    <?php if ($cart['shipping_fee'] > 0): 
                        $remainingForFree = max(0, (float)getSetting('free_shipping_threshold', (string)FREE_SHIPPING_THRESHOLD) - $cart['subtotal']);
                    ?>
                        <div class="p-2 bg-offwhite border rounded small text-muted mb-3">
                            <i class="fas fa-info-circle text-gold me-1"></i> Add <strong><?= formatPrice($remainingForFree) ?></strong> more to unlock complimentary shipping.
                        </div>
                    <?php endif; ?>

                    <div class="summary-row total">
                        <span>Grand Total</span>
                        <span class="total-price-simple"><?= formatPrice($cart['grand_total']) ?></span>
                    </div>

                    <a href="<?= BASE_URL ?>checkout.php" class="btn btn-luxury-gold w-100 py-3 mt-3 <?= $cart['has_out_of_stock'] ? 'disabled' : '' ?>">
                        <i class="fas fa-lock me-2"></i> PROCEED TO SECURE CHECKOUT
                    </a>

                    <div class="mt-4 text-center small text-muted">
                        <div class="mb-1"><i class="fas fa-shield-alt text-gold me-1"></i> 256-Bit SSL Encrypted Razorpay Checkout</div>
                        <div><i class="fas fa-undo text-gold me-1"></i> 7-Day Complimented Returns &amp; Exchanges</div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

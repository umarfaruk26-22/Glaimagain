/**
 * GLAIMAGAIN - Cart AJAX Operations
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Add to Cart Form Handler (Product Details Page)
    const addToCartForm = document.getElementById('addToCartForm');
    if (addToCartForm) {
        addToCartForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = addToCartForm.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;

            const productId = addToCartForm.querySelector('[name="product_id"]')?.value;
            const size = addToCartForm.querySelector('[name="size"]:checked')?.value || addToCartForm.querySelector('[name="size"]')?.value;
            const color = addToCartForm.querySelector('[name="color"]:checked')?.value || addToCartForm.querySelector('[name="color"]')?.value;
            const quantity = parseInt(addToCartForm.querySelector('[name="quantity"]')?.value || '1', 10);
            const csrfToken = addToCartForm.querySelector('[name="csrf_token"]')?.value;

            if (!size) {
                showToast('Please select your preferred Size.', 'error');
                return;
            }

            if (!color) {
                showToast('Please select your preferred Color.', 'error');
                return;
            }

            try {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Adding to Bag...';

                const baseUrl = window.GLAIMAGAIN_BASE_URL || './';
                const response = await fetch(`${baseUrl}api/cart/add.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        size: size,
                        color: color,
                        quantity: quantity,
                        csrf_token: csrfToken
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showToast(data.message || 'Item added to your shopping bag!', 'success');
                    updateCartBadge();
                } else {
                    showToast(data.message || 'Failed to add item.', 'error');
                }
            } catch (err) {
                showToast('Network error while adding to bag.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    }

    // 2. Quantity Increments & Decrements in Cart View
    document.querySelectorAll('.cart-qty-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const input = btn.closest('.quantity-control').querySelector('.quantity-input');
            const cartItemId = btn.dataset.cartItemId;
            let currentVal = parseInt(input.value, 10) || 1;
            const isPlus = btn.classList.contains('btn-plus');

            if (isPlus) {
                currentVal += 1;
            } else {
                currentVal = Math.max(1, currentVal - 1);
            }

            input.value = currentVal;
            await updateCartItemQuantity(cartItemId, currentVal);
        });
    });

    // 3. Remove Cart Item
    document.querySelectorAll('.btn-remove-cart-item').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const cartItemId = btn.dataset.cartItemId;
            if (!confirm('Remove this luxury item from your shopping bag?')) {
                return;
            }

            try {
                const baseUrl = window.GLAIMAGAIN_BASE_URL || './';
                const response = await fetch(`${baseUrl}api/cart/remove.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cart_item_id: cartItemId })
                });
                const data = await response.json();
                if (data.success) {
                    showToast('Item removed from shopping bag.', 'success');
                    setTimeout(() => window.location.reload(), 300);
                } else {
                    showToast(data.message || 'Could not remove item.', 'error');
                }
            } catch (err) {
                showToast('Network error removing item.', 'error');
            }
        });
    });
});

/**
 * Send Quantity Update to Backend and Recalculate
 */
async function updateCartItemQuantity(cartItemId, quantity) {
    try {
        const baseUrl = window.GLAIMAGAIN_BASE_URL || './';
        const response = await fetch(`${baseUrl}api/cart/update.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                cart_item_id: cartItemId,
                quantity: quantity
            })
        });
        const data = await response.json();
        if (data.success) {
            window.location.reload();
        } else {
            showToast(data.message || 'Could not update quantity.', 'error');
        }
    } catch (e) {
        showToast('Network error updating quantity.', 'error');
    }
}

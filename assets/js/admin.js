/**
 * GLAIMAGAIN - Admin Management JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Sidebar Toggle
    const sidebarToggleBtn = document.getElementById('sidebarToggle');
    const adminSidebar = document.querySelector('.admin-sidebar');
    if (sidebarToggleBtn && adminSidebar) {
        sidebarToggleBtn.addEventListener('click', () => {
            adminSidebar.classList.toggle('show');
        });
    }

    // 2. Real-time Live Discount Percentage Calculator in Admin Product Forms
    const originalPriceInput = document.getElementById('original_price');
    const sellingPriceInput = document.getElementById('selling_price');
    const discountDisplay = document.getElementById('discount_preview');

    function updateDiscountCalculation() {
        if (!originalPriceInput || !sellingPriceInput || !discountDisplay) return;
        const orig = parseFloat(originalPriceInput.value) || 0;
        const sell = parseFloat(sellingPriceInput.value) || 0;

        if (orig > 0 && sell > 0 && orig >= sell) {
            const pct = Math.round(((orig - sell) / orig) * 100);
            discountDisplay.innerHTML = `<span class="badge bg-gold text-dark py-2 px-3 fw-bold" style="font-size: 13px;"><i class="fas fa-tag me-1"></i> ${pct}% OFF</span> <small class="text-muted ms-2">(Saving: ₹${(orig - sell).toFixed(2)})</small>`;
        } else if (sell > orig && orig > 0) {
            discountDisplay.innerHTML = `<span class="badge bg-danger py-2 px-3"><i class="fas fa-exclamation-triangle me-1"></i> Selling price cannot exceed original price</span>`;
        } else {
            discountDisplay.innerHTML = `<span class="text-muted small">Enter prices above to calculate live discount</span>`;
        }
    }

    if (originalPriceInput && sellingPriceInput) {
        originalPriceInput.addEventListener('input', updateDiscountCalculation);
        sellingPriceInput.addEventListener('input', updateDiscountCalculation);
        updateDiscountCalculation();
    }

    // 3. Image File Upload Preview
    const imageInput = document.querySelector('input[type="file"][name="product_images[]"], input[type="file"][name="image"]');
    const previewContainer = document.getElementById('imagePreviewContainer');

    if (imageInput && previewContainer) {
        imageInput.addEventListener('change', () => {
            previewContainer.innerHTML = '';
            if (imageInput.files) {
                Array.from(imageInput.files).forEach(file => {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            const imgWrap = document.createElement('div');
                            imgWrap.className = 'col-3 mb-2';
                            imgWrap.innerHTML = `
                                <div class="border rounded p-1 text-center bg-light">
                                    <img src="${e.target.result}" class="img-fluid rounded" style="height: 90px; object-fit: cover;">
                                    <div class="small text-truncate mt-1 text-muted">${file.name}</div>
                                </div>
                            `;
                            previewContainer.appendChild(imgWrap);
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
        });
    }

    // 4. Confirmation on Destructive Actions
    document.querySelectorAll('.btn-confirm-delete').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const message = btn.dataset.confirmMessage || 'Are you sure you want to perform this action? This cannot be undone.';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
});

/**
 * GLAIMAGAIN - Main Global JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Sticky Header Scroll Effect
    const header = document.querySelector('.main-header, .main-header-clean');
    if (header) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 40) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    }

    // 2. Search Modal Overlay Toggle
    const searchTrigger = document.querySelector('.btn-search-trigger');
    const searchOverlay = document.querySelector('.search-overlay');
    const searchClose = document.querySelector('.btn-search-close');
    const searchInput = document.querySelector('.search-input-luxury');

    if (searchTrigger && searchOverlay) {
        searchTrigger.addEventListener('click', (e) => {
            e.preventDefault();
            searchOverlay.classList.add('active');
            setTimeout(() => {
                if (searchInput) searchInput.focus();
            }, 100);
        });

        if (searchClose) {
            searchClose.addEventListener('click', () => {
                searchOverlay.classList.remove('active');
            });
        }

        searchOverlay.addEventListener('click', (e) => {
            if (e.target === searchOverlay) {
                searchOverlay.classList.remove('active');
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && searchOverlay.classList.contains('active')) {
                searchOverlay.classList.remove('active');
            }
        });
    }

    // 3. Update Cart Badge Count
    updateCartBadge();
});

/**
 * Global Toast Notification Helper
 */
function showToast(message, type = 'success') {
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.position = 'fixed';
        toastContainer.style.bottom = '25px';
        toastContainer.style.right = '25px';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }

    const toast = document.createElement('div');
    const isSuccess = type === 'success';
    const bg = isSuccess ? '#013C26' : '#9B2C2C';
    const icon = isSuccess ? 'fa-check-circle' : 'fa-exclamation-circle';

    toast.className = 'luxury-toast animate__animated animate__fadeInUp';
    toast.style.background = bg;
    toast.style.color = '#FFFFFF';
    toast.style.border = '1px solid #B99036';
    toast.style.padding = '14px 22px';
    toast.style.borderRadius = '4px';
    toast.style.boxShadow = '0 10px 30px rgba(0,0,0,0.25)';
    toast.style.marginBottom = '10px';
    toast.style.display = 'flex';
    toast.style.alignItems = 'center';
    toast.style.gap = '12px';
    toast.style.fontSize = '14px';
    toast.style.fontFamily = "'Poppins', sans-serif";
    toast.innerHTML = `
        <i class="fas ${icon}" style="color:#D4AF37; font-size:16px;"></i>
        <span>${message}</span>
    `;

    toastContainer.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.4s ease';
        setTimeout(() => toast.remove(), 400);
    }, 3500);
}

/**
 * Fetch and update cart badge count
 */
async function updateCartBadge() {
    try {
        const badge = document.querySelector('.badge-cart');
        if (!badge) return;

        const baseUrl = window.GLAIMAGAIN_BASE_URL || './';
        const response = await fetch(`${baseUrl}api/cart/count.php`);
        if (response.ok) {
            const data = await response.json();
            if (data && typeof data.count !== 'undefined') {
                badge.textContent = data.count;
                badge.style.display = data.count > 0 ? 'inline-block' : 'none';
            }
        }
    } catch (e) {
        console.warn('Cart count error:', e);
    }
}

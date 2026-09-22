/**
 * GLAIMAGAIN - Checkout & Razorpay Payment Integration
 */

document.addEventListener('DOMContentLoaded', () => {
    const checkoutForm = document.getElementById('checkoutForm');
    const payNowBtn = document.getElementById('btnPayNow');

    if (!checkoutForm || !payNowBtn) return;

    const baseUrl = window.GLAIMAGAIN_BASE_URL || './';

    // Address selection radio toggle styling
    function attachAddressRadioListeners() {
        const addressRadios = document.querySelectorAll('input[name="selected_address_id"]');
        addressRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                document.querySelectorAll('.address-select-card').forEach(card => card.classList.remove('selected', 'border-gold', 'bg-offwhite'));
                const card = radio.closest('.address-select-card');
                if (card) {
                    card.classList.add('selected', 'border-gold', 'bg-offwhite');
                }
                const newFields = document.getElementById('newAddressFields');
                if (radio.value === 'new') {
                    if (newFields) newFields.classList.remove('d-none');
                } else {
                    if (newFields) newFields.classList.add('d-none');
                }
            });
        });
    }
    attachAddressRadioListeners();

    // AJAX Save Address Button
    const saveAddrBtn = document.getElementById('btnSaveAddressAjax');
    if (saveAddrBtn) {
        saveAddrBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            const fullName = document.getElementById('new_full_name')?.value.trim();
            const mobile = document.getElementById('new_mobile')?.value.trim();
            const address1 = document.getElementById('new_address_1')?.value.trim();
            const address2 = document.getElementById('new_address_2')?.value.trim() || '';
            const city = document.getElementById('new_city')?.value.trim();
            const state = document.getElementById('new_state')?.value.trim();
            const pincode = document.getElementById('new_pincode')?.value.trim();
            const addressType = document.getElementById('new_address_type')?.value || 'home';
            const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';

            if (!fullName || !mobile || !address1 || !city || !state || !pincode) {
                showToast('Please complete all required address fields (*).', 'error');
                return;
            }

            const origHtml = saveAddrBtn.innerHTML;
            saveAddrBtn.disabled = true;
            saveAddrBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';

            try {
                const res = await fetch(`${baseUrl}api/address/save.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        full_name: fullName,
                        mobile: mobile,
                        address_line_1: address1,
                        address_line_2: address2,
                        city: city,
                        state: state,
                        pincode: pincode,
                        address_type: addressType,
                        is_default: 1,
                        csrf_token: csrfToken
                    })
                });

                const data = await res.json();
                if (data.success && data.address) {
                    const addr = data.address;
                    const savedSection = document.getElementById('savedAddressesSection');
                    const container = document.getElementById('savedAddressesContainer');
                    const newRadioCard = document.getElementById('newAddressRadio')?.closest('.address-select-card');

                    const newCardLabel = document.createElement('label');
                    newCardLabel.className = 'address-select-card p-3 border rounded selected border-gold bg-offwhite d-flex align-items-start gap-3 cursor-pointer mb-2';
                    newCardLabel.innerHTML = `
                        <input type="radio" name="selected_address_id" value="${addr.id}" checked class="mt-1">
                        <div class="flex-grow-1 small">
                            <div class="d-flex justify-content-between">
                                <strong class="text-emerald fs-6">${escapeHtml(addr.full_name)}</strong>
                                <span class="badge bg-gold text-dark text-uppercase" style="font-size: 10px;">
                                    ${escapeHtml(addr.address_type)}
                                </span>
                            </div>
                            <div class="text-muted"><i class="fas fa-phone-alt text-gold me-1"></i> ${escapeHtml(addr.mobile)}</div>
                            <div class="text-muted mt-1">
                                ${escapeHtml(addr.address_line_1)}${addr.address_line_2 ? ', ' + escapeHtml(addr.address_line_2) : ''}<br>
                                ${escapeHtml(addr.city)}, ${escapeHtml(addr.state)} - ${escapeHtml(addr.pincode)}
                            </div>
                        </div>
                    `;

                    if (newRadioCard) {
                        container.insertBefore(newCardLabel, newRadioCard);
                    } else {
                        container.appendChild(newCardLabel);
                    }

                    if (savedSection) savedSection.classList.remove('d-none');
                    const newFields = document.getElementById('newAddressFields');
                    if (newFields) newFields.classList.add('d-none');

                    attachAddressRadioListeners();
                    showToast('Address saved & selected for this delivery!', 'success');
                } else {
                    showToast(data.message || 'Failed to save address.', 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Network error while saving address.', 'error');
            } finally {
                saveAddrBtn.disabled = false;
                saveAddrBtn.innerHTML = origHtml;
            }
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>'"]/g, tag => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;'
        }[tag] || tag));
    }

    payNowBtn.addEventListener('click', async (e) => {
        e.preventDefault();

        // 1. Verify Address
        const selectedAddress = document.querySelector('input[name="selected_address_id"]:checked');
        const newAddressBlock = document.getElementById('newAddressFields');
        const isNewAddress = selectedAddress && selectedAddress.value === 'new';

        if (!selectedAddress) {
            showToast('Please select a delivery address.', 'error');
            return;
        }

        let addressData = {
            address_id: selectedAddress.value
        };

        if (isNewAddress) {
            const fullName = document.getElementById('new_full_name')?.value.trim();
            const mobile = document.getElementById('new_mobile')?.value.trim();
            const address1 = document.getElementById('new_address_1')?.value.trim();
            const city = document.getElementById('new_city')?.value.trim();
            const state = document.getElementById('new_state')?.value.trim();
            const pincode = document.getElementById('new_pincode')?.value.trim();
            const addressType = document.getElementById('new_address_type')?.value || 'home';

            if (!fullName || !mobile || !address1 || !city || !state || !pincode) {
                showToast('Please complete all required address fields.', 'error');
                return;
            }

            addressData = {
                address_id: 'new',
                full_name: fullName,
                mobile: mobile,
                address_line_1: address1,
                address_line_2: document.getElementById('new_address_2')?.value.trim() || '',
                area: document.getElementById('new_area')?.value.trim() || '',
                city: city,
                state: state,
                pincode: pincode,
                address_type: addressType
            };
        }

        const originalBtnText = payNowBtn.innerHTML;

        try {
            payNowBtn.disabled = true;
            payNowBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Initializing Secure Payment...';

            const baseUrl = window.GLAIMAGAIN_BASE_URL || './';
            const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';

            // 2. Request backend to validate cart, calculate final amount, and generate Razorpay Order
            const orderRes = await fetch(`${baseUrl}api/payment/create-order.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    ...addressData,
                    notes: document.getElementById('order_notes')?.value || '',
                    csrf_token: csrfToken
                })
            });

            const orderData = await orderRes.json();

            if (!orderData.success) {
                showToast(orderData.message || 'Unable to initiate order.', 'error');
                payNowBtn.disabled = false;
                payNowBtn.innerHTML = originalBtnText;
                return;
            }

            // 3. Launch official Razorpay Checkout Modal
            const options = {
                key: orderData.razorpay_key_id,
                amount: orderData.amount, // in paise
                currency: orderData.currency || 'INR',
                name: 'GLAIMAGAIN',
                description: 'Fashion Beyond Today - Order #' + orderData.order_number,
                image: `${baseUrl}assets/images/favicon.svg`,
                order_id: orderData.razorpay_order_id,
                prefill: {
                    name: orderData.customer_name,
                    email: orderData.customer_email,
                    contact: orderData.customer_phone
                },
                theme: {
                    color: '#013C26'
                },
                modal: {
                    ondismiss: function () {
                        payNowBtn.disabled = false;
                        payNowBtn.innerHTML = originalBtnText;
                        showToast('Payment window closed. You can retry payment anytime.', 'warning');
                    }
                },
                handler: async function (response) {
                    // 4. Send Razorpay payment credentials to backend for HMAC verification
                    payNowBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Verifying Payment Signature...';
                    
                    try {
                        const verifyRes = await fetch(`${baseUrl}api/payment/verify.php`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({
                                order_id: orderData.internal_order_id,
                                razorpay_order_id: response.razorpay_order_id,
                                razorpay_payment_id: response.razorpay_payment_id,
                                razorpay_signature: response.razorpay_signature,
                                csrf_token: csrfToken
                            })
                        });

                        const verifyData = await verifyRes.json();

                        if (verifyData.success) {
                            window.location.href = `${baseUrl}order-success.php?order=${orderData.order_number}`;
                        } else {
                            window.location.href = `${baseUrl}order-failed.php?order=${orderData.order_number}&error=${encodeURIComponent(verifyData.message || 'Payment verification failed')}`;
                        }
                    } catch (vErr) {
                        window.location.href = `${baseUrl}order-failed.php?order=${orderData.order_number}&error=VerificationNetworkError`;
                    }
                }
            };

            const rzp = new Razorpay(options);
            rzp.on('payment.failed', function (response) {
                window.location.href = `${baseUrl}order-failed.php?order=${orderData.order_number}&error=${encodeURIComponent(response.error.description || 'Payment Failed')}`;
            });
            rzp.open();

        } catch (err) {
            console.error(err);
            showToast('An unexpected checkout error occurred. Please try again.', 'error');
            payNowBtn.disabled = false;
            payNowBtn.innerHTML = originalBtnText;
        }
    });
});

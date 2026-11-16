"use strict";

document.addEventListener('DOMContentLoaded', function () {
    const selectors = document.querySelectorAll('.product-size-selector');

    selectors.forEach(function (selector) {
        const buttons = selector.querySelectorAll('.size-option');

        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                buttons.forEach(function (item) {
                    item.classList.remove('active');
                });

                button.classList.add('active');
                const productId = selector.dataset.productId || '0';
                const size = button.dataset.size || '';

                if (productId && size) {
                    selector.setAttribute('data-selected-size', size);
                }
            });
        });
    });

    // Add to cart via AJAX
    const addButtons = document.querySelectorAll('.add-to-cart');
    const toast = document.getElementById('cart-toast');
    const toastMsg = document.getElementById('toast-message');
    const cartCountEl = document.getElementById('cart-count');

    function showToast(message) {
        if (!toast || !toastMsg) return;
        toastMsg.textContent = message;
        toast.classList.add('show');
        setTimeout(function () {
            toast.classList.remove('show');
        }, 2500);
    }

    addButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const productId = this.dataset.productId;
            if (!productId) return;

            // Find the size selector for this product
            const card = this.closest('.product-card') || this.closest('[data-product-id="' + productId + '"]');
            const selector = card ? card.querySelector('.product-size-selector') : null;
            const size = selector ? selector.getAttribute('data-selected-size') : '';

            // Find quantity input
            const qtyInput = card ? card.querySelector('.qty-input[data-product-id="' + productId + '"]') : null;
            const qty = qtyInput ? qtyInput.value : '1';

            if (!size) {
                showToast('Vui lòng chọn kích thước');
                return;
            }

            const originalText = btn.textContent;
            btn.textContent = 'Đang thêm...';
            btn.disabled = true;

            fetch('add_to_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'product_id=' + encodeURIComponent(productId) +
                      '&size=' + encodeURIComponent(size) +
                      '&qty=' + encodeURIComponent(qty)
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                btn.textContent = originalText;
                btn.disabled = false;

                if (data.success) {
                    showToast(data.message || 'Đã thêm vào giỏ hàng');
                    if (cartCountEl) cartCountEl.textContent = data.cart_count;
                } else {
                    showToast(data.message || 'Thêm vào giỏ hàng thất bại');
                }
            })
            .catch(function () {
                btn.textContent = originalText;
                btn.disabled = false;
                showToast('Lỗi kết nối, vui lòng thử lại');
            });
        });
    });
});

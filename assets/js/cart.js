"use strict";

document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".js-cart-qty-form").forEach(function (form) {
        const input = form.querySelector(".js-qty-input");
        const decBtn = form.querySelector(".js-qty-dec");
        const incBtn = form.querySelector(".js-qty-inc");
        if (!input) return;

        const minVal = parseInt(input.getAttribute("min") || "1", 10);
        const maxVal = parseInt(input.getAttribute("max") || "9999", 10);

        if (decBtn) {
            decBtn.addEventListener("click", function () {
                const current = parseInt(input.value || "1", 10);
                if (current > minVal) {
                    input.value = String(current - 1);
                    form.submit();
                }
            });
        }

        if (incBtn) {
            incBtn.addEventListener("click", function () {
                const current = parseInt(input.value || "1", 10);
                if (current < maxVal) {
                    input.value = String(current + 1);
                    form.submit();
                } else {
                    alert("Số lượng đã đạt mức tồn kho tối đa (" + maxVal + ").");
                }
            });
        }
    });

    document.querySelectorAll(".js-cart-remove-form").forEach(function (form) {
        form.addEventListener("submit", function (event) {
            if (!window.confirm("Bạn có chắc muốn xóa sản phẩm này khỏi giỏ hàng?")) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll(".js-coupon-input").forEach(function (input) {
        input.addEventListener("input", function () {
            input.value = input.value.toUpperCase().trimStart();
        });
    });
});
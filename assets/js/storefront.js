"use strict";

document.addEventListener("DOMContentLoaded", function () {
    const fallbackImage = "/flora/assets/images/no-image.svg";
    document.querySelectorAll(".ph img, .hero-image img, .flora-thumbnail img").forEach(function (image) {
        function useFallback() {
            if (image.dataset.fallbackApplied) return;
            image.dataset.fallbackApplied = "1";
            image.src = fallbackImage;
        }
        image.addEventListener("error", useFallback);
        if (image.complete && image.naturalWidth === 0) useFallback();
    });

    if ("IntersectionObserver" in window) {
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add("is-visible");
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        document.querySelectorAll(".reveal-on-scroll").forEach(function (section) {
            section.classList.add("reveal-pending");
            observer.observe(section);
        });
    }

    const detailImage = document.getElementById("detail-image");
    document.querySelectorAll("[data-gallery-src]").forEach(function (button) {
        button.addEventListener("click", function () {
            if (detailImage) {
                delete detailImage.dataset.fallbackApplied;
                detailImage.src = button.dataset.gallerySrc;
            }
        });
    });

    const form = document.getElementById("add-to-cart-form");
    const select = document.getElementById("variant");
    const quantity = document.getElementById("cart-quantity");
    const button = document.getElementById("add-to-cart-button");
    const stockMessage = document.getElementById("variant-stock");
    const priceElement = document.getElementById("variant-price");
    if (!form || !select || !quantity || !button) return;
    const formatter = new Intl.NumberFormat("vi-VN");

    function updateControls() {
        const option = select.options[select.selectedIndex];
        const stock = Number(option?.dataset.stock || 0);
        const canAdd = Boolean(option?.value) && stock > 0;
        button.disabled = !canAdd;
        quantity.disabled = !canAdd;
        if (canAdd) {
            quantity.max = String(stock);
            const amount = Number(quantity.value);
            if (!Number.isInteger(amount) || amount < 1) quantity.value = "1";
            else if (amount > stock) quantity.value = String(stock);
        } else {
            quantity.removeAttribute("max");
        }
        if (stockMessage) {
            stockMessage.textContent = !option?.value ? "Chọn màu và kích thước để xem tồn kho."
                : stock <= 0 ? "Biến thể này hiện đã hết hàng."
                : stock <= 5 ? "Chỉ còn " + stock + " sản phẩm." : "Còn hàng (" + stock + ").";
            stockMessage.className = stock <= 0 && option?.value ? "note out" : "note";
        }
        if (option?.value && priceElement) {
            priceElement.textContent = formatter.format(Number(option.dataset.price)) + "₫";
        }
        if (option?.value && option.dataset.image && detailImage) {
            delete detailImage.dataset.fallbackApplied;
            detailImage.src = option.dataset.image;
        }
    }
    select.addEventListener("change", updateControls);
    form.addEventListener("submit", function (event) {
        const option = select.options[select.selectedIndex];
        const amount = Number(quantity.value);
        const stock = Number(option?.dataset.stock || 0);
        if (!option?.value || !Number.isInteger(amount) || amount < 1 || amount > stock) {
            event.preventDefault();
            if (stockMessage) stockMessage.textContent = "Vui lòng chọn biến thể và số lượng phù hợp với tồn kho.";
        }
    });
    updateControls();
});

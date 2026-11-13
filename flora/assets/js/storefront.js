"use strict";

document.addEventListener("DOMContentLoaded", function () {
    const fallbackImage = "assets/images/no-image.svg";

    const revealSections = document.querySelectorAll(".reveal-on-scroll");
    if ("IntersectionObserver" in window) {
        revealSections.forEach(function (section) {
            section.classList.add("reveal-pending");
        });

        const observer = new IntersectionObserver(function (entries, currentObserver) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add("is-visible");
                    currentObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });

        revealSections.forEach(function (section) {
            observer.observe(section);
        });
    }

    document.querySelectorAll(".ph img, .hero-image img").forEach(function (image) {
        image.addEventListener("error", function () {
            if (!image.src.endsWith(fallbackImage)) {
                image.src = fallbackImage;
            }
        });
    });

    const variantSelect = document.getElementById("variant");
    if (!variantSelect) {
        return;
    }

    const stockMessage = document.getElementById("variant-stock");
    const priceElement = document.getElementById("variant-price");
    const detailImage = document.getElementById("detail-image");
    const formatter = new Intl.NumberFormat("vi-VN");

    variantSelect.addEventListener("change", function () {
        const option = variantSelect.options[variantSelect.selectedIndex];
        if (!option.value) {
            stockMessage.textContent = "Chọn màu và kích thước để xem tồn kho.";
            stockMessage.className = "note";
            return;
        }

        const stock = Number(option.dataset.stock);
        stockMessage.textContent = stock === 0
            ? "Biến thể này hiện đã hết hàng."
            : stock <= 5
                ? "Chỉ còn " + stock + " sản phẩm."
                : "Còn hàng (" + stock + ").";
        stockMessage.className = stock === 0 ? "note out" : stock <= 5 ? "note lo" : "note";
        priceElement.textContent = formatter.format(Number(option.dataset.price)) + "₫";

        if (option.dataset.image) {
            detailImage.src = option.dataset.image;
        }
    });
});

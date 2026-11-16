"use strict";

document.addEventListener("DOMContentLoaded", function () {
  const checkoutForm = document.getElementById("checkout-form");
  const submitBtn = document.getElementById("btn-submit-order");

  if (!checkoutForm || !submitBtn) {
    return;
  }

  const defaultBtnText = submitBtn.textContent;
  let isSubmitting = false;

  // Khôi phục trạng thái nút nếu người dùng bấm Back trên trình duyệt (bfcache)
  window.addEventListener("pageshow", function (event) {
    if (event.persisted) {
      isSubmitting = false;
      submitBtn.disabled = false;
      submitBtn.textContent = defaultBtnText;
    }
  });

  checkoutForm.addEventListener("submit", function (event) {
    if (isSubmitting) {
      event.preventDefault();
      return;
    }

    const nameInput = document.getElementById("recipient_name");
    const phoneInput = document.getElementById("recipient_phone");
    const addressInput = document.getElementById("shipping_address");

    const nameVal = nameInput ? nameInput.value.trim() : "";
    const phoneVal = phoneInput ? phoneInput.value.trim() : "";
    const addressVal = addressInput ? addressInput.value.trim() : "";

    if (!nameVal || !phoneVal || !addressVal) {
      event.preventDefault();
      alert("Vui lòng điền đầy đủ họ tên, số điện thoại và địa chỉ giao hàng.");
      return;
    }

    const digitCount = phoneVal.replace(/\D/g, "").length;
    if (digitCount < 8 || digitCount > 15) {
      event.preventDefault();
      alert("Số điện thoại người nhận không hợp lệ (cần từ 8 đến 15 chữ số).");
      return;
    }

    isSubmitting = true;
    submitBtn.disabled = true;
    submitBtn.textContent = "Đang xử lý đơn hàng...";
  });
});

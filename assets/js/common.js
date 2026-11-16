"use strict";
// TODO: JavaScript cho common.

// Chọn một lý do hủy đơn; chỉ yêu cầu nội dung khi chọn lý do khác.
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("[data-cancel-order]").forEach(function (form) {
        const addressGroup = form.querySelector("[data-cancel-address]");
        const confirmButton = form.querySelector("[data-confirm-cancel]");
        const radios = form.querySelectorAll('input[name="cancel_reason"]');
        const otherGroup = form.querySelector("[data-cancel-other]");
        const otherInput = form.querySelector('[name="cancel_reason_other"]');
        if (!otherGroup || !otherInput) return;
        function updateReason() {
            const selected = form.querySelector('input[name="cancel_reason"]:checked');
            const isOther = selected && selected.value === "other";
            if (addressGroup) addressGroup.hidden = !(selected && selected.value === "address");
            if (confirmButton) confirmButton.disabled = !selected;
            otherGroup.hidden = !isOther;
            otherInput.disabled = !isOther;
            otherInput.required = Boolean(isOther);
            otherInput.setCustomValidity("");
        }
        radios.forEach(function (radio) { radio.addEventListener("change", updateReason); });
        otherInput.addEventListener("input", function () { otherInput.setCustomValidity(""); });
        form.addEventListener("submit", function (event) {
            const selected = form.querySelector('input[name="cancel_reason"]:checked');
            if (selected && selected.value === "other" && !otherInput.value.trim()) {
                event.preventDefault();
                otherInput.setCustomValidity("Vui lòng nhập lý do hủy đơn.");
                otherInput.reportValidity();
            }
        });
        window.addEventListener("pageshow", updateReason);
        updateReason();
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const cancel = document.getElementById("flora-cancel-dialog");
    const recipient = document.getElementById("flora-recipient-dialog");
    function openDialog(dialog) {
        if (!dialog) return;
        if (cancel && cancel.open && cancel !== dialog) cancel.close();
        if (recipient && recipient.open && recipient !== dialog) recipient.close();
        if (!dialog.open) dialog.showModal();
    }
    document.querySelectorAll("[data-open-cancel]").forEach(function (button) {
        button.addEventListener("click", function () { openDialog(cancel); });
    });
    document.querySelectorAll("[data-open-recipient]").forEach(function (button) {
        button.addEventListener("click", function () { openDialog(recipient); });
    });
    [cancel, recipient].forEach(function (dialog) {
        if (!dialog) return;
        dialog.querySelectorAll("[data-close-dialog]").forEach(function (button) {
            button.addEventListener("click", function () { dialog.close(); });
        });
        dialog.addEventListener("click", function (event) { if (event.target === dialog) {
            const rect=dialog.getBoundingClientRect();
            if (event.clientX<rect.left || event.clientX>rect.right || event.clientY<rect.top || event.clientY>rect.bottom) dialog.close();
        }});
    });
    if (recipient && recipient.dataset.autoOpen === "true") openDialog(recipient);
    else if (cancel && cancel.dataset.autoOpen === "true") openDialog(cancel);
});

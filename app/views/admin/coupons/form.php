<?php
/** @var bool $isEdit */
/** @var array $formData */
/** @var array $errors */
/** @var string $csrfToken */
?>
<section class="admin-coupon-form-page" style="max-width:680px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h1 style="margin:0;"><?= $isEdit ? 'Sửa mã giảm giá' : 'Thêm mã giảm giá mới' ?></h1>
        <a href="index.php?route=admin/coupons" style="color:#8c4566;text-decoration:none;">&larr; Quay lại danh sách</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div role="alert" style="padding:12px 16px;border-radius:6px;margin-bottom:16px;border:1px solid #d9534f;background:#fff5f5;color:#a94442;">
            <strong>Vui lòng kiểm tra lại dữ liệu:</strong>
            <ul style="margin:8px 0 0 18px;padding:0;">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post"
          action="index.php?route=<?= $isEdit ? 'admin/coupons/update' : 'admin/coupons/store' ?>"
          style="background:#fff;padding:24px;border:1px solid #e8dce1;border-radius:8px;display:grid;gap:14px;">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int) $formData['id'] ?>">
        <?php endif; ?>

        <div>
            <label style="display:block;font-weight:600;margin-bottom:4px;">Mã giảm giá *</label>
            <input type="text" name="code" value="<?= e($formData['code']) ?>" maxlength="50" required
                   placeholder="VD: FLORA10, GIAM30K"
                   style="width:100%;padding:9px 12px;border:1px solid #ccc;border-radius:6px;text-transform:uppercase;">
        </div>

        <div>
            <label style="display:block;font-weight:600;margin-bottom:4px;">Mô tả</label>
            <input type="text" name="description" value="<?= e($formData['description']) ?>" maxlength="255"
                   placeholder="Mô tả điều kiện áp dụng hiển thị cho khách"
                   style="width:100%;padding:9px 12px;border:1px solid #ccc;border-radius:6px;">
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
            <div>
                <label style="display:block;font-weight:600;margin-bottom:4px;">Loại giảm giá *</label>
                <select name="discount_type" id="js-discount-type" style="width:100%;padding:9px 12px;border:1px solid #ccc;border-radius:6px;">
                    <option value="percent" <?= $formData['discount_type'] === 'percent' ? 'selected' : '' ?>>Phần trăm (%)</option>
                    <option value="fixed" <?= $formData['discount_type'] === 'fixed' ? 'selected' : '' ?>>Số tiền cố định (VNĐ)</option>
                </select>
            </div>

            <div>
                <label style="display:block;font-weight:600;margin-bottom:4px;">Giá trị giảm *</label>
                <input type="number" name="discount_value" value="<?= e($formData['discount_value']) ?>"
                       step="any" min="0.01" required
                       style="width:100%;padding:9px 12px;border:1px solid #ccc;border-radius:6px;">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
            <div id="js-max-discount-wrap">
                <label style="display:block;font-weight:600;margin-bottom:4px;">Giảm tối đa (VNĐ — bắt buộc với %) *</label>
                <input type="number" name="max_discount_amount" id="js-max-discount-input"
                       value="<?= e($formData['max_discount_amount']) ?>" min="1" step="1"
                       style="width:100%;padding:9px 12px;border:1px solid #ccc;border-radius:6px;">
            </div>

            <div>
                <label style="display:block;font-weight:600;margin-bottom:4px;">Đơn tối thiểu (VNĐ) *</label>
                <input type="number" name="min_order_amount" value="<?= e($formData['min_order_amount']) ?>"
                       min="0" step="1" required
                       style="width:100%;padding:9px 12px;border:1px solid #ccc;border-radius:6px;">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
            <div>
                <label style="display:block;font-weight:600;margin-bottom:4px;">Bắt đầu áp dụng (Giờ Việt Nam) *</label>
                <input type="datetime-local" name="start_at" value="<?= e($formData['start_at_vn']) ?>" required
                       style="width:100%;padding:9px 12px;border:1px solid #ccc;border-radius:6px;">
            </div>

            <div>
                <label style="display:block;font-weight:600;margin-bottom:4px;">Kết thúc áp dụng (Giờ Việt Nam) *</label>
                <input type="datetime-local" name="end_at" value="<?= e($formData['end_at_vn']) ?>" required
                       style="width:100%;padding:9px 12px;border:1px solid #ccc;border-radius:6px;">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
            <div>
                <label style="display:block;font-weight:600;margin-bottom:4px;">Tổng lượt dùng (để trống = không giới hạn)</label>
                <input type="number" name="usage_limit" value="<?= e($formData['usage_limit']) ?>" min="1" step="1"
                       style="width:100%;padding:9px 12px;border:1px solid #ccc;border-radius:6px;">
            </div>

            <div>
                <label style="display:block;font-weight:600;margin-bottom:4px;">Lượt tối đa / khách (để trống = không giới hạn)</label>
                <input type="number" name="per_user_limit" value="<?= e($formData['per_user_limit']) ?>" min="1" step="1"
                       style="width:100%;padding:9px 12px;border:1px solid #ccc;border-radius:6px;">
            </div>
        </div>

        <div>
            <label style="display:block;font-weight:600;margin-bottom:4px;">Trạng thái *</label>
            <select name="status" style="width:100%;padding:9px 12px;border:1px solid #ccc;border-radius:6px;">
                <option value="active" <?= $formData['status'] === 'active' ? 'selected' : '' ?>>Hoạt động (active)</option>
                <option value="inactive" <?= $formData['status'] === 'inactive' ? 'selected' : '' ?>>Tạm ngưng (inactive)</option>
            </select>
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="button" style="border:none;cursor:pointer;">
                <?= $isEdit ? 'Lưu thay đổi' : 'Tạo mã giảm giá' ?>
            </button>
            <a href="index.php?route=admin/coupons" style="padding:11px 18px;border:1px solid #ccc;border-radius:6px;text-decoration:none;color:#282128;">
                Hủy
            </a>
        </div>
    </form>
</section>

<script>
"use strict";
(function () {
    const typeSelect = document.getElementById("js-discount-type");
    const maxWrap = document.getElementById("js-max-discount-wrap");
    const maxInput = document.getElementById("js-max-discount-input");
    if (!typeSelect || !maxWrap || !maxInput) return;

    function syncMaxDiscountField() {
        const isPercent = typeSelect.value === "percent";
        maxWrap.style.opacity = isPercent ? "1" : "0.45";
        maxInput.disabled = !isPercent;
        maxInput.required = isPercent;
        if (!isPercent) {
            maxInput.value = "";
        }
    }

    typeSelect.addEventListener("change", syncMaxDiscountField);
    syncMaxDiscountField();
})();
</script>
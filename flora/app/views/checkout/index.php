<?php
/** @var array $preview */
/** @var string $checkoutToken */
/** @var string $csrfToken */
/** @var string|null $flashError */
/** @var string|null $flashWarning */
/** @var array $oldInput */

$user = $preview['user'];
$items = $preview['items'];
$issues = $preview['issues'];
$pricing = $preview['pricing'];

$recipientName = $oldInput['recipient_name'] ?? ($user['full_name'] ?? '');
$recipientPhone = $oldInput['recipient_phone'] ?? ($user['phone'] ?? '');
$shippingAddress = $oldInput['shipping_address'] ?? ($user['address'] ?? '');
$note = $oldInput['note'] ?? '';
$paymentMethod = $oldInput['payment_method'] ?? 'cod';
$appliedCouponCode = $oldInput['coupon_code'] ?? ($pricing['coupon']['code'] ?? ($_GET['coupon'] ?? ''));
?>

<h1>Xác nhận đặt hàng</h1>

<?php if (!empty($flashError)): ?>
    <div class="alert alert-error" style="padding:12px 16px;background:#fdecea;color:#991b1b;border:1px solid #f5c2c7;border-radius:6px;margin-bottom:16px;">
        <?= e($flashError) ?>
    </div>
<?php endif; ?>

<?php if (!empty($flashWarning)): ?>
    <div class="alert alert-warning" style="padding:12px 16px;background:#fff8e6;color:#92400e;border:1px solid #fcd34d;border-radius:6px;margin-bottom:16px;">
        <strong>Lưu ý thay đổi giá:</strong> <?= e($flashWarning) ?>
    </div>
<?php endif; ?>

<?php if (!empty($issues)): ?>
    <div class="alert alert-error" style="padding:12px 16px;background:#fdecea;color:#991b1b;border:1px solid #f5c2c7;border-radius:6px;margin-bottom:16px;">
        <strong>Không thể tiến hành đặt hàng do giỏ hàng có vấn đề:</strong>
        <ul style="margin:8px 0 0 20px;">
            <?php foreach ($issues as $issue): ?>
                <li><?= e($issue) ?></li>
            <?php endforeach; ?>
        </ul>
        <p style="margin:10px 0 0;"><a class="button" href="index.php?route=cart">Quay lại giỏ hàng</a></p>
    </div>
<?php endif; ?>

<?php if (!empty($items)): ?>
<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));gap:24px;align-items:start;">
    <!-- Cột trái: Form thông tin nhận hàng & thanh toán -->
    <section style="background:#fff;padding:20px;border:1px solid #e8dce1;border-radius:8px;">
        <h2 style="margin-top:0;">Thông tin nhận hàng</h2>
        <form id="checkout-form" method="post" action="index.php?route=checkout/submit" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="checkout_token" value="<?= e($checkoutToken) ?>">
            <input type="hidden" name="coupon_code" value="<?= e($pricing['coupon_error'] === null && $pricing['coupon'] ? $pricing['coupon']['code'] : '') ?>">
            <input type="hidden" name="expected_subtotal" value="<?= (int) $pricing['subtotal'] ?>">
            <input type="hidden" name="expected_total" value="<?= (int) $pricing['total_amount'] ?>">

            <div style="margin-bottom:14px;">
                <label for="recipient_name" style="display:block;font-weight:700;margin-bottom:4px;">Họ tên người nhận *</label>
                <input id="recipient_name" name="recipient_name" type="text" required maxlength="100"
                       value="<?= e($recipientName) ?>"
                       style="width:100%;padding:10px;border:1px solid #d1c4ca;border-radius:6px;">
            </div>

            <div style="margin-bottom:14px;">
                <label for="recipient_phone" style="display:block;font-weight:700;margin-bottom:4px;">Số điện thoại *</label>
                <input id="recipient_phone" name="recipient_phone" type="tel" required maxlength="20"
                       value="<?= e($recipientPhone) ?>"
                       style="width:100%;padding:10px;border:1px solid #d1c4ca;border-radius:6px;">
            </div>

            <div style="margin-bottom:14px;">
                <label for="shipping_address" style="display:block;font-weight:700;margin-bottom:4px;">Địa chỉ giao hàng *</label>
                <textarea id="shipping_address" name="shipping_address" rows="3" required maxlength="500"
                          style="width:100%;padding:10px;border:1px solid #d1c4ca;border-radius:6px;"><?= e($shippingAddress) ?></textarea>
            </div>

            <div style="margin-bottom:18px;">
                <label for="note" style="display:block;font-weight:700;margin-bottom:4px;">Ghi chú đơn hàng (tùy chọn)</label>
                <textarea id="note" name="note" rows="2" maxlength="1000"
                          placeholder="Ví dụ: Giao giờ hành chính..."
                          style="width:100%;padding:10px;border:1px solid #d1c4ca;border-radius:6px;"><?= e($note) ?></textarea>
            </div>

            <h3>Phương thức thanh toán</h3>
            <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:20px;">
                <label style="display:flex;align-items:center;gap:10px;padding:10px;border:1px solid #e8dce1;border-radius:6px;cursor:pointer;">
                    <input type="radio" name="payment_method" value="cod" <?= $paymentMethod === 'cod' ? 'checked' : '' ?>>
                    <span><strong>Thanh toán khi nhận hàng (COD)</strong></span>
                </label>
                <label style="display:flex;align-items:center;gap:10px;padding:10px;border:1px solid #e8dce1;border-radius:6px;cursor:pointer;">
                    <input type="radio" name="payment_method" value="ewallet" <?= $paymentMethod === 'ewallet' ? 'checked' : '' ?>>
                    <span><strong>Ví điện tử (Mô phỏng)</strong> — Thanh toán trong 30 phút</span>
                </label>
                <label style="display:flex;align-items:center;gap:10px;padding:10px;border:1px solid #e8dce1;border-radius:6px;cursor:pointer;">
                    <input type="radio" name="payment_method" value="paylater" <?= $paymentMethod === 'paylater' ? 'checked' : '' ?>>
                    <span><strong>Ví trả sau (Mô phỏng)</strong> — Thanh toán trong 30 phút</span>
                </label>
            </div>

            <?php if (empty($issues)): ?>
                <button id="btn-submit-order" type="submit" class="button" style="width:100%;border:none;font-size:16px;font-weight:700;cursor:pointer;">
                    Xác nhận đặt hàng (<?= number_format((int) $pricing['total_amount'], 0, ',', '.') ?>đ)
                </button>
            <?php else: ?>
                <button type="button" disabled style="width:100%;padding:11px 20px;background:#ccc;color:#666;border:none;border-radius:6px;">
                    Vui lòng xử lý giỏ hàng trước khi đặt
                </button>
            <?php endif; ?>
        </form>
    </section>

    <!-- Cột phải: Tóm tắt đơn hàng & Áp mã giảm giá -->
    <aside style="background:#fff;padding:20px;border:1px solid #e8dce1;border-radius:8px;">
        <h2 style="margin-top:0;">Tóm tắt đơn hàng (<?= count($items) ?> món)</h2>

        <div style="display:flex;flex-direction:column;gap:12px;border-bottom:1px solid #eee;padding-bottom:14px;margin-bottom:14px;">
            <?php foreach ($items as $item): ?>
                <div style="display:flex;gap:12px;align-items:center;">
                    <img src="<?= e($item['image_path']) ?>" alt="<?= e($item['product_name']) ?>"
                         style="width:56px;height:70px;object-fit:cover;border-radius:4px;border:1px solid #eee;">
                    <div style="flex:1;">
                        <div style="font-weight:700;"><?= e($item['product_name']) ?></div>
                        <div style="font-size:13px;color:#695b62;">
                            Size: <?= e($item['size_name']) ?> | Màu: <?= e($item['color_name']) ?> | SL: <?= (int) $item['quantity'] ?>
                        </div>
                        <div style="font-size:14px;">
                            <?php if ($item['unit_price'] < $item['original_unit_price']): ?>
                                <del style="color:#999;"><?= number_format($item['original_unit_price'], 0, ',', '.') ?>đ</del>
                            <?php endif; ?>
                            <strong><?= number_format($item['unit_price'], 0, ',', '.') ?>đ</strong>
                        </div>
                    </div>
                    <div style="font-weight:700;">
                        <?= number_format($item['line_total'], 0, ',', '.') ?>đ
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Form áp mã giảm giá (GET để giữ trạng thái preview trước khi submit đơn) -->
        <form method="get" action="index.php" style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;">
            <input type="hidden" name="route" value="checkout">
            <input type="text" name="coupon" value="<?= e($appliedCouponCode) ?>"
                   placeholder="Nhập mã giảm giá (VD: FLORA10)"
                   style="flex:1;min-width:160px;padding:9px 12px;border:1px solid #d1c4ca;border-radius:6px;text-transform:uppercase;">
            <button type="submit" class="button" style="border:none;cursor:pointer;">Áp dụng</button>
            <?php if ($appliedCouponCode !== ''): ?>
                <a href="index.php?route=checkout" style="padding:9px 12px;border:1px solid #d1c4ca;border-radius:6px;text-decoration:none;color:#282128;">Gỡ mã</a>
            <?php endif; ?>
        </form>

        <?php if (!empty($pricing['coupon_error'])): ?>
            <p style="color:#b91c1c;font-size:14px;margin:4px 0 12px;"><?= e($pricing['coupon_error']) ?></p>
        <?php elseif (!empty($pricing['coupon'])): ?>
            <p style="color:#15803d;font-size:14px;margin:4px 0 12px;">
                Đã áp dụng mã <strong><?= e($pricing['coupon']['code']) ?></strong>
                <?= !empty($pricing['coupon']['description']) ? '(' . e($pricing['coupon']['description']) . ')' : '' ?>
            </p>
        <?php endif; ?>

        <?php if ($pricing['amount_to_free_ship'] > 0): ?>
            <p style="background:#fdf2f8;color:#8c4566;padding:8px 12px;border-radius:6px;font-size:14px;">
                Mua thêm <strong><?= number_format($pricing['amount_to_free_ship'], 0, ',', '.') ?>đ</strong> tiền hàng sau giảm giá sản phẩm để được miễn phí vận chuyển.
            </p>
        <?php else: ?>
            <p style="background:#ecfdf5;color:#065f46;padding:8px 12px;border-radius:6px;font-size:14px;">
                Đơn hàng đủ điều kiện <strong>miễn phí vận chuyển</strong> (tiền hàng sau giảm sản phẩm từ 500.000đ).
            </p>
        <?php endif; ?>

        <table style="width:100%;border-collapse:collapse;margin-top:12px;">
            <tr>
                <td style="padding:6px 0;color:#695b62;">Tiền hàng (sau giảm sản phẩm):</td>
                <td style="padding:6px 0;text-align:right;font-weight:700;"><?= number_format((int) $pricing['subtotal'], 0, ',', '.') ?>đ</td>
            </tr>
            <tr>
                <td style="padding:6px 0;color:#695b62;">Giảm từ mã khuyến mãi:</td>
                <td style="padding:6px 0;text-align:right;color:#15803d;">-<?= number_format((int) $pricing['coupon_discount'], 0, ',', '.') ?>đ</td>
            </tr>
            <tr>
                <td style="padding:6px 0;color:#695b62;">Phí vận chuyển:</td>
                <td style="padding:6px 0;text-align:right;"><?= number_format((int) $pricing['shipping_fee'], 0, ',', '.') ?>đ</td>
            </tr>
            <tr style="border-top:2px solid #e8dce1;font-size:18px;">
                <td style="padding:10px 0;font-weight:700;">Tổng thanh toán:</td>
                <td style="padding:10px 0;text-align:right;font-weight:700;color:#8c4566;"><?= number_format((int) $pricing['total_amount'], 0, ',', '.') ?>đ</td>
            </tr>
        </table>
    </aside>
</div>
<?php endif; ?>

<script src="assets/js/checkout.js"></script>
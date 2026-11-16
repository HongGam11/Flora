<div class="c flora-order-page">
<?php
/** @var array $order */
$canRetryOnlinePayment = false; $isPaymentExpired = false; $canCancel = false;
$flashSuccess = null; $flashError = null;
/** @var array $items */
/** @var array $history */
/** @var bool $canRetryOnlinePayment */
/** @var bool $isPaymentExpired */
/** @var bool $canCancel */
/** @var string $csrfToken */
/** @var string|null $flashSuccess */
/** @var string|null $flashError */

$utcTz = new DateTimeZone('UTC');
$vnTz = new DateTimeZone('Asia/Ho_Chi_Minh');

$formatVn = static function (?string $utcStr) use ($utcTz, $vnTz): string {
    if ($utcStr === null || $utcStr === '') {
        return '—';
    }
    return (new DateTimeImmutable($utcStr, $utcTz))->setTimezone($vnTz)->format('d/m/Y H:i:s');
};

$statusLabels = [
    'pending' => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'shipping' => 'Đang giao hàng',
    'completed' => 'Hoàn tất',
    'cancelled' => 'Đã hủy',
];

$payMethodLabels = [
    'cod' => 'Thanh toán khi nhận hàng (COD)',
    'ewallet' => 'Ví điện tử (Mô phỏng)',
    'paylater' => 'Ví trả sau (Mô phỏng)',
];

$payStatusLabels = [
    'unpaid' => 'Chưa thanh toán',
    'paid' => 'Đã thanh toán',
    'refunded' => 'Đã hoàn tiền',
    'not_required' => 'Không cần thanh toán (0đ)',
];
?>

<p><a href="index.php?route=orders" style="color:#583b25;text-decoration:none;">← Quay lại danh sách đơn hàng</a></p>
<h1>Chi tiết đơn hàng #<?= e($order['order_code']) ?></h1>

<?php if (!empty($flashSuccess)): ?>
    <div style="padding:12px 16px;background:#ecfdf5;color:#065f46;border-radius:6px;margin-bottom:14px;"><?= e($flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div style="padding:12px 16px;background:#fdecea;color:#991b1b;border-radius:6px;margin-bottom:14px;"><?= e($flashError) ?></div>
<?php endif; ?>

<section style="background:#fff;padding:20px;border:1px solid #e8dce1;border-radius:8px;margin-bottom:20px;">
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:16px;">
        <div>
            <h3 style="margin-top:0;">Thông tin nhận hàng</h3>
            <p><strong>Người nhận:</strong> <?= e($order['recipient_name']) ?></p>
            <p><strong>Số điện thoại:</strong> <?= e($order['recipient_phone']) ?></p>
            <p><strong>Địa chỉ:</strong> <?= e($order['shipping_address']) ?></p>
            <?php if (!empty($order['note'])): ?>
                <p><strong>Ghi chú:</strong> <?= e($order['note']) ?></p>
            <?php endif; ?>
        </div>
        <div>
            <h3 style="margin-top:0;">Trạng thái & Thanh toán</h3>
            <p><strong>Trạng thái đơn:</strong> <?= e($statusLabels[$order['status']] ?? $order['status']) ?></p>
            <p><strong>Phương thức:</strong> <?= e($payMethodLabels[$order['payment_method']] ?? $order['payment_method']) ?></p>
            <p><strong>Thanh toán:</strong> <?= e($payStatusLabels[$order['payment_status']] ?? $order['payment_status']) ?></p>
            <p><strong>Ngày tạo (giờ VN):</strong> <?= e($formatVn($order['created_at'])) ?></p>
            <?php if (!empty($order['payment_expires_at']) && $order['payment_status'] === 'unpaid'): ?>
                <p><strong>Hạn thanh toán online (giờ VN):</strong> <?= e($formatVn($order['payment_expires_at'])) ?></p>
            <?php endif; ?>
            <?php if ($order['status'] === 'cancelled'): ?>
                <p style="color:#b91c1c;"><strong>Lý do hủy:</strong> <?= e($order['cancel_reason'] ?? 'Không có') ?> (<?= e($formatVn($order['cancelled_at'])) ?>)</p>
                <?php if ($order['payment_status'] === 'paid'): ?>
                    <p style="color:#92400e;"><strong>Hoàn tiền:</strong> Đơn đã thanh toán trước khi hủy, đang chờ quản trị viên hoàn tiền.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($order['status']==='pending' && $order['payment_method']==='cod' && in_array($order['payment_status'],['unpaid','not_required'],true)):
        $cancelChoice = is_string($cancelOldInput['choice'] ?? null) ? $cancelOldInput['choice'] : '';
        $cancelOther = is_string($cancelOldInput['other'] ?? null) ? $cancelOldInput['other'] : '';
    ?>
    <div class="flora-order-actions">
        <?php if (!$recipientUpdated): ?><button class="btn" type="button" data-open-recipient>Cập nhật thông tin nhận hàng</button><?php endif; ?>
        <button class="btn" type="button" data-open-cancel>Hủy đơn hàng</button>
    </div>
    <dialog class="flora-order-dialog" id="flora-cancel-dialog" aria-labelledby="flora-cancel-title" data-auto-open="<?= $cancelChoice!==''?'true':'false' ?>">
        <div class="flora-dialog-heading"><h2 id="flora-cancel-title">Lý do hủy</h2><button type="button" data-close-dialog aria-label="Đóng lý do hủy">×</button></div>
        <form class="flora-cancel-form" data-cancel-order method="post" action="/flora/index.php?route=order_cancel">
            <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
            <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
            <p class="flora-dialog-notice">Bạn có thể cập nhật thông tin nhận hàng một lần khi đơn đang chờ xác nhận. Nếu hủy, toàn bộ sản phẩm trong đơn sẽ bị hủy.</p>
            <fieldset class="flora-cancel-reasons">
                <legend class="flora-visually-hidden">Chọn một lý do</legend>
                <?php foreach ($cancelReasonOptions as $key=>$label): ?>
                <label class="flora-cancel-option"><input type="radio" name="cancel_reason" value="<?= e($key) ?>" required <?= $cancelChoice===$key?'checked':'' ?>><span><?= e($label) ?></span></label>
                <?php if ($key==='address'): ?>
                <div class="flora-address-suggestion" data-cancel-address hidden>
                    <?php if (!$recipientUpdated): ?>
                    <p>Bạn có thể sửa địa chỉ/số điện thoại mà vẫn giữ nguyên đơn hàng. Thông tin nhận hàng chỉ được cập nhật một lần.</p>
                    <button class="btn" type="button" data-open-recipient>Cập nhật</button>
                    <?php else: ?><p>Bạn đã cập nhật thông tin nhận hàng một lần. Nếu cần thay đổi tiếp, bạn có thể hủy và đặt lại đơn.</p><?php endif; ?>
                </div>
                <?php endif; endforeach; ?>
            </fieldset>
            <div class="flora-cancel-other" data-cancel-other>
                <label for="cancel-reason-other">Lý do khác</label>
                <textarea class="fc" id="cancel-reason-other" name="cancel_reason_other" maxlength="500" rows="3" aria-describedby="cancel-reason-help"><?= e($cancelOther) ?></textarea>
                <small id="cancel-reason-help">Chỉ cần nhập khi không tìm thấy lý do phù hợp, tối đa 500 ký tự.</small>
            </div>
            <button class="btn" type="submit" data-confirm-cancel>Xác nhận hủy đơn</button>
        </form>
    </dialog>
    <?php if (!$recipientUpdated): $recipientValues=is_array($recipientOldInput)?$recipientOldInput:$order; ?>
    <dialog class="flora-order-dialog" id="flora-recipient-dialog" aria-labelledby="flora-recipient-title" data-auto-open="<?= is_array($recipientOldInput)?'true':'false' ?>">
        <div class="flora-dialog-heading"><h2 id="flora-recipient-title">Cập nhật thông tin nhận hàng</h2><button type="button" data-close-dialog aria-label="Đóng cập nhật thông tin">×</button></div>
        <form class="flora-recipient-form" method="post" action="/flora/index.php?route=order_update_recipient">
            <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
            <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
            <p class="flora-dialog-notice">Bạn được cập nhật một lần khi đơn đang chờ xác nhận. Cập nhật sẽ giữ nguyên đơn hàng.</p>
            <label for="recipient-update-name">Họ và tên người nhận</label>
            <input class="fc" id="recipient-update-name" name="recipient_name" maxlength="100" required autocomplete="shipping name" value="<?= e($recipientValues['recipient_name']) ?>">
            <label for="recipient-update-phone">Số điện thoại nhận hàng</label>
            <input class="fc" type="tel" id="recipient-update-phone" name="recipient_phone" maxlength="20" required autocomplete="shipping tel" value="<?= e($recipientValues['recipient_phone']) ?>">
            <label for="recipient-update-address">Địa chỉ nhận hàng</label>
            <textarea class="fc" id="recipient-update-address" name="shipping_address" maxlength="500" rows="3" required autocomplete="shipping street-address"><?= e($recipientValues['shipping_address']) ?></textarea>
            <button class="btn" type="submit">Lưu cập nhật</button>
        </form>
    </dialog>
    <?php endif; ?>
    <noscript><p>Vui lòng bật JavaScript để mở bảng lý do hủy và cập nhật thông tin nhận hàng.</p></noscript>
    <?php endif; ?>

    <!-- Nút hành động theo trạng thái và hạn của TV6 -->
    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:16px;padding-top:16px;border-top:1px solid #eee;">

    </div>
</section>

<!-- Chi tiết sản phẩm snapshot -->
<section style="background:#fff;padding:20px;border:1px solid #e8dce1;border-radius:8px;margin-bottom:20px;">
    <h3 style="margin-top:0;">Sản phẩm đã đặt</h3>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#fdf2f8;text-align:left;border-bottom:1px solid #e8dce1;">
                    <th style="padding:10px;">Sản phẩm</th>
                    <th style="padding:10px;">Phân loại</th>
                    <th style="padding:10px;text-align:right;">Đơn giá</th>
                    <th style="padding:10px;text-align:center;">SL</th>
                    <th style="padding:10px;text-align:right;">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it): ?>
                    <tr style="border-bottom:1px solid #f1e8ec;">
                        <td style="padding:10px;font-weight:700;"><?= e($it['product_name']) ?></td>
                        <td style="padding:10px;">Size: <?= e($it['size_name']) ?> | Màu: <?= e($it['color_name']) ?></td>
                        <td style="padding:10px;text-align:right;">
                            <?php if ((int) $it['unit_price'] < (int) $it['original_unit_price']): ?>
                                <del style="color:#999;"><?= number_format((int) $it['original_unit_price'], 0, ',', '.') ?>đ</del><br>
                            <?php endif; ?>
                            <?= number_format((int) $it['unit_price'], 0, ',', '.') ?>đ
                        </td>
                        <td style="padding:10px;text-align:center;"><?= (int) $it['quantity'] ?></td>
                        <td style="padding:10px;text-align:right;font-weight:700;"><?= number_format((int) $it['line_total'], 0, ',', '.') ?>đ</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <table style="width:100%;max-width:360px;margin-left:auto;margin-top:16px;border-collapse:collapse;">
        <tr>
            <td style="padding:5px 0;">Tiền hàng:</td>
            <td style="padding:5px 0;text-align:right;"><?= number_format((int) $order['subtotal'], 0, ',', '.') ?>đ</td>
        </tr>
        <tr>
            <td style="padding:5px 0;">
                Mã giảm giá <?= !empty($order['coupon_code']) ? '(' . e($order['coupon_code']) . ')' : '' ?>:
            </td>
            <td style="padding:5px 0;text-align:right;color:#15803d;">-<?= number_format((int) $order['coupon_discount'], 0, ',', '.') ?>đ</td>
        </tr>
        <tr>
            <td style="padding:5px 0;">Phí vận chuyển:</td>
            <td style="padding:5px 0;text-align:right;"><?= number_format((int) $order['shipping_fee'], 0, ',', '.') ?>đ</td>
        </tr>
        <tr style="border-top:2px solid #e8dce1;font-size:18px;">
            <td style="padding:8px 0;font-weight:700;">Tổng thanh toán:</td>
            <td style="padding:8px 0;text-align:right;font-weight:700;color:#583b25;"><?= number_format((int) $order['total_amount'], 0, ',', '.') ?>đ</td>
        </tr>
    </table>
</section>

<!-- Lịch sử trạng thái đơn hàng -->
<section style="background:#fff;padding:20px;border:1px solid #e8dce1;border-radius:8px;">
    <h3 style="margin-top:0;">Lịch sử trạng thái đơn hàng</h3>
    <?php if (empty($history)): ?>
        <p>Chưa có dữ liệu lịch sử.</p>
    <?php else: ?>
        <ul style="line-height:1.8;padding-left:20px;">
            <?php foreach ($history as $h): ?>
                <li>
                    <strong><?= e($formatVn($h['created_at'])) ?></strong>:
                    <?= $h['old_status'] !== null ? e($statusLabels[$h['old_status']] ?? $h['old_status']) . ' → ' : '' ?>
                    <strong><?= e($statusLabels[$h['new_status']] ?? $h['new_status']) ?></strong>
                    <?php if (!empty($h['note'])): ?>
                        — <em><?= e($h['note']) ?></em>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
</div>

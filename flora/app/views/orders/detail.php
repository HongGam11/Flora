<?php
/** @var array $order */
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

<p><a href="index.php?route=orders" style="color:#8c4566;text-decoration:none;">← Quay lại danh sách đơn hàng</a></p>
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
            <h3 style="margin-top:0;">Thông tin nhận hàng (tại lúc đặt)</h3>
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

    <!-- Nút hành động theo trạng thái và hạn của TV6 -->
    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:16px;padding-top:16px;border-top:1px solid #eee;">
        <?php if ($canRetryOnlinePayment): ?>
            <a class="button" href="index.php?route=payment/simulate&order_id=<?= (int) $order['id'] ?>">
                Thanh toán online ngay
            </a>
        <?php elseif ($isPaymentExpired): ?>
            <span style="padding:10px 14px;background:#fef2f2;color:#991b1b;border-radius:6px;">
                Đã quá hạn thanh toán online 30 phút (không thể thanh toán thêm).
            </span>
        <?php endif; ?>

        <?php if ($canCancel): ?>
            <form method="post" action="index.php?route=orders/cancel"
                  onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn hàng này?');"
                  style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                <input type="text" name="cancel_reason" placeholder="Lý do hủy đơn (tùy chọn)" maxlength="500"
                       style="padding:9px 12px;border:1px solid #d1c4ca;border-radius:6px;">
                <button type="submit" style="padding:10px 18px;background:#b91c1c;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:700;">
                    Hủy đơn hàng
                </button>
            </form>
        <?php endif; ?>
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
            <td style="padding:8px 0;text-align:right;font-weight:700;color:#8c4566;"><?= number_format((int) $order['total_amount'], 0, ',', '.') ?>đ</td>
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
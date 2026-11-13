<?php
/** @var array $order */
/** @var array $items */
/** @var string|null $flashInfo */

$utcTz = new DateTimeZone('UTC');
$vnTz = new DateTimeZone('Asia/Ho_Chi_Minh');
$createdVn = (new DateTimeImmutable($order['created_at'], $utcTz))->setTimezone($vnTz)->format('d/m/Y H:i:s');

$methodLabels = [
    'cod' => 'Thanh toán khi nhận hàng (COD)',
    'ewallet' => 'Ví điện tử (Mô phỏng)',
    'paylater' => 'Ví trả sau (Mô phỏng)',
];
?>

<section style="background:#fff;padding:24px;border:1px solid #e8dce1;border-radius:8px;max-width:760px;margin:auto;">
    <?php if (!empty($flashInfo)): ?>
        <div style="padding:10px 14px;background:#eff6ff;color:#1e40af;border-radius:6px;margin-bottom:14px;">
            <?= e($flashInfo) ?>
        </div>
    <?php endif; ?>

    <h1 style="color:#15803d;margin-top:0;">Đặt hàng thành công!</h1>
    <p>Cảm ơn bạn đã đặt hàng tại <strong>Flora</strong>. Mã đơn hàng của bạn là:</p>
    <p style="font-size:24px;font-weight:700;letter-spacing:1px;color:#8c4566;"><?= e($order['order_code']) ?></p>

    <ul style="line-height:1.8;">
        <li><strong>Thời gian đặt (giờ VN):</strong> <?= e($createdVn) ?></li>
        <li><strong>Người nhận:</strong> <?= e($order['recipient_name']) ?> — <?= e($order['recipient_phone']) ?></li>
        <li><strong>Địa chỉ giao hàng:</strong> <?= e($order['shipping_address']) ?></li>
        <li><strong>Phương thức thanh toán:</strong> <?= e($methodLabels[$order['payment_method']] ?? $order['payment_method']) ?></li>
        <li><strong>Tổng thanh toán:</strong> <?= number_format((int) $order['total_amount'], 0, ',', '.') ?>đ
            <?php if ($order['payment_status'] === 'not_required'): ?>
                <span style="color:#065f46;font-weight:700;">(Đơn 0đ — Không cần thanh toán)</span>
            <?php endif; ?>
        </li>
    </ul>

    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:20px;">
        <?php if ($order['payment_method'] !== 'cod' && (int) $order['total_amount'] > 0 && $order['payment_status'] === 'unpaid' && $order['status'] === 'pending'): ?>
            <a class="button" href="index.php?route=payment/simulate&order_id=<?= (int) $order['id'] ?>">
                Thanh toán online ngay
            </a>
        <?php endif; ?>

        <a class="button" href="index.php?route=orders/detail&id=<?= (int) $order['id'] ?>">
            Theo dõi chi tiết đơn hàng
        </a>
        <a href="index.php?route=orders" style="padding:11px 20px;border:1px solid #8c4566;color:#8c4566;text-decoration:none;border-radius:6px;">
            Danh sách đơn của tôi
        </a>
    </div>
</section>
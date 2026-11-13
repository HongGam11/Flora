<?php
/** @var array $orders */
/** @var string|null $statusFilter */
/** @var string|null $flashSuccess */
/** @var string|null $flashError */

$utcTz = new DateTimeZone('UTC');
$vnTz = new DateTimeZone('Asia/Ho_Chi_Minh');

$statusLabels = [
    'pending' => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'shipping' => 'Đang giao hàng',
    'completed' => 'Hoàn tất',
    'cancelled' => 'Đã hủy',
];

$payStatusLabels = [
    'unpaid' => 'Chưa thanh toán',
    'paid' => 'Đã thanh toán',
    'refunded' => 'Đã hoàn tiền',
    'not_required' => 'Không cần thu tiền (0đ)',
];
?>

<h1>Đơn hàng của tôi</h1>

<?php if (!empty($flashSuccess)): ?>
    <div style="padding:12px 16px;background:#ecfdf5;color:#065f46;border-radius:6px;margin-bottom:14px;"><?= e($flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div style="padding:12px 16px;background:#fdecea;color:#991b1b;border-radius:6px;margin-bottom:14px;"><?= e($flashError) ?></div>
<?php endif; ?>

<nav style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px;">
    <a href="index.php?route=orders"
       style="padding:7px 14px;border-radius:6px;text-decoration:none;border:1px solid #8c4566;<?= $statusFilter === null ? 'background:#8c4566;color:#fff;' : 'color:#8c4566;' ?>">
        Tất cả
    </a>
    <?php foreach ($statusLabels as $key => $label): ?>
        <a href="index.php?route=orders&status=<?= e($key) ?>"
           style="padding:7px 14px;border-radius:6px;text-decoration:none;border:1px solid #8c4566;<?= $statusFilter === $key ? 'background:#8c4566;color:#fff;' : 'color:#8c4566;' ?>">
            <?= e($label) ?>
        </a>
    <?php endforeach; ?>
</nav>

<?php if (empty($orders)): ?>
    <div style="background:#fff;padding:24px;border:1px solid #e8dce1;border-radius:8px;text-align:center;">
        <p>Bạn chưa có đơn hàng nào trong mục này.</p>
        <a class="button" href="index.php?route=products">Tiếp tục mua sắm</a>
    </div>
<?php else: ?>
    <div style="overflow-x:auto;background:#fff;border:1px solid #e8dce1;border-radius:8px;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#fdf2f8;text-align:left;border-bottom:1px solid #e8dce1;">
                    <th style="padding:12px;">Mã đơn</th>
                    <th style="padding:12px;">Ngày đặt (VN)</th>
                    <th style="padding:12px;">Số lượng</th>
                    <th style="padding:12px;">Tổng tiền</th>
                    <th style="padding:12px;">Thanh toán</th>
                    <th style="padding:12px;">Trạng thái đơn</th>
                    <th style="padding:12px;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                    <?php $createdVn = (new DateTimeImmutable($o['created_at'], $utcTz))->setTimezone($vnTz)->format('d/m/Y H:i'); ?>
                    <tr style="border-bottom:1px solid #f1e8ec;">
                        <td style="padding:12px;font-weight:700;color:#8c4566;"><?= e($o['order_code']) ?></td>
                        <td style="padding:12px;"><?= e($createdVn) ?></td>
                        <td style="padding:12px;"><?= (int) $o['total_items'] ?> sp</td>
                        <td style="padding:12px;font-weight:700;"><?= number_format((int) $o['total_amount'], 0, ',', '.') ?>đ</td>
                        <td style="padding:12px;"><?= e($payStatusLabels[$o['payment_status']] ?? $o['payment_status']) ?></td>
                        <td style="padding:12px;"><strong><?= e($statusLabels[$o['status']] ?? $o['status']) ?></strong></td>
                        <td style="padding:12px;">
                            <a href="index.php?route=orders/detail&id=<?= (int) $o['id'] ?>" style="color:#8c4566;font-weight:700;">Xem chi tiết</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
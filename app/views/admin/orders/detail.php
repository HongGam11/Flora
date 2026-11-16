<?php
$labels=['pending'=>'Chờ xác nhận','confirmed'=>'Đã xác nhận','shipping'=>'Đang giao','completed'=>'Hoàn tất','cancelled'=>'Đã hủy'];
$next=['pending'=>'confirmed','confirmed'=>'shipping','shipping'=>'completed'];
$actions=['confirmed'=>'Xác nhận đơn','shipping'=>'Bắt đầu giao','completed'=>'Hoàn tất và xác nhận thu tiền COD'];
$vn=static fn($date)=>$date ? (new DateTimeImmutable($date,new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))->format('d/m/Y H:i:s') : '—';
?>
<section class="admin-card tv3-catalog">
<p><a href="/flora/index.php?route=admin_orders">← Danh sách đơn</a></p>
<h1>Đơn <?= e($order['order_code']) ?></h1>
<p>Người nhận: <strong><?= e($order['recipient_name']) ?></strong> · <?= e($order['recipient_phone']) ?></p>
<p>Địa chỉ: <?= e($order['shipping_address']) ?></p><p>Ghi chú: <?= e($order['note']??'') ?></p>
<p>Tài khoản đặt: <?= e($order['full_name']) ?> · <?= e($order['email']) ?></p>
<p>Trạng thái: <strong><?= e($labels[$order['status']]??$order['status']) ?></strong> · <?= e($order['payment_method']) ?> · <?= e($order['payment_status']) ?></p>
<p>Ngày đặt (VN): <?= e($vn($order['created_at'])) ?> · Hoàn tất: <?= e($vn($order['completed_at'])) ?></p>
<?php if ($order['status']==='cancelled'): ?><p>Lý do hủy: <?= e($order['cancel_reason']??'') ?> · <?= e($vn($order['cancelled_at'])) ?></p><?php endif; ?>
<h2>Sản phẩm tại thời điểm đặt</h2>
<div class="tv3-scroll"><table><thead><tr><th>Sản phẩm</th><th>Size</th><th>Màu</th><th>Đơn giá</th><th>Số lượng</th><th>Thành tiền</th></tr></thead><tbody>
<?php foreach ($items as $item): ?><tr><td><?= e($item['product_name']) ?></td><td><?= e($item['size_name']) ?></td><td><?= e($item['color_name']) ?></td><td><?= flora_money($item['unit_price']) ?></td><td><?= (int)$item['quantity'] ?></td><td><?= flora_money($item['line_total']) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<p>Tiền hàng: <?= flora_money($order['subtotal']) ?> · Mã <?= e($order['coupon_code']??'') ?>: −<?= flora_money($order['coupon_discount']) ?> · Ship: <?= flora_money($order['shipping_fee']) ?></p>
<p><strong>Tổng thanh toán: <?= flora_money($order['total_amount']) ?></strong></p>
<?php if ($order['payment_method']==='cod' && isset($next[$order['status']])): $target=$next[$order['status']]; ?>
<form method="post" action="/flora/index.php?route=admin_orders_update">
<input type="hidden" name="csrf_token" value="<?= e($csrf) ?>"><input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
<input type="hidden" name="expected_status" value="<?= e($order['status']) ?>"><input type="hidden" name="status" value="<?= e($target) ?>">
<button><?= e($actions[$target]) ?></button></form>
<?php endif; ?>
<?php if ($order['payment_method']==='cod' && in_array($order['status'],['pending','confirmed'],true)): ?>
<form method="post" action="/flora/index.php?route=admin_orders_update">
<input type="hidden" name="csrf_token" value="<?= e($csrf) ?>"><input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>"><input type="hidden" name="status" value="cancelled">
<label>Lý do hủy<textarea name="reason" maxlength="500" required></textarea></label><button>Hủy đơn và hoàn kho</button></form>
<?php endif; ?>
<h2>Lịch sử trạng thái</h2><ul>
<?php foreach ($histories as $h): ?><li><?= e($vn($h['created_at'])) ?> · <?= e($labels[$h['old_status']]??'Tạo mới') ?> → <?= e($labels[$h['new_status']]??$h['new_status']) ?> · <?= e($h['note']??'') ?> · <?= e($h['actor_name']??'Hệ thống') ?></li><?php endforeach; ?>
</ul><h2>Giao dịch thu tiền (đồ án mô phỏng)</h2>
<?php if (!$transactions): ?><p>Chưa có giao dịch.</p><?php else: ?><ul><?php foreach ($transactions as $t): ?><li><?= e($t['transaction_code']) ?> · <?= flora_money($t['amount']) ?> · <?= e($t['status']) ?> · <?= e($vn($t['processed_at'])) ?></li><?php endforeach; ?></ul><?php endif; ?>
</section>

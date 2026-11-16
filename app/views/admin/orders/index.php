<?php $labels=['pending'=>'Chờ xác nhận','confirmed'=>'Đã xác nhận','shipping'=>'Đang giao','completed'=>'Hoàn tất','cancelled'=>'Đã hủy']; ?>
<section class="admin-card tv3-catalog">
<h1>Quản lý đơn hàng</h1>
<form method="get" action="/flora/index.php">
    <input type="hidden" name="route" value="admin_orders">
    <label>Tìm mã đơn, tên hoặc điện thoại người nhận<input name="q" maxlength="100" value="<?= e($q) ?>"></label>
    <label>Trạng thái<select name="status"><option value="">Tất cả</option>
    <?php foreach ($labels as $value=>$label): ?><option value="<?= e($value) ?>" <?= $status===$value?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?>
    </select></label><button>Lọc đơn</button>
</form>
<p>Hiển thị tối đa 200 đơn mới nhất theo bộ lọc.</p>
<?php if (!$orders): ?><p>Không tìm thấy đơn phù hợp.</p><?php else: ?>
<div class="tv3-scroll"><table><thead><tr><th>Mã đơn</th><th>Người nhận</th><th>Tổng tiền</th><th>Phương thức</th><th>Trạng thái</th><th>Thanh toán</th><th>Thao tác</th></tr></thead><tbody>
<?php foreach ($orders as $ord): ?><tr>
<td><?= e($ord['order_code']) ?></td><td><?= e($ord['recipient_name']) ?><br><?= e($ord['recipient_phone']) ?></td>
<td><?= flora_money($ord['total_amount']) ?></td><td><?= e($ord['payment_method']) ?></td>
<td><?= e($labels[$ord['status']]??$ord['status']) ?></td><td><?= e($ord['payment_status']) ?></td>
<td><a href="/flora/index.php?route=admin_orders_detail&amp;id=<?= (int)$ord['id'] ?>">Chi tiết</a></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?>
</section>

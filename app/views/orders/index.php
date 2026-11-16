<?php $labels=['pending'=>'Chờ xác nhận','confirmed'=>'Đã xác nhận','shipping'=>'Đang giao','completed'=>'Hoàn tất','cancelled'=>'Đã hủy']; ?>
<section class="c flora-order-page">
    <h1>Đơn hàng của tôi</h1>
    <form method="get" action="/flora/index.php" class="order-filter">
        <input type="hidden" name="route" value="orders">
        <label for="order-status">Trạng thái</label>
        <select class="fc" name="status" id="order-status"><option value="">Tất cả</option>
            <?php foreach ($labels as $key=>$label): ?><option value="<?= e($key) ?>" <?= $status===$key?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?>
        </select><button class="btn" type="submit">Lọc</button>
    </form>
    <?php if (!$orders): ?><p class="empty">Chưa có đơn hàng phù hợp.</p><?php else: ?>
        <div class="tw"><table><thead><tr><th>Mã đơn</th><th>Ngày đặt (VN)</th><th>Số sản phẩm</th><th>Tổng thanh toán</th><th>Trạng thái</th><th></th></tr></thead><tbody>
        <?php foreach ($orders as $order): ?>
            <tr><td><?= e($order['order_code']) ?></td>
                <td><?= e((new DateTimeImmutable($order['created_at'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))->format('d/m/Y H:i')) ?></td>
                <td><?= (int)$order['total_items'] ?></td><td><?= flora_money($order['total_amount']) ?></td>
                <td><?= e($labels[$order['status']] ?? $order['status']) ?></td>
                <td><a class="btn" href="/flora/index.php?route=order_detail&amp;id=<?= (int)$order['id'] ?>">Xem chi tiết</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody></table></div>
    <?php endif; ?>
    <p><a href="/flora/index.php?route=products">Tiếp tục mua sắm</a></p>
</section>

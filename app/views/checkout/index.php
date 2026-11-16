<?php
$user = $preview['user']; $items = $preview['items']; $issues = $preview['issues']; $pricing = $preview['pricing'];
?>
<section class="c flora-checkout">
    <h1>Xác nhận đặt hàng</h1>
    <p><a href="/flora/index.php?route=cart">← Quay lại giỏ hàng</a></p>
    <?php if ($issues): ?>
        <div class="flora-alert flora-alert--warning" role="alert"><ul>
        <?php foreach ($issues as $issue): ?><li><?= e($issue) ?></li><?php endforeach; ?>
        </ul></div>
    <?php endif; ?>
    <div class="checkout-grid">
        <form id="checkout-form" class="checkout-panel" method="post" action="/flora/index.php?route=checkout">
            <h2>Thông tin nhận hàng</h2>
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="checkout_token" value="<?= e($checkoutToken) ?>">
            <label for="recipient_name">Họ và tên</label>
            <input class="fc" id="recipient_name" name="recipient_name" maxlength="100" autocomplete="shipping name" required value="<?= e($oldInput['recipient_name'] ?? $user['full_name'] ?? '') ?>">
            <label for="recipient_phone">Số điện thoại</label>
            <input class="fc" id="recipient_phone" name="recipient_phone" type="tel" maxlength="20" autocomplete="shipping tel" required value="<?= e($oldInput['recipient_phone'] ?? $user['phone'] ?? '') ?>">
            <label for="shipping_address">Địa chỉ giao hàng</label>
            <textarea class="fc" id="shipping_address" name="shipping_address" rows="3" maxlength="500" autocomplete="shipping street-address" required><?= e($oldInput['shipping_address'] ?? $user['address'] ?? '') ?></textarea>
            <label for="checkout_note">Ghi chú (không bắt buộc)</label>
            <textarea class="fc" id="checkout_note" name="note" rows="2" maxlength="1000"><?= e($oldInput['note'] ?? '') ?></textarea>
            <input type="hidden" name="payment_method" value="cod">
            <p><strong>Thanh toán khi nhận hàng (COD)</strong></p>
            <button class="btn pri" id="btn-submit-order" type="submit" <?= $issues || !$items ? 'disabled' : '' ?>>Xác nhận đặt hàng COD</button>
        </form>
        <div class="checkout-panel">
            <h2>Đơn hàng của bạn</h2>
            <?php foreach ($items as $item): ?>
                <article class="checkout-item">
                    <img src="<?= e($item['image_path']) ?>" alt="<?= e($item['product_name']) ?>" loading="lazy">
                    <div><strong><?= e($item['product_name']) ?></strong>
                        <p><?= e($item['size_name']) ?> · <?= e($item['color_name']) ?> · SL <?= (int)$item['quantity'] ?></p>
                        <p><?= flora_money($item['unit_price']) ?> × <?= (int)$item['quantity'] ?> = <?= flora_money($item['line_total']) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
            <dl class="order-totals">
                <dt>Tiền hàng sau giảm sản phẩm</dt><dd><?= flora_money($pricing['subtotal']) ?></dd>
                <dt>Mã giảm <?= e($pricing['coupon']['code'] ?? '') ?></dt><dd>−<?= flora_money($pricing['coupon_discount']) ?></dd>
                <dt>Phí vận chuyển</dt><dd><?= flora_money($pricing['shipping_fee']) ?></dd>
                <dt><strong>Tổng thanh toán</strong></dt><dd><strong><?= flora_money($pricing['total_amount']) ?></strong></dd>
            </dl>
            <?php if ($pricing['coupon_error']): ?><p class="note out"><?= e($pricing['coupon_error']) ?></p><?php endif; ?>
            <p class="note">Miễn phí vận chuyển từ 500.000đ tiền hàng sau giảm sản phẩm, trước mã giảm giá. Các đơn khác: 30.000đ.</p>
            <p><a href="/flora/index.php?route=cart">Đổi số lượng hoặc mã giảm giá trong giỏ hàng</a></p>
        </div>
    </div>
</section>
<script src="/flora/assets/js/checkout.js" defer></script>

<?php
/** @var array $summary */
/** @var array $availableCoupons */
/** @var bool $hasInvalid */
/** @var string $csrfToken */
/** @var array|null $flash */
?>
<section class="cart-page">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
        <h1 style="margin:0;">Giỏ hàng của bạn</h1>
        <a href="index.php?route=products" style="color:#8c4566;text-decoration:none;font-weight:600;">&larr; Tiếp tục mua sắm</a>
    </div>

    <?php if (!empty($flash)): ?>
        <div role="alert" style="padding:12px 16px;border-radius:6px;margin-bottom:16px;border:1px solid <?= $flash['type'] === 'error' ? '#d9534f' : ($flash['type'] === 'warning' ? '#f0ad4e' : '#5cb85c') ?>;background:#fff;">
            <?= e($flash['text']) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($summary['has_items'])): ?>
        <div style="padding:32px;text-align:center;background:#fff;border:1px solid #e8dce1;border-radius:8px;">
            <p style="font-size:18px;margin-bottom:16px;">Giỏ hàng của bạn hiện đang trống.</p>
            <a class="button" href="index.php?route=dev-tv4">Chọn thử sản phẩm mẫu</a>
        </div>
    <?php else: ?>
        <div style="padding:12px 16px;border-radius:6px;margin-bottom:16px;background:#f7eef2;border:1px solid #e8dce1;">
            <?php if ($summary['is_free_ship']): ?>
                <strong>Chúc mừng!</strong> Đơn hàng của bạn đã được <strong>miễn phí vận chuyển</strong> (tiền hàng đạt từ 500.000đ).
            <?php else: ?>
                Mua thêm <strong><?= e(PricingService::formatVnd($summary['amount_to_free_ship'])) ?></strong> tiền hàng sau giảm giá sản phẩm để được <strong>miễn phí vận chuyển</strong>.
            <?php endif; ?>
        </div>

        <div style="display:grid;grid-template-columns:1fr;gap:24px;">
            <div style="overflow-x:auto;background:#fff;border:1px solid #e8dce1;border-radius:8px;">
                <table style="width:100%;border-collapse:collapse;min-width:680px;">
                    <thead>
                        <tr style="background:#f7eef2;text-align:left;border-bottom:1px solid #e8dce1;">
                            <th style="padding:12px;">Sản phẩm</th>
                            <th style="padding:12px;">Phân loại</th>
                            <th style="padding:12px;text-align:right;">Đơn giá</th>
                            <th style="padding:12px;text-align:center;">Số lượng</th>
                            <th style="padding:12px;text-align:right;">Thành tiền</th>
                            <th style="padding:12px;text-align:center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($summary['items'] as $item): ?>
                            <tr style="border-bottom:1px solid #eee;<?= empty($item['is_purchasable']) ? 'background:#fff5f5;' : '' ?>">
                                <td style="padding:12px;">
                                    <div style="display:flex;align-items:center;gap:12px;">
                                        <img src="<?= e($item['image_path']) ?>" alt="<?= e($item['product_name']) ?>"
                                             style="width:64px;height:80px;object-fit:cover;border-radius:6px;border:1px solid #e8dce1;">
                                        <div>
                                            <a href="index.php?route=product-detail&id=<?= (int) $item['product_id'] ?>"
                                               style="font-weight:700;color:#282128;text-decoration:none;">
                                                <?= e($item['product_name']) ?>
                                            </a>
                                            <?php if (!empty($item['unavailable_reason'])): ?>
                                                <div style="color:#c9302c;font-size:13px;margin-top:4px;">
                                                    <?= e($item['unavailable_reason']) ?>
                                                </div>
                                            <?php else: ?>
                                                <div style="color:#695b62;font-size:13px;margin-top:4px;">
                                                    Còn lại: <?= (int) $item['stock'] ?> sản phẩm
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding:12px;">
                                    <div>Màu: <strong><?= e($item['color_name']) ?></strong></div>
                                    <div>Size: <strong><?= e($item['size_name']) ?></strong></div>
                                </td>
                                <td style="padding:12px;text-align:right;">
                                    <div style="font-weight:700;color:#8c4566;">
                                        <?= e(PricingService::formatVnd($item['unit_price'])) ?>
                                    </div>
                                    <?php if (!empty($item['is_discounted'])): ?>
                                        <div style="font-size:13px;color:#888;text-decoration:line-through;">
                                            <?= e(PricingService::formatVnd($item['original_price'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:12px;text-align:center;">
                                    <form method="post" action="index.php?route=cart/update" class="js-cart-qty-form"
                                          style="display:inline-flex;align-items:center;gap:4px;">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="cart_item_id" value="<?= (int) $item['cart_item_id'] ?>">
                                        <button type="button" class="js-qty-dec" aria-label="Giảm số lượng"
                                                style="width:28px;height:32px;border:1px solid #ccc;background:#fff;border-radius:4px;cursor:pointer;">-</button>
                                        <input type="number" name="quantity" class="js-qty-input"
                                               value="<?= (int) $item['quantity'] ?>"
                                               min="1" max="<?= max(1, (int) $item['stock']) ?>"
                                               style="width:54px;height:32px;text-align:center;border:1px solid #ccc;border-radius:4px;">
                                        <button type="button" class="js-qty-inc" aria-label="Tăng số lượng"
                                                style="width:28px;height:32px;border:1px solid #ccc;background:#fff;border-radius:4px;cursor:pointer;">+</button>
                                        <button type="submit" style="height:32px;padding:0 10px;border:1px solid #8c4566;background:#fff;color:#8c4566;border-radius:4px;cursor:pointer;">
                                            Lưu
                                        </button>
                                    </form>
                                </td>
                                <td style="padding:12px;text-align:right;font-weight:700;">
                                    <?= e(PricingService::formatVnd($item['line_total'])) ?>
                                </td>
                                <td style="padding:12px;text-align:center;">
                                    <form method="post" action="index.php?route=cart/remove" class="js-cart-remove-form">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="cart_item_id" value="<?= (int) $item['cart_item_id'] ?>">
                                        <button type="submit" style="padding:6px 10px;border:1px solid #d9534f;background:#fff;color:#d9534f;border-radius:4px;cursor:pointer;">
                                            Xóa
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(290px,1fr));gap:20px;align-items:start;">
                <div style="padding:20px;background:#fff;border:1px solid #e8dce1;border-radius:8px;">
                    <h2 style="margin-top:0;font-size:20px;">Mã giảm giá</h2>
                    <?php if (!empty($summary['coupon'])): ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:10px 12px;background:#f7eef2;border-radius:6px;">
                            <div>
                                <strong><?= e($summary['coupon']['code']) ?></strong>
                                <?php if (!empty($summary['coupon']['description'])): ?>
                                    <div style="font-size:13px;color:#695b62;"><?= e($summary['coupon']['description']) ?></div>
                                <?php endif; ?>
                            </div>
                            <form method="post" action="index.php?route=cart/remove-coupon">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                <input type="hidden" name="redirect_to" value="index.php?route=cart">
                                <button type="submit" style="padding:6px 12px;border:1px solid #d9534f;background:#fff;color:#d9534f;border-radius:4px;cursor:pointer;">
                                    Gỡ mã
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <form method="post" action="index.php?route=cart/apply-coupon" style="display:flex;gap:8px;flex-wrap:wrap;">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="redirect_to" value="index.php?route=cart">
                            <input type="text" name="coupon_code" class="js-coupon-input" placeholder="Nhập mã (VD: FLORA10)"
                                   maxlength="50" required
                                   style="flex:1;min-width:160px;padding:10px 12px;border:1px solid #ccc;border-radius:6px;text-transform:uppercase;">
                            <button type="submit" class="button" style="border:none;cursor:pointer;">Áp dụng</button>
                        </form>

                        <?php if (!empty($availableCoupons)): ?>
                            <div style="margin-top:14px;border-top:1px dashed #e8dce1;padding-top:12px;">
                                <div style="font-size:13px;font-weight:600;color:#695b62;margin-bottom:8px;">
                                    Mã giảm giá hiện có:
                                </div>
                                <div style="display:grid;gap:8px;">
                                    <?php foreach ($availableCoupons as $ac): ?>
                                        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:8px 10px;border:1px solid #e8dce1;border-radius:6px;background:#fff9fb;">
                                            <div style="font-size:13px;">
                                                <strong style="color:#8c4566;"><?= e($ac['code']) ?></strong>
                                                <?php if (!empty($ac['description'])): ?>
                                                    <div style="color:#282128;"><?= e($ac['description']) ?></div>
                                                <?php endif; ?>
                                                <?php if (empty($ac['is_eligible'])): ?>
                                                    <div style="color:#b36b00;font-size:12px;">
                                                        Mua thêm <?= e(PricingService::formatVnd((int) $ac['missing_amount'])) ?> tiền hàng để dùng mã này.
                                                    </div>
                                                <?php else: ?>
                                                    <div style="color:#2e7d32;font-size:12px;">
                                                        Ước tính giảm: <?= e(PricingService::formatVnd((int) $ac['estimated_discount'])) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <?php if (!empty($ac['is_eligible'])): ?>
                                                <form method="post" action="index.php?route=cart/apply-coupon">
                                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                    <input type="hidden" name="redirect_to" value="index.php?route=cart">
                                                    <input type="hidden" name="coupon_code" value="<?= e($ac['code']) ?>">
                                                    <button type="submit" style="padding:6px 10px;background:#8c4566;color:#fff;border:none;border-radius:4px;cursor:pointer;white-space:nowrap;">
                                                        Dùng mã
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div style="padding:20px;background:#fff;border:1px solid #e8dce1;border-radius:8px;">
                    <h2 style="margin-top:0;font-size:20px;">Tóm tắt đơn hàng</h2>
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                        <span>Tạm tính (sau giảm giá SP):</span>
                        <strong><?= e(PricingService::formatVnd($summary['subtotal'])) ?></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px;color:#2e7d32;">
                        <span>Giảm giá từ mã<?= !empty($summary['coupon']) ? ' (' . e($summary['coupon']['code']) . ')' : '' ?>:</span>
                        <strong>-<?= e(PricingService::formatVnd($summary['coupon_discount'])) ?></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:12px;">
                        <span>Phí vận chuyển:</span>
                        <strong><?= $summary['shipping_fee'] === 0 ? 'Miễn phí (0đ)' : e(PricingService::formatVnd($summary['shipping_fee'])) ?></strong>
                    </div>
                    <hr style="border:none;border-top:1px solid #e8dce1;margin:12px 0;">
                    <div style="display:flex;justify-content:space-between;font-size:20px;margin-bottom:16px;">
                        <span>Tổng thanh toán:</span>
                        <strong style="color:#8c4566;"><?= e(PricingService::formatVnd($summary['total_amount'])) ?></strong>
                    </div>

                    <?php if ($hasInvalid): ?>
                        <div style="color:#c9302c;font-size:14px;margin-bottom:10px;">
                            Vui lòng xóa hoặc điều chỉnh các sản phẩm không hợp lệ trước khi đặt hàng.
                        </div>
                        <button type="button" disabled style="width:100%;padding:12px;background:#ccc;color:#fff;border:none;border-radius:6px;cursor:not-allowed;">
                            Tiến hành đặt hàng
                        </button>
                    <?php else: ?>
                        <a class="button" href="index.php?route=checkout" style="display:block;text-align:center;width:100%;">
                            Tiến hành đặt hàng
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>
<script src="assets/js/cart.js"></script>
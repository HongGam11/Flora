<?php
/** @var array $coupons */
/** @var string $keyword */
/** @var string $status */
/** @var string $csrfToken */
/** @var array|null $flash */
?>
<section class="admin-coupons-page">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
        <h1 style="margin:0;">Quản lý mã giảm giá</h1>
        <a class="button" href="index.php?route=admin/coupons/create">+ Tạo mã giảm giá</a>
    </div>

    <?php if (!empty($flash)): ?>
        <div role="alert" style="padding:12px 16px;border-radius:6px;margin-bottom:16px;border:1px solid <?= $flash['type'] === 'error' ? '#d9534f' : '#5cb85c' ?>;background:#fff;">
            <?= e($flash['text']) ?>
        </div>
    <?php endif; ?>

    <form method="get" action="index.php" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
        <input type="hidden" name="route" value="admin/coupons">
        <input type="text" name="q" value="<?= e($keyword) ?>" placeholder="Tìm theo mã hoặc mô tả..."
               style="padding:9px 12px;border:1px solid #ccc;border-radius:6px;min-width:220px;">
        <select name="status" style="padding:9px 12px;border:1px solid #ccc;border-radius:6px;">
            <option value="">-- Tất cả trạng thái --</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Đang hoạt động</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Tạm ngưng</option>
        </select>
        <button type="submit" class="button" style="border:none;cursor:pointer;">Lọc</button>
        <?php if ($keyword !== '' || $status !== ''): ?>
            <a href="index.php?route=admin/coupons" style="padding:10px 14px;color:#695b62;text-decoration:none;">Xóa lọc</a>
        <?php endif; ?>
    </form>

    <div style="overflow-x:auto;background:#fff;border:1px solid #e8dce1;border-radius:8px;">
        <table style="width:100%;border-collapse:collapse;min-width:860px;font-size:14px;">
            <thead>
                <tr style="background:#f7eef2;text-align:left;border-bottom:1px solid #e8dce1;">
                    <th style="padding:10px;">Mã</th>
                    <th style="padding:10px;">Mức giảm</th>
                    <th style="padding:10px;">Đơn tối thiểu</th>
                    <th style="padding:10px;">Thời gian áp dụng (Giờ VN)</th>
                    <th style="padding:10px;text-align:center;">Lượt dùng</th>
                    <th style="padding:10px;text-align:center;">Mỗi khách</th>
                    <th style="padding:10px;text-align:center;">Trạng thái</th>
                    <th style="padding:10px;text-align:right;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($coupons)): ?>
                    <tr>
                        <td colspan="8" style="padding:24px;text-align:center;color:#695b62;">Chưa có mã giảm giá nào phù hợp.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($coupons as $c): ?>
                        <?php
                        $isPercent = $c['discount_type'] === 'percent';
                        $valClean  = rtrim(rtrim((string) $c['discount_value'], '0'), '.');
                        $discountLabel = $isPercent
                            ? ($valClean . '% (tối đa ' . PricingService::formatVnd((int) $c['max_discount_amount']) . ')')
                            : PricingService::formatVnd((float) $c['discount_value']);
                        $usedCount = (int) $c['used_count'];
                        $limitText = $c['usage_limit'] !== null ? ($usedCount . ' / ' . (int) $c['usage_limit']) : ($usedCount . ' / ∞');
                        $hasOrders = ((int) $c['attached_orders_count']) > 0;
                        ?>
                        <tr style="border-bottom:1px solid #eee;">
                            <td style="padding:10px;">
                                <strong><?= e($c['code']) ?></strong>
                                <?php if (!empty($c['description'])): ?>
                                    <div style="font-size:12px;color:#695b62;"><?= e($c['description']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding:10px;"><?= e($discountLabel) ?></td>
                            <td style="padding:10px;"><?= e(PricingService::formatVnd((int) $c['min_order_amount'])) ?></td>
                            <td style="padding:10px;">
                                <div>Từ: <?= e(AdminCouponController::formatUtcToVnDisplay((string) $c['start_at'])) ?></div>
                                <div>Đến: <?= e(AdminCouponController::formatUtcToVnDisplay((string) $c['end_at'])) ?></div>
                            </td>
                            <td style="padding:10px;text-align:center;"><?= e($limitText) ?></td>
                            <td style="padding:10px;text-align:center;">
                                <?= $c['per_user_limit'] !== null ? (int) $c['per_user_limit'] : '∞' ?>
                            </td>
                            <td style="padding:10px;text-align:center;">
                                <?php if ($c['status'] === 'active'): ?>
                                    <span style="padding:3px 8px;background:#e6f4ea;color:#1e7e34;border-radius:4px;font-weight:600;">Hoạt động</span>
                                <?php else: ?>
                                    <span style="padding:3px 8px;background:#f1f3f4;color:#5f6368;border-radius:4px;font-weight:600;">Tạm ngưng</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:10px;text-align:right;white-space:nowrap;">
                                <a href="index.php?route=admin/coupons/edit&id=<?= (int) $c['id'] ?>"
                                   style="padding:5px 10px;border:1px solid #8c4566;color:#8c4566;text-decoration:none;border-radius:4px;">Sửa</a>

                                <form method="post" action="index.php?route=admin/coupons/toggle" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                    <button type="submit" style="padding:5px 10px;border:1px solid #695b62;background:#fff;color:#282128;border-radius:4px;cursor:pointer;">
                                        <?= $c['status'] === 'active' ? 'Tắt' : 'Bật' ?>
                                    </button>
                                </form>

                                <?php if (!$hasOrders): ?>
                                    <form method="post" action="index.php?route=admin/coupons/delete" style="display:inline;"
                                          onsubmit="return confirm('Xác nhận xóa mã giảm giá này?');">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                        <button type="submit" style="padding:5px 10px;border:1px solid #d9534f;background:#fff;color:#d9534f;border-radius:4px;cursor:pointer;">
                                            Xóa
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
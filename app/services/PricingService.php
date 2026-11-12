<?php
declare(strict_types=1);

/**
 * Phụ trách: TV4 (Hoàng Huy).
 * Service tính giá sản phẩm, mã giảm giá, phí vận chuyển và tổng thanh toán.
 */
final class PricingService
{
    public const FREE_SHIPPING_THRESHOLD = 500000;
    public const DEFAULT_SHIPPING_FEE    = 30000;

    /**
     * Tính đơn giá bán thực tế của biến thể tại thời điểm hiện tại (UTC).
     */
    public static function calculateProductUnitPrice(
        int $originalPrice,
        float|string $discountPercent,
        ?string $discountStartAt,
        ?string $discountEndAt,
        ?DateTimeInterface $nowUtc = null
    ): array {
        $basePrice = max(0, $originalPrice);
        $pct       = (float) $discountPercent;

        if ($basePrice === 0 || $pct <= 0.0) {
            return [
                'unit_price'      => $basePrice,
                'discount_amount' => 0,
                'is_discounted'   => false,
            ];
        }

        $tz  = new DateTimeZone('UTC');
        $now = $nowUtc
            ? DateTimeImmutable::createFromInterface($nowUtc)->setTimezone($tz)
            : new DateTimeImmutable('now', $tz);

        $isInWindow = true;
        if ($discountStartAt !== null && $discountStartAt !== '') {
            $start = new DateTimeImmutable($discountStartAt, $tz);
            if ($now < $start) {
                $isInWindow = false;
            }
        }
        if ($isInWindow && $discountEndAt !== null && $discountEndAt !== '') {
            $end = new DateTimeImmutable($discountEndAt, $tz);
            if ($now >= $end) {
                $isInWindow = false;
            }
        }

        if (!$isInWindow) {
            return [
                'unit_price'      => $basePrice,
                'discount_amount' => 0,
                'is_discounted'   => false,
            ];
        }

        $effectivePct   = min(100.0, max(0.0, $pct));
        $discountAmount = (int) round($basePrice * $effectivePct / 100.0, 0, PHP_ROUND_HALF_UP);
        $discountAmount = min($basePrice, max(0, $discountAmount));
        $unitPrice      = $basePrice - $discountAmount;

        return [
            'unit_price'      => $unitPrice,
            'discount_amount' => $discountAmount,
            'is_discounted'   => $discountAmount > 0,
        ];
    }

    /**
     * Tính số tiền được giảm từ mã giảm giá trên $subtotal (không giảm phí ship).
     */
    public static function calculateCouponDiscount(int $subtotal, ?array $coupon): int
    {
        if ($subtotal <= 0 || empty($coupon)) {
            return 0;
        }

        $type  = (string) ($coupon['discount_type'] ?? '');
        $value = (float) ($coupon['discount_value'] ?? 0);
        if ($value <= 0.0) {
            return 0;
        }

        if ($type === 'percent') {
            $pct      = min(100.0, max(0.0, $value));
            $discount = (int) round($subtotal * $pct / 100.0, 0, PHP_ROUND_HALF_UP);
            if (isset($coupon['max_discount_amount']) && $coupon['max_discount_amount'] !== null) {
                $maxCap   = max(0, (int) $coupon['max_discount_amount']);
                $discount = min($discount, $maxCap);
            }
        } elseif ($type === 'fixed') {
            $discount = (int) round($value, 0, PHP_ROUND_HALF_UP);
        } else {
            return 0;
        }

        return min($subtotal, max(0, $discount));
    }

    /**
     * Phí ship 30.000đ; miễn ship từ 500.000đ xét trên $subtotal (sau giảm SP, trước mã giảm giá).
     */
    public static function calculateShippingFee(int $subtotal, bool $hasItems = true): int
    {
        if (!$hasItems || $subtotal <= 0) {
            return 0;
        }

        return $subtotal >= self::FREE_SHIPPING_THRESHOLD ? 0 : self::DEFAULT_SHIPPING_FEE;
    }

    /**
     * Tổng hợp toàn bộ số liệu tiền cho giỏ hàng và checkout.
     */
    public static function calculateSummary(
        array $cartItems,
        ?array $coupon = null,
        ?DateTimeInterface $nowUtc = null
    ): array {
        $pricedItems = [];
        $subtotal    = 0;

        foreach ($cartItems as $item) {
            $qty           = max(0, (int) ($item['quantity'] ?? 0));
            $originalPrice = max(0, (int) ($item['original_price'] ?? 0));

            $priceInfo = self::calculateProductUnitPrice(
                $originalPrice,
                $item['discount_percent'] ?? 0,
                $item['discount_start_at'] ?? null,
                $item['discount_end_at'] ?? null,
                $nowUtc
            );

            $unitPrice = $priceInfo['unit_price'];
            $lineTotal = $unitPrice * $qty;
            $subtotal += $lineTotal;

            $item['unit_price']            = $unitPrice;
            $item['unit_discount_amount']  = $priceInfo['discount_amount'];
            $item['is_discounted']         = $priceInfo['is_discounted'];
            $item['line_total']            = $lineTotal;

            $pricedItems[] = $item;
        }

        $hasItems       = count($pricedItems) > 0;
        $couponDiscount = self::calculateCouponDiscount($subtotal, $coupon);
        $shippingFee    = self::calculateShippingFee($subtotal, $hasItems);
        $afterCoupon    = max(0, $subtotal - $couponDiscount);
        $totalAmount    = $afterCoupon + $shippingFee;

        return [
            'items'               => $pricedItems,
            'has_items'           => $hasItems,
            'subtotal'            => $subtotal,
            'coupon'              => $coupon,
            'coupon_discount'     => $couponDiscount,
            'shipping_fee'        => $shippingFee,
            'total_amount'        => $totalAmount,
            'is_free_ship'        => $hasItems && ($shippingFee === 0),
            'amount_to_free_ship' => max(0, self::FREE_SHIPPING_THRESHOLD - $subtotal),
            'is_zero_order'       => $hasItems && ($totalAmount === 0),
        ];
    }

    public static function formatVnd(int|float $amount): string
    {
        return number_format((int) round((float) $amount), 0, ',', '.') . 'đ';
    }
}
<?php
declare(strict_types=1);

/**
 * Phụ trách: TV4 phối hợp TV5.
 * Model quản lý mã giảm giá (coupons) và kiểm tra lượt dùng đồng thời.
 */
final class CouponModel
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public static function normalizeCode(string $code): string
    {
        return mb_strtoupper(trim($code), 'UTF-8');
    }

    public function findAll(string $keyword = '', string $status = ''): array
    {
        $conditions = [];
        $params     = [];
        $types      = '';

        $kw = trim($keyword);
        if ($kw !== '') {
            $conditions[] = '(c.code LIKE ? OR c.description LIKE ?)';
            $like         = '%' . $kw . '%';
            $params[]     = $like;
            $params[]     = $like;
            $types       .= 'ss';
        }

        if (in_array($status, ['active', 'inactive'], true)) {
            $conditions[] = 'c.status = ?';
            $params[]     = $status;
            $types       .= 's';
        }

        $whereSql = empty($conditions) ? '' : ('WHERE ' . implode(' AND ', $conditions));

        $sql = "
            SELECT
                c.*,
                (SELECT COUNT(*) FROM orders o WHERE o.coupon_id = c.id AND o.status <> 'cancelled') AS used_count,
                (SELECT COUNT(*) FROM orders o2 WHERE o2.coupon_id = c.id) AS attached_orders_count
            FROM coupons c
            {$whereSql}
            ORDER BY c.id DESC
        ";

        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    public function getAvailableCouponsForUser(int $userId, int $subtotal): array
    {
        $sql = "
            SELECT
                c.*,
                (SELECT COUNT(*) FROM orders o1 WHERE o1.coupon_id = c.id AND o1.status <> 'cancelled') AS used_total,
                (SELECT COUNT(*) FROM orders o2 WHERE o2.coupon_id = c.id AND o2.user_id = ? AND o2.status <> 'cancelled') AS used_by_user
            FROM coupons c
            WHERE c.status = 'active'
              AND c.start_at <= UTC_TIMESTAMP()
              AND UTC_TIMESTAMP() < c.end_at
            ORDER BY c.min_order_amount ASC, c.id DESC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $available = [];
        foreach ($rows as $row) {
            $usedTotal  = (int) $row['used_total'];
            $usedByUser = (int) $row['used_by_user'];

            if ($row['usage_limit'] !== null && $usedTotal >= (int) $row['usage_limit']) {
                continue;
            }
            if ($row['per_user_limit'] !== null && $usedByUser >= (int) $row['per_user_limit']) {
                continue;
            }

            $minOrder                  = (int) $row['min_order_amount'];
            $row['is_eligible']        = ($subtotal >= $minOrder);
            $row['missing_amount']     = max(0, $minOrder - $subtotal);
            $row['estimated_discount'] = $row['is_eligible']
                ? PricingService::calculateCouponDiscount($subtotal, $row)
                : 0;

            $available[] = $row;
        }

        return $available;
    }

    public function findById(int $id, bool $forUpdate = false): ?array
    {
        $sql  = 'SELECT * FROM coupons WHERE id = ?' . ($forUpdate ? ' FOR UPDATE' : '');
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    public function findByCode(string $code, bool $forUpdate = false): ?array
    {
        $normalized = self::normalizeCode($code);
        if ($normalized === '') {
            return null;
        }

        $sql  = 'SELECT * FROM coupons WHERE code = ?' . ($forUpdate ? ' FOR UPDATE' : '');
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $normalized);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    public function existsCode(string $code, ?int $excludeId = null): bool
    {
        $normalized = self::normalizeCode($code);
        if ($excludeId !== null) {
            $stmt = $this->conn->prepare('SELECT id FROM coupons WHERE code = ? AND id <> ? LIMIT 1');
            $stmt->bind_param('si', $normalized, $excludeId);
        } else {
            $stmt = $this->conn->prepare('SELECT id FROM coupons WHERE code = ? LIMIT 1');
            $stmt->bind_param('s', $normalized);
        }
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc() !== null;
        $stmt->close();

        return $exists;
    }

    public function countUsedTotal(int $couponId): int
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE coupon_id = ? AND status <> 'cancelled'");
        $stmt->bind_param('i', $couponId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int) ($row['cnt'] ?? 0);
    }

    public function countUsedByUser(int $couponId, int $userId): int
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE coupon_id = ? AND user_id = ? AND status <> 'cancelled'");
        $stmt->bind_param('ii', $couponId, $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int) ($row['cnt'] ?? 0);
    }

    public function validateAndLockForOrder(
        string $code,
        int $userId,
        int $subtotal,
        bool $forUpdate = false,
        ?DateTimeInterface $nowUtc = null
    ): array {
        $normalized = self::normalizeCode($code);
        if ($normalized === '') {
            throw new RuntimeException('Vui lòng nhập mã giảm giá.');
        }

        $coupon = $this->findByCode($normalized, $forUpdate);
        if (!$coupon) {
            throw new RuntimeException('Mã giảm giá không tồn tại.');
        }

        if ($coupon['status'] !== 'active') {
            throw new RuntimeException('Mã giảm giá hiện đang tạm ngưng áp dụng.');
        }

        $tz    = new DateTimeZone('UTC');
        $now   = $nowUtc ? DateTimeImmutable::createFromInterface($nowUtc)->setTimezone($tz) : new DateTimeImmutable('now', $tz);
        $start = new DateTimeImmutable((string) $coupon['start_at'], $tz);
        $end   = new DateTimeImmutable((string) $coupon['end_at'], $tz);

        if ($now < $start) {
            throw new RuntimeException('Mã giảm giá chưa đến thời gian áp dụng.');
        }
        if ($now >= $end) {
            throw new RuntimeException('Mã giảm giá đã hết hạn sử dụng.');
        }

        $minOrderAmount = (int) $coupon['min_order_amount'];
        if ($subtotal < $minOrderAmount) {
            $formattedMin = PricingService::formatVnd($minOrderAmount);
            throw new RuntimeException("Đơn hàng cần đạt tối thiểu {$formattedMin} tiền hàng sau giảm sản phẩm để dùng mã này.");
        }

        $couponId = (int) $coupon['id'];

        if ($coupon['usage_limit'] !== null) {
            $usageLimit = (int) $coupon['usage_limit'];
            if ($this->countUsedTotal($couponId) >= $usageLimit) {
                throw new RuntimeException('Mã giảm giá đã hết lượt sử dụng.');
            }
        }

        if ($coupon['per_user_limit'] !== null) {
            $perUserLimit = (int) $coupon['per_user_limit'];
            if ($this->countUsedByUser($couponId, $userId) >= $perUserLimit) {
                throw new RuntimeException('Bạn đã sử dụng hết số lượt cho phép của mã giảm giá này.');
            }
        }

        $discountAmount = PricingService::calculateCouponDiscount($subtotal, $coupon);

        return [
            'coupon'          => $coupon,
            'discount_amount' => $discountAmount,
        ];
    }

    public function create(array $data): int
    {
        $sql = '
            INSERT INTO coupons (
                code, description, discount_type, discount_value,
                max_discount_amount, min_order_amount, start_at, end_at,
                usage_limit, per_user_limit, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ';

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            'sssdiissiis',
            $data['code'],
            $data['description'],
            $data['discount_type'],
            $data['discount_value'],
            $data['max_discount_amount'],
            $data['min_order_amount'],
            $data['start_at'],
            $data['end_at'],
            $data['usage_limit'],
            $data['per_user_limit'],
            $data['status']
        );
        $stmt->execute();
        $id = (int) $stmt->insert_id;
        $stmt->close();

        return $id;
    }

    public function update(int $id, array $data): void
    {
        $sql = '
            UPDATE coupons SET
                code = ?,
                description = ?,
                discount_type = ?,
                discount_value = ?,
                max_discount_amount = ?,
                min_order_amount = ?,
                start_at = ?,
                end_at = ?,
                usage_limit = ?,
                per_user_limit = ?,
                status = ?
            WHERE id = ?
        ';

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            'sssdiissiisi',
            $data['code'],
            $data['description'],
            $data['discount_type'],
            $data['discount_value'],
            $data['max_discount_amount'],
            $data['min_order_amount'],
            $data['start_at'],
            $data['end_at'],
            $data['usage_limit'],
            $data['per_user_limit'],
            $data['status'],
            $id
        );
        $stmt->execute();
        $stmt->close();
    }

    public function toggleStatus(int $id): string
    {
        $coupon = $this->findById($id);
        if (!$coupon) {
            throw new RuntimeException('Không tìm thấy mã giảm giá.');
        }

        $newStatus = $coupon['status'] === 'active' ? 'inactive' : 'active';
        $stmt      = $this->conn->prepare('UPDATE coupons SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $newStatus, $id);
        $stmt->execute();
        $stmt->close();

        return $newStatus;
    }

    public function hasOrders(int $id): bool
    {
        $stmt = $this->conn->prepare('SELECT 1 FROM orders WHERE coupon_id = ? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $has = $stmt->get_result()->fetch_assoc() !== null;
        $stmt->close();

        return $has;
    }

    public function deleteIfUnused(int $id): void
    {
        if ($this->hasOrders($id)) {
            throw new RuntimeException('Mã giảm giá đã gắn vào đơn hàng nên không được phép xóa. Bạn có thể chuyển sang trạng thái tạm ngưng.');
        }

        $stmt = $this->conn->prepare('DELETE FROM coupons WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
}
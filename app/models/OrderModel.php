<?php
declare(strict_types=1);

/**
 * Phụ trách: TV5 phối hợp TV6 TV2.
 * Truy vấn bảng orders và order_items (snapshot đơn hàng).
 */
final class OrderModel
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Tìm đơn hàng theo checkout_token và user_id (chống tạo đơn trùng).
     */
    public function findByCheckoutTokenAndUser(string $token, int $userId): ?array
    {
        $sql = 'SELECT * FROM orders WHERE checkout_token = ? AND user_id = ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('si', $token, $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    /**
     * Tìm đơn hàng theo id và user_id (đảm bảo khách chỉ xem đơn của chính mình).
     */
    public function findByIdAndUser(int $orderId, int $userId): ?array
    {
        $sql = 'SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ii', $orderId, $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    /**
     * Tìm đơn hàng theo order_code và user_id.
     */
    public function findByCodeAndUser(string $orderCode, int $userId): ?array
    {
        $sql = 'SELECT * FROM orders WHERE order_code = ? AND user_id = ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('si', $orderCode, $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    /**
     * Danh sách đơn hàng của một khách hàng (mới nhất lên đầu).
     */
    public function getOrdersByUser(int $userId, ?string $statusFilter = null): array
    {
        $allowedStatuses = ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'];
        if ($statusFilter !== null && in_array($statusFilter, $allowedStatuses, true)) {
            $sql = 'SELECT o.*,
                           (SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi WHERE oi.order_id = o.id) AS total_items
                    FROM orders o
                    WHERE o.user_id = ? AND o.status = ?
                    ORDER BY o.created_at DESC, o.id DESC';
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('is', $userId, $statusFilter);
        } else {
            $sql = 'SELECT o.*,
                           (SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi WHERE oi.order_id = o.id) AS total_items
                    FROM orders o
                    WHERE o.user_id = ?
                    ORDER BY o.created_at DESC, o.id DESC';
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $userId);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Lấy danh sách dòng chi tiết snapshot của một đơn hàng.
     */
    public function getOrderItems(int $orderId): array
    {
        $sql = 'SELECT oi.*,
                       (oi.quantity * oi.unit_price) AS line_total
                FROM order_items oi
                WHERE oi.order_id = ?
                ORDER BY oi.id ASC';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Lấy lịch sử thay đổi trạng thái của đơn hàng.
     */
    public function getOrderStatusHistory(int $orderId): array
    {
        $sql = 'SELECT h.*, u.full_name AS actor_name, u.role AS actor_role
                FROM order_status_history h
                LEFT JOIN users u ON u.id = h.changed_by
                WHERE h.order_id = ?
                ORDER BY h.created_at ASC, h.id ASC';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Đếm tổng lượt đã dùng của mã giảm giá (tính đơn chưa cancelled).
     */
    public function countCouponUsage(int $couponId): int
    {
        $sql = "SELECT COUNT(*) AS cnt FROM orders WHERE coupon_id = ? AND status <> 'cancelled'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $couponId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Đếm lượt đã dùng của mã giảm giá theo từng user (tính đơn chưa cancelled).
     */
    public function countCouponUsageByUser(int $couponId, int $userId): int
    {
        $sql = "SELECT COUNT(*) AS cnt FROM orders WHERE coupon_id = ? AND user_id = ? AND status <> 'cancelled'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ii', $couponId, $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Tạo bản ghi đơn hàng (chạy bên trong transaction của CheckoutService).
     */
    public function createOrder(array $data): int
    {
        $sql = 'INSERT INTO orders (
                    order_code, checkout_token, user_id,
                    recipient_name, recipient_phone, shipping_address, note,
                    subtotal, coupon_id, coupon_code, coupon_discount,
                    shipping_fee, total_amount,
                    payment_method, payment_status, status,
                    payment_expires_at, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $stmt = $this->conn->prepare($sql);
        // Dùng 's' cho coupon_id (vị trí thứ 9) để truyền đúng SQL NULL khi không áp mã
        $stmt->bind_param(
            'ssissssdssdddssssss',
            $data['order_code'],
            $data['checkout_token'],
            $data['user_id'],
            $data['recipient_name'],
            $data['recipient_phone'],
            $data['shipping_address'],
            $data['note'],
            $data['subtotal'],
            $data['coupon_id'],
            $data['coupon_code'],
            $data['coupon_discount'],
            $data['shipping_fee'],
            $data['total_amount'],
            $data['payment_method'],
            $data['payment_status'],
            $data['status'],
            $data['payment_expires_at'],
            $data['created_at'],
            $data['updated_at']
        );
        $stmt->execute();
        return (int) $this->conn->insert_id;
    }

    /**
     * Tạo một dòng chi tiết snapshot trong order_items.
     */
    public function createOrderItem(array $item): void
    {
        $sql = 'INSERT INTO order_items (
                    order_id, variant_id, product_name, size_name, color_name,
                    quantity, original_unit_price, unit_price
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            'iisssidd',
            $item['order_id'],
            $item['variant_id'],
            $item['product_name'],
            $item['size_name'],
            $item['color_name'],
            $item['quantity'],
            $item['original_unit_price'],
            $item['unit_price']
        );
        $stmt->execute();
    }
}
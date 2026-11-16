<?php
final class OrderStatusHistoryModel
{
    public function __construct(private mysqli $conn) {}

    /** Caller owns the order transaction. Do not begin/commit separately here. */
    public function addHistory(int $orderId, string $status, string $note, ?int $createdBy, ?string $oldStatus = null): void
    {
        $stmt = $this->conn->prepare('INSERT INTO order_status_history
            (order_id, changed_by, old_status, new_status, note, created_at)
            VALUES (?, ?, ?, ?, ?, UTC_TIMESTAMP())');
        $stmt->bind_param('iisss', $orderId, $createdBy, $oldStatus, $status, $note);
        $stmt->execute();
        $stmt->close();
    }
    public function getHistoryByOrder(int $orderId): array
    {
        $stmt = $this->conn->prepare('SELECT h.*, u.full_name AS actor_name
            FROM order_status_history h LEFT JOIN users u ON u.id=h.changed_by
            WHERE h.order_id=? ORDER BY h.created_at, h.id');
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }
}

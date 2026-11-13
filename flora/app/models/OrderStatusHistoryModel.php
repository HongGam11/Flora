<?php
class OrderStatusHistoryModel {
    private $db;

    public function __construct($conn) {
        $this->db = $conn;
    }

    // Thêm bản ghi lịch sử
    public function addHistory($orderId, $status, $note, $createdBy) {
        $stmt = $this->db->prepare("INSERT INTO order_status_history (order_id, status, note, created_by, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("issi", $orderId, $status, $note, $createdBy);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    // Lấy danh sách lịch sử theo đơn hàng
    public function getHistoryByOrder($orderId) {
        $stmt = $this->db->prepare("
            SELECT h.*, u.fullname FROM order_status_history h 
            LEFT JOIN users u ON h.created_by = u.id 
            WHERE h.order_id = ? 
            ORDER BY h.created_at ASC
        ");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $list = [];
        while ($row = $result->fetch_assoc()) {
            $list[] = $row;
        }
        $stmt->close();
        return $list;
    }
}

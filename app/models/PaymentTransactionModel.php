<?php
final class PaymentTransactionModel
{
    public function __construct(private mysqli $db) {}

    /** Gọi bên trong transaction đang khóa đơn, không tự commit. */
    public function recordCodReceipt(int $orderId, int $amount, int $actorId): void
    {
        if ($amount <= 0) throw new DomainException('Số tiền thu COD phải lớn hơn 0.');
        $code = 'COD_' . bin2hex(random_bytes(16));
        $message = 'Nhân viên xác nhận đã thu tiền COD (đồ án mô phỏng).';
        $stmt = $this->db->prepare("INSERT INTO payment_transactions
            (order_id, transaction_code, transaction_type, payment_method, amount, status,
             is_simulated, processed_at, processed_by, result_message)
            VALUES (?, ?, 'payment', 'cod', ?, 'success', 1, UTC_TIMESTAMP(), ?, ?)");
        $stmt->bind_param('isiis', $orderId, $code, $amount, $actorId, $message);
        $stmt->execute();
        $stmt->close();
    }
    public function getTransactionsByOrder(int $orderId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM payment_transactions WHERE order_id=? ORDER BY created_at, id');
        $stmt->bind_param('i', $orderId); $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

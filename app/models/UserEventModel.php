<?php
/** Phụ trách: TV2 phối hợp TV4. Giao tiếp ghi thêm giỏ cho D7 đợt 1. */
final class UserEventModel
{
    public function __construct(private mysqli $db) {}

    public function logAddToCart(int $userId, string $sessionKey, int $productId): void
    {
        if ($userId<1 || $productId<1 || $sessionKey==='' || strlen($sessionKey)>64) {
            throw new InvalidArgumentException('Thông tin sự kiện thêm giỏ không hợp lệ.');
        }
        $stmt=$this->db->prepare("INSERT INTO user_events
            (user_id, session_key, event_type, product_id, search_keyword)
            VALUES (?, ?, 'add_to_cart', ?, NULL)");
        $stmt->bind_param('isi',$userId,$sessionKey,$productId);
        $stmt->execute(); $stmt->close();
    }
}

<?php
declare(strict_types=1);

/** Đọc ảnh chung/ảnh theo màu của sản phẩm. */
final class ProductImageModel
{
    public function __construct(private mysqli $db) {}

    public function getByProductId(int $productId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, product_id, color_id, image_path, is_main, sort_order
             FROM product_images WHERE product_id = ?
             ORDER BY is_main DESC, sort_order ASC, id ASC'
        );
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getMainByProductId(int $productId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, product_id, color_id, image_path, is_main, sort_order
             FROM product_images WHERE product_id = ? AND is_main = 1 LIMIT 1'
        );
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    /** Ảnh màu được ưu tiên; nếu chưa có thì trả ảnh chung. */
    public function getForColor(int $productId, int $colorId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, product_id, color_id, image_path, is_main, sort_order
             FROM product_images
             WHERE product_id = ? AND (color_id = ? OR color_id IS NULL)
             ORDER BY (color_id = ?) DESC, is_main DESC, sort_order ASC, id ASC'
        );
        $stmt->bind_param('iii', $productId, $colorId, $colorId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

<?php
declare(strict_types=1);

/** Dữ liệu size/màu/giá/tồn theo từng biến thể. Không điều chỉnh kho ở C1. */
final class ProductVariantModel
{
    public function __construct(private mysqli $db) {}

    /** Lấy các biến thể của một sản phẩm, gồm cả biến thể ngừng bán cho admin. */
    public function getByProductId(int $productId, bool $activeOnly = false): array
    {
        $sql = "SELECT v.id, v.product_id, v.size_id, s.name AS size_name,
                       v.color_id, c.name AS color_name, c.hex_code,
                       v.original_price, v.stock, v.status,
                       p.discount_percent, p.discount_start_at, p.discount_end_at,
                       CASE
                         WHEN p.discount_percent > 0
                          AND UTC_TIMESTAMP() >= p.discount_start_at
                          AND UTC_TIMESTAMP() < p.discount_end_at
                         THEN ROUND(v.original_price * (1 - p.discount_percent / 100), 0)
                         ELSE v.original_price
                       END AS sale_price
                FROM product_variants v
                INNER JOIN sizes s ON s.id = v.size_id
                INNER JOIN colors c ON c.id = v.color_id
                INNER JOIN products p ON p.id = v.product_id
                WHERE v.product_id = ?";
        if ($activeOnly) $sql .= " AND v.status = 'active'";
        $sql .= ' ORDER BY s.sort_order, s.id, c.id, v.id';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Tìm một biến thể theo ID, trả cả dữ liệu sản phẩm/danh mục để kiểm tra mua. */
    public function findById(int $variantId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT v.id AS variant_id, v.product_id, v.size_id, s.name AS size_name,
                    v.color_id, c.name AS color_name, c.hex_code,
                    v.original_price, v.stock, v.status AS variant_status,
                    p.name AS product_name, p.status AS product_status,
                    p.category_id, cat.status AS category_status,
                    p.discount_percent, p.discount_start_at, p.discount_end_at,
                    CASE
                      WHEN p.discount_percent > 0
                       AND UTC_TIMESTAMP() >= p.discount_start_at
                       AND UTC_TIMESTAMP() < p.discount_end_at
                      THEN ROUND(v.original_price * (1 - p.discount_percent / 100), 0)
                      ELSE v.original_price
                    END AS sale_price
             FROM product_variants v
             INNER JOIN products p ON p.id = v.product_id
             INNER JOIN categories cat ON cat.id = p.category_id
             INNER JOIN sizes s ON s.id = v.size_id
             INNER JOIN colors c ON c.id = v.color_id
             WHERE v.id = ? LIMIT 1"
        );
        $stmt->bind_param('i', $variantId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }
}

<?php
declare(strict_types=1);

/**
 * Model dữ liệu sản phẩm dùng chung.
 * Giá/tồn thuộc product_variants; model trả giá thấp nhất để hiển thị danh sách.
 */
final class ProductModel
{
    public function __construct(private mysqli $db) {}

    /**
     * Danh sách sản phẩm đủ điều kiện hiển thị ở cửa hàng.
     * $filters: category_id?, keyword?, featured?, limit?, offset?.
     */
    public function getStorefrontList(array $filters = []): array
    {
        $where = [
            "p.status = 'active'",
            "c.status = 'active'",
            "c.parent_id IS NOT NULL",
            "EXISTS (SELECT 1 FROM product_images mi WHERE mi.product_id = p.id AND mi.is_main = 1)",
            "EXISTS (SELECT 1 FROM product_variants av WHERE av.product_id = p.id AND av.status = 'active')",
        ];
        $types = '';
        $params = [];
        if (!empty($filters['category_id'])) {
            $where[] = 'p.category_id = ?';
            $types .= 'i'; $params[] = (int) $filters['category_id'];
        }
        if (isset($filters['keyword']) && trim((string) $filters['keyword']) !== '') {
            $where[] = 'p.name LIKE ?';
            $types .= 's'; $params[] = '%' . trim((string) $filters['keyword']) . '%';
        }
        if (isset($filters['featured'])) {
            $where[] = 'p.is_featured = ?';
            $types .= 'i'; $params[] = (int) (bool) $filters['featured'];
        }
        $limit = max(1, min(100, (int) ($filters['limit'] ?? 24)));
        $offset = max(0, (int) ($filters['offset'] ?? 0));
        $sql = "SELECT p.id, p.category_id, c.name AS category_name,
                       parent.name AS parent_category_name,
                       p.name, p.description, p.discount_percent,
                       p.discount_start_at, p.discount_end_at, p.is_featured,
                       p.status, p.published_at, p.created_at,
                       (SELECT i.image_path FROM product_images i
                        WHERE i.product_id = p.id AND i.is_main = 1 LIMIT 1) AS main_image,
                       (SELECT MIN(CASE
                          WHEN p.discount_percent > 0
                           AND UTC_TIMESTAMP() >= p.discount_start_at
                           AND UTC_TIMESTAMP() < p.discount_end_at
                          THEN ROUND(v.original_price * (1 - p.discount_percent / 100), 0)
                          ELSE v.original_price END)
                        FROM product_variants v
                        WHERE v.product_id = p.id AND v.status = 'active') AS min_sale_price,
                       (SELECT MIN(v.original_price) FROM product_variants v
                        WHERE v.product_id = p.id AND v.status = 'active') AS min_original_price,
                       (SELECT SUM(v.stock) FROM product_variants v
                        WHERE v.product_id = p.id AND v.status = 'active') AS active_stock
                FROM products p
                INNER JOIN categories c ON c.id = p.category_id
                INNER JOIN categories parent ON parent.id = c.parent_id
                WHERE " . implode(' AND ', $where) . '
                ORDER BY p.is_featured DESC, p.published_at DESC, p.id DESC
                LIMIT ? OFFSET ?';
        $types .= 'ii'; $params[] = $limit; $params[] = $offset;
        $stmt = $this->db->prepare($sql);
        $this->bind($stmt, $types, $params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Chi tiết sản phẩm; mặc định chỉ trả sản phẩm đang đủ điều kiện bán. */
    public function getStorefrontDetail(int $productId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT p.id, p.category_id, c.name AS category_name,
                    parent.name AS parent_category_name,
                    p.name, p.description, p.discount_percent,
                    p.discount_start_at, p.discount_end_at, p.is_featured,
                    p.status, p.published_at, p.created_at,
                    (SELECT i.image_path FROM product_images i
                     WHERE i.product_id = p.id AND i.is_main = 1 LIMIT 1) AS main_image
             FROM products p
             INNER JOIN categories c ON c.id = p.category_id
             INNER JOIN categories parent ON parent.id = c.parent_id
             WHERE p.id = ? AND p.status = 'active' AND c.status = 'active'
               AND parent.status = 'active'
               AND EXISTS (SELECT 1 FROM product_images mi
                           WHERE mi.product_id = p.id AND mi.is_main = 1)
               AND EXISTS (SELECT 1 FROM product_variants av
                           WHERE av.product_id = p.id AND av.status = 'active')
             LIMIT 1"
        );
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    /** Dữ liệu đầy đủ cho trang quản trị, kể cả sản phẩm inactive. */
    public function findById(int $productId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.id, p.category_id, c.name AS category_name,
                    p.name, p.description, p.discount_percent,
                    p.discount_start_at, p.discount_end_at, p.is_featured,
                    p.status, p.published_at, p.created_at, p.updated_at
             FROM products p INNER JOIN categories c ON c.id = p.category_id
             WHERE p.id = ? LIMIT 1'
        );
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    private function bind(mysqli_stmt $stmt, string $types, array &$params): void
    {
        if ($types === '') return;
        $refs = [$types];
        foreach ($params as $key => &$value) $refs[] = &$value;
        $stmt->bind_param(...$refs);
    }
}

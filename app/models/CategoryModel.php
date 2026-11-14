<?php
declare(strict_types=1);

/**
 * Dữ liệu danh mục dùng chung cho cửa hàng và trang quản trị.
 * Phụ trách: TV3; TV2 sử dụng các hàm đọc ở đây.
 */
final class CategoryModel
{
    public function __construct(private mysqli $db) {}

    /** Danh mục con đang hoạt động, có kèm nhóm gốc. */
    public function getActiveChildren(?int $parentId = null): array
    {
        $sql = "SELECT c.id, c.parent_id, c.name, c.status, p.name AS parent_name
                FROM categories c
                INNER JOIN categories p ON p.id = c.parent_id
                WHERE c.parent_id IS NOT NULL AND c.status = 'active'
                  AND p.parent_id IS NULL AND p.status = 'active'";
        if ($parentId !== null) {
            $sql .= ' AND c.parent_id = ?';
        }
        $sql .= ' ORDER BY p.id, c.id';
        $stmt = $this->db->prepare($sql);
        if ($parentId !== null) $stmt->bind_param('i', $parentId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Cây hai cấp: nhóm gốc -> danh mục con. */
    public function getActiveTree(): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.id AS parent_id, p.name AS parent_name,
                    c.id AS category_id, c.name AS category_name
             FROM categories p
             LEFT JOIN categories c ON c.parent_id = p.id AND c.status = 'active'
             WHERE p.parent_id IS NULL AND p.status = 'active'
             ORDER BY p.id, c.id"
        );
        $stmt->execute();
        $tree = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $id = (int) $row['parent_id'];
            if (!isset($tree[$id])) {
                $tree[$id] = ['id' => $id, 'name' => $row['parent_name'], 'children' => []];
            }
            if ($row['category_id'] !== null) {
                $tree[$id]['children'][] = [
                    'id' => (int) $row['category_id'],
                    'name' => $row['category_name'],
                ];
            }
        }
        return array_values($tree);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT c.id, c.parent_id, c.name, c.status,
                    p.name AS parent_name, p.status AS parent_status
             FROM categories c LEFT JOIN categories p ON p.id = c.parent_id
             WHERE c.id = ? LIMIT 1'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }
}

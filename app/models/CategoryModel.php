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

    // Giao tiếp TV2 đã ghép: giữ nguyên để cửa hàng tiếp tục hoạt động.

 public static function all(mysqli $conn): array {
  $result=$conn->query("SELECT c.id,c.name,r.name AS root_name FROM categories c JOIN categories r ON r.id=c.parent_id WHERE c.status='active' AND r.status='active' ORDER BY r.id,c.id");
  return $result->fetch_all(MYSQLI_ASSOC);
 }


    // TV3 đợt 1: nghiệp vụ quản trị đặt ngay trong model có sẵn.
    public function getAdminList(): array {return $this->adminRows('SELECT c.*,p.name parent_name FROM categories c LEFT JOIN categories p ON p.id=c.parent_id ORDER BY COALESCE(c.parent_id,c.id),c.parent_id IS NOT NULL,c.id');}

    public function childCategories(): array {return $this->adminRows("SELECT c.id,CONCAT(p.name,' / ',c.name) name FROM categories c JOIN categories p ON p.id=c.parent_id WHERE c.status='active' AND p.status='active' AND p.parent_id IS NULL ORDER BY p.id,c.id");}

    private function categoryRow(int $id): ?array {return $this->adminOne('SELECT * FROM categories WHERE id=?','i',[$id]);}

    public function save(?int $id,int $parent,string $name,string $status): void {
        $name=trim($name); if($name==='' || preg_match('//u', $name)!==1 || preg_match_all('/./us', $name)>100 || !in_array($status,['active','inactive'],true))throw new DomainException('Danh mục không hợp lệ');
        $root=$this->categoryRow($parent);if(!$root || $root['parent_id']!==null)throw new DomainException('Chỉ chọn nhóm gốc');
        if($id){$old=$this->categoryRow($id);if(!$old || $old['parent_id']===null || (int)$old['parent_id']!==$parent)throw new DomainException('Không sửa nhóm gốc hoặc chuyển nhóm');$this->adminExecute('UPDATE categories SET name=?,status=? WHERE id=?','ssi',[$name,$status,$id]);}
        else $this->adminExecute('INSERT INTO categories(parent_id,name,status) VALUES(?,?,?)','iss',[$parent,$name,$status]);
    }


    private function adminOne(string $sql, string $types = '', array $values = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        if ($types !== '') $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }
    private function adminExecute(string $sql, string $types = '', array $values = []): int
    {
        $stmt = $this->db->prepare($sql);
        if ($types !== '') $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected;
    }
    private function adminRows(string $sql): array
    {
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }
}

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
            "parent.status = 'active'",
            "parent.parent_id IS NULL",
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

    // Giao tiếp TV2 đã ghép: giữ nguyên để cửa hàng tiếp tục hoạt động.

public static function search(mysqli $conn, array $filters, string $sort, ?int $limit = null, int $offset = 0): array
{
    $where = ["p.status = 'active'", "c.status = 'active'", "root.status = 'active'", "v.status = 'active'"];
    $types = '';
    $values = [];

    if (!empty($filters['category'])) {
        $where[] = 'root.name = ?';
        $types .= 's';
        $values[] = $filters['category'] === 'nam' ? 'Thời trang nam' : 'Thời trang nữ';
    }
    if (!empty($filters['category_id'])) {
        $where[] = 'c.id = ?'; $types .= 'i'; $values[] = (int) $filters['category_id'];
    }
    if (!empty($filters['featured'])) {
        $where[] = 'p.is_featured = 1';
    }
    if (!empty($filters['search'])) {
        $where[] = '(p.name LIKE ? OR c.name LIKE ?)';
        $types .= 'ss';
        $term = '%' . $filters['search'] . '%';
        $values[] = $term;
        $values[] = $term;
    }
    if (isset($filters['id'])) {
        $where[] = 'p.id = ?';
        $types .= 'i';
        $values[] = (int) $filters['id'];
    }

    $priceExpression = 'MIN(CASE WHEN p.discount_percent > 0
        AND p.discount_start_at <= UTC_TIMESTAMP()
        AND p.discount_end_at > UTC_TIMESTAMP()
        THEN ROUND(v.original_price * (100 - p.discount_percent) / 100)
        ELSE v.original_price END)';
    $having = [];
    foreach (['min_price' => '>=', 'max_price' => '<='] as $key => $operator) {
        if (isset($filters[$key])) {
            $having[] = $priceExpression . ' ' . $operator . ' ?';
            $types .= 'i';
            $values[] = (int) $filters[$key];
        }
    }

    $baseSql = "FROM products p
        JOIN categories c ON c.id = p.category_id
        JOIN categories root ON root.id = c.parent_id
        JOIN product_variants v ON v.product_id = p.id
        WHERE " . implode(' AND ', $where) . "
        GROUP BY p.id, p.name, p.description, p.is_featured, p.published_at,
                 p.discount_percent, p.discount_start_at, p.discount_end_at,
                 c.name, root.name";
    if ($having !== []) {
        $baseSql .= ' HAVING ' . implode(' AND ', $having);
    }

    $sortOptions = [
        'new' => 'p.published_at DESC, p.id DESC',
        'price_asc' => 'price ASC, p.id DESC',
        'price_desc' => 'price DESC, p.id DESC',
    ];
    $orderBy = $sortOptions[$sort] ?? $sortOptions['new'];
    $selectSql = "SELECT p.id, p.name, p.description, p.is_featured, p.published_at,
            p.discount_percent, c.name AS category_name, root.name AS root_name,
            MIN(v.original_price) AS original_price, {$priceExpression} AS price,
            (SELECT pi.image_path FROM product_images pi
             WHERE pi.product_id = p.id AND pi.is_main = 1 LIMIT 1) AS main_image
        {$baseSql}";

    $countStatement = $conn->prepare("SELECT COUNT(*) AS total FROM (SELECT p.id {$baseSql}) AS filtered_products");
    flora_bind($countStatement, $types, $values);
    $countStatement->execute();
    $total = (int) $countStatement->get_result()->fetch_assoc()['total'];
    $countStatement->close();

    $querySql = $selectSql . " ORDER BY {$orderBy}";
    $queryTypes = $types;
    $queryValues = $values;
    if ($limit !== null) {
        $querySql .= ' LIMIT ? OFFSET ?';
        $queryTypes .= 'ii';
        $queryValues[] = $limit;
        $queryValues[] = $offset;
    }

    $statement = $conn->prepare($querySql);
    flora_bind($statement, $queryTypes, $queryValues);
    $statement->execute();
    $result = $statement->get_result();
    $products = [];
    while ($product = $result->fetch_assoc()) {
        $products[] = $product;
    }
    $statement->close();

    return ['products' => $products, 'total' => $total];
}


public static function variants(mysqli $conn, int $productId): array {
$variants=[];
        $variantStatement = $conn->prepare("SELECT v.id, sz.name AS size_name, col.name AS color_name,
                v.stock, v.original_price,
                CASE WHEN p.discount_percent > 0
                    AND p.discount_start_at <= UTC_TIMESTAMP()
                    AND p.discount_end_at > UTC_TIMESTAMP()
                    THEN ROUND(v.original_price * (100 - p.discount_percent) / 100)
                    ELSE v.original_price END AS price,
                (SELECT pi.image_path FROM product_images pi
                 WHERE pi.product_id = p.id AND pi.color_id = col.id
                 ORDER BY pi.is_main DESC, pi.sort_order ASC LIMIT 1) AS image_path
            FROM product_variants v
            JOIN products p ON p.id = v.product_id
            JOIN sizes sz ON sz.id = v.size_id
            JOIN colors col ON col.id = v.color_id
            WHERE v.product_id = ? AND v.status = 'active'
            ORDER BY col.name, sz.sort_order");
        $variantStatement->bind_param('i', $productId);
        $variantStatement->execute();
        $variantResult = $variantStatement->get_result();
        while ($variant = $variantResult->fetch_assoc()) {
            $variants[] = $variant;
        }
        $variantStatement->close();
return $variants;
}
public static function images(mysqli $conn, int $productId): array
{
    $stmt = $conn->prepare('SELECT id, color_id, image_path, is_main FROM product_images
        WHERE product_id = ? ORDER BY is_main DESC, sort_order, id');
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

public static function cartCount(mysqli $conn, int $userId): int
{
    if ($userId <= 0) return 0;
    $stmt = $conn->prepare('SELECT COALESCE(SUM(ci.quantity), 0) AS quantity
        FROM carts c JOIN cart_items ci ON ci.cart_id = c.id WHERE c.user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $count = (int) $stmt->get_result()->fetch_assoc()['quantity'];
    $stmt->close();
    return $count;
}


    // TV3 đợt 1: nghiệp vụ quản trị đặt ngay trong model có sẵn.
    public function getAdminList(): array {return $this->adminRows('SELECT p.id,p.name,p.status,p.category_id,c.name category_name,p.published_at FROM products p JOIN categories c ON c.id=p.category_id ORDER BY p.id DESC');}

    private function productRow(int $id): ?array {return $this->adminOne('SELECT * FROM products WHERE id=?','i',[$id]);}

    private function categoryRow(int $id): ?array {return $this->adminOne('SELECT * FROM categories WHERE id=?','i',[$id]);}

    public function save(?int $id,int $category,string $name,string $description): int {
        $name=trim($name);if($name===''||preg_match('//u', $name)!==1||preg_match_all('/./us', $name)>200)throw new DomainException('Tên sản phẩm không hợp lệ');
        $cat=$this->categoryRow($category);if(!$cat||$cat['parent_id']===null||$cat['status']!=='active'||($this->categoryRow((int)$cat['parent_id'])['status']??'')!=='active')throw new DomainException('Chọn danh mục con và nhóm gốc đang hoạt động');
        if($id){if(!$this->productRow($id))throw new DomainException('Không tìm thấy sản phẩm');$this->adminExecute('UPDATE products SET category_id=?,name=?,description=? WHERE id=?','issi',[$category,$name,$description,$id]);return $id;}
        $this->adminExecute('INSERT INTO products(category_id,name,description,status) VALUES(?,?,?,\'inactive\')','iss',[$category,$name,$description]);return (int)$this->db->insert_id;
    }

    public function setStatus(int $product,bool $active): void {
        $this->db->begin_transaction();try {
            $p=$this->adminOne('SELECT * FROM products WHERE id=? FOR UPDATE','i',[$product]);if(!$p)throw new DomainException('Không tìm thấy sản phẩm');
            if($active){$cat=$this->adminOne('SELECT c.status,p.status parent_status FROM categories c JOIN categories p ON p.id=c.parent_id WHERE c.id=?','i',[(int)$p['category_id']]);
                if(!$cat||$cat['status']!=='active'||$cat['parent_status']!=='active'||!$this->adminOne('SELECT id FROM product_images WHERE product_id=? AND is_main=1','i',[$product])||!$this->adminOne("SELECT id FROM product_variants WHERE product_id=? AND status='active' LIMIT 1",'i',[$product]))throw new DomainException('Cần danh mục active, ảnh chính và biến thể active');
            }
            $this->adminExecute("UPDATE products SET status=?,published_at=CASE WHEN ?='active' AND published_at IS NULL THEN UTC_TIMESTAMP() ELSE published_at END WHERE id=?",'ssi',[$active?'active':'inactive',$active?'active':'inactive',$product]);$this->db->commit();
        }catch(Throwable $e){$this->db->rollback();throw $e;}
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

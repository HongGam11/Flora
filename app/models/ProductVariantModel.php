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

    // TV3 đợt 1: nghiệp vụ quản trị đặt ngay trong model có sẵn.
    private function productRow(int $id): ?array {return $this->adminOne('SELECT * FROM products WHERE id=?','i',[$id]);}

    public function getForAdmin(int $id): ?array {return $this->adminOne('SELECT * FROM product_variants WHERE id=?','i',[$id]);}

    public function sizes(): array {return $this->adminRows('SELECT id,name FROM sizes ORDER BY sort_order,id');}

    public function colors(): array {return $this->adminRows('SELECT id,name FROM colors ORDER BY id');}

    public function save(?int $id,int $product,int $size,int $color,int $price,string $status): void {
        if(!$this->productRow($product)||$price<=0||$price>999999999999||!in_array($status,['active','inactive'],true))throw new DomainException('Biến thể không hợp lệ');
        if(!$this->adminOne('SELECT id FROM sizes WHERE id=?','i',[$size])||!$this->adminOne('SELECT id FROM colors WHERE id=?','i',[$color]))throw new DomainException('Size hoặc màu không tồn tại');
        $this->db->begin_transaction();try {
            $this->adminOne('SELECT id FROM products WHERE id=? FOR UPDATE','i',[$product]);
            if($this->adminOne('SELECT id FROM product_variants WHERE product_id=? AND size_id=? AND color_id=? AND id<>? LIMIT 1','iiii',[$product,$size,$color,$id??0]))throw new DomainException('Tổ hợp size và màu đã tồn tại');
            if($id){$old=$this->getForAdmin($id);if(!$old||(int)$old['product_id']!==$product)throw new DomainException('Sai biến thể');
                if((int)$old['size_id']!==$size||(int)$old['color_id']!==$color){
                    if($this->adminOne('SELECT id FROM order_items WHERE variant_id=? LIMIT 1','i',[$id]))throw new DomainException('Biến thể đã bán không được đổi size/màu');
                    if((int)$old['color_id']!==$color && !$this->adminOne('SELECT id FROM product_variants WHERE product_id=? AND color_id=? AND id<>? LIMIT 1','iii',[$product,(int)$old['color_id'],$id]) && $this->adminOne('SELECT id FROM product_images WHERE product_id=? AND color_id=? LIMIT 1','ii',[$product,(int)$old['color_id']]))throw new DomainException('Màu cũ còn ảnh, cần xử lý ảnh trước');
                }
                $this->adminExecute('UPDATE product_variants SET size_id=?,color_id=?,original_price=?,status=? WHERE id=?','iiisi',[$size,$color,$price,$status,$id]);
            } else $this->adminExecute('INSERT INTO product_variants(product_id,size_id,color_id,original_price,stock,status) VALUES(?,?,?,?,0,?)','iiiis',[$product,$size,$color,$price,$status]);
            // Giá chỉ khác theo size, không khác theo màu; đồng bộ cả biến thể inactive.
            $this->adminExecute('UPDATE product_variants SET original_price=? WHERE product_id=? AND size_id=?','iii',[$price,$product,$size]);
            if($status==='inactive' && ($this->productRow($product)['status']??'')==='active'
                && !$this->adminOne("SELECT id FROM product_variants WHERE product_id=? AND status='active' LIMIT 1",'i',[$product])) {
                throw new DomainException('Không thể tắt biến thể bán cuối cùng. Hãy ngừng bán sản phẩm trước.');
            }
            $this->db->commit();
        }catch(Throwable $e){$this->db->rollback();throw $e;}
    }

    public function adjustStock(int $variant,int $delta): void {
        if($delta===0 || abs($delta)>1000000)throw new DomainException('Lượng thay đổi không hợp lệ');
        // Cập nhật tương đối, tránh lost update; không khóa ngược thứ tự product -> variant.
        $v=$this->getForAdmin($variant);if(!$v)throw new DomainException('Không tìm thấy biến thể');
        $this->db->begin_transaction();try {
            $this->adminOne('SELECT id FROM products WHERE id=? FOR UPDATE','i',[(int)$v['product_id']]);
            $n=$this->adminExecute('UPDATE product_variants SET stock=CAST(stock AS SIGNED)+? WHERE id=? AND CAST(stock AS SIGNED)+?>=0 AND CAST(stock AS SIGNED)+?<=4294967295','iiii',[$delta,$variant,$delta,$delta]);
            if($n!==1)throw new DomainException('Không đủ tồn hoặc vượt giới hạn');$this->db->commit();
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

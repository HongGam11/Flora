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

    // TV3 đợt 1: nghiệp vụ quản trị đặt ngay trong model có sẵn.
    public function add(int $product,?int $color,string $url,bool $main): void {
        $url=trim($url);$scheme=parse_url($url,PHP_URL_SCHEME);
        if(strlen($url)>500||!in_array($scheme,['https','http'],true)||!filter_var($url,FILTER_VALIDATE_URL))throw new DomainException('URL ảnh phải là http(s) hợp lệ');
        $this->db->begin_transaction();try {
            if(!$this->adminOne('SELECT id FROM products WHERE id=? FOR UPDATE','i',[$product]))throw new DomainException('Sản phẩm không tồn tại');
            if($color!==null&&!$this->adminOne('SELECT id FROM product_variants WHERE product_id=? AND color_id=? LIMIT 1','ii',[$product,$color]))throw new DomainException('Màu ảnh không thuộc sản phẩm');
            if($main)$this->adminExecute('UPDATE product_images SET is_main=0 WHERE product_id=? AND is_main=1','i',[$product]);
            $this->adminExecute('INSERT INTO product_images(product_id,color_id,image_path,is_main) VALUES(?,?,?,?)','iisi',[$product,$color,$url,(int)$main]);$this->db->commit();
        }catch(Throwable $e){$this->db->rollback();throw $e;}
    }

    public function setMain(int $product,int $image): void {
        $this->db->begin_transaction();try {
            if(!$this->adminOne('SELECT id FROM products WHERE id=? FOR UPDATE','i',[$product]))throw new DomainException('Sản phẩm không tồn tại');
            if(!$this->adminOne('SELECT id FROM product_images WHERE id=? AND product_id=?','ii',[$image,$product]))throw new DomainException('Ảnh không thuộc sản phẩm');
            $this->adminExecute('UPDATE product_images SET is_main=0 WHERE product_id=? AND is_main=1','i',[$product]);
            $this->adminExecute('UPDATE product_images SET is_main=1 WHERE id=?','i',[$image]);$this->db->commit();
        }catch(Throwable $e){$this->db->rollback();throw $e;}
    }

    public function update(int $product,int $image,?int $color,string $url,int $sort): void {
        $url=trim($url);
        if($sort<0||$sort>1000000||strlen($url)>500||!in_array(parse_url($url,PHP_URL_SCHEME),['http','https'],true)||!filter_var($url,FILTER_VALIDATE_URL))throw new DomainException('URL ảnh hoặc thứ tự không hợp lệ');
        $this->db->begin_transaction();try {
            if(!$this->adminOne('SELECT id FROM products WHERE id=? FOR UPDATE','i',[$product]))throw new DomainException('Không tìm thấy sản phẩm');
            if(!$this->adminOne('SELECT id FROM product_images WHERE id=? AND product_id=?','ii',[$image,$product]))throw new DomainException('Ảnh không thuộc sản phẩm');
            if($color!==null&&!$this->adminOne('SELECT id FROM product_variants WHERE product_id=? AND color_id=? LIMIT 1','ii',[$product,$color]))throw new DomainException('Màu ảnh không thuộc sản phẩm');
            $this->adminExecute('UPDATE product_images SET color_id=?,image_path=?,sort_order=? WHERE id=? AND product_id=?','isiii',[$color,$url,$sort,$image,$product]);
            $this->db->commit();
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

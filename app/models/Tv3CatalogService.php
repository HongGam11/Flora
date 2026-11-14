<?php
declare(strict_types=1);
/** TV3: thao tác quản trị; dùng cùng mysqli và không tự thay đổi schema. */
final class Tv3CatalogService {
    public function __construct(private mysqli $db) {}
    private function one(string $sql, string $types='', array $values=[]): ?array {
        $s=$this->db->prepare($sql); if($types) $s->bind_param($types,...$values);
        $s->execute(); return $s->get_result()->fetch_assoc() ?: null;
    }
    private function run(string $sql, string $types='', array $values=[]): int {
        $s=$this->db->prepare($sql); if($types) $s->bind_param($types,...$values);
        $s->execute(); return $s->affected_rows;
    }
    public function all(string $sql): array {return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);}
    public function categories(): array {return $this->all('SELECT c.*,p.name parent_name FROM categories c LEFT JOIN categories p ON p.id=c.parent_id ORDER BY COALESCE(c.parent_id,c.id),c.parent_id IS NOT NULL,c.id');}
    public function childCategories(): array {return $this->all("SELECT c.id,CONCAT(p.name,' / ',c.name) name FROM categories c JOIN categories p ON p.id=c.parent_id WHERE c.status='active' AND p.status='active' AND p.parent_id IS NULL ORDER BY p.id,c.id");}
    public function products(): array {return $this->all('SELECT p.id,p.name,p.status,p.category_id,c.name category_name,p.published_at FROM products p JOIN categories c ON c.id=p.category_id ORDER BY p.id DESC');}
    public function variants(int $id): array { $s=$this->db->prepare('SELECT v.*,s.name size_name,c.name color_name FROM product_variants v JOIN sizes s ON s.id=v.size_id JOIN colors c ON c.id=v.color_id WHERE v.product_id=? ORDER BY v.id');$s->bind_param('i',$id);$s->execute();return $s->get_result()->fetch_all(MYSQLI_ASSOC); }
    public function images(int $id): array {$s=$this->db->prepare('SELECT * FROM product_images WHERE product_id=? ORDER BY is_main DESC,sort_order,id');$s->bind_param('i',$id);$s->execute();return $s->get_result()->fetch_all(MYSQLI_ASSOC);}
    public function sizes(): array {return $this->all('SELECT id,name FROM sizes ORDER BY sort_order,id');}
    public function colors(): array {return $this->all('SELECT id,name FROM colors ORDER BY id');}
    public function category(int $id): ?array {return $this->one('SELECT * FROM categories WHERE id=?','i',[$id]);}
    public function product(int $id): ?array {return $this->one('SELECT * FROM products WHERE id=?','i',[$id]);}
    public function variant(int $id): ?array {return $this->one('SELECT * FROM product_variants WHERE id=?','i',[$id]);}
    public function saveCategory(?int $id,int $parent,string $name,string $status): void {
        $name=trim($name); if($name==='' || mb_strlen($name)>100 || !in_array($status,['active','inactive'],true))throw new DomainException('Danh mục không hợp lệ');
        $root=$this->category($parent);if(!$root || $root['parent_id']!==null)throw new DomainException('Chỉ chọn nhóm gốc');
        if($id){$old=$this->category($id);if(!$old || $old['parent_id']===null || (int)$old['parent_id']!==$parent)throw new DomainException('Không sửa nhóm gốc hoặc chuyển nhóm');$this->run('UPDATE categories SET name=?,status=? WHERE id=?','ssi',[$name,$status,$id]);}
        else $this->run('INSERT INTO categories(parent_id,name,status) VALUES(?,?,?)','iss',[$parent,$name,$status]);
    }
    public function saveProduct(?int $id,int $category,string $name,string $description): int {
        $name=trim($name);if($name===''||mb_strlen($name)>200)throw new DomainException('Tên sản phẩm không hợp lệ');
        $cat=$this->category($category);if(!$cat||$cat['parent_id']===null||$cat['status']!=='active'||($this->category((int)$cat['parent_id'])['status']??'')!=='active')throw new DomainException('Chọn danh mục con và nhóm gốc đang hoạt động');
        if($id){if(!$this->product($id))throw new DomainException('Không tìm thấy sản phẩm');$this->run('UPDATE products SET category_id=?,name=?,description=? WHERE id=?','issi',[$category,$name,$description,$id]);return $id;}
        $this->run('INSERT INTO products(category_id,name,description,status) VALUES(?,?,?,\'inactive\')','iss',[$category,$name,$description]);return (int)$this->db->insert_id;
    }
    public function saveVariant(?int $id,int $product,int $size,int $color,int $price,string $status): void {
        if(!$this->product($product)||$price<=0||!in_array($status,['active','inactive'],true))throw new DomainException('Biến thể không hợp lệ');
        if(!$this->one('SELECT id FROM sizes WHERE id=?','i',[$size])||!$this->one('SELECT id FROM colors WHERE id=?','i',[$color]))throw new DomainException('Size hoặc màu không tồn tại');
        $this->db->begin_transaction();try {
            $this->one('SELECT id FROM products WHERE id=? FOR UPDATE','i',[$product]);
            if($id){$old=$this->variant($id);if(!$old||(int)$old['product_id']!==$product)throw new DomainException('Sai biến thể');
                if((int)$old['size_id']!==$size||(int)$old['color_id']!==$color){
                    if($this->one('SELECT id FROM order_items WHERE variant_id=? LIMIT 1','i',[$id]))throw new DomainException('Biến thể đã bán không được đổi size/màu');
                    if((int)$old['color_id']!==$color && !$this->one('SELECT id FROM product_variants WHERE product_id=? AND color_id=? AND id<>? LIMIT 1','iii',[$product,(int)$old['color_id'],$id]) && $this->one('SELECT id FROM product_images WHERE product_id=? AND color_id=? LIMIT 1','ii',[$product,(int)$old['color_id']]))throw new DomainException('Màu cũ còn ảnh, cần xử lý ảnh trước');
                }
                $this->run('UPDATE product_variants SET size_id=?,color_id=?,original_price=?,status=? WHERE id=?','iiisi',[$size,$color,$price,$status,$id]);
            } else $this->run('INSERT INTO product_variants(product_id,size_id,color_id,original_price,stock,status) VALUES(?,?,?,?,0,?)','iiiis',[$product,$size,$color,$price,$status]);
            $this->db->commit();
        }catch(Throwable $e){$this->db->rollback();throw $e;}
    }
    public function adjustStock(int $variant,int $delta): void {
        if($delta===0 || abs($delta)>1000000)throw new DomainException('Lượng thay đổi không hợp lệ');
        // Cập nhật tương đối, tránh lost update; không khóa ngược thứ tự product -> variant.
        $v=$this->variant($variant);if(!$v)throw new DomainException('Không tìm thấy biến thể');
        $this->db->begin_transaction();try {
            $this->one('SELECT id FROM products WHERE id=? FOR UPDATE','i',[(int)$v['product_id']]);
            $n=$this->run('UPDATE product_variants SET stock=CAST(stock AS SIGNED)+? WHERE id=? AND CAST(stock AS SIGNED)+?>=0 AND CAST(stock AS SIGNED)+?<=4294967295','iiii',[$delta,$variant,$delta,$delta]);
            if($n!==1)throw new DomainException('Không đủ tồn hoặc vượt giới hạn');$this->db->commit();
        }catch(Throwable $e){$this->db->rollback();throw $e;}
    }
    public function addImage(int $product,?int $color,string $url,bool $main): void {
        $url=trim($url);$scheme=parse_url($url,PHP_URL_SCHEME);
        if(strlen($url)>500||!in_array($scheme,['https','http'],true)||!filter_var($url,FILTER_VALIDATE_URL))throw new DomainException('URL ảnh phải là http(s) hợp lệ');
        $this->db->begin_transaction();try {
            if(!$this->one('SELECT id FROM products WHERE id=? FOR UPDATE','i',[$product]))throw new DomainException('Sản phẩm không tồn tại');
            if($color!==null&&!$this->one('SELECT id FROM product_variants WHERE product_id=? AND color_id=? LIMIT 1','ii',[$product,$color]))throw new DomainException('Màu ảnh không thuộc sản phẩm');
            if($main)$this->run('UPDATE product_images SET is_main=0 WHERE product_id=? AND is_main=1','i',[$product]);
            $this->run('INSERT INTO product_images(product_id,color_id,image_path,is_main) VALUES(?,?,?,?)','iisi',[$product,$color,$url,(int)$main]);$this->db->commit();
        }catch(Throwable $e){$this->db->rollback();throw $e;}
    }
    public function setMainImage(int $product,int $image): void {
        $this->db->begin_transaction();try {
            if(!$this->one('SELECT id FROM products WHERE id=? FOR UPDATE','i',[$product]))throw new DomainException('Sản phẩm không tồn tại');
            if(!$this->one('SELECT id FROM product_images WHERE id=? AND product_id=?','ii',[$image,$product]))throw new DomainException('Ảnh không thuộc sản phẩm');
            $this->run('UPDATE product_images SET is_main=0 WHERE product_id=? AND is_main=1','i',[$product]);
            $this->run('UPDATE product_images SET is_main=1 WHERE id=?','i',[$image]);$this->db->commit();
        }catch(Throwable $e){$this->db->rollback();throw $e;}
    }
    /** C9: giảm giá theo giờ UTC; không thay đổi published_at. */
    public function savePromotion(int $product, string $percentInput, ?string $startInput, ?string $endInput, bool $featured): void {
        if (!preg_match('/^(?:100(?:\.0{1,2})?|\d{1,2}(?:\.\d{1,2})?)$/D', $percentInput)) throw new DomainException('Giảm giá phải từ 0 đến 100%, tối đa 2 chữ số thập phân');
        $percent=(float)$percentInput;
        $start=$this->utcDate($startInput); $end=$this->utcDate($endInput);
        if ($percent>0 && (!$start || !$end || $start >= $end)) throw new DomainException('Giảm giá cần thời gian bắt đầu trước kết thúc (UTC)');
        if ($percent===0.0) {$start=null;$end=null;}
        $this->db->begin_transaction();
        try {
            if (!$this->one('SELECT id FROM products WHERE id=? FOR UPDATE','i',[$product])) throw new DomainException('Không tìm thấy sản phẩm');
            $this->run('UPDATE products SET discount_percent=?,discount_start_at=?,discount_end_at=?,is_featured=? WHERE id=?','dssii',[$percent,$start,$end,(int)$featured,$product]);
            $this->db->commit();
        } catch(Throwable $e) {$this->db->rollback();throw $e;}
    }
    private function utcDate(?string $input): ?string {
        $input=trim((string)$input); if($input==='') return null;
        $dt=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$input,new DateTimeZone('UTC'));
        if (!$dt || $dt->format('Y-m-d\TH:i')!==$input) throw new DomainException('Thời gian UTC không hợp lệ');
        return $dt->format('Y-m-d H:i:s');
    }
    /** C10: sửa ảnh, màu, thứ tự; khóa sản phẩm để đồng bộ với chọn ảnh chính. */
    public function updateImage(int $product,int $image,?int $color,string $url,int $sort): void {
        $url=trim($url);
        if($sort<0||$sort>1000000||strlen($url)>500||!in_array(parse_url($url,PHP_URL_SCHEME),['http','https'],true)||!filter_var($url,FILTER_VALIDATE_URL))throw new DomainException('URL ảnh hoặc thứ tự không hợp lệ');
        $this->db->begin_transaction();try {
            if(!$this->one('SELECT id FROM products WHERE id=? FOR UPDATE','i',[$product]))throw new DomainException('Không tìm thấy sản phẩm');
            if(!$this->one('SELECT id FROM product_images WHERE id=? AND product_id=?','ii',[$image,$product]))throw new DomainException('Ảnh không thuộc sản phẩm');
            if($color!==null&&!$this->one('SELECT id FROM product_variants WHERE product_id=? AND color_id=? LIMIT 1','ii',[$product,$color]))throw new DomainException('Màu ảnh không thuộc sản phẩm');
            $this->run('UPDATE product_images SET color_id=?,image_path=?,sort_order=? WHERE id=? AND product_id=?','isiii',[$color,$url,$sort,$image,$product]);
            $this->db->commit();
        }catch(Throwable $e){$this->db->rollback();throw $e;}
    }
    /** Không xóa ảnh chính của sản phẩm đang mở bán. */
    public function deleteImage(int $product,int $image): void {
        $this->db->begin_transaction();try {
            $p=$this->one('SELECT id,status FROM products WHERE id=? FOR UPDATE','i',[$product]);
            $i=$this->one('SELECT id,is_main FROM product_images WHERE id=? AND product_id=?','ii',[$image,$product]);
            if(!$p||!$i)throw new DomainException('Không tìm thấy ảnh sản phẩm');
            if($p['status']==='active' && (int)$i['is_main']===1)throw new DomainException('Hãy ngừng bán hoặc chọn ảnh chính khác trước khi xóa');
            $this->run('DELETE FROM product_images WHERE id=? AND product_id=?','ii',[$image,$product]);
            $this->db->commit();
        }catch(Throwable $e){$this->db->rollback();throw $e;}
    }
    public function publish(int $product,bool $active): void {
        $this->db->begin_transaction();try {
            $p=$this->one('SELECT * FROM products WHERE id=? FOR UPDATE','i',[$product]);if(!$p)throw new DomainException('Không tìm thấy sản phẩm');
            if($active){$cat=$this->one('SELECT c.status,p.status parent_status FROM categories c JOIN categories p ON p.id=c.parent_id WHERE c.id=?','i',[(int)$p['category_id']]);
                if(!$cat||$cat['status']!=='active'||$cat['parent_status']!=='active'||!$this->one('SELECT id FROM product_images WHERE product_id=? AND is_main=1','i',[$product])||!$this->one("SELECT id FROM product_variants WHERE product_id=? AND status='active' LIMIT 1",'i',[$product]))throw new DomainException('Cần danh mục active, ảnh chính và biến thể active');
            }
            $this->run("UPDATE products SET status=?,published_at=CASE WHEN ?='active' AND published_at IS NULL THEN UTC_TIMESTAMP() ELSE published_at END WHERE id=?",'ssi',[$active?'active':'inactive',$active?'active':'inactive',$product]);$this->db->commit();
        }catch(Throwable $e){$this->db->rollback();throw $e;}
    }
}

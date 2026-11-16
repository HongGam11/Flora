<?php
final class AdminProductController
{
    private ProductModel $products;
    private CategoryModel $categories;
    private ProductVariantModel $variants;
    private ProductImageModel $images;

    public function __construct()
    {
        Auth::requireRole(['admin', 'staff']);
        require APP_ROOT . '/database.php';
        $this->products = new ProductModel($conn);
        $this->categories = new CategoryModel($conn);
        $this->variants = new ProductVariantModel($conn);
        $this->images = new ProductImageModel($conn);
    }

    public function index(): void { flora_catalog_page('products/index', ['rows'=>$this->products->getAdminList()]); }
    public function form(): void
    {
        $id = isset($_GET['id']) && $_GET['id'] !== '' ? flora_catalog_queryId('id') : null;
        $item = $id ? $this->products->findById($id) : null;
        if ($id && !$item) flora_catalog_notFound();
        flora_catalog_page('products/form', ['item'=>$item, 'categories'=>$this->categories->childCategories()]);
    }
    public function save(): void
    {
        flora_catalog_handle(function () {
            $id = $this->products->save(flora_catalog_optionalId($_POST['id'] ?? null), flora_catalog_id('category_id'), flora_catalog_text('name'), flora_catalog_text('description'));
            flora_catalog_success('Đã lưu sản phẩm. Sản phẩm mới cần ảnh chính và biến thể trước khi mở bán.');
            flora_catalog_redirect('admin_products_form', ['id'=>$id]);
        }, 'admin_products');
    }
    public function publish(): void
    {
        flora_catalog_handle(function () {
            $status = flora_catalog_text('status');
            if (!in_array($status, ['active','inactive'], true)) throw new DomainException('Trạng thái không hợp lệ.');
            $this->products->setStatus(flora_catalog_id('id'), $status === 'active');
            flora_catalog_success('Đã cập nhật trạng thái bán.');
            flora_catalog_redirect('admin_products');
        }, 'admin_products');
    }
    public function images(): void
    {
        $id = flora_catalog_queryId('id');
        $product = $this->products->findById($id);
        if (!$product) flora_catalog_notFound();
        flora_catalog_page('products/images', ['product'=>$product, 'images'=>$this->images->getByProductId($id), 'colors'=>$this->variants->colors()]);
    }
    public function addImage(): void
    {
        flora_catalog_handle(function () {
            $id = flora_catalog_id('product_id');
            $this->images->add($id, flora_catalog_optionalId($_POST['color_id'] ?? null), flora_catalog_text('image_path'), isset($_POST['is_main']));
            flora_catalog_success('Đã thêm ảnh sản phẩm.');
            flora_catalog_redirect('admin_products_images', ['id'=>$id]);
        }, 'admin_products');
    }
    public function updateImage(): void
    {
        flora_catalog_handle(function () {
            $id = flora_catalog_id('product_id');
            $this->images->update($id, flora_catalog_id('image_id'), flora_catalog_optionalId($_POST['color_id'] ?? null), flora_catalog_text('image_path'), flora_catalog_integer($_POST['sort_order'] ?? 0, 0, 1000000));
            flora_catalog_success('Đã lưu URL ảnh.');
            flora_catalog_redirect('admin_products_images', ['id'=>$id]);
        }, 'admin_products');
    }
    public function mainImage(): void
    {
        flora_catalog_handle(function () {
            $id = flora_catalog_id('product_id');
            $this->images->setMain($id, flora_catalog_id('image_id'));
            flora_catalog_success('Đã chọn ảnh chính.');
            flora_catalog_redirect('admin_products_images', ['id'=>$id]);
        }, 'admin_products');
    }
}

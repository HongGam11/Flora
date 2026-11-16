<?php
final class AdminVariantController
{
    private ProductModel $products;
    private ProductVariantModel $variants;

    public function __construct()
    {
        Auth::requireRole(['admin', 'staff']);
        require APP_ROOT . '/database.php';
        $this->products = new ProductModel($conn);
        $this->variants = new ProductVariantModel($conn);
    }

    public function index(): void
    {
        if (!isset($_GET['product_id']) || $_GET['product_id'] === '') {
            flora_catalog_page('variants/index', ['product'=>null, 'rows'=>[], 'products'=>$this->products->getAdminList()]);
            return;
        }
        $id = flora_catalog_queryId('product_id');
        $product = $this->products->findById($id);
        if (!$product) flora_catalog_notFound();
        flora_catalog_page('variants/index', ['product'=>$product, 'rows'=>$this->variants->getByProductId($id)]);
    }
    public function form(): void
    {
        $productId = flora_catalog_queryId('product_id');
        $product = $this->products->findById($productId);
        $id = isset($_GET['id']) && $_GET['id'] !== '' ? flora_catalog_queryId('id') : null;
        $item = $id ? $this->variants->getForAdmin($id) : null;
        if (!$product || ($id && (!$item || (int)$item['product_id'] !== $productId))) flora_catalog_notFound();
        flora_catalog_page('variants/form', ['product'=>$product, 'item'=>$item, 'sizes'=>$this->variants->sizes(), 'colors'=>$this->variants->colors()]);
    }
    public function save(): void
    {
        flora_catalog_handle(function () {
            $id = flora_catalog_id('product_id');
            $this->variants->save(flora_catalog_optionalId($_POST['id'] ?? null), $id, flora_catalog_id('size_id'), flora_catalog_id('color_id'), flora_catalog_integer($_POST['original_price'] ?? null, 1, 999999999999), flora_catalog_text('status'));
            flora_catalog_success('Đã lưu biến thể; giá cùng size được đồng bộ cho mọi màu.');
            flora_catalog_redirect('admin_variants', ['product_id'=>$id]);
        }, 'admin_variants');
    }
    public function stock(): void
    {
        flora_catalog_handle(function () {
            $productId = flora_catalog_id('product_id');
            $variantId = flora_catalog_id('variant_id');
            $variant = $this->variants->getForAdmin($variantId);
            if (!$variant || (int)$variant['product_id'] !== $productId) throw new DomainException('Biến thể không thuộc sản phẩm.');
            $this->variants->adjustStock($variantId, flora_catalog_integer($_POST['delta'] ?? null, -1000000, 1000000));
            flora_catalog_success('Đã điều chỉnh tồn kho.');
            flora_catalog_redirect('admin_variants', ['product_id'=>$productId]);
        }, 'admin_variants');
    }
}

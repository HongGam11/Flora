<?php
final class AdminCategoryController
{
    private CategoryModel $categories;

    public function __construct()
    {
        Auth::requireRole(['admin', 'staff']);
        require APP_ROOT . '/database.php';
        $this->categories = new CategoryModel($conn);
    }

    public function index(): void { flora_catalog_page('categories/index', ['rows'=>$this->categories->getAdminList()]); }
    public function form(): void
    {
        $id = isset($_GET['id']) && $_GET['id'] !== '' ? flora_catalog_queryId('id') : null;
        $item = $id ? $this->categories->findById($id) : null;
        if ($id && (!$item || $item['parent_id'] === null)) flora_catalog_notFound();
        $roots = array_filter($this->categories->getAdminList(), static fn($c) => $c['parent_id'] === null);
        flora_catalog_page('categories/form', compact('item', 'roots'));
    }
    public function save(): void
    {
        flora_catalog_handle(function () {
            $this->categories->save(flora_catalog_optionalId($_POST['id'] ?? null), flora_catalog_id('parent_id'), flora_catalog_text('name'), flora_catalog_text('status'));
            flora_catalog_success('Đã lưu danh mục.');
            flora_catalog_redirect('admin_categories');
        }, 'admin_categories');
    }
}

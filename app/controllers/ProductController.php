<?php
final class ProductController {
 public function home(): void { $this->render('home'); }
 public function index(): void { $this->render('shop'); }
 public function show(): void { $this->render('detail'); }
 private function render(string $mode): void {
 require APP_ROOT.'/database.php';
$category = isset($_GET['category']) && is_scalar($_GET['category'])
    && in_array($_GET['category'], ['nam', 'nu'], true) ? (string) $_GET['category'] : '';
$search = isset($_GET['q']) && is_scalar($_GET['q']) ? trim((string) $_GET['q']) : '';
$search = function_exists('mb_substr') ? mb_substr($search, 0, 100, 'UTF-8') : substr($search, 0, 300);
$sort = isset($_GET['sort']) && is_scalar($_GET['sort']) ? (string) $_GET['sort'] : 'new';
if (!in_array($sort, ['new', 'price_asc', 'price_desc'], true)) {
    $sort = 'new';
}
$minPrice = isset($_GET['min_price']) && is_scalar($_GET['min_price']) && ctype_digit((string) $_GET['min_price'])
    ? (int) $_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) && is_scalar($_GET['max_price']) && ctype_digit((string) $_GET['max_price'])
    ? (int) $_GET['max_price'] : null;
$page = isset($_GET['page']) && is_scalar($_GET['page']) && ctype_digit((string) $_GET['page'])
    ? max(1, (int) $_GET['page']) : 1;
$page = min($page, 100000);
$perPage = 8;
$productId = isset($_GET['id']) && is_scalar($_GET['id']) && ctype_digit((string) $_GET['id'])
    ? (int) $_GET['id'] : null;
$view = $mode === 'home' ? 'home' : 'shop';
$currentUser = Auth::user();
$cartCount = ProductModel::cartCount($conn, (int) ($currentUser['id'] ?? 0));
$categoryId = isset($_GET['category_id']) && is_scalar($_GET['category_id']) && ctype_digit((string)$_GET['category_id']) ? (int)$_GET['category_id'] : null;
$categories = CategoryModel::all($conn);
$detail = null;
$variants = [];
$images = [];
if ($productId !== null) {
    $detailResult = ProductModel::search($conn, ['id' => $productId], 'new', 1);
    $detail = $detailResult['products'][0] ?? null;
    if ($detail !== null) {
        $variants = ProductModel::variants($conn, $productId);
        $images = ProductModel::images($conn, $productId);
    }
}

$filterValues = [
    'category_id' => $categoryId,
    'category' => $category,
    'search' => $search,
    'min_price' => $minPrice,
    'max_price' => $maxPrice,
];
$featured = ProductModel::search($conn, ['featured' => true], 'new', 4)['products'];
$newest = ProductModel::search($conn, [], 'new', 4)['products'];
$catalog = ProductModel::search($conn, $filterValues, $sort, $perPage, ($page - 1) * $perPage);
$pageCount = max(1, (int) ceil($catalog['total'] / $perPage));
if ($page > $pageCount) {
    $page = $pageCount;
    $catalog = ProductModel::search($conn, $filterValues, $sort, $perPage, ($page - 1) * $perPage);
}

$queryParams = array_filter([
    'route' => 'products',
    'category_id' => $categoryId,
    'category' => $category,
    'q' => $search,
    'min_price' => $minPrice,
    'max_price' => $maxPrice,
    'sort' => $sort !== 'new' ? $sort : null,
], static function ($value): bool {
    return $value !== null && $value !== '';
});

if ($mode === 'detail' && $detail === null) { http_response_code(404); require APP_ROOT.'/app/views/errors/404.php'; return; }
$title = $detail !== null ? $detail['name'] . ' | Flora'
    : ($mode === 'home' ? 'Flora | Thời trang nam nữ' : 'Cửa hàng | Flora');
require APP_ROOT.'/app/views/storefront/page.php';

}
}

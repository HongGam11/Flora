<?php
require_once __DIR__ . '/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function flora_e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function flora_bind(mysqli_stmt $statement, string $types, array $values): void
{
    if ($values === []) {
        return;
    }

    $arguments = [$types];
    foreach ($values as $key => &$value) {
        $arguments[] = &$value;
    }
    call_user_func_array([$statement, 'bind_param'], $arguments);
}

function flora_product_data(mysqli $conn, array $filters, string $sort, ?int $limit = null, int $offset = 0): array
{
    $where = ["p.status = 'active'", "c.status = 'active'", "root.status = 'active'", "v.status = 'active'"];
    $types = '';
    $values = [];

    if (!empty($filters['category'])) {
        $where[] = 'root.name = ?';
        $types .= 's';
        $values[] = $filters['category'] === 'nam' ? 'Thời trang nam' : 'Thời trang nữ';
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

function flora_money($value): string
{
    return number_format((float) $value, 0, ',', '.') . '₫';
}

function flora_render_card(array $product): void
{
    $isNew = !empty($product['published_at'])
        && strtotime($product['published_at']) >= time() - 7 * 86400;
    $hasDiscount = (float) $product['price'] < (float) $product['original_price'];
    $image = $product['main_image'] ?: 'assets/images/no-image.svg';
    ?>
    <article class="card product-card">
        <a class="product-link" href="index.php?product=<?= (int) $product['id'] ?>">
            <div class="ph">
                <img src="<?= flora_e($image) ?>" alt="<?= flora_e($product['name']) ?>" loading="lazy">
                <?php if ((int) $product['is_featured'] === 1): ?>
                    <span class="tag">Nổi bật</span>
                <?php elseif ($isNew): ?>
                    <span class="tag">Mới</span>
                <?php elseif ($hasDiscount): ?>
                    <span class="tag">Ưu đãi</span>
                <?php endif; ?>
            </div>
            <div class="nm"><?= flora_e($product['name']) ?></div>
            <div class="pr">
                <?= flora_money($product['price']) ?>
                <?php if ($hasDiscount): ?>
                    <del class="old-price"><?= flora_money($product['original_price']) ?></del>
                <?php endif; ?>
            </div>
            <div class="product-category"><?= flora_e($product['root_name']) ?> · <?= flora_e($product['category_name']) ?></div>
        </a>
    </article>
    <?php
}

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
$perPage = 8;
$productId = isset($_GET['product']) && is_scalar($_GET['product']) && ctype_digit((string) $_GET['product'])
    ? (int) $_GET['product'] : null;
$view = isset($_GET['view']) && is_scalar($_GET['view']) && $_GET['view'] === 'shop'
    || $category !== '' || $search !== '' || $minPrice !== null || $maxPrice !== null || $page > 1
    ? 'shop' : 'home';

$cartCount = 0;
if (isset($_SESSION['user_id'])) {
    $cartStatement = $conn->prepare('SELECT COALESCE(SUM(ci.quantity), 0) AS total
        FROM cart_items ci JOIN carts ca ON ca.id = ci.cart_id WHERE ca.user_id = ?');
    $cartStatement->bind_param('i', $_SESSION['user_id']);
    $cartStatement->execute();
    $cartCount = (int) $cartStatement->get_result()->fetch_assoc()['total'];
    $cartStatement->close();
}

$detail = null;
$variants = [];
if ($productId !== null) {
    $detailResult = flora_product_data($conn, ['id' => $productId], 'new', 1);
    $detail = $detailResult['products'][0] ?? null;
    if ($detail !== null) {
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
    }
}

$filterValues = [
    'category' => $category,
    'search' => $search,
    'min_price' => $minPrice,
    'max_price' => $maxPrice,
];
$featured = flora_product_data($conn, ['featured' => true], 'new', 4)['products'];
$newest = flora_product_data($conn, [], 'new', 4)['products'];
$catalog = flora_product_data($conn, $filterValues, $sort, $perPage, ($page - 1) * $perPage);
$pageCount = max(1, (int) ceil($catalog['total'] / $perPage));
if ($page > $pageCount) {
    $page = $pageCount;
    $catalog = flora_product_data($conn, $filterValues, $sort, $perPage, ($page - 1) * $perPage);
}

$queryParams = array_filter([
    'view' => $view,
    'category' => $category,
    'q' => $search,
    'min_price' => $minPrice,
    'max_price' => $maxPrice,
    'sort' => $sort !== 'new' ? $sort : null,
], static function ($value): bool {
    return $value !== null && $value !== '';
});
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= $detail ? flora_e($detail['name']) . ' | Flora' : 'Flora | Thời trang nam nữ' ?></title>
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/customer.css?v=<?= (int) filemtime(__DIR__ . '/assets/css/customer.css') ?>">
    <link rel="stylesheet" href="assets/css/storefront.css?v=<?= (int) filemtime(__DIR__ . '/assets/css/storefront.css') ?>">
</head>
<body>
    <div class="topbar"><div class="c">
        <span>Giao hàng toàn quốc</span><span>Hỗ trợ khách hàng</span>
        <?php if (isset($_SESSION['full_name'])): ?>
            <span>Xin chào, <?= flora_e($_SESSION['full_name']) ?></span>
        <?php endif; ?>
    </div></div>
    <header>
        <div class="c">
            <a class="logo" href="index.php#home">Flora</a>
            <nav aria-label="Điều hướng chính">
                <a href="index.php#home">Trang chủ</a>
                <a href="index.php?view=shop#products">Tất cả</a>
                <a href="index.php?view=shop&amp;category=nam#products">Nam</a>
                <a href="index.php?view=shop&amp;category=nu#products">Nữ</a>
            </nav>
            <div class="sp">
                <form class="search-form" action="index.php" method="get" role="search">
                    <input type="hidden" name="view" value="shop">
                    <input class="fc" name="q" value="<?= flora_e($search) ?>" placeholder="Tìm sản phẩm" aria-label="Tìm sản phẩm">
                    <button class="search-submit" type="submit" aria-label="Tìm kiếm">
                        <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24">
                            <circle cx="10.8" cy="10.8" r="6.8"></circle>
                            <path d="m16 16 4.5 4.5"></path>
                        </svg>
                    </button>
                </form>
                <a class="cart-link" href="index.php?view=shop#products" aria-label="Giỏ hàng">
                    <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24">
                        <path d="M3 4h2l2.1 10.1a2 2 0 0 0 2 1.6h8.7a2 2 0 0 0 1.9-1.5L21 8H6"></path>
                        <circle cx="10" cy="19" r="1"></circle>
                        <circle cx="18" cy="19" r="1"></circle>
                    </svg>
                    <span>Giỏ</span><span class="cnt" id="cart-count"><?= $cartCount ?></span>
                </a>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <button class="login-button" type="button" disabled title="Chức năng đăng nhập chưa được triển khai">
                        <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24">
                            <circle cx="12" cy="8" r="3.5"></circle>
                            <path d="M5 20a7 7 0 0 1 14 0"></path>
                        </svg>
                        <span>Đăng nhập</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main>
        <?php if ($detail !== null): ?>
            <section id="product-detail">
                <div class="c">
                    <a href="index.php?view=shop#products">← Quay lại danh sách</a>
                    <div class="det">
                        <div class="ph detail-image">
                            <img id="detail-image" src="<?= flora_e($detail['main_image'] ?: 'assets/images/no-image.svg') ?>"
                                alt="<?= flora_e($detail['name']) ?>">
                        </div>
                        <div class="detail-copy">
                            <p class="product-category"><?= flora_e($detail['root_name']) ?> · <?= flora_e($detail['category_name']) ?></p>
                            <h1><?= flora_e($detail['name']) ?></h1>
                            <div class="pr detail-price" id="variant-price"><?= flora_money($detail['price']) ?></div>
                            <?php if (!empty($detail['description'])): ?>
                                <p><?= nl2br(flora_e($detail['description'])) ?></p>
                            <?php endif; ?>
                            <?php if ($variants): ?>
                                <label class="variant-label" for="variant">Màu và kích thước</label>
                                <select class="fc variant-select" id="variant">
                                    <option value="">Chọn màu và kích thước</option>
                                    <?php foreach ($variants as $variant): ?>
                                        <option value="<?= (int) $variant['id'] ?>"
                                            data-stock="<?= (int) $variant['stock'] ?>"
                                            data-price="<?= (int) $variant['price'] ?>"
                                            data-original-price="<?= (int) $variant['original_price'] ?>"
                                            data-image="<?= flora_e($variant['image_path'] ?: $detail['main_image']) ?>">
                                            <?= flora_e($variant['color_name']) ?> · <?= flora_e($variant['size_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="note" id="variant-stock" aria-live="polite">Chọn màu và kích thước để xem tồn kho.</p>
                            <?php else: ?>
                                <p class="note out">Sản phẩm hiện chưa có biến thể đang bán.</p>
                            <?php endif; ?>
                            <div class="product-actions detail-actions" role="group" aria-label="Thao tác sản phẩm">
                                <button class="product-action-btn product-action-cart" type="button">
                                    <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24">
                                        <path d="M3 4h2l2.1 10.1a2 2 0 0 0 2 1.6h8.7a2 2 0 0 0 1.9-1.5L21 8H6"></path>
                                        <circle cx="10" cy="19" r="1"></circle>
                                        <circle cx="18" cy="19" r="1"></circle>
                                    </svg>
                                    <span>Thêm vào giỏ</span>
                                </button>
                                <button class="product-action-btn product-action-buy" type="button">Mua ngay</button>
                            </div>
                            <a class="btn" href="index.php?view=shop#products">Khám phá thêm sản phẩm</a>
                        </div>
                    </div>
                </div>
            </section>
        <?php elseif ($productId !== null): ?>
            <section><div class="c empty"><h1>Không tìm thấy sản phẩm</h1><a class="btn" href="index.php?view=shop#products">Quay lại cửa hàng</a></div></section>
        <?php elseif ($view !== 'shop'): ?>
            <section class="hero" id="home">
                <div class="c hero-layout">
                    <div class="hero-copy">
                        <p class="eyebrow">FLORA · BỘ SƯU TẬP MỚI</p>
                        <h1>Một mùa hoa<br>trăm cách phối</h1>
                        <p>Những thiết kế tinh giản cho phong cách riêng của bạn thanh lịch mỗi ngày, tự tin trong mọi khoảnh khắc</p>
                        <a class="btn pri" href="index.php?view=shop#products">
                            <span>Xem tất cả sản phẩm</span>
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>
                    <div class="hero-visual hero-visual-single">
                        <div class="hero-image hero-image-primary">
                            <img src="assets/images/flora-hero-model.png"
                                alt="Người mẫu trong áo choàng đen trắng và quần ống rộng màu đỏ rượu" fetchpriority="high">
                        </div>
                    </div>
                </div>
            </section>

            <section class="reveal-on-scroll">
                <div class="c">
                    <div class="hd"><h2>Sản phẩm nổi bật</h2><a href="index.php?view=shop#products">Xem tất cả →</a></div>
                    <?php if ($featured): ?>
                        <div class="grid"><?php foreach ($featured as $product) { flora_render_card($product); } ?></div>
                    <?php else: ?>
                        <p class="empty">Chưa có sản phẩm nổi bật.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="reveal-on-scroll">
                <div class="c">
                    <div class="hd"><h2>Sản phẩm mới</h2><a href="index.php?view=shop#products">Khám phá cửa hàng -></a></div>
                    <?php if ($newest): ?>
                        <div class="grid"><?php foreach ($newest as $product) { flora_render_card($product); } ?></div>
                    <?php else: ?>
                        <p class="empty">Chưa có sản phẩm mới</p>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($detail === null && $view === 'shop'): ?>
            <section class="reveal-on-scroll" id="products">
                <div class="c">
                    <div class="hd">
                        <h2><?= $search !== '' ? 'Kết quả tìm kiếm' : 'Tất cả sản phẩm' ?></h2>
                        <span class="result-count"><?= $catalog['total'] ?> sản phẩm</span>
                    </div>
                    <?php if ($search !== ''): ?>
                        <p class="search-summary">Từ khóa: “<?= flora_e($search) ?>”</p>
                    <?php endif; ?>
                    <div class="pills">
                        <a class="<?= $category === '' ? 'on' : '' ?>" href="index.php?view=shop#products">Tất cả</a>
                        <a class="<?= $category === 'nam' ? 'on' : '' ?>" href="index.php?view=shop&amp;category=nam#products">Nam</a>
                        <a class="<?= $category === 'nu' ? 'on' : '' ?>" href="index.php?view=shop&amp;category=nu#products">Nữ</a>
                    </div>
                    <form class="bar" method="get" action="index.php#products">
                        <input type="hidden" name="view" value="shop">
                        <?php if ($category !== ''): ?><input type="hidden" name="category" value="<?= flora_e($category) ?>"><?php endif; ?>
                        <?php if ($search !== ''): ?><input type="hidden" name="q" value="<?= flora_e($search) ?>"><?php endif; ?>
                        <div><label for="min-price">Giá từ</label><input class="fc" id="min-price" name="min_price" type="number" min="0" step="10000" value="<?= $minPrice === null ? '' : $minPrice ?>"></div>
                        <div><label for="max-price">Đến</label><input class="fc" id="max-price" name="max_price" type="number" min="0" step="10000" value="<?= $maxPrice === null ? '' : $maxPrice ?>"></div>
                        <div><label for="sort">Sắp xếp</label>
                            <select class="fc" id="sort" name="sort">
                                <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Mới nhất</option>
                                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
                                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
                            </select>
                        </div>
                        <button class="btn" type="submit">Lọc sản phẩm</button>
                        <a class="btn reset-link" href="index.php?view=shop#products">Xóa bộ lọc</a>
                    </form>
                    <?php if ($catalog['products']): ?>
                        <div class="grid">
                            <?php foreach ($catalog['products'] as $product) { flora_render_card($product); } ?>
                        </div>
                        <?php if ($pageCount > 1): ?>
                            <nav class="pg" aria-label="Phân trang sản phẩm">
                                <?php for ($number = 1; $number <= $pageCount; $number++): ?>
                                    <?php $pageUrl = 'index.php?' . http_build_query($queryParams + ['page' => $number]) . '#products'; ?>
                                    <a class="btn <?= $number === $page ? 'on' : '' ?>" href="<?= flora_e($pageUrl) ?>" <?= $number === $page ? 'aria-current="page"' : '' ?>><?= $number ?></a>
                                <?php endfor; ?>
                            </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty"><h3>Không tìm thấy sản phẩm</h3><p>Thử từ khóa khác hoặc xóa bộ lọc giá.</p></div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <footer><div class="c">Flora · Thời trang nam nữ</div></footer>
    <script src="assets/js/storefront.js?v=<?= (int) filemtime(__DIR__ . '/assets/js/storefront.js') ?>" defer></script>
</body>
</html>

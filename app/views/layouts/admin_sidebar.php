<?php
$adminCurrentRoute = isset($_GET['route']) && is_string($_GET['route'])
    ? $_GET['route']
    : '';

$adminMenuItems = [
    'admin_dashboard'  => 'Tổng quan',
    'admin_categories' => 'Danh mục',
    'admin_products'   => 'Sản phẩm',
    'admin_variants'   => 'Biến thể và kho',
    'admin_orders'     => 'Đơn hàng',
    'admin_coupons'    => 'Mã giảm giá',
    'admin_users'      => 'Tài khoản',
    'admin_reports'    => 'Báo cáo',
];
?>

<aside class="admin-sidebar">
    <nav aria-label="Menu quản trị">
        <?php foreach ($adminMenuItems as $menuRoute => $menuLabel): ?>
            <?php
            $menuActive = $adminCurrentRoute === $menuRoute
                || str_starts_with(
                    $adminCurrentRoute,
                    $menuRoute . '_'
                );
            ?>

            <a
                class="admin-menu-link<?= $menuActive ? ' is-active' : '' ?>"
                href="/flora/index.php?route=<?= e($menuRoute) ?>"
                <?= $menuActive ? 'aria-current="page"' : '' ?>
            >
                <?= e($menuLabel) ?>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>

<main class="admin-content">
    <?php require APP_ROOT . '/app/views/layouts/messages.php'; ?>
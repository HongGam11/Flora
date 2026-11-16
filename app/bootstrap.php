<?php
declare(strict_types=1);
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/app/core/Csrf.php';
require_once APP_ROOT . '/app/controllers/CartController.php';
require_once APP_ROOT . '/app/controllers/admin/AdminCouponController.php';
require_once APP_ROOT . '/app/controllers/HomeController.php';
require_once APP_ROOT . '/app/controllers/ProductController.php';
require_once APP_ROOT . '/app/controllers/AuthController.php';
require_once APP_ROOT . '/app/core/Auth.php';
require_once APP_ROOT . '/app/controllers/ProfileController.php';
require_once APP_ROOT . '/app/controllers/admin/AdminUserController.php';
require_once APP_ROOT . '/app/controllers/admin/AdminDashboardController.php';
require_once APP_ROOT . '/app/controllers/admin/AdminCategoryController.php';
require_once APP_ROOT . '/app/controllers/admin/AdminProductController.php';
require_once APP_ROOT . '/app/controllers/admin/AdminVariantController.php';
date_default_timezone_set('UTC');
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params(['httponly'=>true, 'samesite'=>'Lax',
        'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'path'=>'/']);
    session_start();
}
require_once __DIR__.'/core/helpers.php';
require_once __DIR__.'/core/storefront_helpers.php';
require_once __DIR__.'/models/ProductModel.php';
require_once __DIR__.'/models/CategoryModel.php';
require_once __DIR__.'/models/ProductImageModel.php';
require_once __DIR__.'/models/ProductVariantModel.php';
require_once __DIR__.'/controllers/HomeController.php';
require_once __DIR__.'/controllers/ProductController.php';
require_once __DIR__.'/controllers/CheckoutController.php';
require_once __DIR__.'/controllers/OrderController.php';
require_once __DIR__.'/controllers/admin/AdminOrderController.php';
require_once __DIR__.'/core/Router.php';



<?php

final class Router
{
    public function dispatch(string $route): void
    {
        // Lấy phương thức HTTP hiện tại của request (GET hoặc POST)
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Xử lý các trang lỗi đặc biệt
        if ($route === '403') {
            http_response_code(403);
            require APP_ROOT . '/app/views/errors/403.php';
            return;
        }

        // Danh sách định nghĩa URL toàn cục của dự án Flora
        // Cấu trúc: 'tên_route' => [ 'method' => 'GET|POST', 'handler' => [Controller, action], 'status' => 'ready|pending' ]
        $routes = [
            // --- 1. TRANG CHỦ & SẢN PHẨM ---
            'home'                  => ['method' => 'GET',  'handler' => [HomeController::class, 'index'], 'status' => 'ready'],
            'products'              => ['method' => 'GET',  'handler' => [ProductController::class, 'index'], 'status' => 'ready'],
            'product-detail' => ['method' => 'GET', 'handler' => [ProductController::class, 'show'], 'status' => 'ready'],

            // --- 2. XÁC THỰC & TÀI KHOẢN---
            'login' => ['method' => 'GET|POST', 'handler' => [AuthController::class, 'login'], 'status' => 'ready'],
            'register' => ['method' => 'GET|POST', 'handler' => [AuthController::class, 'register'], 'status' => 'ready'],
            'logout' => ['method' => 'POST', 'handler' => [AuthController::class, 'logout'], 'status' => 'ready'],
            'profile'               => ['method' => 'GET|POST', 'handler' => [ProfileController::class, 'index'], 'status' => 'ready'],
            'change_password'       => ['method' => 'GET|POST', 'handler' => [ProfileController::class, 'changePassword'], 'status' => 'ready'],

            // --- 3. GIỎ HÀNG & MUA HÀNG COD  ---
            // GIỎ HÀNG 
            'cart' => [
                'method' => 'GET',
                'handler' => [CartController::class, 'index'],
                'status' => 'ready',
            ],
            'cart_add' => [
                'method' => 'POST',
                'handler' => [CartController::class, 'add'],
                'status' => 'ready',
            ],
            'cart_update' => [
                'method' => 'POST',
                'handler' => [CartController::class, 'update'],
                'status' => 'ready',
            ],
            'cart_remove' => [
                'method' => 'POST',
                'handler' => [CartController::class, 'remove'],
                'status' => 'ready',
            ],
            'cart_apply_coupon' => [
                'method' => 'POST',
                'handler' => [CartController::class, 'applyCoupon'],
                'status' => 'ready',
            ],
            'cart_remove_coupon' => [
                'method' => 'POST',
                'handler' => [CartController::class, 'removeCoupon'],
                'status' => 'ready',
            ],
            'checkout'              => ['method' => 'GET|POST', 'handler' => [CheckoutController::class, 'index'], 'status' => 'ready'],
            'checkout_success'      => ['method' => 'GET',      'handler' => [CheckoutController::class, 'success'], 'status' => 'ready'],
            // QUẢN LÝ MÃ GIẢM GIÁ — TV4
            'admin_coupons' => [
                'method' => 'GET',
                'handler' => [AdminCouponController::class, 'index'],
                'status' => 'ready',
            ],
            'admin_coupons_create' => [
                'method' => 'GET',
                'handler' => [AdminCouponController::class, 'create'],
                'status' => 'ready',
            ],
            'admin_coupons_store' => [
                'method' => 'POST',
                'handler' => [AdminCouponController::class, 'store'],
                'status' => 'ready',
            ],
            'admin_coupons_edit' => [
                'method' => 'GET',
                'handler' => [AdminCouponController::class, 'edit'],
                'status' => 'ready',
            ],
            'admin_coupons_update' => [
                'method' => 'POST',
                'handler' => [AdminCouponController::class, 'update'],
                'status' => 'ready',
            ],
            'admin_coupons_toggle' => [
                'method' => 'POST',
                'handler' => [AdminCouponController::class, 'toggleStatus'],
                'status' => 'ready',
            ],
            'admin_coupons_delete' => [
                'method' => 'POST',
                'handler' => [AdminCouponController::class, 'delete'],
                'status' => 'ready',
            ],
            // --- 4. ĐƠN HÀNG & THANH TOÁN ---
            'orders'                => ['method' => 'GET',      'handler' => [OrderController::class, 'index'], 'status' => 'ready'],
            'order_detail'          => ['method' => 'GET',      'handler' => [OrderController::class, 'detail'], 'status' => 'ready'],
            'payment_simulate'      => ['method' => 'GET|POST', 'handler' => [PaymentController::class, 'simulate'], 'status' => 'ready'],

            // --- 5. QUẢN TRỊ ADMIN / STAFF  ---
            'admin_dashboard'       => ['method' => 'GET',      'handler' => [AdminDashboardController::class, 'index'], 'status' => 'ready'],
            'admin_users'           => ['method' => 'GET|POST', 'handler' => [AdminUserController::class, 'index'], 'status' => 'ready'],
            
            // TV3 — đợt 1: chỉ dùng route của bản chung.
            'admin_categories' => ['method' => 'GET', 'handler' => [AdminCategoryController::class, 'index'], 'status' => 'ready'],
            'admin_categories_form' => ['method' => 'GET', 'handler' => [AdminCategoryController::class, 'form'], 'status' => 'ready'],
            'admin_categories_save' => ['method' => 'POST', 'handler' => [AdminCategoryController::class, 'save'], 'status' => 'ready'],
            'admin_products' => ['method' => 'GET', 'handler' => [AdminProductController::class, 'index'], 'status' => 'ready'],
            'admin_products_form' => ['method' => 'GET', 'handler' => [AdminProductController::class, 'form'], 'status' => 'ready'],
            'admin_products_save' => ['method' => 'POST', 'handler' => [AdminProductController::class, 'save'], 'status' => 'ready'],
            'admin_products_publish' => ['method' => 'POST', 'handler' => [AdminProductController::class, 'publish'], 'status' => 'ready'],
            'admin_products_images' => ['method' => 'GET', 'handler' => [AdminProductController::class, 'images'], 'status' => 'ready'],
            'admin_products_images_add' => ['method' => 'POST', 'handler' => [AdminProductController::class, 'addImage'], 'status' => 'ready'],
            'admin_products_images_update' => ['method' => 'POST', 'handler' => [AdminProductController::class, 'updateImage'], 'status' => 'ready'],
            'admin_products_images_main' => ['method' => 'POST', 'handler' => [AdminProductController::class, 'mainImage'], 'status' => 'ready'],
            'admin_variants' => ['method' => 'GET', 'handler' => [AdminVariantController::class, 'index'], 'status' => 'ready'],
            'admin_variants_form' => ['method' => 'GET', 'handler' => [AdminVariantController::class, 'form'], 'status' => 'ready'],
            'admin_variants_save' => ['method' => 'POST', 'handler' => [AdminVariantController::class, 'save'], 'status' => 'ready'],
            'admin_variants_stock' => ['method' => 'POST', 'handler' => [AdminVariantController::class, 'stock'], 'status' => 'ready'],

            // Các route đăng ký giữ chỗ nhưng chưa viết code chi tiết (đánh dấu chờ triển khai)

            'admin_orders' => ['method'=>'GET','handler'=>[AdminOrderController::class,'index'],'status'=>'ready'],
            'admin_orders_detail' => ['method'=>'GET','handler'=>[AdminOrderController::class,'detail'],'status'=>'ready'],
            'admin_orders_update' => ['method'=>'POST','handler'=>[AdminOrderController::class,'updateStatus'],'status'=>'ready'],
            'order_cancel' => ['method'=>'POST','handler'=>[OrderController::class,'cancel'],'status'=>'ready'],
            'order_update_recipient' => ['method'=>'POST','handler'=>[OrderController::class,'updateRecipient'],'status'=>'ready'],
        ];

        // Kiểm tra 1: URL có tồn tại trong hệ thống không?
        if (!isset($routes[$route])) {
            http_response_code(404);
            require APP_ROOT . '/app/views/errors/404.php';
            return;
        }

        $routeInfo = $routes[$route];

        // Kiểm tra 3: Phương thức HTTP có khớp không? (Hỗ trợ cấu hình nhiều method như 'GET|POST')
        $allowedMethods = explode('|', $routeInfo['method']);
        if (!in_array($requestMethod, $allowedMethods)) {
            http_response_code(405); // Method Not Allowed chuẩn HTTP
            echo "<div style='font-family:sans-serif; text-align:center; margin-top:50px;'>
                    <h2>405 Method Not Allowed</h2>
                    <p>Phương thức <b>{$requestMethod}</b> không được phép sử dụng cho đường dẫn này.</p>
                    <a href='index.php?route=home'>Trang chủ</a>
                  </div>";
            return;
        }
        // Các chức năng phía khách yêu cầu đăng nhập.
        $loginRequiredRoutes = [
            'cart',
            'cart_add',
            'cart_update',
            'cart_remove',
            'cart_apply_coupon',
            'cart_remove_coupon',

            // Tương thích tên đang có trong bộ khung.
            'cart_apply_coupon',
            'cart_remove_coupon',
            'cart_add',
            'cart_update',
            'cart_remove',
            'cart_apply_coupon',
            'cart_remove_coupon',

            'checkout',
            'checkout_success',
            'orders',
            'order_cancel',
            'order_update_recipient',
            'order_detail',
            'payment_simulate',
            'profile',
            'change_password',
        ];

        if (in_array($route, $loginRequiredRoutes, true)) {
            Auth::requireLogin();
            // Admin quản trị cửa hàng, không sử dụng luồng mua hàng/hồ sơ cá nhân.
            // Đổi mật khẩu vẫn dành cho mọi tài khoản đang hoạt động.
            if ($route !== 'change_password' && Auth::user()['role'] === 'admin') {
                http_response_code(403);
                require APP_ROOT . '/app/views/errors/403.php';
                return;
            }
        }

        // Các route quản trị.
        $isAdminRoute = str_starts_with($route, 'admin_')
            || str_starts_with($route, 'admin/');

        if ($isAdminRoute) {
            // Nhân viên và admin được quản lý hàng hóa, kho và đơn.
            $staffRouteGroups = [
                'admin_categories',
                'admin_products',
                'admin_variants',
                'admin_orders',
            ];

            $staffAllowed = false;

            foreach ($staffRouteGroups as $group) {
                if (
                    $route === $group
                    || str_starts_with($route, $group . '_')
                ) {
                    $staffAllowed = true;
                    break;
                }
            }

            if ($staffAllowed) {
                Auth::requireRole(['admin', 'staff']);
            } else {
                // Tài khoản, mã giảm, báo cáo và các route admin khác.
                Auth::requireRole(['admin']);
            }
        }

        // Kiểm tra 2: Route có đang ở trạng thái chờ triển khai không?
        if (isset($routeInfo['status']) && $routeInfo['status'] === 'pending') {
            http_response_code(200);
            echo "<div style='font-family:sans-serif; text-align:center; margin-top:50px;'>
                    <h2>Chức năng này đang chờ triển khai!</h2>
                    <p>Route <b>?route={$route}</b> đã được đăng ký nhưng nhóm đang hoàn thiện ở giai đoạn sau.</p>
                    <a href='index.php?route=home'>Quay lại trang chủ</a>
                  </div>";
            return;
        }

        // Thực thi Controller và Action tương ứng
        [$controllerClass, $method] = $routeInfo['handler'];
        if (class_exists($controllerClass)) {
            $controller = new $controllerClass();
            if (method_exists($controller, $method)) {
                $controller->$method();
                return;
            }
        }

        // Trường hợp lỗi phát sinh ngầm
        http_response_code(404);
        require APP_ROOT . '/app/views/errors/404.php';
    }
}
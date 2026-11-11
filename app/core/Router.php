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
            'product_detail'        => ['method' => 'GET',  'handler' => [ProductController::class, 'detail'], 'status' => 'ready'],

            // --- 2. XÁC THỰC & TÀI KHOẢN---
            'login'                 => ['method' => 'GET|POST', 'handler' => [AuthController::class, 'login'], 'status' => 'ready'],
            'register'              => ['method' => 'GET|POST', 'handler' => [AuthController::class, 'register'], 'status' => 'ready'],
            'logout'                => ['method' => 'GET',      'handler' => [AuthController::class, 'logout'], 'status' => 'ready'],
            'profile'               => ['method' => 'GET|POST', 'handler' => [ProfileController::class, 'index'], 'status' => 'ready'],
            'change_password'       => ['method' => 'GET|POST', 'handler' => [ProfileController::class, 'changePassword'], 'status' => 'ready'],

            // --- 3. GIỎ HÀNG & MUA HÀNG COD  ---
            'cart'                  => ['method' => 'GET',      'handler' => [CartController::class, 'index'], 'status' => 'ready'],
            'cart_add'              => ['method' => 'POST',     'handler' => [CartController::class, 'add'], 'status' => 'ready'],
            'cart_update'           => ['method' => 'POST',     'handler' => [CartController::class, 'update'], 'status' => 'ready'],
            'cart_remove'           => ['method' => 'GET',      'handler' => [CartController::class, 'remove'], 'status' => 'ready'],
            'checkout'              => ['method' => 'GET|POST', 'handler' => [CheckoutController::class, 'index'], 'status' => 'ready'],
            'checkout_success'      => ['method' => 'GET',      'handler' => [CheckoutController::class, 'success'], 'status' => 'ready'],

            // --- 4. ĐƠN HÀNG & THANH TOÁN ---
            'orders'                => ['method' => 'GET',      'handler' => [OrderController::class, 'index'], 'status' => 'ready'],
            'order_detail'          => ['method' => 'GET',      'handler' => [OrderController::class, 'detail'], 'status' => 'ready'],
            'payment_simulate'      => ['method' => 'GET|POST', 'handler' => [PaymentController::class, 'simulate'], 'status' => 'ready'],

            // --- 5. QUẢN TRỊ ADMIN / STAFF  ---
            'admin_dashboard'       => ['method' => 'GET',      'handler' => [AdminDashboardController::class, 'index'], 'status' => 'ready'],
            'admin_users'           => ['method' => 'GET|POST', 'handler' => [AdminUserController::class, 'index'], 'status' => 'ready'],
            
            // Các route đăng ký giữ chỗ nhưng chưa viết code chi tiết (đánh dấu chờ triển khai)
            'admin_products'        => ['method' => 'GET',      'handler' => [AdminProductController::class, 'index'], 'status' => 'pending'],
            'admin_orders'          => ['method' => 'GET',      'handler' => [AdminOrderController::class, 'index'], 'status' => 'pending'],
        ];

        // Kiểm tra 1: URL có tồn tại trong hệ thống không?
        if (!isset($routes[$route])) {
            http_response_code(404);
            require APP_ROOT . '/app/views/errors/404.php';
            return;
        }

        $routeInfo = $routes[$route];

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
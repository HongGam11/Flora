
| STT | Route | Phương thức | Chức năng | Controller & Hàm xử lý | Quyền truy cập |
| :---: | :--- | :---: | :--- | :--- | :--- |
| 1 | `home` | GET | Trang chủ | `HomeController::index` | Mọi người |
| 2 | `products` | GET | Danh sách, tìm kiếm sản phẩm | `ProductController::index` | Mọi người |
| 3 | `product-detail` | GET | Chi tiết sản phẩm | `ProductController::detail` | Mọi người |
| 4 | `register` | GET / POST | Hiện form / xử lý đăng ký | `AuthController::register` | Chưa đăng nhập |
| 5 | `login` | GET / POST | Hiện form / xử lý đăng nhập | `AuthController::login` | Chưa đăng nhập |
| 6 | `logout` | POST | Đăng xuất | `AuthController::logout` | Đã đăng nhập |
| 7 | `cart` | GET | Xem giỏ hàng | `CartController::index` | Khách hàng |
| 8 | `cart-add` | POST | Thêm vào giỏ | `CartController::add` | Khách hàng |
| 9 | `cart-update` | POST | Sửa số lượng | `CartController::update` | Khách hàng |
| 10 | `cart-remove` | POST | Xóa món trong giỏ | `CartController::remove` | Khách hàng |
| 11 | `checkout` | GET / POST | Hiện form / tạo đơn | `CheckoutController::index` | Khách hàng |
| 12 | `orders` | GET | Danh sách đơn của mình | `OrderController::index` | Khách hàng |
| 13 | `order-detail` | GET | Chi tiết đơn của mình | `OrderController::detail` | Khách hàng |
| 14 | `order-cancel` | POST | Hủy đơn của mình | `OrderController::cancel` | Khách hàng |
| 15 | `admin-products` | GET | Quản lý sản phẩm | `AdminProductController::index` | Staff / admin |
| 16 | `admin-orders` | GET | Quản lý đơn hàng | `AdminOrderController::index` | Staff / admin |
# Flora bộ khung MVC cho nhóm sáu thành viên

Gói này tạo cấu trúc thư mục và các file mẫu. `index.php` hiển thị cửa hàng Flora, lấy sản phẩm, giá, ảnh và biến thể từ MariaDB; có tìm kiếm, lọc, sắp xếp và trang chi tiết sản phẩm. Các nghiệp vụ trong MVC như giỏ hàng và thanh toán vẫn là TODO, nên đây chưa phải website bán hàng hoàn chỉnh.

## Dùng trên dự án đang có

1. Giải nén ra thư mục tạm; sao lưu dự án hiện tại trước khi ghép.
2. Giữ database.php đang chạy nếu muốn giữ cách cấu hình cũ. Không ghi đè cấu hình thật khi copy. File này tiếp tục tạo biến $conn kiểu MySQLi.
3. Nếu dùng cơ chế cấu hình đi kèm, sao chép database.example.php thành database.local.php rồi điền host, username, password, database flora và port. File local bị .gitignore loại khỏi Git. Không gửi mật khẩu thật cho người khác.
4. Ghép thư mục app, assets, scripts, docs và index.php vào C:\xampp\htdocs\flora. Mở đúng thư mục này bằng VS Code.
5. Apache và MySQL trong XAMPP phải đang chạy. Mở http://localhost/flora/index.php để xem cửa hàng. Mở http://localhost/flora/test_db.php để kiểm tra dữ liệu. Không mở file trực tiếp từ ổ đĩa hoặc dùng Live Server.
6. Nếu database flora đã có 15 sản phẩm thì không import SQL lại. Chỉ máy chưa có database mới nhập db/flora_database.sql một lần. File dừng nếu tên database đã tồn tại.

## Môi trường

PHP 8.2, extension MySQLi, MariaDB 10.4.32, utf8mb4, thời gian UTC. Giao diện nghiệp vụ khi hoàn thành chuyển thời gian sang giờ Việt Nam.

## Kiểm tra dữ liệu mẫu

products 15; product_variants 84; product_images 28; users 3; coupons 2. Đây là số liệu nhóm đã xác nhận trên máy trước khi nhận bộ khung; không phải kết quả kiểm thử mới của gói này.

## Phân công và tài liệu

Đọc docs/phan_cong.md, docs/quy_uoc.md và docs/kiem_thu.md. docs/Flora_Database.md và docs/ERD.png là bản nguồn cần TV1 đồng bộ với SQL. Chi tiết điểm cần cập nhật đã ghi trong quy_uoc.md.

## Lưu ý khi phát triển

Mỗi người làm cả giao diện và PHP phần mình. Đầu ra đi qua router và controller. Không gọi các Auth guard chưa triển khai để giả định đã có bảo vệ: các hàm này chủ động báo lỗi. Không có giỏ hàng, thanh toán hoặc tác vụ hết hạn thật trong bộ khung. expire_orders.php hiện báo TODO và thoát lỗi.

TV6 cần triển khai OrderService và tác vụ hết hạn trước khi đăng ký chạy mỗi phút bằng Windows Task Scheduler. Chưa cấu hình tác vụ tự chạy trong gói này.

Các thư mục app, db, docs chỉ dùng nội bộ. Các file .htaccess có quy tắc chặn truy cập trực tiếp khi Apache cho phép override; xác nhận thực tế trước khi đưa lên môi trường khác. Gỡ test_db.php khỏi bản triển khai công khai.

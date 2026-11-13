# Quy ước Flora

## Môi trường
PHP 8.2 hoặc tương thích; MariaDB 10.4.32; MySQLi; UTF-8; database flora; URL qua localhost.

## MVC
Controller nhận yêu cầu; model truy vấn; view chỉ hiển thị; service gom nghiệp vụ dùng chung.

## SQL và tài liệu
Không tự đổi bảng cột hoặc trạng thái. Mọi thay đổi có SQL cập nhật, người phụ trách và cập nhật ERD.

## File dùng chung
TV1 giữ router, phiên, layout và cấu hình; TV2 giữ CSS. Model do người phụ trách module quản lý; báo trước khi sửa.

## Dữ liệu đầu vào
Dùng prepared statements cho giá trị người dùng nhập; danh sách cho phép cho cột sắp xếp và route.

## Quyền và phiên
Kiểm tra quyền/chủ sở hữu ở server; băm mật khẩu; đổi ID phiên sau đăng nhập; không nhận role tùy ý từ form.

## Thay đổi dữ liệu
Dùng POST và kiểm tra CSRF; escape nội dung hiển thị theo ngữ cảnh; thông báo rõ khi sai dữ liệu.

## Cấu hình
Không đưa mật khẩu thật lên Git. Dùng database.local.php hoặc giữ file kết nối riêng đang chạy và loại file đó khỏi Git.

## Giao diện
Tiếng Việt; thống nhất màu font nút bảng thông báo; kiểm tra trên điện thoại và máy tính.

## Git
Nhánh feature riêng; commit nhỏ rõ nghĩa; cập nhật trước khi ghép; Pull Request có người khác kiểm tra; không force push nhánh chính.

## Tiến độ
Mỗi buổi báo mã task đã xong đang làm và vướng. Không có hạn nộp cụ thể nên nhóm điền hạn theo từng task.

## Bằng chứng
Mỗi chức năng có URL cách thử dữ liệu đầu vào kết quả thực tế và lỗi còn lại; không ghi đã đạt khi chưa chạy.

# Nghiệp vụ đã chốt

## Mua hàng
Xem và chọn size màu được trước đăng nhập; muốn thêm giỏ phải đăng nhập.

## Giá và tồn
Giá/tồn theo biến thể; danh mục sản phẩm biến thể đều active và đủ kho mới được mua.

## Giảm giá
Giảm sản phẩm theo thời gian; tối đa một mã đơn; áp mã sau giảm sản phẩm; không giảm ship.

## Vận chuyển
Ship 30.000đ; miễn từ subtotal 500.000đ sau giảm sản phẩm và trước mã. 520.000đ trừ mã 50.000đ còn 470.000đ, ship 0đ.

## Tạo đơn
Kiểm tra lại giá và kho trên server; giá đổi thì yêu cầu khách xác nhận lại; chống tạo trùng bằng checkout_token.

## Kho
Thêm giỏ chưa trừ; trừ khi tạo đơn thành công; xác nhận/giao không trừ lần hai; hủy hoàn một lần.

## Hủy
Khách chỉ đơn của mình pending; staff/admin hủy pending hoặc confirmed và có lý do; không hủy sau bắt đầu giao trong phạm vi bài.

## Online
Mô phỏng ví điện tử và ví trả sau; 30 phút từ tạo đơn; thử lại không gia hạn; paid không tự confirmed.

## Hết hạn
Tác vụ riêng chạy đề xuất mỗi phút; chỉ hủy online pending unpaid đã hết hạn, kiểm tra lại sau khóa.

## Hoàn tiền
Chỉ admin; toàn bộ cho đơn cancelled paid; thất bại giữ paid; thành công mới refunded; hoàn kho độc lập kết quả hoàn tiền.

## Đơn không thu tiền
Tổng 0đ dùng not_required và không có giao dịch amount 0; hủy không tạo refund.

## Snapshot và lịch sử
Lưu thông tin lúc mua; không đổi đơn cũ theo sản phẩm/hồ sơ hiện tại; thay trạng thái cùng ghi lịch sử.

## Giới hạn
Chưa quản lý nhà cung cấp giá vốn sổ nhập xuất; không đổi trả sau giao hoặc hoàn một phần; chưa tích hợp tiền thật.

# Đồng bộ tài liệu

SQL hiện hành dùng main_product_id và success_type. ERD gốc trong gói còn main_slot và success_slot; TV1 cần cập nhật theo database thực tế. MD gốc còn ghi chưa triển khai và thiếu quần jean nữ trong danh mục; TV1 cập nhật khi bàn giao. Các điều chỉnh này chưa được xem là hoàn tất.

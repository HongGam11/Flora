# FLORA — THIẾT KẾ CƠ SỞ DỮ LIỆU

**Môn học:** Lập trình Web  
**Đề tài:** Website bán quần áo nam và nữ  
**Công nghệ:** PHP + MySQL; HTML, CSS, JavaScript; tổ chức mã nguồn MVC  
**Ngôn ngữ giao diện:** Tiếng Việt  
**Ngày cập nhật:** 08/11/2026  
**Trạng thái:** Bản thiết kế cập nhật sau phản biện; chưa triển khai SQL hoặc chạy kiểm thử. Đã chốt miễn ship theo tiền hàng sau giảm giá sản phẩm, trước mã giảm giá.

## 1. Phạm vi nghiệp vụ

| Nội dung | Quy định |
|---|---|
| Sản phẩm | Quần áo nam/nữ, nhiều ảnh và nhiều biến thể size–màu |
| Giá và tồn kho | Riêng cho từng biến thể |
| Giảm giá sản phẩm | Phần trăm và thời gian giảm chung cho các biến thể của sản phẩm |
| Mã giảm giá | Tối đa một mã/đơn; được kết hợp giảm giá sản phẩm |
| Phí ship | 30.000đ; miễn khi tiền hàng sau giảm sản phẩm, trước mã giảm giá đạt từ 500.000đ |
| Thanh toán | COD; ví điện tử, ví trả sau mô phỏng; không giao dịch tiền thật |
| Trừ kho | Khi tạo đơn thành công; hủy hoàn đúng một lần |
| Hạn online | 30 phút từ lúc tạo đơn, không gia hạn khi thử lại |
| Khách hủy | Chỉ đơn của mình đang chờ xác nhận |
| Nhân viên/admin hủy | Trước khi giao, có lý do |
| Hoàn tiền | Toàn bộ bằng mô phỏng khi hủy đơn đã trả tiền |
| Ngoài phạm vi | Giao thất bại, đổi trả sau giao, hoàn một phần, quản lý khoản vay |
| Báo cáo | Bán hàng, tồn kho, lượt xem, tìm kiếm và thêm giỏ |

Luồng chính: xem sản phẩm → chọn size/màu → đăng nhập → thêm giỏ → đặt hàng → thanh toán nếu chọn online → nhân viên/admin xử lý → khách theo dõi đơn.

Khách chưa đăng nhập được xem, tìm kiếm và chọn size/màu. Khi muốn thêm giỏ, khách phải đăng nhập; người đã đăng nhập bỏ qua bước này. Sau đăng nhập, quay lại sản phẩm đã chọn để tiếp tục thêm giỏ.

## 2. Quy ước kỹ thuật

- Gồm 15 bảng. Mỗi bảng có `id INT UNSIGNED`, khóa chính tự tăng.
- FK cùng kiểu dữ liệu với PK được tham chiếu.
- Tiền: `DECIMAL(12,0)`, đơn vị VNĐ; tỷ lệ: `DECIMAL(5,2)`.
- InnoDB, bộ ký tự `utf8mb4`; chọn collation phù hợp phiên bản thực tế.
- Mặc định cột NOT NULL, trừ cột ghi rõ cho phép NULL.
- `created_at` mặc định thời điểm tạo; `updated_at` cập nhật khi sửa.
- Lưu thời gian UTC, giao diện chuyển sang giờ Việt Nam. PHP và kết nối DB phải cùng quy ước.
- PK: khóa chính; FK: khóa ngoại; UNIQUE: không trùng.
- Trạng thái dùng `VARCHAR(20)`; kiểm tra tập giá trị ở server và ràng buộc DB phù hợp.
- Kiểm tra `SELECT VERSION();` trước khi chốt DDL, đặc biệt CHECK và cột sinh.

### 2.1. Lý do tách bảng và dữ liệu lịch sử

- products lưu thuộc tính chung; product_variants lưu giá và tồn riêng từng size–màu, tránh tạo nhiều sản phẩm trùng tên/mô tả.
- sizes/colors là danh sách dùng chung; không lưu nhiều size hoặc màu thành chuỗi trong một cột sản phẩm.
- orders lưu thông tin chung của lần đặt; order_items lưu từng biến thể và số lượng, biểu diễn quan hệ đơn–biến thể qua bảng chi tiết.
- product_images tách riêng vì một sản phẩm có nhiều ảnh; carts/cart_items tách phần giỏ và các dòng giỏ.
- payment_transactions lưu từng lần thử và hoàn tiền; order_status_history lưu các lần thay đổi trạng thái đơn, hai loại lịch sử khác nhau.
- Tên/size/màu/giá trong order_items và người nhận/mã giảm/phí trong orders là snapshot tại lúc đặt, cố ý giữ độc lập với dữ liệu hiện tại.
- subtotal/total_amount/payment_status là dữ liệu tổng hợp được lưu để phục vụ đơn hàng. Các giá trị này phải được cập nhật qua quy trình thống nhất, không sửa tùy ý.
- Thiết kế giảm trùng dữ liệu mô tả và dùng khóa cho các quan hệ. Chưa tuyên bố toàn bộ schema đạt 3NF khi chưa phân tích đầy đủ phụ thuộc hàm; snapshot và dữ liệu tổng hợp được giải thích riêng.

### 2.2. Giới hạn quản lý kho

Giữ 15 bảng: quản lý tồn khả dụng hiện tại, chưa có sổ lịch sử nhập/xuất/điều chỉnh kho và chưa quản lý giá vốn, nhà cung cấp hay đơn nhập hàng. stock giảm khi đặt đơn nên không đồng nghĩa số hàng vật lý đang nằm trong kho. updated_at không thay thế lịch sử kho. Nếu mở rộng truy vết từng lần điều chỉnh, thêm stock_movements ở giai đoạn sau.

### 2.3. Giới hạn mô phỏng thanh toán

Ví điện tử và ví trả sau dùng chung luồng mô phỏng success/failed, khác nhãn và payment_method. Không có hạn mức, xét duyệt tín dụng, lịch trả nợ hoặc chuyển tiền thật. COD cũng dùng dữ liệu demo khi xác nhận thu tiền. Chỉ hiển thị kết quả thử nghiệm, không tuyên bố tích hợp nhà cung cấp.

## 3. Chi tiết bảng

### 3.1. users — Tài khoản

| Cột | Kiểu | Ràng buộc / ý nghĩa |
|---|---|---|
| id | INT UNSIGNED | PK |
| full_name | VARCHAR(100) | Họ tên |
| email | VARCHAR(255) | UNIQUE; chuẩn hóa và bỏ khoảng trắng thừa |
| password_hash | VARCHAR(255) | Mật khẩu đã băm |
| phone | VARCHAR(20) | Cho phép NULL |
| address | VARCHAR(500) | Địa chỉ mặc định; cho phép NULL |
| role | VARCHAR(20) | admin/staff/customer; mặc định customer |
| status | VARCHAR(20) | active/disabled; mặc định active |
| created_at | DATETIME | Ngày tạo |
| updated_at | DATETIME | Ngày cập nhật |

- Đăng ký công khai chỉ tạo customer; chỉ admin tạo staff và đổi vai trò.
- Không cho khóa/hạ quyền admin hoạt động cuối cùng.
- Không cung cấp chức năng xóa tài khoản; khóa không xóa đơn cũ.

### 3.2. categories — Danh mục

| Cột | Kiểu | Ràng buộc / ý nghĩa |
|---|---|---|
| id | INT UNSIGNED | PK |
| parent_id | INT UNSIGNED | FK → categories.id; NULL với nhóm gốc |
| name | VARCHAR(100) | Tên danh mục |
| status | VARCHAR(20) | active/inactive; mặc định active |
| created_at | DATETIME | Ngày tạo |
| updated_at | DATETIME | Ngày cập nhật |

UNIQUE `(parent_id, name)`.

| Nhóm gốc cố định | Danh mục con ban đầu |
|---|---|
| Thời trang nam | Áo thun, áo sơ mi, áo khoác, quần jean, quần short |
| Thời trang nữ | Áo thun, áo kiểu, quần đùi, chân váy, áo sơ mi, đầm |

- Hai nhóm gốc tạo sẵn; không có chức năng thêm/xóa/đổi nhóm gốc.
- Staff/admin quản lý danh mục con; không tạo cấp ba hoặc tự tham chiếu.
- Sản phẩm chỉ thuộc danh mục con. Danh mục inactive thì không được mua sản phẩm bên dưới.
- Không cần cột gender. UNIQUE có parent_id NULL không thay thế việc kiểm soát hai nhóm gốc cố định.

### 3.3. products — Sản phẩm

| Cột | Kiểu | Ràng buộc / ý nghĩa |
|---|---|---|
| id | INT UNSIGNED | PK |
| category_id | INT UNSIGNED | FK → categories.id; phải là danh mục con |
| name | VARCHAR(200) | Tên sản phẩm |
| description | TEXT | Mô tả editor; cho phép NULL |
| discount_percent | DECIMAL(5,2) | 0–100; mặc định 0 |
| discount_start_at | DATETIME | Cho phép NULL khi không giảm |
| discount_end_at | DATETIME | Cho phép NULL khi không giảm |
| is_featured | BOOLEAN | Mặc định 0 |
| status | VARCHAR(20) | active/inactive; mặc định inactive |
| published_at | DATETIME | Ngày mở bán lần đầu; cho phép NULL |
| created_at | DATETIME | Ngày tạo |
| updated_at | DATETIME | Ngày cập nhật |

- Không lưu giá và tồn kho tại products.
- Có giảm giá thì phải có thời gian hợp lệ; bắt đầu < kết thúc. Hiệu lực: bắt đầu ≤ hiện tại < kết thúc.
- Chỉ mở bán khi có ảnh chính và ít nhất một biến thể active.
- published_at không đổi theo mỗi lần bật/tắt sản phẩm.

### 3.4. product_images — Ảnh sản phẩm

| Cột | Kiểu | Ràng buộc / ý nghĩa |
|---|---|---|
| id | INT UNSIGNED | PK |
| product_id | INT UNSIGNED | FK → products.id |
| color_id | INT UNSIGNED | FK → colors.id; NULL với ảnh chung |
| image_path | VARCHAR(500) | Đường dẫn ảnh |
| is_main | BOOLEAN | Mặc định 0 |
| sort_order | INT UNSIGNED | Mặc định 0 |

- color_id có giá trị phải là màu của ít nhất một biến thể cùng product_id; PHP kiểm tra trong transaction khóa sản phẩm. FK riêng tới colors chưa đủ bảo đảm điều này.
- Chọn màu thì ưu tiên ảnh màu đó, kèm ảnh chung; nếu chưa có ảnh theo màu thì dùng bộ ảnh chung. Không tách ảnh theo size.
- Khi xóa biến thể cuối cùng của một màu, phải xóa/gán lại ảnh màu đó trong cùng quy trình; không để liên kết màu không còn thuộc sản phẩm.
- Tối đa một ảnh chính/sản phẩm; sản phẩm đang bán phải có đúng một ảnh chính.
- Mọi thao tác đổi/xóa ảnh chính khóa dòng sản phẩm và cập nhật trong transaction.
- Không xóa ảnh chính cuối cùng của sản phẩm đang bán nếu chưa có ảnh thay thế.

### 3.5. sizes — Kích thước

| Cột | Kiểu | Ràng buộc / ý nghĩa |
|---|---|---|
| id | INT UNSIGNED | PK |
| name | VARCHAR(20) | UNIQUE; S, M, L, XL, 28, 29… |
| sort_order | INT UNSIGNED | Mặc định 0 |

### 3.6. colors — Màu sắc

| Cột | Kiểu | Ràng buộc / ý nghĩa |
|---|---|---|
| id | INT UNSIGNED | PK |
| name | VARCHAR(50) | UNIQUE |
| hex_code | CHAR(7) | Ví dụ #FFFFFF; cho phép NULL |

### 3.7. product_variants — Biến thể, giá và tồn kho

| Cột | Kiểu | Ràng buộc / ý nghĩa |
|---|---|---|
| id | INT UNSIGNED | PK |
| product_id | INT UNSIGNED | FK → products.id |
| size_id | INT UNSIGNED | FK → sizes.id |
| color_id | INT UNSIGNED | FK → colors.id |
| original_price | DECIMAL(12,0) | Giá bán trước giảm, không phải giá nhập; > 0 |
| stock | INT UNSIGNED | Tồn kho; mặc định 0 |
| status | VARCHAR(20) | active/inactive; mặc định active |
| created_at | DATETIME | Ngày tạo |
| updated_at | DATETIME | Ngày cập nhật |

UNIQUE `(product_id, size_id, color_id)`.

Ví dụ áo trắng size M giá 150.000đ và đen size L giá 170.000đ là hai biến thể. Nếu sản phẩm giảm 10%, giá bán tương ứng 135.000đ và 153.000đ.

- Chỉ mua khi danh mục, sản phẩm, biến thể đều active và đủ kho.
- Trang danh sách có thể hiển thị “Từ …” theo giá bán hiện hành thấp nhất của các biến thể active; chọn size/màu hiển thị giá chính xác.
- Một khi biến thể có order_items, không thay product_id/size_id/color_id; chỉ sửa giá, trạng thái và tồn theo quy trình. Cần tổ hợp mới thì tạo biến thể mới.
- Không đổi nghĩa màu/size đang được sử dụng (ví dụ Trắng thành Đen); chỉ sửa lỗi chính tả không đổi nghĩa, còn giá trị mới phải tạo bản ghi mới.
- Sửa tồn kho phải xử lý đồng thời an toàn; không ghi đè số tồn cũ từ màn hình lên tồn hiện tại.

### 3.8. carts — Giỏ hàng

| Cột | Kiểu | Ràng buộc / ý nghĩa |
|---|---|---|
| id | INT UNSIGNED | PK |
| user_id | INT UNSIGNED | FK → users.id; UNIQUE |
| created_at | DATETIME | Ngày tạo |
| updated_at | DATETIME | Ngày cập nhật |

- Một tài khoản tối đa một giỏ DB. Chỉ tài khoản đã đăng nhập và đang hoạt động được thêm, xem hoặc chỉnh sửa giỏ của mình.
- Không tạo giỏ cho khách chưa đăng nhập và không có bước hợp nhất giỏ Session với database. Có thể lưu tạm lựa chọn sản phẩm để quay lại sau đăng nhập; đây không phải giỏ hàng.
- Thêm lại biến thể đã có trong giỏ thì cộng số lượng, kiểm tra và thông báo nếu vượt kho.

### 3.9. cart_items — Chi tiết giỏ

| Cột | Kiểu | Ràng buộc / ý nghĩa |
|---|---|---|
| id | INT UNSIGNED | PK |
| cart_id | INT UNSIGNED | FK → carts.id |
| variant_id | INT UNSIGNED | FK → product_variants.id |
| quantity | INT UNSIGNED | > 0 |
| created_at | DATETIME | Ngày thêm |
| updated_at | DATETIME | Ngày cập nhật |

UNIQUE `(cart_id, variant_id)`. Không giữ giá cố định hoặc trừ kho khi thêm giỏ. Kiểm tra lại giá/trạng thái/kho lúc checkout. Sau khi tạo đơn thành công, loại các mặt hàng đã đặt khỏi giỏ trong quy trình nhất quán.

### 3.10. coupons — Mã giảm giá

| Cột | Kiểu | Ràng buộc / ý nghĩa |
|---|---|---|
| id | INT UNSIGNED | PK |
| code | VARCHAR(50) | UNIQUE; chuẩn hóa viết hoa |
| description | VARCHAR(255) | Cho phép NULL |
| discount_type | VARCHAR(20) | percent/fixed |
| discount_value | DECIMAL(12,2) | > 0; percent ≤ 100; fixed là số đồng nguyên |
| max_discount_amount | DECIMAL(12,0) | Bắt buộc > 0 với mã percent; NULL với mã fixed |
| min_order_amount | DECIMAL(12,0) | ≥ 0; mặc định 0 |
| start_at | DATETIME | Bắt đầu |
| end_at | DATETIME | Sau start_at |
| usage_limit | INT UNSIGNED | NULL nếu không giới hạn; nếu có > 0 |
| per_user_limit | INT UNSIGNED | NULL nếu không giới hạn; nếu có > 0 |
| status | VARCHAR(20) | active/inactive; mặc định active |
| created_at | DATETIME | Ngày tạo |
| updated_at | DATETIME | Ngày cập nhật |

- Xét min_order_amount trên subtotal (sau giảm sản phẩm, trước mã).
- Giảm tối đa bằng subtotal, không giảm ship. Mã fixed để max_discount_amount NULL.
- Hiệu lực: start_at ≤ hiện tại < end_at.
- Đơn đã tạo chưa hủy chiếm lượt, kể cả đang chờ trả tiền; hủy trả lượt.
- Không lưu used_count; đếm từ orders trong transaction có kiểm soát đồng thời.
- Mã đã gắn vào đơn không được xóa; có thể vô hiệu hóa.

### 3.11. orders — Đơn hàng

| Cột | Kiểu | Ràng buộc / ý nghĩa |
|---|---|---|
| id | INT UNSIGNED | PK |
| order_code | VARCHAR(40) | UNIQUE; mã hiển thị |
| checkout_token | CHAR(64) | UNIQUE; chống tạo trùng |
| user_id | INT UNSIGNED | FK → users.id |
| recipient_name | VARCHAR(100) | Người nhận tại lúc đặt |
| recipient_phone | VARCHAR(20) | SĐT tại lúc đặt |
| shipping_address | VARCHAR(500) | Địa chỉ tại lúc đặt |
| note | TEXT | Cho phép NULL |
| subtotal | DECIMAL(12,0) | ≥ 0; tiền hàng sau giảm sản phẩm |
| coupon_id | INT UNSIGNED | FK → coupons.id; cho phép NULL |
| coupon_code | VARCHAR(50) | Bản chụp mã; cho phép NULL |
| coupon_discount | DECIMAL(12,0) | 0 ≤ giá trị ≤ subtotal; mặc định 0 |
| shipping_fee | DECIMAL(12,0) | ≥ 0; thực tế 0/30.000 theo chính sách hiện tại |
| total_amount | DECIMAL(12,0) | Tổng phải trả; ≥ 0; đơn 0đ xử lý theo mục 5.1 |
| payment_method | VARCHAR(20) | cod/ewallet/paylater |
| payment_status | VARCHAR(20) | unpaid/paid/refunded/not_required; mặc định unpaid |
| status | VARCHAR(20) | pending/confirmed/shipping/completed/cancelled; mặc định pending |
| payment_expires_at | DATETIME | created_at + 30 phút với online có tổng > 0; NULL với COD hoặc đơn 0đ |
| cancel_reason | VARCHAR(500) | NULL khi chưa hủy |
| created_at | DATETIME | Thời điểm tạo |
| updated_at | DATETIME | Thời điểm cập nhật |
| completed_at | DATETIME | NULL khi chưa hoàn tất |
| cancelled_at | DATETIME | NULL khi chưa hủy |

- Server cấp checkout_token cho một lần checkout, gắn đúng người dùng. Gửi lại token trả đơn cũ của chính người dùng, không tạo mới.
- order_code có tiền tố Flora và chuỗi ngẫu nhiên; tạo lại nếu trùng UNIQUE.
- Không đổi phương thức, chi tiết hoặc số lượng sau khi tạo đơn; cần đổi thì hủy và đặt lại.
- Snapshot giá, người nhận, mã và phí không thay đổi theo dữ liệu hiện tại.
- Không dùng mã: coupon_id/coupon_code NULL, coupon_discount = 0.

### 3.12. order_items — Chi tiết đơn

| Cột | Kiểu | Ràng buộc / ý nghĩa |
|---|---|---|
| id | INT UNSIGNED | PK |
| order_id | INT UNSIGNED | FK → orders.id |
| variant_id | INT UNSIGNED | FK → product_variants.id |
| product_name | VARCHAR(200) | Tên tại lúc mua |
| size_name | VARCHAR(20) | Size tại lúc mua |
| color_name | VARCHAR(50) | Màu tại lúc mua |
| quantity | INT UNSIGNED | > 0 |
| original_unit_price | DECIMAL(12,0) | Giá biến thể trước giảm tại lúc mua; > 0 |
| unit_price | DECIMAL(12,0) | Giá sau giảm sản phẩm, trước mã |

UNIQUE `(order_id, variant_id)`; `0 ≤ unit_price ≤ original_unit_price`. Giữ bản chụp dữ liệu, không cập nhật theo sản phẩm. Thành tiền dòng = quantity × unit_price.

### 3.13. payment_transactions — Thanh toán và hoàn tiền

| Cột | Kiểu | Ràng buộc / ý nghĩa |
|---|---|---|
| id | INT UNSIGNED | PK |
| order_id | INT UNSIGNED | FK → orders.id |
| transaction_code | VARCHAR(64) | UNIQUE |
| transaction_type | VARCHAR(20) | payment/refund |
| original_transaction_id | INT UNSIGNED | FK → payment_transactions.id; NULL với payment |
| payment_method | VARCHAR(20) | cod/ewallet/paylater |
| amount | DECIMAL(12,0) | > 0; bằng total_amount của đơn |
| status | VARCHAR(20) | pending/success/failed |
| is_simulated | BOOLEAN | 1 cho dữ liệu mô phỏng của bài |
| created_at | DATETIME | Thời điểm tạo lần thử |
| processed_at | DATETIME | NULL khi pending |
| processed_by | INT UNSIGNED | FK → users.id; NULL khi hệ thống xử lý |
| result_message | VARCHAR(500) | Kết quả/lý do thất bại; cho phép NULL |

- Một dòng/một lần thử; giữ cả lần thất bại.
- Khi staff/admin xác nhận COD hoặc admin hoàn tiền, ghi processed_by từ phiên đăng nhập, không lấy người xử lý tùy ý từ form. Kết quả online do bộ mô phỏng xử lý thì để NULL.
- pending có processed_at NULL; success/failed phải có processed_at. result_message không lưu thông tin nhạy cảm hoặc thông tin đăng nhập.
- Refund tham chiếu payment thành công cùng đơn, cùng phương thức; không tự tham chiếu.
- Tối đa một payment success và một refund success/đơn; chỉ toàn bộ, không một phần.
- Khóa dòng đơn trước khi ghi nhận kết quả; giao dịch đã kết thúc không nhận kết quả khác.
- Đơn đã trả tiền không thu lại; đơn đã hủy không nhận thanh toán mới.
- Bổ sung UNIQUE có điều kiện qua cột sinh cho giao dịch success ở bước DDL sau khi xác định phiên bản. Đây là ràng buộc kỹ thuật, không phải dữ liệu nhập trên giao diện.
- orders.payment_status cập nhật cùng transaction; không lấy máy móc giao dịch gần nhất.

### 3.14. order_status_history — Lịch sử đơn

| Cột | Kiểu | Ràng buộc / ý nghĩa |
|---|---|---|
| id | INT UNSIGNED | PK |
| order_id | INT UNSIGNED | FK → orders.id |
| changed_by | INT UNSIGNED | FK → users.id; NULL nếu hệ thống |
| old_status | VARCHAR(20) | NULL ở lần tạo |
| new_status | VARCHAR(20) | Trạng thái mới |
| note | VARCHAR(500) | Cho phép NULL |
| created_at | DATETIME | Thời điểm thay đổi |

Chỉ thêm lịch sử; không sửa/xóa qua giao diện. Cập nhật đơn và ghi lịch sử trong cùng transaction.

### 3.15. user_events — Hành vi

| Cột | Kiểu | Ràng buộc / ý nghĩa |
|---|---|---|
| id | INT UNSIGNED | PK |
| user_id | INT UNSIGNED | FK → users.id; NULL nếu chưa đăng nhập |
| session_key | VARCHAR(64) | Mã phiên theo dõi riêng, không phải token xác thực |
| event_type | VARCHAR(20) | view_product/search/add_to_cart |
| product_id | INT UNSIGNED | FK → products.id; cho phép NULL |
| search_keyword | VARCHAR(255) | Cho phép NULL |
| created_at | DATETIME | Thời điểm sự kiện |

- view_product/add_to_cart phải có product_id; search_keyword NULL.
- search có từ khóa đã chuẩn hóa; product_id NULL.
- add_to_cart bắt buộc có cả user_id và product_id, chỉ ghi sau thao tác thành công của tài khoản đã đăng nhập; view_product/search được user_id NULL.
- Lượt sự kiện không mặc nhiên là số người duy nhất hoặc số sản phẩm mua.

## 4. Quan hệ và xóa dữ liệu

| FK | Tham chiếu | ON DELETE |
|---|---|---|
| categories.parent_id | categories.id | RESTRICT |
| products.category_id | categories.id | RESTRICT |
| product_images.product_id | products.id | CASCADE |
| product_images.color_id | colors.id | RESTRICT |
| product_variants.product_id | products.id | RESTRICT |
| product_variants.size_id | sizes.id | RESTRICT |
| product_variants.color_id | colors.id | RESTRICT |
| carts.user_id | users.id | CASCADE |
| cart_items.cart_id | carts.id | CASCADE |
| cart_items.variant_id | product_variants.id | CASCADE |
| orders.user_id | users.id | RESTRICT |
| orders.coupon_id | coupons.id | RESTRICT |
| order_items.order_id | orders.id | RESTRICT |
| order_items.variant_id | product_variants.id | RESTRICT |
| payment_transactions.order_id | orders.id | RESTRICT |
| payment_transactions.processed_by | users.id | RESTRICT |
| payment_transactions.original_transaction_id | payment_transactions.id | RESTRICT |
| order_status_history.order_id | orders.id | RESTRICT |
| order_status_history.changed_by | users.id | RESTRICT |
| user_events.user_id | users.id | SET NULL |
| user_events.product_id | products.id | RESTRICT |

- Các quan hệ trên là một–nhiều, trừ users–carts là một–không hoặc một do UNIQUE user_id.
- Danh mục con thuộc đúng một nhóm gốc; sản phẩm thuộc đúng một danh mục con.
- Đơn có ít nhất một order_item; đơn có thể chưa có giao dịch hoặc có nhiều lần thử.
- Sản phẩm/biến thể đã bán chuyển inactive. Sản phẩm có lịch sử hành vi giữ lại cho báo cáo.
- Chỉ xóa hẳn sản phẩm tạo nhầm, chưa có dữ liệu cần giữ; xử lý biến thể trước.
- Không có thao tác xóa đơn, giao dịch, lịch sử; tài khoản dùng khóa/kích hoạt.
- Xóa dòng ảnh không tự xóa tệp ảnh: PHP xử lý riêng, tránh để DB trỏ tới tệp đã mất nếu transaction thất bại.

## 5. Tính tiền và vận chuyển

```text
unit_price = làm tròn(original_price × (1 − discount_percent / 100))
             nếu chương trình giảm có hiệu lực;
             ngoài thời gian giảm thì bằng original_price.

subtotal = tổng(quantity × unit_price)
coupon_discount = mức giảm hợp lệ từ mã, không vượt subtotal
tiền hàng sau mã = subtotal − coupon_discount

shipping_fee = 0 nếu subtotal ≥ 500.000
               30.000 nếu subtotal < 500.000

total_amount = subtotal − coupon_discount + shipping_fee
```

Làm tròn đơn giá và giảm phần trăm đến đồng, thống nhất quy tắc 0,5 làm tròn lên cho số tiền không âm. Dùng xử lý số thập phân/số nguyên phù hợp; server tính lại toàn bộ, không tin tổng tiền từ trình duyệt.

| Tiền hàng sau giảm sản phẩm | Mã giảm | Sau mã | Ship | Tổng trả |
|---:|---:|---:|---:|---:|
| 520.000đ | 0đ | 520.000đ | 0đ | 520.000đ |
| 520.000đ | 50.000đ | 470.000đ | 0đ | 470.000đ |
| 500.000đ | 10.000đ | 490.000đ | 0đ | 490.000đ |
| 490.000đ | 50.000đ | 440.000đ | 30.000đ | 470.000đ |
| 500.000đ | 500.000đ | 0đ | 0đ | 0đ |

- 30.000 và 500.000 đặt trong cấu hình dùng chung; orders lưu phí thực tế tại lúc đặt.
- Không tính phí ship vào ngưỡng miễn ship; không tính lại đơn cũ khi chính sách thay đổi.
- Tính lại tổng khi đổi số lượng, áp dụng/gỡ mã. Nếu subtotal giữ nguyên, áp dụng/gỡ mã không làm đổi phí ship.
- Chưa triển khai mã miễn ship riêng.
- Mã fixed: coupon_discount = min(discount_value, subtotal). Mã percent: coupon_discount = min(làm tròn(subtotal × discount_value / 100), max_discount_amount, subtotal). Chỉ tính sau khi mã đã vượt qua mọi kiểm tra hiệu lực và lượt dùng.
- Khi subtotal < 500.000, hiển thị “Mua thêm Xđ tiền hàng sau giảm giá sản phẩm để được miễn phí vận chuyển”, với X = 500.000 − subtotal. Ví dụ subtotal = 470.000 thì X = 30.000; subtotal = 520.000 dù dùng mã 50.000 vẫn hiển thị miễn ship.
- Giao diện hiển thị riêng tiền hàng, giảm từ mã, phí vận chuyển và tổng thanh toán. Cùng một hàm tính tiền trên server phục vụ giỏ và đặt hàng.

### 5.1. Trường hợp tổng phải trả bằng 0

Quy tắc giảm tối đa bằng subtotal kết hợp miễn ship có thể tạo đơn 0đ. Để không phát sinh giao dịch giả:

- total_amount = 0 thì payment_status = not_required (không cần thanh toán), payment_expires_at = NULL; giữ payment_method khách đã chọn chỉ để tham chiếu.
- Tạo đơn pending, trừ kho và ghi lượt dùng mã như đơn thường; bỏ qua màn hình thanh toán, không tạo payment_transactions với amount = 0.
- Staff/admin được xác nhận và giao đơn not_required. Khi hoàn tất, giữ not_required và ghi completed_at; không ghi thu tiền COD.
- Hủy trước giao vẫn hoàn kho và trả lượt mã một lần; không tạo refund vì chưa thu tiền. Không đổi not_required thành refunded.
- CHECK nội dòng: total_amount = 0 khi và chỉ khi payment_status = not_required. Đơn có total_amount > 0 chỉ dùng unpaid/paid/refunded.
- Báo cáo lượng bán gồm đơn completed/not_required; doanh thu tiền hàng của chúng bằng 0. Tách số đơn 0đ để giải thích báo cáo.


## 6. Trạng thái đơn và thanh toán

| Từ | Sang | Điều kiện |
|---|---|---|
| pending | confirmed | Staff/admin xác nhận; online phải paid hoặc đơn 0đ có not_required |
| pending | cancelled | Khách sở hữu đơn, staff/admin, hoặc tác vụ hết hạn |
| confirmed | shipping | Staff/admin bắt đầu giao |
| confirmed | cancelled | Staff/admin hủy trước giao |
| shipping | completed | Đã giao; COD tổng > 0 đồng thời xác nhận thu tiền; đơn 0đ giữ not_required |
| completed | Không đổi tiếp | Kết thúc trong phạm vi bài |
| cancelled | Không đổi tiếp | Mua lại phải tạo đơn mới |

- Thanh toán online thành công không tự confirmed; vẫn pending chờ nhân viên.
- Khách hủy pending đã paid thì hoàn kho và xử lý hoàn tiền mô phỏng.
- Chưa thanh toán thành công: unpaid; đã thanh toán: paid; hoàn tiền thành công: refunded; không cần thu tiền cho đơn 0đ: not_required.
- Hủy đã paid nhưng hoàn chưa thành công: đơn cancelled, thanh toán vẫn paid, nằm trong danh sách cần hoàn tiền.
- COD hoàn tất: ghi payment success và cập nhật completed/paid trong một transaction.
- Hoàn tiền chỉ cho đơn cancelled đã paid; số tiền hoàn toàn bộ bao gồm ship đã thu.
- Hoàn thất bại được thử lại; trạng thái đơn không chuyển ngược.

## 7. Transaction và xử lý đồng thời

### 7.1. Giao thức khóa chung

Đây là thứ tự thiết kế cho các thao tác có cùng tài nguyên; chỉ khóa những nhóm cần dùng và không quay ngược thứ tự:

1. users: tài khoản thực hiện và chủ đơn nếu cần, theo id tăng dần.
2. carts và cart_items nếu thao tác giỏ/checkout; khóa dòng carts trước khi sửa các dòng con.
3. coupons nếu đơn dùng mã.
4. orders nếu xử lý đơn đã tồn tại.
5. categories → products → product_variants; trong mỗi bảng theo id tăng dần.
6. Các dòng phụ thuộc cần ghi/sửa: order_items, payment_transactions, order_status_history, product_images.

- Với đơn đã tồn tại, có thể đọc trước user_id/coupon_id để xác định tài nguyên; các trường này bất biến sau tạo. Sau khi khóa theo thứ tự, đọc lại đơn và kiểm tra lại quyền/trạng thái, không dựa vào lần đọc ban đầu để quyết định.
- Mọi thao tác thêm/sửa giỏ khóa carts; mọi thao tác ảnh/biến thể khóa products; mọi thao tác giao dịch khóa orders trước.
- Quản trị sửa giá, trạng thái, danh mục hoặc kho phải tuân theo thứ tự tương ứng để checkout không đọc dữ liệu thay đổi giữa chừng. Đổi danh mục sản phẩm phải khóa cả danh mục cũ/mới theo id trước sản phẩm và xác minh lại.
- Kiểm tra admin cuối cùng: khóa tập tài khoản admin liên quan theo id trước khi đếm và đổi quyền/trạng thái; chạy kiểm thử thao tác đồng thời.
- Dùng READ COMMITTED cho các transaction nghiệp vụ này. Không gọi dịch vụ ngoài hoặc chờ người dùng trong transaction.
- Thứ tự khóa giảm xung đột nhưng không cam kết không bao giờ deadlock. Nếu deadlock, rollback toàn bộ, thử lại tối đa 3 lần với khoảng lùi ngắn; vẫn lỗi thì báo thử lại, không commit một phần.

### 7.2. Tạo đơn và kiểm tra mã giảm

1. Kiểm tra dữ liệu đầu vào, phiên đăng nhập và checkout_token. Nếu token đã tạo đơn của đúng người dùng, trả lại đơn đó.
2. Bắt đầu transaction. Khóa tài khoản, giỏ và các dòng giỏ; kiểm tra tài khoản active, giỏ không rỗng, số lượng dương.
3. Nếu có mã, khóa dòng coupons. Bước này chỉ khóa và đọc mã, chưa kiểm tra min_order_amount khi chưa tính tiền hàng.
4. Khóa danh mục, sản phẩm, biến thể theo giao thức. Kiểm tra liên kết, trạng thái bán và tồn kho; đọc tên/size/màu để chụp dữ liệu nhất quán.
5. Tính unit_price và subtotal trên server theo giá biến thể và chương trình giảm đang có hiệu lực.
6. Kiểm tra mã active, thời hạn, subtotal tối thiểu, tổng lượt và lượt mỗi user từ các đơn chưa cancelled. Giữ khóa coupon đến commit. Tính coupon_discount, shipping_fee, total_amount.
7. Nếu giá/tổng tiền khác nội dung khách vừa xác nhận, rollback và yêu cầu khách xem lại tổng mới, không tự chấp nhận số tiền khác.
8. Tạo orders, order_items; trừ từng biến thể có điều kiện; ghi lịch sử tạo và xóa các dòng giỏ đã đặt trong cùng transaction.
9. Commit nếu tất cả thành công. Bất kỳ lỗi nào rollback toàn bộ. UNIQUE checkout_token là lớp bảo vệ chống lặp; gặp token trùng thì rollback rồi trả đơn hiện có đúng chủ sở hữu.

```sql
UPDATE product_variants
SET stock = stock - :qty
WHERE id = :variant_id AND stock >= :qty;
```

Kiểm tra số dòng bị ảnh hưởng: 0 thì không tạo đơn thành công. Số lượng đã được xác thực > 0 trước câu lệnh. Lượt mã chỉ được xác nhận cùng commit tạo đơn; đơn hủy trả lượt thông qua trạng thái, không có used_count để cập nhật riêng.

### 7.3. Hủy đơn và hoàn kho

1. Đọc thông tin bất biến để xác định tài nguyên, bắt đầu transaction; khóa users, coupon nếu có, rồi orders.
2. Kiểm tra quyền và đọc lại trạng thái: khách chỉ pending; staff/admin pending hoặc confirmed. Tác vụ hệ thống chỉ hủy online pending/unpaid đã hết hạn.
3. Nếu đã cancelled, kết thúc mà không cộng kho thêm. Nếu không hợp lệ, từ chối.
4. Đọc chi tiết bất biến, khóa products và product_variants theo id; hoàn đúng quantity của từng dòng.
5. Chuyển cancelled, ghi cancelled_at/cancel_reason và lịch sử; commit cùng việc hoàn kho. Khóa coupon giữ tới commit, bảo đảm thao tác đặt mới thấy lượt được giải phóng nhất quán.
6. Nếu đã paid, đưa đơn vào danh sách cần hoàn tiền. Admin thực hiện refund theo luồng riêng; không đánh dấu refunded trước khi có refund success.

Nhân viên xác nhận hoặc bắt đầu giao không trừ kho lần nữa. Việc hoàn kho không được phụ thuộc vào refund đã thành công hay chưa.

### 7.4. Thanh toán, COD, hoàn tiền và hết hạn

**Online:** mỗi lần thử có transaction_code riêng; gửi lại cùng lần thử dùng lại mã. Khóa tài khoản cần kiểm tra và orders; sau đó mới ghi/cập nhật payment_transactions. Chấp nhận success chỉ khi đơn pending/unpaid, phương thức online, hiện tại < payment_expires_at và amount đúng total_amount. Kiểm tra chưa có payment success. Cập nhật giao dịch và orders.payment_status trong cùng transaction. Kết quả cuối không được đổi bởi yêu cầu lặp.

**COD:** staff/admin khóa tài khoản và orders; chỉ từ shipping/unpaid mới ghi nhận thu tiền và completed/paid. Ghi payment success, processed_by, completed_at và lịch sử trong cùng transaction. Không áp dụng kiểm tra pending hoặc hạn online cho COD.

**Hoàn tiền:** admin khóa tài khoản và orders, kiểm tra cancelled/paid; xác minh payment gốc success cùng đơn và chưa có refund success. Tạo lần refund toàn bộ, lưu processed_by và kết quả. Nếu success thì cùng transaction đổi orders.payment_status sang refunded; nếu failed giữ paid để thử lại. original_transaction_id phải trỏ payment gốc, không trỏ lần refund khác.

**Hết hạn:** tác vụ chạy đề xuất mỗi phút, lấy danh sách ứng viên online pending/unpaid đã hết hạn. Mỗi đơn dùng transaction hủy ở mục 7.3, kiểm tra lại trạng thái và thời gian sau khi khóa. Không hủy đơn đã paid, không nhận payment success sau hạn. Thanh toán thất bại không gia hạn; thời hạn cố định 30 phút từ created_at.

30 phút là hạn thanh toán; trạng thái hủy có thể được cập nhật ở lượt tác vụ kế tiếp. Trên môi trường demo phải chạy tác vụ định kỳ riêng; chỉ thêm payment_expires_at không làm DB tự hủy. Nếu tác vụ dừng, đơn quá hạn vẫn không được thanh toán và sẽ được xử lý khi tác vụ chạy lại.

### 7.5. Các bảo vệ bổ sung

| Tình huống | Xử lý |
|---|---|
| Giá đổi khi đặt | Đọc nhất quán trong transaction, chốt snapshot |
| Admin chỉnh kho khi có người mua | Khóa và cập nhật theo lượng tăng/giảm; không ghi đè số cũ |
| Thu/hoàn tiền lặp | Mã UNIQUE, khóa đơn, kiểm tra trạng thái kết thúc |
| Hai ảnh chính | Khóa sản phẩm trong mọi thao tác ảnh chính |
| Sửa sản phẩm/hồ sơ | Không thay snapshot đơn cũ |
| Mã/giá hết hiệu lực khi checkout | Server kiểm tra và báo khách trước khi chấp nhận đơn theo dữ liệu mới |

## 8. Chỉ mục

Ngoài PK/UNIQUE và index phục vụ FK, đề xuất:

| Bảng | Index |
|---|---|
| products | (category_id, status) |
| products | (status, is_featured) |
| product_variants | (product_id, status, original_price) |
| orders | (user_id, created_at, id) |
| orders | (status, created_at, id) |
| orders | (status, payment_status, completed_at) |
| orders | (status, payment_status, payment_expires_at) |
| orders | (coupon_id, status, user_id) |
| payment_transactions | (order_id, created_at, id) |
| order_status_history | (order_id, created_at, id) |
| user_events | (event_type, created_at) |
| user_events | (product_id, event_type, created_at) |

Không tạo trùng index. Đánh giá truy vấn thực tế với dữ liệu mẫu trước khi mở rộng. Tìm kiếm LIKE '%từ khóa%' dùng cho quy mô bài tập; không kỳ vọng index B-tree thông thường tối ưu kiểu tìm này.

## 9. Phân quyền

| Chức năng | Customer | Staff | Admin |
|---|---|---|---|
| Mua hàng, sửa hồ sơ, xem đơn của mình | Có | Có | Có |
| Hủy đơn của mình khi pending | Có | Có | Có |
| Quản lý danh mục con, sản phẩm, ảnh, biến thể, kho | Không | Có | Có |
| Xem và xử lý đơn khách hàng | Không | Có | Có |
| Quản lý mã giảm giá | Không | Không | Có |
| Quản lý tài khoản/phân quyền | Không | Không | Có |
| Xem báo cáo quản trị | Không | Không | Có |
| Xử lý hoàn tiền mô phỏng | Không | Không | Có |

PHP kiểm tra quyền ở từng yêu cầu và chủ sở hữu giỏ/đơn. Ẩn nút không thay thế kiểm tra server. Vai trò cố định lưu users.role; chưa cần bảng quyền riêng.

## 10. Báo cáo

| Báo cáo | Quy tắc |
|---|---|
| Số sản phẩm | Đếm products, không đếm biến thể thành sản phẩm |
| Tài khoản | Đếm users, tách vai trò/trạng thái khi cần |
| Đơn hàng | Đếm theo trạng thái; khoảng tạo đơn dùng created_at |
| Doanh thu bán hàng | completed và payment_status thuộc paid/not_required; tổng subtotal − coupon_discount; theo completed_at; không gồm ship; đơn not_required góp 0đ |
| Bán chạy | Tổng quantity từ chi tiết các đơn đủ điều kiện doanh thu |
| Sản phẩm có số lượng bán thấp trong kỳ | Sản phẩm mở bán trước đầu kỳ; xếp lượng bán thấp trước; gồm cả bán 0; hiển thị tồn/trạng thái hiện tại |
| Tồn kho thấp | Biến thể được phép bán có stock ≤ 5 |
| Xem nhiều | Đếm view_product |
| Từ khóa phổ biến | Nhóm search theo từ khóa chuẩn hóa |
| Lượt thêm giỏ | Đếm add_to_cart, không phải lượng mua |

Báo cáo số lượng bán thấp chưa xác định nguyên nhân: không có lịch sử thời gian còn hàng/ngừng bán nên không kết luận sản phẩm kém hấp dẫn chỉ từ lượng bán.

Khi truy vấn doanh thu không cộng tổng đơn lặp lại sau khi JOIN nhiều chi tiết/giao dịch. Báo cáo bán chậm phải giữ cả sản phẩm không có chi tiết bán phù hợp kỳ. Số liệu demo được ghi rõ là mô phỏng.

## 11. Nơi thực thi ràng buộc

| Loại | Nơi thực thi |
|---|---|
| PK, FK, UNIQUE | Database |
| Số lượng, giá, phần trăm, thời gian hợp lệ | CHECK nếu môi trường hỗ trợ/thực thi; luôn kiểm tra PHP |
| Quyền, chủ sở hữu, trạng thái được chuyển | PHP server |
| Chỉ danh mục con, hai nhóm gốc, điều kiện mở bán | PHP server; dữ liệu gốc tạo bằng seed |
| Snapshot và công thức tổng tiền | PHP trong transaction; CHECK nội dòng cho công thức nếu phù hợp |
| Một ảnh chính | PHP với khóa sản phẩm; bổ sung unique có điều kiện nếu chọn triển khai |
| Một lần thu/hoàn thành công | Khóa đơn + kiểm tra PHP; bổ sung unique có điều kiện ở DDL |
| Tổng chi tiết khớp subtotal | Quy trình tạo đơn trong transaction và kiểm thử |

FK không tự bảo đảm các quy tắc liên bảng như giao dịch hoàn trỏ đúng payment success của cùng đơn; PHP phải kiểm tra rõ.

## 12. Ca kiểm thử bắt buộc

Đây là kế hoạch kiểm thử, chưa phải kết quả đã chạy. Khi thực hiện, bổ sung cột kết quả thực tế, Đạt/Không đạt và bằng chứng.

| Ca kiểm tra | Kết quả mong đợi |
|---|---|
| Trùng sản phẩm–size–màu | Bị từ chối |
| Quantity = 0, giá âm, sai FK | Bị từ chối |
| Hai lượt mua món cuối | Một lượt thành công |
| Một món trong đơn không đủ | Rollback cả đơn, không trừ phần còn lại |
| Hai người dùng lượt mã cuối | Một đơn dùng được mã |
| Hai yêu cầu cùng checkout_token | Một đơn, một lần trừ kho |
| Hủy hai lần | Hoàn kho một lần |
| Thu tiền gửi lặp | Một payment success |
| Hoàn tiền gửi lặp | Một refund success |
| Online hết hạn | Không nhận trả tiền mới; tác vụ hủy và hoàn kho |
| Thanh toán đồng thời hết hạn | Chỉ một kết quả hợp lệ; không paid và hoàn kho nhầm |
| Hủy online paid | Hoàn kho một lần; hoàn tiền toàn bộ có giao dịch |
| Refund thất bại rồi thử lại | Chưa thành công vẫn paid; thành công chuyển refunded |
| COD hoàn tất | completed/paid; có trong doanh thu |
| Subtotal 520.000 − mã 50.000 | Ship 0; tổng 470.000 |
| Subtotal 500.000 − mã 10.000 | Ship 0; tổng 490.000 |
| Subtotal 499.999, không mã | Ship 30.000; tổng 529.999 |
| Subtotal đúng 500.000 | Miễn ship, kể cả sau mã còn dưới ngưỡng |
| Áp/gỡ mã khi subtotal không đổi | Phí ship không đổi |
| Mã percent thiếu mức giảm tối đa | Từ chối lưu mã |
| Mức giảm vượt subtotal | Chặn mức giảm thực tế tại subtotal |
| Subtotal 500.000, giảm hết tiền hàng | Tổng 0, not_required, không có giao dịch; được xác nhận và giao |
| Hủy đơn 0đ | Hoàn kho/trả lượt mã một lần, không refund |
| Đơn 0đ hoàn tất | Có lượng bán, doanh thu 0, không thu COD |
| Đổi tên/giá/địa chỉ hiện tại | Đơn cũ giữ nguyên |
| Hai thao tác chọn ảnh chính | Tối đa một ảnh chính |
| Customer gọi chức năng admin | Bị từ chối |
| Chưa đăng nhập yêu cầu thêm/xem/sửa giỏ | Không ghi giỏ; yêu cầu đăng nhập |
| Xem đơn người khác | Bị từ chối |
| Khóa admin cuối cùng | Bị từ chối |
| Đổi size/màu của biến thể đã bán | Bị từ chối; phải tạo biến thể mới |
| Gán ảnh màu không thuộc sản phẩm | Bị từ chối |
| Chọn màu chưa có ảnh riêng | Dùng bộ ảnh chung |
| Ghi add_to_cart không có user | Bị từ chối |
| Admin hoàn tiền | Ghi đúng processed_by, thời gian và kết quả |
| Giá thay đổi trước xác nhận cuối | Không tự tạo đơn với tổng khác; yêu cầu xem lại |
| Tác vụ hết hạn dừng rồi chạy lại | Không nhận thanh toán quá hạn; hủy và hoàn đúng một lần khi chạy lại |

## 13. Bước triển khai tiếp theo

1. Lấy phiên bản DB thực tế bằng `SELECT VERSION();`.
2. Đối chiếu ERD trong phụ lục với SQL triển khai; kiểm tra đầy đủ FK và tính tùy chọn.
3. Viết schema SQL, CHECK, UNIQUE, FK, index; kiểm tra tính tương thích.
4. Tạo seed: hai nhóm gốc, danh mục con, sizes/colors, tài khoản demo, sản phẩm/biến thể/ảnh/mã.
5. Lập trình transaction PHP và tác vụ hết hạn theo giao thức khóa mục 7; kiểm thử đồng thời.
6. Chạy các ca kiểm thử, sửa lỗi và ghi kết quả thực tế.

Tài liệu này là thiết kế của môn Lập trình Web. Chưa khẳng định database đã chạy đúng hoặc đã kiểm thử cho đến khi hoàn thành các bước triển khai trên.

## 14. ERD theo nhóm chức năng


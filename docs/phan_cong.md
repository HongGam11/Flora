# Phân công Flora theo hai đợt

Các task dưới đây là kế hoạch, chưa được đánh dấu hoàn thành. TV1 là nhóm trưởng; TV2 đến TV6 điền tên thật khi nhóm chốt.

## TV1 Nhóm trưởng phụ trách nền tảng tài khoản phân quyền và tích hợp

Phạm vi file: index.php; bootstrap; core; database; AuthController; ProfileController; AdminUserController; UserModel; layouts; docs.

### Đợt 1

| Mã | Công việc | Kết quả cần đạt | Trạng thái | Hạn |
|---|---|---|---|---|
| A1 | Rà và chốt bộ khung MVC dùng chung | Thống nhất cấu trúc, cách nạp file và kết nối đang chạy. | Chưa làm | Chưa chốt |
| A2 | Hoàn thiện router và danh sách route | Mỗi route có controller, HTTP method và quyền; URL lạ trả 404. | Chưa làm | Chưa chốt |
| A3 | Chốt cấu hình và hướng dẫn XAMPP | Cả sáu máy kết nối được database flora có dữ liệu mẫu. | Chưa làm | Chưa chốt |
| A4 | Chuẩn bị bố cục và hàm dùng chung | Layout nạp CSS của TV2; có thông báo, escape và CSRF. | Chưa làm | Chưa chốt |
| A5 | Làm đăng ký và kiểm tra dữ liệu | Email không trùng; băm mật khẩu; chỉ tạo customer. | Chưa làm | Chưa chốt |
| A6 | Làm đăng nhập đăng xuất và phiên | Đăng nhập đúng, đổi mã phiên; đăng xuất hết quyền phiên. | Chưa làm | Chưa chốt |
| A7 | Làm kiểm tra đăng nhập vai trò và trạng thái | Chặn tài khoản khóa, sai vai trò và yêu cầu trái quyền. | Chưa làm | Chưa chốt |
| A8 | Công bố giao tiếp và hỗ trợ ghép đợt 1 | TV2 đến TV6 dùng cùng phiên, quyền, route và thông báo. | Chưa làm | Chưa chốt |

### Đợt 2

| Mã | Công việc | Kết quả cần đạt | Trạng thái | Hạn |
|---|---|---|---|---|
| A9 | Làm hồ sơ cá nhân và đổi mật khẩu | Chỉ sửa được hồ sơ của người đang đăng nhập. | Chưa làm | Chưa chốt |
| A10 | Làm admin xem tìm kiếm khóa tài khoản | Giữ lịch sử đơn khi khóa; không xóa tài khoản qua giao diện. | Chưa làm | Chưa chốt |
| A11 | Làm tạo staff và thay đổi vai trò | Chỉ admin; bảo vệ admin hoạt động cuối cùng, kể cả đồng thời. | Chưa làm | Chưa chốt |
| A12 | Rà quyền CSRF phiên và dữ liệu hiển thị | Kiểm tra từng route; customer không gọi được quản trị. | Chưa làm | Chưa chốt |
| A13 | Ghép bản chung và xử lý xung đột | Các luồng liên thông; cấu hình riêng không bị ghi đè. | Chưa làm | Chưa chốt |
| A14 | Đồng bộ SQL ERD hướng dẫn và báo cáo nhóm | Đúng bản sáu người; chạy được trên máy khác. | Chưa làm | Chưa chốt |

Bàn giao A1 đến A4 trước; A6 và A7 cần trước khi ghép giỏ, checkout và quản trị.

TV2 kiểm tra chéo TV1. TV1 kiểm tra chéo TV5 và chốt nghiệm thu tích hợp.

## TV2 Phụ trách giao diện cửa hàng dữ liệu xem và báo cáo quản trị

Phạm vi file: HomeController; ProductController; AdminDashboardController; AdminReportController; ReportModel; UserEventModel; views home products dashboard reports; assets/css; common.js; product.js.

### Đợt 1

| Mã | Công việc | Kết quả cần đạt | Trạng thái | Hạn |
|---|---|---|---|---|
| B1 | Chốt mẫu giao diện cùng nhóm trưởng | Có mẫu màu, font, nút, form, bảng và thông báo. | Chưa làm | Chưa chốt |
| B2 | Làm CSS chung khách và quản trị | Các thành viên dùng chung style; thống nhất tên class. | Chưa làm | Chưa chốt |
| B3 | Dùng model TV3 để hiển thị danh mục | Menu nam nữ đúng database; không tự giữ danh sách riêng. | Chưa làm | Chưa chốt |
| B4 | Làm danh sách và trang chủ sản phẩm | Ảnh chính, tên, giá; có sản phẩm nổi bật và mới. | Chưa làm | Chưa chốt |
| B5 | Làm tìm theo tên và xem theo danh mục | Kết quả đúng; không có dữ liệu thì thông báo rõ. | Chưa làm | Chưa chốt |
| B6 | Làm chi tiết và chọn size màu | Đúng variant_id, giá, ảnh, tồn; tổ hợp không bán bị chặn. | Chưa làm | Chưa chốt |
| B7 | Nối nút thêm giỏ với TV4 | Gửi variant_id và quantity; dùng đăng nhập của TV1. | Chưa làm | Chưa chốt |

### Đợt 2

| Mã | Công việc | Kết quả cần đạt | Trạng thái | Hạn |
|---|---|---|---|---|
| B8 | Bổ sung lọc giá sắp xếp và phân trang | Giữ điều kiện khi chuyển trang; cột sắp xếp có danh sách cho phép. | Chưa làm | Chưa chốt |
| B9 | Hoàn thiện ảnh màu và giao diện điện thoại | Có ảnh chung thay thế; bố cục không tràn. | Chưa làm | Chưa chốt |
| B10 | Ghi lượt xem và từ khóa tìm kiếm | UserEventModel ghi đúng dạng; dùng chung với sự kiện thêm giỏ của TV4. | Chưa làm | Chưa chốt |
| B11 | Làm dashboard và báo cáo đơn doanh thu | Đúng kỳ và trạng thái; không cộng trùng sau JOIN. | Chưa làm | Chưa chốt |
| B12 | Làm bán chạy bán thấp và tồn thấp | Gồm sản phẩm bán 0 khi phù hợp; đối chiếu tồn hiện tại. | Chưa làm | Chưa chốt |
| B13 | Làm báo cáo xem tìm kiếm và thêm giỏ | Phân biệt số sự kiện với số người và số đơn. | Chưa làm | Chưa chốt |
| B14 | Rà giao diện chung và số liệu báo cáo | Hỗ trợ TV4 đến TV6 sửa trình bày; báo cáo khớp đơn thử. | Chưa làm | Chưa chốt |

Nhận model sản phẩm từ TV3 và công thức giá từ TV4. Có thể dựng view trước khi có đủ model.

TV6 kiểm tra chéo TV2. TV2 kiểm tra chéo TV1.

## TV3 Phụ trách quản trị danh mục sản phẩm ảnh biến thể và kho

Phạm vi file: AdminCategoryController; AdminProductController; AdminVariantController; CategoryModel; ProductModel; ProductVariantModel; ProductImageModel; views admin categories products variants.

### Đợt 1

| Mã | Công việc | Kết quả cần đạt | Trạng thái | Hạn |
|---|---|---|---|---|
| C1 | Công bố model và dữ liệu sản phẩm dùng chung | TV2 nhận danh mục, sản phẩm, ảnh, biến thể, giá, trạng thái và stock. | Chưa làm | Chưa chốt |
| C2 | Làm danh sách và form danh mục con | Thêm sửa bật tắt; không đổi nhóm gốc hoặc tạo cấp ba. | Chưa làm | Chưa chốt |
| C3 | Làm danh sách và form thông tin sản phẩm | Kiểm tra tên, danh mục; lưu và hiển thị lại đúng. | Chưa làm | Chưa chốt |
| C4 | Làm thêm sửa biến thể size màu | Không trùng tổ hợp; kiểm tra giá và trạng thái. | Chưa làm | Chưa chốt |
| C5 | Làm quản trị URL ảnh và ảnh chính | Tối đa một ảnh chính; ảnh màu thuộc màu sản phẩm. | Chưa làm | Chưa chốt |
| C6 | Làm điều chỉnh tồn kho theo lượng thay đổi | Không ghi đè số tồn cũ; phối hợp khóa với TV5 và TV6. | Chưa làm | Chưa chốt |
| C7 | Làm bật tắt và điều kiện mở bán | Có ảnh chính và biến thể active mới mở bán. | Chưa làm | Chưa chốt |
| C8 | Bàn giao sản phẩm để thử mua COD | Staff/admin tạo được sản phẩm có ảnh và biến thể bán. | Chưa làm | Chưa chốt |

### Đợt 2

| Mã | Công việc | Kết quả cần đạt | Trạng thái | Hạn |
|---|---|---|---|---|
| C9 | Làm quản trị giảm giá và sản phẩm nổi bật | Khoảng thời gian hợp lệ; published_at giữ ngày mở bán đầu. | Chưa làm | Chưa chốt |
| C10 | Hoàn thiện nhiều ảnh và ảnh theo màu | Đổi ảnh chính an toàn; không để ảnh màu không còn thuộc sản phẩm. | Chưa làm | Chưa chốt |
| C11 | Hoàn thiện ngừng bán và bảo toàn lịch sử | Không đổi ý nghĩa size màu của biến thể đã bán. | Chưa làm | Chưa chốt |
| C12 | Kiểm thử sửa giá trạng thái và kho đồng thời | Không mất cập nhật; checkout đọc và kiểm tra nhất quán. | Chưa làm | Chưa chốt |
| C13 | Kiểm thử quản trị và sửa lỗi phần sản phẩm | Đủ quyền, dữ liệu lỗi, dữ liệu rỗng và giao diện nhỏ. | Chưa làm | Chưa chốt |

C1 cần sớm cho TV2 TV4 TV5. C6 cần thống nhất quy tắc khóa với TV5 và TV6 trước khi ghép.

TV5 kiểm tra chéo TV3. TV3 kiểm tra chéo TV4.

## TV4 Phụ trách giỏ hàng tính tiền và toàn bộ mã giảm giá

Phạm vi file: CartController; AdminCouponController; CartModel; CouponModel; PricingService; views cart và admin coupons; cart.js.

### Đợt 1

| Mã | Công việc | Kết quả cần đạt | Trạng thái | Hạn |
|---|---|---|---|---|
| D1 | Chốt giao tiếp nút thêm giỏ với TV2 | Nhận variant_id, quantity; lấy user_id từ phiên TV1. | Chưa làm | Chưa chốt |
| D2 | Làm thêm giỏ và cộng số lượng món đã có | Yêu cầu đăng nhập; kiểm tra bán được và số lượng hợp lệ. | Chưa làm | Chưa chốt |
| D3 | Làm trang giỏ theo người sở hữu | Tên, ảnh, size, màu, lượng, giá và thành tiền đúng. | Chưa làm | Chưa chốt |
| D4 | Làm sửa số lượng và xóa món | Chặn số lượng sai; không sửa giỏ người khác. | Chưa làm | Chưa chốt |
| D5 | Viết công thức giá và vận chuyển dùng chung | Giảm sản phẩm đúng; miễn ship trên subtotal trước mã từ 500.000đ. | Chưa làm | Chưa chốt |
| D6 | Bàn giao giỏ và PricingService cho TV5 | Có dữ liệu dòng giỏ và kết quả tính tiền rõ ràng. | Chưa làm | Chưa chốt |
| D7 | Ghi sự kiện thêm giỏ sau thành công | Dùng UserEventModel TV2; bắt buộc có user_id và product_id. | Chưa làm | Chưa chốt |

### Đợt 2

| Mã | Công việc | Kết quả cần đạt | Trạng thái | Hạn |
|---|---|---|---|---|
| D8 | Làm admin thêm sửa bật tắt mã giảm | Kiểm tra loại mã, giá trị, thời gian, điều kiện và giới hạn. | Chưa làm | Chưa chốt |
| D9 | Làm áp và gỡ mã trên giỏ hoặc checkout | Thông báo rõ; giá trị giảm không vượt subtotal và không giảm ship. | Chưa làm | Chưa chốt |
| D10 | Kiểm tra hiệu lực và giới hạn giá trị mã | Đúng fixed/percent, tiền tối thiểu và max_discount_amount. | Chưa làm | Chưa chốt |
| D11 | Phối hợp TV5 kiểm tra lượt mã trong transaction | Khóa coupon; không vượt tổng lượt và lượt mỗi user. | Chưa làm | Chưa chốt |
| D12 | Hoàn thiện công thức đơn 0đ và làm tròn | Kết quả đúng; TV5 xử lý not_required khi total bằng 0. | Chưa làm | Chưa chốt |
| D13 | Kiểm thử giỏ mã ngưỡng ship và tranh lượt cuối | Có kết quả thực tế; công thức giỏ và tạo đơn thống nhất. | Chưa làm | Chưa chốt |

D5 và D6 bàn giao trước khi TV5 tạo đơn. D11 là logic tham gia transaction do TV5 quản lý, không tự commit.

TV3 kiểm tra chéo TV4. TV4 kiểm tra chéo TV6.

## TV5 Phụ trách checkout tạo đơn và theo dõi đơn phía khách

Phạm vi file: CheckoutController; OrderController; OrderModel; CheckoutService; views checkout orders; checkout.js. Dùng CartModel và PricingService TV4, lịch sử TV6.

### Đợt 1

| Mã | Công việc | Kết quả cần đạt | Trạng thái | Hạn |
|---|---|---|---|---|
| E1 | Chốt dữ liệu giỏ và kết quả giá với TV4 | Dùng chung kết nối, dữ liệu biến thể và công thức tiền. | Chưa làm | Chưa chốt |
| E2 | Làm form người nhận và phương thức COD | Kiểm tra tên, điện thoại, địa chỉ, ghi chú ở PHP. | Chưa làm | Chưa chốt |
| E3 | Cấp checkout_token gắn đúng người dùng | Gửi lại cùng token trả lại đơn cũ đúng chủ. | Chưa làm | Chưa chốt |
| E4 | Khóa và kiểm tra lại tài khoản giỏ giá trạng thái kho | Không tin tổng từ trình duyệt; phát hiện thay đổi trước đặt. | Chưa làm | Chưa chốt |
| E5 | Lưu đơn và chi tiết dạng snapshot | Tên size màu giá người nhận được giữ tại lúc mua. | Chưa làm | Chưa chốt |
| E6 | Gom tạo đơn trừ kho lịch sử và xóa dòng giỏ | Cùng transaction; một món thiếu thì rollback toàn bộ. | Chưa làm | Chưa chốt |
| E7 | Làm trang đặt thành công và danh sách chi tiết đơn | Có mã đơn; chỉ xem đơn của chính người đăng nhập. | Chưa làm | Chưa chốt |
| E8 | Gắn nút hủy vào OrderService của TV6 | Không viết hàm hoàn kho khác; chỉ khách pending được hủy. | Chưa làm | Chưa chốt |
| E9 | Kiểm thử trùng đơn tranh món cuối và rollback | Chỉ một đơn với cùng token; không stock âm hoặc trừ một phần. | Chưa làm | Chưa chốt |

### Đợt 2

| Mã | Công việc | Kết quả cần đạt | Trạng thái | Hạn |
|---|---|---|---|---|
| E10 | Ghép mã giảm và lượt dùng với TV4 | Chốt giá và lượt mã trong transaction tạo đơn. | Chưa làm | Chưa chốt |
| E11 | Xử lý giá thay đổi trước xác nhận cuối | Rollback; yêu cầu xem lại giá hoặc tổng mới. | Chưa làm | Chưa chốt |
| E12 | Thêm lựa chọn online và chuyển màn hình TV6 | Lưu phương thức; hạn thanh toán từ created_at cộng 30 phút. | Chưa làm | Chưa chốt |
| E13 | Xử lý đơn 0đ và các trường hợp checkout lỗi | not_required; bỏ qua thu tiền; giỏ rỗng hoặc ngừng bán bị chặn. | Chưa làm | Chưa chốt |
| E14 | Hoàn thiện theo dõi lịch sử và trả tiền lại | Nút và thông tin đúng trạng thái, quyền và hạn của TV6. | Chưa làm | Chưa chốt |
| E15 | Kiểm thử đồng thời và bàn giao luồng khách | Qua các ca giá mã kho token quyền và phối hợp thanh toán. | Chưa làm | Chưa chốt |

Nhận phiên quyền từ TV1; biến thể TV3; giỏ và giá TV4; lịch sử và hàm hủy TV6. Bàn giao đơn đã tạo cho TV6.

TV1 kiểm tra chéo TV5. TV5 kiểm tra chéo TV3.

## TV6 Phụ trách xử lý đơn thanh toán hoàn tiền và tác vụ hết hạn

Phạm vi file: PaymentController; AdminOrderController; OrderService; PaymentService; PaymentTransactionModel; OrderStatusHistoryModel; views payments admin orders; scripts/expire_orders.php.

### Đợt 1

| Mã | Công việc | Kết quả cần đạt | Trạng thái | Hạn |
|---|---|---|---|---|
| F1 | Chốt giao tiếp và hàm lịch sử với TV5 | TV5 tạo được lịch sử ban đầu; quy định hàm hủy và kết quả. | Chưa làm | Chưa chốt |
| F2 | Làm quản trị danh sách và chi tiết đơn | Staff/admin tìm lọc xem người nhận hàng tiền và lịch sử. | Chưa làm | Chưa chốt |
| F3 | Viết kiểm tra chuyển trạng thái | Không nhảy tùy ý hoặc mở lại completed/cancelled. | Chưa làm | Chưa chốt |
| F4 | Làm xác nhận và bắt đầu giao | Cập nhật cùng lịch sử; không trừ kho lần nữa. | Chưa làm | Chưa chốt |
| F5 | Làm hoàn tất COD và ghi thu tiền | Shipping sang completed/paid và payment success cùng transaction. | Chưa làm | Chưa chốt |
| F6 | Viết hủy đơn và hoàn kho dùng chung | Đúng chủ, quyền, trạng thái, lý do; trả lượt mã qua cancelled. | Chưa làm | Chưa chốt |
| F7 | Chặn hủy lặp và bàn giao cho TV5 | Hoàn kho một lần; nút khách và admin gọi cùng nghiệp vụ. | Chưa làm | Chưa chốt |

### Đợt 2

| Mã | Công việc | Kết quả cần đạt | Trạng thái | Hạn |
|---|---|---|---|---|
| F8 | Làm màn hình thanh toán mô phỏng | Có thành công thất bại; ghi rõ mô phỏng, không thu tiền thật. | Chưa làm | Chưa chốt |
| F9 | Lưu từng lần thử và xử lý kết quả | Giữ thất bại; tối đa một payment success cho mỗi đơn. | Chưa làm | Chưa chốt |
| F10 | Làm thử lại trước hạn và chặn trả quá hạn | Không gia hạn; đơn đã hủy hoặc trả rồi không thu lại. | Chưa làm | Chưa chốt |
| F11 | Làm tác vụ định kỳ hủy đơn hết hạn | Kiểm tra lại sau khóa; gọi hủy chung; có hướng dẫn chạy. | Chưa làm | Chưa chốt |
| F12 | Làm danh sách cần hoàn và hoàn tiền toàn bộ | Chỉ admin; cancelled/paid; tham chiếu payment gốc cùng đơn. | Chưa làm | Chưa chốt |
| F13 | Cho thử lại hoàn thất bại và chặn hoàn lặp | Thất bại giữ paid; một refund success mới chuyển refunded. | Chưa làm | Chưa chốt |
| F14 | Kiểm thử thanh toán cùng hủy hoặc hết hạn | Không paid rồi hoàn kho sai; ghi bằng chứng hai kết nối. | Chưa làm | Chưa chốt |
| F15 | Bàn giao luồng đơn và dữ liệu báo cáo cho TV2 | TV2 biết điều kiện trạng thái, completed_at và giao dịch. | Chưa làm | Chưa chốt |

Dựng quản trị trước; thử đơn thật khi TV5 tạo COD. F1 và F6 cần sớm để TV5 ghép lịch sử và hủy.

TV4 kiểm tra chéo TV6. TV6 kiểm tra chéo TV2.

# Kế hoạch kiểm thử Flora

Chưa có kết quả chạy các ca dưới đây. Tài khoản và dữ liệu thử dùng riêng. Ca đồng thời cần hai kết nối độc lập có bằng chứng chồng lấn, không chỉ bấm tuần tự hai tab.

| Mã | Người chạy chính | Tình huống | Mong đợi | Thực tế | Đạt | Bằng chứng |
|---|---|---|---|---|---|---|
| T01 | TV1 | Email trùng mật khẩu sai và tài khoản bị khóa | Bị từ chối đúng thông báo; không tạo phiên trái phép. | Chưa chạy | Chưa đánh giá | Chưa có |
| T02 | TV1 | Customer gọi URL quản trị và tài khoản khác | Bị chặn ở server; đăng ký không tự chọn admin. | Chưa chạy | Chưa đánh giá | Chưa có |
| T03 | TV2 và TV3 | Sai tổ hợp size màu quantity 0 và sản phẩm ngừng bán | Không thêm giỏ hoặc tạo đơn sai. | Chưa chạy | Chưa đánh giá | Chưa có |
| T04 | TV3 | Hai yêu cầu chọn ảnh chính | Tối đa một ảnh chính; ảnh màu phải thuộc biến thể sản phẩm. | Chưa chạy | Chưa đánh giá | Chưa có |
| T05 | TV4 | Thêm sửa xóa giỏ và xem giỏ người khác | Đúng số lượng và tổng; từ chối không đúng chủ. | Chưa chạy | Chưa đánh giá | Chưa có |
| T06 | TV5 | Hai khách mua món cuối đồng thời | Chỉ một người mua thành công; stock không âm. | Chưa chạy | Chưa đánh giá | Chưa có |
| T07 | TV5 | Một món trong đơn thiếu kho | Rollback toàn bộ; các món còn lại không bị trừ. | Chưa chạy | Chưa đánh giá | Chưa có |
| T08 | TV5 | Gửi lại cùng checkout_token | Một đơn và một lần trừ kho. | Chưa chạy | Chưa đánh giá | Chưa có |
| T09 | TV4 và TV5 | Hai người tranh lượt mã cuối đồng thời | Không dùng vượt tổng lượt hoặc lượt mỗi người. | Chưa chạy | Chưa đánh giá | Chưa có |
| T10 | TV4 | Subtotal 499.999đ và 500.000đ | Ship lần lượt 30.000đ và 0đ. | Chưa chạy | Chưa đánh giá | Chưa có |
| T11 | TV4 | Subtotal 520.000đ áp mã 50.000đ | Ship 0đ; tổng 470.000đ; gỡ mã không đổi ship. | Chưa chạy | Chưa đánh giá | Chưa có |
| T12 | TV5 | Giá đổi trước lần xác nhận cuối | Không tự đặt theo tổng khác; yêu cầu xem lại. | Chưa chạy | Chưa đánh giá | Chưa có |
| T13 | TV6 | Hủy hai lần hoặc hai yêu cầu hủy đồng thời | Hoàn kho và trả lượt mã đúng một lần. | Chưa chạy | Chưa đánh giá | Chưa có |
| T14 | TV6 | Xác nhận giao và hoàn tất COD | Không trừ kho lại; completed paid và một payment success. | Chưa chạy | Chưa đánh giá | Chưa có |
| T15 | TV6 | Online thất bại rồi thử lại trước hạn | Giữ các lần thử; success hợp lệ cập nhật paid một lần. | Chưa chạy | Chưa đánh giá | Chưa có |
| T16 | TV6 | Thanh toán gửi lặp hoặc sau hạn | Không có payment success trùng; sau hạn bị chặn. | Chưa chạy | Chưa đánh giá | Chưa có |
| T17 | TV5 và TV6 | Thanh toán đồng thời hủy hoặc hết hạn | Một kết quả hợp lệ; không paid và hoàn kho sai. | Chưa chạy | Chưa đánh giá | Chưa có |
| T18 | TV6 | Hủy online paid và hoàn thất bại rồi thử lại | Kho hoàn một lần; cancelled paid đến khi refund success. | Chưa chạy | Chưa đánh giá | Chưa có |
| T19 | TV6 | Hoàn tiền gửi lặp | Tối đa một refund success cùng payment gốc của đơn. | Chưa chạy | Chưa đánh giá | Chưa có |
| T20 | TV4 TV5 TV6 | Đơn 0đ đặt giao hoàn tất hoặc hủy | not_required; không thu tiền 0đ hoặc tạo refund. | Chưa chạy | Chưa đánh giá | Chưa có |
| T21 | TV6 | Tác vụ hết hạn dừng rồi chạy lại | Không nhận trả quá hạn; hủy hoàn kho đúng khi chạy lại. | Chưa chạy | Chưa đánh giá | Chưa có |
| T22 | TV3 và TV5 | Đổi tên giá địa chỉ sau khi có đơn | Đơn cũ giữ snapshot; biến thể đã bán không đổi nghĩa. | Chưa chạy | Chưa đánh giá | Chưa có |
| T23 | TV1 | Khóa hoặc hạ quyền admin hoạt động cuối | Bị từ chối kể cả yêu cầu đồng thời. | Chưa chạy | Chưa đánh giá | Chưa có |
| T24 | TV2 | Đối chiếu báo cáo với đơn và chi tiết | Không cộng trùng; đúng kỳ completed_at và không tính ship vào doanh thu. | Chưa chạy | Chưa đánh giá | Chưa có |
| T25 | Cả nhóm | Chạy từ máy khác bằng bản bàn giao | Import một lần; cấu hình đúng; router và chức năng chạy. | Chưa chạy | Chưa đánh giá | Chưa có |
| T26 | Cả nhóm | Giao diện điện thoại dữ liệu rỗng và lỗi | Không tràn bố cục; thông báo rõ; không lộ stack trace cho khách. | Chưa chạy | Chưa đánh giá | Chưa có |

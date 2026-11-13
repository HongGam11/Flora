# Mẫu giao diện chung (B1–B2, TV2)

## Màu và font
- Màu chủ đạo `--brand` #8c4566 (nút, giá, liên kết); nền `--bg` #fff9fb; chữ `--ink` #282128; chữ phụ `--muted` #695b62; viền `--line` #e8dce1; giảm giá `--sale` #c0392b.
- Font hệ thống (`system-ui`); chữ nội dung 16px; tiêu đề h1 26–38px.
- Biến CSS nằm ở đầu `assets/css/common.css`; đổi màu ở đó, không viết lại mã màu trong file khác.

## Thành phần dùng chung (common.css)
| Thành phần | Class |
|---|---|
| Nút | `.btn` (chính), `.btn-ghost` (viền), `.btn-sm`, `.btn-lg` |
| Form | `label` bọc `input/select/textarea`; nhãn nhỏ, màu phụ |
| Bảng | `.table`, cột số `.num`, bọc `.scroll` để cuộn ngang trên điện thoại |
| Thông báo | `.notice` (cảnh báo/trống), `.notice.alert-ok` (thành công) |
| Nhãn | `.badge` + `.badge-sale` / `.badge-off` |
| Chữ phụ | `.muted` |

## Phạm vi file CSS
- `common.css`: dùng cho cả khách và quản trị.
- `customer.css`: header, menu, lưới sản phẩm, lọc, chi tiết (khách).
- `admin.css`: sidebar, thẻ số liệu, thanh doanh thu (quản trị).
- Module khác (giỏ, checkout, đơn…) dùng lại class ở trên; cần class mới thì thêm vào cuối file tương ứng và báo TV2.

## Ảnh
Ảnh lỗi hoặc link hết hạn tự thay bằng `assets/images/no-image.svg` (common.js). Thẻ ảnh sản phẩm nên có `referrerpolicy="no-referrer"` vì ảnh đang là link ngoài.

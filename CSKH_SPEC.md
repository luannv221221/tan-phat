# PHÂN HỆ CSKH (Chăm sóc khách hàng) — lát cắt CSKH-1

| | |
|---|---|
| **Trạng thái** | ✅ **XONG + verify** (18/07/2026, migration `000019`). |
| **Nguồn** | SRS mục 3.12 (CS-01→04), 3.14 biểu mẫu CSKH, TASK_84 |

## Đã làm

| Màn hình | URL | Nội dung |
|---|---|---|
| Phiếu bảo hành / sửa chữa | `admin/warranty` | CRUD + luồng trạng thái tiếp nhận→đang xử lý→hoàn tất/huỷ (CS-01) |
| Lịch bảo hành | `admin/lich-bao-hanh` | Phiếu chưa hoàn tất theo ngày hẹn, **highlight quá hạn** (CS-03) |
| Nhóm khách hàng | `admin/customer-groups` | CRUD (name, chiết khấu %) + `partners.group_id` |
| Kiểm duyệt đánh giá | `admin/reviews` | Duyệt/ẩn/xoá đánh giá từ web (TASK_84) |
| Báo cáo CSKH | `admin/bao-cao-cskh` | Số phiếu + phí theo trạng thái (CS-04) |
| **Đánh giá SP (storefront)** | `san-pham/<slug>` | Thành viên chấm sao + bình luận → chờ duyệt → hiện công khai (TASK_84) |

## Mô hình (migration 000019)

- `customer_groups` (name, discount_percent, note) — seed Khách lẻ/Đại lý/Garage đối tác; `partners.group_id` FK SET NULL.
- `warranty_requests` (request_no BH-, partner_id/customer_name/phone, part_id/product_name/serial_no, received/appointment/completed_date, status, issue/diagnosis, technician, fee). Lịch + báo cáo tính động.
- `product_reviews` (part_id, member_id, author_name, rating 1-5, comment, status 0 chờ/1 duyệt). Storefront chỉ hiện status=1.

## Nguyên tắc

- Phiếu bảo hành: khách có thể là đối tượng `partners` hoặc khách lẻ (nhập tay); thiết bị có thể là `parts` hoặc nhập tay. `set-status` sang *done* tự điền `completed_date`.
- Đánh giá: chỉ **thành viên đăng nhập** mới gửi được (`Session dataMember`); mặc định chờ duyệt; admin duyệt mới hiển thị. Điểm trung bình + số lượt hiện ở trang chi tiết.

## CSKH-2 — Biên bản giao nhận thiết bị bảo hành (migration 000031) ✅

- Gắn với 1 phiếu bảo hành; 2 loại: **BB nhận thiết bị** (`receive`, khi tiếp nhận) và **BB trả thiết bị** (`return`, khi hoàn tất). Số tự tăng `BBGN-xxxxxx`.
- Trong phiếu bảo hành (`admin/warranty/edit`): mục "Biên bản giao nhận" liệt kê các BB + 2 nút lập BB. Form nhập: ngày, bên giao/bên nhận, phụ kiện đi kèm, tình trạng thiết bị, ghi chú (tự điền sẵn khách + thiết bị + serial từ phiếu).
- **Bản in A4** (`warranty/handover-print/<id>`): trang HTML độc lập (không layout admin), tiêu đề "BIÊN BẢN GIAO NHẬN THIẾT BỊ", 2 ô ký Bên giao / Bên nhận, nút In; header lấy tên/địa chỉ/ĐT công ty từ `site_settings`.
- Dùng chung quyền `warranty` (không đăng ký module riêng); model `WarrantyHandoversModel`, bảng `warranty_handovers` (FK CASCADE theo phiếu bảo hành).

## Hoãn (CSKH-2 còn lại / web sau)

Đăng ký bản tin · khách hàng liên hệ · nhắc lịch bảo trì tự động · gán chiết khấu nhóm KH vào giá bán · lịch sử chat real-time (hiện webchat polling).

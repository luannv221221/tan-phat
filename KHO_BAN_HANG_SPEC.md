# SPEC — PHÂN HỆ KHO / BÁN HÀNG

| | |
|---|---|
| **Trạng thái** | ✅ **Kho + Bán hàng — XONG + verify** (18/07/2026). Kho: danh mục kho · nhập/xuất bình quân gia quyền + KT-6 · tồn kho · thẻ kho. Bán hàng: báo giá · hoá đơn bán (doanh thu Nợ131/Có511+3331 + giá vốn Nợ632/Có156 + trừ tồn) · công nợ khách (admin/debt) · báo cáo bán hàng. **KT-6 đã khép vòng.** |
| **Nguồn** | `SRS_ERP_TanPhat.md` mục 3.10 (Kinh doanh SAL-01→13), 3.11 (Kho WH-01→12), 3.14 (biểu mẫu) |
| **Quyết định phạm vi (đã chốt với chủ dự án)** | ① Làm **Kho trước**, Bán hàng sau · ② Kho **phẳng 1 cấp** + vị trí ghi text · ③ Giá vốn **bình quân gia quyền tức thời** · ④ **Nối KT-6 luôn** (ghi sổ phiếu → tự sinh bút toán) |

---

## 1. Nguyên tắc

- **Tồn kho theo từng (kho × phụ tùng)** — bảng `stocks` là số dư tức thời (số lượng + đơn giá bình quân).
- **Thẻ kho** (`stock_cards`) là sổ append-only: mỗi lần nhập/xuất ghi 1 dòng, có số dư luỹ kế. Đây là nguồn sự thật để dựng lại tồn.
- **Bình quân gia quyền tức thời**: mỗi lần *nhập* cập nhật lại đơn giá bình quân
  `bq_mới = (SL_cũ×bq_cũ + SL_nhập×giá_nhập) / (SL_cũ + SL_nhập)`.
  *Xuất* lấy theo bq hiện tại, bq **không đổi** khi xuất.
- **Ghi sổ (post)**: phiếu *nháp* mới sửa/xoá được. Khi ghi sổ → cập nhật tồn + sinh bút toán KT-6. Đã ghi sổ thì khoá.
- **Huỷ ghi sổ**: chỉ cho phép nếu phiếu là **phát sinh cuối cùng** của các phụ tùng liên quan (không có nhập/xuất nào sau nó) — để bình quân gia quyền không bị sai. Có phát sinh sau → chặn, báo rõ.
- Giá vốn phiếu **xuất** tính tại **thời điểm ghi sổ** (không phải lúc lập), vì bq thay đổi theo thời gian.

## 2. Mô hình dữ liệu (migration `000016`)

| Bảng | Vai trò |
|---|---|
| `warehouses` | Danh mục kho (phẳng, có kho mặc định) |
| `stocks` | Tồn tức thời: `(warehouse_id, part_id) → quantity, avg_cost` (UNIQUE) |
| `stock_cards` | Thẻ kho append-only: `qty_in/qty_out, unit_cost, balance_qty, balance_value` + trỏ chứng từ |
| `goods_receipts` + `_items` | Phiếu nhập (nhap_mua/nhap_khac/nhap_tra) |
| `goods_issues` + `_items` | Phiếu xuất (xuat_ban/xuat_khac/xuat_tra) |

Phiếu header có `counter_account_id` (TK đối ứng cho KT-6), `acc_voucher_id` (bút toán sinh ra), `status`, `partner_id`.

## 3. Định khoản tự động KT-6

Khi **ghi sổ**, hệ thống tạo 1 **phiếu kế toán** (`acc_vouchers.voucher_type='ke_toan'`, số PKT-) và định khoản qua `acc_voucher_entries` (Nợ/Có tự do) — chảy thẳng vào Nhật ký chung / Sổ cái / Công nợ đã có.

| Chứng từ | Nợ | Có | Số tiền |
|---|---|---|---|
| Nhập mua (có công nợ NCC) | **156** Hàng hóa | **331** Phải trả NCC (mặc định) | tổng giá nhập |
| Nhập khác | **156** | *counter_account_id* (người dùng chọn) | tổng giá nhập |
| Xuất bán | **632** Giá vốn (mặc định) | **156** | tổng **giá vốn** |
| Xuất khác | *counter_account_id* | **156** | tổng giá vốn |

> Phiếu xuất bán ở Kho chỉ ghi **giá vốn** (Nợ 632/Có 156). Phần **doanh thu** (Nợ 131/Có 511/3331) sẽ do hoá đơn bán hàng ở phân hệ **Bán hàng** sinh ra — tách bạch để không trùng.
> TK kho mặc định **156** (hàng hoá — Tân Phát là DN thương mại phụ tùng). NCC gắn `partner_id` → công nợ 331 tự lên.

## 4. Màn hình (Kho)

| Màn hình | URL | SRS |
|---|---|---|
| Danh mục kho | `admin/warehouses` | WH danh mục |
| Phiếu nhập kho | `admin/goods-receipts` | WH-02/03/04 (theo loại) |
| Phiếu xuất kho | `admin/goods-issues` | WH-02 |
| Tồn kho | `admin/ton-kho` | WH-09/11 |
| Thẻ kho | `admin/the-kho` | WH-08 |

## 5. Bán hàng (đã làm — migration 000017)

| Màn hình | URL | Nội dung |
|---|---|---|
| Báo giá | `admin/quotations` | trạng thái nháp/gửi/chấp nhận/từ chối; tự điền đơn giá từ phụ tùng; **chuyển thành hoá đơn** |
| Hoá đơn bán | `admin/sales-invoices` | ghi sổ → 1 phiếu kế toán: Nợ131/Có511 (doanh thu) · Nợ131/Có3331 (thuế) · Nợ632/Có156 (giá vốn) + **trừ tồn**; huỷ ghi sổ hoàn tất |
| Công nợ khách | `admin/debt` | (KT-4, dùng lại) — 131 tự lên từ hoá đơn |
| Báo cáo bán hàng | `admin/bao-cao-ban-hang` | doanh thu/giá vốn/lãi gộp theo khách + theo nhân viên |

- Giá vốn tính lúc ghi sổ (bình quân gia quyền, tái dùng `StocksModel::applyOut`, doc_type='sale_invoice').
- Báo giá KHÔNG tác động tồn/kế toán. Thuế GTGT 1 mức/hoá đơn (mặc định 10%).
- Đối tượng khách hàng dùng chung `partners`. **KT-6 đã khép vòng** (nhập/xuất kho + bán hàng đều tự sinh bút toán).

## 5b. Kho-2 (đã làm — migration 000020)

| Màn hình | URL | Nội dung |
|---|---|---|
| Điều chuyển kho | `admin/transfers` | chuyển hàng kho A→B: xuất nguồn tại bq → nhập đích tại **cùng giá vốn**; KHÔNG sinh bút toán (nội bộ) |
| Kiểm kê kho | `admin/stock-takes` | nhập SL thực tế; chốt → so tồn sổ, điều chỉnh + bút toán **thừa Nợ156/Có711**, **thiếu Nợ632/Có156** |

- Tựa lên `StocksModel` (applyIn/applyOut/reverseDoc/isLastMovement). Huỷ ghi sổ chỉ khi là phát sinh cuối (bình quân gia quyền). Bổ sung TK 711.

## 5b. KHO-3 — Hàng tồn lâu + Vị trí nhiều cấp (migration 000030) ✅

- **Hàng tồn lâu** (`admin/ton-kho-lau`, chỉ xem): mỗi dòng tồn (SL>0) kèm **ngày phát sinh gần nhất** (từ `stock_cards`) và **số ngày nằm kho** tới ngày báo cáo. Lọc theo kho + ngưỡng ngày tối thiểu (mặc định 90) + ngày chốt. Gộp theo dải tuổi 0–30 / 31–90 / 91–180 / 181–365 / >365 (thẻ tổng hợp SL mã + giá trị). `StocksModel::getAging()`; số ngày tính bằng `DateTime::diff` (chuẩn lịch, không lệch DST).
- **Vị trí trong kho** (`admin/warehouse-locations`, CRUD): danh mục cây **tối đa 5 cấp** (Khu → Tầng → Kệ → Ngăn → Ô) theo từng kho. `full_path` + `level` tự dựng từ cha; đổi tên/cha tự cập nhật `full_path` nhánh con (`reindexChildren`); xoá cha xoá luôn nhánh con (FK CASCADE). Dropdown chọn cha lọc theo kho bằng JS.
- **Nối phiếu nhập**: ô "Vị trí" dòng hàng nhập có `<datalist>` gợi ý từ danh mục vị trí đang bật (vẫn lưu text, không phá luồng nhập kho).

## 6. Hoãn sang đợt sau

- Kho-3 (còn lại): báo cáo đồ thị biến động tồn; ràng buộc chọn vị trí bắt buộc trên phiếu (hiện chỉ gợi ý).
- Bán hàng: hợp đồng (theo yêu cầu "không cần làm hợp đồng"), chiết khấu dòng, hoá đơn điện tử.
- TASK_79 (ẩn tồn theo quyền) & TASK_92 (facet) — cần storefront website.

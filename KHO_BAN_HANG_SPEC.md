# SPEC — PHÂN HỆ KHO / BÁN HÀNG

| | |
|---|---|
| **Trạng thái** | ✅ **Kho — XONG + verify** (18/07/2026): danh mục kho · phiếu nhập/xuất (bình quân gia quyền + tự sinh bút toán KT-6) · tồn kho · thẻ kho. Bán hàng — sau. |
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

## 5. Hoãn sang tăng phần Kho-2 / Bán hàng

- Điều chuyển kho (WH-05), Kiểm kê (WH-07), Hàng tồn lâu (WH-12), báo cáo đồ thị.
- Phân cấp kho 5 tầng (Nhà kho→Dãy→Khoang→Tầng).
- Bán hàng: báo giá → đơn hàng → hoá đơn (doanh thu + 131/511/3331) → công nợ KH.
- TASK_79 (ẩn tồn theo quyền) & TASK_92 (facet) — sau khi có tồn kho + storefront.

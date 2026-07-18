# ĐỀ XUẤT SPEC — PHÂN HỆ KẾ TOÁN

| | |
|---|---|
| **Trạng thái** | ✅ Đã duyệt (TT133 · công nợ dùng bảng `partners` chung · VND · nhập tay) · **KT-1→KT-4 đã code + verify** (18/07) · KT-5 (sổ sách: nhật ký chung, sổ cái) tiếp theo · KT-6 hoãn (chờ Kho/Bán hàng) |
| **Ngày** | 18/07/2026 |
| **Nguồn** | Dựng lại từ `SRS_ERP_TanPhat.md` (mục "Module Kế toán", "Quản lý quỹ", "Sổ sách kế toán", "Công nợ", "Danh mục kế toán") |

> ⚠️ Phân hệ Kế toán **có mô tả trong SRS nhưng KHÔNG có trong sheet Tracking** (không task ID, không ước lượng). Tài liệu này đề xuất phạm vi + mô hình dữ liệu + phân kỳ để BA/Kế toán chốt **trước khi** viết code — tránh dựng sai nghiệp vụ kế toán (rủi ro cao, sửa lại tốn kém).

---

## 1. Phạm vi (theo SRS)

SRS liệt kê các khối sau trong phân hệ Kế toán:

| Khối | Nội dung (SRS) |
|---|---|
| **Quản lý quỹ** | Phiếu thu, Phiếu chi, Giấy báo có, Giấy báo nợ, Phiếu kế toán, Phiếu điều chuyển tiền, Phiếu tạm ứng |
| **Sổ quỹ** | Sổ quỹ, Biểu đồ biến động quỹ |
| **Công nợ** | Sổ chi tiết công nợ 1 khách hàng, theo BPKD, Bảng tổng hợp số dư, Bảng cân đối phát sinh công nợ |
| **Sổ sách kế toán** | Sổ chi tiết 1 tài khoản, Sổ tổng hợp chữ T, Sổ nhật ký chung |
| **Danh mục kế toán** | Danh mục tài khoản, Danh mục mã vụ việc, Danh mục mã phí |

Kế toán **liên thông** với phân hệ Kho (phiếu nhập/xuất, nhập mua hàng có công nợ NCC) và Kinh doanh (báo giá, hợp đồng, công nợ khách). → Cần chốt ranh giới tích hợp.

---

## 2. Mô hình dữ liệu đề xuất

Theo nguyên tắc **bút toán kép** (mỗi chứng từ sinh ≥1 cặp Nợ/Có cân bằng).

### 2.1 Danh mục

- **`acc_accounts`** — hệ thống tài khoản (chart of accounts)
  `id, code (vd 111, 131, 331, 511...), name, parent_id (cây), type (tài sản/nợ/vốn/doanh thu/chi phí), is_detail (TK chi tiết mới cho hạch toán), status`
- **`acc_cost_items`** — danh mục mã phí (`id, code, name, status`)
- **`acc_projects`** — danh mục mã vụ việc (`id, code, name, status`)
- **`partners`** — đối tượng công nợ (khách hàng / NCC). *Có thể tái dùng nếu Kinh doanh đã có bảng khách hàng — CẦN CHỐT.*

### 2.2 Chứng từ (voucher) — bút toán kép

- **`acc_vouchers`** — đầu chứng từ
  `id, voucher_no (số phiếu), voucher_type (thu/chi/bao_co/bao_no/ke_toan/tam_ung/dieu_chuyen), voucher_date, partner_id, description, total_amount, status (nháp/đã ghi sổ/huỷ), created_by, create_at`
- **`acc_voucher_entries`** — dòng bút toán (định khoản)
  `id, voucher_id, account_id (TK), counter_account_id (TK đối ứng), debit, credit, cost_item_id, project_id, note`
  → Ràng buộc: tổng Nợ = tổng Có theo mỗi phiếu (kiểm ở tầng ứng dụng + transaction).

### 2.3 Nguyên tắc

- **Ghi sổ (post)**: phiếu ở trạng thái *nháp* mới sửa/xoá được; *đã ghi sổ* thì khoá, chỉ đảo bút toán.
- **Sổ sách** = truy vấn tổng hợp trên `acc_voucher_entries` (không lưu số dư trùng lặp; tính động hoặc snapshot theo kỳ — CẦN CHỐT hiệu năng).

---

## 3. Phân kỳ đề xuất

| GĐ | Nội dung | Ghi chú |
|---|---|---|
| ~~**KT-1**~~ | Danh mục tài khoản (cây), mã phí, mã vụ việc | ✅ Xong 18/07 — seed 19 TK lõi, CRUD đầy đủ |
| ~~**KT-2**~~ | Phiếu thu / Phiếu chi (quản lý quỹ tiền mặt) + Sổ quỹ | ✅ Xong 18/07 — bút toán kép, tự đánh số, ghi sổ/huỷ ghi sổ + khoá, sổ quỹ luỹ kế |
| ~~**KT-3**~~ | Phiếu kế toán (định khoản tự do Nợ/Có) | ✅ Xong 18/07. Báo có/nợ = phiếu thu/chi qua TK 112; tạm ứng = phiếu chi TK 141; điều chuyển tiền = phiếu kế toán. Đã chốt TT133, seed thêm 141/3331... |
| ~~**KT-4**~~ | Công nợ (sổ chi tiết 1 đối tượng, tổng hợp số dư) | ✅ Xong 18/07 — bảng `partners` chung, gắn vào phiếu, công nạ ròng theo TK 131/331 |
| **KT-5** | Sổ sách: Nhật ký chung, Sổ cái/chi tiết TK, chữ T | Báo cáo tổng hợp |
| **KT-6** | Liên thông Kho + Kinh doanh (tự sinh bút toán từ phiếu nhập/xuất, hoá đơn) | Phụ thuộc 2 phân hệ đó đã có |

---

## 4. ❓ Câu hỏi cần BA / Kế toán chốt

1. **Chế độ kế toán**: theo **Thông tư 200** hay **133** (quyết định hệ thống tài khoản chuẩn)? Có cần nạp sẵn danh mục TK theo TT không?
2. **Mức độ hạch toán**: bút toán kép đầy đủ, hay chỉ ghi thu/chi đơn giản (sổ quỹ) ở giai đoạn đầu?
3. **Đối tượng công nợ**: dùng chung bảng khách hàng/NCC với phân hệ Kinh doanh/Mua hàng, hay Kế toán có danh mục riêng?
4. **Kỳ kế toán & khoá sổ**: có khoá sổ theo tháng/quý/năm không? Cho sửa phiếu kỳ đã khoá?
5. **Liên thông Kho/Bán hàng**: phiếu nhập/xuất kho, hoá đơn **tự sinh bút toán** hay kế toán nhập tay? (Quyết định thứ tự làm — Kho/Kinh doanh phải có trước.)
6. **Hoá đơn điện tử / thuế**: có tích hợp phát hành HĐĐT + tờ khai thuế không? (Thường cần tích hợp bên thứ 3.)
7. **Đa quỹ / đa ngân hàng / đa tiền tệ**: có nhiều quỹ tiền mặt, nhiều tài khoản ngân hàng, ngoại tệ không?
8. **Báo cáo tài chính**: có cần BCĐKT, KQKD, Lưu chuyển tiền tệ ở phase này không, hay chỉ sổ sách nội bộ?

---

## 5. Khuyến nghị

- **KHÔNG dựng mù.** Kế toán sai nguyên tắc (hệ thống TK, bút toán kép, khoá sổ) rất khó sửa về sau và ảnh hưởng tính pháp lý của số liệu.
- Ưu tiên chốt **câu 1, 2, 3, 5** trước — chúng quyết định toàn bộ mô hình dữ liệu.
- Có thể làm **KT-1 + KT-2** (danh mục TK + phiếu thu/chi + sổ quỹ) trước như một lát cắt mỏng để nghiệm thu nghiệp vụ, rồi mở rộng.
- Nên có **kế toán thực tế của Tân Phát** review spec này (không chỉ BA phần mềm).

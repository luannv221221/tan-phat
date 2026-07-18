# PHỤ TÙNG + LIÊN KẾT XE — TASK_86, 87, 90, 91, 93

| | |
|---|---|
| **Ngày** | 17/07/2026 |
| **Trạng thái** | ✅ Đã code, 53 test trên MySQL 8.0.44 thật, 0 fail |
| **Phụ thuộc** | Cây danh mục xe (xem `CAY_DANH_MUC_XE.md`) |

---

## 1. ⚠️ Hai khái niệm "hãng" — rất dễ nhầm

| Bảng | Nghĩa | Ví dụ | Trả lời câu hỏi |
|---|---|---|---|
| `car_brands` | Hãng **XE** | Toyota, Honda | Phụ tùng lắp cho xe nào? |
| `product_brands` | Thương hiệu **PHỤ TÙNG** | Bosch, Denso, Aisin | Ai làm ra món đồ này? |

Sheet Tracking liệt kê "Thương hiệu" và "Hãng sản xuất" thành **2 danh mục riêng**, nên giữ cả hai:
- `product_brands` = nhãn bán ra (Toyota Genuine)
- `product_manufacturers` = nơi gia công thật (Denso)

Hai cái này khác nhau thật: phụ tùng dán nhãn "Toyota Genuine" nhưng do Denso sản xuất.

---

## 2. Quyết định thiết kế quan trọng nhất: nối vào ĐỜI XE

`part_fitments` nối phụ tùng ↔ **`car_years` (đời xe)**, KHÔNG phải `car_models`.

**Vì sao:** cùng model nhưng đời khác nhau lắp phụ tùng khác nhau. Vios 2014–2017 và Vios 2018+ dùng lọc gió **khác nhau**. Nối vào model thì không phân biệt được — khách mua nhầm.

`car_years` đã mang sẵn `model_id` → `brand_id`, nên nối ở mức đời xe là **chi tiết nhất mà vẫn suy ngược lên hãng được**.

```
parts ──┐
        ├── part_fitments ──── car_years ──── car_models ──── car_brands
        │   (nhiều-nhiều)      (đời xe)       (Vios)          (Toyota)
        │
        └── part_categories (cây, phân cấp không giới hạn)
            product_brands / product_manufacturers / product_origins / product_units
```

---

## 3. Bảng

| Bảng | Vai trò | Ghi chú |
|---|---|---|
| `part_categories` | Danh mục phụ tùng | `parent_id` tự tham chiếu → cây không giới hạn cấp |
| `product_brands` | Thương hiệu phụ tùng | ⚠️ Khác `car_brands` |
| `product_manufacturers` | Hãng sản xuất | Nơi gia công |
| `product_origins` | Xuất xứ | Nhật, Đức... |
| `product_units` | Đơn vị tính | Cái, bộ, lít... |
| `parts` | Phụ tùng | `code` + `slug` UNIQUE; có `oem_code` để tra mã hãng |
| `part_fitments` | Phụ tùng ↔ đời xe | `UNIQUE(part_id, car_year_id)` chặn trùng |

### 3.1 Tiền dùng DECIMAL, không dùng FLOAT

`price` và `sale_price` là `DECIMAL(15,2)`.

**Không bao giờ dùng FLOAT/DOUBLE cho tiền**: số thực nhị phân không biểu diễn chính xác số thập phân, cộng dồn nhiều dòng sẽ lệch. Kế toán không chấp nhận sai số kiểu đó. Đây là chỗ nếu làm sai thì đến phân hệ Kế toán mới phát hiện, lúc đó sửa rất đắt.

### 3.2 Quy tắc xoá — đã kiểm chứng trên MySQL thật

| Quan hệ | Quy tắc | Ý nghĩa |
|---|---|---|
| `part_fitments.part_id` → `parts` | CASCADE | Xoá phụ tùng → liên kết vô nghĩa, xoá theo |
| `part_fitments.car_year_id` → `car_years` | CASCADE | Xoá đời xe → liên kết vô nghĩa, xoá theo. **Phụ tùng vẫn còn**, chỉ mất liên kết |
| `parts.category_id` → `part_categories` | SET NULL | Xoá danh mục **không** được làm mất phụ tùng |
| `parts.brand_id` / `origin_id` / `unit_id` | SET NULL | Như trên |
| `part_categories.parent_id` → chính nó | RESTRICT | Không cho xoá danh mục cha khi còn con |

---

## 4. Các hàm chính

### ⭐ `PartsModel::getByCarYear($carYearId)` — TASK_87

*"Chọn xe sẽ lọc ra các phụ tùng"*. Trả về phụ tùng lắp được cho một đời xe cụ thể.

### ⭐ `PartsModel::getByModelAndYear($modelId, $year)` — TASK_93

Khách chọn *"Vios đời 2020"* — họ không biết `car_year_id` là gì. Hàm tự tìm đời xe chứa năm đó rồi lấy phụ tùng. Trả mảng rỗng nếu không đời nào khớp (không ném lỗi).

### `PartsModel::getByModel($modelId)`

Phụ tùng lắp cho **bất kỳ đời nào** của model. Dùng `groupBy('parts.id')` để phụ tùng dùng chung nhiều đời không bị trả về nhiều lần.

> Ghi chú: MySQL 8 bật `ONLY_FULL_GROUP_BY`, nhưng `GROUP BY parts.id` vẫn chạy được vì `parts.id` là khoá chính — MySQL nhận ra các cột khác phụ thuộc hàm vào nó. Đã kiểm chứng trên MySQL 8.0.44 thật.

### `PartsModel::getLists($filters, $keyword)` — TASK_90, TASK_91

Tìm theo **tên HOẶC mã HOẶC mã OEM**. Nhóm OR được bọc trong `where(function(){...})` để không phá điều kiện lọc phía trên — đã có test: lọc "Má phanh" + tìm "Lọc gió" phải ra 0 kết quả, chứ không phải ra hết bảng.

### `PartFitmentsModel::syncForPart($partId, $carYearIds)`

Thay **toàn bộ** liên kết của một phụ tùng — dùng cho màn hình sửa. Bọc **transaction**: gán 10 đời mà đứt ở đời thứ 7 thì không được để lại 6 dòng nửa vời.

Đã có test chứng minh: sync với id đời xe không tồn tại → khoá ngoại chặn → **liên kết cũ vẫn nguyên vẹn**.

---

## 5. Dữ liệu mồi

- **6 đơn vị**: Cái, Bộ, Chiếc, Lít, Hộp, Mét
- **6 xuất xứ**: Nhật Bản, Đức, Hàn Quốc, Thái Lan, Trung Quốc, Việt Nam
- **6 thương hiệu**: Bosch, Denso, Aisin, NGK, Mann Filter, Toyota Genuine
- **16 danh mục** (4 gốc × ~3-4 con): Hệ thống phanh, Động cơ, Hệ thống điện, Hệ thống treo

Chưa có phụ tùng thật nào — cần nhập liệu hoặc import.

---

## 6. Chưa làm

| Việc | Task | Ghi chú |
|---|---|---|
| **Controller + view CRUD** | — | Model + DB xong; chưa có màn hình |
| **Thêm vào bảng `modules`** | — | Để phân quyền được |
| ~~Thư viện ảnh theo slide~~ | TASK_77 | ✅ Xong 18/07 — bảng `part_images`, upload nhiều ảnh + ảnh đại diện, ở màn hình sửa phụ tùng |
| ~~Import Excel~~ | TASK_78 | ✅ Xong 18/07 — `core/SpreadsheetReader` đọc `.xlsx`+`.csv` (không cần thư viện); upsert theo `code` |
| Ẩn/hiện theo phân quyền | TASK_79 | VD: chỉ thành viên thấy tồn kho |
| Tự động nhóm danh mục | TASK_80 | VD: danh mục "Khuyến mại" tự gom SP có `sale_price` |
| ~~Phụ kiện đi kèm~~ | TASK_81 | ✅ Xong 18/07 — bảng `part_related`, picker tìm kiếm AJAX (`products/search-json`), `syncForPart` chặn tự tham chiếu |
| Lọc theo thông số kỹ thuật | TASK_90 | Cần `attributes` / `attribute_values` — mới có lọc theo danh mục |
| Gợi ý khi gõ tìm kiếm | TASK_91 | Mới có tìm cơ bản |
| Lọc tích chọn (facet) | TASK_92 | |
| Tải catalogue | TASK_85 | |

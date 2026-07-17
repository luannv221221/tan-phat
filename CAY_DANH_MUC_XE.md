# CÂY DANH MỤC XE — Thiết kế & Giả định

| | |
|---|---|
| **Ngày** | 17/07/2026 |
| **Trạng thái** | ✅ Đã code, 39 test trên MySQL 8.0.44 thật, 0 fail |
| **Nguồn yêu cầu** | `Tracking-Todo-Funtion list.xlsx`, sheet Tracking, dòng 27–34 |
| **Vì sao làm trước** | Khuyến nghị #9 trong SRS — gốc của mọi thứ |

---

## 1. ✅ ĐÃ CHỐT (17/07/2026)

> **"Dòng xe" = KIỂU DÁNG THÂN XE (phương án A) — đã được xác nhận.**
> Bảng `car_body_types` giữ nguyên. Mục 1.1 dưới đây lưu lại để biết vì sao chốt như vậy.

Sheet Tracking ghi nguyên văn:

```
DANH MỤC XE, HÃNG XE
  Hãng xe (toyota, honda..)
  Dòng xe (hackback, sedan..)
  Model xe (Morning, vios..)
  Đời xe (năm sản xuất)
  Model xe (Morning, vios..)      ← LẶP LẠI (r32 trùng r30)
  Nhiên liệu (động cơ xe)
  Màu xe
```

### 1.1 "Dòng xe" nghĩa là gì?

| Cách hiểu | Hệ quả |
|---|---|
| **(A) Kiểu dáng thân xe** — hatchback, sedan, SUV | Là danh mục riêng, độc lập với hãng. **✅ ĐÃ CHỐT 17/07/2026** |
| ~~(B) Dòng sản phẩm~~ — Camry, Vios, Innova | ~~Trùng hoàn toàn với "Model xe"~~ → đã loại |

**Chốt (A)**, khớp với bằng chứng trong file: *"hackback, sedan"* là kiểu dáng, không phải dòng sản phẩm. Bảng `car_body_types` và cột `car_models.body_type_id` giữ nguyên.

### 1.2 "Model xe" bị lặp 2 dòng

Dòng r30 và r32 trong file gốc giống hệt nhau. Hiểu là **lỗi nhập liệu**, không phải 2 khái niệm khác nhau. Chỉ tạo 1 bảng.

### 1.3 "Màu xe" thuộc về đâu?

`car_colors` đang là danh mục độc lập, đúng như sheet liệt kê. Nhưng lưu ý: **màu xe không liên quan tới việc bán phụ tùng** — một cái lọc dầu không phụ thuộc màu xe. Nhiều khả năng màu xe dùng cho nghiệp vụ gara (tiếp nhận xe khách) chứ không cho website bán hàng. Chưa gắn vào đâu.

---

## 2. Sơ đồ

```
car_brands (Hãng xe)                car_body_types (Dòng xe = kiểu dáng)
  Toyota, Honda, Kia...               Sedan, Hatchback, SUV...
      │                                       │
      │ 1                                     │ 0..1
      │                                       │
      └──────────┐              ┌─────────────┘
                 ▼              ▼
              car_models (Model xe)
                Vios, Morning...
                     │
                     │ 1
                     ▼
              car_years (Đời xe)
                2014-2017, 2018-nay


car_fuels (Nhiên liệu)      car_colors (Màu xe)
  Xăng, Dầu, Điện...          Trắng, Đen...
  (chưa gắn vào đâu)          (chưa gắn vào đâu)
```

---

## 3. Bảng

| Bảng | Vai trò | Ghi chú |
|---|---|---|
| `car_brands` | Hãng xe | `slug` UNIQUE; có `country`, `logo` |
| `car_body_types` | Dòng xe (kiểu dáng) | ⚠️ Xem giả định 1.1 |
| `car_models` | Model xe | Thuộc 1 hãng; `UNIQUE(brand_id, slug)` — Toyota và Kia đều có thể có model trùng tên |
| `car_years` | Đời xe | `year_from` / `year_to`; `year_to = NULL` = còn sản xuất |
| `car_fuels` | Nhiên liệu | Danh mục tra cứu |
| `car_colors` | Màu xe | Có `hex` để hiển thị |

### 3.1 Vì sao đời xe gắn với model, không phải danh sách năm rời

Sheet ghi "Đời xe (năm sản xuất)" — đọc thoáng thì tưởng chỉ cần bảng năm (2018, 2019...). Nhưng **"2018" đứng một mình không lọc được phụ tùng**. Phải là "Vios 2018" mới có nghĩa. Nên `car_years` tham chiếu `model_id`.

Dùng khoảng năm (`year_from`–`year_to`) thay vì từng năm một: đời xe thực tế là một khoảng đời (Vios 2014–2017, 2018–nay), và một phụ tùng lắp cho cả khoảng đó. Nếu lưu từng năm thì mỗi phụ tùng phải khai báo lại cho từng năm.

### 3.2 Quy tắc xoá — đã kiểm chứng ở tầng MySQL

| Quan hệ | Quy tắc | Vì sao |
|---|---|---|
| `car_models.brand_id` → `car_brands` | **RESTRICT** | Không cho xoá Toyota khi còn model Vios. `CarBrandsModel::remove()` kiểm tra trước và trả `false` để báo lỗi cho người dùng, thay vì để exception SQL bắn lên |
| `car_years.model_id` → `car_models` | **CASCADE** | Xoá model Vios thì các đời Vios tự xoá — đời xe không có nghĩa nếu tách khỏi model |
| `car_models.body_type_id` → `car_body_types` | **SET NULL** | Xoá kiểu dáng "Sedan" **không** được làm mất model Vios; chỉ bỏ phân loại |

Cả ba đều có test chứng minh **ở tầng DB thật**, không chỉ ở tầng PHP.

> Test cây này chạy trên **MySQL thật chứ không SQLite** là có chủ ý: SQLite **mặc định không bật khoá ngoại** (`PRAGMA foreign_keys` mặc định OFF), nên test khoá ngoại trên SQLite sẽ PASS oan.

---

## 4. Model (PHP)

| Class | Bảng | Ghi chú |
|---|---|---|
| `CarLookupModel` (abstract) | — | Lớp cha cho 3 danh mục tra cứu đơn giản; gom `getLists/getDetail/findBySlug/add/edit/remove` |
| `CarBrandsModel` | `car_brands` | `countModels()`, `remove()` chặn khi còn model |
| `CarBodyTypesModel` | `car_body_types` | Kế thừa lookup + `countModels()` |
| `CarFuelsModel` | `car_fuels` | Kế thừa lookup |
| `CarColorsModel` | `car_colors` | Kế thừa lookup |
| `CarModelsModel` | `car_models` | `getLists()` join hãng + kiểu dáng; `getByBrand()` cho dropdown phụ thuộc |
| `CarYearsModel` | `car_years` | `getByModel()`, `findByModelAndYear()` |

### 4.1 `findByModelAndYear()` — hàm sẽ dùng nhiều nhất

Khi khách chọn *"Vios đời 2020"* để lọc phụ tùng (TASK_87, TASK_93):

```php
$doi = (new CarYearsModel())->findByModelAndYear($modelId, 2020);
```

Sinh ra:
```sql
WHERE `model_id` = ? AND `year_from` <= ? AND (`year_to` IS NULL OR `year_to` >= ?)
```

Đã test cả biên: năm 2017 thuộc đời 2014–2017, năm 2018 thuộc đời 2018–nay.

---

## 5. Dữ liệu mồi

Migration `000004` tạo sẵn để dùng được ngay:

- **6 hãng**: Toyota, Honda, Kia, Hyundai, Mazda, Ford
- **6 kiểu dáng**: Sedan, Hatchback, SUV, Crossover, MPV, Bán tải
- **4 nhiên liệu**: Xăng, Dầu (Diesel), Điện, Hybrid
- **6 màu**: Trắng, Đen, Bạc, Xám, Đỏ, Xanh

Chưa có model/đời xe nào — cần nghiệp vụ nhập, hoặc import từ nguồn thật.

---

## 6. Chưa làm

| Việc | Ghi chú |
|---|---|
| **Controller + view CRUD** | Model và DB xong; chưa có màn hình quản trị |
| **Thêm vào bảng `modules`** | Để phân quyền được cho các danh mục xe này |
| **Bảng phụ tùng + liên kết xe** | TASK_86/87 — bước kế tiếp, sẽ tham chiếu `car_years` |
| **Import dữ liệu xe thật** | 6 hãng mồi không đủ cho vận hành |
| **Xác nhận giả định mục 1.1** | ⚠️ Nên làm TRƯỚC khi xây phụ tùng lên trên |

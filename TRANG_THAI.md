# TRẠNG THÁI DỰ ÁN — chốt ngày 17/07/2026

> Đọc file này trước khi tiếp tục làm ở máy khác.

| | |
|---|---|
| **Test** | **381 test, 0 fail** — `C:\xampp\php\php.exe tests\run.php` |
| **DB** | MySQL 8.0.44, DB `tanphat_php`, 20 bảng, 6 migration |
| **PHP** | Chạy trên 8.0.30 (bản gốc chỉ chạy được ≤ 7.4) |
| **Nền tảng** | Framework tự viết (giữ theo yêu cầu), đã vá |

---

## 🔴 ĐỌC TRƯỚC KHI PUSH — 2 cảnh báo

### 1. Git root đang là CẢ thư mục `Downloads`

```
git rev-parse --show-toplevel  ->  C:/Users/admin/Downloads
```

Thư mục `core/` **chưa từng được git theo dõi**. Và `git status` ở `Downloads` đang có **510 mục** chờ commit — gồm **tài liệu cá nhân**: CV, hợp đồng, báo giá, ảnh 4x6, giấy ĐKKD, file Excel nhân sự...

> ⚠️ **`git add .` ở `Downloads` = đẩy toàn bộ tài liệu cá nhân lên remote.**

**Nên làm:** tạo repo riêng **bên trong** thư mục dự án:

```bash
cd C:/Users/admin/Downloads/framework_11_12_2021_fix
git init
git add .
git commit -m "Phase 0: va base + cay danh muc xe + phu tung + CRUD danh muc"
```

`.gitignore` trong thư mục dự án đã sẵn sàng cho việc này.

### 2. `.env` chứa mật khẩu DB — ĐÃ được chặn

```
git check-ignore -v .env  ->  .gitignore:2:.env    ✅ an toàn
```

Nhưng ở máy nhà **phải tự tạo `.env`**: copy `.env.example` → `.env` rồi điền `DB_PASS`.

**Vẫn cần làm (ngoài code):** credential cũ và 16 file session **đã nằm trong git history** của repo `Downloads`. Nếu repo đó từng được push đi đâu, phải đổi mật khẩu DB và coi mọi phiên cũ đã lộ.

---

## ✅ ĐÃ LÀM

### Phase 0 — vá base (chi tiết: `PHASE0_VA_BASE.md`)

| Mã | Nội dung |
|---|---|
| **B1** | SQL Injection — viết lại QueryBuilder dùng **bound parameters thật** + whitelist toán tử + kiểm tra tên cột |
| **B2** | Thêm **transaction** (`beginTransaction/commit/rollBack/transaction()`) |
| **B3** | Bỏ `escape()`/`get_magic_quotes_gpc()` — **chạy được trên PHP 8** |
| **B4** | **bcrypt** thay md5; hash md5 cũ **tự nâng cấp** khi đăng nhập đúng; token dùng `random_bytes` |
| **B5** | `catch (\PDOException)` thay `catch (Exception)` (bản cũ không bao giờ bắt được) |
| **H1** | Reset query state — bản cũ gọi 2 lần ra `WHERE id=1 AND id=2` |
| **H2** | **CSRF** middleware global + `csrf_field()` |
| **H3** | Session ra khỏi `public/`; cookie `httponly`+`samesite`; chống session fixation |
| **H4** | Credential ra `.env`; `_WEB_URL` hết hardcode |
| **H5** | **Migration runner**: `php migrate.php` / `status` / `rollback` / `make` |
| **M1** | `removeExpired()` — 1 câu DELETE thay vì nạp cả bảng + xoá từng dòng |
| **M2** | `utf8mb4` thay `utf8` |
| **M6** | Rà XSS toàn bộ view, sửa 5 chỗ chưa escape |
| **M7** | `Connection` dùng chung 1 PDO (bản cũ mỗi `new Model()` mở 1 kết nối mới) |
| **M9** | Viết `CHUAN_CODE.md` |
| **M10** | `joinOn()` bọc backtick — sửa bug `groups` là từ khoá dành riêng MySQL 8 |
| **M11** | `.htaccess` chặn `.env`, `.sql`; bỏ `error_reporting -1` |

### Nghiệp vụ

| Phần | Chi tiết | Tài liệu |
|---|---|---|
| **Cây danh mục xe** | 6 bảng: hãng, kiểu dáng, model, đời, nhiên liệu, màu | `CAY_DANH_MUC_XE.md` |
| **Phụ tùng + liên kết xe** | 7 bảng; TASK_86/87/90/91/93 | `PHU_TUNG.md` |
| **CRUD 7 danh mục** | Màn hình quản trị + menu + phân quyền. **18/07: đã tách mỗi danh mục thành controller độc lập kế thừa `Controller` + view riêng, bỏ `LookupCrudController`** | `CRUD_DANH_MUC.md` |

### Tài liệu

| File | Nội dung |
|---|---|
| `SRS_ERP_TanPhat.md` | SRS dựng lại từ file Excel — **ứng dụng làm về cái gì** |
| `PHASE0_VA_BASE.md` | Chi tiết từng lỗi + bằng chứng + cách sửa |
| `CHUAN_CODE.md` | **Đọc trước khi viết code mới** — quy tắc + phụ lục các bẫy đã cắn dự án |
| `CAY_DANH_MUC_XE.md` | Thiết kế cây xe (đã chốt "Dòng xe" = kiểu dáng) |
| `PHU_TUNG.md` | Thiết kế phụ tùng |
| `CRUD_DANH_MUC.md` | Cách thêm danh mục mới |

---

## ⬜ CHƯA LÀM — việc tiếp theo

### Ưu tiên 1 — để người dùng nhập được dữ liệu xe ✅ XONG (18/07/2026)

- [x] **CRUD Hãng xe** (`car_brands`) — có **upload logo** (validate ảnh thật, sinh tên an toàn, xoá file khi xoá/thay)
- [x] **CRUD Model xe** (`car_models`) — dropdown hãng (bắt buộc) + kiểu dáng (tuỳ chọn), slug duy nhất theo hãng, lọc danh sách theo hãng
- [x] **CRUD Đời xe** (`car_years`) — **cascade hãng → model** (JS, dữ liệu nhúng), năm from/to, tên tự sinh
- [x] **CRUD Danh mục phụ tùng** (`part_categories`) — **cây cha-con** thụt lề, chặn vòng lặp khi chọn cha, RESTRICT khi xoá cha còn con

> 4 màn hình viết controller độc lập riêng (kế thừa `Controller`) + view riêng theo card AdminLTE.
> Đã verify end-to-end (thêm/sửa/xoá, upload, cascade, RESTRICT) + 383 test PASS.
> Route đăng ký trong `$relationalModules` (`routes/web.php`); quyền cấp qua migration `000007`.

### Ưu tiên 2 — phụ tùng

- [x] **CRUD Phụ tùng** (18/07) — controller `Products` (URL `admin/products`), form gán nhiều đời xe (`syncForPart`), nhiều dropdown FK, giá VND
- [x] **Phân trang** (18/07) — 20 dòng/trang + tìm kiếm (tên/mã/OEM) + lọc theo danh mục
- [x] **TASK_77 thư viện ảnh** (18/07) — upload nhiều ảnh/phụ tùng, đặt ảnh đại diện, xoá (kèm file); bảng `part_images`
- [x] **TASK_78 import Excel/CSV** (18/07) — reader `.xlsx` tự viết (zip+XML, không cần thư viện) + `.csv`; upsert theo `code`, map FK theo slug
- [x] **TASK_81 phụ kiện đi kèm** (18/07) — bảng `part_related` (tự tham chiếu có hướng, CASCADE), picker tìm kiếm AJAX trong form phụ tùng, chặn tự tham chiếu
- [x] **TASK_80 lọc khuyến mãi** (18/07) — lọc SP có `sale_price` trong danh sách
- [x] **TASK_90 thông số kỹ thuật** (18/07) — bảng `attributes` + `part_attribute_values`, CRUD thông số, gán giá trị + lọc theo thông số
- [x] **TASK_91 gợi ý tìm kiếm** (18/07) — autocomplete ở ô tìm (dùng `products/search-json`)
- [x] **TASK_85 tải catalogue** (18/07) — xuất CSV theo bộ lọc (`products/export`)
- [ ] **TASK_79 ẩn tồn kho theo quyền** — ⏸ HOÃN: parts chưa có trường tồn kho + chưa có hệ thống thành viên website
- [ ] **TASK_92 lọc facet** — ⏸ HOÃN: tính năng website khách hàng, chưa có storefront

### Ưu tiên 3 — cần người có quyền, không phải code

- [ ] **Đổi mật khẩu DB** (credential cũ trong git history)
- [ ] **Xoá 16 file session khỏi git history** (`git filter-repo`)
- [ ] **Trỏ docroot vào `public/`** thay vì thư mục gốc — `.htaccess` chỉ có tác dụng trên Apache; dùng Nginx là mọi chặn vô hiệu

### Còn treo trong SRS

- **Phân hệ Kế toán**: đã soạn `KE_TOAN_SPEC_DE_XUAT.md` + **đã code KT-1 + KT-2** (18/07): danh mục tài khoản (cây, seed 19 TK) · mã phí · mã vụ việc · phiếu thu/chi (bút toán kép, tự đánh số, ghi sổ/huỷ ghi sổ + khoá) · sổ quỹ (số dư đầu/cuối kỳ + luỹ kế). Nhóm menu "Kế toán". **KT-3→KT-6 chờ chốt 8 câu hỏi trong spec** (TT200/133, đối tượng công nợ, liên thông Kho/Bán hàng...).
- **~48 dòng trong Tracking không có trạng thái** → con số "Total 68" không phản ánh quy mô thật (>130 hạng mục).

---

## 🚀 Dựng lại ở máy nhà

```bash
# 1. Cài PHP 8 + MySQL 8 (hoặc XAMPP)

# 2. Cấu hình
cp .env.example .env
#    rồi sửa DB_PASS, DB_PORT, APP_BASE_PATH cho đúng máy

# 3. Tạo DB trắng
#    CREATE DATABASE tanphat_php CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 4. Dựng schema + dữ liệu mồi
C:\xampp\php\php.exe migrate.php

# 5. Kiểm tra
C:\xampp\php\php.exe tests\run.php      # phải PASS hết
C:\xampp\php\php.exe migrate.php status
```

**Đăng nhập:** `hoangan.web@gmail.com` / `123456` (dữ liệu từ dump gốc).

> ⚠️ **Chạy `migrate.php` TRƯỚC khi ai đăng nhập lần đầu.** Cột `password` gốc là `varchar(50)`, bcrypt dài 60 → không migrate thì người đầu tiên đăng nhập sẽ bị cắt hash và **mất tài khoản vĩnh viễn**. Xem `PHASE0_VA_BASE.md` mục 5.4.

### Nếu import dump cũ vào DB đã có sẵn

Migration `000003` sẽ chuyển các bảng cũ sang utf8mb4. **Bắt buộc** — không có nó thì không lưu được emoji và MySQL 8 sẽ từ chối INSERT (xem `PHASE0_VA_BASE.md` mục 5.10).

---

## 📌 Ba điều dễ quên nhất

1. **Đừng `git add .` ở `Downloads`** — xem cảnh báo đầu file.
2. **Chạy `migrate.php` trước khi đăng nhập** — nếu không sẽ mất tài khoản.
3. **Đọc `CHUAN_CODE.md` trước khi viết code mới** — nhất là 3 chỗ raw còn lại trong QueryBuilder (`select()`, `join()` kiểu cũ, `$where` của `getList()`).

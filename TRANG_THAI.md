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
- [x] **TASK_79 ẩn tồn kho theo quyền** (18/07) — ✅ storefront gate tồn kho: chỉ thành viên đăng nhập mới thấy (bảng `members`, `StocksModel::totalByPart`)
- [x] **TASK_92 lọc facet** (18/07) — ✅ storefront lọc facet checkbox (danh mục/thương hiệu/xuất xứ/xe/giá/KM), `PartsModel::storefront()`

### Ưu tiên 3 — cần người có quyền, không phải code

- [ ] **Đổi mật khẩu DB** (credential cũ trong git history)
- [ ] **Xoá 16 file session khỏi git history** (`git filter-repo`)
- [ ] **Trỏ docroot vào `public/`** thay vì thư mục gốc — `.htaccess` chỉ có tác dụng trên Apache; dùng Nginx là mọi chặn vô hiệu

### Còn treo trong SRS

- **Phân hệ Kế toán**: đã soạn `KE_TOAN_SPEC_DE_XUAT.md` + **đã code KT-1 + KT-2** (18/07): danh mục tài khoản (cây, seed 19 TK) · mã phí · mã vụ việc · phiếu thu/chi (bút toán kép, tự đánh số, ghi sổ/huỷ ghi sổ + khoá) · sổ quỹ (số dư đầu/cuối kỳ + luỹ kế). Nhóm menu "Kế toán". **KT-3** (18/07): Phiếu kế toán (định khoản tự do Nợ/Có, cho điều chuyển/kết chuyển). **Đã chốt 8 câu hỏi**: TT133 · công nợ dùng bảng `partners` chung · VND · nhập tay. **KT-4** (18/07): bảng `partners` (khách/NCC dùng chung) gắn vào phiếu, **công nợ** (tổng hợp số dư + sổ chi tiết 1 đối tượng, theo TK 131/331). **KT-5** (18/07): Nhật ký chung + Sổ cái/chi tiết 1 TK (số dư đầu/cuối kỳ + luỹ kế, tính động). **→ Toàn bộ KT-1→KT-5 XONG.** KT-6 (tự sinh bút toán từ Kho/Bán hàng) hoãn tới khi có 2 phân hệ đó.
- **Phân hệ CSKH**: ✅ **XONG lát cắt CSKH-1** (18/07, migration `000019`, `CSKH_SPEC.md`): phiếu bảo hành/sửa chữa (luồng trạng thái) · lịch bảo hành (highlight quá hạn) · nhóm khách hàng (+`partners.group_id`) · **đánh giá SP TASK_84** (thành viên gửi→admin duyệt→hiện web) · báo cáo CSKH. Nhóm menu "CSKH". Hoãn: bản tin, nhắc lịch tự động.
- **Phân hệ CSKH — CSKH-2 BB giao nhận**: ✅ **XONG** (19/07, migration `000031`): biên bản giao nhận thiết bị bảo hành (BB **nhận** khi tiếp nhận / BB **trả** khi hoàn tất, số `BBGN-`) lập trong phiếu bảo hành + **bản in A4** ký giao/nhận (header lấy từ cấu hình website). Dùng chung quyền `warranty`.
- **Phân hệ Nhân sự (HR)**: ✅ **XONG lát cắt HR-1** (19/07, migration `000022`, `HR_SPEC.md`): phòng ban · chức vụ · hồ sơ nhân viên (lọc phòng/trạng thái) · đơn nghỉ phép (luồng chờ duyệt→duyệt/từ chối, số ngày tự tính). Nhóm menu "Nhân sự". Hoãn HR-2: chấm công, bảng lương, hợp đồng LĐ.
- **Nối đơn hàng ↔ hoá đơn/kho**: ✅ **XONG** (19/07, migration `000025`): đơn có `sales_invoice_id`; admin "Tạo hoá đơn bán" từ đơn → ghi sổ hoá đơn trừ tồn + doanh thu Nợ131/Có511 + giá vốn Nợ632/Có156 (KT-6). Khép vòng web→back-office. Verify: đặt 3 SP→hoá đơn→ghi sổ→tồn 50→47, doanh thu 1.05tr, giá vốn 600k.
- **Đặt hàng online**: ✅ **XONG** (19/07, migration `000024`): giỏ hàng → đặt hàng (chuyển khoản/COD, KHÔNG cổng thẻ thật) → `orders`+`order_items` → trang hoàn tất kèm hướng dẫn chuyển khoản (thông tin NH ở cấu hình). Admin `orders` quản lý + luồng trạng thái Mới→Xác nhận→Đang giao→Hoàn tất/Huỷ. Nhóm menu "Bán hàng". Hoãn: trừ tồn/liên thông hoá đơn, cổng thanh toán thật.
- **SEO + Cấu hình website**: ✅ **XONG** (19/07, migration `000023`): `site_settings` key-value + màn `admin/settings` (tên/slogan/meta/OG image/liên hệ) · storefront render động meta description/keywords + canonical + Open Graph/Twitter · tin/dự án có meta_title/meta_description riêng, chi tiết SP/tin/dự án truyền `$seo` · footer/topbar dùng cấu hình. Kèm **upload ảnh** (helper `upload_image`/`media_url`) cho tin/dự án.
- **Webchat**: ✅ **XONG** (19/07, migration `000029`, TASK_112-113): widget chat nổi ở storefront (polling, không websocket) + inbox admin `admin/chat` trả lời. **→ Storefront Phần B (36 task) cơ bản phủ hết.**
- **Thống kê truy cập**: ✅ **XONG** (19/07, migration `000028`, TASK_109-111): `visits` log lượt xem trang khách (trong layout storefront); `admin/thong-ke` — tổng lượt/khách theo 7/30/90 ngày, biểu đồ cột theo ngày, top trang/nguồn/từ khoá.
- **Menu website động**: ✅ **XONG** (19/07, migration `000027`, TASK_105-108): `menus` cây cha-con; admin `admin/menus` CRUD; storefront nav render động từ DB (dropdown submenu), thay nav hardcode. Seed 6 mục.
- **Thư viện ảnh/video + sitemap**: ✅ **XONG** (19/07, migration `000026`): album ảnh/video (`admin/galleries` → `thu-vien`), upload nhiều ảnh + video YouTube nhúng; `sitemap.xml` động từ SP/tin/dự án/thư viện. Nav web thêm "Thư viện".
- **CMS nội dung website**: ✅ **XONG** (18/07, migration `000021`, `STOREFRONT_SPEC.md`): Tin tức (`admin/news` + danh mục `admin/news-categories` → công khai `tin-tuc`, lượt xem) + Dự án/công trình (`admin/du-an` controller `Projectportfolio` → công khai `du-an`). Nav storefront thêm Tin tức/Dự án. Lưu ý: link `projects` đã là Mã vụ việc kế toán nên portfolio dùng `du-an`. Hoãn: video/thư viện ảnh, SEO, menu CMS, upload ảnh.
- **Storefront website (Phần B)**: ✅ **XONG nền storefront** (18/07, migration `000018`, `STOREFRONT_SPEC.md`): bề mặt công khai (controller gốc, CSS tự chứa) — trang chủ · danh sách + **lọc facet TASK_92** · chi tiết SP (thông số/xe tương thích/phụ kiện) · **thành viên `members` + gate tồn kho TASK_79** · giỏ hàng → gửi yêu cầu báo giá (tạo `quotations` sent → NVKD). **Mở khoá TASK_79 + TASK_92.** Hoãn: SEO/menu CMS/tin-video-dự án/analytics/webchat/thanh toán/CSKH.
- **Phân hệ Bán hàng (SAL)**: ✅ **XONG lát cắt Bán hàng-1** (18/07, migration `000017`, `KHO_BAN_HANG_SPEC.md`): báo giá (trạng thái + chuyển thành hoá đơn) · hoá đơn bán (ghi sổ sinh 1 phiếu kế toán **Nợ131/Có511 + Nợ131/Có3331 + Nợ632/Có156** và trừ tồn) · công nợ khách (dùng lại `admin/debt`) · báo cáo bán hàng (doanh thu/giá vốn/lãi gộp theo khách + NV). **KT-6 ĐÃ KHÉP VÒNG.** Hoãn: hợp đồng, chiết khấu dòng, HĐĐT.
- **Phân hệ Kho — Kho-3**: ✅ **XONG** (19/07, migration `000030`): **hàng tồn lâu** (`ton-kho-lau`, số ngày nằm kho + dải tuổi tồn, `DateTime::diff` chuẩn lịch) + **vị trí trong kho** (`warehouse-locations`, cây tối đa 5 cấp Khu→Tầng→Kệ→Ngăn→Ô, gợi ý `<datalist>` trên phiếu nhập). Nhóm menu "Kho".
- **Phân hệ Kho — Kho-2**: ✅ **XONG** (18/07, migration `000020`): điều chuyển kho (WH-05, bảo toàn giá vốn, không kế toán) + kiểm kê (WH-07, điều chỉnh thừa/thiếu + bút toán Nợ156/Có711 · Nợ632/Có156). Tựa lên StocksModel.
- **Phân hệ Kho (WH)**: ✅ **XONG lát cắt Kho-1** (18/07, migration `000016`, `KHO_BAN_HANG_SPEC.md`): danh mục kho (phẳng, kho mặc định) · phiếu nhập kho (nhap_mua/khac/tra) · phiếu xuất kho (xuat_ban/khac/tra) · **tồn kho bình quân gia quyền tức thời** · thẻ kho · tồn kho tổng hợp. **Nối KT-6 luôn**: ghi sổ phiếu tự sinh phiếu kế toán (nhập: Nợ 156/Có 331; xuất: Nợ 632/Có 156) chảy vào Nhật ký chung/Sổ cái/Công nợ. Huỷ ghi sổ khôi phục bình quân chính xác (chỉ khi là phát sinh cuối). Đã mở khoá tồn kho cho **TASK_79**. Nhóm menu "Kho". Hoãn: điều chuyển/kiểm kê/hàng tồn lâu, phân cấp kho 5 tầng, phân hệ Bán hàng.
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

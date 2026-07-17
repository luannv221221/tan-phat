# CRUD ADMIN CHO DANH MỤC

| | |
|---|---|
| **Ngày** | 17/07/2026 |
| **Trạng thái** | ✅ Đã kiểm chứng end-to-end trên trình duyệt + MySQL 8.0.44 thật |
| **Test** | 381 test, 0 fail |

---

## 1. Làm được gì

7 danh mục đã có màn hình quản trị đầy đủ (danh sách / thêm / sửa / xoá), hiện trên menu, có phân quyền:

| URL | Danh mục |
|---|---|
| `admin/car-body-types` | Dòng xe (kiểu dáng) |
| `admin/car-fuels` | Nhiên liệu |
| `admin/car-colors` | Màu xe (có chọn mã màu) |
| `admin/product-brands` | Thương hiệu phụ tùng |
| `admin/product-origins` | Xuất xứ |
| `admin/product-manufacturers` | Hãng sản xuất |
| `admin/product-units` | Đơn vị tính |

---

## 2. Viết 1 lần, dùng 7 chỗ

`App\core\LookupCrudController` chứa toàn bộ CRUD. Mỗi controller cụ thể chỉ **6 dòng**:

```php
class Carfuels extends \App\core\LookupCrudController {
    protected $modelName = 'CarFuelsModel';
    protected $routeBase = 'car-fuels';
    protected $labelOne  = 'nhiên liệu';
    protected $labelMany = 'Nhiên liệu (động cơ xe)';
}
```

Ba view dùng chung: `app/views/admin/lookup/{lists,add,edit}.php`.

Đặt lớp cha ở `core/` (không phải `app/controllers/`) để **URL không với tới được** — `App::handleUrl()` chỉ tìm file trong `app/controllers/`.

### Thêm danh mục tra cứu mới

1. Tạo bảng bằng migration (cột: `name`, `slug`, `sort_order`, `status`)
2. Model: `class XxxModel extends LookupModel { protected $_table = 'xxx'; }`
3. Controller: 6 dòng như trên
4. Thêm 1 dòng vào `$lookupModules` trong `routes/web.php`
5. Migration đăng ký vào bảng `modules` + cấp quyền Admin

---

## 3. ⚠️ Không có bước 5 thì không ai vào được

`RoleMiddleware` khớp URL với `modules.link`. Thiếu dòng trong `modules`:
- Menu **không hiện** link (`AppServiceProvider` share `listModules` ra view)
- Vào thẳng URL sẽ bị đá về `khong-co-quyen`

Migration `000006` đăng ký 7 module + cấp 4 quyền (`view/add/edit/delete`) cho nhóm **Admin**. Manager/Staff **cố ý không cấp** — để quản trị viên tự phân qua màn hình Phân quyền, không đoán thay nghiệp vụ.

---

## 4. 🔴 Bug thật đã sửa: `_WEB_URL` sinh URL hai gạch

Phát hiện khi bấm nút "Thêm mới" trên trình duyệt — ra trang 404.

**Nguyên nhân:** `_WEB_URL` kết thúc bằng `/`, mà **mọi** chỗ dùng đều nối thêm `/`:

```php
_WEB_URL.'/admin/users'    // view
_WEB_URL.'/'.$path         // Response::redirect
```

→ sinh ra `http://host/app//admin/users`.

**Vì sao lỗi này nằm im từ đầu:** Apache bỏ qua được, vì `App::handleUrl()` dùng `array_filter()` nên đoạn rỗng bị loại. Nhưng nó vỡ ở chỗ khác:

```php
parse_url('//admin/car-fuels', PHP_URL_PATH)   // => '/car-fuels'  ← SAI
```

`//admin` là cú pháp **URL protocol-relative**, nên `parse_url` hiểu `admin` là **tên máy chủ**. Bất kỳ proxy, router hay thư viện nào dùng `parse_url` đều hiểu sai URL.

**Đã sửa:** `define('_WEB_URL', rtrim($__url, '/'))`. Đã kiểm tra: cả 20 chỗ dùng `_WEB_URL` đều theo dạng `_WEB_URL . '/...'`, nên bỏ dấu gạch cuối là an toàn. `tests/HelpersTest.php` có test chặn tái phát.

---

## 5. `slugify()` — không dùng iconv

```php
slugify('Dầu (Diesel)')  // => 'dau-diesel'
slugify('Bán tải')       // => 'ban-tai'
```

**Không dùng `iconv('UTF-8','ASCII//TRANSLIT')`** vì kết quả phụ thuộc **locale của hệ điều hành**: cùng chữ `Đ` có thể ra `D`, `DJ` hoặc `?` tuỳ máy → dev Windows và server Linux sinh slug khác nhau → dữ liệu lệch. Bảng thay thế tường minh cho kết quả giống nhau ở mọi nơi.

Test quan trọng nhất: **slug tự sinh phải khớp slug đã seed trong migration**. Nếu lệch, người dùng thêm "Xăng" sẽ không bị báo trùng mà tạo bản ghi thứ hai.

Ký tự đặc biệt bị **bỏ hẳn** (không đổi thành gạch), giống Laravel `Str::slug`: `dac!!!tu` → `dactu`.

---

## 6. Xử lý lỗi cho người dùng

| Tình huống | Hành vi |
|---|---|
| Bỏ trống tên | Báo lỗi ngay trên form, giữ lại dữ liệu đã nhập |
| Bỏ trống slug | **Tự sinh từ tên** (form ghi rõ vậy nên không bắt buộc) |
| Tên toàn ký tự đặc biệt (`###`) | Slug ra rỗng → báo "Vui lòng nhập slug thủ công" thay vì để UNIQUE ném exception |
| Slug trùng | Kiểm tra **trước**, báo lỗi tử tế thay vì trang trắng |
| Xoá bản ghi còn dữ liệu con | Model trả `false` → báo "đang được dữ liệu khác sử dụng" |
| Sửa/xoá id không tồn tại | Báo "Không tìm thấy", quay về danh sách |

---

## 7. Đã kiểm chứng trên trình duyệt thật

| Kiểm tra | Kết quả |
|---|---|
| 7 danh mục hiện trên menu | ✅ |
| Danh sách render tiếng Việt có dấu | ✅ |
| Thêm "Khí nén (CNG)", **bỏ trống slug** | ✅ Tự sinh `khi-nen-cng`, báo "Thêm nhiên liệu thành công" |
| Xoá | ✅ Xoá đúng bản ghi |
| Xoá kiểu dáng đang được model dùng | ✅ `SET NULL` — model **vẫn còn**, chỉ mất phân loại (đúng thiết kế) |
| CSRF | ✅ Mọi form có token |

---

## 8. Chưa làm

| Việc | Ghi chú |
|---|---|
| **CRUD Hãng xe / Model xe / Đời xe** | Phức tạp hơn (có dropdown phụ thuộc), chưa dùng được `LookupCrudController` |
| **CRUD Phụ tùng** | Cần form gán nhiều đời xe (`syncForPart`) |
| **CRUD Danh mục phụ tùng** | Có phân cấp cha-con |
| Phân trang | Danh mục ít bản ghi nên chưa cần; phụ tùng thì bắt buộc |
| Upload logo | `car_brands.logo`, `product_brands.logo` đã có cột, chưa có form |

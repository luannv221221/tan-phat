# CHUẨN CODE — Dự án ERP Tân Phát

| | |
|---|---|
| **Ngày** | 17/07/2026 |
| **Áp dụng** | Mọi code viết sau Phase 0 |
| **Kiểm tra** | `C:\xampp\php\php.exe tests\run.php` phải xanh trước khi commit |

> Tài liệu này ngắn có chủ đích. Nó chỉ ghi những quy tắc mà **vi phạm sẽ gây lỗ hổng hoặc mất dữ liệu**. Không bàn về dấu cách hay đặt tên biến.

---

## 1. 🔴 SQL — ba chỗ RAW còn lại trong QueryBuilder

QueryBuilder đã tự động bảo vệ **giá trị** (bound parameters), **tên cột** (`wrapField`) và **toán tử** (whitelist). Nhưng còn **ba chỗ vẫn nhận chuỗi thô**. Đây là toàn bộ bề mặt tấn công SQL còn lại của hệ thống:

| Chỗ | Ví dụ | Quy tắc |
|---|---|---|
| `select($fields)` | `select('users.id, groups.name as g')` | ❌ **KHÔNG** đưa dữ liệu từ `$_GET`/`$_POST` vào |
| `join()/leftJoin()` — tham số `$relation` | `join('groups', 'users.group_id=groups.id')` | ⚠️ **Dùng `joinOn()` thay thế** (xem mục 2) |
| `getList($where)` / `getLimit($where)` / `update($where)` / `delete($where)` | `delete('users', '`id` = ?', [$id])` | ✅ Chỉ viết **tên cột + `?`**. Giá trị luôn qua mảng bindings |

**Sai:**
```php
$this->delete($this->_table, "group_id=$id");           // ❌ nối giá trị vào chuỗi
$this->getList('name = "'.$_GET['name'].'"');           // ❌ dữ liệu người dùng
$this->table('users')->select($_GET['fields']);         // ❌ không bao giờ
```

**Đúng:**
```php
$this->delete($this->_table, '`group_id` = ?', [$id]);
$this->getList('`name` = ?', [$_GET['name']]);
$this->table('users')->where('name', '=', $_GET['name'])->get();
```

> **Quy tắc một câu**: nếu một giá trị có thể do người dùng nhập, nó **phải** đi qua `?` + bindings. Không có ngoại lệ.

---

## 2. 🔴 JOIN — luôn dùng `joinOn()`

```php
// ✅ ĐÚNG — builder tự bọc backtick
->joinOn('groups', 'users.group_id', 'groups.id')
->leftJoinOn('groups', 'users.group_id', 'groups.id')

// ❌ SAI — tự viết chuỗi ON
->join('groups', 'users.group_id=groups.id')
```

**Vì sao:** `groups` là **từ khoá dành riêng của MySQL 8.0** (window function `GROUPS`). Viết không backtick → lỗi cú pháp. Các từ khoá khác dễ dính trong dự án này: `rank`, `rows`, `groups`, `system`, `lead`, `first_value`.

Bug này đã có thật: `GroupsModel::getGroupByUser()` sinh ra `groups.id` không backtick, và hàm đó chạy ở **mọi** request admin.

---

## 3. 🔴 Mật khẩu

```php
// ✅
$hash = Hash::make($plain);
if (Hash::check($plain, $user['password'])) { ... }

// ❌ không bao giờ
md5($password);  sha1($password);  hash('sha256', $password);
```

- Cột `password` phải là **VARCHAR(255)** — bcrypt dài 60, `VARCHAR(50)` cũ sẽ **cắt cụt và khoá tài khoản vĩnh viễn**.
- Sinh token/khoá ngẫu nhiên: `Hash::randomToken()`, **không** dùng `md5(uniqid())` (uniqid dựa trên thời gian → đoán được).

---

## 4. 🔴 Transaction — bắt buộc khi ghi nhiều bảng

```php
$db->transaction(function($d) use ($data) {
    $d->insert('goods_receipts', $data['phieu']);
    $id = $d->lastId();
    foreach ($data['items'] as $item){
        $d->insert('goods_receipt_items', $item + ['receipt_id' => $id]);
    }
    $d->update('stock', ['qty' => $newQty], '`id` = ?', [$stockId]);
});
```

Nghiệp vụ **bắt buộc** bọc transaction: nhập/xuất/điều chuyển kho, kiểm kê, phiếu thu/chi, phiếu kế toán, hợp đồng, báo giá có nhiều dòng.

> Không bọc transaction ở phiếu điều chuyển kho = kho A trừ, kho B không cộng = **hàng bốc hơi trên sổ**.

**Lưu ý:** đừng bọc lệnh DDL (`CREATE`/`ALTER`/`DROP`) trong transaction — MySQL tự commit ngầm, transaction chỉ là ảo giác an toàn.

---

## 5. 🔴 Form — luôn có CSRF token

```php
<form method="post">
    <?php echo csrf_field(); ?>
    ...
</form>
```

Thiếu → `CsrfMiddleware` trả **HTTP 419**. Middleware đăng ký ở `global_middleware` nên không thể quên bảo vệ, chỉ có thể quên nhúng token.

AJAX: gửi header `X-CSRF-Token: <?php echo csrf_token(); ?>`.

`tests/SecurityTest.php` **quét tự động** mọi form POST — thêm form mà quên token là test đỏ.

---

## 6. 🔴 In dữ liệu ra view — luôn escape

```php
{{ $user['name'] }}                  <!-- ✅ Template tự htmlentities -->
<?php echo e($user['name']); ?>      <!-- ✅ khi buộc dùng PHP thuần -->

<?php echo $user['name']; ?>         <!-- ❌ XSS lưu trữ -->
{!! $user['name'] !!}                <!-- ❌ chỉ dùng cho HTML mình tự viết -->
```

`{!! !!}` chỉ được dùng khi nội dung là HTML **do lập trình viên viết**, không có dữ liệu người dùng.

`tests/SecurityTest.php` quét tự động toàn bộ view.

---

## 7. 🟠 Migration — mọi thay đổi DB

```
C:\xampp\php\php.exe migrate.php make create_hang_xe_table
C:\xampp\php\php.exe migrate.php            # chạy
C:\xampp\php\php.exe migrate.php status     # xem trạng thái
C:\xampp\php\php.exe migrate.php rollback   # lùi batch gần nhất
```

- **Không** sửa DB bằng tay hay bằng phpMyAdmin. Sửa tay = máy người khác không có = nguyên nhân đổ vỡ #4.
- `down()` phải đảo ngược đúng `up()`.
- Migration đã chạy trên máy người khác thì **không sửa nữa** — viết migration mới.

---

## 8. 🟠 Cấu hình — không hardcode

```php
// ✅
Env::get('DB_HOST', 'localhost')

// ❌
define('_PASS', 'matkhau123');
define('_WEB_URL', 'http://localhost/Unicode/2021/FRAMEWORK/...');
```

Thêm khoá mới → **phải** thêm vào `.env.example` kèm chú thích, nếu không người mới clone về sẽ không chạy được.

**Không bao giờ commit `.env`.**

---

## 9. 🟡 Model

- Một model một bảng. Một thư mục views một controller.
- Không tái dùng một instance model cho nhiều truy vấn khác nhau trong cùng một hàm nếu thấy khó theo dõi — builder đã reset state sau `get()/first()`, nhưng code rõ ràng vẫn hơn.
- Model tự verify mật khẩu (`checkLogin`), controller không băm.

---

## 10. Trước khi commit

```
C:\xampp\php\php.exe tests\run.php      # phải PASS hết
C:\xampp\php\php.exe -l <file da sua>   # lint
```

Sửa code lõi (`core/`) mà không thêm test → **không merge**.

---

## Phụ lục: những bẫy đã cắn dự án này

Ghi lại để không ai vấp lại:

| Bẫy | Hậu quả thật |
|---|---|
| `catch (Exception)` trong file có `namespace` | Bắt `App\core\Exception` (không tồn tại) → **không bao giờ bắt được** |
| `?>` trong comment `//` | **Thoát khỏi chế độ PHP** → hỏng toàn bộ phần còn lại của file (comment `/* */` thì không sao) |
| `if (!$this->_conn)` với `_conn` là thuộc tính instance | Luôn đúng → mỗi `new Model()` mở một kết nối MySQL mới |
| `die()` trong tầng DB | `transaction()` không rollback được, `catch` của caller không chạy |
| `VARCHAR(50)` cho mật khẩu | bcrypt dài 60 → **cắt cụt, khoá tài khoản vĩnh viễn**, không báo lỗi |
| Test bằng SQLite | **Bỏ qua độ dài VARCHAR** → không bắt được lỗi trên |
| `RewriteCond %{REQUEST_FILENAME} !-f` | File có thật **không** qua `index.php` → `.env`, `.sql`, file session tải về được |
| `md5(uniqid())` làm token | `uniqid()` dựa trên thời gian → đoán được |
| `get_magic_quotes_gpc()` | **Xoá khỏi PHP 8.0** → fatal error |
| `groups` không backtick | **Từ khoá dành riêng MySQL 8.0** → lỗi cú pháp |
| Quét cả bảng rồi xoá từng dòng | 1.000 user = 1.000 câu DELETE mỗi request |

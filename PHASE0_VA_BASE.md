# PHASE 0 — VÁ BASE TRƯỚC KHI TRIỂN KHAI ERP

| | |
|---|---|
| **Quyết định** | Giữ framework tự viết (`core/` — 2.094 dòng) |
| **Ngày** | 17/07/2026 |
| **Nguyên tắc** | Không viết dòng nghiệp vụ nào cho tới khi hết mục 🔴 BLOCKER |
| **Cơ sở** | Khảo sát trực tiếp `core/*.php` ngày 17/07/2026 |

> **Vì sao phải có Phase 0**: Base hiện tại có 5 lỗi khiến **không thể** dùng cho ERP có dữ liệu khách hàng, hóa đơn và kế toán. Chồng 130 chức năng lên nguyên trạng = lặp lại nguyên nhân đổ vỡ #5 và #6 trong sheet Help.

---

## 1. 🔴 BLOCKER — phải xong trước khi viết nghiệp vụ

### B1. SQL Injection toàn bộ tầng `where()`

**Bằng chứng** — `core/QueryBuilder.php:57-60`:
```php
if (is_numeric($value)){
    $where = "$field $compare $value";
} else {
    $where = "$field $compare '$value'";   // ← nối thẳng, KHÔNG escape
}
```

`escape()` chỉ được gọi ở **2 chỗ duy nhất** (`Database.php:95` trong `insert()`, `Database.php:117` trong `update()`). Toàn bộ các hàm sau **không escape gì cả**:

| Hàm | File:dòng | Tình trạng |
|---|---|---|
| `where()` | QueryBuilder.php:57,60 | ❌ Nối chuỗi trần |
| `orWhere()` | QueryBuilder.php:97,100 | ❌ Nối chuỗi trần |
| `whereLike()` | QueryBuilder.php:115 | ❌ Nối chuỗi trần |
| `whereOrLike()` | QueryBuilder.php:135 | ❌ Nối chuỗi trần |
| `whereIn()` / `whereNotIn()` | QueryBuilder.php:156,180 | ❌ `implode` thẳng |
| `whereOrIn()` / `whereOrNotIn()` | QueryBuilder.php:202,224 | ❌ `implode` thẳng |
| `delete()` | Database.php:132 | ❌ `$where` truyền trần |
| `having()` | QueryBuilder.php:288 | ❌ Nối chuỗi trần |

**Ví dụ khai thác** — form đăng nhập gọi `->where('email', '=', $_POST['email'])`:
```
email = ' OR '1'='1
→ SELECT * FROM users WHERE email = '' OR '1'='1'
→ Đăng nhập bằng tài khoản đầu tiên trong bảng (thường là Admin)
```

**`prepare()` ở `Database.php:29` KHÔNG bảo vệ gì** — vì giá trị đã bị nối vào chuỗi SQL từ trước, `execute()` gọi không tham số:
```php
$statement = $this->_conn->prepare($this->__last_query);  // SQL đã chứa giá trị rồi
$statement->execute();                                     // không bind gì
```
Đây là prepared statement hình thức, không có tác dụng chống injection.

**Cách sửa**: Viết lại QueryBuilder dùng **bound parameters thật**. Tích lũy mảng `$bindings[]` song song với chuỗi SQL dùng placeholder `?`, rồi `execute($bindings)`.

---

### B2. Không có transaction — kế toán và kho không thể đúng

**Bằng chứng**: `grep -rniE "beginTransaction|commit|rollBack" core/ app/` → **0 kết quả**.

Nghiệp vụ bắt buộc phải nguyên tử (all-or-nothing):

| Nghiệp vụ | Nếu đứt giữa chừng |
|---|---|
| Phiếu nhập kho | Tồn kho tăng nhưng công nợ NCC không ghi → lệch sổ |
| Phiếu thu | Trừ công nợ khách nhưng không vào sổ quỹ → mất tiền trên sổ |
| Phiếu điều chuyển kho | Kho A trừ, kho B không cộng → **bốc hơi hàng** |
| Phiếu kế toán | Ghi Nợ không ghi Có → sai nguyên tắc kép, không cân đối |

**Cách sửa**: Thêm `beginTransaction()` / `commit()` / `rollBack()` vào `Database`, và bắt buộc mọi service ghi nhiều bảng phải bọc transaction.

---

### B3. `escape()` dùng hàm đã bị xóa khỏi PHP 8

**Bằng chứng** — `core/Database.php:71-80`:
```php
function escape($value){
    if(!get_magic_quotes_gpc()){     // ← XÓA khỏi PHP 8.0
        $value = addslashes($value);
    } else {
        $value = stripslashes($value);
    }
    return $value;
}
```

| PHP | Hành vi |
|---|---|
| ≤ 7.3 | Chạy |
| 7.4 | `E_DEPRECATED` |
| **≥ 8.0** | **Fatal error: Call to undefined function** → `insert()` và `update()` chết |

Nghĩa là base này **khóa cứng vào PHP ≤ 7.4**. PHP 7.4 đã **hết hạn hỗ trợ (EOL) từ 28/11/2022** — gần 4 năm không còn bản vá bảo mật. Chạy ERP có dữ liệu khách hàng trên PHP EOL là rủi ro tuân thủ, không chỉ rủi ro kỹ thuật.

Ngoài ra `addslashes()` **không phải** cơ chế chống injection an toàn (bị vượt qua với charset đa byte).

**Cách sửa**: Xóa hẳn `escape()`. Dùng bound parameters (B1) — khi đó không cần escape thủ công nữa. Nâng lên PHP 8.2+.

---

### B4. Mật khẩu hash bằng MD5

**Bằng chứng**: `app/controllers/Auth.php` dùng `md5()`. Bảng `users` trong dump có các hash giống hệt nhau (cùng mật khẩu seed, không salt).

MD5 không salt: tra bảng rainbow ra mật khẩu trong vài giây.

**Cách sửa**: `password_hash($pw, PASSWORD_DEFAULT)` + `password_verify()`. Migration: đổi cột `password` sang `VARCHAR(255)`, bắt toàn bộ user đổi mật khẩu ở lần đăng nhập kế tiếp.

---

### B5. `catch (Exception)` không bao giờ bắt được — lộ thông tin

**Bằng chứng** — `Connection.php:36` và `Database.php:35` đều nằm trong `namespace App\core;` nhưng viết:
```php
catch (Exception $exception){    // ← PHP hiểu là App\core\Exception
```
Class `App\core\Exception` **không tồn tại**. `PDOException` nằm ở global namespace (`\PDOException`), nên **không khớp** → khối catch là code chết.

`Connection.php:29` đã bật `PDO::ERRMODE_EXCEPTION`, nên PDO **có** ném exception. Kết quả: mọi lỗi truy vấn thành **fatal error chưa bắt**, in stack trace ra trình duyệt — kèm câu SQL và có thể cả thông tin kết nối.

**Cách sửa**: Đổi thành `catch (\PDOException $e)` (hoặc `\Throwable`), ghi log ra file, trả trang lỗi chung. Tắt `display_errors` ở production.

---

## 2. 🟠 HIGH — xong trong Phase 0

### H1. Query state không reset → kết quả sai khi tái dùng model

`QueryBuilder.php:9` khai báo `$whereQuery` là **thuộc tính instance**, và `get()`/`first()` **không reset** sau khi chạy. Tương tự `Database.php:19` `$__update_set` chỉ khởi tạo một lần.

```php
$m->table('users')->where('id','=',1)->get();   // WHERE id = 1
$m->table('users')->where('id','=',2)->get();   // WHERE id = 1 AND id = 2  ← SAI
```
Với `update()` gọi 2 lần trên cùng instance, lần 2 sẽ **kèm luôn các cột của lần 1**.

**Cách sửa**: Reset toàn bộ state trong `get()`/`first()`/`update()` sau khi build xong SQL.

### H2. Không có CSRF protection

`grep -rniE "csrf|_token" core/ app/` → không có gì. Mọi form POST (báo giá, phiếu chi, xóa dữ liệu) đều bị CSRF.

**Cách sửa**: Sinh token trong session, nhúng vào form, verify trong middleware cho mọi POST/PUT/DELETE.

### H3. Session lưu trong thư mục web-accessible

`public/logs/session/` — chỉ chặn bằng `.htaccess`. Nếu đổi sang Nginx hoặc `.htaccess` bị bỏ qua → lộ toàn bộ session, chiếm quyền tài khoản. **16 file session thật đã bị commit vào repo.**

**Cách sửa**: Chuyển `session.save_path` ra ngoài webroot. Xóa 16 file khỏi repo và khỏi lịch sử git.

### H4. Credential DB commit trong repo

`config.php` chứa `_DB/_HOST/_USER/_PASS` hardcode. `_WEB_URL` cũng hardcode theo máy local.

**Cách sửa**: Chuyển sang `.env` + `.gitignore`. Xóa khỏi lịch sử git, đổi mật khẩu DB.

### H5. Không có migration

60 bảng, nhiều người làm, không có cơ chế version DB → mỗi máy một schema. Đây là nguyên nhân đổ vỡ #4 (*"không thống nhất mô hình"*).

**Cách sửa**: Viết migration runner tối thiểu (bảng `migrations` + thư mục `database/migrations/` + lệnh CLI up/rollback). ~200 dòng.

---

## 3. 🟡 MEDIUM — làm được trong Phase 0 thì tốt

| # | Vấn đề | Cách sửa |
|---|---|---|
| M1 | `AuthMiddleware` quét **toàn bộ** bảng `login_token` mỗi request | Index `user_id`, `token`; xóa token hết hạn bằng cron |
| M2 | `SET NAMES utf8` (3-byte) — không lưu được emoji, dễ lỗi dấu | Đổi sang `utf8mb4` |
| M3 | Không có test nào | PHPUnit + test cho QueryBuilder (đặc biệt binding) |
| M4 | `Model` không có quan hệ | Thêm hasMany/belongsTo hoặc chấp nhận join tay |
| M5 | Không có phân trang | Helper paginate (bảng `options` đã có sẵn chỗ cấu hình) |
| M6 | View trộn `{{ }}` (có `htmlentities`) và `<?php echo ?>` (không escape) | Thống nhất: mọi output qua `{{ }}` |

---

## 4. Thứ tự làm Phase 0

```
Tuần 1   B1 (bound parameters) + B3 (bỏ escape) + M3 (test cho QueryBuilder)
         ↑ Làm cùng nhau: sửa B1 là B3 tự hết. Test là bắt buộc — đây là tầng
           mọi thứ khác đứng lên.
Tuần 2   B2 (transaction) + H1 (reset state) + B5 (catch \PDOException)
Tuần 3   B4 (bcrypt) + H2 (CSRF) + H3 (session) + H4 (.env)
Tuần 4   H5 (migration runner) + M1 + M2
Tuần 5-6 Thiết kế ERD đầy đủ ~60 bảng + migration cho cây danh mục xe
```

**Ước lượng**: 4–6 tuần cho 1–2 dev. Chưa có dòng nghiệp vụ nào.

> ⚠️ Đây là ước lượng thô. Nguyên nhân đổ vỡ #2 là *"không đánh giá được thời gian"* — nên hãy làm tuần 1 trước, đo tốc độ thật, rồi ước lượng lại phần còn lại.

---

## 5. Định nghĩa "xong Phase 0"

Không chuyển sang nghiệp vụ cho tới khi **tất cả** dòng sau đều đúng:

- [x] Không còn chỗ nào nối giá trị vào chuỗi SQL — toàn bộ qua bound parameters ✅ *17/07*
- [x] Có test chứng minh `where("x", "=", "' OR '1'='1")` không đăng nhập được ✅ *17/07*
- [x] `beginTransaction/commit/rollBack` có thật, có test chứng minh rollback hoạt động ✅ *17/07*
- [x] Chạy được trên PHP 8 (không còn `get_magic_quotes_gpc`) ✅ *17/07 — test chạy trên PHP 8.0.30*
- [x] Lỗi DB được ghi log, không in stack trace ra trình duyệt ✅ *17/07*
- [x] Tái dùng model 2 lần liên tiếp cho kết quả đúng (test H1) ✅ *17/07*
- [x] Mật khẩu dùng `password_hash`, không còn `md5(` trong code nghiệp vụ ✅ *17/07*
- [x] Mọi form POST có CSRF token ✅ *17/07 — 6/6 form, có test quét tự động*
- [x] Session nằm ngoài webroot; 16 file session đã xóa ✅ *17/07*
- [x] `config.php` không còn credential ✅ *17/07 — chuyển sang `.env`*
- [x] `php migrate.php` dựng được DB trắng từ đầu ✅ *17/07 — có rollback + status*
- [x] **Chạy `php migrate.php` trên MySQL thật** ✅ *17/07 — MySQL 8.0.44, xem 5.10*
- [x] **Đăng nhập thật trên trình duyệt** ✅ *17/07 — xem 5.10*
- [ ] **Đã đổi mật khẩu DB** ← còn lại của H4, cần người có quyền DB làm

### 5.1 Đã xong — tuần 1 (17/07/2026)

| Mã | Nội dung | Bằng chứng |
|---|---|---|
| **B1** | QueryBuilder viết lại dùng bound parameters; whitelist toán tử; kiểm tra tên cột | 29 test trong `tests/QueryBuilderTest.php` |
| **B2** | Thêm `beginTransaction/commit/rollBack/transaction()` vào `Database` | Test rollback trong `tests/DatabaseIntegrationTest.php` |
| **B3** | Xoá `escape()` + `get_magic_quotes_gpc()` + `addslashes()` | Test chạy được trên PHP 8.0.30 |
| **B5** | `catch (\PDOException)` thay cho `catch (Exception)`; ghi log thay vì in stack trace | `core/Database.php`, `core/Connection.php` |
| **H1** | Reset query state sau mỗi `get()/first()`; `update()` dùng biến cục bộ | Test tái dùng instance |
| **M2** | `SET NAMES utf8mb4` thay `utf8`; tắt `ATTR_EMULATE_PREPARES` | `core/Connection.php` |
| **M3** | 66 test tự động | `php tests\run.php` → exit 0 |

### 5.2 Đã xong — đợt 2 (17/07/2026)

| Mã | Nội dung | Bằng chứng |
|---|---|---|
| **M7** | `Connection` dùng chung 1 PDO (`static $_shared`) | `tests/EnvTest.php` — 2 instance trả về cùng object PDO |
| **H4** | Credential ra `.env`; thêm `.env.example`, `.gitignore`; `_WEB_URL` hết hardcode | `tests/EnvTest.php` |
| **B4** | `Hash` dùng bcrypt; md5 cũ **tự nâng cấp** khi đăng nhập đúng; token dùng `random_bytes` thay `md5(uniqid())` | `tests/HashTest.php` — 28 test |
| **H2** | `CsrfMiddleware` đăng ký **global**; `csrf_field()`; 6/6 form đã nhúng token | `tests/SecurityTest.php` — có test **quét tự động** mọi form POST |
| **H3** | Session ra `storage/sessions`; xoá 16 file lộ; thêm `.htaccess` chặn; cookie `httponly`+`samesite`; `regenerate()` chống session fixation | `tests/SecurityTest.php` |

**Tổng: 146 test, 0 fail.**

**Chạy test:**
```
C:\xampp\php\php.exe tests\run.php
```

**File đã đổi/thêm:**
`core/QueryBuilder.php`, `core/Database.php`, `core/Model.php`, `core/Connection.php`, `core/Session.php`, `core/Env.php` *(mới)*, `core/Hash.php` *(mới)*, `config.php`, `configs/session.php`, `configs/app.php`, `app/controllers/Auth.php`, `app/controllers/admin/Users.php`, `app/models/UsersModel.php`, `app/models/PermissionsModel.php`, `app/models/LoginToken.php`, `app/middlewares/CsrfMiddleware.php` *(mới)*, `app/helpers/functions.php`, 6 file view, `.env`/`.env.example`/`.gitignore`

**Bản gốc:** `_backup_truoc_phase0/`

### 5.3 🔴 Phát hiện: file session TỪNG tải về được qua web

Khi làm H3 mới thấy vấn đề **nặng hơn** mô tả ở mục 2 (H3 ban đầu ghi "chỉ chặn bằng .htaccess"). Thực tế:

- `public/logs/` **không hề có `.htaccess`** nào.
- `.htaccess` gốc có `RewriteCond %{REQUEST_FILENAME} !-f` → **file có thật được web server phục vụ thẳng**, không qua `index.php`.

Nghĩa là bất kỳ ai cũng tải được `http://host/<app>/public/logs/session/sess_xxxxx` rồi chiếm phiên đăng nhập. **Đây là lỗ đang mở, không phải rủi ro lý thuyết.** 16 file session thật đã nằm sẵn ở đó.

Đã xử lý: chuyển sang `storage/sessions`, xoá 16 file, thêm `.htaccess` chặn cho cả `public/logs/` và `storage/`.

> ⚠️ **Việc cần làm ngoài code**: 16 file đó đã bị commit vào git. Phải xoá khỏi **lịch sử git** (`git filter-repo`), và coi như mọi phiên cũ đã lộ.

### 5.4 🔴 Phát hiện chặn đăng nhập: cột `users.password` chỉ VARCHAR(50)

Phát hiện khi đọc dump để viết migration. Schema cũ:

```sql
`password` varchar(50)
```

**Hash bcrypt dài đúng 60 ký tự.** Ghi 60 ký tự vào `VARCHAR(50)` trên MySQL:

| Chế độ MySQL | Hậu quả |
|---|---|
| `STRICT_TRANS_TABLES` (mặc định MySQL 8) | Lỗi `Data too long for column 'password'` |
| Không strict | **Cắt cụt còn 50 ký tự, không báo gì** → `password_verify()` luôn false → **không ai đăng nhập được**, và không có thông báo lỗi nào để lần ra |

Vì sao schema cũ để 50 là hợp lý: md5 chỉ dài 32 ký tự.

**Vì sao 190 test vẫn xanh mà không bắt được:** toàn bộ test chạy trên SQLite, mà **SQLite bỏ qua độ dài VARCHAR**. Đây chính là loại lỗi mà mục 5.5 cảnh báo — và nó có thật.

Đã xử lý: migration `2026_07_17_000002_fix_password_column_length.php` đổi cột sang `VARCHAR(255)`, và baseline tạo `VARCHAR(255)` ngay từ đầu. `tests/MigratorTest.php` có test chứng minh hash bị cắt còn 50 thì `password_verify()` thất bại.

> ⚠️ **Bắt buộc chạy `php migrate.php` TRƯỚC khi ai đó đăng nhập lần đầu sau khi triển khai bản này.** Nếu không, người đầu tiên đăng nhập sẽ kích hoạt nâng cấp hash md5→bcrypt, hash bị cắt cụt, và tài khoản đó **mất quyền truy cập vĩnh viễn**.

### 5.5 Phát hiện: `die()` trong `query()` làm hỏng rollback

Khi viết Migrator mới thấy: `Database::query()` (do chính tôi viết ở đợt 1) gọi `die()` khi lỗi SQL. Hậu quả:

- `transaction()` **không bao giờ rollback được** — `die()` giết script trước khi khối `catch` chạy tới. Chính thứ B2 sinh ra để bảo vệ lại bị vô hiệu.
- `Migration::hasTable()` không dò được bảng.
- Mọi caller mất khả năng tự xử lý lỗi.

Đã sửa: `query()` nay **ném** `RuntimeException` (giữ `PDOException` gốc làm `previous`), chi tiết SQL chỉ lộ khi `_DEBUG=true`. Thêm `set_exception_handler` trong `bootstrap.php` làm lưới cuối để exception chưa bắt vẫn ra trang thân thiện, không lộ stack trace.

`tests/DatabaseIntegrationTest.php` có test cho đúng kịch bản này: một query lỗi giữa transaction phải rollback cả các lệnh trước đó.

### 5.6 Đã xong — đợt 3 (17/07/2026)

| Mã | Nội dung | Bằng chứng |
|---|---|---|
| **M10** | Thêm `joinOn()/leftJoinOn()` bọc backtick tự động. Sửa `GroupsModel::getGroupByUser()` — bản cũ sinh `groups.id` không backtick, mà `GROUPS` là **từ khoá dành riêng MySQL 8.0** → lỗi cú pháp ở **mọi** request admin | `tests/QueryBuilderTest.php` |
| **M1** | `LoginToken::removeExpired()` — **1 câu DELETE** thay cho "nạp cả bảng + xoá từng dòng". Dọn thêm token `current_activity = NULL` mà bản cũ **bỏ sót vĩnh viễn** | `tests/ModelsSmokeTest.php` |
| **M11** | Bỏ `php_value error_reporting -1` khỏi `.htaccess`; `display_errors` theo `APP_DEBUG`; **chặn `.env`, `.sql`, `.md`, `composer.*`**; `Options -Indexes`; thêm `.htaccess` cho `database/` và `tests/` | `tests/SecurityTest.php` |
| **M6** | Rà XSS toàn bộ view; sửa 5 chỗ `<?php echo $bien` chưa escape | `tests/SecurityTest.php` — có test **quét tự động** mọi view |
| **M9** | Viết `CHUAN_CODE.md` — quy tắc cho 3 chỗ raw còn lại + phụ lục các bẫy đã cắn dự án | `CHUAN_CODE.md` |

**Tổng: 217 test, 0 fail.**

### 5.7 🔴 Lỗ do CHÍNH `.env` tạo ra (đã bịt)

Đặt `.env` ở thư mục gốc là chuẩn, **nhưng** `.htaccess` gốc có `RewriteCond %{REQUEST_FILENAME} !-f` → file **có thật** không đi qua `index.php` mà được web server phục vụ thẳng. Nghĩa là `http://host/<app>/.env` **tải về được** → lộ mật khẩu DB.

Đây là lỗ do đợt H4 tự tạo ra. Đã bịt bằng `<FilesMatch "^\.env">` trong `.htaccess`.

Cùng cơ chế đó, hai thứ **đã lộ từ trước**:
- `database/tanphat_php_11_12_2021.sql` — dump chứa toàn bộ dữ liệu + hash mật khẩu, tải về được.
- `public/logs/session/sess_*` — 16 file session (đã xử lý ở 5.3).

Đã chặn cả `.sql`, `.log`, `.lock`, `.md`, `composer.*`, và thêm `.htaccess` riêng cho `database/`.

> ⚠️ `.htaccess` **chỉ có tác dụng trên Apache**. Nếu triển khai bằng Nginx thì toàn bộ chặn này **vô hiệu** — phải cấu hình lại trong server block, và tốt nhất là trỏ docroot vào `public/` thay vì thư mục gốc dự án.

### 5.8 Còn lại — chưa làm

| Mã | Vấn đề | Mức |
|---|---|---|
| **H4b** | **Chưa đổi mật khẩu DB** — credential cũ đã nằm trong git history. Cần người có quyền DB. | 🟠 HIGH |
| **DOC-1** | Docroot đang là thư mục gốc dự án. Nên trỏ vào `public/` để không phụ thuộc `.htaccess` (xem 5.7). | 🟠 HIGH |
| **M9** | `join()`/`leftJoin()` nhận `$relation` raw; `select()` và `$where` của `getList()` cũng raw. An toàn *nếu* chỉ lập trình viên truyền. **Phải ghi vào chuẩn code**: không bao giờ đưa dữ liệu người dùng vào 3 chỗ này. | 🟡 MED |
| **M10** | `groups` là **từ khoá dành riêng của MySQL 8.0** — `$relation` không bọc backtick sẽ lỗi khi nâng MySQL 8. | 🟡 MED |
| **M11** | `.htaccess` gốc có `php_value error_reporting -1` → bật hiển thị mọi lỗi. Phải tắt ở production. | 🟡 MED |
| **M4** | `Model` chưa có quan hệ (hasMany/belongsTo) — 60 bảng sẽ phải join tay. | 🟡 MED |
| **M5** | Chưa có phân trang. | 🟡 MED |
| **M6** | View còn trộn `{{ }}` (có escape) và `<?php echo ?>` (không escape). Đã thêm helper `e()`, cần rà lại toàn bộ view. | 🟡 MED |

### 5.9 ✅ ĐÃ kiểm chứng trên MySQL thật (17/07/2026)

Trước đây mục này ghi "chưa kiểm chứng được" vì thiếu mật khẩu DB. **Đã có mật khẩu và chạy xong.**

**Môi trường thật:** MySQL **8.0.44** @ `127.0.0.1:3306`, `sql_mode` có `STRICT_TRANS_TABLES`.

**Cách làm:** import dump cũ (`tanphat_php_11_12_2021.sql`) vào DB trắng → đúng trạng thái mà bản này sẽ gặp khi triển khai → chạy `php migrate.php` → đăng nhập thật.

`tests/MySqlLiveTest.php` — **27 test chạy trên MySQL thật**, tự bỏ qua (skip) nếu không kết nối được nên `tests/run.php` vẫn chạy được trên máy không có DB.

**Tổng: 246 test, 0 fail** (219 SQLite + 27 MySQL thật).

#### Bug VARCHAR(50) — chứng minh trực tiếp trên MySQL 8

```
Hash bcrypt cần ghi: $2y$10$UNSRz7yrjJUb9Lazyv... (60 ký tự)
Cột chứa được: 50

>>> MySQL TỪ CHỐI: SQLSTATE[22001]: String data, right truncated:
    1406 Data too long for column 'password' at row 1
```

Không còn là suy luận. Dump cũ có **5 user cùng một hash** `e10adc3949ba59abbe56e057f20f883e` = md5 của `123456` — đúng như mục B4 nói về md5 không salt.

Sau `php migrate.php`: `users.password` → `varchar(255)`, dữ liệu nguyên vẹn (5 user, 3 group, 12 permission).

#### Đăng nhập thật trên trình duyệt

| Kiểm tra | Kết quả |
|---|---|
| Đăng nhập user hash **md5** bằng mật khẩu thô | ✅ Vào được |
| Hash sau khi đăng nhập | ✅ Tự nâng cấp `$2y$10$2M9VY1tX...` — **60 ký tự, không bị cắt** |
| Token đăng nhập | ✅ 64 ký tự ngẫu nhiên (bản cũ: `md5(uniqid())` = 32, đoán được) |
| Cookie session | ✅ `HttpOnly; SameSite=Lax` |
| Trang `/admin/users` | ✅ Render đủ menu + cột "Nhóm" (Admin/Manager/Staff) |
| `GroupsModel::getGroupByUser()` (bug M10) | ✅ Chạy được trên MySQL 8 |
| Phân quyền | ✅ Nút xoá chính mình bị vô hiệu |

#### Tấn công thử — trên app đang chạy

| Tấn công | Kết quả |
|---|---|
| `POST /dang-nhap` **không** CSRF token | ✅ **HTTP 419** |
| `POST /dang-nhap` token bịa đặt | ✅ **HTTP 419** |
| `POST /dang-nhap` payload `' OR '1'='1` (kèm CSRF token **hợp lệ**) | ✅ Không tạo `login_token` nào → **injection thất bại** |
| `GET /.env` | ✅ **HTTP 403** |
| `GET /database/tanphat_php_11_12_2021.sql` | ✅ **HTTP 403** |

### 5.10 🔴 Bug MỚI chỉ MySQL thật mới lộ: `IF NOT EXISTS` bỏ qua utf8mb4

Chạy trên MySQL thật lộ ra một lỗi **trong chính migration 000001** của tôi:

```
SQLSTATE[HY000]: General error: 3988
Conversion from collation utf8mb4_general_ci into utf8mb3_unicode_ci impossible for parameter
```

**Nguyên nhân:** migration 000001 dùng `CREATE TABLE IF NOT EXISTS ... CHARSET=utf8mb4`. Với DB trắng thì đúng. Nhưng với DB **đã có sẵn** (import từ dump cũ), bảng đã tồn tại → `IF NOT EXISTS` **bỏ qua hoàn toàn** → bảng vẫn `utf8mb3`. Comment trong migration ghi *"utf8mb4 thay utf8"* nhưng thực tế **không đổi gì**.

Kiểm tra xác nhận 6/7 bảng vẫn là `utf8mb3_unicode_ci` — chỉ `migrations` (bảng tạo mới) là utf8mb4.

**Hậu quả:** connection dùng utf8mb4, INSERT tham số 4 byte (emoji) vào cột utf8mb3 → MySQL 8 từ chối. Với dữ liệu ERP (tên hàng hoá, ghi chú khách hàng) đây là lỗi **chặn nghiệp vụ**.

**SQLite không thể bắt được** vì nó không có khái niệm collation.

Đã sửa: migration `000003_convert_tables_to_utf8mb4.php` dùng `ALTER TABLE ... CONVERT TO CHARACTER SET utf8mb4`. Sau khi chạy: 7/7 bảng utf8mb4, `password` giữ nguyên 255, dữ liệu nguyên vẹn.

> **Bài học chung**: `CREATE TABLE IF NOT EXISTS` chỉ đúng cho DB trắng. Mọi thay đổi lên DB đang chạy **phải** dùng `ALTER`. Một migration "baseline" không bao giờ nâng cấp được DB đã tồn tại.

### 5.11 Ghi chú thêm

- Thêm `DB_PORT` vào `.env` (bản cũ ép cứng 3306). Cần khi máy chạy nhiều instance MySQL — đúng trường hợp máy này: MySQL 8 giữ 3306 nên MariaDB của XAMPP không khởi động được.
- Client `mysql.exe` của XAMPP là MariaDB nên **không** nói chuyện được với `caching_sha2_password` của MySQL 8 (`caching_sha2_password.dll not found`). Dùng PDO hoặc client MySQL 8 thay thế. Đây là lý do lệnh `mysql.exe ... < dump.sql` thất bại.

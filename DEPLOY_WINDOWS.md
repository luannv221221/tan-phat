# TRIỂN KHAI TÂN PHÁT ERP LÊN WINDOWS SERVER (XAMPP / Apache)

> Cấu hình đã chốt: **Apache (XAMPP)** · cài **XAMPP trọn gói** · **bê nguyên DB hiện tại** (import dump).
> Ứng dụng PHP thuần, **không có thư viện Composer ngoài** (autoloader tự viết đã đi kèm mã nguồn) → không cần `composer install`.

---

## 0. Chuẩn bị / yêu cầu

| Thành phần | Yêu cầu |
|---|---|
| Windows Server | 2016 / 2019 / 2022 đều được |
| XAMPP | Bản có **PHP 8.0+** (khớp dev: PHP 8.0.30). Nên PHP 8.0 hoặc 8.1 |
| PHP extensions | `pdo_mysql`, `mbstring`, `fileinfo`, `openssl` (mặc định XAMPP đã bật). `gd` không bắt buộc |
| Cổng | Mở **80** (HTTP) và **443** (HTTPS) trong Windows Firewall |
| Quyền | Tài khoản Administrator để cài dịch vụ |

**Cần copy từ máy dev sang server 3 thứ KHÔNG nằm trong git:**
1. File **`deploy/tanphat_php.sql`** — bản dump DB (đã tạo sẵn, ~153KB).
2. Thư mục **`public/assets/uploads/`** — ảnh đã tải (ảnh phụ tùng demo, logo…). DB tham chiếu tới các file này.
3. File **`.env`** — bạn tự tạo trên server từ `.env.production.example` (KHÔNG copy .env của dev).

---

## 1. Cài XAMPP

1. Tải XAMPP (PHP 8.0/8.1) từ apachefriends.org, cài vào `C:\xampp`.
2. Mở **XAMPP Control Panel** → Start **Apache** và **MySQL**.
3. (Khuyến nghị) Bấm nút **Config → Service** để đăng ký Apache và MySQL chạy như **Windows Service** (tự khởi động cùng máy).
4. Kiểm tra: mở `http://localhost` thấy trang XAMPP là OK.

> **Bảo mật MySQL:** vào `http://localhost/phpmyadmin` → User accounts → đặt **mật khẩu cho `root`** (mặc định XAMPP để trống).

---

## 2. Đưa mã nguồn lên server

Chọn thư mục **ngoài** `htdocs` cho gọn, ví dụ `C:\tanphat\app-erp`.

**Cách A — Git (khuyến nghị):**
```
cd C:\tanphat
git clone <URL_REPO> app-erp
cd app-erp
git checkout feature/admin-crud        (hoặc main sau khi đã merge)
```

**Cách B — Copy tay:** nén thư mục dự án ở máy dev (trừ `.git`, `node_modules` nếu có) rồi giải nén vào `C:\tanphat\app-erp`.

Sau đó **copy 2 thứ gitignore** vào đúng vị trí:
- Thư mục `public/assets/uploads/`  → `C:\tanphat\app-erp\public\assets\uploads\`
- File `deploy/tanphat_php.sql`      → để tạm ở `C:\tanphat\app-erp\deploy\` (dùng ở bước 3)

> `vendor/autoload.php` đã được commit kèm mã nguồn nên **không cần** `composer install`.

---

## 3. Tạo & import Database

**Tạo database + user riêng** (đừng để app dùng `root`). Mở `http://localhost/phpmyadmin` → tab **SQL**, chạy:
```sql
CREATE DATABASE IF NOT EXISTS tanphat_php
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'tanphat_app'@'127.0.0.1' IDENTIFIED BY 'DAT_MAT_KHAU_MANH';
GRANT ALL PRIVILEGES ON tanphat_php.* TO 'tanphat_app'@'127.0.0.1';
FLUSH PRIVILEGES;
```

**Import dump** (bằng dòng lệnh cho chắc UTF-8):
```
C:\xampp\mysql\bin\mysql.exe -u root -p --default-character-set=utf8mb4 tanphat_php < C:\tanphat\app-erp\deploy\tanphat_php.sql
```
> Hoặc qua phpMyAdmin → chọn DB `tanphat_php` → tab **Import** → chọn file `tanphat_php.sql`. (Dump đã đặt sẵn charset utf8mb4, tiếng Việt hiển thị đúng.)

Kiểm tra nhanh (phpMyAdmin → SQL):
```sql
SELECT svalue FROM site_settings WHERE skey='site_slogan';   -- phải ra "Phụ tùng & thiết bị gara ô tô chính hãng"
SELECT COUNT(*) FROM parts;                                  -- ~16
```

> Vì "bê nguyên DB", **các migration đã nằm trong dump** — KHÔNG cần chạy `php migrate.php` nữa. Nếu muốn chắc, chạy `C:\xampp\php\php.exe migrate.php status` (phải báo tất cả đã chạy).

---

## 4. Cấu hình `.env` trên server

Trong `C:\tanphat\app-erp`, copy `.env.production.example` thành `.env` rồi sửa:
```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=tanphat_php
DB_USER=tanphat_app
DB_PASS=DAT_MAT_KHAU_MANH        # đúng mật khẩu vừa tạo ở bước 3

APP_DEBUG=false                  # BẮT BUỘC false ở production
APP_BASE_PATH=/                  # vì dùng VirtualHost (bước 5)
APP_URL=https://erp.tanphat.vn   # domain thật; chưa có SSL thì ghi http://

SESSION_COOKIE=tanphat_session
SESSION_SECURE=true              # true nếu có HTTPS, false nếu tạm chạy HTTP
```
> `APP_URL` phải đúng vì nó là gốc mọi link + đường dẫn ảnh (`_WEB_URL`). Sai domain là ảnh/CSS/redirect hỏng.

---

## 5. Cấu hình Apache VirtualHost

1. Mở `C:\xampp\apache\conf\extra\httpd-vhosts.conf`, dán nội dung mẫu trong **`deploy/httpd-vhosts.conf.example`** (sửa `DocumentRoot` = `C:/tanphat/app-erp` và `ServerName` = domain của bạn).
   - Quan trọng: khối `<Directory>` phải có **`AllowOverride All`** (để `.htaccess` của dự án hoạt động: rewrite về `index.php` + chặn `.env`).
2. Bật **mod_rewrite** (XAMPP bật sẵn). Kiểm tra trong `C:\xampp\apache\conf\httpd.conf` có dòng không bị `#`:
   ```
   LoadModule rewrite_module modules/mod_rewrite.so
   ```
3. Trỏ domain về server:
   - Có DNS thật: tạo bản ghi A `erp.tanphat.vn` → IP server.
   - Chạy nội bộ/test: sửa `C:\Windows\System32\drivers\etc\hosts` thêm dòng `127.0.0.1  erp.tanphat.vn`.
4. **Restart Apache** (XAMPP Control Panel → Stop/Start Apache).

> **Phương án thay thế (đơn giản hơn, không cần vhost):** đặt dự án tại `C:\xampp\htdocs\tanphat` và truy cập `http://localhost/tanphat`. Khi đó đặt `APP_BASE_PATH=/tanphat/` và `APP_URL=http://<ip-hoac-domain>/tanphat`.

---

## 6. Quyền ghi thư mục

Apache (chạy dưới tài khoản dịch vụ) cần **ghi** được vào:
- `public/assets/uploads/**` — lưu ảnh tải lên.
- `storage/sessions/` — file session.
- `public/logs/errors/`, `public/logs/access/` — log.

Trên Windows thường Apache ghi được sẵn. Nếu bị lỗi ghi: chuột phải các thư mục trên → **Properties → Security → Edit** → cấp **Modify** cho `Users` (hoặc tài khoản chạy dịch vụ Apache).

---

## 7. Kiểm tra sau khi deploy

- `https://erp.tanphat.vn/` → trang chủ storefront hiện đúng, **tiếng Việt không lỗi font**, có ảnh sản phẩm.
- `https://erp.tanphat.vn/san-pham` → danh sách phụ tùng + ảnh.
- `https://erp.tanphat.vn/admin` → đăng nhập admin:
  - Mặc định: **hoangan.web@gmail.com / 123456** → **ĐỔI MẬT KHẨU NGAY** (mục Người dùng).
- Vào **Kho → Tồn kho**, **Bán hàng → Hoá đơn**, **CSKH → Nhắc bảo trì** xem dữ liệu demo lên đủ.
- Nếu ảnh sản phẩm không hiện: kiểm tra đã copy thư mục `public/assets/uploads/` chưa (bước 2).

---

## 8. HTTPS (khuyến nghị cho production)

- **Có domain public:** dùng **win-acme** (Let's Encrypt cho Windows) để xin chứng chỉ miễn phí, trỏ vào Apache; hoặc mua SSL rồi khai trong khối `<VirtualHost *:443>` (đã có mẫu trong file vhost).
- **Nội bộ / demo:** tạo chứng chỉ self-signed bằng XAMPP (`C:\xampp\apache\makecert.bat`).
- Sau khi bật HTTPS: đặt `SESSION_SECURE=true` và `APP_URL=https://...` trong `.env`.

---

## 9. Bảo mật & vận hành

- [ ] `APP_DEBUG=false` trong `.env` (không lộ SQL/lỗi ra ngoài).
- [ ] Đổi mật khẩu tài khoản admin mặc định.
- [ ] MySQL `root` có mật khẩu; app dùng user riêng `tanphat_app` (không phải root).
- [ ] `.htaccess` đang chặn `.env`, `.sql`, `database/` (đã có sẵn — cần `AllowOverride All`).
- [ ] **Xoá khỏi webroot** các file chỉ dùng khi seed/dev nếu không cần: `database/seed_demo.php`, `database/seed_part_images.php`, `database/fix_settings.php`, `deploy/tanphat_php.sql`. (Các script này đã tự chặn chạy qua web — chỉ chạy được bằng CLI — nhưng bỏ đi cho sạch.)
- [ ] **Sao lưu định kỳ**: tạo Task Scheduler chạy hằng ngày:
  ```
  C:\xampp\mysql\bin\mysqldump.exe -u root -pMATKHAU --default-character-set=utf8mb4 --single-transaction tanphat_php > C:\backup\tanphat_%date%.sql
  ```
  và sao lưu kèm thư mục `public/assets/uploads/`.
- [ ] Apache + MySQL đăng ký **Windows Service** (tự chạy lại sau reboot).

---

## 10. Xử lý lỗi thường gặp

| Triệu chứng | Nguyên nhân / cách sửa |
|---|---|
| Lỗi 500, hoặc URL `/san-pham` ra 404 | `.htaccess` chưa chạy → thiếu **`AllowOverride All`** hoặc chưa bật `mod_rewrite`. |
| CSS/ảnh/redirect sai đường dẫn | `APP_URL` / `APP_BASE_PATH` trong `.env` chưa khớp cách truy cập (vhost `/` vs subfolder `/tanphat/`). |
| Ảnh sản phẩm không hiện | Chưa copy `public/assets/uploads/`; hoặc quyền đọc thư mục. |
| Tiếng Việt lỗi font | Import không đúng utf8mb4 → import lại bằng lệnh CLI có `--default-character-set=utf8mb4`. |
| Trang trắng, không log | Tạm đặt `APP_DEBUG=true`, xem lỗi, sửa xong đặt lại `false`. Log ở `public/logs/errors/`. |
| Không kết nối được DB | Sai `DB_USER/DB_PASS/DB_HOST` trong `.env`, hoặc user chưa được `GRANT` trên `tanphat_php`. |
| `#1273 Unknown collation: 'utf8mb4_0900_ai_ci'` khi import + thiếu bảng | Dump tạo từ **MySQL 8** nhưng server dùng **MariaDB** (XAMPP mặc định). Dùng bản dump đã đổi collation về `utf8mb4_unicode_ci` (đã xử lý trong `deploy/tanphat_php.sql`). Nếu tự dump lại, thêm: `... \| sed "s/utf8mb4_0900_ai_ci/utf8mb4_unicode_ci/g" > file.sql`. |

---

*Ứng dụng chạy tốt trên cùng stack với môi trường phát triển (XAMPP/Apache/PHP 8/MySQL). Sau khi nghiệm thu, nên gộp nhánh `feature/admin-crud` vào `main` rồi deploy từ `main`.*

# Chuyển sang tên miền `eteksofts.vn` — làm trên máy chủ

Khách yêu cầu (13/11/2026). Anh đã trỏ DNS xong. File này là các việc còn lại
trên máy chủ (Windows + XAMPP), **làm đúng thứ tự** — bước sau dựa vào bước trước.

## Tình trạng đo được ngày 13/11/2026

| Bản ghi DNS anh đã tạo | Kết quả đo |
|---|---|
| `@` A `103.74.101.148` | `eteksofts.vn` → đúng IP máy chủ |
| `*.etek` A `103.74.101.148` | `tp01.etek.eteksofts.vn` → đúng IP máy chủ |
| `www` CNAME `eteksofts.vn.` | `www.eteksofts.vn` → đúng IP máy chủ |
| `crm` A `27.72.102.65` | Máy khác, **không phải** máy này — không liên quan |

Máy chủ **đã nhận** request cho các tên đó (Apache đã đưa vào ứng dụng), nhưng ứng
dụng trả **"Không tìm thấy gara"** vì bảng `garage_domains` chưa khai chúng.
`https://eteksofts.vn` không kết nối được — chưa có SSL.

**Đo lại chiều 13/11/2026:** `http://eteksofts.vn/` đã ra trang Tân Phát (migration
`000112` đã chạy, code trên máy chủ đã có bản mới). `https://` đã kết nối được
nhưng ra trang XAMPP với chứng chỉ `localhost` hết hạn — còn thiếu Bước 5.

`*.etek` chỉ phủ tên **có thêm một nhãn phía trước** (`tp01.etek...`). Tên trần
`etek.eteksofts.vn` không có bản ghi và cũng **không cần**.

---

## Bước 1 — Sao lưu CSDL máy chủ

phpMyAdmin máy chủ → chọn CSDL → tab **Export** → Go. Cất file. Đường lùi duy nhất.

## Bước 2 — Chạy migration TRƯỚC, đẩy code SAU

**Không nhập đè CSDL lần này.** Khách đang nhập dữ liệu thật trên máy chủ (đặt hàng
web, báo giá…); nhập đè là xoá sạch chỗ đó. Chạy migration thì chỉ thêm, không mất gì.

Mở **cmd** trên máy chủ:

```bat
cd /d C:\xampp\htdocs\tan-phat
git fetch origin
git checkout origin/ban-giao-khach -- database/migrations
C:\xampp\php\php.exe migrate.php status
C:\xampp\php\php.exe migrate.php
git pull --ff-only origin ban-giao-khach
```

- Dòng `git checkout ... -- database/migrations` lấy **riêng** các file migration mới
  về trước, để chạy migration khi code cũ vẫn đang chạy. Sau đó `git pull` vẫn chạy
  bình thường (đã thử trên một bản sao: pull qua, cây sạch).
- `migrate.php status` để xem máy chủ đang thiếu những migration nào. Đợt này có
  thể gồm `000109` → `000112` (tuỳ lần cuối anh đẩy lên là lúc nào).
- Cả bốn migration đợt này đều **chỉ thêm** (thêm cột có giá trị mặc định, thêm
  module, thêm dòng tên miền) — code cũ chạy trên CSDL mới không sao.
- `000112` khai `eteksofts.vn` cho gara tổng (`www.` tự khớp) và
  `<mã gara>.etek.eteksofts.vn` cho mỗi gara. **Không** đổi host chính, **không**
  gỡ tên miền cũ.

## Bước 3 — `.env` trên máy chủ

```
APP_URL=
APP_DEBUG=false
```

- `APP_URL` **trống**: địa chỉ tự suy từ tên miền đang mở. Nếu đang ghim
  `http://etek.rikkeiedu.org` thì trang mở bằng `eteksofts.vn` vẫn nạp ảnh, CSS, font
  từ tên cũ — trình duyệt chặn font khác tên miền, và liên kết dẫn về tên cũ.
- `APP_DEBUG=false`: đang `true` thì trang lỗi in câu SQL ra cho khách xem.

## Bước 4 — Mở thử bằng `http://` (chưa cần SSL)

| Địa chỉ | Phải thấy |
|---|---|
| `http://eteksofts.vn/` | Trang Tân Phát |
| `http://www.eteksofts.vn/` | Trang Tân Phát |
| `http://tp01.etek.eteksofts.vn/` | Trang Tân Phát |
| `http://dmsg.etek.eteksofts.vn/` | Trang gara Sài Gòn *(nếu còn gara demo)* |
| `http://khong-co.etek.eteksofts.vn/` | "Không tìm thấy gara" (bị chặn đúng) |
| `http://etek.rikkeiedu.org/` | Vẫn chạy như cũ |

Còn "Không tìm thấy gara" ở 4 dòng đầu: migration `000112` chưa chạy.

## Bước 5 — SSL (Let's Encrypt, miễn phí, bằng win-acme)

**Cách gọn nhất: MỘT chứng chỉ kê đủ các tên đang dùng**, xác minh qua HTTP (tự
động, tự gia hạn), không cần chứng chỉ wildcard. Wildcard `*.etek.eteksofts.vn` bắt
buộc xác minh qua DNS (thêm bản ghi TXT ở Mắt Bão) và **phải làm tay lại mỗi 60
ngày** — sớm muộn cũng quên.

**5.1. Bật SSL cho Apache** (nếu chưa): trong `C:\xampp\apache\conf\httpd.conf` bỏ
dấu `#` ở `LoadModule ssl_module modules/mod_ssl.so` và
`Include conf/extra/httpd-ssl.conf`. Mở cổng **443** trên tường lửa Windows và
tường lửa nhà cung cấp máy chủ. **Giữ cổng 80** — Let's Encrypt cần nó.

> **Máy chủ hiện tại ĐÃ CÓ SẴN bước này** (anh gửi `httpd.conf` ngày 13/11, em đọc
> và đo từ ngoài): `mod_ssl` đã bật, `httpd-ssl.conf` đã được nạp, cổng 443 đã mở.
> **Không sửa `httpd.conf`.** Có điều 443 đang trả trang mặc định của XAMPP
> (`/dashboard/`) với chứng chỉ tự ký `localhost` hết hạn từ 2019 — vì vậy mở
> `https://...` thấy cảnh báo đỏ rồi ra trang XAMPP. Bước 5.2 + 5.3 sửa đúng chỗ đó.

**5.2. Cấp chứng chỉ** — tải win-acme (bản x64) rồi giải nén vào **`C:\win-acme`**,
đừng để chạy trong `Downloads`: win-acme tạo tác vụ gia hạn trỏ vào đúng chỗ đặt
`wacs.exe`, ai dọn `Downloads` là gia hạn chết. Dễ nhất: chuột phải file zip →
WinRAR *Extract files...* → ô đích gõ `C:\win-acme` → OK. Hoặc trong PowerShell:

```powershell
Add-Type -AssemblyName System.IO.Compression.FileSystem; [System.IO.Compression.ZipFile]::ExtractToDirectory("$env:USERPROFILE\Downloads\win-acme.v2.2.9.1701.x64.trimmed.zip", "C:\win-acme")
```

*(Không dùng `Expand-Archive`: PowerShell trên máy chủ là bản cũ (dưới 5.0), báo
"Expand-Archive is not recognized" — đã gặp 13/11. Lệnh trên dùng thẳng .NET, chạy
được cả bản cũ.)*

Tạo sẵn thư mục chứa chứng chỉ — win-acme **không tự tạo**, thiếu là báo
`Directory C:\ssl does not exist ... invalid --pemfilespath` (đã gặp 13/11):

```powershell
mkdir C:\ssl
```

Rồi chạy **một dòng** (dán được cả vào cmd lẫn PowerShell):

```bat
C:\win-acme\wacs.exe --target manual --host "eteksofts.vn,www.eteksofts.vn,tp01.etek.eteksofts.vn,dmsg.etek.eteksofts.vn,dmdn.etek.eteksofts.vn" --validation filesystem --webroot "C:\xampp\htdocs\tan-phat" --store pemfiles --pemfilespath "C:\ssl" --installation none --accepttos
```

`--accepttos` = **đồng ý Điều khoản sử dụng của Let's Encrypt**. Cần cờ này vì có
tham số dòng lệnh là win-acme chạy chế độ *Unattended*, không có chỗ bấm đồng ý.
Dù vậy nó **vẫn dừng lại hỏi email** (`Enter email(s) for notifications about
problems and abuse`, đã gặp 13/11) — gõ email nhận thư báo rồi Enter. Muốn khỏi bị
hỏi thì thêm `--emailaddress <email của anh>` vào cuối lệnh.

> **Kết quả 13/11/2026:** đã cấp xong. Chứng chỉ Let's Encrypt `CN=eteksofts.vn`,
> hạn tới **11/02/2027** (tự gia hạn, xem 5.4), phủ đúng 5 tên: `eteksofts.vn`,
> `www.eteksofts.vn`, `tp01` / `dmsg` / `dmdn.etek.eteksofts.vn`. **Không** phủ các tên
> `*.etek.rikkeiedu.org` — các tên đó chỉ dùng được bằng `http://`.

*(13/11: bản trước viết nhiều dòng nối bằng `^` — dấu nối của cmd. Dán vào
PowerShell thì mỗi dòng thành một lệnh riêng và báo `Missing expression after unary
operator '--'`. Danh sách `--host` phải để trong nháy kép: PowerShell coi dấu phẩy
trần là mảng và tách thành nhiều tham số.)*

- `--host`: kê **đúng các gara đang có** (xem màn Quản lý gara). Gara mở sau thì chạy
  lại lệnh này với danh sách mới.
- `--webroot`: đúng `DocumentRoot` của vhost. `.htaccess` của dự án phục vụ thẳng
  file có thật, nên file xác minh `/.well-known/acme-challenge/...` đi qua được.
- `--store pemfiles`: Apache cần file `.pem`. Thiếu cờ này win-acme cất chứng chỉ
  vào kho chứng chỉ của Windows, Apache không đọc được.

**5.3. VirtualHost 443** — **thêm** vào `C:\xampp\apache\conf\extra\httpd-vhosts.conf`
(không phải `httpd.conf`), đường dẫn file lấy đúng như win-acme in ra trong `C:\ssl`.

> **Chỉ thêm khối này SAU KHI `C:\ssl` đã có file `.pem`.** Thêm trước thì Apache
> không khởi động được — sập cả trang `http://` đang chạy, không riêng `https://`.

> **THÊM, đừng dán đè cả file.** File này đang chứa khối `<VirtualHost *:80>` — chính
> khối đưa `http://` vào ứng dụng. 13/11 file bị dán đè chỉ còn khối 443: restart
> xong `https://` chạy nhưng **mọi** `http://` rơi về trang XAMPP (`/dashboard/`), vì
> cổng 80 không còn vhost nào, Apache trả thư mục gốc `C:/xampp/htdocs`.
>
> Nội dung đầy đủ của file sau bước này — **khối 80 đứng đầu** (thành mặc định cho
> mọi tên và cả địa chỉ IP), khối 443 đứng sau:
>
> ```apache
> <VirtualHost *:80>
>     ServerName   eteksofts.vn
>     ServerAlias  www.eteksofts.vn *.etek.eteksofts.vn etek.rikkeiedu.org *.etek.rikkeiedu.org
>     DocumentRoot "C:/xampp/htdocs/tan-phat"
>
>     <Directory "C:/xampp/htdocs/tan-phat">
>         AllowOverride All
>         Require all granted
>         Options -Indexes +FollowSymLinks
>     </Directory>
> </VirtualHost>
>
> # ... rồi khối <VirtualHost *:443> ngay dưới đây
> ```

```apache
<VirtualHost *:443>
    ServerName   eteksofts.vn
    ServerAlias  www.eteksofts.vn *.etek.eteksofts.vn
    DocumentRoot "C:/xampp/htdocs/tan-phat"

    SSLEngine on
    SSLCertificateFile    "C:/ssl/eteksofts.vn-chain.pem"
    SSLCertificateKeyFile "C:/ssl/eteksofts.vn-key.pem"

    <Directory "C:/xampp/htdocs/tan-phat">
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>
</VirtualHost>
```

Trước khi restart, kiểm cấu hình:

```bat
C:\xampp\apache\bin\httpd.exe -t
```

Phải ra `Syntax OK`. Ra lỗi thì **đừng restart** — gỡ khối vừa thêm, Apache vẫn
chạy như cũ. Ra `Syntax OK` thì restart Apache (XAMPP Control Panel → Stop → Start).
Mở `https://eteksofts.vn/` — phải có ổ khoá, không cảnh báo, ra trang Tân Phát
(không còn `/dashboard/` của XAMPP).

Khối này đứng trong `httpd-vhosts.conf`, được nạp **trước** `httpd-ssl.conf`, nên
nó thành khối 443 mặc định: tên chưa có trong chứng chỉ (vd `https://etek.rikkeiedu.org`)
cũng vào ứng dụng, chỉ còn cảnh báo sai tên — đỡ hơn trang XAMPP như hiện nay.

**5.4. Gia hạn:** win-acme tự tạo tác vụ theo lịch, tự lấy chứng chỉ mới trước khi
hết hạn (60 ngày). Apache chỉ đọc file mới **sau khi restart** — nhớ restart Apache
sau mỗi lần gia hạn, hoặc gắn lệnh restart vào win-acme (`--installation script`).

## Bước 6 — Đặt tên miền mới làm **chính** (làm SAU bước 4 và 5)

**Quản lý gara** → biểu tượng quả địa cầu (Tên miền) của gara Tân Phát → nút
**ngôi sao** (*Đặt làm tên miền chính*) trên dòng `tp01.etek.eteksofts.vn`.

Hệ thống suy "tên miền gốc" cho gara mở sau từ host chính của gara tổng. Đổi chính
xong thì gara mới mở nhận `<mã>.etek.eteksofts.vn`. Làm sớm (chưa SSL, chưa ai mở
thử) là phát ra địa chỉ chưa kiểm tra — vì vậy migration cố ý không tự đổi.

## Bước 7 — Chuyển hẳn khách sang tên mới (tuỳ anh, làm sau cùng)

Tên miền cũ vẫn chạy song song: link đã gửi khách không chết. Muốn chuyển hẳn thì
thêm vào khối `*:80` một chuyển hướng 301 — **sau khi** SSL đã chạy, và **không**
chuyển hướng đường xác minh của Let's Encrypt:

```apache
RewriteEngine On
RewriteCond %{REQUEST_URI} !^/\.well-known/acme-challenge/
RewriteRule ^(.*)$ https://%{HTTP_HOST}$1 [R=301,L]
```

Người đang đăng nhập ở tên cũ sẽ phải đăng nhập lại một lần ở tên mới (phiên đăng
nhập gắn theo từng tên miền).

---

## Hoàn tác

- Code: `git reset --hard <commit cũ>` trên máy chủ.
- CSDL: `C:\xampp\php\php.exe migrate.php rollback` gỡ batch cuối — với `000112` là
  gỡ **đúng** các dòng tên miền nó đã thêm (theo tên), tên miền anh tự khai thêm sau
  không bị đụng. Nặng hơn thì nhập lại file sao lưu ở Bước 1.

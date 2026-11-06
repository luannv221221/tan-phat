# SSL/TLS cho nền tảng nhiều gara

Viết 06/11/2026, sau khi khách hỏi: *"anh chỉ lo cái ssl/tls — lúc test có vấn
đề gì hay bị hỏi không giải thích được"*.

Tài liệu này trả lời đúng hai câu đó: **làm thế nào** và **nói gì khi bị hỏi**.

---

## 0. Hiện trạng — đọc trước

Máy chủ `etek.rikkeiedu.org` (IP `103.74.101.148`) **chưa có SSL**. Cổng 443
đóng hẳn, không phải chứng chỉ hết hạn:

```bash
curl -s -o /dev/null -m 10 -w "https: %{http_code}\n" https://etek.rikkeiedu.org/
```

Trả `000` = không kết nối được.

Nghĩa là **mật khẩu đăng nhập trang quản trị đang đi qua mạng dạng chữ
thường**. Ai ngồi chung wifi với nhân viên gara đều đọc được. Trình duyệt cũng
dán nhãn **"Không bảo mật"** ngay cạnh thanh địa chỉ — và đó chính là câu hỏi
khó nhất sẽ gặp lúc demo.

**Việc này phải xong trước khi bàn giao cho gara**, không phải sau.

---

## 1. Hai trường hợp, khác nhau về công sức

### A. Gara dùng subdomain của mình — `<mã>.etek.rikkeiedu.org`

**Gara không phải làm gì. Mình cũng gần như không phải làm gì.**

Tên miền `etek.rikkeiedu.org` đã có bản ghi **wildcard**: mọi subdomain, kể cả
tên bịa ra, đều phân giải về `103.74.101.148`. Mở gara mới là địa chỉ chạy
ngay, không phải thêm bản ghi DNS nào.

Về chứng chỉ, có hai cách — **nên chọn cách 2**:

| | Cách 1: một chứng chỉ wildcard | Cách 2: từng subdomain một |
|---|---|---|
| Phủ | `*.etek.rikkeiedu.org`, mọi gara | Mỗi gara một chứng chỉ |
| Xác minh | **DNS-01** — phải thêm bản ghi TXT | **HTTP-01** — tự động hoàn toàn |
| Cần gì | API của nhà cung cấp DNS, hoặc làm tay mỗi 60 ngày | Không cần gì, chỉ cần cổng 80 mở |
| Gara mới | Không phải làm gì | Chạy thêm một lệnh (hoặc để tự quét) |

Cách 1 nghe gọn hơn nhưng **làm tay mỗi 60 ngày là chuyện sớm muộn cũng quên**,
và quên thì cả nền tảng mất SSL cùng lúc. Cách 2 tự chạy, hỏng một tên miền
thì chỉ tên miền đó hỏng.

### B. Gara dùng tên miền riêng — `abc.com`

Mỗi tên miền một chứng chỉ riêng, nhưng **vẫn tự động**:

1. Gara trỏ `A record` → `103.74.101.148` (việc của gara, một lần)
2. Mình khai tên miền trong *Hệ thống › Quản lý gara › Tên miền*
3. Mình chạy `win-acme` cho tên miền đó — **khoảng 1 phút**
4. Xong. Gia hạn tự động, không ai phải nhớ

---

## 2. Vì sao thứ tự không đảo được

Đây là chỗ hay bị hỏi nhất, và là chỗ dễ nói sai nhất.

Let's Encrypt cấp chứng chỉ cho `abc.com` chỉ khi chứng minh được **mình thật
sự đang vận hành `abc.com`**. Nó chứng minh bằng cách tự gọi:

```
http://abc.com/.well-known/acme-challenge/<chuỗi ngẫu nhiên>
```

Request đó đi theo DNS. **DNS chưa trỏ về máy mình thì nó rơi vào máy khác**,
không thấy chuỗi cần tìm, và từ chối cấp.

Nên thứ tự bắt buộc là:

```
Gara trỏ DNS  →  (chờ DNS lan, 5 phút – vài giờ)  →  mình cấp SSL
```

Không có cách nào xin chứng chỉ trước rồi trỏ sau — trừ khi dùng DNS-01, mà
cách đó lại đòi quyền sửa DNS của gara.

> **Trong lúc chờ DNS lan, trang vẫn chạy bình thường** ở subdomain cũ. Không
> có khoảng thời gian nào gara bị mất trang.

---

## 3. Một IP phục vụ được bao nhiêu tên miền?

**Không giới hạn.** Đây cũng là câu hay bị hỏi.

Trình duyệt ngày nay gửi kèm tên miền ngay trong lúc bắt tay mã hoá (gọi là
**SNI**), nên máy chủ biết phải đưa chứng chỉ nào trước khi trang bắt đầu tải.
Một IP, một máy chủ Apache, trăm tên miền — mỗi tên miền một chứng chỉ riêng.

Không cần thuê thêm IP cho mỗi gara.

---

## 4. Làm thật — Windows Server + XAMPP/Apache

### 4.1. Bật mod_ssl

Trong `C:\xampp\apache\conf\httpd.conf`, bỏ dấu `#` ở hai dòng:

```apache
LoadModule ssl_module modules/mod_ssl.so
Include conf/extra/httpd-ssl.conf
```

Mở cổng **443** trên tường lửa Windows và trên tường lửa của nhà cung cấp máy
chủ. Giữ nguyên cổng **80** — Let's Encrypt cần nó để xác minh và gia hạn.

### 4.2. Cài win-acme

Tải bản `win-acme` (pluggable, x64) từ trang chính thức, giải nén vào
`C:\win-acme`. Không cần cài đặt gì thêm.

### 4.3. Cấp chứng chỉ cho một tên miền

```
C:\win-acme\wacs.exe --target manual --host abc.com,www.abc.com --installation script
```

Lần đầu nó hỏi email (để Let's Encrypt báo khi sắp hết hạn) và yêu cầu đồng ý
điều khoản. Những lần sau không hỏi lại.

Công cụ tự tạo một **tác vụ theo lịch của Windows** để gia hạn — chạy mỗi
ngày, và chỉ thật sự gia hạn khi chứng chỉ còn dưới 30 ngày.

### 4.4. Thêm VirtualHost 443 cho tên miền đó

Mỗi tên miền một khối, trỏ vào file chứng chỉ win-acme vừa sinh ra:

```apache
<VirtualHost *:443>
    ServerName abc.com
    ServerAlias www.abc.com
    DocumentRoot "C:/xampp/htdocs/tan-phat"

    SSLEngine on
    SSLCertificateFile    "C:/ProgramData/win-acme/.../abc.com-crt.pem"
    SSLCertificateKeyFile "C:/ProgramData/win-acme/.../abc.com-key.pem"

    <Directory "C:/xampp/htdocs/tan-phat">
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>
</VirtualHost>
```

Đường dẫn thật của hai file `.pem` do win-acme in ra sau khi cấp xong — chép
đúng đường dẫn đó, đừng đoán.

### 4.5. Chuyển http sang https

Sau khi 443 đã chạy, thêm vào khối `*:80` của tên miền đó:

```apache
<VirtualHost *:80>
    ServerName abc.com
    ServerAlias www.abc.com

    # KHÔNG chuyển hướng đường xác minh của Let's Encrypt, nếu không thì
    # lần gia hạn sau thất bại mà không ai biết cho tới khi chứng chỉ hết hạn.
    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/\.well-known/acme-challenge/
    RewriteRule ^(.*)$ https://%{HTTP_HOST}$1 [R=301,L]
</VirtualHost>
```

Dòng `RewriteCond` là chỗ hay bị bỏ sót nhất. Thiếu nó thì mọi thứ chạy ngon
suốt 60 ngày rồi đột nhiên chứng chỉ hết hạn, mà không có lỗi nào báo trước.

### 4.6. Phần mềm không phải sửa gì

Code đã suy giao thức từ chính request (sửa 05/11/2026, `config.php`). Bật SSL
xong là mọi đường dẫn sinh ra tự thành `https://`, không phải đụng vào cấu
hình nào.

Nếu sau này đặt Cloudflare hoặc nginx đứng trước, mới cần thêm vào `.env`:

```
TRUST_PROXY=true
```

**Chỉ bật khi thật sự có proxy đứng trước.** Máy chủ đứng thẳng ra Internet mà
bật cái này thì bất kỳ ai cũng gửi được header giả để ép ứng dụng sinh link
https.

---

## 5. Bị hỏi lúc demo thì trả lời thế nào

| Câu hỏi | Trả lời |
|---|---|
| "Có bảo mật không? Sao hiện *Không bảo mật*?" | Bản đang chạy thử chưa bật chứng chỉ. Bản thật có HTTPS đầy đủ, cấp xong trong một phút sau khi tên miền trỏ về. |
| "Chứng chỉ giá bao nhiêu một năm?" | Miễn phí. Let's Encrypt, cùng loại mà phần lớn web hiện nay đang dùng. |
| "Hết hạn thì ai gia hạn?" | Máy tự gia hạn, 2 tháng một lần, không ai phải nhớ. |
| "Mỗi gara một tên miền thì có phải mua thêm IP không?" | Không. Một IP phục vụ được không giới hạn tên miền. |
| "Bao lâu thì xong sau khi tôi trỏ tên miền?" | Trỏ xong chờ DNS lan (5 phút đến vài giờ, tuỳ nhà cung cấp), rồi cấp chứng chỉ mất một phút. |
| "Trong lúc chờ thì trang có chết không?" | Không. Địa chỉ cũ vẫn chạy song song, chuyển xong mới thôi. |
| "Tôi muốn đổi sang tên miền khác sau này?" | Được. Khai thêm tên miền mới, cấp chứng chỉ, đặt làm chính. Tên miền cũ vẫn chạy nên link cũ không chết. |
| "Dữ liệu gara tôi có lẫn với gara khác không?" | Không. Mỗi gara một vùng dữ liệu riêng, và tên miền quyết định vào vùng nào. Tên miền lạ bị chặn thẳng, không rơi về dữ liệu của ai. |

---

## 6. Việc cần làm, theo thứ tự

1. **Bật SSL cho `etek.rikkeiedu.org` và `*.etek...`** — làm trước buổi demo
2. Chạy migration `000097–000104` **trước khi đẩy code** (xem
   [`HUONG-DAN-TRIEN-KHAI-NHIEU-GARA.md`](HUONG-DAN-TRIEN-KHAI-NHIEU-GARA.md))
3. Kiểm `APP_URL` trong `.env` **để trống**
4. Tắt `APP_DEBUG` trên máy chủ
5. Gara nào có tên miền riêng thì làm theo mục 1.B khi tới lượt

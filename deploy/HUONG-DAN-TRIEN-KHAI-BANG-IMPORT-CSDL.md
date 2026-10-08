# Triển khai bằng xuất / nhập CSDL (không chạy migration)

Cách này hợp với nếp vẫn làm: làm xong ở máy local, **xuất nguyên CSDL**, nhập
đè lên máy chủ, rồi đẩy code. Không đụng tới `php migrate.php` trên máy chủ.

> Muốn chạy migration trên máy chủ thì xem
> [`HUONG-DAN-TRIEN-KHAI-NHIEU-GARA.md`](HUONG-DAN-TRIEN-KHAI-NHIEU-GARA.md).
> Chỉ làm **một trong hai**, đừng làm cả hai.

---

## 0. Hai điều phải biết trước, đọc kỹ

### 0.1. Nhập đè là XOÁ SẠCH dữ liệu máy chủ

File xuất ra có `DROP TABLE IF EXISTS` cho cả 68 bảng. Nhập vào là **mọi thứ
đang có trên máy chủ bị thay bằng bản local**. Phát sinh trên máy chủ kể từ lần
xuất gần nhất — báo giá mới, hoá đơn mới, khách mới, đơn web mới — **mất hết**.

Vì vậy bước đầu tiên là **kiểm máy chủ có gì mới không** (mục 1).

### 0.2. Tài khoản mẫu không được lên máy chủ

CSDL local đang có 5 tài khoản `@gara-mau.test`, trong đó **một tài khoản nhóm
Admin thuộc gara tổng**. Mật khẩu của chúng nằm nguyên văn trong
`tools/tao-du-lieu-gara-mau.php` — ai đọc được mã nguồn là đăng nhập được.

Đưa lên máy chủ là mở sẵn cửa quản trị. **Bắt buộc dọn trước khi xuất** (mục 2).

---

## 1. Kiểm máy chủ trước

Vào phpMyAdmin của máy chủ, chạy:

```sql
SELECT 'bao gia'   AS bang, MAX(create_at) AS moi_nhat FROM quotations
UNION ALL SELECT 'hoa don',      MAX(create_at) FROM sales_invoices
UNION ALL SELECT 'khach',        MAX(create_at) FROM partners
UNION ALL SELECT 'phieu nhap',   MAX(create_at) FROM goods_receipts
UNION ALL SELECT 'don web',      MAX(create_at) FROM orders;
```

So với mốc anh xuất CSDL về máy local lần gần nhất.

- **Không có gì mới hơn** → nhập đè an toàn, đi tiếp.
- **Có dòng mới hơn** → ĐỪNG nhập đè. Dữ liệu đó sẽ mất. Lúc này phải xuất
  CSDL máy chủ về, nhập vào local, chạy `php migrate.php` ở local, rồi mới quay
  lại quy trình này.

---

## 2. Dọn CSDL local

Xem trước sẽ xoá gì:

```bash
C:\xampp\php\php.exe deploy\don-truoc-khi-xuat.php
```

Xoá thật:

```bash
C:\xampp\php\php.exe deploy\don-truoc-khi-xuat.php --that
```

Script dọn:

| | |
|---|---|
| Tên miền `.localhost` | Chỉ để chạy thử ở máy local, lên máy chủ vô nghĩa |
| 5 tài khoản `@gara-mau.test` | **Mật khẩu nằm trong mã nguồn** |
| Dữ liệu thử còn sót (`ZZ...`) | Bộ test tự dọn, nhưng lần chạy nào chết giữa chừng thì còn lại |

**Hai gara demo (Sài Gòn, Đà Nẵng) được GIỮ** — có người muốn giữ để trình diễn
tính năng nhiều gara. Muốn bỏ thì thêm `--bo-gara-demo`.

> Dọn xong thì tài khoản mẫu không đăng nhập được ở máy local nữa. Cần chạy thử
> tiếp thì tạo lại: `php tools\tao-du-lieu-gara-mau.php`

---

## 3. Xuất CSDL

```bash
C:\xampp\php\php.exe tools\xuat-csdl.php --ra=deploy\len-may-chu.sql
```

Khoảng **430 KB / 68 bảng**. Tool này thay cho `mysqldump` (trên máy này
`mysqldump.exe` không kết nối được).

File sinh ra **không có `CREATE DATABASE`**, chỉ thao tác ở mức bảng — nhập vào
CSDL nào cũng được, không phụ thuộc tên CSDL trên máy chủ.

---

## 4. Sao lưu máy chủ

**Đừng bỏ qua bước này.** Đây là đường lùi duy nhất.

phpMyAdmin trên máy chủ → chọn CSDL → tab **Export** → Go. Cất file lại.

---

## 5. Nhập và đẩy code — làm liền nhau

Thứ tự **bắt buộc**: nhập CSDL **trước**, đẩy code **ngay sau**.

1. phpMyAdmin máy chủ → chọn CSDL → tab **Import** → chọn `len-may-chu.sql` → Go
2. Ngay sau đó, trên máy chủ:

```bash
git pull origin ban-giao-khach
```

**Vì sao đúng thứ tự này:** code mới cần bảng `garage_domains` và các cột
`garage_id` mới — đẩy code trước khi có chúng là trang đổ ngay.

**Vì sao phải liền nhau:** trong khoảng giữa hai bước, CSDL đã mở các màn Cấu
hình / Tin tức / Đơn hàng cho mọi gara, nhưng code cũ **chưa biết lọc theo
gara**. Một người của gara khác vào lúc đó là sửa trúng dữ liệu của Tân Phát.

Khoảng hở chỉ dài bằng thời gian `git pull`. Cứ làm ngoài giờ làm việc cho chắc.

---

## 6. Phần KHÔNG nằm trong CSDL

Ba việc sau nhập CSDL không mang theo được, phải làm riêng trên máy chủ:

> **Khai tên miền cho một gara thì dùng màn hình**, đừng gõ SQL: Hệ thống ›
> Quản lý gara → nút 🌐. Màn đó hiện tên miền gốc của hệ thống, nhắc hai việc
> dưới đây ngay cạnh, và chặn các thao tác dễ làm hỏng (tắt / xoá tên miền cuối
> cùng của một gara).

### 6.1. DNS — một bản ghi là đủ

```
*.etek.rikkeiedu.org    A    103.74.101.148
```

Đúng IP mà `etek.rikkeiedu.org` đang trỏ tới. Có bản ghi này rồi thì **mở gara
mới không phải đụng DNS nữa**.

### 6.2. Apache — thêm ServerAlias

Trong `httpd-vhosts.conf`, thêm vào **cả khối cổng 80 lẫn 443**:

```apache
ServerName   etek.rikkeiedu.org
ServerAlias  *.etek.rikkeiedu.org
```

Thiếu dòng này thì mọi tên miền phụ về vhost mặc định, không tới được ứng dụng.
Restart Apache sau khi sửa.

### 6.3. HTTPS — hiện đang hỏng, cần làm lại từ đầu

Máy chủ đang dùng **chứng chỉ mẫu của XAMPP**:

```
CN = localhost        hết hạn 08/11/2019
```

Nên `https://etek.rikkeiedu.org/` vào bằng trình duyệt là báo lỗi bảo mật; trang
thật đang chạy qua **HTTP cổng 80**.

Nếu làm HTTPS thì làm luôn **chứng chỉ wildcard**, phủ cả hai:

```
etek.rikkeiedu.org
*.etek.rikkeiedu.org
```

Chứng chỉ cho riêng tên miền gốc **không** phủ được tên miền phụ. Let's Encrypt
cấp wildcard miễn phí nhưng **bắt buộc xác thực qua DNS-01**, không dùng cách
xác thực qua file được.

**Chưa làm HTTPS cũng không sao** — hệ thống nhiều gara chạy bình thường qua
HTTP, chỉ là không mã hoá.

---

## 7. Kiểm sau khi triển khai

| Việc | Mong đợi |
|---|---|
| `http://etek.rikkeiedu.org/` | Trang Tân Phát, vào được |
| `http://tp01.etek.rikkeiedu.org/` | Cũng là trang Tân Phát |
| `http://dmsg.etek.rikkeiedu.org/` | Trang gara Sài Gòn *(nếu giữ gara demo)* |
| Một tên miền phụ chưa khai | **404** "Không tìm thấy gara" |
| Đăng nhập gara A tại địa chỉ gara B | **Bị đá ra** |
| Menu nhóm Kho | Có mục **Đặt hàng kho tổng** |
| Quản lý gara › Thêm gara | Có khối **Tài khoản chủ gara** |
| Đăng nhập bằng `admin@gara-mau.test` | **KHÔNG vào được** — đã dọn |

Việc cuối quan trọng nhất: nếu tài khoản đó vẫn vào được nghĩa là **chưa chạy
bước dọn**, phải xoá ngay trên máy chủ.

### Kiểm riêng cho đợt 08/10/2026 (gara chủ động việc của mình)

Đăng nhập bằng **tài khoản chủ gara** (nhóm Manager, không phải Tân Phát):

| Việc | Mong đợi |
|---|---|
| Hệ thống › Quản lý nhóm | Thấy **đúng hai nhóm của gara mình**, có cột Gara |
| Nhóm của chính mình | **Không có** nút Phân quyền / Sửa / Xoá |
| Lưu phân quyền nhóm Staff | Chỉ gara mình đổi — gara khác **không đổi gì** |
| Hệ thống › Quản lý module | **Không có trên menu** |
| Hàng hoá › Quản lý hàng hoá | Chỉ thấy **hàng của gara mình** |
| Hàng hoá › Thương hiệu | Hàng kho tổng gắn nhãn **"Kho tổng"**, không có nút Sửa |
| Gõ `/admin/products/edit/<id hàng kho tổng>` | **Bị từ chối**, nói rõ là hàng kho tổng |
| Góc trên menu trái | Hiện **tên gara mình** |

Hai việc giữa là quan trọng nhất: nếu lưu phân quyền ở gara này mà gara khác đổi
theo, hoặc gara thêm hàng xong Tân Phát nhìn thấy, thì **CSDL nhập vào chưa có
migration `000093` / `000094`** — xuất lại ở local rồi nhập lại.

---

## 8. Nếu hỏng

Nhập lại file sao lưu ở mục 4, rồi:

```bash
git reset --hard <commit cũ>
```

Vì nhập đè thay toàn bộ CSDL nên lùi lại là nhập bản sao lưu — không cần
`migrate.php rollback`.

---

## 9. Hai chỗ còn thiếu, đã biết

- **Giá trên web chưa theo gara.** Gara đặt giá riêng thì màn báo giá dùng giá
  đó, nhưng website vẫn hiện giá công ty. Chưa gara nào đặt giá riêng nên chưa
  lệch.
- **Lời nhắn khi bị đá khỏi trang quản trị không hiện.** Lỗi có sẵn từ trước.
  Người dùng vào nhầm địa chỉ gara khác bị đá ra mà không biết vì sao.

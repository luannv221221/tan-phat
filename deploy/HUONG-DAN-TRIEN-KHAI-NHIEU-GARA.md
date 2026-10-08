# Triển khai: mỗi gara một website riêng

Hướng dẫn cho đợt thay đổi **nền tảng nhiều gara** (07/10/2026) — migration
`000084` → `000091`, và đợt **gara chủ động việc của mình** (08/10/2026) —
migration `000092` → `000094`.

Sau đợt này, **tên miền quyết định đang phục vụ gara nào**. Mỗi gara có website
riêng (logo, tên, hotline, tin tức, banner, gian hàng), khách web và đơn hàng
riêng, đặt được hàng từ kho tổng, **tự phân quyền cho nhân viên của mình** và
**tự khai hàng hoá của mình**.

---

## 0. Điều quan trọng nhất phải biết trước

**Chưa trỏ DNS cũng KHÔNG sao.** Hệ thống có hai cửa thoát cố ý:

- Bảng `garage_domains` **rỗng** → giữ nguyên cách cũ.
- Host là **máy nội bộ** (`localhost`, `*.test`, tên máy trong mạng LAN) → giữ
  nguyên cách cũ.

Nghĩa là đẩy code lên **trước** khi trỏ DNS xong thì trang vẫn chạy y như trước
qua tên miền gốc. Không phải canh hai việc khít nhau.

**Nhưng** migration `000084` khai sẵn tên miền cho các gara đang có, nên bảng sẽ
**không còn rỗng** ngay sau khi chạy. Vì vậy nó cũng khai luôn **chính tên miền
gốc** `etek.rikkeiedu.org` cho gara tổng — không có dòng đó thì trang đang chạy
thành "host lạ" và bị chặn 404. Đừng xoá dòng đó.

---

## 1. Chạy ở máy local

### 1.1. Lấy code và chạy migration

```bash
git pull origin ban-giao-khach
```

```bash
C:\xampp\php\php.exe migrate.php
```

Kiểm lại không còn migration nào chờ:

```bash
C:\xampp\php\php.exe migrate.php status
```

### 1.2. Cho xem được website của từng gara ở máy local

Máy local không có tên miền thật. Nhưng `*.localhost` trỏ về `127.0.0.1` sẵn
trên Windows, nên chỉ cần khai thêm host `.localhost` cho từng gara:

```sql
INSERT INTO garage_domains (garage_id, host, is_primary, status, create_at)
SELECT g.id, CONCAT(LOWER(g.code), '.localhost'), 0, 1, NOW()
FROM garages g
WHERE g.status = 1
  AND NOT EXISTS (
        SELECT 1 FROM garage_domains d
        WHERE d.host = CONCAT(LOWER(g.code), '.localhost')
      );
```

Rồi mở:

| Địa chỉ | Gara |
|---|---|
| `http://localhost:88/tan-phat/` | Tân Phát (cửa thoát "host nội bộ") |
| `http://tp01.localhost:88/tan-phat/` | Tân Phát |
| `http://dmsg.localhost:88/tan-phat/` | Gara mẫu Sài Gòn |
| `http://dmdn.localhost:88/tan-phat/` | Gara mẫu Đà Nẵng |

**Mấy host `.localhost` này chỉ để chạy thử — đừng đưa lên máy chủ thật.**

### 1.3. Kiểm nhanh là xong

Mở `/san-pham` ở ba địa chỉ trên — số mặt hàng phải **khác nhau**, vì mỗi gara
một gian hàng. Nếu giống hệt nhau thì tên miền chưa ăn.

Chạy bộ test:

```bash
C:\xampp\php\php.exe tests\run.php
```

---

## 2. Triển khai lên máy chủ

### 2.1. Sao lưu CSDL trước

Bắt buộc. Đợt này đụng cấu trúc 12 bảng.

### 2.2. Chạy migration — HAI ĐỢT

Thứ tự của dự án xưa nay là **migration trước, code sau** (đẩy code trước đã
làm sập admin production một lần). Đợt này giữ nguyên nguyên tắc đó, nhưng
**bốn migration phải chờ tới sau khi code lên**.

#### Đợt 1 — chạy TRƯỚC khi đẩy code

| Migration | Làm gì |
|---|---|
| `000084` | Tạo bảng `garage_domains` + khai tên miền |
| `000085` | Đổi tên kho `KHO01` → "Kho Tân Phát" |
| `000087` | Thêm `garage_id` cho 6 bảng nội dung web |
| `000089` | Thêm `garage_id` cho 6 bảng khách web |

Bốn cái này chỉ **thêm cột và dữ liệu**. Code cũ không biết tới chúng nên không
ảnh hưởng gì.

#### Đợt 2 — chạy SAU khi code đã lên

| Migration | Làm gì |
|---|---|
| `000086` | Mở màn Cấu hình cho mọi gara |
| `000088` | Mở 6 màn nội dung web cho mọi gara |
| `000090` | Mở 6 màn khách web cho mọi gara |
| `000091` | Khai màn Đặt hàng kho tổng |
| `000092` | Cấp quyền quản lý website cho nhóm Manager |
| `000093` | Nhóm quyền theo gara + mở màn Quản lý nhóm |
| `000094` | `garage_id` cho 6 bảng danh mục + mở 8 màn nhóm Hàng hoá |

**Vì sao phải chờ:** `000086`, `000088`, `000090` **mở màn hình cho gara**. Chạy
trước khi code lên thì gara mở màn đó ra bằng **code cũ** — code chưa biết lọc
theo gara. Lúc đó một gara vào màn Cấu hình là sửa trúng cấu hình chung của cả
hệ thống, vào màn Tin tức là thấy và sửa được bài của Tân Phát.

`000093` và `000094` cũng vậy, và nặng hơn: `000093` mở màn **Quản lý nhóm**, mà
code cũ chưa biết nhóm thuộc về gara nào — một gara vào đó là sửa bảng phân
quyền của cả hệ thống. `000094` mở màn **Hàng hoá**, code cũ chưa gán gara khi
thêm hàng nên hàng của gara rơi thẳng vào kho tổng.

`000091` thì không nguy hiểm như vậy (code cũ chưa có màn đó nên không ai vào
được), nhưng khai sớm cũng vô ích — để chung đợt 2 cho gọn.

**Ngoại lệ:** nếu máy chủ **chỉ có mỗi gara Tân Phát** (chưa mở gara nào khác)
thì chạy cả 8 một lượt cũng được — không có gara nào để lọt.

#### Cách tách hai đợt

Trình chạy migration không có lệnh "chạy tới số N". Làm thủ công:

```bash
mkdir database\cho-sau
move database\migrations\2026_10_07_000086_*.php database\cho-sau\
move database\migrations\2026_10_07_000088_*.php database\cho-sau\
move database\migrations\2026_10_07_000090_*.php database\cho-sau\
move database\migrations\2026_10_07_000091_*.php database\cho-sau\
move database\migrations\2026_10_08_000092_*.php database\cho-sau\
move database\migrations\2026_10_08_000093_*.php database\cho-sau\
move database\migrations\2026_10_08_000094_*.php database\cho-sau\
```

> `000094` làm **hai việc**: thêm cột (an toàn, chạy sớm được) và mở màn (phải
> chờ). Không tách được bằng cách di chuyển file, nên cả file về đợt 2 — thêm
> cột muộn một chút không sao.

```bash
C:\xampp\php\php.exe migrate.php
```

→ **Đẩy code lên** → rồi trả bốn file về chỗ cũ và chạy lại:

```bash
move database\cho-sau\*.php database\migrations\
C:\xampp\php\php.exe migrate.php
```

### 2.3. DNS — một bản ghi là đủ

Thêm **một** bản ghi wildcard:

```
*.etek.rikkeiedu.org    A    <IP máy chủ>
```

Có bản ghi này rồi thì **mở gara mới không phải đụng vào DNS nữa** — hệ thống
tự cấp tên miền phụ theo mã gara.

Giữ nguyên bản ghi của `etek.rikkeiedu.org` — đó là địa chỉ của gara tổng.

### 2.4. Chứng chỉ HTTPS — phải là wildcard

Chứng chỉ cho riêng `etek.rikkeiedu.org` **không** phủ được `dmsg.etek...`.
Trình duyệt sẽ báo lỗi bảo mật. Cần chứng chỉ phủ cả hai:

```
etek.rikkeiedu.org
*.etek.rikkeiedu.org
```

Let's Encrypt cấp wildcard miễn phí, nhưng **bắt buộc xác thực qua DNS**
(`DNS-01`), không dùng được cách xác thực qua file như thường lệ.

### 2.5. Apache — thêm ServerAlias

Trong `httpd-vhosts.conf` (xem `deploy/httpd-vhosts.conf.example`), thêm một
dòng vào **cả khối cổng 80 lẫn 443**:

```apache
ServerName   etek.rikkeiedu.org
ServerAlias  *.etek.rikkeiedu.org
```

Thiếu dòng `ServerAlias` thì Apache đưa mọi tên miền phụ về vhost mặc định —
không tới được ứng dụng. Restart Apache sau khi sửa.

---

## 3. Kiểm sau khi triển khai

| Việc | Mong đợi |
|---|---|
| Mở `https://etek.rikkeiedu.org/` | Trang Tân Phát như cũ |
| Mở `https://tp01.etek.rikkeiedu.org/` | Cũng là trang Tân Phát |
| Mở `https://<mã gara>.etek.rikkeiedu.org/` | Trang của gara đó |
| Mở một tên miền phụ chưa khai | **404** "Không tìm thấy gara" |
| Đăng nhập tài khoản gara A tại địa chỉ gara B | **Bị đá ra** |
| Menu nhóm Kho | Có mục **Đặt hàng kho tổng** |
| Màn Quản lý gara › Thêm gara | Có khối **Tài khoản chủ gara** |

### Kiểm riêng cho đợt 08/10/2026

Đăng nhập bằng **tài khoản chủ gara** (nhóm Manager, không phải Tân Phát):

| Việc | Mong đợi |
|---|---|
| Hệ thống › Quản lý nhóm | Thấy **đúng hai nhóm của gara mình**, có cột Gara |
| Nhóm của chính mình | **Không có** nút Phân quyền / Sửa / Xoá |
| Nhóm Staff › Phân quyền | Mở được; ô ngoài quyền của mình hiện **khoá** |
| Lưu phân quyền Staff | Chỉ gara mình đổi — gara khác **không đổi gì** |
| Hệ thống › Quản lý module | **Không có trên menu** |
| Hàng hoá › Quản lý hàng hoá | Chỉ thấy **hàng của gara mình** |
| Hàng hoá › Thương hiệu | Thấy cả hàng kho tổng, nhưng **gắn nhãn "Kho tổng"** và không có nút Sửa |
| Thêm thương hiệu trùng tên kho tổng | **Lưu được**, slug tự thành `...-2` |
| Gõ `/admin/products/edit/<id hàng kho tổng>` | **Bị từ chối**, nói rõ là hàng kho tổng |
| Góc trên menu trái | Hiện **tên gara mình**, không phải "Tân Phát" |

Việc cuối: thử **thêm một gara mới**. Hệ thống phải tự dựng tên miền, kho, nhóm
khách, cấu hình web, **bộ nhóm quyền riêng** và tài khoản chủ — thông báo sau
khi lưu sẽ liệt kê ra.

---

## 4. Nếu phải lùi lại

Mỗi migration đều có `down()`. Lùi lần lượt:

```bash
C:\xampp\php\php.exe migrate.php rollback
```

Chạy 11 lần để về trước `000084`. Lùi `000087`, `000089` và `000094` sẽ **xoá
cột `garage_id`** của các bảng nội dung, khách web và danh mục hàng hoá — dữ
liệu phân chia theo gara mất theo. Lùi `000093` còn **xoá các nhóm quyền đã
nhân bản** (có trả người về nhóm mẫu trước, nhưng những quyền chủ gara tự sửa
thì mất). Nên **khôi phục từ bản sao lưu** vẫn là đường chắc chắn hơn.

---

## 5. Hai chỗ còn thiếu, đã biết

- **Giá trên web chưa theo gara.** Gara đặt giá riêng thì màn báo giá dùng giá
  đó, nhưng website vẫn hiện giá công ty. Chưa gara nào đặt giá riêng nên chưa
  lệch.
- **Lời nhắn khi bị đá khỏi trang quản trị không hiện.** Lỗi có sẵn từ trước
  đợt này. Người dùng vào nhầm địa chỉ gara khác bị đá ra mà không biết vì sao.

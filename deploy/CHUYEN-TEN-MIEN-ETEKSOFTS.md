# Chuyển sang tên miền `eteksofts.vn`

Khách yêu cầu (13/11/2026). Anh đã trỏ DNS xong. File này là danh sách việc còn lại,
theo thứ tự — **làm đúng thứ tự**, vì bước sau dựa vào bước trước.

## Tình trạng đo được ngày 13/11/2026

| Bản ghi DNS anh đã tạo | Kết quả đo |
|---|---|
| `@` A `103.74.101.148` | `eteksofts.vn` → đúng IP máy chủ |
| `*.etek` A `103.74.101.148` | `tp01.etek.eteksofts.vn` → đúng IP máy chủ |
| `www` CNAME `eteksofts.vn.` | `www.eteksofts.vn` → đúng IP máy chủ |
| `crm` A `27.72.102.65` | Máy khác, **không phải** máy này — không liên quan |

Máy chủ **đã nhận** request cho các tên đó (Apache nhận mọi host), nhưng ứng dụng trả
trang **"Không tìm thấy gara"** vì bảng `garage_domains` chưa khai chúng. Đường đã thông,
chỉ thiếu khai báo "tên này thuộc gara nào". Cổng 443 chưa có chứng chỉ:
`https://eteksofts.vn` không kết nối được.

Lưu ý: `*.etek` chỉ phủ tên **có thêm một nhãn phía trước** (`tp01.etek...`). Tên trần
`etek.eteksofts.vn` không có bản ghi nào và cũng **không cần** — đừng khai nó.

---

## Bước 1 — Chạy migration `000112` (TRƯỚC khi đẩy code, như mọi migration)

```bash
php migrate.php
```

Migration `2026_11_13_000112_ten_mien_eteksofts` **chỉ thêm** dòng vào `garage_domains`:

- Gara tổng: `eteksofts.vn` (`www.eteksofts.vn` tự khớp — hệ thống bỏ `www.` trước khi so).
- Mỗi gara: `<mã gara>.etek.eteksofts.vn`, ví dụ `tp01.etek.eteksofts.vn`.

Nó **không** đổi host chính, **không** gỡ tên miền cũ, chạy lại không sinh dòng trùng.
Không chạy được migration thì vào **Quản lý gara → Tên miền** khai tay đúng các dòng trên.

## Bước 2 — Mở thử từng địa chỉ (bằng `http://`, chưa cần SSL)

| Địa chỉ | Phải thấy |
|---|---|
| `http://eteksofts.vn/` | Trang Tân Phát |
| `http://www.eteksofts.vn/` | Trang Tân Phát |
| `http://tp01.etek.eteksofts.vn/` | Trang Tân Phát |
| `http://dmsg.etek.eteksofts.vn/` | Trang gara Sài Gòn *(nếu còn gara demo)* |
| `http://khong-co.etek.eteksofts.vn/` | "Không tìm thấy gara" (bị chặn đúng) |

Vẫn thấy "Không tìm thấy gara" ở dòng 1–4: migration chưa chạy trên máy chủ này.

## Bước 3 — `.env` trên máy chủ: `APP_URL` phải **để trống**

```
APP_URL=
```

Nếu đang ghim `APP_URL=http://etek.rikkeiedu.org` thì trang mở bằng `eteksofts.vn` vẫn nạp
ảnh, CSS, font từ tên miền **cũ** — trình duyệt chặn font khác tên miền (CORS) và mọi liên
kết/chuyển hướng văn bản dẫn về tên cũ. Để trống thì địa chỉ tự suy ra từ tên miền đang mở.
(Đây cũng là lời khuyên của `HUONG-DAN-SSL-NHIEU-TEN-MIEN.md` mục 6.)

## Bước 4 — SSL cho tên miền mới

Cần chứng chỉ cho:

| Phủ | Cách cấp |
|---|---|
| `eteksofts.vn`, `www.eteksofts.vn` | HTTP-01 (win-acme) — làm được ngay |
| `*.etek.eteksofts.vn` (mọi gara) | **Wildcard bắt buộc DNS-01**: thêm bản ghi TXT `_acme-challenge.etek` ở nơi quản lý DNS (đang là Mắt Bão — `ns1.matbao.vn`). Hoặc cấp lẻ từng gara bằng HTTP-01 |

Cách làm chi tiết: [`HUONG-DAN-SSL-NHIEU-TEN-MIEN.md`](HUONG-DAN-SSL-NHIEU-TEN-MIEN.md). VirtualHost:

```apache
ServerName   eteksofts.vn
ServerAlias  www.eteksofts.vn *.etek.eteksofts.vn
```

## Bước 5 — Đặt tên miền mới làm **chính** (làm SAU bước 2 và 4)

Màn **Quản lý gara → biểu tượng quả địa cầu (Tên miền) của gara Tân Phát**, bấm nút
**ngôi sao** (*Đặt làm tên miền chính*) trên dòng `tp01.etek.eteksofts.vn`.

**Vì sao không làm sớm:** hệ thống suy "tên miền gốc" cho gara mở sau từ host chính của
gara tổng. Đổi chính xong thì từ giây đó mọi gara mới mở nhận `<mã>.etek.eteksofts.vn`.
Đổi khi địa chỉ đó chưa có SSL / chưa ai mở thử là phát ra những địa chỉ chưa kiểm tra.
Vì vậy migration cố ý **không** tự đổi chính — đây là một cú bấm có chủ ý.

## Bước 6 — Tên miền cũ

Giữ chạy song song: link đã gửi khách, in trên giấy tờ không chết. Gỡ khi nào anh quyết,
ở cùng màn Tên miền (nút *Tắt* hoặc *Xoá* từng dòng). Nếu muốn khách tự chuyển sang địa chỉ mới,
thêm chuyển hướng 301 ở Apache **sau khi** bước 4 xong — chuyển hướng sang một địa chỉ
chưa có SSL là tự tạo lỗi bảo mật.

---

## Hoàn tác

`php migrate.php rollback` (nếu `000112` là batch cuối) gỡ **đúng** các dòng migration này
đã thêm (theo tên, chỉ những dòng không phải host chính). Tên miền anh tự khai thêm sau đó
ở màn Tên miền không bị đụng tới.

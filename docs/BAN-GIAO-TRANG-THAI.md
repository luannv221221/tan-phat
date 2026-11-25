# Bàn giao — trạng thái ngày 25/11/2026

Viết để một phiên Claude khác nhận việc tiếp. Cách làm việc, kiến trúc và các quy
tắc lâu dài nằm ở [`CLAUDE.md`](../CLAUDE.md) ở gốc repo. File này chỉ ghi **đang ở
đâu**. Mọi điều dưới đây đã đo lại ngày 25/11/2026, trừ chỗ ghi rõ "chưa kiểm".

## 1. Code

| | |
|---|---|
| Nhánh | `ban-giao-khach` = `origin/ban-giao-khach`, cây sạch |
| Đợt cuối | 25/11/2026 — sửa theo file Tracking của khách (mục 2a) |
| Test | **5017 PASS / 0 FAIL / 0 bỏ qua** — chạy toàn bộ 25/11/2026 sau khi gộp đợt này |
| Migration mới nhất | `000120` (`2026_11_25_000120_video_hang_hoa.php`). Đợt 25/11 thêm `000114` → `000120`, **chạy TRƯỚC khi đẩy code** (mục 4) |

Worktree cũ dọn được (đã merge hết vào `ban-giao-khach`): `.claude/worktrees/kind-mendel-d5ca2f`,
`competent-herschel-09e8e5`, `sua-loi-khach-2511`, `agent-ab2024cf2229f66b0`,
`agent-a028e6acfff919c76`.

Apache ở máy local **không tự bật lại** khi phiên Claude cũ kết thúc. Chạy test thấy
hàng loạt FAIL phần HTTP thì bật Apache trước (XAMPP Control Panel, hoặc chạy nền
`C:\xampp\apache\bin\httpd.exe`).

## 2a. Đợt 25/11/2026 — file "Tracking-Todo-Funtion list.xlsx" của khách

Khách ghi vào cột G ("Sửa lại") sheet Tracking; hai ô chữ đỏ là việc mới. Anh nhắn
thêm hai lỗi: chat không chạy, Sửa gara mất tài khoản chủ gara.

| Commit | Việc | Migration |
|---|---|---|
| `fc48a3d` | **Chat không chạy**: hội thoại ghi `insert()` thô, không gắn gara → admin không thấy, tin thứ hai lỗi trùng `session_key` | 000114 gán hội thoại mồ côi |
| `0735712` | Sửa gara: xem / sửa / đặt lại mật khẩu tài khoản chủ gara; bỏ nhãn "gara tổng" mồ côi | — |
| `3ce6c9a` | Phiếu xuất thêm loại Xuất bảo hành, Xuất dùng nội bộ; sửa loại `'ban'` của đặt hàng nội bộ | 000115 |
| `ecd7204` | Tên viết tắt về màn Cấu hình; Lĩnh vực = "Sửa chữa, bảo dưỡng gara" / "Khác" (giá trị lưu giữ `gara`/`phu_tung`) | — |
| `6ff526d` `99d4494` | Bản in báo giá / hoá đơn hết thừa dòng (cột Tên ~65mm, CK + thuế 0% chỉ in khi có) | — |
| `dd887cb` | Chân trang tự soạn THAY khối mặc định; menu chân trang hết lặp link | — |
| `505193c` | Chủ gara tự thêm / đổi tên / xoá nhóm quyền; chặn trùng tên nhóm trong gara | 000116 |
| `722d309` | Ô "Lắp cho đời xe" gập theo hãng | — |
| `c373549` | Ô Mô tả hàng hoá / dịch vụ có bộ soạn thảo; trang khách in qua `mo_ta_hang_hoa()` | — |
| `31e913b` | Báo cáo Hàng bán chạy (trong Báo cáo bán hàng) | — |
| `b6c4cb8` | 🔴 8 màn phiếu kho trên điện thoại giống đơn mua (`dong-hang-kho.js`) | — |
| `55dd7e6` `6e73cbc` | 🔴 **VAT nhập / VAT xuất theo hàng hoá**, VAT từng dòng đơn mua / báo giá / hoá đơn, in tách theo mức, HĐĐT `TSuat` từng dòng | 000117 |
| `c94c54e` | Trang **Hồ sơ của tôi** (tên, ảnh, SĐT, địa chỉ + đổi mật khẩu) | 000118 |
| `19a320e` | **Sổ quỹ** tiền mặt + tiền gửi ngân hàng (tab trong Báo cáo kế toán; 5 loại phiếu BC/BN qua 1121) | 000119 |
| (video) | Video YouTube cho hàng hoá: form, Excel nhập / xuất, tab Video trang khách | 000120 |

Câu khách hỏi đã trả lời qua anh: MySQL 8; Model xe ở kho tổng (Danh mục xe);
Quận/Huyện không còn (Tỉnh → Phường/Xã sau 2025); doanh thu theo NV / KH đã có ở
Báo cáo bán hàng (nhóm Bán hàng bị menu gọn của Tân Phát ẩn); trạng thái báo giá
đã có, không làm hợp đồng.

**Chưa làm từ file này:** thông số kỹ thuật chưa có trong file Excel nhập / xuất (có
trên form); đổi chữ "gara" → "công ty" toàn giao diện (~270 chỗ, đã hỏi anh phạm
vi); hai ghi chú chờ khách — xem mục 5.

## 2. Đã làm gần đây (11 → 20/11/2026)

| Commit | Việc |
|---|---|
| `33e05e5` `11e7bb3` | Đổi mật khẩu cho tài khoản quản trị; tuyến chỉ-có-POST của admin đi qua AuthMiddleware / RoleMiddleware |
| `7b834bb` `6ee4d5b` `e989e48` | Bảng tính giá (giá vốn + giá bán theo loại khách), đặt lịch tự cập nhật giá, chuyển sang nhóm Kế toán |
| `1841807` `e75cdf0` | Rà soát 12/11: bài viết, cấu hình website, đơn đặt hàng từ web, lối tắt tự đặt |
| `4554740` `1961b0e` | Bảng mobile trở lại dạng trượt; nút −/+ số lượng trên điện thoại; chọn trùng mặt hàng (đơn mua, báo giá, hoá đơn bán) thì cộng số lượng |
| `062c371` (000112) | Khai `eteksofts.vn` + `<mã>.etek.eteksofts.vn` (chỉ thêm, không đổi tên chính) |
| `fad9d23` | Màn Tên miền nhắc cấp lại chứng chỉ SSL |
| `d5391a6` | Ô chọn nhóm ghi tên gara; chặn gán nhóm của gara khác |
| `ac115af` | **Mã QR chuyển khoản VietQR** trên hoá đơn bán; mỗi gara tự chọn ngân hàng ở Cấu hình (`core/VietQr.php`, vẽ phía trình duyệt bằng qrcode-generator) |
| `2a6e42c` | Trang "Đặt hàng thành công" hiện tài khoản của gara đang bán, không phải Tân Phát |
| `42cedbe` | Hoá đơn không có QR thì hiện dòng nhắc vàng (chỉ trên màn hình) chỉ cách khai ở Cấu hình |
| `b992996` (000113) | Danh sách hoá đơn có QR nhỏ + nút **Gửi**; link thanh toán công khai cho khách (`/thanh-toan/<token 32 ký tự>`, không cần đăng nhập; test `LinkThanhToanTest.php`) |
| `f24994c` | Mở gara mới thì nhận tên miền trên **mọi** đuôi gara tổng đang chạy (`MoGaraModel::cacDuoiPhu()`) |
| `1a927a0` | Viết lại Bước 6 của `deploy/CHUYEN-TEN-MIEN-ETEKSOFTS.md` |

## 3. Máy chủ thật — đo từ ngoài ngày 25/11/2026

- `eteksofts.vn` và `etek.rikkeiedu.org` đều trỏ `103.74.101.148`.
- Code trên máy chủ có ít nhất tới `b992996`: `http://eteksofts.vn/public/assets/js/vietqr.js`
  trả 200 (file này sinh ra ở `b992996`). Vậy migration `000113` gần như chắc đã chạy
  — **chưa kiểm** trong CSDL. `f24994c` đã lên chưa: **chưa kiểm** (không có tệp công
  khai nào để dò).
- Chứng chỉ SSL: Let's Encrypt `CN=eteksofts.vn`, hạn **11/02/2027**, đúng 5 tên:
  `eteksofts.vn`, `www.eteksofts.vn`, `tp01` / `dmsg` / `dmdn` `.etek.eteksofts.vn`.
- DNS `*.eteksofts.vn` **ĐÃ CÓ** (đo chiều 25/11): `tp01` / `ml` `.eteksofts.vn` → `103.74.101.148`.
  Cả cổng 80 lẫn 443 đã vào tới ứng dụng ("Không tìm thấy gara" — đúng, chưa khai).
  Host lạ bất kỳ cũng vào ứng dụng (vhost mặc định), nên không đo được `ServerAlias`.
  Chứng chỉ vẫn 5 tên cũ → `https://tp01.eteksofts.vn` lỗi chứng chỉ.
- Anh mở thêm gara **lotus** (`lotus.etek.rikkeiedu.org`, ảnh chụp 25/11).
- Gara trên máy chủ đã biết (theo chứng chỉ và lần đo 20/11): TP01 Tân Phát (gara
  tổng), DMSG và DMDN (hai gara mẫu), **ML MinhLong** (anh mở 20/11, hiện chỉ có
  `ml.etek.rikkeiedu.org` — `http://ml.etek.rikkeiedu.org/` ra đúng trang MinhLong). Có
  thể còn gara khác — xem màn Quản lý gara trên máy chủ.

## 4. Việc đang dở — phần của anh trên máy chủ

1. **Đổi tên chính của mọi gara sang `<mã>.eteksofts.vn`** (anh chốt 13/11, nhắc lại
   20/11). Không cần sửa code. Làm đúng thứ tự trong
   `deploy/CHUYEN-TEN-MIEN-ETEKSOFTS.md` **Bước 6**:
   1. DNS Mắt Bão: thêm bản ghi Host `*`, loại `A`, giá trị `103.74.101.148`.
      **XONG** (đo 25/11 chiều).
   2. Thêm `*.eteksofts.vn` vào `ServerAlias` cả khối 80 lẫn 443, `httpd.exe -t`,
      restart Apache. Đường đã thông (xem mục 3) — nếu chưa thêm thì vẫn nên thêm.
   3. **Đang ở bước này:** chạy lại win-acme kê thêm `<mã>.eteksofts.vn` của mọi gara
      (giữ tên cũ; **nhớ cả `lotus`**); `wacs --list` chỉ được còn một lịch gia hạn.
   4. Màn Tên miền: khai `<mã>.eteksofts.vn` + tích "Đặt làm tên miền chính", **Tân
      Phát trước**, rồi các gara còn lại.
   5. Mở thử `https://<mã>.eteksofts.vn`.

   Khi anh gửi ảnh / báo xong một bước: đo lại từ ngoài (`nslookup <tên> 8.8.8.8`,
   `curl`, `openssl s_client -connect eteksofts.vn:443 -servername <tên>`) trước khi
   bảo anh làm bước sau.
2. **Đẩy đợt 25/11 lên máy chủ**: 7 migration `000114` → `000120` (đều chỉ thêm cột /
   thêm dòng / điền dữ liệu, chạy được khi code cũ còn chạy). Lệnh đúng thứ tự ở
   `deploy/CHUYEN-TEN-MIEN-ETEKSOFTS.md` Bước 2 (`git checkout origin/ban-giao-khach --
   database/migrations` → `migrate.php` → `git pull --ff-only`). **Chưa kiểm** anh đã
   đẩy chưa.
3. Mỗi gara tự chọn **ngân hàng** + số tài khoản ở Cấu hình thì hoá đơn mới có QR.
3. Gara chưa khai cấu hình website riêng thì trang khách vẫn mang tên, hotline, địa
   chỉ, email của Tân Phát — gara tự điền ở Cấu hình (không phải lỗi code; muốn đổi
   cách lấy mặc định thì hỏi anh trước).
4. `.env` máy chủ: `APP_URL=` (để trống) và `APP_DEBUG=false`. Ghi chép cũ nói máy
   chủ còn `APP_DEBUG=true` — **chưa xác nhận** đã sửa.
5. win-acme tự gia hạn chứng chỉ nhưng Apache chỉ đọc chứng chỉ mới **sau khi
   restart** — nhắc anh trước 11/02/2027, hoặc gắn lệnh restart vào win-acme.
6. Mỗi lần mở gara mới: chạy lại lệnh win-acme thêm tên gara đó (chứng chỉ kê từng
   tên). Muốn bỏ việc này phải dùng wildcard qua DNS có API (vd. Cloudflare) — việc
   riêng, chưa ai giao.

## 5. Chờ khách trả lời — đừng tự đoán

- File Tracking 25/11, dòng "Quản lý giá nhập": "chuyển tất cả danh sách chức năng theo
  chiều ngang" — menu chức năng nằm ngang trên đầu trang, hay nút thao tác trong bảng
  xếp ngang? Hai việc khác hẳn nhau.
- File Tracking 25/11, dòng "Thêm mới, sửa, xóa sản phẩm": "chưa đổi đường dẫn" — slug
  sản phẩm khi đổi tên, hay tên miền mới?
- File Tracking 25/11: "đổi gara thành công ty (gara chỉ là 1 việc của công ty)" — đã
  hỏi anh có đổi chữ trên toàn giao diện không.
- File Tracking 25/11: "dùng AI gì, bước tiếp theo có thêm AI không" — anh trả lời khách.
- "Đo đạc thông minh" trong tài liệu khách 05/11 là gì.
- Lỗi nhóm tài khoản (chưa tái hiện được).
- "Khách đăng ký tài khoản chưa chạy" có cùng gốc với lỗi đơn web (migration 000099) không.
- **Menu gọn của kho tổng** (16/10) vẫn là bản thử, anh chưa chốt. Anh bảo "về như
  cũ" thì đổi `_KHO_TONG_MENU_GON` thành `false` trong `config.php` rồi chạy lại test;
  ghi chú ở `docs/thu-nghiem-menu-gon-kho-tong.md`.

## 6. Đã chốt — đừng hỏi lại

- Đơn mua: serial theo **từng chiếc**; chưa theo dõi đã bán serial cho ai; lĩnh vực
  (gara / bán phụ tùng) mỗi gara tự chọn, mặc định `gara`; phiếu nhập từ đặt hàng kho
  tổng có tính nợ NCC. Đơn mua **có** giá (đảo lại 31/10).
- Danh mục hãng / model / năm / màu / đơn vị dùng chung mọi gara, gara được tự thêm
  (chặn trùng bằng slug). Nhóm khách là riêng từng gara.
- Màn **Đối tượng** (Bán hàng) là nơi khai khách + nhà cung cấp, giữ trên menu. Màn
  **CSKH › Khách hàng** chỉ chăm sóc, **không** có nút Thêm.
- Gara không được sửa tên / ảnh hàng của kho tổng, chỉ đổi giá.

## 7. Chỗ còn để ngỏ (chưa ai giao, đừng tự làm nếu anh không nhắc)

- Nút Messenger ở CSKH: cần thêm cột lưu địa chỉ Facebook từng khách.
- Serial: gắn vào phiếu xuất, hạn bảo hành (khi làm thì thêm cột, đừng dựng lại bảng).
- Tương thích nginx / LiteSpeed: chưa thử thật, đừng hứa với khách.
- Trang hướng dẫn nhiều gara trên Vercel (`https://tan-phat-huong-dan-gara.vercel.app`)
  còn mô tả mô hình chi nhánh cũ. Đưa lại lên Vercel: file `docs/huong-dan-nhieu-gara.html`
  thiếu `<!DOCTYPE>` / charset / viewport — bọc lại thành `index.html`, đặt **một mình**
  trong thư mục tên `tan-phat-huong-dan-gara`, rồi `vercel deploy --prod --yes` trong
  thư mục đó. **Không** chạy `vercel` ở gốc repo.

## 8. Ghi nhớ của phiên trước

Phiên trước lưu ghi nhớ ở `C:\Users\admin\.claude\projects\C--xampp-htdocs-tan-phat\memory\`
(chỉ có trên máy này). Nội dung quan trọng đã chép vào `CLAUDE.md` và file này.
Vài ghi nhớ ở đó đã cũ so với code: "Kế toán mới là chỗ đứng" (Phiếu thu / Phiếu chi
đã dựng thật từ `35d6c4d`, 14/10), và IP máy chủ `103.180.138.189` (nay là
`103.74.101.148`). Code và file này đúng hơn.

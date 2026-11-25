# Bàn giao — trạng thái ngày 25/11/2026

Viết để một phiên Claude khác nhận việc tiếp. Cách làm việc, kiến trúc và các quy
tắc lâu dài nằm ở [`CLAUDE.md`](../CLAUDE.md) ở gốc repo. File này chỉ ghi **đang ở
đâu**. Mọi điều dưới đây đã đo lại ngày 25/11/2026, trừ chỗ ghi rõ "chưa kiểm".

## 1. Code

| | |
|---|---|
| Nhánh | `ban-giao-khach` = `origin/ban-giao-khach`, cây sạch |
| Commit code cuối | `f24994c` (20/11) — sau đó chỉ có commit tài liệu |
| Test | **4482 PASS / 0 FAIL / 0 bỏ qua** — chạy lại 25/11/2026 trên bản có hai file bàn giao này |
| Migration mới nhất | `000113` (`2026_11_18_000113_link_thanh_toan_hoa_don.php`, thêm cột `pay_token`) |

Worktree cũ dọn được: `.claude/worktrees/kind-mendel-d5ca2f` và
`.claude/worktrees/competent-herschel-09e8e5` — cả hai đã merge hết vào `ban-giao-khach`.

Apache ở máy local **không tự bật lại** khi phiên Claude cũ kết thúc. Chạy test thấy
hàng loạt FAIL phần HTTP thì bật Apache trước (XAMPP Control Panel, hoặc chạy nền
`C:\xampp\apache\bin\httpd.exe`).

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
- DNS `*.eteksofts.vn` **chưa có**: `tp01.eteksofts.vn`, `ml.eteksofts.vn` → *Non-existent domain*.
- Gara trên máy chủ đã biết (theo chứng chỉ và lần đo 20/11): TP01 Tân Phát (gara
  tổng), DMSG và DMDN (hai gara mẫu), **ML MinhLong** (anh mở 20/11, hiện chỉ có
  `ml.etek.rikkeiedu.org` — `http://ml.etek.rikkeiedu.org/` ra đúng trang MinhLong). Có
  thể còn gara khác — xem màn Quản lý gara trên máy chủ.

## 4. Việc đang dở — phần của anh trên máy chủ

1. **Đổi tên chính của mọi gara sang `<mã>.eteksofts.vn`** (anh chốt 13/11, nhắc lại
   20/11). Không cần sửa code. Làm đúng thứ tự trong
   `deploy/CHUYEN-TEN-MIEN-ETEKSOFTS.md` **Bước 6**:
   1. DNS Mắt Bão: thêm bản ghi Host `*`, loại `A`, giá trị `103.74.101.148`.
      **Đang ở bước này:** 20/11 anh chụp màn Mắt Bão, ô "Tạo bản ghi" đã mở nhưng
      chưa thêm; 25/11 tra vẫn chưa có.
   2. Thêm `*.eteksofts.vn` vào `ServerAlias` cả khối 80 lẫn 443, `httpd.exe -t`,
      restart Apache.
   3. Chạy lại win-acme kê thêm `<mã>.eteksofts.vn` của mọi gara (giữ tên cũ);
      `wacs --list` chỉ được còn một lịch gia hạn.
   4. Màn Tên miền: khai `<mã>.eteksofts.vn` + tích "Đặt làm tên miền chính", **Tân
      Phát trước**, rồi DMSG, DMDN, ML.
   5. Mở thử `https://<mã>.eteksofts.vn`.

   Khi anh gửi ảnh / báo xong một bước: đo lại từ ngoài (`nslookup <tên> 8.8.8.8`,
   `curl`, `openssl s_client -connect eteksofts.vn:443 -servername <tên>`) trước khi
   bảo anh làm bước sau.
2. Mỗi gara tự chọn **ngân hàng** + số tài khoản ở Cấu hình thì hoá đơn mới có QR.
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

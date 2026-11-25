# Tân Phát — nền tảng quản lý gara ô tô

PHP MVC tự viết, không framework (`core/`, `app/controllers|models|views`,
`routes/web.php`). Một bản cài, một CSDL, phục vụ nhiều gara.

**Trạng thái hiện tại + việc đang dở: [docs/BAN-GIAO-TRANG-THAI.md](docs/BAN-GIAO-TRANG-THAI.md).
Đọc file đó trước khi nhận việc.**

## Làm việc với chủ dự án

- Gọi là "anh", trả lời **tiếng Việt**. Anh hay nhắn từ điện thoại: tin nhắn cuối
  phải đủ ý, tự đứng được, không bắt anh kéo lên đọc lại.
- **Cứ làm, đừng chờ chốt.** Yêu cầu rõ thì làm luôn theo cách tốt nhất. Chỗ tự
  quyết thì nói ra ("anh thấy không hợp thì nói em sửa") rồi vẫn làm tiếp. Chỉ
  dừng hỏi khi hai cách hiểu dẫn tới hai khối việc khác hẳn nhau. Anh hay lướt qua
  câu xin phê duyệt, nên dừng chờ chỉ khiến việc đứng yên.
- Anh gửi ảnh chụp màn hình máy chủ / điện thoại để hỏi. Đọc kỹ ảnh, đo từ ngoài
  được thì đo (`curl`, `nslookup`, `openssl s_client`) rồi mới trả lời.

## Git

- Nhánh làm việc là **`ban-giao-khach`**. `main` bị bỏ xa (170+ commit), **đừng**
  rẽ nhánh từ `main` hay mở PR vào `main`.
- Quy trình: làm trên nhánh / worktree riêng → commit →
  `git merge --ff-only` vào `ban-giao-khach` → `git push origin ban-giao-khach`.
  Có phiên khác đẩy trước thì rebase lên rồi **chạy lại toàn bộ test** mới merge.
- Commit: tiêu đề tiếng Việt không dấu, kiểu `feat(hoa-don): ...`, `fix(mo-gara): ...`.
  Thân nói **vì sao** (thường kể đúng ca anh gặp), số test đỏ trước khi sửa / số
  PASS sau, và dòng `Khong doi CSDL` hoặc migration nào phải chạy.

## Chạy ở máy local

- XAMPP. Apache cổng **88** (IIS chiếm 80): `http://localhost:88/tan-phat/`.
  PHP: `C:\xampp\php\php.exe`. CSDL MySQL 8 `tanphat_php`, cấu hình trong `.env`.
- `mysql.exe` / `mysqldump.exe` **hỏng** (thiếu plugin `caching_sha2_password`).
  Truy vấn CSDL bằng PDO: hằng `_HOST _PORT _USER _PASS _DB` ở `config.php`.
- Gara thử theo tên miền ở local: `http://dmsg.localhost:88/tan-phat/`, `dmdn.localhost`,
  `tp01.localhost`.
- Tài khoản thử (chỉ local): email `@gara-mau.test`, sinh bằng
  `tools/tao-du-lieu-gara-mau.php` (mật khẩu ở hằng `MK_MAU`). `admin@gara-mau.test`
  tạo tay — chạy lại tool đó là mất. **Không bao giờ** tạo các tài khoản này trên máy chủ.
- Ô CSRF tên `_token`.

## Test

- `C:\xampp\php\php.exe tests\run.php` — runner tự viết, **không** PHPUnit. Danh
  sách file khai cứng trong `tests/run.php`: thêm file test mới phải đăng ký vào đó.
- Baseline là **0 FAIL**. Thấy FAIL nào là hồi quy thật. Mốc gần nhất ghi trong
  file trạng thái.
- Phần HTTP của test cần **Apache local đang chạy**; Apache tắt là FAIL oan.
- Làm trong worktree (`.claude/worktrees/<tên>/`): copy `.env` từ thư mục chính,
  sửa `APP_BASE_PATH=/tan-phat/.claude/worktrees/<tên>`. Không thì test HTTP đo bản
  mã ở thư mục chính. Test mới có gọi HTTP thì dùng `web_goc()` (`tests/_helpers.php`).
- Làm TDD: viết test, chạy thấy đỏ đúng lý do, rồi mới sửa.

## Kiến trúc phải nắm trước khi viết tính năng

- **Mô hình:** kho tổng (Tân Phát, gara mã `TP01`, `garages.is_master = 1`) → nhiều
  gara **độc lập** → khách → xe → phiếu. Gara không thấy dữ liệu của nhau.
  Spec: `docs/superpowers/specs/2026-09-22-gara-doc-lap-tren-nen-tang-design.md`.
- **Nhận gara theo tên miền:** bảng `garage_domains` + `TenMienMiddleware`. Hai cửa
  thoát cố ý, đừng gỡ: bảng rỗng → chạy kiểu cũ; host nội bộ (`localhost`, `*.test`)
  → chạy kiểu cũ (bộ test sống nhờ nó).
- **Dữ liệu nghiệp vụ mới phải có `garage_id`.** Model bật `$_theoGara = true`.
  Ba bẫy đã mắc thật:
  1. Truy vấn tự viết phải dùng `bangGara()`, **không** `$this->table($this->_table)`
     — cờ `$_theoGara` không áp vào đó (bài của gara Sài Gòn từng hiện ở trang Tân
     Phát mà test vẫn xanh).
  2. Lấy gara trong model bằng `Model::garaLoc()`, **không** `gara_hien_tai()`
     (dòng lệnh luôn trả gara tổng).
  3. Làm việc dưới danh nghĩa gara khác: `Model::trongGara($id, fn)`.
     `Model::epGara()` **không** có tác dụng trên web.
  Danh mục dùng chung + riêng: cờ `$_chungVaRieng` (`garage_id` NULL = chung).
  Chốt chặn: `tests/CachLyGaraTest.php`.
- Màn chỉ của Tân Phát: cờ `chi_tan_phat`. Menu gọn của kho tổng là **bản thử**:
  `_KHO_TONG_MENU_GON` trong `config.php`, đổi `false` là về như cũ.
- Cấu hình theo gara: `garage_settings` (`GarageSettingsModel`).
  `cau_hinh_in_an($garaId)` cho thông tin in ấn / ngân hàng — tài khoản ngân hàng
  **không** kế thừa từ Tân Phát (gara chưa khai thì không in QR, không mượn tài
  khoản Tân Phát).
- Mở gara mới: `MoGaraModel::dungBoKhung()` dựng tên miền (trên mọi đuôi gara tổng
  đang chạy), kho, nhóm khách, cấu hình web, nhóm quyền (nhân bản từ gara tổng),
  tài khoản chủ gara. Tên miền gốc suy từ host CHÍNH của gara tổng (`tenMienGoc()`).

## Quy tắc giao diện anh đã chốt

- Ô mang giá trị lặp lại (hãng, model, năm, màu, đơn vị, danh mục, khách, phụ tùng,
  nhân viên) phải **CHỌN** từ danh mục, kèm nút **+** thêm nhanh (`nut_them_nhanh()`,
  trùng thì dùng lại dòng cũ theo slug). Bỏ ô gõ tay ở cả form lẫn đường lưu.
  Ngoại lệ gõ tay: biển số, số khung, số máy, số km, ghi chú / tình trạng.
- Bảng trên điện thoại: **trượt ngang** + dòng gợi ý "Vuốt ngang để xem đủ các
  cột". Khách đã bác cột ghim (sticky) và thẻ xếp dọc.
- Dữ liệu lưu nguyên văn, escape lúc in (`e()`, `{{ }}`). `SecurityTest` quét XSS:
  mọi `<?php echo $bien` trong view phải qua `e()` / `(int)` / `number_format`.
- View: `@if` / `@foreach` / `@endif` phải **đứng riêng một dòng** — viết chung dòng
  với chữ thì bộ dựng view in ra nguyên văn `@if (...)` (đã gặp 25/11). Cần điều kiện
  giữa dòng thì dùng `<?php if (...): ?>`.

## Máy chủ thật

- Windows + XAMPP, cùng đường dẫn `C:\xampp\htdocs\tan-phat` — thấy đường dẫn đó
  trong lỗi **không** nói được lỗi ở máy nào. IP `103.74.101.148`; tên miền
  `eteksofts.vn` (mới) và `etek.rikkeiedu.org` (cũ, vẫn chạy song song).
- **Chạy migration TRƯỚC, đẩy code SAU** — đẩy code trước từng làm sập cả admin.
  Ngoại lệ 000069 + 000080: chạy sau (`deploy/sau-khi-day-code.sql`).
  Cách làm từng bước: `deploy/CHUYEN-TEN-MIEN-ETEKSOFTS.md` Bước 2.
- Khách đang nhập dữ liệu thật trên máy chủ: **đừng** bảo anh xuất CSDL local rồi
  nhập đè lên máy chủ nữa.
- **Không** đăng nhập máy chủ / trang quản trị thật bằng mật khẩu. Muốn biết tình
  trạng thì đo từ ngoài, hoặc nhờ anh chụp màn hình.
- Tài liệu triển khai: `deploy/*.md`.

## Đừng làm

- Đừng chạy `vercel` ở gốc repo (đẩy cả `.env`). Cách đưa trang hướng dẫn lên
  Vercel ghi trong file trạng thái.
- Đừng commit file `.sql` dump (có email, SĐT khách, hash mật khẩu).
- Khách **đã bỏ**: kết nối sàn TMĐT, đa ngôn ngữ — đừng báo giá, đừng làm.

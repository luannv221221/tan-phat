# TÀI LIỆU ĐẶC TẢ YÊU CẦU PHẦN MỀM (SRS)
## Dự án: ERP Tân Phát

| | |
|---|---|
| **Tên dự án** | ERP Tân Phát Project |
| **Nguồn yêu cầu** | `Tracking-Todo-Funtion list.xlsx` (các sheet: Tracking, MENU, Biểu mẫu, Công việc, Rule, Help) |
| **Nguồn kỹ thuật** | Source code `framework_11_12_2021_fix` (PHP MVC tự viết) |
| **Phiên bản** | 1.1 — dựng lại từ tracking file + source code; toàn bộ trạng thái reset về Open |
| **Ngày lập** | 17/07/2026 |
| **Trạng thái dự án** | Dừng/đổ vỡ (xem mục 9 — Bài học từ sheet Help) |
| **Tiến độ** | **0/68 hoàn thành** — toàn bộ hạng mục ở trạng thái `Open` (xem mục 8.1) |

---

## 1. GIỚI THIỆU

### 1.1 Mục đích tài liệu
Tài liệu này đặc tả lại toàn bộ yêu cầu chức năng và phi chức năng của hệ thống ERP Tân Phát, được tái dựng (reverse-engineer) từ file tracking công việc và mã nguồn hiện có. Tài liệu phục vụ 2 mục tiêu:
1. Xác định **ứng dụng này làm về cái gì** — phạm vi nghiệp vụ thực sự.
2. Làm cơ sở để đánh giá lại (re-scope) hoặc khởi động lại dự án.

### 1.2 Phạm vi sản phẩm
Hệ thống được chia làm **2 phần lớn** (theo ghi chú tại ô C8 sheet Tracking):

> **"TỔNG THỂ CHIA 2 PHẦN: DÙNG CHO GARA VÀ WEBSITE BÁN HÀNG"**

| Phần | Mô tả | Người dùng |
|---|---|---|
| **A. ERP nội bộ (Back-office)** | Quản trị toàn bộ hoạt động của một doanh nghiệp kinh doanh **thiết bị gara ô tô và phụ tùng ô tô**: mua hàng, kinh doanh, kho, kế toán, CSKH, nhân sự, báo cáo. | Nhân viên nội bộ (Kinh doanh, Kho, Kế toán, CSKH, BGĐ) |
| **B. Website bán hàng (Front-office)** | Website thương mại điện tử bán thiết bị gara + phụ tùng ô tô, có giỏ hàng, SEO, CMS, chat hỗ trợ. | Khách hàng, khách vãng lai |

### 1.3 Định nghĩa và viết tắt

| Thuật ngữ | Ý nghĩa |
|---|---|
| **Phân hệ** | Module nghiệp vụ lớn (Kinh doanh, Kho, Kế toán, CSKH, Website) |
| **NCC** | Nhà cung cấp |
| **CSKH** | Chăm sóc khách hàng |
| **BPKD** | Bộ phận kinh doanh |
| **NVKD** | Nhân viên kinh doanh |
| **BGĐ** | Ban Giám đốc |
| **Chứng từ / Biểu mẫu** | Phiếu nghiệp vụ (phiếu thu, phiếu nhập kho, báo giá…) |
| **Module** | Đơn vị phân quyền trong hệ thống (bảng `modules`) |

### 1.4 Quy ước trạng thái công việc (sheet Rule + Help)

| Trạng thái | Định nghĩa |
|---|---|
| **Open** | Công việc chưa hoàn thành. Chưa làm đến, đang làm, hoặc đang sửa lỗi |
| **Coding** | Đang code |
| **Testing** | Đang test |
| **Closed** | Công việc đã hoàn thành, đã test xong |

---

## 2. MÔ TẢ TỔNG QUAN

### 2.1 Bối cảnh nghiệp vụ
Tân Phát là doanh nghiệp kinh doanh **thiết bị gara ô tô và phụ tùng ô tô**. Nghiệp vụ đặc thù thể hiện rõ qua:
- Danh mục xe nhiều tầng: **Hãng xe → Dòng xe → Model xe → Đời xe → Nhiên liệu → Màu xe**
- Phụ tùng gắn với xe: "tạo phụ tùng (phụ tùng theo xe)", "Chọn xe sẽ lọc ra các phụ tùng"
- Nghiệp vụ lắp đặt/bàn giao/bảo hành thiết bị tại chỗ khách hàng
- Kho nhiều cấp: **Kho hàng → Nhà kho → Dãy → Khoang → Tầng**

### 2.2 Các nhóm người dùng

| Nhóm | Nguồn | Chức năng chính |
|---|---|---|
| **Admin** | Bảng `groups` (seed) | Toàn quyền, cấu hình hệ thống, phân quyền |
| **Manager** (Trưởng phòng/BGĐ) | Bảng `groups` (seed) | Duyệt, xem báo cáo phòng, báo cáo kinh doanh |
| **Staff** (Nhân viên) | Bảng `groups` (seed) | Nghiệp vụ theo phân hệ được cấp quyền |
| **Nhân viên Kinh doanh** | Sheet Biểu mẫu | Báo giá, hợp đồng, khách hàng, công nợ |
| **Kế toán / Thủ quỹ** | Sheet Biểu mẫu | Phiếu thu/chi, sổ quỹ, sổ sách kế toán, công nợ |
| **Thủ kho** | Sheet MENU | Nhập/xuất/điều chuyển/kiểm kê, thẻ kho |
| **CSKH / Bảo hành** | Sheet Biểu mẫu | Yêu cầu bảo hành, giao nhận thiết bị |
| **Khách hàng (web)** | Sheet Tracking | Tìm kiếm, lọc, giỏ hàng, thanh toán, bình luận |

### 2.3 Ràng buộc thiết kế (từ tài liệu kỹ thuật trong repo)
- Kiến trúc **MVC tự viết**, mô phỏng Laravel (`co_che_hoat_dong_route.txt`, `query_builder.txt`)
- Mô hình **request–respond kiểu Laravel** (tracking C16)
- Route dạng **regex virtual path → controller path**, phân biệt GET/POST
- Middleware nằm giữa Route và Controller; 2 loại: global và route-registered
- Query Builder fluent: `select/table/where/orWhere/whereLike/join/orderBy/limit/get/first/insert/update/delete/lastId`
- Quy ước: **1 model / 1 bảng**, **1 thư mục views / 1 controller**
- Không dùng thư viện ngoài (composer chỉ dùng để autoload PSR-4)
- Frontend: PHP server-rendered + Bootstrap + jQuery, không có build step

---

## 3. YÊU CẦU CHỨC NĂNG — PHẦN A: ERP NỘI BỘ

### 3.1 CÔNG VIỆC CHUNG (nền tảng)

| ID | Yêu cầu | Trạng thái |
|---|---|---|
| GEN-01 | Tìm hiểu quy trình nghiệp vụ; lập biểu đồ luồng hoạt động, tài liệu mô tả; thu thập chứng từ liên quan | Chưa track |
| GEN-02 | Thiết kế database hệ thống | Chưa track |
| GEN-03 | Thiết kế mô hình request–respond theo kiểu Laravel | Chưa track |
| GEN-04 | Viết tài liệu | Chưa track |

### 3.2 CHỨC NĂNG TÀI KHOẢN

| ID | Yêu cầu | Ghi chú | Hiện trạng code |
|---|---|---|---|
| ACC-01 | Xây dựng chức năng đăng nhập, đăng xuất | | ✅ Đã có (`Auth.php`, `dang-nhap`/`dang-xuat`) |
| ACC-02 | Cập nhật tài khoản | | ⚠️ Một phần (`users/edit`) |
| ACC-03 | Quản lý tài khoản người dùng | | ✅ Đã có (`Users.php` CRUD) |
| ACC-04 | Phân quyền theo nhóm (tài khoản — nhóm — module) | *"tạo modul từ danh mục (kinh doanh, quản trị..), phân quyền cho group và user"* | ✅ Đã có (`Groups.php`, `permissions`, `RoleMiddleware`) |

**Cơ chế đăng nhập (từ `co che login token`):**
- Token đăng nhập lưu trong DB (bảng `login_token`), có lưu lịch sử, chống dùng chung tài khoản
- Ghi nhận `client_ip` và `current_activity`
- Session chỉ lưu **id của dòng token**, không lưu token
- Middleware kiểm tra token còn tồn tại; token idle > 15 phút bị xóa

**Cơ chế phân quyền (từ `co_che_phan_quyen_middleware.txt`):**
- Phân quyền theo **module × group × role**
- Role gồm: `view`, `add`, `edit`, `delete`, `permission`
- Middleware có 2 chế độ: (a) chặn URL → redirect `admin/khong-co-quyen`; (b) trả về true/false để **ẩn link không có quyền trên view**

### 3.3 CHỨC NĂNG CẤU HÌNH HỆ THỐNG

| ID | Yêu cầu | Hiện trạng code |
|---|---|---|
| CFG-01 | Thiết lập logo, tên công ty, điện thoại, slogan… | ⚠️ Có bảng `options` (opt_name/opt_value), chưa có UI |
| CFG-02 | Thiết lập số trang trên phân trang, dung lượng ảnh upload… | ⚠️ Như trên |
| CFG-03 | Thiết lập tài khoản mạng xã hội, cửa hàng… | ⚠️ Như trên |

### 3.4 DANH MỤC XE, HÃNG XE

| ID | Yêu cầu | Ví dụ |
|---|---|---|
| CAR-01 | Hãng xe | Toyota, Honda… |
| CAR-02 | Dòng xe | Hatchback, Sedan… |
| CAR-03 | Model xe | Morning, Vios… |
| CAR-04 | Đời xe | Năm sản xuất |
| CAR-05 | Nhiên liệu (động cơ xe) | |
| CAR-06 | Màu xe | |

> Đây là **danh mục lõi** phân biệt hệ thống này với ERP thông thường — mọi phụ tùng đều tham chiếu tới cây danh mục xe.

### 3.5 DANH MỤC HÀNG HÓA

| ID | Yêu cầu |
|---|---|
| ITM-01 | Danh sách hàng hóa |
| ITM-02 | Danh sách hàng tạm |
| ITM-03 | Hàng tạm của tôi |
| ITM-04 | Nhóm hàng hóa |
| ITM-05 | Danh mục hàng hóa |
| ITM-06 | Cập nhật nhanh giá hàng hóa |
| ITM-07 | Thương hiệu |
| ITM-08 | Xuất xứ |
| ITM-09 | Hãng sản xuất |
| ITM-10 | Đơn vị |
| ITM-11 | Thuộc tính |
| ITM-12 | Đơn vị thuộc tính |

Bổ sung từ sheet MENU: **Model, Lĩnh vực, Chương, Nhóm công việc, Cụm công việc, Code đặt hàng, Phân loại**.

### 3.6 DANH MỤC ĐỊA CHÍNH

| ID | Yêu cầu |
|---|---|
| GEO-01 | Danh mục quốc gia |
| GEO-02 | Danh mục Tỉnh/TP |
| GEO-03 | Danh mục Quận/Huyện |
| GEO-04 | Danh mục Phường/Xã |
| GEO-05 | Danh mục Đường phố/Thôn |

### 3.7 DANH MỤC KHO HÀNG, NHÀ CUNG CẤP

| ID | Yêu cầu |
|---|---|
| WHC-01 | Danh mục kho hàng |
| WHC-02 | Danh mục nhà cung cấp |

Chi tiết hóa từ sheet MENU — kho phân cấp 5 tầng: **Kho hàng → Nhà kho → Dãy → Khoang → Tầng**. Nhóm khách hàng, nhóm NCC.

### 3.8 DANH MỤC NHÂN SỰ (từ sheet MENU)

Danh mục công ty → phòng ban → bộ phận → tổ → nhân viên; chuyên ngành; chức vụ; danh sách người dùng.

### 3.9 PHÂN HỆ MUA HÀNG

| ID | Yêu cầu | Ghi chú |
|---|---|---|
| PUR-01 | Quản lý giá nhập | |
| PUR-02 | Quản lý giá bán | |
| PUR-03 | Phiếu mua hàng | |
| PUR-04 | Trả hàng NCC | **6 kiểu trả hàng** |

Từ sheet MENU: Phiếu nhập mua hàng, Phiếu nhập hàng bán bị trả lại, Phiếu nhập chi phí mua hàng, Phiếu nhập khẩu.

### 3.10 PHÂN HỆ KINH DOANH — 🔴 **Open**

| ID | Yêu cầu | Trạng thái | Ghi chú |
|---|---|---|---|
| SAL-01 | Báo giá | Open | |
| SAL-02 | Danh sách báo giá | Open | |
| SAL-03 | Danh sách báo giá của phòng | Open | |
| SAL-04 | Tạo báo giá | Open | *"các trạng thái báo giá, không cần làm hợp đồng"* |
| SAL-05 | Hợp đồng | Open | |
| SAL-06 | Quản lý khách hàng | Open | |
| SAL-07 | Danh sách khách hàng | Open | |
| SAL-08 | Công nợ khách hàng | Open | |
| SAL-09 | Báo cáo — Kế hoạch | Open | |
| SAL-10 | Báo cáo kinh doanh | Open | |
| SAL-11 | Báo cáo nợ xấu | Open | |
| SAL-12 | Doanh thu theo nhân viên | Open | |
| SAL-13 | Doanh thu theo khách hàng | Open | |

### 3.11 PHÂN HỆ KHO — 🔴 **Open**

| ID | Yêu cầu | Trạng thái |
|---|---|---|
| WH-01 | Quản lý xuất — nhập | Open |
| WH-02 | Phiếu nhập kho | Open |
| WH-03 | Phiếu nhập kho trả lại | Open |
| WH-04 | Phiếu xuất kho trả lại NCC | Open |
| WH-05 | Phiếu điều chuyển kho | Open |
| WH-06 | Quản lý hàng hóa | Open |
| WH-07 | Kiểm kê | Open |
| WH-08 | Thẻ kho | Open |
| WH-09 | Tổng hợp xuất nhập tồn | Open |
| WH-10 | Báo cáo kho | Open |
| WH-11 | Báo cáo tồn kho | Open |
| WH-12 | Báo cáo hàng tồn lâu | Open |

Bổ sung từ MENU: Báo cáo tồn theo nhiều kho, Biểu đồ biến động hàng hóa.

### 3.12 PHÂN HỆ CHĂM SÓC KHÁCH HÀNG — 🔴 **Open**

| ID | Yêu cầu | Trạng thái |
|---|---|---|
| CS-01 | Chăm sóc khách hàng | Open |
| CS-02 | Danh sách khách hàng | Open |
| CS-03 | Lịch bảo hành, bảo trì | Open |
| CS-04 | Báo cáo — Thống kê | Open |

Bổ sung từ MENU: Chat, Lịch sử chat, Tin nhắn chat, Nhóm khách hàng, Khách hàng liên hệ, Đăng ký bản tin, Bình luận.

### 3.13 PHÂN HỆ KẾ TOÁN (có trong sheet Help + MENU + Biểu mẫu, **thiếu trong sheet Tracking**)

> ⚠️ **Khoảng trống nghiêm trọng**: sheet Help liệt kê "PHÂN HỆ KẾ TOÁN" là 1 trong 6 đầu việc chính, nhưng sheet Tracking **không có dòng nào** cho phân hệ này.

**Quản lý quỹ:** Phiếu thu, Phiếu chi, Giấy báo có, Giấy báo nợ, Phiếu kế toán, Phiếu điều chuyển tiền, Phiếu tạm ứng.

**Sổ quỹ:** Sổ quỹ, Biểu đồ biến động quỹ.

**Công nợ:** Sổ chi tiết công nợ 1 khách hàng, Sổ chi tiết công nợ theo BPKD, Bảng tổng hợp số dư công nợ, Bảng cân đối phát sinh công nợ.

**Sổ sách kế toán:** Sổ chi tiết của một tài khoản, Sổ tổng hợp chữ T của một tài khoản, Sổ nhật ký chung.

**Danh mục kế toán:** Danh mục tài khoản, Danh mục mã vụ việc, Danh mục mã phí.

### 3.14 DANH SÁCH BIỂU MẪU / CHỨNG TỪ (sheet Biểu mẫu)

#### Module Kinh doanh
| STT | Tên phiếu | Mô tả |
|---|---|---|
| 1 | Báo giá | NVKD lập báo giá gửi khách hàng |
| 2 | Hợp đồng | NVKD lập hợp đồng để ký kết với khách hàng |
| 3 | Thanh lý hợp đồng | Sử dụng sau khi hoàn thành hợp đồng |
| 4 | Kế hoạch kinh doanh | Dùng để lập kế hoạch kinh doanh |
| 5 | Biên bản giao nhận thiết bị | Giao nhận thiết bị cho khách hàng |
| 6 | Phiếu yêu cầu lắp đặt bàn giao | Yêu cầu bộ phận lắp đặt thực hiện lắp đặt, bàn giao |
| 7 | Biên bản bàn giao nghiệm thu | Chứng từ nghiệm thu sau khi lắp đặt xong |
| 8 | Phiếu yêu cầu bảo hành sửa chữa | Lập yêu cầu bảo hành sản phẩm cho khách |
| 9 | Phiếu đề nghị nhập kho trả lại | Đề nghị kế toán lập Phiếu nhập kho trả lại |
| 10 | Phiếu đề nghị xuất kho | Đề nghị kế toán lập phiếu xuất kho |
| 11 | Phiếu đề nghị xuất hóa đơn | Đề nghị kế toán xuất hóa đơn |

#### Module Kế toán
| STT | Tên phiếu | Mô tả |
|---|---|---|
| 1 | Phiếu thu | Xác định số tiền nhập quỹ (thu tiền khách hàng, thu khác) |
| 2 | Phiếu chi | Xác định số tiền xuất quỹ (trả NCC, chi lương, tạm ứng, khác) |
| 3 | Giấy báo có | Ngân hàng gửi khi có tiền về tài khoản |
| 4 | Giấy báo nợ | Ngân hàng gửi khi chuyển tiền đi |
| 5 | Phiếu kế toán | Hạch toán nghiệp vụ không có chứng từ đi kèm |
| 6 | Phiếu đề nghị xuất giữ | Khi NVKD có nhu cầu giữ hàng cho khách |
| 7 | Phiếu mượn hàng | Khi có nhu cầu mượn hàng của kho |
| 8 | Phiếu đề nghị xuất hóa đơn | Khi cần kế toán xuất hóa đơn |

#### Module Kho
| STT | Tên phiếu | Mô tả |
|---|---|---|
| 1 | Phiếu nhập kho | Nhập hàng không liên quan đến công nợ |
| 2 | Phiếu xuất kho | Xuất hàng không liên quan đến doanh thu |
| 3 | Phiếu xuất điều chuyển kho | Điều chuyển hàng giữa các kho |
| 4 | Phiếu nhập mua hàng | Nhập hàng có liên quan đến công nợ NCC |
| 5 | Phiếu nhập khẩu | Khi nhập khẩu hàng |
| 6 | Phiếu xuất trả lại NCC | Trả hàng lại nhà cung cấp |
| 7 | Phiếu nhập chi phí mua hàng | Phát sinh chi phí khi mua hàng |

#### Module CSKH
| STT | Tên phiếu | Mô tả |
|---|---|---|
| 1 | Phiếu yêu cầu bảo hành sửa chữa | Gửi yêu cầu bảo hành tới bộ phận bảo hành |
| 2 | BB giao nhận thiết bị bảo hành sửa chữa | Lưu tình trạng sản phẩm **trước** khi bảo hành |
| 3 | BB giao nhận thiết bị sau bảo hành sửa chữa | Bàn giao lại sản phẩm cho khách **sau** khi bảo hành |

#### Danh sách báo cáo
| Module | Báo cáo | Luồng | Mô tả |
|---|---|---|---|
| Kinh doanh | Báo cáo kinh doanh | NV → TP | |
| Kinh doanh | Báo cáo thị trường | NV → TP | |
| Kinh doanh | Báo cáo nợ xấu | NV → TP | Thống kê khách hàng có công nợ quá hạn, khó đòi |
| Kinh doanh | Báo cáo phòng | Phòng → BGĐ | |
| Kế toán | Sổ quỹ | | Thủ quỹ phản ánh tình hình thu, chi, tồn quỹ tiền mặt VND |
| Kế toán | Sổ chi tiết của 1 tài khoản | | Ghi chép phát sinh nợ/có của 1 tài khoản |
| Kế toán | Sổ tổng hợp chữ T của 1 tài khoản | | Ghi chép phát sinh nợ/có theo sơ đồ chữ T |
| Kế toán | Sổ chi tiết công nợ của 1 khách hàng | | Theo dõi công nợ phải thu/phải trả theo từng KH/NCC |
| Kế toán | Sổ chi tiết công nợ theo BPKD | | Công nợ nhóm theo bộ phận kinh doanh |
| Kế toán | Bảng tổng hợp số dư công nợ | | Số dư công nợ KH/NCC tại 1 thời điểm |

---

## 4. YÊU CẦU CHỨC NĂNG — PHẦN B: NGHIỆP VỤ WEBSITE

> Toàn bộ 36 task dưới đây đều ở trạng thái **Open** (chưa code).

### 4.1 Quản lý sản phẩm (bán thiết bị gara ô tô)

| Task ID | Yêu cầu | Trạng thái |
|---|---|---|
| TASK_76 | Thêm mới, sửa, xóa sản phẩm | Open |
| TASK_77 | Quản lý thư viện hình ảnh sản phẩm theo slide | Open |
| TASK_78 | Cập nhật thông số sản phẩm bằng file Excel theo các trường thông tin | Open |
| TASK_79 | Cho phép lựa chọn thông tin hiển thị tương ứng với phân quyền thành viên (VD: chỉ thành viên mới nhìn được tồn kho sản phẩm) | Open |
| TASK_80 | Tự động nhóm các danh mục khi lựa chọn thông tin (VD: danh mục "Sản phẩm Khuyến mại" lựa chọn các sản phẩm có thông tin ở trường "Giá khuyến mại") | Open |
| TASK_81 | Lựa chọn các phụ kiện hoặc sản phẩm đi kèm (hiển thị kèm sản phẩm chính) | Open |
| TASK_82 | Thêm/sửa/xóa bài viết giới thiệu sản phẩm | Open |
| TASK_83 | Chức năng thêm vào giỏ hàng / báo giá | Open |
| TASK_84 | Bình luận / Đánh giá dưới bài viết sản phẩm | Open |
| TASK_85 | Tải Catalogue chi tiết của sản phẩm | Open |

### 4.2 Quản lý phụ tùng (bán phụ tùng ô tô)

| Task ID | Yêu cầu | Trạng thái |
|---|---|---|
| TASK_86 | Tạo phụ tùng (phụ tùng theo xe) | Open |
| TASK_87 | Chọn xe sẽ lọc ra các phụ tùng tương thích | Open |

### 4.3 Lọc, tìm kiếm

| Task ID | Yêu cầu | Trạng thái |
|---|---|---|
| TASK_90 | Lọc sản phẩm theo thông số kỹ thuật, chức năng, danh mục | Open |
| TASK_91 | Tìm kiếm và gợi ý sản phẩm tìm kiếm (auto-suggest) | Open |
| TASK_92 | Phương pháp lọc: tích chọn các thông tin (checkbox facet) | Open |
| TASK_93 | Tìm kiếm phụ tùng: theo dòng xe, model… và các trường thông tin khác | Open |

### 4.4 Chức năng giỏ hàng

| Task ID | Yêu cầu | Trạng thái |
|---|---|---|
| TASK_94 | Thêm, sửa, xóa khỏi giỏ hàng | Open |
| TASK_95 | Hướng dẫn mua hàng | Open |
| TASK_96 | Chức năng thanh toán | Open |

### 4.5 Cấu hình SEO

| Task ID | Yêu cầu | Trạng thái |
|---|---|---|
| TASK_97 | Tùy chỉnh tiêu đề website (thẻ title) | Open |
| TASK_98 | Tùy chỉnh URL theo ý | Open |
| TASK_99 | Tùy chỉnh thẻ meta description | Open |
| TASK_100 | Tùy chỉnh thẻ meta keyword | Open |
| TASK_101 | Cấu hình URL redirect, thẻ SEO canonical | Open |
| TASK_102 | Thêm các thẻ heading một cách linh động | Open |
| TASK_103 | Tự động chèn alt cho ảnh, hỗ trợ SEO hình ảnh | Open |
| TASK_104 | Công cụ đo SEO onpage cho bài viết | Open |

### 4.6 Quản lý menu

| Task ID | Yêu cầu | Trạng thái |
|---|---|---|
| TASK_105 | Quản lý, sắp xếp, sửa nội dung tất cả các menu trên website | Open |
| TASK_106 | Thêm các menu mới | Open |
| TASK_107 | Phân cấp menu (không giới hạn cấp) | Open |
| TASK_108 | Lựa chọn style menu (mega, text…) | Open |

### 4.7 Quản lý truy cập

| Task ID | Yêu cầu | Trạng thái |
|---|---|---|
| TASK_109 | Thống kê truy cập theo từ khóa | Open |
| TASK_110 | Thống kê truy cập theo nguồn (referral) | Open |
| TASK_111 | Thống kê truy cập và lưu nguồn đặt hàng online theo link từng bộ phận | Open |

### 4.8 Hỗ trợ trực tiếp

| Task ID | Yêu cầu | Trạng thái |
|---|---|---|
| TASK_112 | Số điện thoại liên hệ theo từng danh mục sản phẩm hoặc phòng Kinh doanh | Open |
| TASK_113 | Webchat cho từng danh mục sản phẩm hoặc phòng Kinh doanh | Open |

### 4.9 CMS & Dự án (từ sheet MENU — chưa có trong Tracking)

Bảng điều khiển; Danh mục tin / Tin tức; Danh mục video / Video; Danh mục ảnh / Thư viện ảnh; Danh mục dự án / Dự án / Hình ảnh dự án; Bộ lọc; Chính sách giá sản phẩm; Chính sách giá đơn hàng.

---

## 5. CẤU TRÚC MENU HỆ THỐNG (sheet MENU)

Hệ thống có **7 menu cấp cao**:

```
1. WEBSITE      → Bảng điều khiển, CSKH (Chat), Khách hàng, Đơn hàng, Sản phẩm, CMS, Dự án
2. DANH MỤC     → Báo giá, Hợp đồng, Thanh lý HĐ, Chứng từ, Quản lý khách hàng, Báo cáo-Kế hoạch
3. NHẬP LIỆU    → Nhập kho, Xuất kho, Kiểm kê, Báo cáo (Thẻ kho, Tồn kho, Xuất nhập tồn…)
4. BÁO CÁO      → Quản lý giá, Quản lý quỹ, Mua hàng, Bán hàng, Báo cáo (Quỹ, Công nợ, Sổ sách KT)
5. CSKH         → (chăm sóc khách hàng)
6. DANH MỤC     → Hàng hóa, Địa chỉ, Nhân sự, Khách hàng-NCC, Kho, Kế toán
7. HỆ THỐNG     → Quản lý tài khoản, Cấu hình
```

---

## 6. YÊU CẦU DỮ LIỆU

### 6.1 Bảng đã tồn tại (`database/tanphat_php_11_12_2021.sql`, DB `tanphat_php`)

| Bảng | Cột chính | Mục đích |
|---|---|---|
| `users` | id, name, email, password, status, group_id, current_activity, forgot_key, active_key | Tài khoản người dùng |
| `login_token` | id, user_id, token, create_at, client_ip, current_activity | Token đăng nhập + lịch sử |
| `groups` | id, name (seed: Admin, Manager, Staff) | Nhóm quyền |
| `modules` | id, name, link (seed: products, news, users, groups) | Đơn vị phân quyền |
| `permissions` | module_id, group_id, role | Ma trận phân quyền |
| `options` | opt_name, opt_value | Cấu hình hệ thống |

### 6.2 Bảng cần bổ sung (chưa tồn tại)

| Nhóm | Bảng cần có |
|---|---|
| **Danh mục xe** | car_brands, car_lines, car_models, car_years, fuels, colors |
| **Hàng hóa** | products, product_groups, product_categories, brands, origins, manufacturers, units, attributes, attribute_units, product_temp |
| **Phụ tùng** | parts, part_car_compatibility |
| **Địa chính** | countries, provinces, districts, wards, streets |
| **Kho** | warehouses, buildings, rows, compartments, floors, stock, stock_cards |
| **Nhân sự** | companies, departments, divisions, teams, employees, majors, positions |
| **KH/NCC** | customers, customer_groups, suppliers, supplier_groups, contacts, newsletters |
| **Kinh doanh** | quotations, quotation_items, contracts, contract_liquidations, business_plans |
| **Mua hàng** | purchase_orders, purchase_returns, import_costs |
| **Kho (chứng từ)** | goods_receipts, goods_issues, transfers, inventory_checks |
| **Kế toán** | receipts, payments, credit_notes, debit_notes, accounting_vouchers, advances, cash_transfers, accounts, cost_codes, case_codes, ledger_entries |
| **CSKH** | warranty_requests, maintenance_schedules, handover_records, chats, chat_messages |
| **Website** | orders, order_items, carts, comments, news, news_categories, videos, video_categories, images, image_galleries, projects, project_categories, menus, seo_settings, visit_logs |

> ⚠️ Lưu ý: `products` và `news` hiện **chỉ tồn tại như module phân quyền + trang list rỗng** — chưa có bảng dữ liệu.

---

## 7. YÊU CẦU PHI CHỨC NĂNG

| ID | Loại | Yêu cầu |
|---|---|---|
| NFR-01 | **Bảo mật — Session** | Token đăng nhập lưu DB, idle > 15 phút tự hủy; session chỉ giữ id token |
| NFR-02 | **Bảo mật — Chống dùng chung tài khoản** | Ghi `client_ip` + lịch sử token để phát hiện tài khoản dùng chung |
| NFR-03 | **Bảo mật — Phân quyền** | Kiểm tra quyền ở 2 lớp: chặn URL (middleware) và ẩn link trên view |
| NFR-04 | **Kiến trúc** | Tuân thủ MVC; 1 model / 1 bảng; 1 thư mục views / 1 controller |
| NFR-05 | **Cấu hình** | Số bản ghi/trang và dung lượng ảnh upload phải cấu hình được qua UI |
| NFR-06 | **SEO** | Website phải kiểm soát được title, URL, meta, canonical, heading, alt ảnh |
| NFR-07 | **Nhập liệu hàng loạt** | Hỗ trợ cập nhật thông số sản phẩm bằng file Excel |
| NFR-08 | **Đa cấp không giới hạn** | Menu phải phân cấp không giới hạn |

### 7.1 Rủi ro bảo mật cần khắc phục (phát hiện từ code review)

| Mức | Vấn đề | Vị trí |
|---|---|---|
| 🔴 Cao | Mật khẩu hash bằng **MD5** (không salt), hash seed giống nhau | `app/controllers/Auth.php` |
| 🔴 Cao | File session lưu trong thư mục **web-accessible** (`public/logs/session`), chỉ chặn bằng `.htaccess`; 16 file session live đã bị commit vào repo | `public/logs/session/` |
| 🟠 TB | Credential DB **hardcode và commit** vào repo | `config.php` |
| 🟠 TB | `_WEB_URL` hardcode theo máy local | `config.php` |
| 🟡 Thấp | Middleware **quét toàn bộ bảng** `login_token` ở mọi request (không scale) | `app/middlewares/AuthMiddleware.php` |

---

## 8. HIỆN TRẠNG TRIỂN KHAI

### 8.1 Số liệu tracking

**Trạng thái đã được thống nhất về Open cho toàn bộ hạng mục** (quyết định ngày 17/07/2026):

| Total | Open | Coding | Testing | Closed |
|---|---|---|---|---|
| **68** | **68** | 0 | 0 | **0** |

**Lý do reset toàn bộ về Open:** 32 mục trước đây đánh dấu `Closed` (Phân hệ Kinh doanh, Kho, CSKH) **không có mã nguồn tương ứng** trong repo — xem đối chiếu tại mục 8.2. Trạng thái `Closed` do đó không phản ánh thực tế và đã được đưa về `Open` để tránh ngộ nhận về tiến độ.

| Nhóm | Dòng | Số mục | Trạng thái |
|---|---|---|---|
| Phân hệ Kinh doanh | r61–74 | 14 | Open *(trước là Closed)* |
| Phân hệ Kho | r75–87 | 13 | Open *(trước là Closed)* |
| Phân hệ CSKH | r88–92 | 5 | Open *(trước là Closed)* |
| *Cộng phần trước là Closed* | | **32** | |
| Nghiệp vụ Website (TASK_76 → TASK_113) | r95–137 | 36 | Open *(không đổi)* |
| **TỔNG** | | **68** | **Open** |

> 📌 Lưu ý khi đọc số: mỗi phân hệ có **1 dòng tiêu đề cũng mang trạng thái** (r61 "PHÂN HỆ KINH DOANH", r75 "PHÂN HỆ KHO", r88 "CHĂM SÓC KHÁCH HÀNG"), nên số mục ở bảng trên **lớn hơn 1** so với số chức năng thật liệt kê ở mục 3.10–3.12. Số chức năng thật: Kinh doanh 13, Kho 12, CSKH 4.

**Các cảnh báo vẫn còn nguyên giá trị:**
- ⚠️ **~48 dòng (Công việc chung, Tài khoản, Cấu hình, Danh mục xe/hàng hóa/địa chính/kho, Mua hàng) KHÔNG có trạng thái** → không được tính vào Total. Con số 68 **không phản ánh** quy mô thật (thực tế >130 hạng mục).
- ⚠️ **Phân hệ Kế toán hoàn toàn không có trong Tracking** dù được Help liệt kê là 1 trong 6 đầu việc chính, và Biểu mẫu/MENU đặc tả rất chi tiết.
- ⚠️ Cột **Start Date / End Date rỗng toàn bộ** → không có dữ liệu tiến độ.

> **Kết luận về tiến độ**: Sau khi reset, **tiến độ thực tế của dự án là 0/68 hoàn thành**. Phần duy nhất thực sự chạy được là bộ khung framework + Auth/User/Group/Permission — vốn nằm ở ~48 dòng không được track.

### 8.2 Đối chiếu tracking vs. code thực tế

*(Cột "Tracking cũ" giữ lại để làm bằng chứng cho quyết định reset ở mục 8.1)*

| Hạng mục | Tracking cũ | Code thực tế | Trạng thái mới |
|---|---|---|---|
| Đăng nhập/đăng xuất | — | ✅ Có (`Auth.php`) | (không track) |
| Quản lý user | — | ✅ Có (CRUD đầy đủ) | (không track) |
| Quản lý group + phân quyền | — | ✅ Có (`Groups.php`, `permission/(\d+)`) | (không track) |
| Dashboard | — | ✅ Có (khung rỗng) | (không track) |
| Products | Open | ⚠️ Chỉ có route list + view rỗng, **không có bảng DB** | Open |
| News | — | ⚠️ Chỉ có route list + view rỗng, **không có bảng DB** | (không track) |
| Kinh doanh (13 chức năng) | ~~Closed~~ | ❌ **Không tồn tại trong code** | **Open** |
| Kho (12 chức năng) | ~~Closed~~ | ❌ **Không tồn tại trong code** | **Open** |
| CSKH (4 chức năng) | ~~Closed~~ | ❌ **Không tồn tại trong code** | **Open** |
| Kế toán | (thiếu) | ❌ Không tồn tại | (cần bổ sung) |
| Website (36 task) | Open | ❌ Không tồn tại | Open |

> 🔴 **Cơ sở của quyết định reset**: 32 mục đánh dấu **Closed** trong tracking **không có mã nguồn tương ứng** trong repo này. Repo `framework_11_12_2021_fix` chỉ là **bộ khung framework + module Auth/User/Group/Permission**, tương ứng phần "CÔNG VIỆC CHUNG" và "CHỨC NĂNG TÀI KHOẢN". Nếu sau này tìm được codebase khác chứa phần đã làm, cần đối chiếu lại và cập nhật trạng thái theo bằng chứng mã nguồn — **không đánh Closed nếu chưa verify được code**.

---

## 9. BÀI HỌC & NGUYÊN NHÂN ĐỔ VỠ (sheet Help — nguyên văn)

Sheet Help ghi lại đánh giá thất bại của dự án:

| # | Nguyên nhân |
|---|---|
| 1 | Các công việc vẫn còn phát sinh thêm (scope creep) |
| 2 | Không đánh giá được thời gian dành cho dự án, bỏ qua giai đoạn tìm hiểu |
| 3 | Quy trình viết tài liệu không đạt, dành ít thời gian cho quá trình viết tài liệu, khảo sát người dùng |
| 4 | Không có 1 coder đủ sức viết theo toàn bộ hệ thống, không thống nhất mô hình cùng code Tân Phát |
| 5 | Không đáp ứng được cách viết theo yêu cầu của IT, chọn framework để viết dẫn đến **vỡ trận** |
| 6 | Không kiểm soát được code vì không theo mô hình MVC |
| 7 | **Không có 1 BA bao quát toàn bộ dự án**, những nhân sự đang làm chưa đủ chuyên môn |
| 8 | Không đào tạo nhân sự cùng dự án dẫn đến validate không đủ |

### 9.1 Danh sách công việc dự án (sheet Công việc)

| STT | Tên công việc |
|---|---|
| 1 | Lên sơ đồ framework (Laravel) |
| 2 | Sơ đồ request–respond |
| 3 | Viết tài liệu cho dự án (hướng dẫn, SRS…) |
| 4 | Quản lý tài nguyên (code, database…) |
| 5 | Thu thập tài liệu cho dự án |
| 6–7 | Làm sơ đồ luồng các phân hệ phần mềm |
| 8 | Test luồng, fix bug |
| 9 | Bảo trì phần View |
| 10 | Hỗ trợ, đào tạo nội bộ |
| 11 | Bắt tay code |

---

## 10. KHUYẾN NGHỊ

| # | Khuyến nghị | Lý do |
|---|---|---|
| 1 | ✅ **ĐÃ XỬ LÝ** — 32 mục "Closed" đã được reset về Open (mục 8.1). Việc còn lại: tìm codebase thật (nếu có) và chỉ đánh Closed khi verify được mã nguồn | Không có mã nguồn tương ứng trong repo |
| 2 | **Bổ sung Phân hệ Kế toán vào Tracking** | Help liệt kê là đầu việc chính nhưng Tracking bỏ trống hoàn toàn |
| 3 | **Gán trạng thái cho ~60 dòng đang trống** | Total 68 không phản ánh quy mô thật (thực tế >130 hạng mục) |
| 4 | **Điền Start Date / End Date** | Hiện không thể đo tiến độ hay ước lượng |
| 5 | **Cắt phạm vi (re-scope)**: làm ERP nội bộ **hoặc** website trước, không làm song song | Nguyên nhân đổ vỡ #1, #2, #5 |
| 6 | **Bố trí 1 BA chuyên trách** | Nguyên nhân đổ vỡ #7 — được chính team ghi nhận |
| 7 | **Cân nhắc dùng Laravel thật** thay vì framework tự viết | Nguyên nhân đổ vỡ #5, #6; framework tự viết không có test, không có migration, không có ORM |
| 8 | **Khắc phục ngay các lỗi bảo mật mục 7.1** | MD5 password + session file public + credential trong repo |
| 9 | **Ưu tiên thiết kế cây danh mục xe trước** | Là danh mục lõi, mọi thứ khác (phụ tùng, lọc, tìm kiếm) phụ thuộc vào nó |
| 10 | **Thiết kế DB đầy đủ trước khi code tiếp** | Hiện chỉ có 6/~60 bảng cần thiết |

---

## PHỤ LỤC A — Nguồn dữ liệu

| Sheet | Nội dung | Số dòng có dữ liệu |
|---|---|---|
| **Tracking** | Danh sách công việc + trạng thái | 131 |
| **Task** | Gần như rỗng (chỉ "Mua hàng", "Tạo phiếu yêu cầu") | 2 |
| **Rule** | Định nghĩa Open/Closed | 2 |
| **MENU** | Cây menu 7 nhánh × 54 dòng | 48 |
| **Biểu mẫu** | 29 biểu mẫu + 10 báo cáo, theo module | 30 |
| **Công việc** | 11 đầu việc dự án | 12 |
| **Help** | Hướng dẫn + 8 nguyên nhân đổ vỡ | 27 |


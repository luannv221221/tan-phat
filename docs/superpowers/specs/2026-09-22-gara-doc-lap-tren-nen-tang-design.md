# Tân Phát là nền tảng, gara độc lập — phần 1: tách dữ liệu theo gara

Ngày chốt: 22/09/2026

Thay cho các quyết định "các gara thấy tất dữ liệu của nhau" trong
`2026-09-03-danh-muc-tong-nhieu-gara-design.md`. Phần danh mục riêng / giá riêng
của gara trong thiết kế đó vẫn giữ.

## Quan điểm xuyên suốt

```
KHO TỔNG (Tân Phát)
  └─ nhiều GARA            — độc lập, không liên quan nhau
       └─ nhiều KHÁCH HÀNG
            └─ nhiều XE    — biển số, VIN, số máy, hãng / model / năm, phiên bản
                 └─ nhiều PHIẾU — tiếp nhận → báo giá → hoá đơn → bảo hành / bảo trì
```

Mọi màn hình, mọi bảng đều phải khớp chuỗi này. Chỗ nào làm đứt chuỗi (vd. xe
không lập được phiếu, khách nằm hai nơi) là sai thiết kế.

## Vấn đề

Hệ thống đang được làm theo mô hình **chuỗi chi nhánh**: các gara là chi nhánh
của Tân Phát, thấy tất dữ liệu của nhau, khách và xe dùng chung toàn hệ thống,
gara lấy hàng từ "kho tổng" như chuyển kho nội bộ.

Mô hình đúng là **nền tảng**:

- **Tân Phát** là nhà phân phối phụ tùng và là chủ nền tảng. Có kho, có website
  bán hàng, và giữ **kho tổng** — danh mục phụ tùng / dịch vụ gốc có giá.
- **Mỗi gara là một đơn vị độc lập** dùng nền tảng. Khách, xe, phiếu, báo giá,
  hoá đơn, kho, nhân viên, giá bán là của riêng gara đó. Các gara không liên quan
  nhau.
- Gara **chỉ tận dụng kho tổng làm nguồn tham khảo khi lập báo giá**: lấy tên,
  mã, ảnh, xe lắp vừa và giá tham khảo, không phải tự nhập lại.

Thêm vào đó, màn **CSKH › Khách hàng** đang làm đứt chuỗi: khách ở đó lưu trong
bảng tài khoản website, xe lưu ở một bảng riêng chỉ có biển số, hãng / model /
năm gõ tay, màu, km — **không có VIN, số máy**, và **không lập được phiếu tiếp
nhận**. Xe đầy đủ lại nằm ở màn Đối tượng.

## Các quyết định đã chốt

| Câu hỏi | Chốt | Vì sao |
|---|---|---|
| Các gara có thấy dữ liệu của nhau không? | **Không** | Mỗi gara là một doanh nghiệp độc lập |
| Tân Phát xem dữ liệu của gara đến đâu? | **Chỉ phần liên quan**: danh sách gara và tài khoản | Nhà cung cấp đọc được danh sách khách của gara thì gara ngại dùng |
| Tân Phát có tự sửa xe không? | **Có.** Tân Phát cũng là một đơn vị (`TP01`), dữ liệu tách như mọi gara, chỉ có thêm quyền giữ kho tổng và quản trị nền tảng | Dữ liệu đang có giữ nguyên, thuộc Tân Phát |
| Gara dùng kho tổng thế nào? | **Chỉ làm nguồn tham khảo khi lập báo giá.** Gara tick mặt hàng mình bán, đặt giá riêng, thêm hàng riêng | Giữ được màn "Danh mục của gara" đang có; gara khỏi nhập lại tên, mã, ảnh |
| Gara có đặt hàng Tân Phát trên hệ thống không? | **Không.** Mua hàng của Tân Phát thì gara tự lập phiếu nhập vào kho mình, NCC là Tân Phát, như mua của mọi NCC khác | Gara độc lập, kho tổng chỉ để tham khảo |
| Khách hàng và xe lưu ở đâu? | **Một khách, một bảng xe.** Màn Khách hàng dùng chung danh sách khách với Đối tượng, xe đầy đủ, lập được phiếu. Tài khoản đăng nhập website tách ra menu riêng của Tân Phát | Nối liền chuỗi khách → xe → phiếu; một người không nằm hai nơi |
| Chặn dữ liệu bằng cách nào? | **Lớp Model gốc + test dò rò rỉ** | Đọc / sửa / xoá theo ID được chặn chung một chỗ, không phụ thuộc việc nhớ từng controller |
| Số chứng từ đánh thế nào? | **Riêng từng gara** | Mỗi gara là một doanh nghiệp, tự đánh số từ đầu |

## Hai phần, làm lần lượt

1. **Tách dữ liệu theo gara + nối liền Khách hàng → xe** — tài liệu này.
2. **Tân Phát quản trị nền tảng** — tạo gara kèm tài khoản chủ gara trong một
   bước, khoá / mở gara, gara tự sửa thông tin của mình. Thiết kế riêng.

Không làm: gara đặt hàng Tân Phát trên hệ thống, thu phí thuê nền tảng, website
riêng cho từng gara.

## Quy tắc gốc

Mỗi tài khoản thuộc đúng **một** gara. Gara làm việc được xác định theo loại
request, và **chỉ ở một hàm duy nhất**:

| Request | Gara làm việc |
|---|---|
| Trang quản trị | Gara ghi trên tài khoản (`users.garage_id`). Không đọc session, không có ô đổi gara |
| Website bán hàng | Gara chủ nền tảng (`is_master = 1`). Website, giỏ hàng, yêu cầu báo giá, đơn web đều là của Tân Phát |
| Dòng lệnh (migrate, gieo dữ liệu, xuất SQL, test gọi model trực tiếp) | Không chặn |

Quy tắc áp cho **mọi người, kể cả Admin Tân Phát**: dữ liệu nghiệp vụ chỉ thấy
của gara mình.

**Đóng khi thiếu:** trang quản trị mà không xác định được gara (tài khoản chưa
gán gara, gara bị khoá) thì không cho vào; model riêng gara trong tình huống đó
trả về rỗng chứ không trả tất cả.

## Kho tổng

- Là `parts` có `garage_id IS NULL` cùng ảnh, xe lắp vừa, nhóm hàng, thương hiệu,
  đơn vị, xuất xứ, thuộc tính. Chỉ Tân Phát sửa.
- Gara **thấy**: tên, mã, ảnh, xe lắp vừa, giá tham khảo (giá bán lẻ của Tân
  Phát). Gara **không thấy** tồn kho, giá nhập, chứng từ của Tân Phát.
- Form lập báo giá giữ hai nguồn: **"Kho tổng"** và **"Danh mục của gara"** (đổi
  tên từ "Gara hiện tại" vì không còn đổi gara).
  - Nguồn "Danh mục của gara": hàng riêng của gara + mặt hàng kho tổng gara đã
    tick, giá riêng nếu có, không thì giá tham khảo.
  - Nguồn "Kho tổng": toàn bộ kho tổng, giá tham khảo điền sẵn, sửa được.
- Lập hoá đơn thì trừ **kho của gara**. Gara chưa có hàng thì phải nhập kho trước
  (chốt chặn tồn âm hiện có vẫn áp dụng).

## Khách hàng của gara

Một khách hàng lưu **một chỗ**: bảng `partners`, loại khách. Hai màn hình cùng
đọc / sửa bản ghi đó:

- **CSKH › Khách hàng** — màn chính của gara cho khách. Giữ link `customers`
  (nên quyền của nhóm Manager / Staff giữ nguyên), nhưng đọc từ `partners` loại
  khách của gara, không đọc bảng tài khoản website nữa.
  - Danh sách: mã, tên, SĐT, biển số các xe, số phiếu. Tìm theo tên, SĐT, biển
    số, VIN.
  - Thêm / sửa: tên, SĐT (giữ cảnh báo trùng SĐT đang có), email (không bắt
    buộc), tỉnh / phường, địa chỉ, nhóm khách.
  - **Khối xe đầy đủ** — dùng lại bảng `vehicles` và phần nhập xe của màn Đối
    tượng: biển số, VIN, số máy, hãng / model / năm chọn từ danh mục (thiếu thì
    gõ thêm), phiên bản, màu, km. Mỗi xe có số phiếu và nút **Lập phiếu tiếp
    nhận**.
- **Bán hàng › Đối tượng** — giữ nguyên, gồm cả khách và NCC.
- **Tài khoản website** — module mới `tai-khoan-web`, **chỉ Tân Phát**: tài khoản
  khách đăng nhập website (bảng `members`), đặt lại mật khẩu, khoá / mở.
- Bảng `partners` thêm cột `email` (không bắt buộc).
- Thêm khách vãng lai tại gara: tạo đối tượng loại khách của gara đó.

### Chuyển dữ liệu cũ

- Xe trong bảng cũ `member_vehicles` (khảo sát 22/09/2026: 0 xe trên máy local;
  server có thể có) chuyển sang `vehicles`. Tài khoản chủ xe chưa có đối tượng
  liên kết thì tạo đối tượng loại khách từ tên / SĐT / email / địa chỉ, ghi vào
  `members.partner_id`.
- Tài khoản không có email (khách vãng lai lập tại gara, không đăng nhập website
  được) cũng tạo đối tượng loại khách và liên kết như trên, để vẫn tìm thấy ở màn
  Khách hàng. Màn Tài khoản website chỉ liệt kê tài khoản có email.
- Bảng `member_vehicles` để nguyên, không dùng nữa; xoá ở một đợt dọn sau, khi
  đã chắc server không còn gì cần lấy.
- `WarrantyRequestsModel` đang đọc số km từ `member_vehicles` — đổi sang
  `vehicles`.

## Phân loại dữ liệu

### Riêng từng gara — có `garage_id`

| Bảng | Cột `garage_id` | Chặn tự động ở Model gốc |
|---|---|---|
| `partners` (khách hàng, NCC) | thêm | có |
| `customer_groups` | thêm | có |
| `vehicles` | thêm | có |
| `receptions` | đã có | có |
| `quotations` | đã có | có |
| `sales_invoices` | đã có | có |
| `warranty_requests` (bảo hành + bảo trì) | thêm | có |
| `warranty_handovers` | thêm | có |
| `warehouses` | đã có | có |
| `goods_receipts` | thêm | có |
| `goods_issues` | thêm | có |
| `stock_takes` | thêm | có |
| `warehouse_transfers` | thêm | có |
| `garage_part_prices` | đã có | có |
| `users` | đã có | **không** — đăng nhập phải tìm khắp các gara, và Admin Tân Phát xem tài khoản mọi gara. Chặn tay như `Users::phamVi()` đang làm |
| `parts` | đã có (NULL = kho tổng) | **không** — dùng điều kiện riêng `garage_id IS NULL OR garage_id = ?`; gara chỉ sửa / xoá hàng riêng của mình |

### Đi theo cha — không thêm cột

- Dòng hàng của chứng từ (`quotation_items`, `sales_invoice_items`,
  `goods_receipt_items`, `goods_issue_items`, `stock_take_items`,
  `warehouse_transfer_items`) — theo chứng từ cha. Chỉ sửa qua form của chứng từ
  cha, nên kiểm chứng từ cha là đủ.
- Tồn kho, thẻ kho, vị trí kho, giữ hàng (`stocks`, `stock_cards`,
  `warehouse_locations`, `stock_reservations`) — theo kho hoặc chứng từ cha.
  Truy vấn "mọi kho" phải giới hạn trong các kho của gara mình.

### Chỉ Tân Phát — module có cờ `chi_tan_phat`

Nhóm quyền Admin / Manager / Staff dùng chung cho mọi gara, nên quyền của nhóm
không đủ để giấu các màn của Tân Phát (nhóm Manager hiện được xem Đơn hàng web,
Đánh giá, Liên hệ, Quản lý gara). Thêm cột `modules.chi_tan_phat`: module bật cờ
thì chỉ tài khoản thuộc gara chủ nền tảng mới thấy trong menu và mới qua được
`RoleMiddleware`, dù nhóm có quyền.

Bật cờ cho: `attributes`, `banners`, `car-body-types`, `car-brands`,
`car-colors`, `car-fuels`, `car-models`, `car-years`, `chat`,
`contact-messages`, `du-an`, `galleries`, `garages`, `groups`, `menus`,
`modules`, `news`, `news-categories`, `newsletter`, `orders`,
`part-categories`, `product-brands`, `product-manufacturers`,
`product-origins`, `product-units`, `products`, `reviews`, `services`,
`settings`, `thong-ke` (thống kê lượt truy cập website), `tai-khoan-web` (mới).

`customers` **không** bật cờ: từ nay là màn Khách hàng của gara. Dịch vụ và hàng
riêng của gara quản lý ở **Danh mục của gara**.

### Gara đọc chung, không sửa

Kho tổng (xem mục Kho tổng), danh mục xe (hãng / model / năm), tỉnh / phường.

## Chặn kỹ thuật

### Lớp Model gốc (`core/Model.php`)

- Thuộc tính mới `protected $_theoGara = false;`. Model của bảng riêng gara bật
  lên `true`.
- Khi bật:
  - `getList`, `getLimit`, `getFirst`, `updateById`, `deleteById` tự thêm
    `garage_id = <gara làm việc>`. Gara B mở `/edit/<id của gara A>` nhận được
    "không tìm thấy".
  - `addNew` **ghi đè** `garage_id` bằng gara làm việc, bỏ qua giá trị form gửi
    lên.
- Truy vấn viết tay (danh sách có lọc, báo cáo, JOIN) thêm điều kiện từng chỗ,
  qua một hàm dùng chung trả về đoạn SQL + giá trị bind (hoặc `1 = 0` khi đóng).
- Ô gợi ý / tra cứu trả JSON (biển số, xe, khách, model / năm xe theo xe của
  khách) cũng lọc theo gara.

### Kiểm ID tham chiếu khi lưu

Mọi ID gửi lên từ form phải thuộc gara làm việc: khách / đối tượng, xe, phiếu
tiếp nhận, báo giá gốc, kho (cả kho đích khi chuyển kho), nhóm khách, phiếu bảo
hành của biên bản bàn giao. Mặt hàng được phép thuộc kho tổng hoặc hàng riêng
của gara mình. Sai thì báo lỗi form, không lưu.

Không kiểm thì gara B gửi `vehicle_id` của gara A, gắn phiếu vào xe đó rồi bấm
in là đọc được thông tin chủ xe.

### Mã chứng từ và ràng buộc "không trùng"

| Bảng | Hiện tại | Sau |
|---|---|---|
| `partners` | `UNIQUE (code)` | `UNIQUE (garage_id, code)` |
| `warehouses` | `UNIQUE (code)` | `UNIQUE (garage_id, code)` |
| `quotations` | `UNIQUE (quote_no)` | `UNIQUE (garage_id, quote_no)` |
| `sales_invoices` | `UNIQUE (invoice_no)` | `UNIQUE (garage_id, invoice_no)` |
| `receptions` | `UNIQUE (reception_no)` | `UNIQUE (garage_id, reception_no)` |
| `warranty_requests` | `UNIQUE (request_no)` | `UNIQUE (garage_id, request_no)` |
| `warranty_handovers` | `UNIQUE (handover_no)` | `UNIQUE (garage_id, handover_no)` |
| `goods_receipts` | `UNIQUE (receipt_no)` | `UNIQUE (garage_id, receipt_no)` |
| `goods_issues` | `UNIQUE (issue_no)` | `UNIQUE (garage_id, issue_no)` |
| `stock_takes` | `UNIQUE (take_no)` | `UNIQUE (garage_id, take_no)` |
| `warehouse_transfers` | `UNIQUE (transfer_no)` | `UNIQUE (garage_id, transfer_no)` |
| `vehicles` | `UNIQUE (bien_so_chuan)`, `UNIQUE (so_khung)` | `UNIQUE (garage_id, bien_so_chuan)`, `UNIQUE (garage_id, so_khung)` |
| `parts` | `UNIQUE (code)` | `UNIQUE (garage_id, code)` + kiểm bằng PHP (xem dưới) |

- Hàm `nextNo()` của từng model lấy số lớn nhất **trong gara làm việc**.
- **Mã hàng:** không được trùng trong phạm vi "kho tổng + hàng riêng của gara
  đó". MySQL coi các NULL là khác nhau, nên `UNIQUE (garage_id, code)` không chặn
  được hai mặt hàng kho tổng cùng mã, cũng không chặn hàng riêng của gara trùng
  mã kho tổng. Hai trường hợp đó kiểm bằng PHP ở màn Hàng hoá và màn Danh mục
  của gara. Không dùng cột sinh (generated column) vì công cụ dump
  `tools/xuat-csdl.php` sẽ ghi giá trị vào cột sinh và làm hỏng file import.
- `parts.slug` giữ duy nhất toàn hệ thống (đường dẫn website). Hàng riêng của
  gara không lên website; slug sinh kèm mã gara để không đụng nhau.
- Chuyển kho chỉ giữa các kho của cùng một gara.

### Khoá ngoại

Cột `garage_id` ở các bảng riêng gara thành **NOT NULL**, khoá ngoại **ON
DELETE RESTRICT**: gara đã có dữ liệu không xoá được, chỉ khoá được. Các khoá
đang là `SET NULL` (`fk_wh_garage`, `fk_user_garage`, `fk_quote_garage`,
`fk_inv_garage`, khoá của `receptions`) đổi sang `RESTRICT`. Giữ nguyên
`garage_part_prices` (CASCADE) và `parts` (RESTRICT).

Lý do: để `SET NULL` thì xoá gara xong, chứng từ của gara đó nằm lại mà không
thuộc về ai và không ai thấy.

`users.garage_id` vẫn **để NULL được** (khoá ngoại đã đổi sang RESTRICT): tài
khoản chưa gán gara là trạng thái có thật. Tài khoản đó không đăng nhập được
(`AuthMiddleware`), không phải dữ liệu mồ côi.

MySQL 8 không cho `MODIFY` cột đang nằm trong khoá ngoại `ON UPDATE CASCADE`
khi `FOREIGN_KEY_CHECKS = 1` ("Cannot change column … used in a foreign key").
Vì vậy câu đặt NOT NULL chạy lúc tắt kiểm tra khoá ngoại, sau đó bật lại. Làm vậy
an toàn: bước trước đó đã lấp hết NULL, và câu này chỉ đổi cho-phép-NULL, không
đổi kiểu cột.

## Giao diện

- **Đầu trang:** bỏ ô đổi gara, chỉ hiện tên gara của tài khoản.
- **Form lập báo giá:** hai nguồn "Kho tổng" / "Danh mục của gara" như mục Kho
  tổng.
- **Menu trái:** gara không thấy module `chi_tan_phat`. Tân Phát có thêm mục
  **Tài khoản website**.
- **CSKH › Khách hàng:** như mục Khách hàng của gara.
- **Quản lý gara:** thêm mã số thuế, email, logo. Gara đã có dữ liệu thì nút Xoá
  đổi thành Khoá.
- **Phiếu in** (báo giá, hoá đơn, bảo hành, bảo trì, bàn giao): đầu phiếu lấy tên,
  địa chỉ, SĐT, mã số thuế, logo của gara lập phiếu. Gara `TP01` thiếu thông tin
  nào thì lấy từ Cấu hình chung như hiện nay.
- **Nhân viên:** Admin Tân Phát xem tài khoản của mọi gara; Manager gara chỉ thấy
  người của gara mình (đã có). Tạo tài khoản bắt buộc chọn gara.
- **Kho:** kho luôn thuộc gara của người tạo, form không còn ô chọn gara. Chủ gara
  (nhóm Manager) được thêm / sửa / xoá kho và vị trí kho của gara mình. Quyền này
  cấp ở migration khoá lại, **sau** khi đẩy code: form Kho cũ còn ô chọn gara, cấp
  sớm là Manager tạo được kho cho gara khác.
- **Tổng quan:** Tân Phát thấy thẻ đơn hàng website như cũ. Gara khác thấy doanh
  thu hoá đơn đã ghi sổ, hoá đơn chưa ghi sổ và báo giá đã lập **của chính mình**.
  Không thấy doanh thu web của Tân Phát.

## Dữ liệu đang có

- Mọi dòng hiện có gán về `TP01 — Tân Phát` (khảo sát 22/09/2026: toàn bộ báo
  giá, hoá đơn, tồn kho, thẻ kho, phiếu nhập đều ở kho `KHO01` của Tân Phát).
- Hai gara mẫu `DMSG` và `DMDN` giữ lại (đang có 5 hàng riêng, 9 dòng giá riêng),
  đổi tên "Tân Phát Sài Gòn" / "Tân Phát Đà Nẵng" thành "Gara mẫu Sài Gòn" /
  "Gara mẫu Đà Nẵng" để khỏi bị hiểu là chi nhánh. Kho `KHO02` đang thuộc gara
  mẫu Sài Gòn và trống, không ảnh hưởng.
- Khách hàng và xe cũ: như mục Chuyển dữ liệu cũ.

## Deploy

Mỗi bước thi công mang migration riêng của nó, chạy **trước** khi đẩy code của
bước đó (code cũ vẫn chạy được sau migration: cột mới để NULL được, ràng buộc
chỉ nới ra, bảng cũ còn nguyên):

| Bước | Migration |
|---|---|
| 1. Nền | thêm `garage_id` cho 9 bảng, gán dữ liệu cũ (chứng từ kho theo kho, còn lại về Tân Phát), `modules.chi_tan_phat` và bật cờ, cột thông tin gara, đổi tên hai gara mẫu |
| 2. Khách và xe | `partners.email`, module `tai-khoan-web`, chuyển xe / khách cũ sang `vehicles` / `partners`, ràng buộc "không trùng" theo gara cho đối tượng, xe, phiếu tiếp nhận, bảo hành, bàn giao |
| 3. Bán hàng | ràng buộc theo gara cho báo giá, hoá đơn, mã hàng |
| 4. Kho | ràng buộc theo gara cho kho, nhập, xuất, kiểm kê, chuyển kho |
| 5. Khoá lại | chạy **sau** khi đẩy code bước 5: gán về đúng gara những dòng `garage_id` NULL phát sinh trong lúc chờ (chứng từ kho theo kho, còn lại về Tân Phát), đổi các khoá ngoại còn `SET NULL` sang RESTRICT, đặt NOT NULL, cấp quyền kho / vị trí kho cho Manager |

Không đặt NOT NULL sớm hơn: trước bước 5 vẫn còn model chưa tự ghi gara, cột bắt
buộc là form của model đó sập.

`tools/xuat-sql-thay-doi.php` thêm một phần cho mỗi migration bước 1-4; migration
bước 5 vào chế độ `--sau-khi-day-code`. Mọi câu phải chạy lại được nhiều lần
không lỗi.

## Kiểm thử

File mới `tests/CachLyGaraTest.php`, đăng ký vào `tests/run.php`.

- Dựng hai gara `ZZ-A`, `ZZ-B`, mỗi gara một tài khoản tạm `zz-*@local.test` và
  một bộ dữ liệu đủ loại có dấu nhận biết. Dọn sạch khi xong, kể cả khi test chết
  giữa chừng.
- Đăng nhập gara B qua HTTP thật, khẳng định:
  1. Mọi màn danh sách, sửa, in, JSON của nhóm riêng gara **không lộ** dấu của A.
  2. Mở / sửa / xoá / đổi trạng thái bằng ID của A → không tìm thấy, và dữ liệu
     của A **không đổi** (đọc lại từ CSDL).
  3. Lưu chứng từ của B kèm ID khách / xe / phiếu / kho của A → bị từ chối.
  4. Không thấy module `chi_tan_phat` trong menu; gõ thẳng URL cũng bị chặn.
  5. Hai gara cùng có `BG-000001`, cùng biển số `30A-12345` mà không va nhau.
  6. Tài khoản không gán gara, hoặc thuộc gara bị khoá, không vào được trang quản
     trị.
  7. Nguồn "Kho tổng" trả giá tham khảo nhưng không lộ tồn kho / giá nhập của
     Tân Phát.
- **Chuỗi khách → xe → phiếu** (sửa `XeCuaKhachTest`, `ThemKhachHangTest`,
  `XeVaPhieuTest`):
  - Thêm khách ở màn Khách hàng thì thấy ngay ở Đối tượng và ngược lại — cùng một
    bản ghi.
  - Thêm xe ở màn Khách hàng lưu đủ biển số, VIN, số máy, hãng / model / năm từ
    danh mục, phiên bản; lập được phiếu tiếp nhận từ xe đó.
  - Tìm khách theo biển số và theo VIN.
  - Chuyển dữ liệu cũ: xe trong `member_vehicles` sang `vehicles` đúng chủ; tài
    khoản không có email thành khách ở màn Khách hàng; chạy lại migration không
    nhân đôi.
- **Tự bắt chỗ quên:**
  - Bảng nào có cột `garage_id` mà model của nó chưa bật `$_theoGara` (trừ
    `users`, `parts`) → đỏ.
  - Module không có cờ `chi_tan_phat` mà chưa nằm trong danh sách màn test đi
    qua → đỏ. Thêm màn riêng gara mới mà quên đưa vào test là bị bắt.
- **Thử phá:** tắt phần chặn ở lớp Model gốc, bỏ kiểm ID tham chiếu → test phải
  đỏ.
- Website bán hàng (giỏ hàng, yêu cầu báo giá, đặt hàng, đăng nhập tài khoản
  khách) vẫn chạy và ghi dữ liệu vào Tân Phát.
- Bộ test hiện có vẫn xanh. Các test đang dựa vào cách "thấy tất", ô đổi gara
  hoặc màn Khách hàng cũ (`NhieuGaraTest`, `NguonBaoGiaTest`,
  `PhanQuyenNhomTest`, `XeVaPhieuTest`, `XeCuaKhachTest`, `ThemKhachHangTest`…)
  sửa lại cho đúng mô hình mới. Khẳng định nào mô tả hành vi đã bỏ (vd. đổi gara
  bằng session) thì thay bằng khẳng định cho hành vi mới (đổi gara bằng session
  **không** có tác dụng), không xoá trắng.

## Thứ tự thi công

Mỗi bước chạy được và test xong mới sang bước sau. `CachLyGaraTest` viết từ bước
1 và phủ thêm dần theo từng bước.

1. **Nền** — migration bước 1, lớp Model gốc, hàm gara làm việc duy nhất, bỏ ô
   đổi gara, đóng khi thiếu gara, cờ `chi_tan_phat` trong menu và
   `RoleMiddleware`. Chưa bật chặn cho model thật nào — mỗi bước sau bật cho
   nhóm model của nó.
2. **Khách và xe** — màn Khách hàng mới (đọc `partners`, khối xe đầy đủ), màn
   Tài khoản website, chuyển dữ liệu cũ, đối tượng, nhóm khách, xe, phiếu tiếp
   nhận, bảo hành / bảo trì, bàn giao, lịch bảo hành, nhắc bảo trì.
3. **Bán hàng** — báo giá, hoá đơn, hai nguồn Kho tổng / Danh mục của gara,
   danh mục của gara, phiếu in, thông tin gara.
4. **Kho** — kho, tồn kho, tồn kho lâu, biến động tồn, thẻ kho, nhập, xuất,
   kiểm kê, chuyển kho.
5. **Còn lại** — báo cáo bán hàng, báo cáo CSKH, Dashboard, nhân viên,
   migration khoá lại (NOT NULL + RESTRICT).

## Rủi ro đã biết

- Khoảng 28 controller và hơn 200 truy vấn, nhiều truy vấn viết tay có JOIN. Test
  dò rò rỉ là lưới an toàn, không thay được việc đọc kỹ từng truy vấn.
- Website bán hàng đang gọi `QuotationsModel` (giỏ hàng / yêu cầu báo giá),
  `StocksModel` (hiện tồn), và đơn web chuyển sang `SalesInvoicesModel`. Các chỗ
  này phải chạy như Tân Phát — nếu áp "đóng khi thiếu" nhầm sang website thì
  website mất báo giá và tồn kho.
- Đăng nhập dùng `UsersModel` — không được bật chặn tự động cho `users`.
- Màn Khách hàng đổi bảng nguồn (`members` → `partners`): link `customers` giữ
  nguyên nhưng mọi chỗ gọi `Customers` / `MembersModel` phía quản trị phải rà lại,
  kể cả lọc theo nhóm khách và báo cáo CSKH.
- Mã hàng kho tổng (`garage_id` NULL) chỉ được kiểm trùng bằng PHP.
- Deploy có bước chạy **sau** khi đẩy code; quên bước 3 thì cột vẫn để NULL được
  và gara vẫn xoá được, dù dữ liệu không lộ.

## Đã cân nhắc và loại

- **Tân Phát thấy hết dữ liệu của gara** / **thấy số liệu tổng hợp** — gara là
  doanh nghiệp độc lập, không muốn nhà cung cấp đọc danh sách khách.
- **Tân Phát chỉ phân phối, không sửa xe** — phải chuyển hoặc xoá dữ liệu xe /
  bảo hành đang có, và Tân Phát mất màn sửa xe.
- **Gara đặt hàng Tân Phát trên hệ thống** (đơn đặt → Tân Phát xuất → kho gara
  nhận) — không cần: kho tổng chỉ để tham khảo, gara mua của Tân Phát thì lập
  phiếu nhập như mua của NCC khác.
- **Gara tự nhập danh mục riêng từ đầu** — gara phải nhập lại tên, mã, ảnh.
- **Giữ hai loại khách, nâng bảng xe cũ của màn Khách hàng** — xe ở đó vẫn không
  lập được phiếu, và một người có thể nằm ở hai nơi: đứt chuỗi khách → xe →
  phiếu.
- **Sửa tay từng controller, không có lớp chung** — dễ sót ở trang sửa / xoá / in
  theo ID, đúng kiểu lỗ hổng gõ số ID của người khác.
- **Mỗi gara một CSDL** — kho tổng phải đi qua nhiều CSDL, mỗi lần deploy chạy
  migration cho từng gara, hosting thường không cho tự tạo CSDL.
- **Cột sinh `COALESCE(garage_id, 0)` để chặn trùng mã hàng bằng chỉ mục** — công
  cụ dump ghi giá trị vào cột sinh, file import bị hỏng.

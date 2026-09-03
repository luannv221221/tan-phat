# Danh mục tổng cho nhiều gara — thiết kế

Ngày chốt: 03/09/2026

## Vấn đề

Hệ thống hiện phục vụ **một** đơn vị. Sắp tới nhiều gara cùng dùng, và khi lập
báo giá người dùng phải chọn được lấy hàng từ **danh mục tổng** hay từ **danh
mục riêng của gara mình**.

Luồng mong muốn:

```
TẠO BÁO GIÁ → Chọn xe / chủ xe → Chọn nguồn danh mục
                                   ├─ Kho tổng    → Master Catalog
                                   └─ Gara hiện tại → Garage Catalog
                                 → Chọn phụ tùng + dịch vụ → BÁO GIÁ
```

## Các quyết định đã chốt

| Câu hỏi | Chốt | Vì sao |
|---|---|---|
| Nhiều gara dùng chung một hệ thống hay mỗi gara một bản cài? | **Một hệ thống, một CSDL** | Rẻ nhất, không phải mở cổng MySQL ra Internet, không phải lưu mật khẩu CSDL của gara khác |
| Danh mục gara khác danh mục tổng ở chỗ nào? | **Vừa giá riêng, vừa hàng riêng** | Giá công thợ mỗi nơi một khác; gara còn mua hàng ngoài và có dịch vụ đặc thù |
| Gara và kho là một hay hai? | **Gara là đơn vị, kho thuộc gara** | Hai dòng kho đang có đúng là kho của Tân Phát, không phải hai gara. Sau này một gara tách kho vẫn làm được |
| Gara có thấy khách và lịch sử của nhau không? | **Thấy tất** | Kỹ thuật viên cần biết xe đã thay gì, khi nào |
| Xác định "gara hiện tại" kiểu gì? | **Theo nhân viên, cho phép đổi** | Ít thao tác, mà giám đốc đi nhiều chi nhánh vẫn dùng được |

### Hệ quả quan trọng của "thấy tất"

`garage_id` trên báo giá / hoá đơn chỉ để **ghi nhận ai lập và lấy giá nào**,
KHÔNG phải để chặn xem. Không cần lọc quyền theo từng dòng ở bất kỳ màn hình
nào — khối lượng việc nhẹ đi rất nhiều.

Đánh đổi đã được chấp nhận: giám đốc gara Sài Gòn đọc được doanh thu và giá của
gara Hà Nội.

## Hiện trạng (khảo sát 03/09/2026)

- Đã có bảng `warehouses` với `KHO01 — Kho tổng` (`is_default = 1`) và
  `KHO02 — Kho chi nhánh Miền Nam`
- `parts` là **một danh mục dùng chung**, không gắn kho; chỉ `stocks` mới có
  `warehouse_id`
- `quotations` **chưa gắn** với kho/gara nào; chỉ lúc chuyển sang hoá đơn mới
  gọi `WarehousesModel::getDefault()`
- `warehouse_id` đã nằm trong 10 controller (tồn kho, nhập, xuất, kiểm kê, thẻ
  kho…) — nên gara **không** được cài đè lên khái niệm kho
- Picker hàng hoá của form báo giá lấy từ `PartsModel::getForSelect()`, trả về
  toàn bộ `parts` có `status = 1`

## Mô hình dữ liệu

### Bảng mới `garages`

```
id, code, name, address, phone,
is_master   -- 1 = gara tổng (Tân Phát), sở hữu danh mục tổng
status, sort_order, create_at, update_at
```

### Cột thêm vào bảng có sẵn

| Bảng | Cột | Ý nghĩa |
|---|---|---|
| `warehouses` | `garage_id` | Kho thuộc gara nào. Hai kho đang có gán về gara tổng |
| `users` | `garage_id` | Nhân viên thuộc gara nào |
| `quotations` | `garage_id` | Báo giá do gara nào lập |
| `sales_invoices` | `garage_id` | Hoá đơn do gara nào lập |
| `parts` | `garage_id` | **NULL = danh mục tổng**; có giá trị = hàng riêng của gara đó |

`parts.garage_id` để NULL cho hàng tổng chứ không trỏ về gara tổng: toàn bộ dữ
liệu đang có giữ nguyên, không phải cập nhật 18 dòng và không sợ sót.

### Bảng mới `garage_part_prices`

```
garage_id, part_id, price, sale_price, status
UNIQUE (garage_id, part_id)
```

Một dòng mang **hai nghĩa cùng lúc**: "gara này có làm mặt hàng đó" và "với giá
này". Giá bỏ trống thì lấy theo giá tổng. Nhờ vậy không cần thêm bảng thứ hai
chỉ để tick chọn.

### Hai nguồn danh mục định nghĩa ra sao

- **Kho tổng** — `parts` có `garage_id IS NULL`, lấy giá gốc
- **Gara hiện tại** — hàng riêng của gara (`parts.garage_id = X`) **cộng** hàng
  tổng mà gara đã chọn làm (có dòng trong `garage_part_prices`), lấy giá riêng
  nếu có, không thì giá gốc

## Thứ tự thi công

Ba tầng chồng lên nhau, làm lần lượt, mỗi tầng chạy và test được rồi mới sang
tầng sau. Làm cả cụm một lần thì lúc hỏng không biết hỏng ở đâu.

### Tầng 1 — Khái niệm gara

- Migration: bảng `garages`; thêm `garage_id` vào `warehouses`, `users`,
  `quotations`, `sales_invoices`
- `GaragesModel`
- Màn **Quản lý gara** (CRUD), đăng ký vào `modules` + `permissions`, thêm vào
  menu trái — quên bước đăng ký thì màn hình có mà không ai thấy, và
  `RoleMiddleware` cũng không gác được
- Màn Sửa người dùng: chọn gara
- Màn Sửa kho: chọn gara
- Đăng nhập ghi `garage_id` vào session; ô **đổi gara** trên thanh đầu trang
- Dữ liệu đang có: gara tổng `TP01 — Tân Phát` (`is_master = 1`), hai kho và
  năm người dùng hiện tại gán hết về gara này

### Tầng 2 — Danh mục riêng của gara

- Migration: `parts.garage_id`, bảng `garage_part_prices`
- Màn **Danh mục của gara**: tick chọn hàng từ kho tổng + đặt giá riêng; thêm
  hàng riêng
- `PartsModel` có thêm cách lấy theo nguồn

### Tầng 3 — Chọn nguồn danh mục khi lập báo giá

- Form Lập báo giá: ô chọn nguồn ngay trên bảng dòng hàng
- Đổi nguồn chỉ đổi danh sách gợi ý, các dòng đã chọn giữ nguyên
- Giá điền vào dòng lấy theo nguồn đang chọn
- Báo giá lưu kèm `garage_id`

## Những thứ KHÔNG đổi

- **Trang bán hàng (storefront)** vẫn chạy trên danh mục tổng — một website
  chung cho cả hệ thống, không tách theo gara
- **Tồn kho** vẫn theo `warehouse_id` như cũ; gara chỉ là chủ sở hữu của kho
- **Phân quyền** không đổi: không lọc dữ liệu theo gara (xem hệ quả ở trên)
- Khách hàng, xe, biển số vẫn dùng chung toàn hệ thống

## Kiểm thử

Theo đúng nếp của dự án: mỗi tầng một file test trong `tests/`, đăng ký vào
`tests/run.php`.

Những chỗ dễ hỏng cần có khẳng định riêng:

1. **Dữ liệu cũ không mất chỗ đứng** — sau migration, mọi kho / người dùng /
   báo giá cũ phải thuộc gara tổng, không có dòng nào `garage_id` rỗng
2. **Đổi gara trên header không rò sang phiên khác** — đổi gara là ghi vào
   session của chính người đó
3. **Danh mục gara không nuốt mất danh mục tổng** — gara chưa chọn mặt hàng nào
   thì nguồn "Gara hiện tại" phải rỗng, chứ không phải hiện toàn bộ hàng tổng
4. **Giá riêng đè đúng giá gốc**, và bỏ trống giá riêng thì rơi về giá gốc
5. **Hàng riêng của gara A không lọt sang gara B**
6. **Báo giá cũ (chưa có `garage_id`)** vẫn mở, vẫn in, vẫn chuyển hoá đơn được

## Rủi ro đã biết

- `warehouse_id` nằm trong 10 controller. Tầng 1 **chỉ thêm chủ sở hữu cho
  kho**, không đụng vào các truy vấn tồn kho — nếu thấy mình đang sửa
  `Tonkho.php` / `Thekho.php` là đã đi lệch thiết kế
- Migration tầng 1 đụng cấu trúc 4 bảng đang có dữ liệu thật. Phải xuất kèm SQL
  cho người không chạy `migrate.php` (`tools/xuat-sql-thay-doi.php`), và kiểm
  bằng cách chạy lại file nhiều lần

## Đã cân nhắc và loại

- **Mỗi gara một bản cài, khai IP + user/pass CSDL của gara tổng** (sơ đồ ban
  đầu) — phải mở cổng MySQL ra Internet và lưu mật khẩu CSDL của đơn vị khác
- **Mỗi gara một bản cài, kéo danh mục qua API có khoá** — an toàn hơn hẳn cách
  trên, nhưng vẫn phải làm cơ chế đồng bộ, xử lý xung đột và trạng thái mất
  mạng. Không cần thiết khi một bản cài là đủ
- **Gara chính là kho** (thêm cột vào `warehouses`, không thêm bảng) — rẻ hơn,
  nhưng hai dòng kho đang có sẽ bị hiểu thành hai gara, và một gara muốn có hai
  kho thì bế tắc
- **Nguồn "Gara hiện tại" chỉ là bộ lọc tồn kho** — rẻ nhất nhưng không đáp ứng
  được giá riêng và hàng riêng

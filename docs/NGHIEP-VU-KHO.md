# Nghiệp vụ kho — Tân Phát

Tài liệu mô tả toàn bộ phần kho: dữ liệu nằm ở đâu, tồn được tính thế nào, chứng
từ nào tác động tới tồn, và những chốt an toàn nào đang chặn sai sót.

Viết cho người bàn giao và người tiếp nhận bảo trì. Mọi con số và tên hàm trong
đây đối chiếu trực tiếp với code, không phải mô tả mong muốn.

---

## 1. Hai bảng cốt lõi

Toàn bộ kho dựng trên đúng hai bảng.

### `stocks` — số dư tức thời

Mỗi dòng là tồn của **một mặt hàng tại một kho**.

| Cột | Ý nghĩa |
|---|---|
| `warehouse_id`, `part_id` | khoá nghiệp vụ, mỗi cặp đúng một dòng |
| `quantity` | số lượng đang còn — `DECIMAL(15,3)`, cho phép 2.5 lít dầu |
| `avg_cost` | giá vốn bình quân gia quyền hiện tại |

Đây là bảng bị **ghi đè** liên tục. Nó chỉ trả lời được "bây giờ còn bao nhiêu",
không trả lời được "vì sao còn bấy nhiêu".

### `stock_cards` — sổ thẻ kho

Sổ **chỉ ghi thêm, không bao giờ sửa**. Mỗi lần nhập hoặc xuất sinh đúng một dòng.

| Cột | Ý nghĩa |
|---|---|
| `move_date` | ngày phát sinh (lấy từ ngày trên chứng từ) |
| `doc_type`, `doc_id`, `doc_no` | truy ngược về chứng từ đã sinh ra nó |
| `qty_in` / `qty_out` | nhập / xuất, một trong hai bằng 0 |
| `unit_cost` | đơn giá của lần phát sinh đó |
| `balance_qty`, `balance_value` | **số dư luỹ kế sau** phát sinh này |

`balance_qty` là thứ làm nên giá trị của bảng này: mọi báo cáo cắt theo thời gian
đều đọc từ đây chứ không đọc `stocks`.

> **Quan trọng:** `balance_qty` là số dư luỹ kế **riêng của từng kho**. Một mặt
> hàng nằm ở hai kho thì mỗi kho có dãy số dư riêng. Cộng tổng phải cộng số dư
> của từng kho lại (xem `getBalanceBefore()`), không được lấy thẻ mới nhất rồi
> coi đó là tổng.

Cả hai bảng nằm ở `app/models/StocksModel.php`.

---

## 2. Giá vốn: bình quân gia quyền tức thời

Hệ thống dùng **bình quân gia quyền**, tính lại ngay mỗi lần nhập.

**Khi nhập:**

```
bq_mới = (SL_cũ × bq_cũ + SL_nhập × giá_nhập) / (SL_cũ + SL_nhập)
```

**Khi xuất: bình quân KHÔNG đổi.** Xuất chỉ trừ số lượng và lấy bình quân hiện
tại làm giá vốn cho lần xuất đó.

Ví dụ:

| Thao tác | SL | Đơn giá | Tồn sau | Bình quân sau |
|---|---|---|---|---|
| Nhập | 10 | 100.000 | 10 | 100.000 |
| Nhập | 10 | 140.000 | 20 | **120.000** |
| Xuất | 5 | (giá vốn 120.000) | 15 | 120.000 |

Hệ quả cần nhớ: **không đảo ngược được**. Sau khi nhập lần 2 thì bình quân
120.000 đã trộn hai lô, không tách lại thành 100.000 và 140.000 được nữa. Đây
chính là lý do của chốt an toàn số 4 ở mục 5.

---

## 3. Vòng đời chứng từ

Mọi chứng từ kho đều đi theo đúng một vòng:

```
   Nháp  ──[Ghi sổ]──►  Đã ghi sổ
 (status 0)               (status 1)
     ▲                        │
     └──────[Huỷ ghi sổ]──────┘
     │
   [Xoá]  ← chỉ xoá được khi còn nháp
```

**Nháp** — sửa thoải mái, xoá thoải mái. Chưa đụng gì tới tồn kho.

**Ghi sổ** — thời điểm duy nhất tồn kho thay đổi. Toàn bộ việc ghi nằm trong
**một transaction**: tồn và thẻ kho hoặc cùng thành công, hoặc cùng không có gì.

**Huỷ ghi sổ** — đảo lại: xoá thẻ kho của chứng từ đó rồi khôi phục tồn về số dư
của thẻ liền trước (`reverseDoc()`).

**Đã ghi sổ thì không xoá được.** Phải huỷ ghi sổ trước.

---

## 4. Năm loại chứng từ tác động tồn

`doc_type` trên thẻ kho cho biết ai đã sinh ra phát sinh đó:

| `doc_type` | Chứng từ | Tác động | Màn hình |
|---|---|---|---|
| `receipt` | Phiếu nhập kho | **+** tồn, cập nhật bình quân | Kho › Phiếu nhập |
| `issue` | Phiếu xuất kho | **−** tồn, chốt giá vốn | Kho › Phiếu xuất |
| `transfer_out` / `transfer_in` | Phiếu chuyển kho | **−** kho nguồn, **+** kho đích | Kho › Chuyển kho |
| `stock_take` | Phiếu kiểm kê | **±** theo chênh lệch | Kho › Kiểm kê |
| `sale_invoice` | Hoá đơn bán | **−** tồn, chốt giá vốn | Bán hàng › Hoá đơn |
| `order` | Đơn hàng web | **−** tồn khi Hoàn thành | Bán hàng › Đơn hàng |

### 4.1 Phiếu nhập kho

Đường **duy nhất** đưa hàng mới vào kho kèm giá vốn. Ghi sổ gọi `applyIn()` cho
từng dòng — cộng tồn và kéo lại bình quân.

Có ô chọn **vị trí trong kho** (`warehouse_locations`) để ghi hàng nằm kệ nào.
Đây là thông tin tra cứu, không tham gia vào tính tồn.

### 4.2 Phiếu xuất kho

Xuất cho mục đích nội bộ (hỏng, dùng nội bộ, xuất huỷ). Ghi sổ gọi `applyOut()`
— trừ tồn và **ghi lại giá vốn** của lần xuất đó vào dòng phiếu.

Trước khi ghi, controller gộp số lượng theo mã hàng rồi mới so tồn. Gộp là bắt
buộc: phiếu có 2 dòng cùng mã, mỗi dòng 10, tồn 15 — so từng dòng thì cả hai
đều lọt rồi tồn tụt xuống âm.

### 4.3 Phiếu chuyển kho

Một phiếu sinh **hai** thẻ kho cho mỗi mặt hàng: `transfer_out` ở kho nguồn và
`transfer_in` ở kho đích.

Giá nhập vào kho đích **chính là giá vốn bình quân lấy ra từ kho nguồn**. Nên
chuyển kho không làm thay đổi giá trị tồn của toàn công ty — chỉ dời chỗ.

Vì phiếu đụng vào hai kho nên chốt lùi ngày và chốt "phát sinh cuối" đều phải
kiểm **cả hai** kho.

### 4.4 Phiếu kiểm kê

Người dùng nhập **số lượng thực tế đếm được**. Khi chốt, hệ thống tự so:

```
chênh lệch = thực tế − tồn trên sổ
   > 0  →  thừa  →  applyIn  (nhập thêm phần thừa)
   < 0  →  thiếu →  applyOut (xuất bớt phần thiếu)
   = 0  →  không sinh thẻ kho nào
```

Ô nhập số đếm dùng `soDem()` chứ không phải `soLuong()` — **0 là giá trị hợp lệ
và rất quan trọng**: "sổ ghi 5, thực tế không còn cái nào". Ép 0 thành 1 là che
mất khoản thiếu 5.

**Hàng thừa mà kho đó chưa từng có mã này** thì bình quân đang là 0. Nhập vào với
giá 0 sẽ kéo bình quân của cả mã hàng về 0, và mọi lần bán sau đều ghi giá vốn 0
→ lãi ảo. Hệ thống xử lý theo thứ tự:

1. Lấy bình quân của mã đó ở **kho khác** đang còn hàng (`avgCostAnyWarehouse()`,
   bình quân theo lượng chứ không lấy đại một kho).
2. Không kho nào có → vẫn ghi 0 **nhưng báo rõ ra màn hình** danh sách mã cần
   vào sửa lại giá vốn.

Không chặn việc chốt phiếu, nhưng không im lặng.

### 4.5 Hoá đơn bán

Xem mục 6 — hoá đơn có thêm ràng buộc riêng về dịch vụ.

---

## 5. Năm chốt an toàn

Đây là phần đáng đọc nhất. Mỗi chốt sinh ra từ một cách hỏng cụ thể.

### Chốt 1 — Không cho tồn âm

`applyOut()` ném exception nếu tồn sau khi trừ nhỏ hơn 0.

Controller nào cũng đã kiểm tồn trước khi ghi, nhưng phần kiểm đó nằm **ngoài**
transaction: hai người bấm ghi sổ cùng lúc thì cả hai đều thấy đủ tồn rồi cùng
trừ. Chốt này là lưới cuối — ném lỗi để transaction rollback, thay vì để tồn âm
nằm im trong CSDL. Tồn âm không có nghĩa gì về nghiệp vụ và rất khó truy lại.

Dung sai `1e-9` vì số lượng là `DECIMAL(15,3)`, so bằng 0 tuyệt đối sẽ vướng sai
số dấu phẩy động.

### Chốt 2 — Khoá dòng khi ghi sổ

`applyIn`/`applyOut` là **đọc → tính → ghi đè**. Hai phiên chạy song song trên
cùng một (kho, mặt hàng) mà không khoá thì cả hai đọc ra cùng số dư cũ, người ghi
sau đè mất phần của người ghi trước — tồn sai mà không ai biết.

`getRowForUpdate()` dùng `SELECT ... FOR UPDATE` để khoá dòng tới hết transaction.

Ngoài transaction thì `FOR UPDATE` chỉ là câu `SELECT` bình thường, vô hại.

Riêng lúc khách đặt hàng ngoài web, `lockParts()` khoá trước cả loạt mặt hàng rồi
mới đọc lại tồn — hai khách cùng đặt cái cuối cùng thì chỉ một người qua.

### Chốt 3 — Không ghi sổ lùi ngày

`balance_qty` trên thẻ là số dư luỹ kế theo **thứ tự ghi sổ**. Ghi một phiếu đề
ngày cũ hơn phát sinh cuối cùng thì thẻ mới mang ngày cũ nhưng số dư lại tính
sau — mọi báo cáo cắt theo `move_date` (tồn đầu kỳ của thẻ kho, biến động theo
ngày) đọc ra số sai.

Chốt ngày 04/08/2026: **chặn hẳn**, thay vì dựng lại số dư theo ngày.

Chặn hai tầng:

- `kiemLuiNgay()` — controller gọi trước, báo lỗi tử tế kèm tên mặt hàng và ngày
  phát sinh gần nhất.
- `chanLuiNgay()` — engine chặn lần nữa ở đầu `applyIn`/`applyOut`.

> Chốt này **phải đặt ở đầu** `applyIn`/`applyOut`, không được đặt trong
> `addCard()`. `setBalance()` chạy trước `addCard()`, nên ném lỗi ở đó sẽ để lại
> tồn đã sửa mà không có thẻ kho tương ứng. Lỗi này đã từng mắc phải và bị test
> bắt được.

### Chốt 4 — Chỉ huỷ được phát sinh cuối cùng

`isLastMovement()` chỉ cho huỷ ghi sổ khi chứng từ đó là **thẻ kho mới nhất** của
(kho, mặt hàng).

Lý do là bình quân gia quyền không đảo ngược được (mục 2). Nếu sau phiếu này đã
có nhập/xuất khác chen vào, đảo nó ra sẽ để lại một bình quân đã trộn nhầm mà
không cách nào tính lại.

Khi bị chặn, hệ thống nói rõ **mặt hàng nào** đang vướng để người dùng biết phải
huỷ phiếu nào trước.

### Chốt 5 — Dịch vụ không đi qua kho

Mặt hàng có `item_type = 'service'` (thay dầu, rửa xe, công thợ) **không có tồn
kho**. `PartsModel::coKho()` là chỗ duy nhất quyết định điều này.

Thiếu chốt này thì "thay dầu" có tồn luôn bằng 0 và **mọi hoá đơn có dòng dịch
vụ đều bị chặn** ngay ở bước kiểm tồn.

Dịch vụ khi ghi sổ:

- Không trừ tồn, **không sinh thẻ kho** nào.
- Giá vốn ghi **0** — công thợ chưa được theo dõi ở đâu cả, ghi bừa một con số
  vào đây là làm sai lãi gộp. Doanh thu vẫn ghi nhận đầy đủ.

---

## 6. Hoá đơn bán và ràng buộc kho

Hoá đơn bán là chứng từ **vừa bán vừa xuất kho**, nên có thêm luật riêng.

**Kho xuất là bắt buộc — trừ khi hoá đơn toàn dịch vụ.** Cột
`sales_invoices.warehouse_id` để trống được. Luật:

| Nội dung hoá đơn | Kho xuất |
|---|---|
| Có ít nhất một dòng hàng hoá | **bắt buộc chọn** |
| Toàn dịch vụ | để trống |

Giao diện có hai tab **Hàng hoá / Dịch vụ**. Chưa chọn kho thì tab Hàng hoá hiện
lời nhắc và khoá nút "Thêm dòng hàng hoá".

> Giao diện **cố ý không** `disable` các ô của dòng đã nhập. Ô `disabled` thì
> trình duyệt không gửi lên — nhập một dòng hàng rồi bỏ kho đi, form gửi lên 0
> dòng hàng hoá, hoá đơn lưu êm ru và **mất hàng**, mà chốt phía máy chủ cũng
> không kêu vì nó không thấy dòng nào. Nên chỉ khoá nút Thêm, còn dữ liệu giữ
> nguyên để máy chủ chặn và trả form về đầy đủ.

Khi ghi sổ, hoá đơn:

1. Tách dòng theo loại — dịch vụ bỏ qua toàn bộ phần kho.
2. Kiểm tồn cho phần hàng hoá.
3. `applyOut()` từng dòng, **ghi lại giá vốn** vào `unit_cost` / `cost_amount`.
4. Cộng `cost_amount` lên đầu hoá đơn để tính lãi gộp.

Lãi gộp hiển thị trên màn hình hoá đơn đã ghi sổ = `subtotal − cost_amount`.

---

## 7. Đơn hàng web: giữ tồn và trừ tồn

Đơn đặt ngoài website đi một đường khác hẳn, vì lúc khách đặt thì hàng **chưa
rời kho**.

### Giữ tồn (reservation)

Khi khách chốt đơn, hệ thống ghi vào `stock_reservations` — **giữ chỗ, chưa trừ
tồn thật**.

Tồn hiển thị ngoài website là **tồn khả dụng**, không phải tồn sổ:

```
tồn khả dụng = tổng tồn mọi kho − tổng đang giữ
```

(`sellableByPart()`). Nhờ vậy hai khách không cùng mua được cái cuối cùng.

Toàn bộ bước chốt đơn nằm trong một transaction có `lockParts()` — khoá trước,
đọc lại tồn, thiếu thì **không tạo gì cả**.

### Trừ tồn thật

Đơn có 6 trạng thái. Chỉ hai trạng thái đụng tới tồn:

| Trạng thái | Tác động |
|---|---|
| **Hoàn thành** | trừ tồn thật, nhả giữ tồn |
| **Hoàn hàng** | cộng tồn lại |
| Đã huỷ | chỉ nhả giữ tồn |
| Chờ xử lý / Đã xác nhận / Đang giao | không đụng tồn |

Cờ `orders.stock_applied` là **nguồn sự thật** về "đơn này đã trừ kho chưa" —
không suy ra từ trạng thái. Vì trạng thái bấm qua bấm lại được, suy từ trạng thái
là trừ trùng.

Các trường hợp bị chặn:

- **Hoàn hàng khi chưa từng trừ kho** → chặn. Cộng lại là đẻ ra hàng không có thật.
- **Huỷ đơn đã trừ kho** → chặn. Phải chuyển "Hoàn hàng" để cộng tồn lại trước.
- **Đơn đã có hoá đơn đã ghi sổ** → đơn **không** trừ thêm lần nữa; hoá đơn đã
  trừ rồi.
- **Xuất hoá đơn cho đơn đã trừ kho theo trạng thái** → chặn, vì hoá đơn sẽ trừ
  thêm một lần nữa.

Nói gọn: một đơn chỉ trừ kho **đúng một lần**, hoặc qua đường đơn hàng, hoặc qua
đường hoá đơn — không bao giờ cả hai.

Đơn **chỉ toàn dịch vụ** vẫn hoàn thành được bình thường, không cần kho.

---

## 8. Bốn báo cáo

| Báo cáo | Trả lời câu hỏi | Nguồn |
|---|---|---|
| **Tồn kho** | Bây giờ còn gì, ở kho nào, trị giá bao nhiêu | `stocks` |
| **Thẻ kho** | Vì sao mặt hàng này còn bấy nhiêu | `stock_cards` |
| **Hàng tồn lâu** | Hàng nào nằm kho lâu chưa động tới | `stock_cards` |
| **Biến động tồn** | Tồn mặt hàng này lên xuống thế nào theo ngày | `stock_cards` |

**Thẻ kho** mặc định chọn sẵn một kho, và nên giữ như vậy: số dư luỹ kế chỉ đúng
khi khoá đúng một kho.

**Hàng tồn lâu** đếm số ngày từ **phát sinh gần nhất** tới ngày báo cáo, lọc theo
ngưỡng số ngày. Dùng để tìm hàng cần đẩy hoặc giảm giá.

---

## 9. Những gì hệ thống KHÔNG làm

Phần này quan trọng khi bàn giao — để không ai trông đợi nhầm.

**Không có kế toán.** Toàn bộ phần kế toán (tài khoản, bút toán, sổ cái) đã được
gỡ khỏi hệ thống. Kho **chỉ** quản lý số lượng và giá vốn, không sinh bút toán
nào. Trong code còn sót vài hằng số (`INVENTORY_CODE`, `DEFAULT_COUNTER`,
`SHORTAGE`, `SURPLUS`) không được dùng tới — xem mục 10.

**Không quản lý theo lô / hạn dùng.** Giá vốn là bình quân gia quyền, không phải
FIFO, không phải đích danh. Không truy được "cái này thuộc lô nhập ngày nào".

**Không quản lý số serial.**

**Vị trí trong kho chỉ để tra cứu** — ghi được hàng nằm kệ nào, nhưng tồn không
tính theo vị trí.

**Không ghi sổ lùi ngày được.** Đây là lựa chọn có chủ ý (chốt 3), không phải
thiếu sót. Phiếu đề ngày cũ phải nhập theo đúng thứ tự phát sinh.

**Không sửa được chứng từ đã ghi sổ.** Phải huỷ ghi sổ, và chỉ huỷ được nếu là
phát sinh cuối cùng.

---

## 10. Nợ kỹ thuật đã biết

Ghi ra để người sau không mất công truy lại.

**Hằng số kế toán chết.** `Goodsreceipts::INVENTORY_CODE`, `DEFAULT_COUNTER`,
`Goodsissues::DEFAULT_COUNTER`, `Stocktakes::SHORTAGE`, `SURPLUS` không còn chỗ
nào dùng sau khi gỡ kế toán (migration `000048`). Biến `$voucher` truyền ra view
luôn là `null`. Xoá được nhưng chưa xoá.

**Giá vốn công thợ.** Dịch vụ ghi giá vốn 0. Muốn tính lãi gộp thật cho phần dịch
vụ thì phải có chỗ khai chi phí nhân công — hiện chưa có.

---

## 11. Xử lý sự cố thường gặp

**"Tồn không đủ để xuất"** — kiểm bằng Thẻ kho của đúng mặt hàng đó tại đúng kho
đó. Nhớ là hoá đơn/phiếu xuất trừ theo **một kho**, còn tồn ngoài website là tổng
mọi kho trừ phần đang giữ.

**"Không huỷ được: đã có nhập/xuất sau phiếu này"** — đúng thiết kế (chốt 4). Mở
Thẻ kho ra xem những phiếu nào phát sinh sau, huỷ ngược từ mới nhất về.

**"Phiếu đề ngày cũ hơn phát sinh đã có"** — chốt 3. Hoặc sửa ngày phiếu cho
đúng thứ tự, hoặc huỷ ghi sổ các phiếu sau rồi ghi lại theo đúng thứ tự.

**Hàng thừa sau kiểm kê có giá vốn 0** — hệ thống đã báo danh sách mã ngay khi
chốt phiếu. Vào màn hình hàng hoá sửa lại giá, hoặc huỷ chốt và làm phiếu nhập
kho cho đúng giá rồi kiểm kê lại.

**Tồn ngoài web ít hơn tồn trong admin** — đúng, vì ngoài web trừ cả phần đang
giữ cho các đơn chưa xử lý xong. Xem `stock_reservations`.

---

## 12. Bản đồ file

| Việc | File |
|---|---|
| Engine tồn kho | `app/models/StocksModel.php` |
| Giữ tồn đơn web | `app/models/StockReservationsModel.php` |
| Phiếu nhập | `app/controllers/admin/Goodsreceipts.php` |
| Phiếu xuất | `app/controllers/admin/Goodsissues.php` |
| Chuyển kho | `app/controllers/admin/Transfers.php` |
| Kiểm kê | `app/controllers/admin/Stocktakes.php` |
| Hoá đơn bán | `app/controllers/admin/Salesinvoices.php` |
| Đơn hàng web | `app/controllers/admin/Orders.php` |
| Chốt đơn ngoài web | `app/controllers/Cart.php` |
| Phân loại hàng/dịch vụ | `app/models/PartsModel.php` |
| Báo cáo | `Tonkho.php`, `Thekho.php`, `Tonkholau.php`, `Biendongton.php` |

**Test:** `tests/StockGuardTest.php` (các chốt an toàn), `tests/OrderStockTest.php`
(đơn web trừ/cộng kho), `tests/ItemTypeTest.php` (dịch vụ không qua kho).

```bash
php tests/run.php
```

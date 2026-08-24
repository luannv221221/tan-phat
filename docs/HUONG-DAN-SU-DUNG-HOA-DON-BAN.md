# Hướng dẫn sử dụng — Hoá đơn bán

Dành cho người bán hàng và kế toán gara. Màn hình **Bán hàng › Hoá đơn bán**.

Hoá đơn bán là chứng từ **vừa bán vừa xuất kho**: nó ghi nhận doanh thu cho khách
và trừ hàng khỏi kho trong cùng một lần.

---

## Khi nào kho tăng, khi nào kho giảm

Đây là câu hỏi hay gặp nhất. Trả lời ngắn: **tồn kho chỉ đổi đúng hai thời điểm**.

```
                                          ┌── dòng HÀNG HOÁ ──► KHO GIẢM
                                          │                     (chốt giá vốn)
  [Lập hoá đơn] ──Lưu──► [ NHÁP ] ──Ghi sổ──┤
                            ▲             │
                            │             └── dòng DỊCH VỤ ───► kho KHÔNG đổi
                            │                                   (giá vốn = 0)
                            │
                            └──── Huỷ ghi sổ ──► KHO TĂNG LẠI
```

| Thời điểm | Tồn kho |
|---|---|
| Lập hoá đơn, bấm **Lưu** | **không đổi** — mới chỉ là nháp |
| Bấm **Ghi sổ** | **GIẢM** — chỉ phần dòng hàng hoá |
| Bấm **Huỷ ghi sổ** | **TĂNG lại** đúng bằng phần đã trừ |
| Sửa hoá đơn còn nháp | **không đổi** |
| Xoá hoá đơn nháp | **không đổi** |

> **Lưu chưa phải là xong.** Hoá đơn mới lưu vẫn ở trạng thái *Nháp*, hàng vẫn còn
> nguyên trong kho. Phải bấm **Ghi sổ** thì hàng mới thực sự ra khỏi kho.

**Dòng dịch vụ không bao giờ đụng vào kho.** Công thợ, rửa xe, thay dầu — đó là
việc làm chứ không phải hàng, nên ghi sổ không trừ gì cả.

---

## Một đơn hàng chỉ trừ kho MỘT lần

Đơn khách đặt ngoài website có **hai đường** để trừ kho. Hệ thống bắt chọn một,
không cho cả hai.

```
                    ĐƠN HÀNG TỪ WEBSITE
                            │
            ┌───────────────┴───────────────┐
            │                               │
   Đường A: chuyển đơn              Đường B: tạo Hoá đơn
     sang "Hoàn thành"                 rồi bấm Ghi sổ
            │                               │
            ▼                               ▼
       KHO GIẢM                         KHO GIẢM
     (đơn tự trừ)                    (hoá đơn trừ)
            │                               │
            ▼                               ▼
   Không tạo hoá đơn                Đơn chuyển "Hoàn thành"
   kho được nữa                     sẽ KHÔNG trừ thêm
```

Nếu đã đi đường A mà muốn chuyển sang đường B, phải chuyển đơn sang **Hoàn hàng**
để cộng tồn lại trước.

---

## Màn hình danh sách

Lọc theo **Trạng thái** (Tất cả / Nháp / Đã ghi sổ), **Từ ngày**, **Đến ngày**.

Mỗi hoá đơn có nhãn trạng thái:

- **Nháp** — chưa trừ kho, còn sửa và xoá được
- **Đã ghi sổ** — đã trừ kho, muốn sửa phải huỷ ghi sổ

Số hoá đơn tự sinh dạng `HD-000001`, tăng dần.

---

## Lập hoá đơn

### Các bước

1. Bấm **Thêm hoá đơn bán**
2. Chọn **Ngày**
3. Chọn **Kho xuất** — xem luật ở mục dưới
4. Nhập **Thuế GTGT (%)** — mặc định 10
5. Chọn **Khách hàng**, để trống nếu khách lẻ
6. Nhập dòng hàng ở tab **Hàng hoá** và/hoặc tab **Dịch vụ**
7. Bấm **Lưu** → hoá đơn ở trạng thái *Nháp*
8. Kiểm tra lại rồi bấm **Ghi sổ**

### Hai tab: Hàng hoá và Dịch vụ

Dòng hàng chia làm hai tab, con số bên cạnh tên tab là số dòng đã nhập.

| | Tab Hàng hoá | Tab Dịch vụ |
|---|---|---|
| Nội dung | Phụ tùng, thiết bị | Công thợ, rửa xe, thay dầu |
| Trừ kho khi ghi sổ | **Có** | Không |
| Giá vốn | Bình quân gia quyền | 0 |
| Cần chọn kho xuất | **Có** | Không |

Ô chọn hàng của mỗi tab **chỉ liệt kê đúng loại của tab đó**. Mặt hàng đã chọn ở
một dòng sẽ không xuất hiện ở dòng khác — cần nhiều thì tăng số lượng.

Bảng tổng ở chân hiện tách bạch **Tiền hàng hoá** và **Tiền dịch vụ**, rồi mới
cộng chưa thuế, thuế, và tổng thanh toán.

### Luật về Kho xuất

| Hoá đơn có gì | Kho xuất |
|---|---|
| Có ít nhất một dòng hàng hoá | **bắt buộc chọn** |
| Chỉ toàn dịch vụ | để trống — chọn *“Không cần”* |

Chưa chọn kho thì tab Hàng hoá hiện lời nhắc và khoá nút **Thêm dòng hàng hoá**.
Lý do: ghi sổ phải biết trừ ở kho nào.

### Đơn giá

Chọn mặt hàng xong, hệ thống **tự điền đơn giá** theo giá niêm yết hoặc giá khuyến
mãi của mặt hàng đó. Muốn giá riêng thì gõ đè **sau khi** đã chọn xong hàng.

> **Đổi mặt hàng thì giá đổi theo.** Nếu bạn chọn nhầm rồi đổi sang mặt hàng khác,
> đơn giá tự cập nhật theo mặt hàng mới. Đừng để giá cũ của mặt hàng trước.

---

## Ghi sổ — điều gì thực sự xảy ra

Bấm **Ghi sổ**, hệ thống làm lần lượt:

1. Tách dòng hàng hoá ra khỏi dòng dịch vụ
2. Kiểm tồn cho phần hàng hoá — thiếu thì **dừng lại**, không ghi gì cả
3. Trừ tồn từng dòng hàng hoá tại kho đã chọn
4. **Chốt giá vốn** cho từng dòng theo bình quân gia quyền tại đúng thời điểm này
5. Ghi vết lên **Thẻ kho** để sau này truy lại được
6. Chuyển hoá đơn sang trạng thái *Đã ghi sổ*

Sau khi ghi sổ, màn hình hiện thêm **Giá vốn** và **Lãi gộp**:

```
Lãi gộp = Doanh thu chưa thuế − Giá vốn
```

Vì dịch vụ có giá vốn 0 nên hoá đơn toàn dịch vụ sẽ có lãi gộp bằng đúng doanh thu.
Đó là do chi phí công thợ chưa được theo dõi trong hệ thống, không phải lãi thật.

---

## Huỷ ghi sổ

Bấm **Huỷ ghi sổ** — hệ thống **cộng tồn kho trở lại** đúng bằng phần đã trừ, xoá
vết trên thẻ kho, và hoá đơn quay về *Nháp* để sửa.

**Không phải lúc nào cũng huỷ được.** Nếu sau hoá đơn này, mặt hàng đó đã có phiếu
nhập/xuất khác, hệ thống chặn và nói rõ mặt hàng nào đang vướng.

Lý do: giá vốn bình quân trộn theo thứ tự phát sinh, huỷ giữa chừng sẽ làm sai giá
vốn của tất cả các phiếu sau. Cách xử lý là mở **Thẻ kho** xem phiếu nào phát sinh
sau, huỷ ngược từ mới nhất trở về.

---

## In hoá đơn

Trên màn hình hoá đơn có hai nút:

- **In / Lưu PDF** — mở biểu mẫu A4 kèm logo Tân Phát, bấm In rồi chọn máy in
  *“Lưu thành PDF”* nếu muốn ra file PDF
- **Tải Word** — tải file `.doc` mở bằng Word, sửa được

Biểu mẫu in có: logo và thông tin công ty, thông tin khách, bảng hàng tách nhóm
hàng hoá / dịch vụ, **số tiền bằng chữ**, thông tin chuyển khoản và hai ô ký tên.

In được cả khi hoá đơn còn nháp lẫn khi đã ghi sổ.

---

## Hoá đơn điện tử

Hiện ở màn hình hoá đơn **đã ghi sổ**.

1. Nhập **Ký hiệu**, **Mẫu số**, **Số hoá đơn** — hệ thống điền sẵn theo cấu hình
2. Bấm **Phát hành HĐĐT**
3. Bấm **Xuất XML** để lấy file nộp lên phần mềm hoá đơn điện tử

Phát hành nhầm thì bấm **Thu hồi**.

> Hệ thống **không nối trực tiếp** với nhà cung cấp hoá đơn điện tử. Nó chỉ gán số
> và xuất file XML để bạn nộp sang phần mềm HĐĐT.

---

## Từ báo giá sang hoá đơn

Ở màn hình **Báo giá** có nút **Chuyển thành hoá đơn**. Nút này tạo một hoá đơn
**nháp** với đầy đủ dòng hàng, giá và thuế copy từ báo giá sang.

Hoá đơn tạo ra vẫn là nháp — vẫn phải kiểm lại và bấm **Ghi sổ**.

---

## Các thông báo thường gặp

### “Hoá đơn có hàng hoá nên phải chọn kho xuất trước khi ghi sổ”

Hoá đơn có dòng hàng hoá nhưng ô Kho xuất đang để trống.

*Xử lý:* chọn kho xuất. Nếu thực ra hoá đơn chỉ có dịch vụ thì xoá các dòng hàng
hoá đi.

### “Tồn không đủ”

Kho đã chọn không đủ hàng. Hệ thống ghi rõ mặt hàng nào, còn bao nhiêu, cần bao nhiêu.

*Xử lý:* kiểm tra ở **Kho › Tồn kho**, điều chuyển từ kho khác sang, hoặc nhập thêm
hàng, hoặc đổi kho xuất.

### “Không huỷ được: đã có nhập/xuất sau hoá đơn này”

Xem mục *Huỷ ghi sổ* ở trên.

### “Đơn đã trừ kho theo trạng thái Hoàn thành nên không xuất hoá đơn kho được nữa”

Đơn hàng web đó đã đi đường A. Muốn đi đường hoá đơn thì chuyển đơn sang **Hoàn
hàng** để cộng tồn lại trước.

### “Hoá đơn chưa có dòng hàng, không thể ghi sổ”

Hoá đơn trống. Thêm ít nhất một dòng ở tab Hàng hoá hoặc tab Dịch vụ.

---

## Bốn nguyên tắc nên nhớ

**Lưu xong phải Ghi sổ.** Chưa ghi sổ thì hàng vẫn còn trong kho, doanh thu chưa
được tính.

**Dịch vụ không đụng kho.** Hoá đơn toàn dịch vụ thì để trống Kho xuất.

**Sai thì huỷ ghi sổ rồi sửa.** Đừng lập hoá đơn âm hay hoá đơn ngược để bù.

**Một đơn web chỉ trừ kho một lần.** Hoặc qua trạng thái đơn, hoặc qua hoá đơn.

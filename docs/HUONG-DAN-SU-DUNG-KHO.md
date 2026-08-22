# Hướng dẫn sử dụng — Phần Kho

Dành cho thủ kho, kế toán và quản lý gara. Giải thích từng màn hình trong menu
**Kho** dùng để làm gì, khi nào dùng, làm theo các bước nào.

Không cần biết kỹ thuật. Nếu bạn đang tìm tài liệu về cách hệ thống tính toán bên
trong, xem `docs/NGHIEP-VU-KHO.md`.

---

## Điều quan trọng nhất: Nháp và Ghi sổ

Đọc mục này trước, mọi thứ còn lại sẽ dễ hiểu.

Mỗi phiếu kho đều có **hai giai đoạn**:

**Giai đoạn 1 — Nháp.** Bạn vừa lập phiếu xong. Phiếu đã lưu nhưng **tồn kho chưa
thay đổi gì cả**. Giai đoạn này sửa thoải mái, xoá thoải mái.

**Giai đoạn 2 — Đã ghi sổ.** Bạn bấm nút **Ghi sổ**. Lúc này tồn kho mới thực sự
tăng hoặc giảm.

> **Lập phiếu xong chưa phải là xong.** Phải bấm **Ghi sổ** thì hàng mới vào kho
> hoặc ra khỏi kho. Rất nhiều nhầm lẫn kiểu "sao nhập rồi mà tồn không tăng" đều
> vì quên bước này.

Phiếu đã ghi sổ thì **không sửa, không xoá được**. Muốn sửa phải bấm **Huỷ ghi
sổ** trước — hệ thống trả tồn kho về như cũ, phiếu quay lại trạng thái nháp.

Riêng màn hình Kiểm kê, hai nút này tên là **Chốt kiểm kê** và **Huỷ chốt**, ý
nghĩa giống hệt.

---

## Tôi cần làm gì? Dùng màn hình nào?

| Tình huống | Màn hình dùng |
|---|---|
| Mua phụ tùng về nhập kho | **Phiếu nhập kho** |
| Hàng hỏng, mất, dùng nội bộ | **Phiếu xuất kho** |
| Trả hàng lại cho nhà cung cấp | **Phiếu xuất kho** (loại *Xuất trả NCC*) |
| Khách trả hàng đã mua | **Phiếu nhập kho** (loại *Nhập trả lại*) |
| Bán hàng cho khách, có thu tiền | **Hoá đơn bán** (menu Bán hàng) |
| Chuyển hàng từ kho này sang kho khác | **Điều chuyển kho** |
| Đếm hàng thực tế, khớp lại với sổ | **Kiểm kê kho** |
| Xem còn bao nhiêu hàng | **Tồn kho** |
| Xem một mặt hàng đã nhập/xuất những gì | **Thẻ kho** |

> **Bán hàng thì dùng Hoá đơn bán, không dùng Phiếu xuất kho.** Hoá đơn vừa trừ
> kho vừa ghi nhận doanh thu và công nợ khách. Phiếu xuất kho chỉ trừ kho, không
> có doanh thu — dùng cho việc nội bộ.

---

## 1. Phiếu nhập kho

Đưa hàng vào kho. Đây là **cách duy nhất** để hàng mới có mặt trong kho kèm giá
vốn.

### Khi nào dùng

- Mua phụ tùng, thiết bị về nhập kho
- Khách trả lại hàng đã mua
- Nhập hàng từ nguồn khác (biếu tặng, chuyển từ đơn vị khác)

### Các bước

1. Bấm **Thêm phiếu nhập kho**
2. Chọn **Loại phiếu**:
   - *Nhập mua (công nợ NCC)* — mua từ nhà cung cấp, phát sinh công nợ phải trả
   - *Nhập khác* — nguồn khác, không phải mua
   - *Nhập trả lại* — khách trả hàng về
3. Chọn **Ngày** và **Kho nhập**
4. Chọn **Nhà cung cấp** nếu là nhập mua
5. Ghi **Diễn giải** cho dễ tra sau này
6. Thêm từng dòng hàng: chọn hàng hoá, nhập **số lượng** và **đơn giá** nhập
7. Bấm **Lưu** → phiếu ở trạng thái nháp
8. Kiểm tra lại rồi bấm **Ghi sổ** → tồn kho tăng

### Lưu ý

**Đơn giá nhập rất quan trọng.** Hệ thống dùng nó để tính giá vốn bình quân. Nhập
sai giá thì mọi lần bán sau đó đều tính sai lãi.

Mỗi dòng hàng có cột **Vị trí / Ghi chú** để ghi hàng nằm kệ nào. Đây chỉ là thông
tin tra cứu cho dễ tìm hàng, không ảnh hưởng tới số lượng tồn.

**Giá vốn bình quân là gì:** nếu kho đang có 10 cái giá 100.000, bạn nhập thêm 10
cái giá 140.000, thì từ giờ 20 cái đó đều tính giá vốn **120.000** — trung bình
của cả hai lô. Hệ thống không phân biệt cái nào thuộc lô nào.

---

## 2. Phiếu xuất kho

Đưa hàng ra khỏi kho **không qua bán hàng**.

### Khi nào dùng

- Hàng hỏng, vỡ, hết hạn phải huỷ
- Lấy hàng dùng cho nội bộ gara
- Trả hàng lại cho nhà cung cấp
- Mất hàng, thiếu hàng đã xác định nguyên nhân

### Khi nào KHÔNG dùng

**Bán cho khách thì dùng Hoá đơn bán**, không dùng phiếu này. Phiếu xuất kho
không ghi nhận doanh thu, không ghi công nợ khách, không in được hoá đơn cho
khách.

### Các bước

1. Bấm **Thêm phiếu xuất kho**
2. Chọn **Loại phiếu**: *Xuất bán (giá vốn)* / *Xuất khác* / *Xuất trả NCC*
3. Chọn **Ngày** và **Kho xuất**
4. Chọn **Đối tượng** nếu xuất trả nhà cung cấp
5. Ghi **Diễn giải** — nên ghi rõ lý do, ví dụ "vỡ khi bốc dỡ"
6. Thêm từng dòng hàng: chọn hàng hoá và **số lượng**
7. **Lưu** → **Ghi sổ**

### Lưu ý

**Không nhập đơn giá.** Hệ thống tự lấy giá vốn bình quân đang có tại thời điểm
ghi sổ.

**Không xuất quá tồn.** Nếu tồn không đủ, hệ thống báo rõ mặt hàng nào thiếu, còn
bao nhiêu và cần bao nhiêu.

---

## 3. Điều chuyển kho

Chuyển hàng từ kho này sang kho khác. Tổng hàng của gara không đổi, chỉ đổi chỗ.

### Các bước

1. Bấm **Thêm điều chuyển kho**
2. Chọn **Ngày**, **Từ kho (nguồn)** và **Đến kho (đích)**
3. Ghi **Lý do**
4. Thêm từng dòng hàng và **số lượng**
5. **Lưu** → **Ghi sổ**

### Lưu ý

**Giá vốn đi theo hàng.** Hàng sang kho mới vẫn giữ nguyên giá vốn của kho cũ, nên
điều chuyển không làm thay đổi giá trị hàng tồn của gara.

Một phiếu điều chuyển tạo **hai dấu vết** trên thẻ kho: một dòng xuất ở kho nguồn
và một dòng nhập ở kho đích.

Kho nguồn phải đủ hàng, nếu không hệ thống chặn.

---

## 4. Kiểm kê kho

Đếm hàng thực tế trong kho rồi khớp lại với số trên sổ. Nên làm định kỳ — cuối
tháng hoặc cuối quý.

### Các bước

1. Bấm **Thêm kiểm kê kho**
2. Chọn **Ngày** và **Kho kiểm kê**
3. Ghi **Lý do**, ví dụ "Kiểm kê cuối tháng 8"
4. Thêm từng mặt hàng cần kiểm, nhập **số lượng thực tế đếm được**
5. **Lưu** → bấm **Chốt kiểm kê**

Hệ thống tự so số bạn đếm với số trên sổ và điều chỉnh:

| Kết quả | Hệ thống làm gì |
|---|---|
| Đếm được **nhiều hơn** sổ | Nhập thêm phần thừa |
| Đếm được **ít hơn** sổ | Xuất bớt phần thiếu |
| Bằng nhau | Không làm gì |

### Lưu ý quan trọng

**Đếm được 0 thì phải nhập số 0**, đừng bỏ trống dòng. "Sổ ghi 5 mà thực tế không
còn cái nào" là thông tin rất quan trọng — nhập 0 để hệ thống ghi nhận thiếu 5.

**Nhập đúng số đếm được, đừng nhập số chênh lệch.** Hệ thống tự tính chênh lệch.

**Hàng thừa mà kho chưa từng có mã đó:** hệ thống chưa biết giá vốn. Nó sẽ lấy tạm
giá ở kho khác; nếu không kho nào có, nó ghi tạm giá 0 và **báo danh sách mã ra
màn hình**. Thấy thông báo đó thì vào màn hình Hàng hoá sửa lại giá, hoặc huỷ chốt
rồi làm phiếu nhập kho cho đúng giá.

---

## 5. Tồn kho

Xem hiện tại còn những gì, ở kho nào, trị giá bao nhiêu. Chỉ xem, không nhập liệu.

**Bộ lọc:** chọn **Kho**, hoặc gõ tên / mã / mã OEM vào ô **Tìm hàng hoá**.

Dùng khi cần trả lời nhanh: "còn hàng này không", "kho nào còn".

---

## 6. Hàng tồn lâu

Tìm hàng nằm kho lâu chưa động tới — để đẩy bán hoặc giảm giá.

**Số ngày tồn** = khoảng cách từ lần nhập/xuất gần nhất tới ngày báo cáo.

**Bộ lọc:** **Kho**, **Tồn tối thiểu (ngày)** — mặc định 90 ngày, và **Tính đến
ngày**.

Kết quả gộp theo dải tuổi: 0–30 ngày, 31–90, 91–180, 181–365, trên 365 ngày.

Hàng nằm ở dải **trên 365 ngày** là tiền đang chết trong kho — nên xem xét giảm giá
hoặc trả lại nhà cung cấp.

---

## 7. Biến động tồn

Xem tồn của **một mặt hàng** lên xuống thế nào theo từng ngày, dạng biểu đồ.

**Bộ lọc:** chọn **Hàng hoá**, **Kho**, **Từ ngày** và **Đến ngày**.

Dùng khi cần nhìn xu hướng: hàng này bán nhanh hay chậm, có thời điểm nào hết hàng
không.

---

## 8. Thẻ kho

Xem **lịch sử đầy đủ** của một mặt hàng: tồn đầu kỳ, từng lần nhập, từng lần xuất,
và số dư sau mỗi lần.

**Bộ lọc:** chọn **Hàng hoá**, **Kho**, **Từ ngày**, **Đến ngày**.

Đây là màn hình để **truy vết khi thấy số liệu lạ**. Mỗi dòng đều ghi rõ phiếu nào
đã gây ra thay đổi đó, nên tra ngược được ngay.

> **Nên chọn một kho cụ thể.** Số dư luỹ kế chỉ đúng khi xem trong phạm vi một
> kho. Xem gộp nhiều kho thì cột số dư không có ý nghĩa.

---

## 9. Danh mục kho

Khai báo các kho của gara. Thường chỉ cài một lần lúc đầu.

**Các ô:** Mã kho, Tên kho, Địa chỉ, Điện thoại, Thứ tự, **Đặt làm kho mặc định**,
Hoạt động.

> **Kho mặc định rất quan trọng.** Đơn hàng khách đặt ngoài website sẽ trừ vào kho
> mặc định. Nếu chưa đặt kho nào làm mặc định, hệ thống lấy kho đang hoạt động có
> thứ tự đầu tiên.

Kho không dùng nữa thì **tắt Hoạt động**, đừng xoá — xoá đi là mất dấu vết các
phiếu cũ.

---

## 10. Vị trí trong kho

Khai báo kệ, tầng, ô trong từng kho để dễ tìm hàng.

**Các ô:** Kho, **Vị trí cha** (để trống nếu là cấp gốc), Mã vị trí, Tên vị trí,
Thứ tự, Đang hoạt động.

Có thể khai nhiều cấp, ví dụ: *Khu A* → *Kệ A1* → *Tầng 2*.

Vị trí được chọn khi lập **Phiếu nhập kho**. Đây chỉ là thông tin tra cứu — tồn
kho **không** tính theo vị trí, chỉ tính theo kho.

---

## Các thông báo thường gặp

### "Tồn không đủ để xuất"

Kho đó không đủ hàng. Hệ thống ghi rõ mặt hàng nào, còn bao nhiêu, cần bao nhiêu.

*Xử lý:* mở **Tồn kho** kiểm tra lại, hoặc dùng **Điều chuyển kho** chuyển hàng từ
kho khác sang, hoặc nhập thêm hàng.

### "Không huỷ được: đã có nhập/xuất sau phiếu này"

Sau phiếu bạn muốn huỷ, mặt hàng đó đã có phiếu khác phát sinh. Huỷ giữa chừng sẽ
làm sai giá vốn của tất cả các phiếu sau.

*Xử lý:* mở **Thẻ kho** của mặt hàng đó xem những phiếu nào phát sinh sau, huỷ
ngược từ phiếu mới nhất trở về.

### "Phiếu đề ngày cũ hơn phát sinh đã có"

Bạn đang ghi sổ một phiếu đề ngày cũ, trong khi mặt hàng đó đã có phiếu ngày mới
hơn được ghi sổ rồi. Làm vậy sẽ khiến báo cáo theo ngày ra số sai.

*Xử lý:* sửa lại ngày phiếu cho đúng thứ tự thực tế, hoặc huỷ ghi sổ các phiếu sau
rồi ghi lại lần lượt theo đúng thứ tự ngày.

### "Hoá đơn có hàng hoá nên phải chọn kho xuất"

Hoá đơn đang có dòng hàng hoá nhưng chưa chọn kho — hệ thống không biết trừ ở kho
nào.

*Xử lý:* chọn kho xuất. Nếu hoá đơn **chỉ có dịch vụ** (công thợ, rửa xe) thì để
trống kho là đúng.

### Tồn ngoài website ít hơn tồn trong quản trị

Đúng, không phải lỗi. Ngoài website trừ thêm phần hàng **đang giữ** cho các đơn
khách đã đặt nhưng chưa xử lý xong, để hai khách không cùng mua được cái cuối cùng.

---

## Việc nên làm định kỳ

**Hằng ngày**
- Ghi sổ hết các phiếu còn nháp trong ngày
- Xử lý đơn hàng mới từ website

**Hằng tuần**
- Xem **Tồn kho** kiểm tra hàng sắp hết để đặt thêm

**Hằng tháng**
- **Kiểm kê kho** ít nhất một kho chính
- Xem **Hàng tồn lâu** lọc từ 90 ngày, lên phương án xả hàng chậm

---

## Ba nguyên tắc nên nhớ

**Lập phiếu xong phải Ghi sổ.** Chưa ghi sổ thì tồn kho chưa đổi.

**Bán hàng dùng Hoá đơn bán.** Phiếu xuất kho chỉ dành cho việc nội bộ.

**Sai thì huỷ ghi sổ rồi sửa, đừng lập phiếu ngược để bù.** Lập phiếu ngược làm sai
giá vốn bình quân và rối thẻ kho.

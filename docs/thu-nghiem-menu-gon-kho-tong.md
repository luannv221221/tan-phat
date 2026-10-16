# Thử nghiệm: menu gọn cho kho tổng — 16/10/2026

Bản thử theo yêu cầu: *"cái Tân Phát ý nó chỉ có quản lý kho tổng thôi, không
cần mấy cái kiểu bán hàng kế toán quản lý website, và hiện cái quản lý các gara
ra menu ngoài"*.

## Quay lại nguyên trạng — sửa đúng MỘT dòng

Mở `config.php`, đổi `true` thành `false`:

```php
define('_KHO_TONG_MENU_GON', false);
```

Xong. Không cần chạy SQL, không cần migration, không cần đẩy lại CSDL.
Mở lại trang quản trị là thấy menu như cũ.

Muốn bỏ hẳn bản thử thì:

```bash
git revert <mã commit của bản thử>
```

Commit ngay trước bản thử là `549c337` (phần kế toán + bảo hiểm).

## Khi bật thì khác gì

Chỉ đổi trên trang quản trị của **gara tổng (Tân Phát)**. Gara thường không đổi
một li nào — họ vẫn thấy đủ tám nhóm như trước.

| | Trước | Sau |
|---|---|---|
| Tổng quan | ✓ | ✓ |
| **Quản lý gara** | nằm trong *Hệ thống* | **đứng riêng ngoài menu**, ngay dưới Tổng quan |
| Bán hàng | ✓ | ẩn |
| Kế toán | ✓ | ẩn |
| Kho | ✓ | ✓ |
| Hàng hoá | ✓ | ✓ |
| Danh mục xe | ✓ | ✓ |
| CSKH | ✓ | ẩn |
| Quản lý website | ✓ | ẩn |
| Hệ thống | ✓ | ✓ (đã rút *Quản lý gara* ra) |

## Hai điều cần biết trước khi demo

**1. Chỉ ẩn trên menu, không chặn đường dẫn.** Gõ thẳng
`/admin/quotations` thì vẫn vào được màn Báo giá. Đây là lựa chọn có chủ ý: thử
một bố cục menu mà đi gỡ dòng trong bảng `permissions` là đánh đổi sai chiều —
bật menu lại thì quyền đã mất rồi, và không ai nhớ đã gỡ những gì.

Nếu lúc demo cần chặn hẳn thì nói, sẽ làm thêm — vẫn giữ nguyên công tắc này.

**2. Dữ liệu không mất gì.** Không có migration nào đi kèm bản thử. Báo giá,
hoá đơn, phiếu thu chi, tin tức… còn nguyên trong CSDL, chỉ là nút bấm tới
chúng tạm không hiện trên menu của Tân Phát.

## Chỗ đã sửa

| File | Sửa gì |
|---|---|
| `config.php` | thêm hằng `_KHO_TONG_MENU_GON` |
| `app/views/layouts/admin/sidebar.php` | lọc nhóm khi là gara tổng + khối "mục đơn" |
| `tests/MenuGonKhoTongTest.php` | test mới, 35 khẳng định |
| `tests/CachLyGaraTest.php` | một guard cũ cho Tân Phát, nay phụ thuộc công tắc |
| `tests/run.php` | đăng ký test mới |

`tests/CachLyGaraTest.php` trước đây chốt *"Manager của Tân Phát VẪN thấy Đơn
hàng web trên menu"*. Bản thử ẩn nhóm Bán hàng nên khẳng định đó không còn đúng
— đã bọc theo công tắc, **tắt công tắc là guard tự sống lại**, không phải sửa
test lần nữa. Khẳng định "vẫn MỞ ĐƯỢC Đơn hàng web" thì giữ nguyên vô điều
kiện, vì đó mới là cái gác chuyện cách ly gara.

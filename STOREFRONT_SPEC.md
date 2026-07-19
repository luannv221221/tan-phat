# STOREFRONT — WEBSITE CÔNG KHAI (Phần B SRS)

| | |
|---|---|
| **Trạng thái** | ✅ **Nền storefront XONG + verify** (18/07/2026). Mở khoá **TASK_79** (gate tồn kho theo thành viên) + **TASK_92** (lọc facet). |
| **Quyết định đã chốt** | Làm **toàn bộ website** (dựng nền trước, mở rộng dần) · **bảng `members` riêng** · **CSS gọn tự chứa** (nhúng `<style>`, không CDN) |

## Kiến trúc

Bề mặt **công khai** — controller gốc `app/controllers/` (KHÔNG thuộc group `admin/` nên không bị AuthMiddleware/RoleMiddleware chặn). Route công khai ở `routes/web.php` ngoài group admin. Trang chủ `/` = controller mặc định `Home`. Layout `layouts/storefront/master.php` (raw PHP, CSS nhúng, tự tính menu/member/giỏ). Views dùng template engine (`{{ }}`/`{!! !!}`/`@if`/`@foreach` — directive phải ở **cuối dòng**).

## Đã làm (migration 000018 — bảng `members`)

| Trang | URL | Task |
|---|---|---|
| Trang chủ | `/` | — |
| Danh sách sản phẩm + **lọc facet** | `san-pham` | **TASK_92**, 90, 91 |
| Chi tiết sản phẩm | `san-pham/<slug>` | 76, 81 (phụ kiện), 87/93 (xe), thông số |
| Đăng ký / đăng nhập / tài khoản | `thanh-vien/...` | nền **TASK_79** |
| Giỏ hàng → gửi yêu cầu báo giá | `gio-hang`, `gio-hang/gui` | **TASK_83/94** |

- **TASK_79**: tồn kho (tổng qua mọi kho, `StocksModel::totalByPart`) chỉ hiện khi `Session::get('dataMember')`; khách ẩn, hiện link đăng nhập.
- **TASK_92**: facet checkbox — danh mục / thương hiệu / xuất xứ / xe tương thích (model → fitment) / khoảng giá / khuyến mãi; đổi lọc auto-submit. `PartsModel::storefront()` + `storefrontCount()`.
- **Giỏ → báo giá**: giỏ session; "Gửi yêu cầu báo giá" tạo `quotations` trạng thái *sent* trong phân hệ Bán hàng → NVKD xử lý ở `admin/quotations`. Khép vòng web ↔ nội bộ.
- Thành viên: `MembersModel` bcrypt (`password_hash`), tách khỏi `users`/`partners`.

## CMS nội dung (đã làm — migration 000021)

| Màn hình | Admin | Storefront |
|---|---|---|
| Tin tức | `admin/news` + `admin/news-categories` | `tin-tuc`, `tin-tuc/<slug>` (lọc theo danh mục, lượt xem) |
| Dự án / công trình | `admin/du-an` (controller `Projectportfolio`) | `du-an`, `du-an/<slug>` |

- Bảng `news_categories` + `news` (is_published, published_at, view_count) + `projects` (portfolio, client/location/completed).
- Nội dung cho phép HTML (admin tin cậy) — render raw `{!! content !!}`. Nav storefront thêm Tin tức + Dự án.
- ⚠️ Link/module `projects` đã là Mã vụ việc kế toán → portfolio dùng `du-an` + controller `Projectportfolio` (tránh trùng `Projects`).

## Upload ảnh + SEO (đã làm — migration 000023)

- **Upload ảnh**: helper `upload_image()` + `media_url()`; gắn vào tin/dự án (thumbnail), products đã có gallery TASK_77.
- **SEO** (TASK_97–103): `site_settings` (key-value) + màn `admin/settings` (Cấu hình website: tên/slogan/meta/OG image/liên hệ). Storefront layout render động `<title>`, meta description/keywords, canonical, Open Graph + Twitter card. Tin/dự án có `meta_title`/`meta_description` riêng; trang chi tiết SP/tin/dự án truyền `$content['seo']` (description/image/type) → layout ưu tiên, fallback site defaults. Footer/topbar dùng hotline/email/địa chỉ từ cấu hình.

## Đặt hàng online (đã làm — migration 000024)

- **Luồng an toàn**: giỏ hàng → `dat-hang` (checkout: tên/SĐT/địa chỉ/thanh toán) → tạo `orders`+`order_items` → `dat-hang/hoan-tat` (mã đơn + **hướng dẫn chuyển khoản** từ cấu hình, hoặc COD). KHÔNG cổng thẻ thật.
- Thanh toán: **chuyển khoản** / **COD**. Thông tin ngân hàng ở `site_settings` (bank_name/account/holder), sửa ở màn Cấu hình.
- Admin `orders`: danh sách (badge đơn mới) + chi tiết + luồng trạng thái Mới→Xác nhận→Đang giao→Hoàn tất/Huỷ. Nhóm menu "Bán hàng".
- Giỏ vẫn giữ song song nút "Gửi yêu cầu báo giá" (tạo quotation).
- **Nối đơn ↔ hoá đơn (migration 000025)**: đơn có `sales_invoice_id`; admin bấm "Tạo hoá đơn bán" → sinh hoá đơn nháp từ dòng đơn (vat_rate=0, giá web là giá cuối) → ghi sổ hoá đơn **trừ tồn + doanh thu Nợ131/Có511 + giá vốn Nợ632/Có156 (KT-6)**. Khép vòng web → back-office.

## Thư viện ảnh/video + sitemap (đã làm — migration 000026)

- **Thư viện** (`admin/galleries` → `thu-vien`, `thu-vien/<slug>`): album (`galleries`) + ảnh/video (`gallery_items`). Admin: CRUD album + upload nhiều ảnh + thêm video YouTube (helper `youtube_id`) + xoá item. Storefront: lưới album → chi tiết (ảnh lightbox + video nhúng youtube-nocookie). Nav web thêm "Thư viện".
- **sitemap.xml** (`Sitemap` controller): XML động từ trang tĩnh + sản phẩm/tin/dự án/thư viện đã đăng.

## Menu website động (đã làm — migration 000027, TASK_105-108)

- `menus` (cây cha-con 1 cấp: label/url/target/sort/status), seed 6 mục (Trang chủ/Sản phẩm/Khuyến mãi/Dự án/Thư viện/Tin tức). Admin `admin/menus` CRUD (list cây, dropdown chọn cha).
- Storefront nav render động từ `MenusModel::getActiveTree()` (thay nav hardcode), có **dropdown submenu** (CSS hover). Helper `nav_url()`.

## Thống kê truy cập (đã làm — migration 000028, TASK_109-111)

- `visits` (url/referrer/keyword/ip/user_agent/member_id). Log ngay trong layout storefront (`VisitsModel::log`) → chỉ trang khách (không admin/asset/POST).
- Admin `admin/thong-ke`: tổng lượt + khách (IP) theo 7/30/90 ngày · biểu đồ cột lượt/ngày · top trang · nguồn giới thiệu (loại domain nhà) · từ khoá tìm kiếm. Gộp bằng PHP (tránh quirk QueryBuilder).

## Hoãn (các đợt web sau)

**webchat** (TASK_112-113 — cần polling/websocket + widget + inbox admin) · cổng thanh toán thật (thẻ/ví) · giỏ hàng lưu DB · tự trừ tồn ngay khi đặt (hiện admin bấm tạo+ghi sổ hoá đơn).

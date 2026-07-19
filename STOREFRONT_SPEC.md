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

## Hoãn (các đợt web sau)

Quản lý menu (105–108) · thống kê truy cập (109–111) · webchat/hotline (112–113) · video/thư viện ảnh · cổng thanh toán thật (thẻ/ví) · giỏ hàng lưu DB · sitemap.xml · trừ tồn kho khi đặt (hiện đơn chỉ ghi nhận, chưa liên thông kho/hoá đơn).

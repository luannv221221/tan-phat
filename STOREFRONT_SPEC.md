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

## Hoãn (các đợt web sau)

SEO (TASK_97–104) · quản lý menu (105–108) · thống kê truy cập (109–111) · webchat/hotline (112–113) · video/thư viện ảnh · thanh toán online (TASK_96) · giỏ hàng lưu DB · upload ảnh (tin/dự án/SP hiện nhập URL/placeholder).

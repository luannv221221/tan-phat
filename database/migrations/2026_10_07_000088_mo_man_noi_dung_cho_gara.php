<?php
/**
 * BƯỚC 3 (tiếp): MỞ CÁC MÀN NỘI DUNG WEBSITE CHO MỌI GARA.
 *
 * Migration 000087 đã tách nội dung web theo gara ở tầng dữ liệu. Nhưng sáu
 * màn quản lý chúng vẫn mang cờ `chi_tan_phat` — RoleMiddleware chặn thẳng,
 * gara khác mở ra là bị đá về "không có quyền". Tách dữ liệu xong mà không mở
 * màn thì gara có website riêng nhưng không tự viết được gì lên đó.
 *
 * Gỡ cờ cho: tin tức, danh mục tin, banner, menu website, thư viện ảnh, dự án.
 *
 * KHÔNG MỞ TOANG: model của sáu bảng này đã bật `$_theoGara`, nên gara chỉ đọc
 * và sửa được nội dung CỦA CHÍNH MÌNH. Gỡ cờ ở đây không cho ai thấy dữ liệu
 * của ai. Quyền `view`/`add`/`edit`/`delete` vẫn do Nhóm > Phân quyền quyết
 * định như mọi màn khác.
 *
 * VẪN GIỮ cờ cho các màn thật sự của riêng kho tổng: hàng hoá, dịch vụ, danh
 * mục phụ tùng, danh mục xe, đơn hàng web, nhóm quyền, quản lý gara... Những
 * thứ đó dùng chung toàn hệ thống, mở ra là gara sửa được dữ liệu của mọi gara.
 */

use App\core\Migration;

return new class extends Migration {

    const LINKS = ['news', 'news-categories', 'banners', 'menus', 'galleries', 'du-an'];

    public function up(){
        $mo = 0;
        foreach (self::LINKS as $link){
            $m = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($m)){ echo "  Khong co module `$link` — bo qua.\n"; continue; }
            if ((int) $m['chi_tan_phat'] === 0){ echo "  `$link` von da mo.\n"; continue; }
            $this->db->update('modules', ['chi_tan_phat' => 0], '`id` = ?', [(int) $m['id']]);
            echo "  Da mo man `$link` cho moi gara.\n";
            $mo++;
        }
        echo "  Tong cong mo $mo man.\n";
    }

    public function down(){
        foreach (self::LINKS as $link){
            $m = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($m)) continue;
            $this->db->update('modules', ['chi_tan_phat' => 1], '`id` = ?', [(int) $m['id']]);
        }
        echo "  Da khoa lai sau man noi dung cho rieng gara tong.\n";
    }
};

<?php
/**
 * BƯỚC 4 (tiếp): MỞ CÁC MÀN KHÁCH WEB / GIAO DỊCH WEB CHO MỌI GARA.
 *
 * Migration 000089 đã tách dữ liệu theo gara. Nhưng sáu màn quản lý chúng vẫn
 * mang cờ `chi_tan_phat`, nên gara có website riêng mà không xem được ai đăng
 * ký, ai đặt hàng, ai gửi thư liên hệ trên chính trang của mình.
 *
 * Gỡ cờ cho: đơn hàng web, tài khoản website, hộp thư liên hệ, đăng ký bản tin,
 * kiểm duyệt đánh giá, hỗ trợ/chat.
 *
 * Model của sáu bảng này đã bật `$_theoGara` VÀ mọi truy vấn tự viết đã đi qua
 * `bangGara()` (bài học từ bước 3: bật cờ thôi chưa đủ). Nên mở màn ở đây
 * không cho gara nào thấy dữ liệu của gara khác.
 *
 * VẪN GIỮ cờ cho các màn dùng chung toàn hệ thống: hàng hoá, dịch vụ, danh mục
 * phụ tùng, danh mục xe, quản lý gara, nhóm quyền, quản lý module.
 */

use App\core\Migration;

return new class extends Migration {

    const LINKS = ['orders', 'tai-khoan-web', 'contact-messages', 'newsletter', 'reviews', 'chat'];

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
        echo "  Da khoa lai sau man khach web cho rieng gara tong.\n";
    }
};

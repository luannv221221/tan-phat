<?php
/**
 * Cho phép bật/tắt THANH XANH TRÊN CÙNG (topbar) từ admin > Cấu hình website.
 *
 * Giống show_car_filter: gieo sẵn '1', phía đọc coi "thiếu khoá" là ĐANG BẬT
 * để đẩy code lên trước khi chạy migration không làm mất thanh trên web khách.
 *
 * Lưu ý nghiệp vụ: thanh này chứa Đăng ký/Đăng nhập (và Tài khoản/Đăng xuất),
 * mà bảng `menus` không có mục đăng nhập nào. Nên khi tắt, master.php dời link
 * tài khoản xuống hàng logo — nếu không khách sẽ hết lối vào tài khoản.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $ex = $this->db->table('site_settings')->where('skey', '=', 'show_topbar')->first();
        if (empty($ex)){
            $this->db->insert('site_settings', [
                'skey'      => 'show_topbar',
                'svalue'    => '1',
                'update_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down(){
        $this->db->delete('site_settings', '`skey` = ?', ['show_topbar']);
    }
};

<?php
/**
 * Cho phép bật/tắt THANH LỌC XE ở header từ admin > Cấu hình website.
 *
 * Gieo sẵn '1' để trạng thái nằm rõ ràng trong CSDL thay vì ngầm hiểu.
 * Phía đọc vẫn coi "thiếu khoá" là ĐANG BẬT, nên nếu đẩy code lên trước khi
 * chạy migration thì thanh lọc vẫn hiện chứ không biến mất khỏi web khách.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $ex = $this->db->table('site_settings')->where('skey', '=', 'show_car_filter')->first();
        if (empty($ex)){
            $this->db->insert('site_settings', [
                'skey'      => 'show_car_filter',
                'svalue'    => '1',
                'update_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down(){
        $this->db->delete('site_settings', '`skey` = ?', ['show_car_filter']);
    }
};

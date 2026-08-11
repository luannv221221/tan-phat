<?php
/**
 * THÔNG SỐ KỸ THUẬT ÁP THEO TỪNG LOẠI HÀNG — để khách tự thêm trường.
 *
 * Khách hỏi: mỗi loại hàng cần trường khác nhau, có làm động được không để
 * họ thích thì tự thêm.
 *
 * Cơ chế động đã có sẵn: bảng `part_attributes` (Chất liệu, Trọng lượng,
 * Điện áp...) khách tự quản lý trong admin. Thiếu đúng một thứ: thông số nào
 * áp cho loại hàng nào. Nay thêm cột đó.
 *
 * Dùng kiểu SET chứ không phải chuỗi phẩy tự chế: MySQL tự chặn giá trị lạ,
 * và FIND_IN_SET() lọc thẳng trong SQL được.
 *
 * Mặc định cả 3 loại -> thông số đang có giữ nguyên hành vi cũ (hiện ở mọi
 * mặt hàng), không phải sửa tay dòng nào.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $this->run("ALTER TABLE `part_attributes`
                    ADD COLUMN `item_types` SET('part','equipment','service')
                    NOT NULL DEFAULT 'part,equipment,service' AFTER `unit`");

        // Đơn vị tính cho dịch vụ — đang chỉ có Cái/Bộ/Chiếc/Lít/Hộp/Mét,
        // không có gì để chọn cho "một lần thay dầu".
        $now = date('Y-m-d H:i:s');
        $sort = 10;
        foreach (['Lần' => 'lan', 'Giờ' => 'gio', 'Gói' => 'goi'] as $ten => $slug){
            $co = $this->db->table('part_units')->where('slug', '=', $slug)->first();
            if (empty($co)){
                $this->db->insert('part_units', [
                    'name' => $ten, 'slug' => $slug, 'sort_order' => $sort++,
                    'status' => 1, 'create_at' => $now,
                ]);
            }
        }
    }

    public function down(){
        $this->run("ALTER TABLE `part_attributes` DROP COLUMN `item_types`");
        foreach (['lan', 'gio', 'goi'] as $slug){
            $this->db->delete('part_units', '`slug` = ?', [$slug]);
        }
    }
};

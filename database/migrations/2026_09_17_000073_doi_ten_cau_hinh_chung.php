<?php
/**
 * Đổi tên màn "Cấu hình website" thành "Cấu hình chung".
 *
 * Màn này không còn riêng của website: nó giữ hotline, email, địa chỉ, MÃ SỐ
 * THUẾ in lên báo giá / hoá đơn, tài khoản ngân hàng, thông tin bên bán cho
 * hoá đơn điện tử… Gọi là "Cấu hình website" thì người làm gara không nghĩ
 * tới chuyện vào đây sửa mã số thuế trên chứng từ.
 *
 * Chỉ đổi TÊN hiển thị (menu đọc tên từ bảng `modules`). Đường dẫn
 * `admin/settings` và quyền giữ nguyên — không ai mất quyền, link cũ vẫn chạy.
 */

use App\core\Migration;

return new class extends Migration {

    const LINK    = 'settings';
    const TEN_MOI = 'Cấu hình chung';
    const TEN_CU  = 'Cấu hình website';

    public function up(){
        $this->db->update('modules', ['name' => self::TEN_MOI], '`link` = ?', [self::LINK]);
        echo "  Da doi ten man admin/settings thanh \"" . self::TEN_MOI . "\".\n";
    }

    public function down(){
        $this->db->update('modules', ['name' => self::TEN_CU], '`link` = ?', [self::LINK]);
    }
};

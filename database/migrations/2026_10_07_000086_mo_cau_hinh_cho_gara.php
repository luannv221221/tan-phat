<?php
/**
 * BƯỚC 2: MỞ MÀN CẤU HÌNH CHO MỌI GARA.
 *
 * Mỗi gara có website riêng thì phải tự đặt được tên, logo, hotline, địa chỉ,
 * Zalo, Facebook của mình. Nhưng module `settings` đang mang cờ `chi_tan_phat`
 * — RoleMiddleware chặn thẳng, gara khác mở ra là bị đá về "không có quyền".
 *
 * Gỡ cờ đó. Không mở toang: màn Cấu hình làm hai việc khác nhau tuỳ ai mở
 * (xem Settings controller) —
 *   gara tổng  -> sửa `site_settings`, là MẶC ĐỊNH chung cho mọi gara;
 *   gara khác  -> sửa `garage_settings` của chính nó, không đụng được vào
 *                 mặc định chung.
 * Nên gỡ cờ ở đây KHÔNG cho gara quyền sửa dữ liệu của gara khác.
 *
 * Quyền `view`/`edit` trên module này vẫn do Nhóm > Phân quyền quyết định như
 * mọi màn khác; migration này chỉ bỏ lớp chặn "chỉ Tân Phát" nằm đè lên trên.
 */

use App\core\Migration;

return new class extends Migration {

    const LINK = 'settings';

    public function up(){
        $m = $this->db->table('modules')->where('link', '=', self::LINK)->first();
        if (empty($m)){
            echo "  Khong co module `" . self::LINK . "` — bo qua.\n";
            return;
        }
        if ((int) $m['chi_tan_phat'] === 0){
            echo "  Module `" . self::LINK . "` von da mo cho moi gara.\n";
            return;
        }
        $this->db->update('modules', ['chi_tan_phat' => 0], '`id` = ?', [(int) $m['id']]);
        echo "  Da mo man Cau hinh cho moi gara.\n";
    }

    public function down(){
        $m = $this->db->table('modules')->where('link', '=', self::LINK)->first();
        if (empty($m)) return;
        $this->db->update('modules', ['chi_tan_phat' => 1], '`id` = ?', [(int) $m['id']]);
        echo "  Da khoa lai man Cau hinh cho rieng gara tong.\n";
    }
};

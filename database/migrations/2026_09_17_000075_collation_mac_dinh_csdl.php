<?php
/**
 * ĐỔI COLLATION MẶC ĐỊNH của cả CSDL sang utf8mb4_unicode_ci.
 *
 * 000074 đã chuyển hai bảng lệch về utf8mb4_unicode_ci, nhưng MẶC ĐỊNH của
 * CSDL trên máy MySQL 8 vẫn là utf8mb4_0900_ai_ci. Nghĩa là bảng nào tạo sau
 * này mà không ghi collation — kể cả tạo tay trong phpMyAdmin — lại nhận
 * 0900, và lần export -> import lên server (MySQL 5.7 / MariaDB) kế tiếp lại
 * báo #1273 Unknown collation.
 *
 * Chỉ đổi mặc định cho bảng TẠO SAU; không đụng bảng / dữ liệu đang có.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $ten = $this->db->firstRaw("SELECT DATABASE() AS ten");
        if (empty($ten['ten'])) return;
        $this->run("ALTER DATABASE `" . str_replace('`', '', $ten['ten']) . "`
                    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "  Mac dinh CSDL `{$ten['ten']}` -> utf8mb4_unicode_ci.\n";
    }

    /* Không trả ngược về utf8mb4_0900_ai_ci: đó là trạng thái gây lỗi deploy. */
    public function down(){
        echo "  (khong tra nguoc collation mac dinh)\n";
    }
};

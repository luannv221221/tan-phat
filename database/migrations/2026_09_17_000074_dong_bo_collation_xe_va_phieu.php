<?php
/**
 * ĐỒNG BỘ COLLATION cho hai bảng `vehicles` và `receptions`.
 *
 * Migration 000072 tạo hai bảng này chỉ ghi `DEFAULT CHARSET=utf8mb4`, KHÔNG
 * ghi collation — trong khi mọi migration trước đều ghi rõ
 * `COLLATE=utf8mb4_unicode_ci`. MySQL 8 tự gán collation mặc định của nó là
 * `utf8mb4_0900_ai_ci`, nên hai bảng lệch với 64 bảng còn lại.
 *
 * Hậu quả đã xảy ra: export CSDL từ máy local (MySQL 8) mang lên server
 * (MySQL 5.7 / MariaDB) báo `#1273 Unknown collation: 'utf8mb4_0900_ai_ci'`,
 * vì bản cũ không có collation đó.
 * Hậu quả tiềm ẩn: so sánh chuỗi giữa bảng lệch collation (vd. biển số của xe
 * với biển số trên báo giá) báo "Illegal mix of collations".
 *
 * 000072 cũng đã được sửa để ghi collation cho lần cài mới; migration này dành
 * cho các máy đã chạy 000072 bản cũ. Chuyển trên bảng đã đúng collation thì
 * không đổi gì.
 */

use App\core\Migration;

return new class extends Migration {

    const BANG = ['vehicles', 'receptions'];

    public function up(){
        foreach (self::BANG as $b){
            if (!$this->hasTable($b)) continue;
            $this->run("ALTER TABLE `$b` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "  Da chuyen `$b` sang utf8mb4_unicode_ci.\n";
        }
    }

    /* Không trả ngược về utf8mb4_0900_ai_ci: đó chính là trạng thái lỗi, và
       collation đó không tồn tại trên MySQL 5.7 / MariaDB. */
    public function down(){
        echo "  (khong tra nguoc collation loi)\n";
    }
};

<?php
/**
 * BƯỚC 1 CỦA NỀN TẢNG NHIỀU GARA: NHẬN GARA THEO TÊN MIỀN.
 *
 * Trước migration này, gara làm việc được suy ra từ TÀI KHOẢN đang đăng nhập
 * (trang quản trị), còn trang người dùng thì luôn là gara tổng. Nghĩa là cả hệ
 * thống chỉ có MỘT website — gara không có trang của riêng mình.
 *
 * Nay mỗi gara có tên miền riêng, và tên miền quyết định đang phục vụ gara nào:
 *
 *     tp01.etek.rikkeiedu.org  -> Tân Phát
 *     dmsg.etek.rikkeiedu.org  -> Gara mẫu Sài Gòn
 *
 * VÌ SAO LÀ MỘT BẢNG RIÊNG CHỨ KHÔNG PHẢI MỘT CỘT `garages`.`domain`
 * Một gara cần NHIỀU tên miền cùng trỏ về: có www và không www, tên miền phụ
 * của hệ thống lẫn tên miền riêng khách tự mua, cộng thêm host dùng khi chạy
 * thử. Nhét vào một cột là tới lúc đó phải sửa cấu trúc lại.
 *
 * `host` DUY NHẤT TOÀN BẢNG — không phải duy nhất trong một gara. Hai gara
 * cùng khai một tên miền thì không có cách nào biết request thuộc về ai; chặn
 * ngay ở CSDL chứ đừng để sinh ra rồi mới phát hiện.
 *
 * KHÔNG khai host của máy chạy thử (localhost) vào đây — cố ý. Host nào không
 * khớp dòng nào thì hệ thống giữ NGUYÊN cách cũ (quản trị: gara của tài khoản;
 * web: gara tổng). Nhờ vậy:
 *   - máy chạy thử và bộ test không phải sửa gì;
 *   - đẩy lên máy chủ trước khi trỏ DNS xong cũng KHÔNG sập — chừng nào bảng
 *     này còn rỗng thì mọi thứ chạy y như trước.
 */

use App\core\Migration;

return new class extends Migration {

    /** Tên miền gốc của hệ thống — gara mới lấy mã gara làm tên miền phụ. */
    const TEN_MIEN_GOC = 'etek.rikkeiedu.org';

    public function up(){
        $this->run("CREATE TABLE IF NOT EXISTS `garage_domains` (
            `id`         INT NOT NULL AUTO_INCREMENT,
            `garage_id`  INT NOT NULL,
            `host`       VARCHAR(190) NOT NULL,
            `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
            `status`     TINYINT(1) NOT NULL DEFAULT 1,
            `create_at`  DATETIME DEFAULT NULL,
            `update_at`  DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_garage_domains_host` (`host`),
            KEY `idx_garage_domains_garage` (`garage_id`),
            CONSTRAINT `fk_garage_domains_garage` FOREIGN KEY (`garage_id`)
                REFERENCES `garages` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        /* Gara đang có thì cấp sẵn tên miền phụ theo mã gara. Chạy lại không
           sinh dòng trùng. */
        $now  = date('Y-m-d H:i:s');
        $gara = $this->db->table('garages')->select('`id`, `code`, `is_master`')->get();
        $them = 0;

        $khai = function ($garageId, $host, $chinh) use ($now, &$them) {
            $host = strtolower(trim($host));
            if ($host === '') return;
            $co = $this->db->table('garage_domains')->where('host', '=', $host)->first();
            if (!empty($co)) return;
            $this->db->insert('garage_domains', [
                'garage_id'  => (int) $garageId,
                'host'       => $host,
                'is_primary' => $chinh ? 1 : 0,
                'status'     => 1,
                'create_at'  => $now,
            ]);
            $them++;
            echo "  $host -> gara #$garageId\n";
        };

        foreach ((array) $gara as $g){
            $khai((int) $g['id'], strtolower(trim((string) $g['code'])) . '.' . self::TEN_MIEN_GOC, true);

            /* GARA TỔNG NHẬN THÊM CHÍNH TÊN MIỀN GỐC.
               Trang đang chạy thật nằm ở https://etek.rikkeiedu.org — không khai
               thì ngay khi bảng này có dòng đầu tiên, trang đó thành "host lạ"
               và bị chặn. Đẩy code lên là web sập, mà lỗi không nằm ở code. */
            if (!empty($g['is_master'])) $khai((int) $g['id'], self::TEN_MIEN_GOC, false);
        }
        echo "  Da khai $them ten mien.\n";
    }

    public function down(){
        $this->run("DROP TABLE IF EXISTS `garage_domains`");
        echo "  Da go bang garage_domains.\n";
    }
};

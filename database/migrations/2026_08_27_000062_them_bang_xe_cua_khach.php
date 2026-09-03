<?php
/**
 * Xe của khách hàng — biển số + số km.
 *
 * VÌ SAO LÀ BẢNG RIÊNG, KHÔNG PHẢI HAI CỘT TRÊN `members`
 * Một khách có thể có NHIỀU xe: gara nào cũng gặp khách mang lúc thì con
 * Camry, lúc thì con Ranger. Nhét `bien_so` vào `members` thì khách thứ hai
 * xe trở đi không có chỗ ghi, và tra theo biển số sẽ ra sai người.
 *
 * `so_km` để ở ĐÂY chứ không phải ở bảng khách: số km là của từng xe, hai xe
 * của cùng một người có số km khác nhau hoàn toàn.
 *
 * BIỂN SỐ LƯU HAI CỘT
 *   bien_so       — đúng như người nhập: "30A-123.45", "51F 678.90"
 *   bien_so_chuan — chỉ chữ và số, viết hoa: "30A12345"
 *
 * Người nhập mỗi lần một kiểu (có gạch, có chấm, có khoảng trắng, chữ thường).
 * Tra cứu mà so thẳng cột gốc thì gõ "30a12345" không ra "30A-123.45" — đúng
 * xe đó mà máy báo không tìm thấy. Cột chuẩn hoá lo việc so khớp, cột gốc giữ
 * để in ra cho đẹp.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $this->run("
            CREATE TABLE IF NOT EXISTS `member_vehicles` (
                `id`            INT AUTO_INCREMENT PRIMARY KEY,
                `member_id`     INT NOT NULL,
                `bien_so`       VARCHAR(20)  NOT NULL,
                `bien_so_chuan` VARCHAR(20)  NOT NULL,
                `hang_xe`       VARCHAR(60)  DEFAULT NULL,
                `model_xe`      VARCHAR(60)  DEFAULT NULL,
                `nam_sx`        SMALLINT     DEFAULT NULL,
                `mau_xe`        VARCHAR(40)  DEFAULT NULL,
                `so_km`         INT          DEFAULT NULL,
                `ghi_chu`       VARCHAR(255) DEFAULT NULL,
                `create_at`     DATETIME     DEFAULT NULL,
                `update_at`     DATETIME     DEFAULT NULL,

                KEY `idx_mv_member` (`member_id`),
                /* Tra theo biển số là việc CSKH làm nhiều nhất -> phải có index,
                   không thì mỗi lần tìm là quét cả bảng. */
                KEY `idx_mv_bien_so` (`bien_so_chuan`),

                CONSTRAINT `fk_mv_member`
                    FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        /* CỐ Ý KHÔNG đặt UNIQUE trên bien_so_chuan.
           Xe sang tay là chuyện thường: chủ cũ và chủ mới đều từng mang xe đó
           tới gara, hai dòng cùng biển số nhưng khác member_id. Ép UNIQUE thì
           lúc khách mới tới, người nhập liệu bị chặn mà không hiểu vì sao —
           trong khi dữ liệu cũ vẫn cần giữ để tra lịch sử. */

        echo "  Da tao bang `member_vehicles`.\n";
    }

    public function down(){
        $this->run("DROP TABLE IF EXISTS `member_vehicles`");
    }
};

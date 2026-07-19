<?php
/**
 * CSKH-2 — Biên bản giao nhận thiết bị bảo hành.
 *
 * Gắn với 1 phiếu bảo hành (warranty_requests). Hai loại:
 *   - receive : biên bản NHẬN thiết bị từ khách khi tiếp nhận.
 *   - return  : biên bản TRẢ thiết bị cho khách khi hoàn tất.
 * Có bản in A4 (ký giao / ký nhận). Không đăng ký module riêng — thao tác
 * nằm trong phiếu bảo hành, dùng chung quyền 'warranty'.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){

        $this->run("
            CREATE TABLE IF NOT EXISTS `warranty_handovers` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `handover_no` VARCHAR(50) NOT NULL,
                `warranty_id` INT(11) NOT NULL,
                `type` VARCHAR(10) NOT NULL DEFAULT 'receive',
                `handover_date` DATE NOT NULL,
                `deliverer` VARCHAR(150) DEFAULT NULL,
                `receiver` VARCHAR(150) DEFAULT NULL,
                `accessories` TEXT,
                `condition_note` TEXT,
                `note` VARCHAR(255) DEFAULT NULL,
                `created_by` INT(11) DEFAULT NULL,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_handover_no` (`handover_no`),
                KEY `idx_handover_warranty` (`warranty_id`),
                CONSTRAINT `fk_handover_warranty`
                    FOREIGN KEY (`warranty_id`) REFERENCES `warranty_requests` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(){
        $this->run("DROP TABLE IF EXISTS `warranty_handovers`");
    }
};

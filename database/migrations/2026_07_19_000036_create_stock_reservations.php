<?php
/**
 * ĐẶT HÀNG (nốt) — Giữ tồn khi khách đặt hàng (reservation, đặt trước).
 *
 * Khi đặt hàng web -> ghi 'tồn đang giữ' theo phụ tùng; tồn KHẢ DỤNG bán =
 * tổng tồn - tổng đang giữ (dùng cho gate hiển thị tồn TASK_79). Chưa trừ tồn
 * thật, chưa sinh bút toán. Nhả giữ khi: huỷ đơn, hoặc tạo hoá đơn từ đơn
 * (từ đó hoá đơn ghi sổ mới trừ tồn thật).
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $this->run("
            CREATE TABLE IF NOT EXISTS `stock_reservations` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `order_id` INT(11) NOT NULL,
                `part_id` INT(11) NOT NULL,
                `quantity` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
                `create_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_resv_order` (`order_id`),
                KEY `idx_resv_part` (`part_id`),
                CONSTRAINT `fk_resv_order`
                    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_resv_part`
                    FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(){
        $this->run("DROP TABLE IF EXISTS `stock_reservations`");
    }
};

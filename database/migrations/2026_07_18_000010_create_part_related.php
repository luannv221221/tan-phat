<?php
/**
 * TASK_81 — Phụ kiện / sản phẩm đi kèm.
 *
 * Quan hệ tự tham chiếu CÓ HƯỚNG trên `parts`: part_id "gợi ý đi kèm" related_part_id.
 * (A đi kèm B không bắt buộc B đi kèm A — admin tự chọn theo từng sản phẩm.)
 *
 * UNIQUE(part_id, related_part_id) chặn trùng. Cả hai FK CASCADE:
 * xoá phụ tùng nào thì mọi liên kết đi kèm liên quan tự xoá theo.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $this->run("
            CREATE TABLE IF NOT EXISTS `part_related` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `part_id` INT(11) NOT NULL,
                `related_part_id` INT(11) NOT NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `create_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_part_related` (`part_id`, `related_part_id`),
                KEY `idx_part_related_part` (`part_id`),
                KEY `idx_part_related_related` (`related_part_id`),
                CONSTRAINT `fk_part_related_part`
                    FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_part_related_related`
                    FOREIGN KEY (`related_part_id`) REFERENCES `parts` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(){
        $this->run("DROP TABLE IF EXISTS `part_related`");
    }
};

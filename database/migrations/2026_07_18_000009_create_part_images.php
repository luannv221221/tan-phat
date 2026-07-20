<?php
/**
 * TASK_77 — Thư viện ảnh phụ tùng (nhiều ảnh / 1 phụ tùng, có ảnh đại diện).
 *
 * ON DELETE CASCADE: xoá phụ tùng thì ảnh (bản ghi) tự xoá theo.
 * File vật lý do controller xoá riêng.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $this->run("
            CREATE TABLE IF NOT EXISTS `part_images` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `part_id` INT(11) NOT NULL,
                `image` VARCHAR(255) NOT NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
                `create_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_part_images_part` (`part_id`),
                CONSTRAINT `fk_part_images_part`
                    FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(){
        $this->run("DROP TABLE IF EXISTS `part_images`");
    }
};

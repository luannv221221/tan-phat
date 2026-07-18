<?php
/**
 * STOREFRONT — Thành viên website (TASK_79).
 *
 * Tách RIÊNG khỏi `users` (nhân viên nội bộ) và `partners` (đối tượng công nợ):
 * thành viên là khách đăng ký trên web, đăng nhập để xem thông tin gate
 * (vd tồn kho sản phẩm — TASK_79) và gửi yêu cầu báo giá từ giỏ hàng.
 *
 * Mật khẩu bcrypt (password_hash), KHÔNG md5.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $this->run("
            CREATE TABLE IF NOT EXISTS `members` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `email` VARCHAR(150) NOT NULL,
                `password` VARCHAR(255) NOT NULL,
                `name` VARCHAR(150) NOT NULL,
                `phone` VARCHAR(30) DEFAULT NULL,
                `address` VARCHAR(255) DEFAULT NULL,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_members_email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(){
        $this->run("DROP TABLE IF EXISTS `members`");
    }
};

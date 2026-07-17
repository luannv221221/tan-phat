<?php
/**
 * Baseline — dựng lại schema nền đang có trong dump tanphat_php_11_12_2021.sql.
 *
 * Dùng CREATE TABLE IF NOT EXISTS để chạy được trên CẢ hai trường hợp:
 *   - DB trắng (dev mới clone về)  -> tạo mới
 *   - DB đang chạy (đã có bảng)    -> bỏ qua, không phá dữ liệu
 *
 * Khác dump cũ:
 *   - utf8mb4 thay utf8 (utf8 của MySQL chỉ 3 byte)
 *   - password VARCHAR(255) thay VARCHAR(50) — xem migration 000002 để biết vì sao
 *   - có PRIMARY KEY + AUTO_INCREMENT (dump cũ khai báo ở phần ALTER rời)
 *   - có index cho login_token
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){

        $this->run("
            CREATE TABLE IF NOT EXISTS `groups` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(100) DEFAULT NULL,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->run("
            CREATE TABLE IF NOT EXISTS `users` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(100) DEFAULT NULL,
                `email` VARCHAR(100) DEFAULT NULL,
                `password` VARCHAR(255) DEFAULT NULL,
                `status` TINYINT(1) NOT NULL DEFAULT 0,
                `group_id` INT(11) DEFAULT NULL,
                `current_activity` DATETIME DEFAULT NULL,
                `forgot_key` VARCHAR(50) DEFAULT NULL,
                `active_key` VARCHAR(50) DEFAULT NULL,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_users_email` (`email`),
                KEY `idx_users_group` (`group_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->run("
            CREATE TABLE IF NOT EXISTS `modules` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(200) DEFAULT NULL,
                `link` VARCHAR(200) DEFAULT NULL,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->run("
            CREATE TABLE IF NOT EXISTS `permissions` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `module_id` INT(11) DEFAULT NULL,
                `group_id` INT(11) DEFAULT NULL,
                `role` VARCHAR(100) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_perm_group` (`group_id`),
                KEY `idx_perm_module` (`module_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->run("
            CREATE TABLE IF NOT EXISTS `options` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `opt_name` VARCHAR(200) DEFAULT NULL,
                `opt_value` TEXT,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_opt_name` (`opt_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // token: 64 ky tu hex (random_bytes(32)) — de 100 cho du
        $this->run("
            CREATE TABLE IF NOT EXISTS `login_token` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `user_id` INT(11) NOT NULL,
                `token` VARCHAR(100) NOT NULL,
                `create_at` DATETIME NOT NULL,
                `client_ip` VARCHAR(45) NOT NULL,
                `current_activity` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_token_user` (`user_id`),
                KEY `idx_token_activity` (`current_activity`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(){
        // Thu tu nguoc lai up()
        $this->run("DROP TABLE IF EXISTS `login_token`");
        $this->run("DROP TABLE IF EXISTS `options`");
        $this->run("DROP TABLE IF EXISTS `permissions`");
        $this->run("DROP TABLE IF EXISTS `modules`");
        $this->run("DROP TABLE IF EXISTS `users`");
        $this->run("DROP TABLE IF EXISTS `groups`");
    }
};

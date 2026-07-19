<?php
/**
 * STOREFRONT — Thống kê truy cập (TASK_109-111).
 * Ghi log lượt xem trang KHÁCH (trong layout storefront) -> báo cáo admin.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $now = date('Y-m-d H:i:s');

        $this->run("
            CREATE TABLE IF NOT EXISTS `visits` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `url` VARCHAR(255) NOT NULL DEFAULT '/',
                `referrer` VARCHAR(255) DEFAULT NULL,
                `keyword` VARCHAR(150) DEFAULT NULL,
                `ip` VARCHAR(45) DEFAULT NULL,
                `user_agent` VARCHAR(255) DEFAULT NULL,
                `member_id` INT(11) DEFAULT NULL,
                `create_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_visit_date` (`create_at`),
                KEY `idx_visit_url` (`url`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $admin = $this->db->table('groups')->where('name', '=', 'Admin')->first();
        $ex = $this->db->table('modules')->where('link', '=', 'thong-ke')->first();
        if (empty($ex)){
            $this->db->insert('modules', ['name' => 'Thống kê truy cập', 'link' => 'thong-ke', 'create_at' => $now]);
        }
        $module = $this->db->table('modules')->where('link', '=', 'thong-ke')->first();
        if (!empty($admin) && !empty($module)){
            $has = $this->db->table('permissions')
                ->where('module_id', '=', $module['id'])->where('group_id', '=', $admin['id'])->where('role', '=', 'view')->first();
            if (empty($has)){
                $this->db->insert('permissions', ['module_id' => $module['id'], 'group_id' => $admin['id'], 'role' => 'view']);
            }
        }
    }

    public function down(){
        $this->run("DROP TABLE IF EXISTS `visits`");
        $m = $this->db->table('modules')->where('link', '=', 'thong-ke')->first();
        if (!empty($m)){
            $this->db->delete('permissions', '`module_id` = ?', [$m['id']]);
            $this->db->delete('modules', '`id` = ?', [$m['id']]);
        }
    }
};

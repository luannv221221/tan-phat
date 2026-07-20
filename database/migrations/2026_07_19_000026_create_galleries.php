<?php
/**
 * CMS — Thư viện ảnh / video (SRS Phần B 4.9).
 *
 * - galleries: album (bộ sưu tập ảnh hoặc video).
 * - gallery_items: ảnh (upload) hoặc video (URL YouTube) trong album.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $now = date('Y-m-d H:i:s');

        $this->run("
            CREATE TABLE IF NOT EXISTS `galleries` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(200) NOT NULL,
                `slug` VARCHAR(230) NOT NULL,
                `description` VARCHAR(500) DEFAULT NULL,
                `cover` VARCHAR(255) DEFAULT NULL,
                `is_published` TINYINT(1) NOT NULL DEFAULT 0,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_gallery_slug` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->run("
            CREATE TABLE IF NOT EXISTS `gallery_items` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `gallery_id` INT(11) NOT NULL,
                `media_type` VARCHAR(10) NOT NULL DEFAULT 'image',
                `image` VARCHAR(255) DEFAULT NULL,
                `video_url` VARCHAR(255) DEFAULT NULL,
                `caption` VARCHAR(255) DEFAULT NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `create_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_gi_gallery` (`gallery_id`),
                CONSTRAINT `fk_gi_gallery`
                    FOREIGN KEY (`gallery_id`) REFERENCES `galleries` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $admin = $this->db->table('groups')->where('name', '=', 'Admin')->first();
        $ex = $this->db->table('modules')->where('link', '=', 'galleries')->first();
        if (empty($ex)){
            $this->db->insert('modules', ['name' => 'Thư viện ảnh/video', 'link' => 'galleries', 'create_at' => $now]);
        }
        $module = $this->db->table('modules')->where('link', '=', 'galleries')->first();
        if (!empty($admin) && !empty($module)){
            foreach (['view', 'add', 'edit', 'delete'] as $role){
                $has = $this->db->table('permissions')
                    ->where('module_id', '=', $module['id'])->where('group_id', '=', $admin['id'])->where('role', '=', $role)->first();
                if (empty($has)){
                    $this->db->insert('permissions', ['module_id' => $module['id'], 'group_id' => $admin['id'], 'role' => $role]);
                }
            }
        }
    }

    public function down(){
        $this->run("DROP TABLE IF EXISTS `gallery_items`");
        $this->run("DROP TABLE IF EXISTS `galleries`");
        $m = $this->db->table('modules')->where('link', '=', 'galleries')->first();
        if (!empty($m)){
            $this->db->delete('permissions', '`module_id` = ?', [$m['id']]);
            $this->db->delete('modules', '`id` = ?', [$m['id']]);
        }
    }
};

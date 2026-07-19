<?php
/**
 * SEO — Cấu hình website (site_settings key-value) + meta cho tin/dự án.
 *
 * - site_settings: cấu hình toàn site (tên, mô tả mặc định, OG image, liên hệ...).
 * - news/projects: thêm meta_title, meta_description (SEO từng bài).
 * - Storefront layout render meta description/keywords + Open Graph động.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $now = date('Y-m-d H:i:s');

        $this->run("
            CREATE TABLE IF NOT EXISTS `site_settings` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `skey` VARCHAR(80) NOT NULL,
                `svalue` TEXT,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_settings_key` (`skey`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // SEO cho tin tức + dự án
        $this->run("ALTER TABLE `news` ADD COLUMN `meta_title` VARCHAR(255) DEFAULT NULL AFTER `slug`");
        $this->run("ALTER TABLE `news` ADD COLUMN `meta_description` VARCHAR(500) DEFAULT NULL AFTER `meta_title`");
        $this->run("ALTER TABLE `projects` ADD COLUMN `meta_title` VARCHAR(255) DEFAULT NULL AFTER `slug`");
        $this->run("ALTER TABLE `projects` ADD COLUMN `meta_description` VARCHAR(500) DEFAULT NULL AFTER `meta_title`");

        // Seed cấu hình mặc định
        $defaults = [
            'site_name'        => 'Tân Phát',
            'site_slogan'      => 'Phụ tùng & thiết bị gara ô tô chính hãng',
            'meta_description' => 'Tân Phát chuyên cung cấp phụ tùng và thiết bị gara ô tô chính hãng. Tư vấn tương thích theo hãng, model, đời xe.',
            'meta_keywords'    => 'phụ tùng ô tô, thiết bị gara, phụ tùng chính hãng, tân phát',
            'og_image'         => '',
            'hotline'          => '1900 0000',
            'email'            => 'info@tanphat.vn',
            'address'          => '',
            'facebook'         => '',
            'zalo'             => '',
        ];
        foreach ($defaults as $k => $v){
            $ex = $this->db->table('site_settings')->where('skey', '=', $k)->first();
            if (empty($ex)){
                $this->db->insert('site_settings', ['skey' => $k, 'svalue' => $v, 'update_at' => $now]);
            }
        }

        // Module cấu hình
        $admin = $this->db->table('groups')->where('name', '=', 'Admin')->first();
        $ex = $this->db->table('modules')->where('link', '=', 'settings')->first();
        if (empty($ex)){
            $this->db->insert('modules', ['name' => 'Cấu hình website', 'link' => 'settings', 'create_at' => $now]);
        }
        $module = $this->db->table('modules')->where('link', '=', 'settings')->first();
        if (!empty($admin) && !empty($module)){
            foreach (['view', 'edit'] as $role){
                $has = $this->db->table('permissions')
                    ->where('module_id', '=', $module['id'])->where('group_id', '=', $admin['id'])->where('role', '=', $role)->first();
                if (empty($has)){
                    $this->db->insert('permissions', ['module_id' => $module['id'], 'group_id' => $admin['id'], 'role' => $role]);
                }
            }
        }
    }

    public function down(){
        $this->run("ALTER TABLE `news` DROP COLUMN `meta_title`");
        $this->run("ALTER TABLE `news` DROP COLUMN `meta_description`");
        $this->run("ALTER TABLE `projects` DROP COLUMN `meta_title`");
        $this->run("ALTER TABLE `projects` DROP COLUMN `meta_description`");
        $this->run("DROP TABLE IF EXISTS `site_settings`");
        $m = $this->db->table('modules')->where('link', '=', 'settings')->first();
        if (!empty($m)){
            $this->db->delete('permissions', '`module_id` = ?', [$m['id']]);
            $this->db->delete('modules', '`id` = ?', [$m['id']]);
        }
    }
};

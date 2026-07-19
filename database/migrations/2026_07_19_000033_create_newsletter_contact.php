<?php
/**
 * CSKH (web) — Đăng ký bản tin + Hộp thư liên hệ (form công khai storefront).
 *
 * - newsletter_subscribers : email đăng ký nhận bản tin (UNIQUE email).
 * - contact_messages       : liên hệ từ khách (tên/email/điện thoại/nội dung),
 *   admin đọc → đánh dấu đã xử lý.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){

        $now = date('Y-m-d H:i:s');

        $this->run("
            CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `email` VARCHAR(190) NOT NULL,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `source` VARCHAR(50) DEFAULT NULL,
                `create_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_newsletter_email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->run("
            CREATE TABLE IF NOT EXISTS `contact_messages` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(150) NOT NULL,
                `email` VARCHAR(190) DEFAULT NULL,
                `phone` VARCHAR(30) DEFAULT NULL,
                `subject` VARCHAR(200) DEFAULT NULL,
                `message` TEXT,
                `status` VARCHAR(15) NOT NULL DEFAULT 'new',
                `ip` VARCHAR(45) DEFAULT NULL,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_contact_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Đăng ký module + quyền Admin ----------
        $modules = [
            'newsletter'       => 'Đăng ký bản tin',
            'contact-messages' => 'Hộp thư liên hệ',
        ];
        $admin = $this->db->table('groups')->where('name', '=', 'Admin')->first();
        foreach ($modules as $link => $name){
            $ex = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($ex)){
                $this->db->insert('modules', ['name' => $name, 'link' => $link, 'create_at' => $now]);
            }
            $module = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($admin) || empty($module)) continue;
            foreach (['view', 'add', 'edit', 'delete'] as $role){
                $has = $this->db->table('permissions')
                    ->where('module_id', '=', $module['id'])
                    ->where('group_id', '=', $admin['id'])
                    ->where('role', '=', $role)->first();
                if (empty($has)){
                    $this->db->insert('permissions', [
                        'module_id' => $module['id'], 'group_id' => $admin['id'], 'role' => $role,
                    ]);
                }
            }
        }
    }

    public function down(){
        $this->run("DROP TABLE IF EXISTS `newsletter_subscribers`");
        $this->run("DROP TABLE IF EXISTS `contact_messages`");
        foreach (['newsletter', 'contact-messages'] as $link){
            $m = $this->db->table('modules')->where('link', '=', $link)->first();
            if (!empty($m)){
                $this->db->delete('permissions', '`module_id` = ?', [$m['id']]);
                $this->db->delete('modules', '`id` = ?', [$m['id']]);
            }
        }
    }
};

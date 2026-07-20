<?php
/**
 * KẾ TOÁN KT-4 — Đối tượng công nợ (partners) + công nợ.
 *
 * `partners`: khách hàng + NCC DÙNG CHUNG (đã chốt) — có cờ loại. Sau này
 * Kinh doanh / Mua hàng dùng lại chung bảng này.
 * Gắn `partner_id` vào acc_vouchers để quy công nợ theo đối tượng.
 * Đăng ký module `partners` (Đối tượng) + `debt` (Công nợ).
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $this->run("
            CREATE TABLE IF NOT EXISTS `partners` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `code` VARCHAR(30) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `type` VARCHAR(10) NOT NULL DEFAULT 'both',
                `tax_code` VARCHAR(30) DEFAULT NULL,
                `phone` VARCHAR(30) DEFAULT NULL,
                `address` VARCHAR(255) DEFAULT NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_partners_code` (`code`),
                KEY `idx_partners_type` (`type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Gắn đối tượng vào chứng từ (giữ partner_name làm nhãn tự do cho khách vãng lai)
        $this->run("ALTER TABLE `acc_vouchers` ADD COLUMN `partner_id` INT(11) DEFAULT NULL AFTER `cash_account_id`");
        $this->run("ALTER TABLE `acc_vouchers` ADD KEY `idx_acc_vouchers_partner` (`partner_id`)");
        $this->run("ALTER TABLE `acc_vouchers`
                    ADD CONSTRAINT `fk_acc_vouchers_partner` FOREIGN KEY (`partner_id`)
                    REFERENCES `partners` (`id`) ON DELETE SET NULL ON UPDATE CASCADE");

        // Module
        $now = date('Y-m-d H:i:s');
        $modules = ['partners' => 'Đối tượng (khách/NCC)', 'debt' => 'Công nợ'];
        $admin = $this->db->table('groups')->where('name', '=', 'Admin')->first();

        foreach ($modules as $link => $name){
            $ex = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($ex)){
                $this->db->insert('modules', ['name' => $name, 'link' => $link, 'create_at' => $now]);
            }
            if (empty($admin)) continue;
            $module = $this->db->table('modules')->where('link', '=', $link)->first();
            $roles = ($link === 'debt') ? ['view'] : ['view', 'add', 'edit', 'delete'];
            foreach ($roles as $role){
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
        $this->run("ALTER TABLE `acc_vouchers` DROP FOREIGN KEY `fk_acc_vouchers_partner`");
        $this->run("ALTER TABLE `acc_vouchers` DROP COLUMN `partner_id`");
        $this->run("DROP TABLE IF EXISTS `partners`");

        foreach (['partners', 'debt'] as $link){
            $m = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($m)) continue;
            $this->db->delete('permissions', '`module_id` = ?', [$m['id']]);
            $this->db->delete('modules', '`id` = ?', [$m['id']]);
        }
    }
};

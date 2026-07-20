<?php
/**
 * STOREFRONT — Đặt hàng online (an toàn: chuyển khoản / COD, KHÔNG cổng thẻ thật).
 *
 * - orders + order_items: đơn hàng từ giỏ hàng.
 * - site_settings: thêm thông tin ngân hàng (hiển thị hướng dẫn chuyển khoản).
 * - Module admin 'orders' để quản lý & cập nhật trạng thái đơn.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $now = date('Y-m-d H:i:s');

        $this->run("
            CREATE TABLE IF NOT EXISTS `orders` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `order_no` VARCHAR(50) NOT NULL,
                `member_id` INT(11) DEFAULT NULL,
                `customer_name` VARCHAR(255) NOT NULL,
                `phone` VARCHAR(30) NOT NULL,
                `email` VARCHAR(150) DEFAULT NULL,
                `address` VARCHAR(255) DEFAULT NULL,
                `note` VARCHAR(500) DEFAULT NULL,
                `payment_method` VARCHAR(20) NOT NULL DEFAULT 'bank_transfer',
                `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `status` VARCHAR(20) NOT NULL DEFAULT 'new',
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_order_no` (`order_no`),
                KEY `idx_order_member` (`member_id`),
                KEY `idx_order_status` (`status`),
                CONSTRAINT `fk_order_member`
                    FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->run("
            CREATE TABLE IF NOT EXISTS `order_items` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `order_id` INT(11) NOT NULL,
                `part_id` INT(11) DEFAULT NULL,
                `part_name` VARCHAR(255) NOT NULL,
                `part_code` VARCHAR(80) DEFAULT NULL,
                `quantity` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
                `unit_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                PRIMARY KEY (`id`),
                KEY `idx_oi_order` (`order_id`),
                CONSTRAINT `fk_oi_order`
                    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_oi_part`
                    FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Thông tin ngân hàng cho hướng dẫn chuyển khoản
        foreach (['bank_name' => 'Vietcombank', 'bank_account' => '0123456789', 'bank_holder' => 'CONG TY TAN PHAT'] as $k => $v){
            $ex = $this->db->table('site_settings')->where('skey', '=', $k)->first();
            if (empty($ex)){
                $this->db->insert('site_settings', ['skey' => $k, 'svalue' => $v, 'update_at' => $now]);
            }
        }

        // Module đơn hàng
        $admin = $this->db->table('groups')->where('name', '=', 'Admin')->first();
        $ex = $this->db->table('modules')->where('link', '=', 'orders')->first();
        if (empty($ex)){
            $this->db->insert('modules', ['name' => 'Đơn hàng', 'link' => 'orders', 'create_at' => $now]);
        }
        $module = $this->db->table('modules')->where('link', '=', 'orders')->first();
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
        $this->run("DROP TABLE IF EXISTS `order_items`");
        $this->run("DROP TABLE IF EXISTS `orders`");
        $m = $this->db->table('modules')->where('link', '=', 'orders')->first();
        if (!empty($m)){
            $this->db->delete('permissions', '`module_id` = ?', [$m['id']]);
            $this->db->delete('modules', '`id` = ?', [$m['id']]);
        }
    }
};

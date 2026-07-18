<?php
/**
 * KHO-2 — Điều chuyển kho (WH-05) + Kiểm kê (WH-07).
 *
 * Tựa lên engine tồn kho bình quân gia quyền (StocksModel) đã có.
 *   - Điều chuyển: applyOut kho nguồn (lấy giá bq), applyIn kho đích tại CHÍNH
 *     giá đó -> bảo toàn giá vốn, tổng tồn không đổi -> KHÔNG sinh bút toán.
 *   - Kiểm kê: so tồn sổ với thực tế; thừa -> applyIn + Nợ156/Có711;
 *     thiếu -> applyOut + Nợ632/Có156 (1 phiếu kế toán KT-6).
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $now = date('Y-m-d H:i:s');

        // ---------- Phiếu điều chuyển kho ----------
        $this->run("
            CREATE TABLE IF NOT EXISTS `warehouse_transfers` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `transfer_no` VARCHAR(50) NOT NULL,
                `from_warehouse_id` INT(11) NOT NULL,
                `to_warehouse_id` INT(11) NOT NULL,
                `transfer_date` DATE NOT NULL,
                `reason` VARCHAR(255) DEFAULT NULL,
                `total_value` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `status` TINYINT(1) NOT NULL DEFAULT 0,
                `created_by` INT(11) DEFAULT NULL,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_transfer_no` (`transfer_no`),
                KEY `idx_transfer_from` (`from_warehouse_id`),
                KEY `idx_transfer_to` (`to_warehouse_id`),
                CONSTRAINT `fk_transfer_from`
                    FOREIGN KEY (`from_warehouse_id`) REFERENCES `warehouses` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk_transfer_to`
                    FOREIGN KEY (`to_warehouse_id`) REFERENCES `warehouses` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->run("
            CREATE TABLE IF NOT EXISTS `warehouse_transfer_items` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `transfer_id` INT(11) NOT NULL,
                `part_id` INT(11) NOT NULL,
                `quantity` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
                `unit_cost` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `note` VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_ti_transfer` (`transfer_id`),
                KEY `idx_ti_part` (`part_id`),
                CONSTRAINT `fk_ti_transfer`
                    FOREIGN KEY (`transfer_id`) REFERENCES `warehouse_transfers` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_ti_part`
                    FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Phiếu kiểm kê ----------
        $this->run("
            CREATE TABLE IF NOT EXISTS `stock_takes` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `take_no` VARCHAR(50) NOT NULL,
                `warehouse_id` INT(11) NOT NULL,
                `take_date` DATE NOT NULL,
                `reason` VARCHAR(255) DEFAULT NULL,
                `surplus_value` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `shortage_value` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `status` TINYINT(1) NOT NULL DEFAULT 0,
                `acc_voucher_id` INT(11) DEFAULT NULL,
                `created_by` INT(11) DEFAULT NULL,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_take_no` (`take_no`),
                KEY `idx_take_wh` (`warehouse_id`),
                CONSTRAINT `fk_take_warehouse`
                    FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk_take_voucher`
                    FOREIGN KEY (`acc_voucher_id`) REFERENCES `acc_vouchers` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->run("
            CREATE TABLE IF NOT EXISTS `stock_take_items` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `take_id` INT(11) NOT NULL,
                `part_id` INT(11) NOT NULL,
                `book_qty` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
                `actual_qty` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
                `diff_qty` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
                `unit_cost` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `diff_value` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `note` VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_sti_take` (`take_id`),
                KEY `idx_sti_part` (`part_id`),
                CONSTRAINT `fk_sti_take`
                    FOREIGN KEY (`take_id`) REFERENCES `stock_takes` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_sti_part`
                    FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Bổ sung TK 711 (thu nhập khác) nếu thiếu — cho kiểm kê thừa ----------
        $ex = $this->db->table('acc_accounts')->where('code', '=', '711')->first();
        if (empty($ex)){
            $max = $this->db->table('acc_accounts')->select('MAX(sort_order) AS mx')->first();
            $this->db->insert('acc_accounts', [
                'code' => '711', 'name' => 'Thu nhập khác', 'type' => 'revenue',
                'is_detail' => 1, 'sort_order' => (int) ($max['mx'] ?? 0) + 1, 'status' => 1, 'create_at' => $now,
            ]);
        }

        // ---------- Đăng ký module + quyền Admin ----------
        $modules = [
            'transfers'   => 'Điều chuyển kho',
            'stock-takes' => 'Kiểm kê kho',
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
        $this->run("DROP TABLE IF EXISTS `warehouse_transfer_items`");
        $this->run("DROP TABLE IF EXISTS `warehouse_transfers`");
        $this->run("DROP TABLE IF EXISTS `stock_take_items`");
        $this->run("DROP TABLE IF EXISTS `stock_takes`");
        foreach (['transfers', 'stock-takes'] as $link){
            $m = $this->db->table('modules')->where('link', '=', $link)->first();
            if (!empty($m)){
                $this->db->delete('permissions', '`module_id` = ?', [$m['id']]);
                $this->db->delete('modules', '`id` = ?', [$m['id']]);
            }
        }
    }
};

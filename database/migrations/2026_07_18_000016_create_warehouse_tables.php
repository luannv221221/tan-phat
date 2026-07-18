<?php
/**
 * PHÂN HỆ KHO (WH) — nền tồn kho + phiếu nhập/xuất + KT-6.
 *
 * Quyết định phạm vi (KHO_BAN_HANG_SPEC.md):
 *   - Kho phẳng 1 cấp, vị trí trong kho ghi text trên dòng hàng.
 *   - Giá vốn BÌNH QUÂN GIA QUYỀN tức thời (cập nhật bq mỗi lần nhập).
 *   - Ghi sổ phiếu -> cập nhật tồn + TỰ SINH phiếu kế toán (KT-6).
 *
 * DECIMAL cho tiền/số lượng — KHÔNG dùng FLOAT (cộng dồn lệch tiền, kế toán không nhận).
 * Số lượng để (15,3) đề phòng đơn vị lẻ (lít, mét).
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){

        $now = date('Y-m-d H:i:s');

        // ---------- Danh mục kho (phẳng) ----------
        $this->run("
            CREATE TABLE IF NOT EXISTS `warehouses` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `code` VARCHAR(50) NOT NULL,
                `name` VARCHAR(150) NOT NULL,
                `address` VARCHAR(255) DEFAULT NULL,
                `phone` VARCHAR(30) DEFAULT NULL,
                `is_default` TINYINT(1) NOT NULL DEFAULT 0,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_warehouses_code` (`code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Tồn tức thời theo (kho x phụ tùng) ----------
        // quantity + avg_cost là số dư hiện tại; dựng lại được từ stock_cards.
        $this->run("
            CREATE TABLE IF NOT EXISTS `stocks` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `warehouse_id` INT(11) NOT NULL,
                `part_id` INT(11) NOT NULL,
                `quantity` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
                `avg_cost` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_stock_wh_part` (`warehouse_id`, `part_id`),
                KEY `idx_stocks_part` (`part_id`),
                CONSTRAINT `fk_stocks_warehouse`
                    FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_stocks_part`
                    FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Thẻ kho (sổ append-only) ----------
        // Mỗi nhập/xuất 1 dòng; balance_qty/balance_value = số dư SAU dòng này.
        // Dùng để: báo cáo thẻ kho, và khôi phục tồn khi huỷ ghi sổ.
        $this->run("
            CREATE TABLE IF NOT EXISTS `stock_cards` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `warehouse_id` INT(11) NOT NULL,
                `part_id` INT(11) NOT NULL,
                `move_date` DATE NOT NULL,
                `doc_type` VARCHAR(20) NOT NULL,
                `doc_id` INT(11) NOT NULL,
                `doc_no` VARCHAR(50) DEFAULT NULL,
                `qty_in` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
                `qty_out` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
                `unit_cost` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `balance_qty` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
                `balance_value` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `note` VARCHAR(255) DEFAULT NULL,
                `create_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_cards_wh_part` (`warehouse_id`, `part_id`, `id`),
                KEY `idx_cards_doc` (`doc_type`, `doc_id`),
                KEY `idx_cards_part_date` (`part_id`, `move_date`),
                CONSTRAINT `fk_cards_warehouse`
                    FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_cards_part`
                    FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Phiếu nhập kho ----------
        $this->run("
            CREATE TABLE IF NOT EXISTS `goods_receipts` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `receipt_no` VARCHAR(50) NOT NULL,
                `receipt_type` VARCHAR(20) NOT NULL DEFAULT 'nhap_mua',
                `warehouse_id` INT(11) NOT NULL,
                `partner_id` INT(11) DEFAULT NULL,
                `partner_name` VARCHAR(255) DEFAULT NULL,
                `counter_account_id` INT(11) DEFAULT NULL,
                `receipt_date` DATE NOT NULL,
                `reason` VARCHAR(255) DEFAULT NULL,
                `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `status` TINYINT(1) NOT NULL DEFAULT 0,
                `acc_voucher_id` INT(11) DEFAULT NULL,
                `created_by` INT(11) DEFAULT NULL,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_receipt_no` (`receipt_no`),
                KEY `idx_receipt_wh` (`warehouse_id`),
                KEY `idx_receipt_status` (`status`),
                CONSTRAINT `fk_receipt_warehouse`
                    FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk_receipt_partner`
                    FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_receipt_counter`
                    FOREIGN KEY (`counter_account_id`) REFERENCES `acc_accounts` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_receipt_voucher`
                    FOREIGN KEY (`acc_voucher_id`) REFERENCES `acc_vouchers` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->run("
            CREATE TABLE IF NOT EXISTS `goods_receipt_items` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `receipt_id` INT(11) NOT NULL,
                `part_id` INT(11) NOT NULL,
                `quantity` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
                `unit_cost` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `location` VARCHAR(100) DEFAULT NULL,
                `note` VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_ri_receipt` (`receipt_id`),
                KEY `idx_ri_part` (`part_id`),
                CONSTRAINT `fk_ri_receipt`
                    FOREIGN KEY (`receipt_id`) REFERENCES `goods_receipts` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_ri_part`
                    FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Phiếu xuất kho ----------
        $this->run("
            CREATE TABLE IF NOT EXISTS `goods_issues` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `issue_no` VARCHAR(50) NOT NULL,
                `issue_type` VARCHAR(20) NOT NULL DEFAULT 'xuat_ban',
                `warehouse_id` INT(11) NOT NULL,
                `partner_id` INT(11) DEFAULT NULL,
                `partner_name` VARCHAR(255) DEFAULT NULL,
                `counter_account_id` INT(11) DEFAULT NULL,
                `issue_date` DATE NOT NULL,
                `reason` VARCHAR(255) DEFAULT NULL,
                `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `status` TINYINT(1) NOT NULL DEFAULT 0,
                `acc_voucher_id` INT(11) DEFAULT NULL,
                `created_by` INT(11) DEFAULT NULL,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_issue_no` (`issue_no`),
                KEY `idx_issue_wh` (`warehouse_id`),
                KEY `idx_issue_status` (`status`),
                CONSTRAINT `fk_issue_warehouse`
                    FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk_issue_partner`
                    FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_issue_counter`
                    FOREIGN KEY (`counter_account_id`) REFERENCES `acc_accounts` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_issue_voucher`
                    FOREIGN KEY (`acc_voucher_id`) REFERENCES `acc_vouchers` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->run("
            CREATE TABLE IF NOT EXISTS `goods_issue_items` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `issue_id` INT(11) NOT NULL,
                `part_id` INT(11) NOT NULL,
                `quantity` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
                `unit_cost` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `note` VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_ii_issue` (`issue_id`),
                KEY `idx_ii_part` (`part_id`),
                CONSTRAINT `fk_ii_issue`
                    FOREIGN KEY (`issue_id`) REFERENCES `goods_issues` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_ii_part`
                    FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Kho mặc định ----------
        $ex = $this->db->table('warehouses')->where('code', '=', 'KHO01')->first();
        if (empty($ex)){
            $this->db->insert('warehouses', [
                'code' => 'KHO01', 'name' => 'Kho tổng', 'is_default' => 1,
                'sort_order' => 0, 'status' => 1, 'create_at' => $now,
            ]);
        }

        // ---------- Đăng ký module + quyền Admin ----------
        $modules = [
            'warehouses'     => 'Danh mục kho',
            'goods-receipts' => 'Phiếu nhập kho',
            'goods-issues'   => 'Phiếu xuất kho',
            'ton-kho'        => 'Tồn kho',
            'the-kho'        => 'Thẻ kho',
        ];
        // Báo cáo chỉ cần quyền xem
        $viewOnly = ['ton-kho' => true, 'the-kho' => true];

        $admin = $this->db->table('groups')->where('name', '=', 'Admin')->first();
        foreach ($modules as $link => $name){
            $ex = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($ex)){
                $this->db->insert('modules', ['name' => $name, 'link' => $link, 'create_at' => $now]);
            }
            $module = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($admin) || empty($module)) continue;

            $roles = isset($viewOnly[$link]) ? ['view'] : ['view', 'add', 'edit', 'delete'];
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
        $this->run("DROP TABLE IF EXISTS `goods_issue_items`");
        $this->run("DROP TABLE IF EXISTS `goods_issues`");
        $this->run("DROP TABLE IF EXISTS `goods_receipt_items`");
        $this->run("DROP TABLE IF EXISTS `goods_receipts`");
        $this->run("DROP TABLE IF EXISTS `stock_cards`");
        $this->run("DROP TABLE IF EXISTS `stocks`");
        $this->run("DROP TABLE IF EXISTS `warehouses`");

        foreach (['warehouses', 'goods-receipts', 'goods-issues', 'ton-kho', 'the-kho'] as $link){
            $m = $this->db->table('modules')->where('link', '=', $link)->first();
            if (!empty($m)){
                $this->db->delete('permissions', '`module_id` = ?', [$m['id']]);
                $this->db->delete('modules', '`id` = ?', [$m['id']]);
            }
        }
    }
};

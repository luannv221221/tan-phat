<?php
/**
 * PHÂN HỆ BÁN HÀNG (SAL) — báo giá + hoá đơn bán -> khép vòng doanh thu + công nợ khách.
 *
 * Quyết định:
 *   - Báo giá (quotations): chỉ đề xuất giá, KHÔNG tác động tồn/kế toán. Có trạng thái.
 *   - Hoá đơn bán (sales_invoices): ghi sổ -> sinh bút toán KT-6 (doanh thu + thuế + giá vốn)
 *     và TRỪ TỒN kho (giá vốn bình quân gia quyền). Công nợ khách tự lên qua TK 131.
 *   - Đối tượng khách hàng DÙNG CHUNG bảng `partners` (KT-4).
 *
 * Định khoản khi ghi sổ hoá đơn (1 phiếu kế toán, đối tượng = khách):
 *   Nợ 131 / Có 511  = doanh thu chưa thuế
 *   Nợ 131 / Có 3331 = thuế GTGT (nếu có)
 *   Nợ 632 / Có 156  = giá vốn (tính lúc ghi sổ)
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){

        $now = date('Y-m-d H:i:s');

        // ---------- Báo giá ----------
        $this->run("
            CREATE TABLE IF NOT EXISTS `quotations` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `quote_no` VARCHAR(50) NOT NULL,
                `customer_id` INT(11) DEFAULT NULL,
                `customer_name` VARCHAR(255) DEFAULT NULL,
                `quote_date` DATE NOT NULL,
                `valid_until` DATE DEFAULT NULL,
                `vat_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `tax_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
                `note` VARCHAR(255) DEFAULT NULL,
                `created_by` INT(11) DEFAULT NULL,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_quote_no` (`quote_no`),
                KEY `idx_quote_customer` (`customer_id`),
                CONSTRAINT `fk_quote_customer`
                    FOREIGN KEY (`customer_id`) REFERENCES `partners` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->run("
            CREATE TABLE IF NOT EXISTS `quotation_items` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `quotation_id` INT(11) NOT NULL,
                `part_id` INT(11) NOT NULL,
                `quantity` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
                `unit_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `note` VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_qi_quote` (`quotation_id`),
                KEY `idx_qi_part` (`part_id`),
                CONSTRAINT `fk_qi_quote`
                    FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_qi_part`
                    FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Hoá đơn bán ----------
        $this->run("
            CREATE TABLE IF NOT EXISTS `sales_invoices` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `invoice_no` VARCHAR(50) NOT NULL,
                `customer_id` INT(11) DEFAULT NULL,
                `customer_name` VARCHAR(255) DEFAULT NULL,
                `warehouse_id` INT(11) NOT NULL,
                `quotation_id` INT(11) DEFAULT NULL,
                `invoice_date` DATE NOT NULL,
                `vat_rate` DECIMAL(5,2) NOT NULL DEFAULT 10.00,
                `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `tax_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `cost_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `status` TINYINT(1) NOT NULL DEFAULT 0,
                `acc_voucher_id` INT(11) DEFAULT NULL,
                `note` VARCHAR(255) DEFAULT NULL,
                `created_by` INT(11) DEFAULT NULL,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_invoice_no` (`invoice_no`),
                KEY `idx_inv_customer` (`customer_id`),
                KEY `idx_inv_status` (`status`),
                CONSTRAINT `fk_inv_customer`
                    FOREIGN KEY (`customer_id`) REFERENCES `partners` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_inv_warehouse`
                    FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk_inv_quote`
                    FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_inv_voucher`
                    FOREIGN KEY (`acc_voucher_id`) REFERENCES `acc_vouchers` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->run("
            CREATE TABLE IF NOT EXISTS `sales_invoice_items` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `invoice_id` INT(11) NOT NULL,
                `part_id` INT(11) NOT NULL,
                `quantity` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
                `unit_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `unit_cost` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `cost_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `note` VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_sii_invoice` (`invoice_id`),
                KEY `idx_sii_part` (`part_id`),
                CONSTRAINT `fk_sii_invoice`
                    FOREIGN KEY (`invoice_id`) REFERENCES `sales_invoices` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_sii_part`
                    FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Đăng ký module + quyền Admin ----------
        $modules = [
            'quotations'        => 'Báo giá',
            'sales-invoices'    => 'Hoá đơn bán',
            'bao-cao-ban-hang'  => 'Báo cáo bán hàng',
        ];
        $viewOnly = ['bao-cao-ban-hang' => true];

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
        $this->run("DROP TABLE IF EXISTS `sales_invoice_items`");
        $this->run("DROP TABLE IF EXISTS `sales_invoices`");
        $this->run("DROP TABLE IF EXISTS `quotation_items`");
        $this->run("DROP TABLE IF EXISTS `quotations`");

        foreach (['quotations', 'sales-invoices', 'bao-cao-ban-hang'] as $link){
            $m = $this->db->table('modules')->where('link', '=', $link)->first();
            if (!empty($m)){
                $this->db->delete('permissions', '`module_id` = ?', [$m['id']]);
                $this->db->delete('modules', '`id` = ?', [$m['id']]);
            }
        }
    }
};

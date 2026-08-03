<?php
/**
 * GỠ BỎ HẲN PHÂN HỆ KẾ TOÁN VÀ NHÂN SỰ KHỎI CƠ SỞ DỮ LIỆU.
 *
 * BỐI CẢNH: commit 12b18ac ("bỏ Kế toán/HR") đã xoá toàn bộ controller, model
 * và view của hai phân hệ này khỏi mã nguồn, nhưng phần dấu vết trong CSDL bị
 * bỏ lại. Rà soát trước khi bàn giao thấy còn sót:
 *
 *   - 9 bảng không dòng mã nào đọc hay ghi nữa.
 *   - 13 dòng `modules` trỏ tới chức năng không tồn tại, kéo theo 40 dòng
 *     `permissions` (chiếm gần một phần năm bảng phân quyền).
 *   - 6 cột ở 4 bảng chứng từ đang dùng: acc_voucher_id và counter_account_id.
 *     Hàm counterId() trong hai controller phiếu nhập/xuất đọc một trường form
 *     mà không view nào còn gửi lên, nên các cột này luôn rỗng với dữ liệu mới.
 *
 * Số liệu kế toán còn trong bảng (14 phiếu, 23 dòng định khoản) là do
 * database/seed_demo.php sinh ra hồi trước, không phải hệ thống chạy sinh ra.
 *
 * KHÔNG KHÔI PHỤC ĐƯỢC DỮ LIỆU: down() chỉ dựng lại cấu trúc bảng và cột,
 * không lấy lại được số liệu đã xoá. Cần giữ thì sao lưu trước khi chạy.
 */

use App\core\Migration;

return new class extends Migration {

    /** [bảng => [tên khoá ngoại => cột cần bỏ]] */
    protected $deadColumns = [
        'sales_invoices' => ['fk_inv_voucher'     => 'acc_voucher_id'],
        'goods_receipts' => ['fk_receipt_voucher' => 'acc_voucher_id',
                             'fk_receipt_counter' => 'counter_account_id'],
        'goods_issues'   => ['fk_issue_voucher'   => 'acc_voucher_id',
                             'fk_issue_counter'   => 'counter_account_id'],
        'stock_takes'    => ['fk_take_voucher'    => 'acc_voucher_id'],
    ];

    /** Xoá bảng con trước bảng cha để khoá ngoại không chặn */
    protected $deadTables = [
        'acc_voucher_entries', 'acc_vouchers', 'acc_cost_items', 'acc_projects', 'acc_accounts',
        'leave_requests', 'employees', 'positions', 'departments',
    ];

    /** link của các module đã mất chức năng */
    protected $deadModules = [
        'accounts', 'cost-items', 'projects', 'vouchers', 'cash-book', 'journal',
        'debt', 'nhat-ky-chung', 'so-cai',
        'departments', 'positions', 'employees', 'leave-requests',
    ];

    public function up(){

        // 1) Gỡ cột chết ở các bảng chứng từ vẫn đang dùng
        foreach ($this->deadColumns as $table => $pairs){
            foreach ($pairs as $fk => $col){
                if ($this->fkExists($table, $fk)){
                    $this->run("ALTER TABLE `$table` DROP FOREIGN KEY `$fk`");
                }
                if ($this->indexExists($table, $fk)){
                    $this->run("ALTER TABLE `$table` DROP INDEX `$fk`");
                }
                if ($this->hasColumn($table, $col)){
                    $this->run("ALTER TABLE `$table` DROP COLUMN `$col`");
                }
            }
        }

        // 2) Gỡ menu + quyền của các chức năng không còn
        foreach ($this->deadModules as $link){
            $m = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($m)) continue;
            $this->db->delete('permissions', '`module_id` = ?', [$m['id']]);
            $this->db->delete('modules', '`id` = ?', [$m['id']]);
        }

        // 3) Bỏ bảng
        foreach ($this->deadTables as $t){
            $this->run("DROP TABLE IF EXISTS `$t`");
        }
    }

    /**
     * Dựng lại CẤU TRÚC (không có dữ liệu) để rollback không làm hỏng lược đồ.
     * Nếu thật sự cần dùng lại kế toán / nhân sự thì nên viết migration mới
     * theo yêu cầu nghiệp vụ lúc đó, chứ không nên rollback cái này.
     */
    public function down(){

        $this->run("
            CREATE TABLE IF NOT EXISTS `acc_accounts` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `code` VARCHAR(20) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `parent_id` INT(11) DEFAULT NULL,
                `type` VARCHAR(20) NOT NULL DEFAULT 'other',
                `is_detail` TINYINT(1) NOT NULL DEFAULT 1,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_acc_accounts_code` (`code`),
                KEY `idx_acc_accounts_parent` (`parent_id`),
                CONSTRAINT `fk_acc_accounts_parent` FOREIGN KEY (`parent_id`)
                    REFERENCES `acc_accounts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        foreach (['acc_cost_items' => 'uq_acc_cost_items_code',
                  'acc_projects'   => 'uq_acc_projects_code'] as $t => $uq){
            $this->run("
                CREATE TABLE IF NOT EXISTS `$t` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `code` VARCHAR(30) NOT NULL,
                    `name` VARCHAR(255) NOT NULL,
                    `sort_order` INT(11) NOT NULL DEFAULT 0,
                    `status` TINYINT(1) NOT NULL DEFAULT 1,
                    `create_at` DATETIME DEFAULT NULL,
                    `update_at` DATETIME DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `$uq` (`code`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        $this->run("
            CREATE TABLE IF NOT EXISTS `acc_vouchers` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `voucher_no` VARCHAR(30) NOT NULL,
                `voucher_type` VARCHAR(10) NOT NULL,
                `voucher_date` DATE NOT NULL,
                `cash_account_id` INT(11) DEFAULT NULL,
                `partner_id` INT(11) DEFAULT NULL,
                `partner_name` VARCHAR(255) DEFAULT NULL,
                `reason` VARCHAR(255) DEFAULT NULL,
                `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `status` TINYINT(1) NOT NULL DEFAULT 0,
                `created_by` INT(11) DEFAULT NULL,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_acc_vouchers_no` (`voucher_no`),
                KEY `idx_acc_vouchers_date` (`voucher_date`),
                KEY `idx_acc_vouchers_cash` (`cash_account_id`),
                KEY `idx_acc_vouchers_partner` (`partner_id`),
                CONSTRAINT `fk_acc_vouchers_cash` FOREIGN KEY (`cash_account_id`)
                    REFERENCES `acc_accounts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk_acc_vouchers_partner` FOREIGN KEY (`partner_id`)
                    REFERENCES `partners` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->run("
            CREATE TABLE IF NOT EXISTS `acc_voucher_entries` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `voucher_id` INT(11) NOT NULL,
                `account_id` INT(11) DEFAULT NULL,
                `debit_account_id` INT(11) DEFAULT NULL,
                `credit_account_id` INT(11) DEFAULT NULL,
                `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `description` VARCHAR(255) DEFAULT NULL,
                `cost_item_id` INT(11) DEFAULT NULL,
                `project_id` INT(11) DEFAULT NULL,
                `create_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_ave_voucher` (`voucher_id`),
                KEY `idx_ave_account` (`account_id`),
                KEY `fk_ave_debit` (`debit_account_id`),
                KEY `fk_ave_credit` (`credit_account_id`),
                KEY `fk_ave_cost_item` (`cost_item_id`),
                KEY `fk_ave_project` (`project_id`),
                CONSTRAINT `fk_ave_voucher` FOREIGN KEY (`voucher_id`)
                    REFERENCES `acc_vouchers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_ave_account` FOREIGN KEY (`account_id`)
                    REFERENCES `acc_accounts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk_ave_debit` FOREIGN KEY (`debit_account_id`)
                    REFERENCES `acc_accounts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk_ave_credit` FOREIGN KEY (`credit_account_id`)
                    REFERENCES `acc_accounts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk_ave_cost_item` FOREIGN KEY (`cost_item_id`)
                    REFERENCES `acc_cost_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_ave_project` FOREIGN KEY (`project_id`)
                    REFERENCES `acc_projects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->run("
            CREATE TABLE IF NOT EXISTS `departments` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `code` VARCHAR(50) DEFAULT NULL,
                `name` VARCHAR(150) NOT NULL,
                `description` VARCHAR(255) DEFAULT NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $this->run("
            CREATE TABLE IF NOT EXISTS `positions` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(150) NOT NULL,
                `description` VARCHAR(255) DEFAULT NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $this->run("
            CREATE TABLE IF NOT EXISTS `employees` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `code` VARCHAR(50) NOT NULL,
                `name` VARCHAR(150) NOT NULL,
                `department_id` INT(11) DEFAULT NULL,
                `position_id` INT(11) DEFAULT NULL,
                `gender` VARCHAR(10) DEFAULT NULL,
                `dob` DATE DEFAULT NULL,
                `phone` VARCHAR(30) DEFAULT NULL,
                `email` VARCHAR(150) DEFAULT NULL,
                `address` VARCHAR(255) DEFAULT NULL,
                `hire_date` DATE DEFAULT NULL,
                `salary_base` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `note` VARCHAR(255) DEFAULT NULL,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_employees_code` (`code`),
                KEY `idx_emp_dept` (`department_id`),
                KEY `idx_emp_pos` (`position_id`),
                CONSTRAINT `fk_emp_dept` FOREIGN KEY (`department_id`)
                    REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_emp_pos` FOREIGN KEY (`position_id`)
                    REFERENCES `positions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $this->run("
            CREATE TABLE IF NOT EXISTS `leave_requests` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `employee_id` INT(11) NOT NULL,
                `leave_type` VARCHAR(20) NOT NULL DEFAULT 'annual',
                `from_date` DATE NOT NULL,
                `to_date` DATE NOT NULL,
                `days` DECIMAL(4,1) NOT NULL DEFAULT 1.0,
                `reason` VARCHAR(255) DEFAULT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
                `approver_note` VARCHAR(255) DEFAULT NULL,
                `created_by` INT(11) DEFAULT NULL,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_leave_emp` (`employee_id`),
                KEY `idx_leave_status` (`status`),
                CONSTRAINT `fk_leave_emp` FOREIGN KEY (`employee_id`)
                    REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Trả lại các cột chứng từ
        foreach ($this->deadColumns as $table => $pairs){
            foreach ($pairs as $fk => $col){
                if (!$this->hasColumn($table, $col)){
                    $this->run("ALTER TABLE `$table` ADD COLUMN `$col` INT(11) DEFAULT NULL");
                }
                if (!$this->fkExists($table, $fk)){
                    $ref = $col === 'acc_voucher_id' ? 'acc_vouchers' : 'acc_accounts';
                    $this->run("ALTER TABLE `$table` ADD CONSTRAINT `$fk` FOREIGN KEY (`$col`)
                                REFERENCES `$ref` (`id`) ON DELETE SET NULL ON UPDATE CASCADE");
                }
            }
        }
    }

    /* ---------- tiện ích ---------- */

    protected function hasColumn($table, $column){
        $r = $this->db->firstRaw(
            "SELECT COUNT(*) AS c FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $column]);
        return !empty($r['c']);
    }

    protected function indexExists($table, $index){
        $r = $this->db->firstRaw(
            "SELECT COUNT(*) AS c FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?",
            [$table, $index]);
        return !empty($r['c']);
    }

    protected function fkExists($table, $name){
        $r = $this->db->firstRaw(
            "SELECT COUNT(*) AS c FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$table, $name]);
        return !empty($r['c']);
    }
};

<?php
/**
 * KẾ TOÁN KT-1 + KT-2 (theo KE_TOAN_SPEC_DE_XUAT.md — bản đã duyệt).
 *
 * KT-1: acc_accounts (danh mục tài khoản, cây), acc_cost_items (mã phí),
 *       acc_projects (mã vụ việc).
 * KT-2: acc_vouchers (phiếu thu/chi), acc_voucher_entries (định khoản đối ứng).
 *
 * Bút toán kép: phiếu THU -> Nợ TK quỹ / Có TK đối ứng; phiếu CHI -> ngược lại.
 * Tổng các dòng entry = tổng tiền phiếu.
 *
 * Đăng ký 5 module + cấp quyền Admin.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){

        // ---------- KT-1: Danh mục tài khoản (cây) ----------
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
                CONSTRAINT `fk_acc_accounts_parent`
                    FOREIGN KEY (`parent_id`) REFERENCES `acc_accounts` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- KT-1: Mã phí ----------
        $this->run("
            CREATE TABLE IF NOT EXISTS `acc_cost_items` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `code` VARCHAR(30) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_acc_cost_items_code` (`code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- KT-1: Mã vụ việc ----------
        $this->run("
            CREATE TABLE IF NOT EXISTS `acc_projects` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `code` VARCHAR(30) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_acc_projects_code` (`code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- KT-2: Phiếu thu/chi ----------
        $this->run("
            CREATE TABLE IF NOT EXISTS `acc_vouchers` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `voucher_no` VARCHAR(30) NOT NULL,
                `voucher_type` VARCHAR(10) NOT NULL,
                `voucher_date` DATE NOT NULL,
                `cash_account_id` INT(11) NOT NULL,
                `partner_name` VARCHAR(255) DEFAULT NULL,
                `reason` VARCHAR(255) DEFAULT NULL,
                `amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 0,
                `created_by` INT(11) DEFAULT NULL,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_acc_vouchers_no` (`voucher_no`),
                KEY `idx_acc_vouchers_date` (`voucher_date`),
                KEY `idx_acc_vouchers_cash` (`cash_account_id`),
                CONSTRAINT `fk_acc_vouchers_cash`
                    FOREIGN KEY (`cash_account_id`) REFERENCES `acc_accounts` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- KT-2: Định khoản chi tiết ----------
        $this->run("
            CREATE TABLE IF NOT EXISTS `acc_voucher_entries` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `voucher_id` INT(11) NOT NULL,
                `account_id` INT(11) NOT NULL,
                `amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
                `description` VARCHAR(255) DEFAULT NULL,
                `cost_item_id` INT(11) DEFAULT NULL,
                `project_id` INT(11) DEFAULT NULL,
                `create_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_ave_voucher` (`voucher_id`),
                KEY `idx_ave_account` (`account_id`),
                CONSTRAINT `fk_ave_voucher`
                    FOREIGN KEY (`voucher_id`) REFERENCES `acc_vouchers` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_ave_account`
                    FOREIGN KEY (`account_id`) REFERENCES `acc_accounts` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk_ave_cost_item`
                    FOREIGN KEY (`cost_item_id`) REFERENCES `acc_cost_items` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_ave_project`
                    FOREIGN KEY (`project_id`) REFERENCES `acc_projects` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Seed hệ thống tài khoản (bộ lõi, dùng chung TT200/TT133) ----------
        // Điều chỉnh theo chế độ kế toán thực tế của Tân Phát (xem spec câu 1).
        $now = date('Y-m-d H:i:s');
        $accounts = [
            // code, name, type, is_detail
            ['111', 'Tiền mặt',                       'asset',     1],
            ['112', 'Tiền gửi ngân hàng',             'asset',     1],
            ['131', 'Phải thu của khách hàng',        'asset',     1],
            ['133', 'Thuế GTGT được khấu trừ',        'asset',     1],
            ['156', 'Hàng hóa',                       'asset',     1],
            ['211', 'Tài sản cố định hữu hình',       'asset',     1],
            ['331', 'Phải trả cho người bán',         'liability', 1],
            ['333', 'Thuế và các khoản phải nộp NN',  'liability', 1],
            ['334', 'Phải trả người lao động',        'liability', 1],
            ['411', 'Vốn đầu tư của chủ sở hữu',      'equity',    1],
            ['421', 'Lợi nhuận sau thuế chưa PP',     'equity',    1],
            ['511', 'Doanh thu bán hàng và CCDV',     'revenue',   1],
            ['515', 'Doanh thu hoạt động tài chính',  'revenue',   1],
            ['632', 'Giá vốn hàng bán',               'expense',   1],
            ['635', 'Chi phí tài chính',              'expense',   1],
            ['641', 'Chi phí bán hàng',               'expense',   1],
            ['642', 'Chi phí quản lý doanh nghiệp',   'expense',   1],
            ['711', 'Thu nhập khác',                  'revenue',   1],
            ['811', 'Chi phí khác',                   'expense',   1],
        ];
        $i = 0;
        foreach ($accounts as $a){
            $existed = $this->db->table('acc_accounts')->where('code', '=', $a[0])->first();
            if (empty($existed)){
                $this->db->insert('acc_accounts', [
                    'code' => $a[0], 'name' => $a[1], 'type' => $a[2],
                    'is_detail' => $a[3], 'sort_order' => $i++, 'status' => 1, 'create_at' => $now,
                ]);
            }
        }

        // ---------- Đăng ký module + quyền Admin ----------
        $modules = [
            'accounts'   => 'Danh mục tài khoản',
            'cost-items' => 'Mã phí',
            'projects'   => 'Mã vụ việc',
            'vouchers'   => 'Phiếu thu / chi',
            'cash-book'  => 'Sổ quỹ',
        ];
        $admin = $this->db->table('groups')->where('name', '=', 'Admin')->first();

        foreach ($modules as $link => $name){
            $existed = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($existed)){
                $this->db->insert('modules', ['name' => $name, 'link' => $link, 'create_at' => $now]);
            }
            if (empty($admin)) continue;

            $module = $this->db->table('modules')->where('link', '=', $link)->first();
            // Sổ quỹ chỉ cần xem; còn lại đủ 4 quyền
            $roles = ($link === 'cash-book') ? ['view'] : ['view', 'add', 'edit', 'delete'];
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
        $this->run("DROP TABLE IF EXISTS `acc_voucher_entries`");
        $this->run("DROP TABLE IF EXISTS `acc_vouchers`");
        $this->run("DROP TABLE IF EXISTS `acc_projects`");
        $this->run("DROP TABLE IF EXISTS `acc_cost_items`");
        $this->run("DROP TABLE IF EXISTS `acc_accounts`");

        foreach (['accounts', 'cost-items', 'projects', 'vouchers', 'cash-book'] as $link){
            $m = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($m)) continue;
            $this->db->delete('permissions', '`module_id` = ?', [$m['id']]);
            $this->db->delete('modules', '`id` = ?', [$m['id']]);
        }
    }
};

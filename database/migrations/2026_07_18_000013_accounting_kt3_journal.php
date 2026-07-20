<?php
/**
 * KẾ TOÁN KT-3 — Phiếu kế toán (general journal: định khoản tự do Nợ/Có).
 *
 * - Bổ sung TK theo TT133 (đã chốt): 141, 138, 338, 3331, 153, 154...
 * - acc_vouchers.cash_account_id -> cho NULL (phiếu kế toán không có TK quỹ).
 * - acc_voucher_entries: thêm debit_account_id / credit_account_id (mỗi dòng
 *   phiếu kế toán = Nợ TK / Có TK / số tiền); account_id cho NULL để dùng chung
 *   bảng với phiếu thu/chi (thu/chi dùng account_id, phiếu KT dùng debit/credit).
 * - Đăng ký module `journal` + quyền Admin.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        // Cho phép phiếu kế toán không có TK quỹ
        $this->run("ALTER TABLE `acc_vouchers` MODIFY `cash_account_id` INT(11) DEFAULT NULL");

        // Thêm cột định khoản tự do; account_id cho NULL
        $this->run("ALTER TABLE `acc_voucher_entries` MODIFY `account_id` INT(11) DEFAULT NULL");
        $this->run("ALTER TABLE `acc_voucher_entries` ADD COLUMN `debit_account_id` INT(11) DEFAULT NULL AFTER `account_id`");
        $this->run("ALTER TABLE `acc_voucher_entries` ADD COLUMN `credit_account_id` INT(11) DEFAULT NULL AFTER `debit_account_id`");
        $this->run("ALTER TABLE `acc_voucher_entries`
                    ADD CONSTRAINT `fk_ave_debit` FOREIGN KEY (`debit_account_id`) REFERENCES `acc_accounts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE");
        $this->run("ALTER TABLE `acc_voucher_entries`
                    ADD CONSTRAINT `fk_ave_credit` FOREIGN KEY (`credit_account_id`) REFERENCES `acc_accounts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE");

        // Bổ sung TK TT133 thường dùng (idempotent theo code)
        $now = date('Y-m-d H:i:s');
        $more = [
            ['141', 'Tạm ứng',                          'asset',     1],
            ['138', 'Phải thu khác',                    'asset',     1],
            ['153', 'Công cụ, dụng cụ',                 'asset',     1],
            ['154', 'Chi phí SXKD dở dang (dịch vụ)',   'asset',     1],
            ['338', 'Phải trả, phải nộp khác',          'liability', 1],
            ['3331', 'Thuế GTGT phải nộp',              'liability', 1],
        ];
        $max = $this->db->table('acc_accounts')->select('MAX(sort_order) AS mx')->first();
        $i = (int) ($max['mx'] ?? 0) + 1;
        foreach ($more as $a){
            $ex = $this->db->table('acc_accounts')->where('code', '=', $a[0])->first();
            if (empty($ex)){
                $this->db->insert('acc_accounts', [
                    'code' => $a[0], 'name' => $a[1], 'type' => $a[2],
                    'is_detail' => $a[3], 'sort_order' => $i++, 'status' => 1, 'create_at' => $now,
                ]);
            }
        }

        // Module Phiếu kế toán
        $admin = $this->db->table('groups')->where('name', '=', 'Admin')->first();
        $ex = $this->db->table('modules')->where('link', '=', 'journal')->first();
        if (empty($ex)){
            $this->db->insert('modules', ['name' => 'Phiếu kế toán', 'link' => 'journal', 'create_at' => $now]);
        }
        $module = $this->db->table('modules')->where('link', '=', 'journal')->first();
        if (!empty($admin) && !empty($module)){
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
        $this->run("ALTER TABLE `acc_voucher_entries` DROP FOREIGN KEY `fk_ave_debit`");
        $this->run("ALTER TABLE `acc_voucher_entries` DROP FOREIGN KEY `fk_ave_credit`");
        $this->run("ALTER TABLE `acc_voucher_entries` DROP COLUMN `debit_account_id`");
        $this->run("ALTER TABLE `acc_voucher_entries` DROP COLUMN `credit_account_id`");

        $m = $this->db->table('modules')->where('link', '=', 'journal')->first();
        if (!empty($m)){
            $this->db->delete('permissions', '`module_id` = ?', [$m['id']]);
            $this->db->delete('modules', '`id` = ?', [$m['id']]);
        }
    }
};

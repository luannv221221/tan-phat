<?php
/**
 * GARA ĐỘC LẬP — bước 5: KHOÁ LẠI. CHẠY SAU KHI ĐẨY CODE (như 000069).
 *
 *   1. Gán về đúng gara những dòng `garage_id` còn NULL — code cũ tạo ra trong
 *      lúc chờ đẩy code mới: chứng từ kho theo kho của nó, còn lại về gara tổng.
 *   2. Khoá ngoại còn ON DELETE SET NULL (000063, 000072) đổi sang RESTRICT:
 *      xoá gara rồi để chứng từ "không của ai" là chứng từ không ai thấy nữa.
 *      Gara đã có dữ liệu chỉ khoá được (màn Quản lý gara).
 *   3. `garage_id` thành NOT NULL ở mọi bảng riêng gara. Làm SAU (2): MySQL
 *      không cho cột NOT NULL đứng trong khoá ngoại SET NULL (lỗi 1830).
 *      `users` giữ NULL được: tài khoản chưa gán gara là trạng thái có thật
 *      (không đăng nhập được — AuthMiddleware), không phải dữ liệu mồ côi.
 *   4. Chủ gara (nhóm Manager) tự quản lý KHO và VỊ TRÍ KHO của gara mình: kho
 *      luôn thuộc gara của người tạo, Tân Phát không tạo kho hộ gara được nữa.
 *      Cấp SAU khi đẩy code vì form Kho CŨ còn ô chọn gara — cấp sớm là Manager
 *      tạo được kho cho gara khác.
 *
 * Vì sao phải chạy SAU: trước khi code mới lên, còn model chưa tự ghi gara —
 * cột NOT NULL là form của model đó sập.
 */

use App\core\Migration;

return new class extends Migration {

    /** Bảng => cột kho để lấy gara theo kho (null = không có) */
    protected $bang = [
        'partners' => null, 'customer_groups' => null, 'vehicles' => null, 'receptions' => null,
        'quotations' => null, 'sales_invoices' => 'warehouse_id', 'warranty_requests' => null,
        'warranty_handovers' => null, 'warehouses' => null, 'goods_receipts' => 'warehouse_id',
        'goods_issues' => 'warehouse_id', 'stock_takes' => 'warehouse_id', 'warehouse_transfers' => 'from_warehouse_id',
    ];

    /** Khoá ngoại SET NULL cũ: bảng => tên khoá */
    protected $fkCu = [
        'warehouses' => 'fk_wh_garage', 'users' => 'fk_user_garage', 'quotations' => 'fk_quote_garage',
        'sales_invoices' => 'fk_inv_garage', 'receptions' => 'fk_receptions_garage',
    ];

    /** Quyền cấp thêm cho nhóm Manager: link => roles */
    protected $quyenManager = [
        'warehouses'          => ['add', 'edit', 'delete'],
        'warehouse-locations' => ['view', 'add', 'edit', 'delete'],
    ];

    public function up(){
        $tong = $this->db->firstRaw("SELECT `id` FROM `garages` WHERE `is_master` = 1 ORDER BY `id` LIMIT 1");
        if (empty($tong['id'])) throw new \RuntimeException('Chua co gara tong.');
        $tongId = (int) $tong['id'];

        // 1. Gán nốt dòng NULL
        foreach ($this->bang as $b => $cotKho){
            if (!$this->hasTable($b)) continue;
            if ($cotKho !== null){
                $this->db->query("UPDATE `$b` x JOIN `warehouses` w ON w.`id` = x.`$cotKho`
                                     SET x.`garage_id` = w.`garage_id`
                                   WHERE x.`garage_id` IS NULL AND w.`garage_id` IS NOT NULL");
            }
            $this->db->query("UPDATE `$b` SET `garage_id` = ? WHERE `garage_id` IS NULL", [$tongId]);
        }

        // 2. SET NULL -> RESTRICT
        foreach ($this->fkCu as $b => $fk){
            if (!$this->hasTable($b) || $this->luatXoa($b, $fk) !== 'SET NULL') continue;
            $this->run("ALTER TABLE `$b` DROP FOREIGN KEY `$fk`");
            $this->run("ALTER TABLE `$b` ADD CONSTRAINT `$fk` FOREIGN KEY (`garage_id`)
                        REFERENCES `garages` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE");
        }

        // 3. NOT NULL. MySQL 8 chặn MODIFY cột đứng trong khoá ngoại ON UPDATE
        //    CASCADE khi FOREIGN_KEY_CHECKS = 1 — tắt tạm. An toàn: (1) đã lấp
        //    hết NULL, ở đây chỉ đổi cho-phép-NULL, không đổi kiểu.
        $this->db->query("SET FOREIGN_KEY_CHECKS = 0");
        try {
            foreach (array_keys($this->bang) as $b){
                if (!$this->hasTable($b) || !$this->choNull($b)) continue;
                $this->run("ALTER TABLE `$b` MODIFY `garage_id` INT NOT NULL");
            }
        } finally {
            $this->db->query("SET FOREIGN_KEY_CHECKS = 1");
        }
        echo "  garage_id bat buoc o " . count($this->bang) . " bang, khoa ngoai RESTRICT.\n";

        // 4. Quyền cho chủ gara
        $mg = $this->db->firstRaw("SELECT `id` FROM `groups` WHERE `name` = 'Manager'");
        if (!empty($mg)){
            foreach ($this->quyenManager as $link => $roles){
                $m = $this->db->firstRaw("SELECT `id` FROM `modules` WHERE `link` = ?", [$link]);
                if (empty($m)) continue;
                foreach ($roles as $role){
                    $co = $this->db->firstRaw("SELECT `id` FROM `permissions` WHERE `module_id` = ? AND `group_id` = ? AND `role` = ?",
                                              [$m['id'], $mg['id'], $role]);
                    if (empty($co)) $this->db->insert('permissions', ['module_id' => $m['id'], 'group_id' => $mg['id'], 'role' => $role]);
                }
            }
            echo "  Chu gara (Manager) tu quan ly kho + vi tri kho cua gara minh.\n";
        }
    }

    public function down(){
        $mg = $this->db->firstRaw("SELECT `id` FROM `groups` WHERE `name` = 'Manager'");
        if (!empty($mg)){
            foreach ($this->quyenManager as $link => $roles){
                $m = $this->db->firstRaw("SELECT `id` FROM `modules` WHERE `link` = ?", [$link]);
                if (empty($m)) continue;
                foreach ($roles as $role){
                    $this->db->delete('permissions', '`module_id` = ? AND `group_id` = ? AND `role` = ?', [$m['id'], $mg['id'], $role]);
                }
            }
        }
        $this->db->query("SET FOREIGN_KEY_CHECKS = 0");
        try {
            foreach (array_keys($this->bang) as $b){
                if ($this->hasTable($b) && !$this->choNull($b)) $this->run("ALTER TABLE `$b` MODIFY `garage_id` INT DEFAULT NULL");
            }
        } finally {
            $this->db->query("SET FOREIGN_KEY_CHECKS = 1");
        }
        foreach ($this->fkCu as $b => $fk){
            if (!$this->hasTable($b) || $this->luatXoa($b, $fk) !== 'RESTRICT') continue;
            $this->run("ALTER TABLE `$b` DROP FOREIGN KEY `$fk`");
            $this->run("ALTER TABLE `$b` ADD CONSTRAINT `$fk` FOREIGN KEY (`garage_id`)
                        REFERENCES `garages` (`id`) ON DELETE SET NULL ON UPDATE CASCADE");
        }
    }

    /** Luật ON DELETE của khoá ngoại $fk, hoặc '' nếu không có */
    private function luatXoa($bang, $fk){
        $r = $this->db->firstRaw("SELECT `DELETE_RULE` FROM information_schema.REFERENTIAL_CONSTRAINTS
                                   WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?", [$bang, $fk]);
        return !empty($r['DELETE_RULE']) ? $r['DELETE_RULE'] : '';
    }

    /** Cột garage_id đang cho NULL không */
    private function choNull($bang){
        $r = $this->db->firstRaw("SELECT `IS_NULLABLE` FROM information_schema.COLUMNS
                                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'garage_id'", [$bang]);
        return !empty($r) && $r['IS_NULLABLE'] === 'YES';
    }
};

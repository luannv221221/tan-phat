<?php
/**
 * GARA ĐỘC LẬP — bước 2: khách và xe.
 *
 *   1. `partners.email` — màn Khách hàng giờ đọc bảng đối tượng, mà khách vãng
 *      lai trước đây có email (không bắt buộc).
 *   2. `garage_settings` — cấu hình RIÊNG từng gara (chu kỳ nhắc bảo trì...).
 *      Trước đây lưu vào `site_settings` dùng chung: gara B bấm Lưu là đổi luôn
 *      chu kỳ của Tân Phát. Chưa đặt thì đọc `site_settings` như cũ.
 *   3. Module `tai-khoan-web` (chỉ Tân Phát) — tài khoản khách đăng nhập
 *      website (`members`). Màn Khách hàng (`customers`) từ nay là khách của
 *      gara, đọc từ `partners`. Quyền chép từ module customers (xem, sửa).
 *   4. Chuyển khách / xe cũ: tài khoản có xe khai ở bảng cũ `member_vehicles`,
 *      hoặc không có email (khách vãng lai lập tại gara), thì tạo đối tượng
 *      loại khách và nối qua `members.partner_id`; xe chuyển sang `vehicles`.
 *      Bảng `member_vehicles` để nguyên, KHÔNG xoá.
 *   5. "Không trùng" tính trong từng gara: mã đối tượng, biển số, số khung, số
 *      phiếu tiếp nhận / bảo hành / biên bản giao nhận. Hai gara độc lập cùng
 *      có khách mang xe 30A-123.45 là chuyện bình thường.
 */

use App\core\Migration;

return new class extends Migration {

    /** [bảng, chỉ mục cũ, chỉ mục mới, cột mới] */
    protected $duyNhat = [
        ['partners',           'uq_partners_code',     'uq_partners_gara_code',     '`garage_id`, `code`'],
        ['vehicles',           'uq_vehicles_bien_so',  'uq_vehicles_gara_bien_so',  '`garage_id`, `bien_so_chuan`'],
        ['vehicles',           'uq_vehicles_so_khung', 'uq_vehicles_gara_so_khung', '`garage_id`, `so_khung`'],
        ['receptions',         'uq_receptions_no',     'uq_receptions_gara_no',     '`garage_id`, `reception_no`'],
        ['warranty_requests',  'uq_warranty_no',       'uq_warranty_gara_no',       '`garage_id`, `request_no`'],
        ['warranty_handovers', 'uq_handover_no',       'uq_handover_gara_no',       '`garage_id`, `handover_no`'],
    ];

    public function up(){
        $tong = $this->db->firstRaw("SELECT `id` FROM `garages` WHERE `is_master` = 1 ORDER BY `id` LIMIT 1");
        if (empty($tong['id'])) throw new \RuntimeException('Chua co gara tong — chay migration 000063 truoc.');
        $tongId = (int) $tong['id'];

        // 1. Email cho đối tượng
        if (!$this->hasColumn('partners', 'email')){
            $this->run("ALTER TABLE `partners` ADD COLUMN `email` VARCHAR(150) DEFAULT NULL AFTER `phone`");
        }

        // 2. Cấu hình riêng từng gara
        $this->run("
            CREATE TABLE IF NOT EXISTS `garage_settings` (
                `id`        INT AUTO_INCREMENT PRIMARY KEY,
                `garage_id` INT NOT NULL,
                `skey`      VARCHAR(100) NOT NULL,
                `svalue`    TEXT DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                UNIQUE KEY `uq_gs_key` (`garage_id`, `skey`),
                CONSTRAINT `fk_gs_garage` FOREIGN KEY (`garage_id`)
                    REFERENCES `garages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 3. Màn Tài khoản website
        $this->dangKyTaiKhoanWeb();

        // 4. Chuyển khách / xe cũ
        $this->chuyenKhachCu($tongId);

        // 5. Không trùng — tính trong từng gara. Thêm chỉ mục mới TRƯỚC rồi mới
        //    bỏ chỉ mục cũ, để không lúc nào bảng không có ràng buộc.
        foreach ($this->duyNhat as $d){
            list($bang, $cu, $moi, $cot) = $d;
            if (!$this->hasTable($bang)) continue;
            if (!$this->hasIndex($bang, $moi)) $this->run("ALTER TABLE `$bang` ADD UNIQUE KEY `$moi` ($cot)");
            if ($this->hasIndex($bang, $cu))   $this->run("ALTER TABLE `$bang` DROP INDEX `$cu`");
        }
        echo "  Ma doi tuong, bien so, so khung, so phieu: khong trung TRONG TUNG GARA.\n";
    }

    private function dangKyTaiKhoanWeb(){
        $now = date('Y-m-d H:i:s');
        $m = $this->db->firstRaw("SELECT `id` FROM `modules` WHERE `link` = 'tai-khoan-web'");
        if (empty($m)){
            $this->db->insert('modules', ['name' => 'Tài khoản website', 'link' => 'tai-khoan-web',
                                          'chi_tan_phat' => 1, 'create_at' => $now]);
            $moduleId = (int) $this->db->lastId();
        } else {
            $moduleId = (int) $m['id'];
            $this->db->query("UPDATE `modules` SET `chi_tan_phat` = 1 WHERE `id` = ?", [$moduleId]);
        }

        /* Ai đang xem / sửa được màn Khách hàng cũ thì xem / sửa được màn này:
           trước hôm nay tài khoản website nằm chính ở màn đó. */
        $this->db->query(
            "INSERT INTO `permissions` (`module_id`, `group_id`, `role`)
             SELECT ?, p.`group_id`, p.`role`
               FROM `permissions` p JOIN `modules` c ON c.`id` = p.`module_id`
              WHERE c.`link` = 'customers' AND p.`role` IN ('view', 'edit')
                AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `permissions`) x
                                 WHERE x.`module_id` = ? AND x.`group_id` = p.`group_id` AND x.`role` = p.`role`)",
            [$moduleId, $moduleId]);
        echo "  Da dang ky man \"Tai khoan website\" (chi Tan Phat).\n";
    }

    /** Mã KH-0001 kế tiếp trong gara $garaId */
    private function maKhachKeTiep($garaId){
        $n = 0;
        foreach ((array) $this->db->getRaw("SELECT `code` FROM `partners` WHERE `garage_id` = ? AND `code` LIKE 'KH-%'", [$garaId]) as $r){
            if (preg_match('/(\d+)$/', $r['code'], $mm)) $n = max($n, (int) $mm[1]);
        }
        return 'KH-' . str_pad($n + 1, 4, '0', STR_PAD_LEFT);
    }

    private function chuyenKhachCu($tongId){
        $now  = date('Y-m-d H:i:s');
        $coMv = $this->hasTable('member_vehicles');

        $ds = $this->db->getRaw(
            "SELECT m.* FROM `members` m
              WHERE m.`partner_id` IS NULL
                AND (m.`email` IS NULL OR m.`email` = ''"
          . ($coMv ? " OR EXISTS (SELECT 1 FROM `member_vehicles` v WHERE v.`member_id` = m.`id`)" : '')
          . ")");
        foreach ((array) $ds as $m){
            $this->db->insert('partners', [
                'code'          => $this->maKhachKeTiep($tongId),
                'name'          => !empty($m['name']) ? $m['name'] : ('Khách ' . $m['id']),
                'type'          => 'customer',
                'phone'         => !empty($m['phone']) ? $m['phone'] : null,
                'email'         => !empty($m['email']) ? $m['email'] : null,
                'address'       => !empty($m['address']) ? $m['address'] : null,
                'province_code' => $m['province_code'], 'province_name' => $m['province_name'],
                'ward_code'     => $m['ward_code'],     'ward_name'     => $m['ward_name'],
                'status'        => (int) $m['status'],
                'garage_id'     => $tongId,
                'create_at'     => $now,
            ]);
            $pid = (int) $this->db->lastId();
            $this->db->query("UPDATE `members` SET `partner_id` = ? WHERE `id` = ?", [$pid, (int) $m['id']]);
        }
        if (!empty($ds)) echo "  Da tao " . count($ds) . " khach hang tu tai khoan cu.\n";

        if (!$coMv) return;
        $xe = $this->db->getRaw(
            "SELECT v.*, m.`partner_id` FROM `member_vehicles` v JOIN `members` m ON m.`id` = v.`member_id`");
        $them = 0;
        foreach ((array) $xe as $v){
            $chuan = chuan_hoa_bien_so($v['bien_so']);
            if ($chuan === '') continue;
            $co = $this->db->firstRaw("SELECT `id`, `partner_id` FROM `vehicles` WHERE `garage_id` = ? AND `bien_so_chuan` = ?",
                                      [$tongId, $chuan]);
            if (!empty($co)){
                // Xe đã có (dựng từ biển số trên chứng từ) mà chưa có chủ -> gán chủ
                if (empty($co['partner_id']) && !empty($v['partner_id'])){
                    $this->db->query("UPDATE `vehicles` SET `partner_id` = ? WHERE `id` = ?", [(int) $v['partner_id'], (int) $co['id']]);
                }
                continue;
            }
            $this->db->insert('vehicles', [
                'partner_id'    => !empty($v['partner_id']) ? (int) $v['partner_id'] : null,
                'bien_so'       => $v['bien_so'],
                'bien_so_chuan' => $chuan,
                'hang_xe'       => !empty($v['hang_xe']) ? $v['hang_xe'] : null,
                'model_xe'      => !empty($v['model_xe']) ? $v['model_xe'] : null,
                'nam_sx'        => !empty($v['nam_sx']) ? (int) $v['nam_sx'] : null,
                'mau_xe'        => !empty($v['mau_xe']) ? $v['mau_xe'] : null,
                'so_km'         => $v['so_km'] !== null ? (int) $v['so_km'] : null,
                'ghi_chu'       => !empty($v['ghi_chu']) ? $v['ghi_chu'] : null,
                'status'        => 1,
                'garage_id'     => $tongId,
                'create_at'     => $now,
            ]);
            $them++;
        }
        if ($them > 0) echo "  Da chuyen $them xe tu bang cu sang `vehicles`.\n";
    }

    public function down(){
        /* Trả chỉ mục "không trùng" về toàn hệ thống. Nếu hai gara đã có trùng
           (vd. cùng biển số) thì không trả được — để nguyên, báo ra. */
        foreach (array_reverse($this->duyNhat) as $d){
            list($bang, $cu, $moi, $cot) = $d;
            if (!$this->hasTable($bang)) continue;
            $cotCu = trim(str_replace('`garage_id`,', '', $cot));
            try {
                if (!$this->hasIndex($bang, $cu)) $this->run("ALTER TABLE `$bang` ADD UNIQUE KEY `$cu` ($cotCu)");
                if ($this->hasIndex($bang, $moi)) $this->run("ALTER TABLE `$bang` DROP INDEX `$moi`");
            } catch (\Throwable $e){
                echo "  (giu chi muc $moi: da co du lieu trung giua cac gara)\n";
            }
        }
        $m = $this->db->firstRaw("SELECT `id` FROM `modules` WHERE `link` = 'tai-khoan-web'");
        if (!empty($m)){
            $this->db->delete('permissions', '`module_id` = ?', [$m['id']]);
            $this->db->delete('modules', '`id` = ?', [$m['id']]);
        }
        $this->run("DROP TABLE IF EXISTS `garage_settings`");
        if ($this->hasColumn('partners', 'email')) $this->run("ALTER TABLE `partners` DROP COLUMN `email`");
        /* Khách / xe đã chuyển sang partners / vehicles KHÔNG xoá: có thể đã có
           phiếu tiếp nhận, báo giá gắn vào. */
    }

    protected function hasColumn($bang, $cot){
        try { $rows = $this->db->query("SHOW COLUMNS FROM `$bang`")->fetchAll(\PDO::FETCH_ASSOC); }
        catch (\Throwable $e){ return false; }
        foreach ($rows as $r){ if (isset($r['Field']) && $r['Field'] === $cot) return true; }
        return false;
    }

    protected function hasIndex($bang, $ten){
        try { $rows = $this->db->query("SHOW INDEX FROM `$bang`")->fetchAll(\PDO::FETCH_ASSOC); }
        catch (\Throwable $e){ return false; }
        foreach ($rows as $r){ if (isset($r['Key_name']) && $r['Key_name'] === $ten) return true; }
        return false;
    }
};

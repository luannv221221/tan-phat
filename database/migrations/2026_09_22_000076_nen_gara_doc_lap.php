<?php
/**
 * GARA ĐỘC LẬP — bước 1: nền.
 *
 * Mô hình đổi từ chuỗi chi nhánh sang NỀN TẢNG: mỗi gara là một doanh nghiệp
 * độc lập, không thấy dữ liệu của nhau (xem
 * docs/superpowers/specs/2026-09-22-gara-doc-lap-tren-nen-tang-design.md).
 *
 * Migration này chỉ chuẩn bị chỗ đứng, CHƯA chặn gì:
 *   1. `garage_id` cho 9 bảng riêng gara chưa có cột này
 *   2. Gán dữ liệu cũ vào gara: chứng từ kho theo KHO của nó, còn lại về gara
 *      tổng. Năm bảng đã có cột từ 000063 / 000072 cũng quét lại dòng NULL.
 *   3. `modules.chi_tan_phat` — màn của riêng Tân Phát (website, kho tổng, đơn
 *      web...). Nhóm quyền dùng chung cho mọi gara, nên quyền của nhóm không
 *      đủ để giấu các màn này khỏi gara khác.
 *   4. Thông tin gara để in lên phiếu: mã số thuế, email, logo
 *   5. Đổi tên hai gara mẫu cho khỏi bị hiểu là chi nhánh của Tân Phát
 *
 * CỘT MỚI ĐỂ NULL ĐƯỢC — cố ý. Code hiện tại chưa ghi `garage_id` cho các bảng
 * này; đặt NOT NULL bây giờ là form thêm đối tượng / phiếu nhập... sập ngay.
 * NOT NULL làm ở bước cuối, khi mọi model đã tự điền gara.
 *
 * KHOÁ NGOẠI RESTRICT, không SET NULL như 000063: gara độc lập mà xoá gara rồi
 * để chứng từ lại "không của ai" thì chứng từ đó không ai thấy được nữa. Màn
 * Quản lý gara đếm trước (GaragesModel::dangDungODau) để báo cho dễ hiểu.
 */

use App\core\Migration;

return new class extends Migration {

    /** Bảng riêng gara CHƯA có `garage_id`: bảng => tên khoá ngoại */
    protected $them = [
        'partners'            => 'fk_partner_garage',
        'customer_groups'     => 'fk_cgroup_garage',
        'vehicles'            => 'fk_vehicle_garage',
        'warranty_requests'   => 'fk_warranty_garage',
        'warranty_handovers'  => 'fk_handover_garage',
        'goods_receipts'      => 'fk_receipt_garage',
        'goods_issues'        => 'fk_issue_garage',
        'stock_takes'         => 'fk_take_garage',
        'warehouse_transfers' => 'fk_transfer_garage',
    ];

    /** Đã có `garage_id` từ trước — chỉ quét lại dòng còn NULL */
    protected $daCo = ['warehouses', 'users', 'quotations', 'sales_invoices', 'receptions'];

    /** Chứng từ kho lấy gara theo kho của nó: bảng => cột kho */
    protected $theoKho = [
        'goods_receipts'      => 'warehouse_id',
        'goods_issues'        => 'warehouse_id',
        'stock_takes'         => 'warehouse_id',
        'warehouse_transfers' => 'from_warehouse_id',
    ];

    /** Màn của riêng Tân Phát — gara khác không thấy, dù nhóm có quyền */
    protected $chiTanPhat = [
        'attributes', 'banners', 'car-body-types', 'car-brands', 'car-colors', 'car-fuels',
        'car-models', 'car-years', 'chat', 'contact-messages', 'du-an', 'galleries', 'garages',
        'groups', 'menus', 'modules', 'news', 'news-categories', 'newsletter', 'orders',
        'part-categories', 'product-brands', 'product-manufacturers', 'product-origins',
        'product-units', 'products', 'reviews', 'services', 'settings', 'thong-ke',
    ];

    /** mã gara => [tên cũ, tên mới] */
    protected $doiTen = [
        'DMSG' => ['Tân Phát Sài Gòn', 'Gara mẫu Sài Gòn'],
        'DMDN' => ['Tân Phát Đà Nẵng', 'Gara mẫu Đà Nẵng'],
    ];

    public function up(){
        $tong = $this->db->firstRaw("SELECT `id` FROM `garages` WHERE `is_master` = 1 ORDER BY `id` LIMIT 1");
        if (empty($tong['id'])){
            throw new \RuntimeException('Chua co gara tong (is_master = 1) — chay migration 000063 truoc.');
        }
        $tongId = (int) $tong['id'];

        // 1. Cột + khoá ngoại
        foreach ($this->them as $bang => $fk){
            if (!$this->hasTable($bang) || $this->hasColumn($bang, 'garage_id')) continue;
            $this->run("ALTER TABLE `$bang` ADD COLUMN `garage_id` INT DEFAULT NULL");
            $this->run("ALTER TABLE `$bang` ADD KEY `idx_{$bang}_garage` (`garage_id`)");
            $this->run("ALTER TABLE `$bang` ADD CONSTRAINT `$fk` FOREIGN KEY (`garage_id`)
                        REFERENCES `garages` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE");
            echo "  Da them `$bang`.`garage_id`.\n";
        }

        // 2. Gán dữ liệu cũ — chứng từ kho theo kho trước, còn sót thì về gara tổng
        foreach ($this->theoKho as $bang => $cotKho){
            if (!$this->hasColumn($bang, 'garage_id')) continue;
            $this->db->query("UPDATE `$bang` x JOIN `warehouses` w ON w.`id` = x.`$cotKho`
                                 SET x.`garage_id` = w.`garage_id`
                               WHERE x.`garage_id` IS NULL AND w.`garage_id` IS NOT NULL");
        }
        foreach (array_merge(array_keys($this->them), $this->daCo) as $bang){
            if (!$this->hasTable($bang) || !$this->hasColumn($bang, 'garage_id')) continue;
            $this->db->query("UPDATE `$bang` SET `garage_id` = ? WHERE `garage_id` IS NULL", [$tongId]);
        }
        echo "  Da gan du lieu cu vao gara (chung tu kho theo kho, con lai ve gara tong).\n";

        // 3. Màn chỉ Tân Phát
        if (!$this->hasColumn('modules', 'chi_tan_phat')){
            $this->run("ALTER TABLE `modules` ADD COLUMN `chi_tan_phat` TINYINT(1) NOT NULL DEFAULT 0");
        }
        $dau = implode(',', array_fill(0, count($this->chiTanPhat), '?'));
        $this->db->query("UPDATE `modules` SET `chi_tan_phat` = 1 WHERE `link` IN ($dau)", $this->chiTanPhat);
        echo "  Da danh dau " . count($this->chiTanPhat) . " man chi Tan Phat.\n";

        // 4. Thông tin gara để in lên phiếu
        foreach (['tax_code' => 'VARCHAR(30)', 'email' => 'VARCHAR(150)', 'logo' => 'VARCHAR(255)'] as $c => $kieu){
            if (!$this->hasColumn('garages', $c)){
                $this->run("ALTER TABLE `garages` ADD COLUMN `$c` $kieu DEFAULT NULL");
            }
        }

        // 5. Tên gara mẫu — chỉ đổi khi vẫn còn đúng tên cũ, không đè tên ai đã sửa
        foreach ($this->doiTen as $ma => $ten){
            $this->db->query("UPDATE `garages` SET `name` = ? WHERE `code` = ? AND `name` = ?",
                             [$ten[1], $ma, $ten[0]]);
        }
    }

    public function down(){
        foreach ($this->doiTen as $ma => $ten){
            $this->db->query("UPDATE `garages` SET `name` = ? WHERE `code` = ? AND `name` = ?",
                             [$ten[0], $ma, $ten[1]]);
        }
        foreach (['logo', 'email', 'tax_code'] as $c){
            if ($this->hasColumn('garages', $c)) $this->run("ALTER TABLE `garages` DROP COLUMN `$c`");
        }
        if ($this->hasColumn('modules', 'chi_tan_phat')){
            $this->run("ALTER TABLE `modules` DROP COLUMN `chi_tan_phat`");
        }
        foreach ($this->them as $bang => $fk){
            if (!$this->hasTable($bang) || !$this->hasColumn($bang, 'garage_id')) continue;
            try { $this->run("ALTER TABLE `$bang` DROP FOREIGN KEY `$fk`"); } catch (\Throwable $e){}
            try { $this->run("ALTER TABLE `$bang` DROP KEY `idx_{$bang}_garage`"); } catch (\Throwable $e){}
            $this->run("ALTER TABLE `$bang` DROP COLUMN `garage_id`");
        }
        /* Dòng NULL ở 5 bảng $daCo đã được gán về gara tổng: KHÔNG trả lại NULL.
           Trả lại là làm dữ liệu mất chủ, mà giữ nguyên thì vô hại. */
    }

    /** SHOW COLUMNS rồi lọc bằng PHP — `SHOW COLUMNS ... LIKE ?` bị lỗi 1064 */
    protected function hasColumn($bang, $cot){
        try {
            $rows = $this->db->query("SHOW COLUMNS FROM `$bang`")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e){ return false; }
        foreach ($rows as $r){
            if (isset($r['Field']) && $r['Field'] === $cot) return true;
        }
        return false;
    }
};

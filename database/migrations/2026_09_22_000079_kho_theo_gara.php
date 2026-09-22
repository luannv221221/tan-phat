<?php
/**
 * GARA ĐỘC LẬP — bước 4: kho.
 *
 * "Không trùng" tính TRONG TỪNG GARA cho mã kho và số phiếu nhập / xuất /
 * kiểm kê / chuyển kho: mỗi gara tự đánh PNK-000001, PXK-000001... từ đầu, và
 * gara nào cũng đặt được một kho mã KHO01 của riêng mình.
 *
 * Tồn kho, thẻ kho, vị trí kho KHÔNG có cột gara — chúng đi theo kho, mà kho
 * thuộc gara (xem StocksModel / WarehouseLocationsModel: locKhoGara).
 */

use App\core\Migration;

return new class extends Migration {

    /** [bảng, chỉ mục cũ, chỉ mục mới, cột mới] */
    protected $duyNhat = [
        ['warehouses',          'uq_warehouses_code', 'uq_warehouses_gara_code', '`garage_id`, `code`'],
        ['goods_receipts',      'uq_receipt_no',      'uq_receipt_gara_no',      '`garage_id`, `receipt_no`'],
        ['goods_issues',        'uq_issue_no',        'uq_issue_gara_no',        '`garage_id`, `issue_no`'],
        ['stock_takes',         'uq_take_no',         'uq_take_gara_no',         '`garage_id`, `take_no`'],
        ['warehouse_transfers', 'uq_transfer_no',     'uq_transfer_gara_no',     '`garage_id`, `transfer_no`'],
    ];

    public function up(){
        foreach ($this->duyNhat as $d){
            list($bang, $cu, $moi, $cot) = $d;
            if (!$this->hasTable($bang)) continue;
            if (!$this->hasIndex($bang, $moi)) $this->run("ALTER TABLE `$bang` ADD UNIQUE KEY `$moi` ($cot)");
            if ($this->hasIndex($bang, $cu))   $this->run("ALTER TABLE `$bang` DROP INDEX `$cu`");
        }
        echo "  Ma kho, so phieu nhap / xuat / kiem ke / chuyen kho: khong trung TRONG TUNG GARA.\n";
    }

    public function down(){
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
    }

    protected function hasIndex($bang, $ten){
        try { $rows = $this->db->query("SHOW INDEX FROM `$bang`")->fetchAll(\PDO::FETCH_ASSOC); }
        catch (\Throwable $e){ return false; }
        foreach ($rows as $r){ if (isset($r['Key_name']) && $r['Key_name'] === $ten) return true; }
        return false;
    }
};

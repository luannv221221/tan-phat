<?php
/**
 * GARA ĐỘC LẬP — bước 3: bán hàng.
 *
 * "Không trùng" tính TRONG TỪNG GARA cho số báo giá, số hoá đơn và mã hàng:
 * mỗi gara là một doanh nghiệp, tự đánh BG-000001, HD-000001 từ đầu.
 *
 * MÃ HÀNG — (garage_id, code). MySQL coi các NULL là khác nhau, nên chỉ mục này
 * KHÔNG chặn được hai mặt hàng kho tổng (garage_id NULL) cùng mã, cũng không
 * chặn hàng riêng của gara trùng mã kho tổng. Hai trường hợp đó kiểm bằng PHP
 * (PartsModel::findByCode tìm trong "kho tổng + hàng riêng của gara"). Không
 * dùng cột sinh COALESCE(garage_id, 0): tools/xuat-csdl.php ghi giá trị vào
 * cột sinh và file import hỏng.
 *
 * `parts.slug` GIỮ duy nhất toàn hệ thống — nó là đường dẫn trên website.
 */

use App\core\Migration;

return new class extends Migration {

    /** [bảng, chỉ mục cũ, chỉ mục mới, cột mới] */
    protected $duyNhat = [
        ['quotations',     'uq_quote_no',   'uq_quote_gara_no',   '`garage_id`, `quote_no`'],
        ['sales_invoices', 'uq_invoice_no', 'uq_invoice_gara_no', '`garage_id`, `invoice_no`'],
        ['parts',          'uq_parts_code', 'uq_parts_gara_code', '`garage_id`, `code`'],
    ];

    public function up(){
        foreach ($this->duyNhat as $d){
            list($bang, $cu, $moi, $cot) = $d;
            if (!$this->hasTable($bang)) continue;
            if (!$this->hasIndex($bang, $moi)) $this->run("ALTER TABLE `$bang` ADD UNIQUE KEY `$moi` ($cot)");
            if ($this->hasIndex($bang, $cu))   $this->run("ALTER TABLE `$bang` DROP INDEX `$cu`");
        }
        echo "  So bao gia, so hoa don, ma hang: khong trung TRONG TUNG GARA.\n";
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

<?php
/**
 * VAT NHẬP / VAT XUẤT THEO HÀNG HOÁ + VAT TỪNG DÒNG PHIẾU.
 *
 * Khách yêu cầu 25/11/2026: "thêm vào hàng hóa 2 trường (VAT nhập, VAT xuất)
 * dùng cho các phiếu mua hàng và bán hàng (các phiếu thêm trường này)".
 *
 * Trước đây thuế suất chỉ có MỘT con số ở đầu phiếu, nhân với cả tổng — phiếu
 * có dầu nhớt 10% lẫn hàng 8% thì không lập đúng được. Nay:
 *
 *   `parts`.`vat_in`   thuế suất khi MUA (đơn mua)        NULL = theo phiếu
 *   `parts`.`vat_out`  thuế suất khi BÁN (báo giá, hoá đơn) NULL = theo phiếu
 *   `purchase_order_items` / `quotation_items` / `sales_invoice_items`:
 *       `vat_rate`   thuế suất của DÒNG
 *       `tax_amount` tiền thuế của dòng = ROUND(amount x vat_rate / 100)
 *
 * Thuế đầu phiếu (`tax_amount`) từ nay = tổng thuế các dòng. Cột `vat_rate` ở
 * đầu phiếu giữ nguyên, nghĩa mới là "VAT mặc định cho dòng".
 *
 * ĐIỀN VAT CHO DÒNG CỦA PHIẾU CŨ: dòng lấy thuế suất đầu phiếu, thuế dòng tính
 * lại theo dòng. KHÔNG đụng tổng tiền đầu phiếu — hoá đơn đã ghi sổ là căn cứ
 * kế toán, lệch vài đồng làm tròn giữa "tổng thuế dòng" và "thuế trên tổng"
 * cũng không được phép sửa sau lưng.
 *
 * Chỉ điền cho phiếu "chưa biết VAT dòng": đầu phiếu có thuế suất mà KHÔNG dòng
 * nào mang thuế suất / tiền thuế, và đầu phiếu có tiền thuế (hoặc chưa có tiền
 * hàng). Code mới luôn ghi thuế đầu phiếu = tổng thuế dòng, nên phiếu nó lập ra
 * không bao giờ lọt vào điều kiện này — chạy lại migration không đè lên dòng
 * người dùng cố ý chọn 0%. Phiếu code CŨ lập trong khoảng giữa chạy migration và
 * đẩy code thì lọt vào, và đúng là phải điền (code mới cũng tự đọc đúng chúng,
 * xem vat_dong_phieu_cu() trong app/helpers/functions.php).
 *
 * AN TOÀN KHI CHẠY TRƯỚC KHI ĐẨY CODE: chỉ thêm cột (NULL / DEFAULT 0) và điền
 * cột mới. Code cũ không biết tới các cột này: thêm dòng thì cột mang mặc định,
 * đọc `SELECT *` thì thừa cột — không chỗ nào hỏng.
 *
 * Chạy tay trong phpMyAdmin (nếu không chạy migrate.php):
 *
 *   ALTER TABLE `parts`
 *     ADD COLUMN `vat_in`  DECIMAL(5,2) NULL DEFAULT NULL,
 *     ADD COLUMN `vat_out` DECIMAL(5,2) NULL DEFAULT NULL;
 *   ALTER TABLE `purchase_order_items`
 *     ADD COLUMN `vat_rate`   DECIMAL(5,2)  NOT NULL DEFAULT 0,
 *     ADD COLUMN `tax_amount` DECIMAL(15,2) NOT NULL DEFAULT 0;
 *   ALTER TABLE `quotation_items`
 *     ADD COLUMN `vat_rate`   DECIMAL(5,2)  NOT NULL DEFAULT 0,
 *     ADD COLUMN `tax_amount` DECIMAL(15,2) NOT NULL DEFAULT 0;
 *   ALTER TABLE `sales_invoice_items`
 *     ADD COLUMN `vat_rate`   DECIMAL(5,2)  NOT NULL DEFAULT 0,
 *     ADD COLUMN `tax_amount` DECIMAL(15,2) NOT NULL DEFAULT 0;
 *
 *   -- Điền VAT cho dòng của phiếu cũ (chạy lại được). Mỗi bảng một câu:
 *   UPDATE `quotation_items` i JOIN `quotations` h ON h.`id` = i.`quotation_id`
 *      SET i.`vat_rate` = h.`vat_rate`, i.`tax_amount` = ROUND(i.`amount` * h.`vat_rate` / 100)
 *    WHERE h.`vat_rate` <> 0 AND (h.`tax_amount` <> 0 OR h.`subtotal` = 0)
 *      AND h.`id` NOT IN (SELECT `dp` FROM (SELECT DISTINCT `quotation_id` AS `dp` FROM `quotation_items`
 *                                            WHERE `vat_rate` <> 0 OR `tax_amount` <> 0) t);
 *   UPDATE `sales_invoice_items` i JOIN `sales_invoices` h ON h.`id` = i.`invoice_id`
 *      SET i.`vat_rate` = h.`vat_rate`, i.`tax_amount` = ROUND(i.`amount` * h.`vat_rate` / 100)
 *    WHERE h.`vat_rate` <> 0 AND (h.`tax_amount` <> 0 OR h.`subtotal` = 0)
 *      AND h.`id` NOT IN (SELECT `dp` FROM (SELECT DISTINCT `invoice_id` AS `dp` FROM `sales_invoice_items`
 *                                            WHERE `vat_rate` <> 0 OR `tax_amount` <> 0) t);
 *   UPDATE `purchase_order_items` i JOIN `purchase_orders` h ON h.`id` = i.`po_id`
 *      SET i.`vat_rate` = h.`vat_rate`, i.`tax_amount` = ROUND(i.`amount` * h.`vat_rate` / 100)
 *    WHERE h.`vat_rate` <> 0 AND (h.`tax_amount` <> 0 OR h.`subtotal` = 0)
 *      AND h.`id` NOT IN (SELECT `dp` FROM (SELECT DISTINCT `po_id` AS `dp` FROM `purchase_order_items`
 *                                            WHERE `vat_rate` <> 0 OR `tax_amount` <> 0) t);
 *
 * (Bảng phụ `t` với DISTINCT là bắt buộc: MySQL không cho UPDATE một bảng mà
 * điều kiện lại SELECT thẳng từ chính bảng đó — lỗi #1093.)
 *
 * CHẠY LẠI ĐƯỢC: có cột rồi thì bỏ qua; bước điền chỉ chạm phiếu chưa biết VAT dòng.
 */

use App\core\Migration;

return new class extends Migration {

    /** Bảng dòng => [bảng đầu phiếu, cột khoá nối] */
    const DONG = [
        'purchase_order_items' => ['purchase_orders', 'po_id'],
        'quotation_items'      => ['quotations',      'quotation_id'],
        'sales_invoice_items'  => ['sales_invoices',  'invoice_id'],
    ];

    public function up(){

        if ($this->hasTable('parts')){
            foreach (['vat_in', 'vat_out'] as $c){
                if (!$this->hasColumn('parts', $c)){
                    $this->run("ALTER TABLE `parts` ADD COLUMN `$c` DECIMAL(5,2) NULL DEFAULT NULL");
                    echo "  Da them `parts`.`$c`.\n";
                }
            }
        }

        foreach (self::DONG as $bang => $dau){
            if (!$this->hasTable($bang) || !$this->hasTable($dau[0])) continue;

            if (!$this->hasColumn($bang, 'vat_rate')){
                $this->run("ALTER TABLE `$bang` ADD COLUMN `vat_rate` DECIMAL(5,2) NOT NULL DEFAULT 0");
                echo "  Da them `$bang`.`vat_rate`.\n";
            }
            if (!$this->hasColumn($bang, 'tax_amount')){
                $this->run("ALTER TABLE `$bang` ADD COLUMN `tax_amount` DECIMAL(15,2) NOT NULL DEFAULT 0");
                echo "  Da them `$bang`.`tax_amount`.\n";
            }

            /* Điền VAT cho dòng phiếu cũ — đúng câu trong khối "chạy tay" ở trên. */
            $khoa = $dau[1];
            $st = $this->db->query("UPDATE `$bang` i JOIN `{$dau[0]}` h ON h.`id` = i.`$khoa`
                    SET i.`vat_rate` = h.`vat_rate`, i.`tax_amount` = ROUND(i.`amount` * h.`vat_rate` / 100)
                  WHERE h.`vat_rate` <> 0 AND (h.`tax_amount` <> 0 OR h.`subtotal` = 0)
                    AND h.`id` NOT IN (SELECT `dp` FROM (SELECT DISTINCT `$khoa` AS `dp` FROM `$bang`
                                                          WHERE `vat_rate` <> 0 OR `tax_amount` <> 0) t)");
            $n = $st ? (int) $st->rowCount() : 0;
            echo "  `$bang`: dien VAT cho $n dong cua phieu cu.\n";
        }
    }

    public function down(){
        foreach (array_keys(self::DONG) as $bang){
            foreach (['tax_amount', 'vat_rate'] as $c){
                if ($this->hasColumn($bang, $c)) $this->run("ALTER TABLE `$bang` DROP COLUMN `$c`");
            }
        }
        foreach (['vat_out', 'vat_in'] as $c){
            if ($this->hasColumn('parts', $c)) $this->run("ALTER TABLE `parts` DROP COLUMN `$c`");
        }
    }

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

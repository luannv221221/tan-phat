<?php
/**
 * BÁN HÀNG (nốt) — chiết khấu theo dòng + hoá đơn điện tử (nội bộ).
 *
 * - quotation_items/sales_invoice_items : thêm discount_percent (chiết khấu %/dòng).
 *   amount = qty * unit_price * (1 - discount/100). Chiết khấu nhóm KH áp mặc định
 *   ở form (JS), NV chỉnh được từng dòng.
 * - sales_invoices : trường HĐĐT nội bộ (ký hiệu/mẫu số/số HĐ/trạng thái/ngày phát
 *   hành). Xuất XML để nộp phần mềm HĐĐT; KHÔNG gọi API nhà cung cấp.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $this->run("ALTER TABLE `quotation_items`
                    ADD COLUMN `discount_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER `unit_price`");
        $this->run("ALTER TABLE `sales_invoice_items`
                    ADD COLUMN `discount_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER `unit_price`");

        $this->run("ALTER TABLE `sales_invoices`
                    ADD COLUMN `einvoice_status` VARCHAR(10) NOT NULL DEFAULT 'none' AFTER `acc_voucher_id`,
                    ADD COLUMN `einvoice_serial` VARCHAR(20) DEFAULT NULL AFTER `einvoice_status`,
                    ADD COLUMN `einvoice_form` VARCHAR(20) DEFAULT NULL AFTER `einvoice_serial`,
                    ADD COLUMN `einvoice_no` VARCHAR(30) DEFAULT NULL AFTER `einvoice_form`,
                    ADD COLUMN `einvoice_issued_at` DATETIME DEFAULT NULL AFTER `einvoice_no`");

        // Cấu hình mặc định ký hiệu / mẫu số HĐĐT
        foreach (['einvoice_serial' => 'K' . date('y') . 'TTP', 'einvoice_form' => '1'] as $k => $v){
            $ex = $this->db->table('site_settings')->where('skey', '=', $k)->first();
            if (empty($ex)){
                $this->db->insert('site_settings', ['skey' => $k, 'svalue' => $v]);
            }
        }
    }

    public function down(){
        $this->run("ALTER TABLE `quotation_items` DROP COLUMN `discount_percent`");
        $this->run("ALTER TABLE `sales_invoice_items` DROP COLUMN `discount_percent`");
        $this->run("ALTER TABLE `sales_invoices`
                    DROP COLUMN `einvoice_status`,
                    DROP COLUMN `einvoice_serial`,
                    DROP COLUMN `einvoice_form`,
                    DROP COLUMN `einvoice_no`,
                    DROP COLUMN `einvoice_issued_at`");
        foreach (['einvoice_serial', 'einvoice_form'] as $k){
            $this->db->delete('site_settings', '`skey` = ?', [$k]);
        }
    }
};

<?php
/**
 * Nối ĐƠN HÀNG web -> HOÁ ĐƠN BÁN.
 *
 * Thêm orders.sales_invoice_id. Admin bấm "Tạo hoá đơn" trên đơn -> sinh 1 hoá đơn
 * bán (nháp) từ dòng hàng đơn; ghi sổ hoá đơn sẽ trừ tồn + doanh thu + KT-6
 * (tái dùng toàn bộ máy móc Salesinvoices).
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $this->run("ALTER TABLE `orders` ADD COLUMN `sales_invoice_id` INT(11) DEFAULT NULL AFTER `status`");
        $this->run("ALTER TABLE `orders`
                    ADD CONSTRAINT `fk_order_invoice` FOREIGN KEY (`sales_invoice_id`)
                    REFERENCES `sales_invoices` (`id`) ON DELETE SET NULL ON UPDATE CASCADE");
    }

    public function down(){
        $this->run("ALTER TABLE `orders` DROP FOREIGN KEY `fk_order_invoice`");
        $this->run("ALTER TABLE `orders` DROP COLUMN `sales_invoice_id`");
    }
};

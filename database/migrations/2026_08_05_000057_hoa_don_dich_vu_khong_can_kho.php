<?php
/**
 * HOÁ ĐƠN TOÀN DỊCH VỤ KHÔNG CẦN KHO XUẤT.
 *
 * "Thay dầu", "Bảo dưỡng" không xuất gì khỏi kho cả. Bắt chọn kho là bắt
 * điền một thông tin vô nghĩa, và tệ hơn: người dùng sẽ chọn đại một kho
 * rồi báo cáo kho nhìn như có phát sinh ở đó.
 *
 * `sales_invoices.warehouse_id` đang NOT NULL nên phải nới thành NULL được.
 * Khoá ngoại giữ nguyên (ON DELETE ... của FK cũ vẫn áp), chỉ đổi nullability.
 *
 * Hoá đơn CÓ hàng hoá thì vẫn bắt buộc chọn kho — chốt ở Salesinvoices:
 * validateInput() lúc lưu và post() lúc ghi sổ.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        // Phải gỡ FK trước khi đổi kiểu cột, xong gắn lại — MySQL không cho
        // MODIFY cột đang bị khoá ngoại tham chiếu trong một số cấu hình.
        $this->run("ALTER TABLE `sales_invoices` DROP FOREIGN KEY `fk_inv_warehouse`");
        $this->run("ALTER TABLE `sales_invoices` MODIFY `warehouse_id` INT(11) DEFAULT NULL");
        $this->run("ALTER TABLE `sales_invoices`
                    ADD CONSTRAINT `fk_inv_warehouse`
                    FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)");
    }

    public function down(){
        // Hoá đơn dịch vụ đang để trống -> phải gán tạm kho mặc định thì mới
        // đặt lại NOT NULL được.
        $wh = $this->db->table('warehouses')->orderBy('id', 'ASC')->first();
        if (!empty($wh)){
            $this->db->query('UPDATE `sales_invoices` SET `warehouse_id` = ? WHERE `warehouse_id` IS NULL',
                             [(int) $wh['id']]);
        }
        $this->run("ALTER TABLE `sales_invoices` DROP FOREIGN KEY `fk_inv_warehouse`");
        $this->run("ALTER TABLE `sales_invoices` MODIFY `warehouse_id` INT(11) NOT NULL");
        $this->run("ALTER TABLE `sales_invoices`
                    ADD CONSTRAINT `fk_inv_warehouse`
                    FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)");
    }
};

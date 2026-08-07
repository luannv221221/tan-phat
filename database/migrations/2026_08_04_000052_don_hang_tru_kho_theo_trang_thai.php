<?php
/**
 * ĐƠN HÀNG KHÁCH ĐẶT — trừ/cộng kho theo TRẠNG THÁI.
 *
 * Chốt 04/08/2026: hoá đơn bán tại quầy trừ kho ngay lúc ghi sổ (giữ nguyên),
 * còn đơn khách đặt trên web thì "Hoàn thành" mới trừ, "Hoàn hàng" thì cộng lại.
 *
 * Ba cột thêm vào, mỗi cột giải một bài toán cụ thể:
 *
 *   orders.stock_applied  — CHỐNG TRỪ HAI LẦN. Trạng thái bấm qua bấm lại được
 *     (Hoàn thành -> Đang giao -> Hoàn thành), không có cờ này thì mỗi lần bấm
 *     lại trừ thêm một lượt. Đây là lỗi kinh điển của kiểu gắn kho vào trạng thái.
 *
 *   orders.warehouse_id   — TRỪ Ở KHO NÀO. Bảng orders vốn không có kho; lấy kho
 *     mặc định lúc trừ rồi ghi lại đây, để lúc hoàn hàng cộng đúng về kho đó
 *     chứ không phải kho mặc định tại thời điểm hoàn (kho mặc định có thể đã đổi).
 *
 *   order_items.unit_cost / cost_amount — GIÁ VỐN LÚC XUẤT. Hoàn hàng mà cộng lại
 *     bằng giá bán hoặc bằng bình quân hiện thời là phá bình quân gia quyền của
 *     cả mã hàng. Phải chốt đúng con số đã trừ đi rồi trả lại đúng con số đó.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $this->run("ALTER TABLE `orders`
                    ADD COLUMN `warehouse_id` INT(11) DEFAULT NULL AFTER `status`,
                    ADD COLUMN `stock_applied` TINYINT(1) NOT NULL DEFAULT 0 AFTER `warehouse_id`");

        $this->run("ALTER TABLE `orders`
                    ADD CONSTRAINT `fk_orders_warehouse`
                    FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE");

        $this->run("ALTER TABLE `order_items`
                    ADD COLUMN `unit_cost` DECIMAL(15,2) DEFAULT NULL AFTER `unit_price`,
                    ADD COLUMN `cost_amount` DECIMAL(15,2) DEFAULT NULL AFTER `amount`");
    }

    public function down(){
        $this->run("ALTER TABLE `orders` DROP FOREIGN KEY `fk_orders_warehouse`");
        $this->run("ALTER TABLE `orders` DROP COLUMN `stock_applied`, DROP COLUMN `warehouse_id`");
        $this->run("ALTER TABLE `order_items` DROP COLUMN `unit_cost`, DROP COLUMN `cost_amount`");
    }
};

<?php
/**
 * Tách địa chỉ đơn hàng thành tỉnh/thành + phường/xã + địa chỉ cụ thể.
 *
 * Trước đây `orders.address` là một ô chữ tự do — không lọc được đơn theo khu
 * vực, không đối chiếu được với đơn vị vận chuyển.
 *
 * Theo cơ cấu hành chính sau sáp nhập 2025: 34 tỉnh/thành, BỎ cấp quận/huyện,
 * dưới tỉnh là phường/xã. Nên chỉ cần 2 cấp, không có cột quận/huyện.
 *
 * Lưu cả mã lẫn tên:
 *   - mã  : ổn định, dùng để đối chiếu / tích hợp về sau
 *   - tên : chốt tại thời điểm đặt hàng, để đơn cũ không đổi nội dung khi
 *           danh mục hành chính thay đổi lần nữa
 *
 * `address` giữ nguyên, nay mang nghĩa "địa chỉ cụ thể" (số nhà, đường).
 * Đơn cũ không có 4 cột này -> NULL, view tự lùi về hiển thị mỗi `address`.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $this->run("
            ALTER TABLE `orders`
                ADD COLUMN `province_code` INT(11)      DEFAULT NULL AFTER `address`,
                ADD COLUMN `province_name` VARCHAR(150) DEFAULT NULL AFTER `province_code`,
                ADD COLUMN `ward_code`     INT(11)      DEFAULT NULL AFTER `province_name`,
                ADD COLUMN `ward_name`     VARCHAR(150) DEFAULT NULL AFTER `ward_code`
        ");

        // Lọc đơn theo tỉnh là truy vấn hay dùng nhất khi chia đơn cho kho/vận chuyển
        $this->run("ALTER TABLE `orders` ADD KEY `idx_orders_province` (`province_code`)");
    }

    public function down(){
        $this->run("ALTER TABLE `orders` DROP KEY `idx_orders_province`");
        $this->run("
            ALTER TABLE `orders`
                DROP COLUMN `province_code`,
                DROP COLUMN `province_name`,
                DROP COLUMN `ward_code`,
                DROP COLUMN `ward_name`
        ");
    }
};

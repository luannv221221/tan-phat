<?php
/**
 * TÁCH "hiển thị website" RA KHỎI "đang kinh doanh".
 *
 * Khách chốt 05/08/2026: "Sản phẩm website" KHÔNG phải nhóm hàng thứ 4, mà là
 * thuộc tính "được đăng website" — tức là có mặt hàng vẫn kinh doanh bình
 * thường nhưng không đăng lên web.
 *
 * Trước đây `parts.status` gánh cả hai việc:
 *   - gác website  : applyStorefront, getBySlugFull, getByCarYear, getByModel
 *   - gác admin    : getForSelect (ô chọn hàng của hoá đơn / báo giá / kho)
 *
 * Nên chỉ đổi nhãn ô đó thành "Hiển thị website" là bẫy: người dùng tắt đi để
 * gỡ khỏi web thì mặt hàng biến mất luôn khỏi hoá đơn và phiếu kho.
 *
 *   status      -> "Đang kinh doanh"  (còn dùng trong hệ thống)
 *   show_on_web -> "Hiển thị website" (có đăng lên web hay không)
 *
 * Gieo show_on_web = status để hành vi hôm nay không đổi một chút nào.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $this->run("ALTER TABLE `parts`
                    ADD COLUMN `show_on_web` TINYINT(1) NOT NULL DEFAULT 1 AFTER `status`");

        // Hàng đang ẩn thì vẫn ẩn; hàng đang hiện thì vẫn hiện.
        $this->run("UPDATE `parts` SET `show_on_web` = `status`");

        // Website luôn lọc kèm cột này -> đánh index đôi cho khỏi quét bảng.
        $this->run("ALTER TABLE `parts` ADD KEY `idx_parts_web` (`status`, `show_on_web`)");
    }

    public function down(){
        $this->run("ALTER TABLE `parts` DROP INDEX `idx_parts_web`");
        $this->run("ALTER TABLE `parts` DROP COLUMN `show_on_web`");
    }
};

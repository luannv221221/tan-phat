<?php
/**
 * TỈNH / PHƯỜNG cho Đối tượng (khách + NCC) và Khách hàng CSKH.
 *
 * Trước đây địa chỉ là MỘT dòng chữ tự do ("12 Trần Phú, Hà Nội"), nên không
 * lọc được theo tỉnh, không thống kê được khách ở đâu, và mỗi người gõ một kiểu.
 *
 * Bốn cột giống hệt bảng `orders` (đã có từ trước): mã + TÊN của cả tỉnh và
 * phường. Lưu cả tên chứ không chỉ mã là cố ý:
 *   - đơn vị hành chính có thể đổi tên / sáp nhập lần nữa (2025 vừa gộp 63
 *     tỉnh thành 34 và bỏ cấp quận/huyện) — địa chỉ đã lưu phải đọc được
 *     đúng như lúc nhập;
 *   - API ngoài chết hay đổi mã thì bản in cũ vẫn ra đúng chữ.
 *
 * `address` giữ nguyên, từ nay là "số nhà, tên đường". KHÔNG tự tách 6 địa chỉ
 * cũ ra tỉnh/phường: đoán sai thì sai âm thầm, để người dùng tự sửa khi cần.
 *
 * Không bắt buộc: NCC nước ngoài hoặc dữ liệu cũ vẫn lưu được với 4 cột NULL.
 */

use App\core\Migration;

return new class extends Migration {

    const BANG = ['partners', 'members'];

    public function up(){
        foreach (self::BANG as $b){
            $this->run("ALTER TABLE `$b`
                        ADD COLUMN `province_code` INT DEFAULT NULL AFTER `address`,
                        ADD COLUMN `province_name` VARCHAR(150) DEFAULT NULL AFTER `province_code`,
                        ADD COLUMN `ward_code` INT DEFAULT NULL AFTER `province_name`,
                        ADD COLUMN `ward_name` VARCHAR(150) DEFAULT NULL AFTER `ward_code`,
                        ADD KEY `idx_{$b}_province` (`province_code`)");
        }
    }

    public function down(){
        foreach (self::BANG as $b){
            $this->run("ALTER TABLE `$b`
                        DROP KEY `idx_{$b}_province`,
                        DROP COLUMN `province_code`,
                        DROP COLUMN `province_name`,
                        DROP COLUMN `ward_code`,
                        DROP COLUMN `ward_name`");
        }
    }
};

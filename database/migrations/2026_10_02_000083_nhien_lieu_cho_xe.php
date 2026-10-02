<?php
/**
 * NHIÊN LIỆU CỦA XE — thêm cột `vehicles`.`fuel_id`.
 *
 * Danh mục `car_fuels` (Xăng / Dầu / Điện / Hybrid) đã có từ lâu và có màn
 * quản trị riêng, nhưng KHÔNG chỗ nào gắn được nhiên liệu vào một chiếc xe —
 * danh mục đứng không. Nay form Thêm / Sửa xe có ô chọn nhiên liệu.
 *
 * Làm GIỐNG HỆT `color_id` (migration 000081): khoá ngoại, cho để trống,
 * ON DELETE SET NULL — xoá một dòng nhiên liệu khỏi danh mục thì xe mất
 * nhiên liệu chứ không chặn việc xoá và cũng không làm hỏng bản ghi xe.
 *
 * KHÔNG có cột chữ gõ tay kèm theo, khác với hang_xe / mau_xe ngày trước:
 * theo đúng luật "ô nào mang giá trị lặp lại thì phải CHỌN từ danh mục".
 * Thiếu loại nhiên liệu thì bấm nút + ngay trên form.
 *
 * Không có dữ liệu cũ nào phải dồn: trước migration này chưa từng có chỗ ghi
 * nhiên liệu của xe.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $this->themCot('vehicles', 'fuel_id',
            "ALTER TABLE `vehicles` ADD COLUMN `fuel_id` INT NULL AFTER `color_id`,
             ADD KEY `fk_vehicles_fuel` (`fuel_id`),
             ADD CONSTRAINT `fk_vehicles_fuel` FOREIGN KEY (`fuel_id`)
                 REFERENCES `car_fuels` (`id`) ON DELETE SET NULL");

        echo "  Da them `vehicles`.`fuel_id` (noi vao danh muc car_fuels).\n";
    }

    public function down(){
        if (!$this->coCot('vehicles', 'fuel_id')) return;
        $this->run("ALTER TABLE `vehicles` DROP FOREIGN KEY `fk_vehicles_fuel`");
        $this->run("ALTER TABLE `vehicles` DROP COLUMN `fuel_id`");
        echo "  Da go `vehicles`.`fuel_id`.\n";
    }

    /* themCot / coCot KHÔNG có sẵn ở core\Migration — migration 000081 cũng tự
       khai hai hàm này. Giữ nguyên cách đó để chạy lại không nổ. */
    private function themCot($bang, $cot, $sql){
        if (!$this->hasTable($bang)) return;
        if ($this->coCot($bang, $cot)){ echo "  `$bang`.`$cot` da co san.\n"; return; }
        $this->run($sql);
    }

    private function coCot($bang, $cot){
        try {
            $this->db->query("SELECT `$cot` FROM `$bang` LIMIT 1");
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
};

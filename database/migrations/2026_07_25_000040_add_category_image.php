<?php
/**
 * Thêm cột `image` cho danh mục phụ tùng (part_categories).
 * Dùng cho lưới "Danh mục sản phẩm" ngoài trang chủ storefront.
 * Lưu đường dẫn tương đối (public/assets/uploads/categories/<file>).
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        // Bỏ qua nếu cột đã có: trên CSDL bàn giao cột này tồn tại nhưng lại
        // KHÔNG được ghi vào bảng `migrations`, khiến `php migrate.php` chết
        // giữa chừng với lỗi "Duplicate column name 'image'".
        if ($this->hasColumn('part_categories', 'image')) return;

        $this->run("ALTER TABLE `part_categories`
            ADD COLUMN `image` VARCHAR(255) DEFAULT NULL AFTER `slug`");
    }

    public function down(){
        if (!$this->hasColumn('part_categories', 'image')) return;

        $this->run("ALTER TABLE `part_categories` DROP COLUMN `image`");
    }

    protected function hasColumn($table, $column){
        $r = $this->db->firstRaw(
            "SELECT COUNT(*) AS c FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $column]);
        return !empty($r['c']);
    }
};

<?php
/**
 * Thêm cột `image` cho danh mục phụ tùng (part_categories).
 * Dùng cho lưới "Danh mục sản phẩm" ngoài trang chủ storefront.
 * Lưu đường dẫn tương đối (public/assets/uploads/categories/<file>).
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $this->run("ALTER TABLE `part_categories`
            ADD COLUMN `image` VARCHAR(255) DEFAULT NULL AFTER `slug`");
    }

    public function down(){
        $this->run("ALTER TABLE `part_categories` DROP COLUMN `image`");
    }
};

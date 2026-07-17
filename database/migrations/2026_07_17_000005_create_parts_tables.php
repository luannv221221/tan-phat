<?php
/**
 * PHỤ TÙNG + LIÊN KẾT VỚI XE.
 *
 * Nguồn yêu cầu — sheet Tracking:
 *   TASK_86  tạo phụ tùng (phụ tùng theo xe)
 *   TASK_87  Chọn xe sẽ lọc ra các phụ tùng
 *   TASK_93  Tìm kiếm phụ tùng: theo dòng xe, model... và các trường thông tin khác
 * Và mục "DANH MỤC HÀNG HÓA" (dòng 35-47): Thương hiệu, Xuất xứ, Hãng sản xuất, Đơn vị.
 *
 * ⚠️ PHÂN BIỆT HAI KHÁI NIỆM "HÃNG" — rất dễ nhầm:
 *   - `car_brands`      = hãng XE       (Toyota, Honda)  -> xe nào
 *   - `product_brands`  = thương hiệu PHỤ TÙNG (Bosch, Denso, Aisin) -> ai làm ra món đồ
 *   Sheet liệt kê "Thương hiệu" và "Hãng sản xuất" thành 2 danh mục riêng nên giữ cả hai:
 *   thương hiệu là nhãn bán ra, hãng sản xuất là nơi gia công (có thể khác nhau).
 *
 * THIẾT KẾ LIÊN KẾT XE (quan trọng nhất):
 *   part_fitments nối phụ tùng <-> ĐỜI XE (car_years), không phải model.
 *   Vì cùng một model, đời khác nhau lắp phụ tùng khác nhau
 *   (Vios 2014-2017 và Vios 2018+ dùng lọc gió khác nhau).
 *   car_years đã mang sẵn model -> hãng, nên nối ở đây là mức chi tiết nhất mà vẫn suy ngược được.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){

        // ---------- Danh mục phụ tùng (có phân cấp) ----------
        // parent_id tự tham chiếu -> cây không giới hạn cấp.
        // Ví dụ: Hệ thống phanh > Má phanh > Má phanh trước
        $this->run("
            CREATE TABLE IF NOT EXISTS `part_categories` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `parent_id` INT(11) DEFAULT NULL,
                `name` VARCHAR(150) NOT NULL,
                `slug` VARCHAR(180) NOT NULL,
                `description` TEXT,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_part_categories_slug` (`slug`),
                KEY `idx_part_categories_parent` (`parent_id`),
                CONSTRAINT `fk_part_categories_parent`
                    FOREIGN KEY (`parent_id`) REFERENCES `part_categories` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Thương hiệu phụ tùng: Bosch, Denso... (KHÁC car_brands) ----------
        $this->run("
            CREATE TABLE IF NOT EXISTS `product_brands` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `slug` VARCHAR(120) NOT NULL,
                `logo` VARCHAR(255) DEFAULT NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_product_brands_slug` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Xuất xứ: Nhật Bản, Đức... ----------
        $this->run("
            CREATE TABLE IF NOT EXISTS `product_origins` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `slug` VARCHAR(120) NOT NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_product_origins_slug` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Hãng sản xuất (nơi gia công) ----------
        $this->run("
            CREATE TABLE IF NOT EXISTS `product_manufacturers` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(150) NOT NULL,
                `slug` VARCHAR(180) NOT NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_product_manufacturers_slug` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Đơn vị tính: cái, bộ, lít... ----------
        $this->run("
            CREATE TABLE IF NOT EXISTS `product_units` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(50) NOT NULL,
                `slug` VARCHAR(60) NOT NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_product_units_slug` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Phụ tùng ----------
        // DECIMAL(15,2) cho tiền — KHONG dung FLOAT/DOUBLE:
        // số thực nhị phân không biểu diễn chính xác được số thập phân,
        // cộng dồn nhiều dòng sẽ lệch tiền. Kế toán không chấp nhận.
        $this->run("
            CREATE TABLE IF NOT EXISTS `parts` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `code` VARCHAR(80) NOT NULL,
                `oem_code` VARCHAR(80) DEFAULT NULL,
                `name` VARCHAR(255) NOT NULL,
                `slug` VARCHAR(280) NOT NULL,
                `category_id` INT(11) DEFAULT NULL,
                `brand_id` INT(11) DEFAULT NULL,
                `manufacturer_id` INT(11) DEFAULT NULL,
                `origin_id` INT(11) DEFAULT NULL,
                `unit_id` INT(11) DEFAULT NULL,
                `price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `sale_price` DECIMAL(15,2) DEFAULT NULL,
                `warranty_month` SMALLINT(3) DEFAULT NULL,
                `description` TEXT,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_parts_code` (`code`),
                UNIQUE KEY `uq_parts_slug` (`slug`),
                KEY `idx_parts_category` (`category_id`),
                KEY `idx_parts_brand` (`brand_id`),
                KEY `idx_parts_status` (`status`),
                KEY `idx_parts_oem` (`oem_code`),
                CONSTRAINT `fk_parts_category`
                    FOREIGN KEY (`category_id`) REFERENCES `part_categories` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_parts_brand`
                    FOREIGN KEY (`brand_id`) REFERENCES `product_brands` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_parts_manufacturer`
                    FOREIGN KEY (`manufacturer_id`) REFERENCES `product_manufacturers` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_parts_origin`
                    FOREIGN KEY (`origin_id`) REFERENCES `product_origins` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_parts_unit`
                    FOREIGN KEY (`unit_id`) REFERENCES `product_units` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Phụ tùng lắp cho đời xe nào (TASK_86, TASK_87) ----------
        // Quan hệ nhiều-nhiều: 1 phụ tùng lắp nhiều đời xe, 1 đời xe có nhiều phụ tùng.
        // CASCADE cả 2 phía: xoá phụ tùng hoặc xoá đời xe thì dòng liên kết vô nghĩa.
        $this->run("
            CREATE TABLE IF NOT EXISTS `part_fitments` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `part_id` INT(11) NOT NULL,
                `car_year_id` INT(11) NOT NULL,
                `note` VARCHAR(255) DEFAULT NULL,
                `create_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_fitment` (`part_id`, `car_year_id`),
                KEY `idx_fitment_car_year` (`car_year_id`),
                CONSTRAINT `fk_fitment_part`
                    FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_fitment_car_year`
                    FOREIGN KEY (`car_year_id`) REFERENCES `car_years` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Dữ liệu mồi ----------
        $now = date('Y-m-d H:i:s');

        $units = ['Cái' => 'cai', 'Bộ' => 'bo', 'Chiếc' => 'chiec',
                  'Lít' => 'lit', 'Hộp' => 'hop', 'Mét' => 'met'];
        $i = 0;
        foreach ($units as $name => $slug){
            $this->db->insert('product_units', [
                'name' => $name, 'slug' => $slug, 'sort_order' => $i++,
                'status' => 1, 'create_at' => $now,
            ]);
        }

        $origins = ['Nhật Bản' => 'nhat-ban', 'Đức' => 'duc', 'Hàn Quốc' => 'han-quoc',
                    'Thái Lan' => 'thai-lan', 'Trung Quốc' => 'trung-quoc', 'Việt Nam' => 'viet-nam'];
        $i = 0;
        foreach ($origins as $name => $slug){
            $this->db->insert('product_origins', [
                'name' => $name, 'slug' => $slug, 'sort_order' => $i++,
                'status' => 1, 'create_at' => $now,
            ]);
        }

        $brands = ['Bosch' => 'bosch', 'Denso' => 'denso', 'Aisin' => 'aisin',
                   'NGK' => 'ngk', 'Mann Filter' => 'mann-filter', 'Toyota Genuine' => 'toyota-genuine'];
        $i = 0;
        foreach ($brands as $name => $slug){
            $this->db->insert('product_brands', [
                'name' => $name, 'slug' => $slug, 'sort_order' => $i++,
                'status' => 1, 'create_at' => $now,
            ]);
        }

        // Danh mục phụ tùng 2 cấp mồi
        $roots = [
            'Hệ thống phanh' => ['he-thong-phanh', ['Má phanh' => 'ma-phanh', 'Đĩa phanh' => 'dia-phanh', 'Dầu phanh' => 'dau-phanh']],
            'Động cơ'        => ['dong-co', ['Lọc dầu' => 'loc-dau', 'Lọc gió' => 'loc-gio', 'Bugi' => 'bugi', 'Dây curoa' => 'day-curoa']],
            'Hệ thống điện'  => ['he-thong-dien', ['Ắc quy' => 'ac-quy', 'Đèn' => 'den-xe', 'Máy phát' => 'may-phat']],
            'Hệ thống treo'  => ['he-thong-treo', ['Giảm xóc' => 'giam-xoc', 'Lò xo' => 'lo-xo']],
        ];

        $sort = 0;
        foreach ($roots as $rootName => $cfg){
            $this->db->insert('part_categories', [
                'parent_id' => null, 'name' => $rootName, 'slug' => $cfg[0],
                'sort_order' => $sort++, 'status' => 1, 'create_at' => $now,
            ]);
            $parentId = $this->db->lastId();

            $childSort = 0;
            foreach ($cfg[1] as $childName => $childSlug){
                $this->db->insert('part_categories', [
                    'parent_id' => $parentId, 'name' => $childName, 'slug' => $childSlug,
                    'sort_order' => $childSort++, 'status' => 1, 'create_at' => $now,
                ]);
            }
        }
    }

    public function down(){
        $this->run("DROP TABLE IF EXISTS `part_fitments`");
        $this->run("DROP TABLE IF EXISTS `parts`");
        $this->run("DROP TABLE IF EXISTS `part_categories`");
        $this->run("DROP TABLE IF EXISTS `product_units`");
        $this->run("DROP TABLE IF EXISTS `product_manufacturers`");
        $this->run("DROP TABLE IF EXISTS `product_origins`");
        $this->run("DROP TABLE IF EXISTS `product_brands`");
    }
};

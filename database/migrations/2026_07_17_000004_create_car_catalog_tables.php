<?php
/**
 * CÂY DANH MỤC XE — nền tảng của toàn bộ hệ thống.
 *
 * Nguồn yêu cầu: sheet Tracking, mục "DANH MỤC XE, HÃNG XE" (dòng 27-34):
 *     Hãng xe (toyota, honda..)
 *     Dòng xe (hackback, sedan..)
 *     Model xe (Morning, vios..)
 *     Đời xe (năm sản xuất)
 *     Model xe (Morning, vios..)     <- LẶP LẠI (dòng r32 trùng r30 trong file gốc)
 *     Nhiên liệu (động cơ xe)
 *     Màu xe
 *
 * ✅ ĐÃ CHỐT (17/07/2026): "Dòng xe" = KIỂU DÁNG THÂN XE (hatchback, sedan, SUV...),
 *   khớp với ví dụ "hackback, sedan" trong file. Xem CAY_DANH_MUC_XE.md mục 1.
 *
 * Vì sao cây này quan trọng (khuyến nghị #9 trong SRS):
 *   - Phụ tùng tham chiếu tới nó ("tạo phụ tùng theo xe" — TASK_86)
 *   - Bộ lọc website dựa vào nó ("Chọn xe sẽ lọc ra các phụ tùng" — TASK_87)
 *   - Tìm kiếm phụ tùng theo dòng xe, model (TASK_93)
 *   Sai ở đây phải sửa lại toàn hệ thống.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){

        // ---------- Hãng xe: Toyota, Honda... ----------
        $this->run("
            CREATE TABLE IF NOT EXISTS `car_brands` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `slug` VARCHAR(120) NOT NULL,
                `logo` VARCHAR(255) DEFAULT NULL,
                `country` VARCHAR(100) DEFAULT NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_car_brands_slug` (`slug`),
                KEY `idx_car_brands_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Dòng xe = kiểu dáng thân xe: hatchback, sedan... (đã chốt) ----------
        $this->run("
            CREATE TABLE IF NOT EXISTS `car_body_types` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `slug` VARCHAR(120) NOT NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_car_body_types_slug` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Nhiên liệu: xăng, dầu, điện, hybrid ----------
        $this->run("
            CREATE TABLE IF NOT EXISTS `car_fuels` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `slug` VARCHAR(120) NOT NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_car_fuels_slug` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Màu xe ----------
        $this->run("
            CREATE TABLE IF NOT EXISTS `car_colors` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `slug` VARCHAR(120) NOT NULL,
                `hex` VARCHAR(7) DEFAULT NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_car_colors_slug` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Model xe: Vios, Morning... thuộc 1 hãng ----------
        // ON DELETE RESTRICT: không cho xoá hãng khi còn model — tránh mồ côi dữ liệu.
        // body_type_id cho NULL vì dữ liệu cũ có thể chưa phân loại kiểu dáng.
        $this->run("
            CREATE TABLE IF NOT EXISTS `car_models` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `brand_id` INT(11) NOT NULL,
                `body_type_id` INT(11) DEFAULT NULL,
                `name` VARCHAR(100) NOT NULL,
                `slug` VARCHAR(120) NOT NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_car_models_brand_slug` (`brand_id`, `slug`),
                KEY `idx_car_models_brand` (`brand_id`),
                KEY `idx_car_models_body` (`body_type_id`),
                CONSTRAINT `fk_car_models_brand`
                    FOREIGN KEY (`brand_id`) REFERENCES `car_brands` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk_car_models_body`
                    FOREIGN KEY (`body_type_id`) REFERENCES `car_body_types` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Đời xe: năm sản xuất, thuộc 1 model ----------
        // Đời xe gắn với model chứ không phải danh sách năm rời rạc:
        // "Vios 2018" mới có nghĩa, còn "2018" đứng một mình thì không lọc được phụ tùng.
        $this->run("
            CREATE TABLE IF NOT EXISTS `car_years` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `model_id` INT(11) NOT NULL,
                `year_from` SMALLINT(4) NOT NULL,
                `year_to` SMALLINT(4) DEFAULT NULL,
                `name` VARCHAR(100) DEFAULT NULL,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_car_years_model` (`model_id`),
                KEY `idx_car_years_range` (`year_from`, `year_to`),
                CONSTRAINT `fk_car_years_model`
                    FOREIGN KEY (`model_id`) REFERENCES `car_models` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Dữ liệu mồi ----------
        $now = date('Y-m-d H:i:s');

        $brands = [
            ['Toyota', 'toyota', 'Nhật Bản'],
            ['Honda', 'honda', 'Nhật Bản'],
            ['Kia', 'kia', 'Hàn Quốc'],
            ['Hyundai', 'hyundai', 'Hàn Quốc'],
            ['Mazda', 'mazda', 'Nhật Bản'],
            ['Ford', 'ford', 'Mỹ'],
        ];
        foreach ($brands as $i => $b){
            $this->db->insert('car_brands', [
                'name' => $b[0], 'slug' => $b[1], 'country' => $b[2],
                'sort_order' => $i, 'status' => 1, 'create_at' => $now,
            ]);
        }

        $bodies = ['Sedan' => 'sedan', 'Hatchback' => 'hatchback', 'SUV' => 'suv',
                   'Crossover' => 'crossover', 'MPV' => 'mpv', 'Bán tải' => 'ban-tai'];
        $i = 0;
        foreach ($bodies as $name => $slug){
            $this->db->insert('car_body_types', [
                'name' => $name, 'slug' => $slug, 'sort_order' => $i++,
                'status' => 1, 'create_at' => $now,
            ]);
        }

        $fuels = ['Xăng' => 'xang', 'Dầu (Diesel)' => 'dau-diesel', 'Điện' => 'dien',
                  'Hybrid' => 'hybrid'];
        $i = 0;
        foreach ($fuels as $name => $slug){
            $this->db->insert('car_fuels', [
                'name' => $name, 'slug' => $slug, 'sort_order' => $i++,
                'status' => 1, 'create_at' => $now,
            ]);
        }

        $colors = [
            ['Trắng', 'trang', '#FFFFFF'], ['Đen', 'den', '#000000'],
            ['Bạc', 'bac', '#C0C0C0'],     ['Xám', 'xam', '#808080'],
            ['Đỏ', 'do', '#FF0000'],       ['Xanh', 'xanh', '#0000FF'],
        ];
        foreach ($colors as $i => $c){
            $this->db->insert('car_colors', [
                'name' => $c[0], 'slug' => $c[1], 'hex' => $c[2],
                'sort_order' => $i, 'status' => 1, 'create_at' => $now,
            ]);
        }
    }

    public function down(){
        // Thứ tự ngược: xoá bảng con trước (vì có khoá ngoại)
        $this->run("DROP TABLE IF EXISTS `car_years`");
        $this->run("DROP TABLE IF EXISTS `car_models`");
        $this->run("DROP TABLE IF EXISTS `car_colors`");
        $this->run("DROP TABLE IF EXISTS `car_fuels`");
        $this->run("DROP TABLE IF EXISTS `car_body_types`");
        $this->run("DROP TABLE IF EXISTS `car_brands`");
    }
};

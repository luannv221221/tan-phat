<?php
/**
 * CHỈ ĐƯỢC CHỌN, KHÔNG ĐƯỢC GÕ TAY.
 *
 * Trước migration này, nhiều ô trên form cho gõ tay một giá trị mà lẽ ra phải
 * lấy từ danh mục đã thiết lập. Hậu quả: mỗi người ghi một kiểu nên lọc và
 * thống kê không gom được — "Toyota" với "toyota " là hai thứ khác nhau.
 *
 * Số liệu thật lúc viết migration:
 *   - 10 xe trong CSDL thì 6 xe ghi hãng / model / năm bằng chữ gõ tay,
 *     KHÔNG xe nào dùng brand_id / model_id / car_year_id.
 *   - 6 xe ghi màu bằng chữ, trong khi danh mục car_colors đã có sẵn 6 màu.
 *   - 3/3 phiếu tiếp nhận gõ tên cố vấn, không phiếu nào dùng co_van_id.
 *   - 7/13 phiếu bảo hành gõ tên kỹ thuật viên.
 *
 * Migration này làm hai việc:
 *   1. Thêm cột khoá ngoại còn thiếu: vehicles.color_id, warranty_requests
 *      .technician_id.
 *   2. Dồn chữ gõ tay cũ vào danh mục: tìm trong danh mục, không có thì thêm
 *      mới — đối chiếu bằng SLUG nên "MITSUBISHI", "Mitsubishi" và
 *      "mitsubishi " đều về cùng một dòng, không sinh dòng trùng.
 *
 * CỘT CHỮ CŨ (hang_xe, model_xe, nam_sx, phien_ban, mau_xe, co_van,
 * technician) KHÔNG bị xoá ở bước này — cố ý. Đẩy code và chạy migration
 * không bao giờ khít nhau tuyệt đối; xoá cột mà code cũ còn đọc là sập trang.
 * Code sau migration này thôi ghi vào các cột đó. Xoá hẳn để dành cho một
 * migration dọn dẹp sau, khi code mới đã chạy ổn trên máy chủ.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $this->themCot('vehicles', 'color_id',
            "ALTER TABLE `vehicles` ADD COLUMN `color_id` INT NULL AFTER `mau_xe`,
             ADD KEY `fk_vehicles_color` (`color_id`),
             ADD CONSTRAINT `fk_vehicles_color` FOREIGN KEY (`color_id`)
                 REFERENCES `car_colors` (`id`) ON DELETE SET NULL");

        $this->themCot('warranty_requests', 'technician_id',
            "ALTER TABLE `warranty_requests` ADD COLUMN `technician_id` INT NULL AFTER `technician`,
             ADD KEY `fk_wr_technician` (`technician_id`),
             ADD CONSTRAINT `fk_wr_technician` FOREIGN KEY (`technician_id`)
                 REFERENCES `users` (`id`) ON DELETE SET NULL");

        $this->donXe();
        $this->donNguoi();
    }

    /**
     * Chỉ gỡ hai cột vừa thêm. Chữ gõ tay cũ vẫn nằm nguyên ở cột cũ nên
     * rollback không mất gì.
     */
    public function down(){
        foreach ([['vehicles', 'color_id', 'fk_vehicles_color'],
                  ['warranty_requests', 'technician_id', 'fk_wr_technician']] as [$bang, $cot, $fk]){
            if (!$this->coCot($bang, $cot)) continue;
            $this->run("ALTER TABLE `$bang` DROP FOREIGN KEY `$fk`");
            $this->run("ALTER TABLE `$bang` DROP COLUMN `$cot`");
            echo "  Da go `$bang`.`$cot`.\n";
        }
    }

    /* ---------------------------------------------------------------- xe */

    /** Hãng / model / năm / màu gõ tay -> id trong danh mục. */
    private function donXe(){
        $xeDs = $this->lay(
            "SELECT id, hang_xe, model_xe, nam_sx, mau_xe, brand_id, model_id, car_year_id
               FROM vehicles
              WHERE (brand_id IS NULL AND COALESCE(hang_xe,'') <> '')
                 OR (model_id IS NULL AND COALESCE(model_xe,'') <> '')
                 OR (car_year_id IS NULL AND COALESCE(nam_sx,'') <> '')
                 OR (color_id IS NULL AND COALESCE(mau_xe,'') <> '')");

        if (!$xeDs){ echo "  Khong co xe nao can don.\n"; return; }

        foreach ($xeDs as $xe){
            $dat = [];

            $brandId = (int) $xe['brand_id'] ?: $this->timHoacTao('car_brands', $xe['hang_xe']);
            if ($brandId && !$xe['brand_id']) $dat['brand_id'] = $brandId;

            $modelId = (int) $xe['model_id'];
            if (!$modelId && $brandId)
                $modelId = $this->timHoacTao('car_models', $xe['model_xe'], ['brand_id' => $brandId]);
            if ($modelId && !$xe['model_id']) $dat['model_id'] = $modelId;

            if (!$xe['car_year_id'] && $modelId && (int) $xe['nam_sx']){
                $namId = $this->namCuaModel($modelId, (int) $xe['nam_sx']);
                if ($namId) $dat['car_year_id'] = $namId;
            }

            $mauId = $this->timHoacTao('car_colors', $xe['mau_xe']);
            if ($mauId) $dat['color_id'] = $mauId;

            if ($dat) $this->capNhat('vehicles', $dat, (int) $xe['id']);
        }

        echo "  Da don " . count($xeDs) . " xe vao danh muc.\n";
    }

    /** Năm sản xuất nằm trong khoảng nào của model, chưa có thì mở khoảng mới. */
    private function namCuaModel($modelId, $nam){
        if ($nam < 1950 || $nam > 2100) return 0;

        $co = $this->lay("SELECT id FROM car_years
                           WHERE model_id = ? AND ? BETWEEN year_from AND COALESCE(year_to, year_from)
                           LIMIT 1", [$modelId, $nam]);
        if ($co) return (int) $co[0]['id'];

        $this->db->query(
            "INSERT INTO car_years (model_id, year_from, year_to, name, status, create_at)
             VALUES (?, ?, ?, ?, 1, NOW())", [$modelId, $nam, $nam, (string) $nam]);

        return (int) $this->db->lastId();
    }

    /* ------------------------------------------------------------- người */

    /** Tên cố vấn / kỹ thuật viên gõ tay -> id nhân viên, khớp trong cùng gara. */
    private function donNguoi(){
        $viec = [
            ['receptions',         'co_van',     'co_van_id'],
            ['warranty_requests',  'technician', 'technician_id'],
        ];

        foreach ($viec as [$bang, $cotChu, $cotId]){
            if (!$this->hasTable($bang) || !$this->coCot($bang, $cotId)) continue;

            $ds = $this->lay("SELECT id, `$cotChu` AS ten, garage_id FROM `$bang`
                               WHERE `$cotId` IS NULL AND COALESCE(`$cotChu`,'') <> ''");
            $khop = 0;
            foreach ($ds as $d){
                $ai = $this->lay(
                    "SELECT id FROM users
                      WHERE garage_id <=> ? AND LOWER(TRIM(name)) = LOWER(TRIM(?)) LIMIT 1",
                    [$d['garage_id'], $d['ten']]);
                if (!$ai) continue;
                $this->capNhat($bang, [$cotId => (int) $ai[0]['id']], (int) $d['id']);
                $khop++;
            }
            echo "  `$bang`.$cotChu: khop duoc $khop / " . count($ds) . " dong.\n";
        }
    }

    /* -------------------------------------------------------------- chung */

    /**
     * Tìm trong danh mục theo SLUG, không có thì thêm dòng mới.
     *
     * Đối chiếu bằng slug là chỗ chặn trùng: "MITSUBISHI", "Mitsubishi" và
     * "mitsubishi " cùng ra slug `mitsubishi` nên dùng lại đúng một dòng.
     */
    private function timHoacTao($bang, $ten, array $them = []){
        $ten = trim(preg_replace('/\s+/u', ' ', (string) $ten));
        if ($ten === '') return 0;

        $slug = $this->slug($ten);
        if ($slug === '') return 0;

        $dk = "slug = ?"; $bd = [$slug];
        if (isset($them['brand_id'])){ $dk .= " AND brand_id = ?"; $bd[] = $them['brand_id']; }

        $co = $this->lay("SELECT id FROM `$bang` WHERE $dk LIMIT 1", $bd);
        if ($co) return (int) $co[0]['id'];

        /* Slug phải là duy nhất trong bảng. Khác hãng mà trùng tên model
           (Mazda "CX-5" với hãng khác cũng "CX-5") thì gắn thêm đuôi. */
        if (isset($them['brand_id'])){
            $dung = $this->lay("SELECT id FROM `$bang` WHERE slug = ? LIMIT 1", [$slug]);
            if ($dung) $slug .= '-' . $them['brand_id'];
        }

        $cot = ['name' => $ten, 'slug' => $slug];
        foreach ($them as $k => $v) $cot[$k] = $v;
        $cot['status'] = 1;

        $ten_cot = implode(', ', array_map(function($c){ return "`$c`"; }, array_keys($cot)));
        $dau     = implode(', ', array_fill(0, count($cot), '?'));

        $this->db->query("INSERT INTO `$bang` ($ten_cot, create_at) VALUES ($dau, NOW())",
                         array_values($cot));

        return (int) $this->db->lastId();
    }

    /** Bỏ dấu tiếng Việt, còn lại chữ thường và gạch nối. */
    private function slug($str){
        if (function_exists('slugify')) return slugify($str);

        $str = mb_strtolower(trim($str), 'UTF-8');
        $bang = ['à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ',
                 'è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ','ì','í','ị','ỉ','ĩ',
                 'ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ',
                 'ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ','ỳ','ý','ỵ','ỷ','ỹ','đ'];
        $the  = ['a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
                 'e','e','e','e','e','e','e','e','e','e','e','i','i','i','i','i',
                 'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
                 'u','u','u','u','u','u','u','u','u','u','u','y','y','y','y','y','d'];
        $str = str_replace($bang, $the, $str);
        $str = preg_replace('/[^a-z0-9]+/', '-', $str);

        return trim($str, '-');
    }

    private function themCot($bang, $cot, $sql){
        if (!$this->hasTable($bang)) return;
        if ($this->coCot($bang, $cot)){ echo "  `$bang`.`$cot` da co san.\n"; return; }
        $this->run($sql);
        echo "  Da them `$bang`.`$cot`.\n";
    }

    private function coCot($bang, $cot){
        try {
            $this->db->query("SELECT `$cot` FROM `$bang` LIMIT 1");
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function lay($sql, array $bd = []){
        return $this->db->query($sql, $bd)->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function capNhat($bang, array $dat, $id){
        $dat_sql = implode(', ', array_map(function($c){ return "`$c` = ?"; }, array_keys($dat)));
        $this->db->query("UPDATE `$bang` SET $dat_sql WHERE id = ?",
                         array_merge(array_values($dat), [$id]));
    }
};

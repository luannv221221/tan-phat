<?php
/**
 * HÀNG HOÁ ĐẦY ĐỦ CHO GARA — gara khai hàng của mình như gara tổng khai hàng
 * của kho tổng.
 *
 * TRƯỚC: tám màn của nhóm Hàng hoá mang cờ `chi_tan_phat`, chỉ Tân Phát vào
 * được. Gara chỉ có màn "Danh mục của gara" — tick hàng kho tổng, đặt giá, và
 * một biểu mẫu GỌN để thêm hàng riêng: đúng bốn ô tên / loại / đơn vị / giá.
 * Đủ cho công thợ ("Thay dầu 30 phút"), không đủ cho một mặt hàng thật: không
 * khai được mã OEM, danh mục, thương hiệu, hãng sản xuất, xuất xứ, ảnh, thông
 * số kỹ thuật, bảo hành, lắp cho đời xe nào, có lên web hay không.
 *
 * SAU: gara dùng chính tám màn đó, trên hàng CỦA MÌNH.
 *
 *     Quản lý hàng hoá   `products`
 *     Dịch vụ            `services`
 *     Danh mục hàng hoá  `part-categories`
 *     Thông số kỹ thuật  `attributes`
 *     Thương hiệu        `product-brands`
 *     Xuất xứ            `product-origins`
 *     Hãng sản xuất      `product-manufacturers`
 *     Đơn vị tính        `product-units`
 *
 * SÁU BẢNG DANH MỤC ĐƯỢC THÊM `garage_id` theo kiểu CHUNG-VÀ-RIÊNG, giống y
 * `parts` từ migration 000065:
 *
 *     garage_id IS NULL -> dòng của danh mục tổng. MỌI GARA ĐỀU THẤY (để chọn
 *                          khi khai hàng), chỉ gara tổng SỬA.
 *     garage_id = X     -> dòng riêng của gara X.
 *
 * Vì sao không lọc cứng `garage_id = X` như các bảng chứng từ: gara mới mở sẽ
 * thấy một danh mục TRỐNG RỖNG — không chọn được "Phụ tùng", "Dịch vụ", "Cái",
 * "Bộ" nào cả, phải khai lại từ đầu mọi thứ trước khi thêm được một mặt hàng.
 *
 * CỘT `slug` GIỮ NGUYÊN DUY NHẤT TOÀN BẢNG, không đổi thành (garage_id, slug).
 * Hai lý do:
 *   - slug là địa chỉ trên website; hai dòng cùng slug thì tra theo slug trả về
 *     dòng nào là tuỳ thứ tự truy vấn.
 *   - MySQL coi các NULL là KHÁC NHAU, nên UNIQUE(garage_id, slug) không chặn
 *     được hai dòng danh mục tổng trùng slug — mất đúng cái ràng buộc đang có.
 * Đổi lại, trùng tên giữa hai gara là chuyện thường ("Bosch"), nên các model
 * tự thêm đuôi -2, -3 (Model::slugRanh) thay vì báo lỗi.
 *
 * HÀNG KHO TỔNG GARA KHÔNG SỬA ĐƯỢC — chốt 07/10/2026, giữ nguyên. Màn Quản lý
 * hàng hoá của một gara chỉ liệt kê hàng của chính gara đó; hàng kho tổng gara
 * nhận làm vẫn chọn và đặt giá ở màn "Danh mục của gara".
 */

use App\core\Migration;

return new class extends Migration {

    /** Bảng danh mục chung-và-riêng: bảng => tên khoá ngoại */
    const BANG = [
        'part_categories'    => 'fk_part_cat_garage',
        'part_brands'        => 'fk_part_brand_garage',
        'part_origins'       => 'fk_part_origin_garage',
        'part_units'         => 'fk_part_unit_garage',
        'part_manufacturers' => 'fk_part_mnf_garage',
        'part_attributes'    => 'fk_part_attr_garage',
    ];

    /** link màn => [quyền cho Manager, quyền cho Staff] */
    const QUYEN = [
        'products'              => [['view', 'add', 'edit', 'delete'], ['view']],
        'services'              => [['view', 'add', 'edit', 'delete'], ['view']],
        'part-categories'       => [['view', 'add', 'edit', 'delete'], ['view']],
        'attributes'            => [['view', 'add', 'edit', 'delete'], ['view']],
        'product-brands'        => [['view', 'add', 'edit', 'delete'], ['view']],
        'product-origins'       => [['view', 'add', 'edit', 'delete'], ['view']],
        'product-manufacturers' => [['view', 'add', 'edit', 'delete'], ['view']],
        'product-units'         => [['view', 'add', 'edit', 'delete'], ['view']],
    ];

    public function up(){
        // --- 1. `garage_id` cho sáu bảng danh mục ---
        foreach (self::BANG as $bang => $fk){
            if (!$this->hasTable($bang) || $this->hasColumn($bang, 'garage_id')) continue;
            $this->run("ALTER TABLE `$bang` ADD COLUMN `garage_id` INT DEFAULT NULL");
            $this->run("ALTER TABLE `$bang` ADD KEY `idx_{$bang}_garage` (`garage_id`)");
            /* RESTRICT như `parts`: xoá gara mà xoá luôn danh mục riêng của nó
               thì hàng hoá đang trỏ vào danh mục đó mất danh mục. SET NULL còn
               tệ hơn — danh mục riêng của một gara bỗng nhảy vào danh mục tổng
               và mọi gara khác đều thấy. */
            $this->run("ALTER TABLE `$bang` ADD CONSTRAINT `$fk` FOREIGN KEY (`garage_id`)
                        REFERENCES `garages` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE");
            echo "  Da them `$bang`.`garage_id` (NULL = danh muc tong).\n";
        }

        /* KHÔNG gán dòng cũ về gara tổng: NULL đã có nghĩa "danh mục tổng",
           đúng cái mọi dòng hiện có đang là. Gán về id gara tổng thì phải sửa
           thêm điều kiện đọc ở mọi nơi, mà không được gì. */

        // --- 2. Mở tám màn cho gara ---
        if ($this->hasColumn('modules', 'chi_tan_phat')){
            $dau = implode(',', array_fill(0, count(self::QUYEN), '?'));
            $this->db->query("UPDATE `modules` SET `chi_tan_phat` = 0 WHERE `link` IN ($dau)",
                             array_keys(self::QUYEN));
            echo "  Da mo " . count(self::QUYEN) . " man nhom Hang hoa cho moi gara.\n";
        }

        // --- 3. Cấp quyền cho MỌI nhóm Manager / Staff của MỌI gara ---
        $them = 0;
        foreach (self::QUYEN as $link => $bo){
            $m = $this->db->firstRaw("SELECT `id`, `chi_tan_phat` FROM `modules` WHERE `link` = ? LIMIT 1", [$link]);
            if (empty($m['id'])){ echo "  Khong co module `$link` — bo qua.\n"; continue; }
            if (!empty($m['chi_tan_phat'])){
                echo "  CANH BAO: `$link` van mang co chi_tan_phat — cap quyen khong co tac dung.\n";
            }
            $them += $this->cap((int) $m['id'], 'Manager', $bo[0]);
            $them += $this->cap((int) $m['id'], 'Staff',   $bo[1]);
            echo "  `$link`: Manager " . implode('/', $bo[0]) . ", Staff " . implode('/', $bo[1]) . "\n";
        }
        echo "  Da them $them dong quyen.\n";
    }

    public function down(){
        if ($this->hasColumn('modules', 'chi_tan_phat')){
            $dau = implode(',', array_fill(0, count(self::QUYEN), '?'));
            $this->db->query("UPDATE `modules` SET `chi_tan_phat` = 1 WHERE `link` IN ($dau)",
                             array_keys(self::QUYEN));
        }

        /* Gỡ quyền của các nhóm CỦA GARA, giữ nguyên quyền nhóm hệ thống
           (Admin) — nó vốn có từ trước migration này. */
        foreach (array_keys(self::QUYEN) as $link){
            $m = $this->db->firstRaw("SELECT `id` FROM `modules` WHERE `link` = ? LIMIT 1", [$link]);
            if (empty($m['id'])) continue;
            $this->db->query(
                "DELETE p FROM `permissions` p JOIN `groups` g ON g.`id` = p.`group_id`
                  WHERE p.`module_id` = ? AND g.`garage_id` IS NOT NULL", [(int) $m['id']]);
        }

        foreach (self::BANG as $bang => $fk){
            if (!$this->hasTable($bang) || !$this->hasColumn($bang, 'garage_id')) continue;
            try { $this->run("ALTER TABLE `$bang` DROP FOREIGN KEY `$fk`"); } catch (\Throwable $e){}
            try { $this->run("ALTER TABLE `$bang` DROP KEY `idx_{$bang}_garage`"); } catch (\Throwable $e){}
            $this->run("ALTER TABLE `$bang` DROP COLUMN `garage_id`");
        }
        echo "  Da dong lai nhom man Hang hoa.\n";
    }

    // ===== Helper =====

    /** Cấp $roles trên module $moduleId cho MỌI nhóm tên $tenNhom. Trả về số dòng thêm được. */
    private function cap($moduleId, $tenNhom, array $roles){
        $them = 0;
        foreach ((array) $this->db->getRaw("SELECT `id` FROM `groups` WHERE `name` = ?", [$tenNhom]) as $n){
            foreach ($roles as $role){
                $co = $this->db->firstRaw("SELECT `id` FROM `permissions`
                                            WHERE `group_id` = ? AND `module_id` = ? AND `role` = ? LIMIT 1",
                                          [(int) $n['id'], $moduleId, $role]);
                if (!empty($co['id'])) continue;
                $this->db->insert('permissions', [
                    'group_id' => (int) $n['id'], 'module_id' => $moduleId, 'role' => $role,
                ]);
                $them++;
            }
        }
        return $them;
    }

    /** SHOW COLUMNS rồi lọc bằng PHP — `SHOW COLUMNS ... LIKE ?` bị lỗi 1064 */
    protected function hasColumn($bang, $cot){
        try {
            $rows = $this->db->query("SHOW COLUMNS FROM `$bang`")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e){ return false; }
        foreach ($rows as $r){
            if (isset($r['Field']) && $r['Field'] === $cot) return true;
        }
        return false;
    }
};

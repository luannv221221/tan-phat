<?php
/**
 * BẢNG TÍNH GIÁ — giá vốn + giá bán theo từng loại khách.
 *
 * Yêu cầu khách nêu trong buổi họp 11/11/2026 (sơ đồ khách chiếu trên màn):
 *
 *   "xây dựng bảng tính giá gồm giá vốn, giá bán (theo từng loại khách)"
 *   "cấu hình giá theo 2 kiểu: bình quân hoặc gần nhất"
 *   "giá vốn không được tùy chỉnh"
 *   "hệ số lãi được tùy chỉnh trong nhóm"   (vip 10% / đại lý 15% / lẻ 20%)
 *   "nút tính giá — đặt lịch ngày, tuần, tháng"
 *   "giá vốn + trường chi phí thành giá bán"
 *   "giá bán tùy chỉnh"                      (trên đơn vẫn sửa tay được)
 *
 * ──────────────────────────────────────────────────────────────────────────
 *  BA CHỖ DỄ LÀM SAI
 * ──────────────────────────────────────────────────────────────────────────
 *
 * 1. GIÁ VỐN ĐÃ CÓ SẴN, KHÔNG DỰNG LẠI. Kho đang tính bình quân gia quyền từ
 *    ngày đầu (`stocks`.`avg_cost`, cập nhật mỗi lần ghi sổ phiếu nhập — xem
 *    StocksModel::applyIn). Việc ở đây chỉ là CHO CHỌN kiểu thứ hai (giá nhập
 *    gần nhất) và BÀY nó ra màn hình. Dựng một đường tính giá vốn thứ hai song
 *    song là có ngày hai nơi ra hai số.
 *
 * 2. `parts`.`price` KHÔNG PHẢI GIÁ VỐN. Trong hệ này `price` = giá bán niêm
 *    yết, `sale_price` = giá khuyến mãi — cả hai đều gõ tay ở màn Hàng hoá.
 *    Giá vốn không có ô nào để gõ, và đó chính là điều khách muốn ("giá vốn
 *    không được tùy chỉnh"). Bảng tính giá vì vậy ĐỌC giá vốn từ kho, không
 *    bao giờ ghi ngược lại.
 *
 * 3. `cost_extra` MẶC ĐỊNH 0 LÀ CÓ CHỦ Ý. Sơ đồ khách ghi hai điều hơi khác
 *    nhau: công thức xanh là "giá vốn × hệ số + giá vốn", còn ghi chú xanh
 *    dương là "giá vốn + trường chi phí thành giá bán". Để 0 thì công thức ra
 *    ĐÚNG BẰNG con số trong sơ đồ (gạt mưa 1,2tr × 20% + 1,2tr), mà ô chi phí
 *    vẫn có đó cho ai cần. Hai điều cùng được tôn trọng, không phải chọn một.
 *
 * CHẠY LẠI ĐƯỢC: bảng/cột nào có rồi thì bỏ qua.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){

        /* ------------------------------------------------ 1. Hệ số lãi theo nhóm
         * Nhóm khách hàng đang chỉ có `discount_percent` (chiết khấu trừ vào
         * giá bán). Hệ số lãi là chiều NGƯỢC LẠI: cộng lên trên giá vốn. Hai
         * con số khác bản chất nên phải là hai cột, không dùng chung một cột.
         */
        if ($this->hasTable('customer_groups') && !$this->hasColumn('customer_groups', 'markup_percent')){
            $this->run("ALTER TABLE `customer_groups`
                        ADD COLUMN `markup_percent` DECIMAL(6,2) NOT NULL DEFAULT 0.00
                        AFTER `discount_percent`");
        }

        /* ------------------------------------------------- 2. Trường chi phí
         * Một khoản cộng thẳng vào giá vốn trước khi nhân hệ số lãi — vận
         * chuyển, bao bì, công lắp. Để 0 thì không đổi một đồng nào so với
         * công thức trong sơ đồ.
         */
        if ($this->hasTable('parts') && !$this->hasColumn('parts', 'cost_extra')){
            $this->run("ALTER TABLE `parts`
                        ADD COLUMN `cost_extra` DECIMAL(15,2) NOT NULL DEFAULT 0.00
                        AFTER `sale_price`");
        }

        /* ------------------------------------------------- 3. Bảng tính giá
         * MỘT DÒNG = MỘT (gara, mặt hàng, nhóm khách).
         *
         * Vì sao LƯU chứ không tính tại chỗ mỗi lần hỏi: khách yêu cầu "nút
         * tính giá" và "đặt lịch ngày, tuần, tháng". Hai thứ đó chỉ có nghĩa
         * khi con số được CHỐT lại tại một thời điểm — tính tại chỗ thì giá
         * đổi âm thầm ngay giữa lúc đang lập báo giá cho khách.
         *
         * `cost_price` và `method` lưu kèm để sau này nhìn lại còn biết con số
         * đó sinh ra từ giá vốn nào, bằng kiểu nào.
         */
        if (!$this->hasTable('part_prices')){
            $this->run("
                CREATE TABLE IF NOT EXISTS `part_prices` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `garage_id` INT(11) DEFAULT NULL,
                    `part_id` INT(11) NOT NULL,
                    `group_id` INT(11) NOT NULL,
                    `cost_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                    `markup_percent` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
                    `cost_extra` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                    `sale_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                    `method` VARCHAR(16) NOT NULL DEFAULT 'binh_quan',
                    `calculated_at` DATETIME DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uq_part_price` (`garage_id`, `part_id`, `group_id`),
                    KEY `idx_part_prices_part` (`part_id`),
                    KEY `idx_part_prices_group` (`group_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            /* Khoá ngoại tách riêng: bảng vừa tạo xong mới gắn được, và nếu
               môi trường nào đó thiếu bảng cha thì phần trên vẫn đứng. */
            if ($this->hasTable('parts')){
                $this->run("ALTER TABLE `part_prices`
                            ADD CONSTRAINT `fk_part_prices_part` FOREIGN KEY (`part_id`)
                            REFERENCES `parts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE");
            }
            if ($this->hasTable('customer_groups')){
                $this->run("ALTER TABLE `part_prices`
                            ADD CONSTRAINT `fk_part_prices_group` FOREIGN KEY (`group_id`)
                            REFERENCES `customer_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE");
            }
        }

        /* ------------------------------------ 4. Đăng ký màn Bảng tính giá
         * Thanh menu trái và cả hệ phân quyền đọc từ `modules`: thiếu dòng này
         * thì màn không hiện trên menu, và route() trả về false nên không ai
         * vào được — kể cả quản trị viên cao nhất.
         *
         * `chi_tan_phat` = 0: mỗi gara có giá vốn và nhóm khách riêng, nên đây
         * là màn của TỪNG GARA chứ không phải màn riêng của kho tổng.
         */
        if ($this->hasTable('modules')){
            $co = $this->db->query(
                'SELECT `id` FROM `modules` WHERE `link` = ? LIMIT 1', ['part-prices']
            )->fetch(\PDO::FETCH_ASSOC);

            if (empty($co)){
                $this->db->query(
                    'INSERT INTO `modules` (`name`, `link`, `create_at`, `chi_tan_phat`)
                     VALUES (?, ?, NOW(), 0)',
                    ['Bảng tính giá', 'part-prices']
                );
            }
        }

        /* ------------------------------------------ 5. Cấp quyền cho màn mới
         * CHÉP ĐÚNG THEO NHÓM NÀO ĐANG CÓ `products`, với đúng các vai trò đó.
         * Ai được quản hàng hoá thì cũng được xem bảng giá của hàng hoá đó.
         * Chép lại một quyết định đã đúng sẵn, thay vì tự nghĩ ra luật mới —
         * nhờ thế gara nào cố tình không cho nhóm Staff đụng hàng hoá thì vẫn
         * giữ nguyên ý đó.
         *
         * Không cấp ở đây thì sau khi triển khai, mỗi gara phải vào Nhóm >
         * Phân quyền tích tay cho từng nhóm — và gần như chắc chắn sẽ nghĩ là
         * màn hình bị lỗi.
         */
        if ($this->hasTable('permissions') && $this->hasTable('modules')){
            $m = $this->db->query(
                'SELECT `id` FROM `modules` WHERE `link` = ? LIMIT 1', ['part-prices']
            )->fetch(\PDO::FETCH_ASSOC);
            $mid = !empty($m) ? (int) $m['id'] : 0;

            if ($mid > 0){
                /* Đọc hết ra PHP rồi mới ghi: MySQL không cho vừa SELECT vừa
                   INSERT trên cùng một bảng trong một câu lệnh. */
                $mau = $this->db->query(
                    'SELECT p.`group_id`, p.`role`
                       FROM `permissions` p
                       JOIN `modules` m ON m.`id` = p.`module_id`
                      WHERE m.`link` = ?', ['products']
                )->fetchAll(\PDO::FETCH_ASSOC);

                $daCo = [];
                foreach ((array) $this->db->query(
                            'SELECT `group_id`, `role` FROM `permissions` WHERE `module_id` = ?', [$mid]
                         )->fetchAll(\PDO::FETCH_ASSOC) as $r){
                    $daCo[$r['group_id'] . '|' . $r['role']] = true;
                }

                foreach ((array) $mau as $r){
                    $khoa = $r['group_id'] . '|' . $r['role'];
                    if (isset($daCo[$khoa])) continue;
                    $this->db->query(
                        'INSERT INTO `permissions` (`module_id`, `group_id`, `role`) VALUES (?, ?, ?)',
                        [$mid, (int) $r['group_id'], $r['role']]
                    );
                    $daCo[$khoa] = true;
                }
            }
        }

        /* ------------------- 5b. Mở quyền SỬA Nhóm khách hàng cho gara
         * Khách yêu cầu "hệ số lãi được tùy chỉnh trong nhóm". Nhưng hôm nay
         * nhóm Manager của gara CHỈ ĐƯỢC XEM màn Nhóm khách hàng
         * (migration 000068: 'customer-groups' => XEM), nên chủ gara không tự
         * đặt được hệ số lãi của chính gara mình — đúng thứ vừa làm ra.
         *
         * Quyết định XEM-THÔI đó có lý ở thời điểm của nó: lúc ấy
         * `customer_groups` chưa có `garage_id`, một danh sách nhóm dùng chung
         * cho mọi gara, nên để gara sửa là gara này đổi nhóm của gara kia.
         * Từ 22/09/2026 bảng đã có `garage_id` và CustomerGroupsModel bật
         * $_theoGara — lý do đó không còn.
         *
         * CHÉP THEO `customers`, không tự nghĩ luật mới: hai màn cùng là dữ
         * liệu nền của CSKH, cùng chia theo gara, và ai khai được khách thì
         * cũng khai được loại khách. Nhờ chép mà nhóm nào Tân Phát cố tình
         * không cho đụng vào khách hàng thì cũng không được đụng vào nhóm.
         *
         * KHÔNG ĐỤNG tới nhóm đã có sẵn quyền: chỉ THÊM dòng còn thiếu.
         */
        if ($this->hasTable('permissions') && $this->hasTable('modules')){
            $mNhom = $this->db->query(
                'SELECT `id` FROM `modules` WHERE `link` = ? LIMIT 1', ['customer-groups']
            )->fetch(\PDO::FETCH_ASSOC);
            $midNhom = !empty($mNhom) ? (int) $mNhom['id'] : 0;

            if ($midNhom > 0){
                $mau = $this->db->query(
                    'SELECT p.`group_id`, p.`role`
                       FROM `permissions` p
                       JOIN `modules` m ON m.`id` = p.`module_id`
                      WHERE m.`link` = ?', ['customers']
                )->fetchAll(\PDO::FETCH_ASSOC);

                $daCo = [];
                foreach ((array) $this->db->query(
                            'SELECT `group_id`, `role` FROM `permissions` WHERE `module_id` = ?', [$midNhom]
                         )->fetchAll(\PDO::FETCH_ASSOC) as $r){
                    $daCo[$r['group_id'] . '|' . $r['role']] = true;
                }

                foreach ((array) $mau as $r){
                    $khoa = $r['group_id'] . '|' . $r['role'];
                    if (isset($daCo[$khoa])) continue;
                    $this->db->query(
                        'INSERT INTO `permissions` (`module_id`, `group_id`, `role`) VALUES (?, ?, ?)',
                        [$midNhom, (int) $r['group_id'], $r['role']]
                    );
                    $daCo[$khoa] = true;
                }
            }
        }

        /* --------------------------------------------- 6. Cấu hình mặc định
         * `binh_quan` là mặc định vì đó ĐÚNG LÀ cách kho đang chạy từ trước.
         * Đặt mặc định khác là đổi giá vốn của mọi gara ngay khi migration
         * chạy xong — không ai yêu cầu điều đó.
         *
         * Lịch để 'tat': bật lịch là việc của từng gara, không tự bật hộ.
         */
        if ($this->hasTable('site_settings')){
            foreach ([
                'cost_method'    => 'binh_quan',   // binh_quan | gan_nhat
                'price_schedule' => 'tat',         // tat | ngay | tuan | thang
            ] as $k => $v){
                $co = $this->db->query(
                    'SELECT `id` FROM `site_settings` WHERE `skey` = ? LIMIT 1', [$k]
                )->fetch(\PDO::FETCH_ASSOC);
                if (empty($co)){
                    $this->db->query(
                        'INSERT INTO `site_settings` (`skey`, `svalue`, `update_at`) VALUES (?, ?, NOW())',
                        [$k, $v]
                    );
                }
            }
        }

        echo "  OK — bang tinh gia (part_prices) + he so lai theo nhom + cau hinh kieu gia von\n";
    }

    public function down(){
        $this->run("DROP TABLE IF EXISTS `part_prices`");

        if ($this->hasColumn('customer_groups', 'markup_percent')){
            $this->run("ALTER TABLE `customer_groups` DROP COLUMN `markup_percent`");
        }
        if ($this->hasColumn('parts', 'cost_extra')){
            $this->run("ALTER TABLE `parts` DROP COLUMN `cost_extra`");
        }

        if ($this->hasTable('modules')){
            $m = $this->db->query('SELECT `id` FROM `modules` WHERE `link` = ? LIMIT 1',
                                  ['part-prices'])->fetch(\PDO::FETCH_ASSOC);
            if (!empty($m)){
                $this->db->query('DELETE FROM `permissions` WHERE `module_id` = ?', [(int) $m['id']]);
                $this->db->query('DELETE FROM `modules` WHERE `id` = ?', [(int) $m['id']]);
            }
        }

        $this->run("DELETE FROM `site_settings` WHERE `skey` IN ('cost_method','price_schedule')");

        /* KHÔNG THU LẠI quyền sửa Nhóm khách hàng (mục 5b của up()) — CỐ Ý.
           Lúc này không còn phân biệt được dòng nào do migration thêm với
           dòng nào người quản trị tự tick sau đó ở màn Nhóm > Phân quyền. Thu
           hết là cướp mất quyền người ta tự cấp; mà thu nửa vời thì còn tệ
           hơn. Thừa vài dòng quyền trên một màn mỗi gara vốn đã nhìn thấy là
           cái giá rẻ hơn nhiều. */
    }

    /* `hasTable` có sẵn ở lớp cha, `hasColumn` thì KHÔNG — mỗi migration tự
       khai, đúng theo lệ của các migration trước (xem 000106). */
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

<?php
/**
 * PHẦN BẢO HIỂM — danh mục hãng bảo hiểm, và tách "khách trả / bảo hiểm trả"
 * trên báo giá.
 *
 * Theo tài liệu khách gửi 14/10/2026:
 *   Danh mục bảo hiểm  id, mã, tên, tài khoản ngân hàng, địa chỉ, website,
 *                      mã số thuế, người liên hệ — CRUD
 *   Dùng cho báo giá   "khi tạo báo giá có mục: khách trả, bảo hiểm trả và
 *                      load tên hãng bảo hiểm" (ảnh minh hoạ số 3)
 *
 * DANH MỤC LÀ "CHUNG VÀ RIÊNG" (Model::$_chungVaRieng), như danh mục tài khoản
 * và loại phiếu: Bảo Việt, PVI, PJICO… là hãng toàn quốc, gara nào cũng làm
 * việc với đúng mấy hãng đó. Tân Phát khai sẵn, gara thêm hãng địa phương của
 * mình thì chỉ gara đó thấy. Lọc cứng theo gara là mỗi gara phải tự gõ lại
 * cùng một danh sách.
 *
 * BÁO GIÁ CHỈ THÊM HAI CỘT, KHÔNG THÊM BẢNG:
 *   `insurer_id`   hãng bảo hiểm của ca này
 *   `bao_hiem_tra` số tiền hãng bảo hiểm chịu
 *
 * KHÔNG LƯU "khách trả" THÀNH CỘT RIÊNG. Khách trả = tổng cộng − bảo hiểm trả,
 * tính ra lúc hiển thị. Lưu cả hai là hai nguồn sự thật cho cùng một con số:
 * sửa dòng hàng làm tổng đổi mà "khách trả" đã lưu thì đứng yên, và tới lúc
 * đối chiếu không biết tin cột nào.
 */

use App\core\Migration;

return new class extends Migration {

    const LINK = 'insurers';
    const TEN  = 'Danh mục bảo hiểm';

    public function up(){
        // ================= 1. Danh mục hãng bảo hiểm =================
        $this->run("
            CREATE TABLE IF NOT EXISTS `insurers` (
                `id`          INT AUTO_INCREMENT PRIMARY KEY,
                `code`        VARCHAR(30)  NOT NULL,
                `name`        VARCHAR(255) NOT NULL,
                `tax_code`    VARCHAR(30)  DEFAULT NULL,
                `bank_account` VARCHAR(100) DEFAULT NULL,
                `address`     VARCHAR(255) DEFAULT NULL,
                `website`     VARCHAR(255) DEFAULT NULL,
                `contact_name` VARCHAR(255) DEFAULT NULL,
                `contact_phone` VARCHAR(50) DEFAULT NULL,
                `sort_order`  INT NOT NULL DEFAULT 0,
                `status`      TINYINT(1) NOT NULL DEFAULT 1,
                `garage_id`   INT DEFAULT NULL,
                `create_at`   DATETIME DEFAULT NULL,
                `update_at`   DATETIME DEFAULT NULL,

                /* (garage_id, code): hai gara cùng khai một hãng địa phương là
                   chuyện thường. MySQL coi các NULL là khác nhau nên chỉ mục
                   này KHÔNG chặn được hai dòng CHUNG trùng mã — InsurersModel
                   ::findByCode() kiểm thêm ở tầng mã nguồn, giống `parts`. */
                UNIQUE KEY `uq_insurers` (`garage_id`, `code`),
                KEY `idx_insurers_garage` (`garage_id`),

                CONSTRAINT `fk_insurer_garage` FOREIGN KEY (`garage_id`)
                    REFERENCES `garages` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "  Da tao bang `insurers`.\n";

        // ================= 2. Hai cột trên báo giá =================
        if (!$this->hasColumn('quotations', 'insurer_id')){
            $this->run("ALTER TABLE `quotations` ADD COLUMN `insurer_id` INT DEFAULT NULL");
            $this->run("ALTER TABLE `quotations` ADD KEY `idx_quotations_insurer` (`insurer_id`)");
            /* SET NULL: xoá một hãng khỏi danh mục thì báo giá cũ mất tên hãng
               chứ không biến mất. RESTRICT thì không bao giờ dọn được danh mục. */
            $this->run("ALTER TABLE `quotations`
                        ADD CONSTRAINT `fk_quotation_insurer` FOREIGN KEY (`insurer_id`)
                        REFERENCES `insurers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE");
            echo "  Da them `quotations`.`insurer_id`.\n";
        }
        if (!$this->hasColumn('quotations', 'bao_hiem_tra')){
            $this->run("ALTER TABLE `quotations`
                        ADD COLUMN `bao_hiem_tra` DECIMAL(15,2) NOT NULL DEFAULT 0");
            echo "  Da them `quotations`.`bao_hiem_tra` (khach tra = tong cong - cot nay).\n";
        }

        $this->gieoHang();
        $this->khaiManHinh();
    }

    public function down(){
        $m = $this->db->table('modules')->where('link', '=', self::LINK)->first();
        if (!empty($m)){
            $this->db->delete('permissions', '`module_id` = ?', [(int) $m['id']]);
            $this->db->delete('modules', '`id` = ?', [(int) $m['id']]);
        }
        if ($this->hasColumn('quotations', 'insurer_id')){
            try { $this->run("ALTER TABLE `quotations` DROP FOREIGN KEY `fk_quotation_insurer`"); } catch (\Throwable $e){}
            try { $this->run("ALTER TABLE `quotations` DROP KEY `idx_quotations_insurer`"); } catch (\Throwable $e){}
            $this->run("ALTER TABLE `quotations` DROP COLUMN `insurer_id`");
        }
        if ($this->hasColumn('quotations', 'bao_hiem_tra')){
            $this->run("ALTER TABLE `quotations` DROP COLUMN `bao_hiem_tra`");
        }
        $this->run("DROP TABLE IF EXISTS `insurers`");
        echo "  Da go phan bao hiem.\n";
    }

    /**
     * Khai sẵn mấy hãng bảo hiểm phi nhân thọ lớn đang bán bảo hiểm xe cơ giới
     * ở Việt Nam. Dùng chung (`garage_id` NULL).
     *
     * CHỈ MÃ VÀ TÊN, để trống mọi thứ còn lại: tài khoản ngân hàng, người liên
     * hệ, địa chỉ chi nhánh là thứ KHÁC NHAU theo từng gara và từng thời điểm —
     * điền bừa vào đây là gara chuyển tiền nhầm số tài khoản vì tin phần mềm.
     * Gara tự điền phần của mình.
     */
    private function gieoHang(){
        $ds = [
            ['BAOVIET', 'Bảo hiểm Bảo Việt'],
            ['PVI',     'Bảo hiểm PVI'],
            ['PJICO',   'Bảo hiểm PJICO'],
            ['BAOMINH', 'Bảo hiểm Bảo Minh'],
            ['PTI',     'Bảo hiểm Bưu điện PTI'],
            ['MIC',     'Bảo hiểm Quân đội MIC'],
            ['BIC',     'Bảo hiểm BIDV (BIC)'],
            ['VBI',     'Bảo hiểm VietinBank (VBI)'],
        ];
        $now  = date('Y-m-d H:i:s');
        $them = 0;
        foreach ($ds as $i => $r){
            $co = $this->db->firstRaw("SELECT `id` FROM `insurers` WHERE `code` = ? AND `garage_id` IS NULL LIMIT 1", [$r[0]]);
            if (!empty($co['id'])) continue;
            $this->db->insert('insurers', [
                'code' => $r[0], 'name' => $r[1], 'sort_order' => $i,
                'status' => 1, 'garage_id' => null, 'create_at' => $now,
            ]);
            $them++;
        }
        echo "  Da gieo $them hang bao hiem dung chung.\n";
    }

    /**
     * QUYỀN: Manager toàn quyền, Staff chỉ XEM. Thợ lập báo giá cần CHỌN được
     * hãng bảo hiểm, nên phải thấy danh mục; còn khai một hãng mới kèm số tài
     * khoản ngân hàng thì là việc của chủ gara.
     */
    private function khaiManHinh(){
        $m = $this->db->table('modules')->where('link', '=', self::LINK)->first();
        if (empty($m)){
            $this->db->insert('modules', [
                'name' => self::TEN, 'link' => self::LINK, 'create_at' => date('Y-m-d H:i:s'),
            ]);
            $moduleId = (int) $this->db->lastId();
            echo "  Da khai man \"" . self::TEN . "\" (/admin/" . self::LINK . ").\n";
        } else {
            $moduleId = (int) $m['id'];
        }

        $quyen = [
            'Admin'   => ['view', 'add', 'edit', 'delete'],
            'Manager' => ['view', 'add', 'edit', 'delete'],
            'Staff'   => ['view'],
        ];
        $them = 0;
        foreach ($quyen as $tenNhom => $roles){
            /* MỌI nhóm cùng tên — từ migration 000093 mỗi gara một bộ nhóm riêng */
            foreach ((array) $this->db->getRaw("SELECT `id` FROM `groups` WHERE `name` = ?", [$tenNhom]) as $n){
                foreach ($roles as $role){
                    $co = $this->db->firstRaw("SELECT `id` FROM `permissions`
                                                WHERE `module_id` = ? AND `group_id` = ? AND `role` = ? LIMIT 1",
                                              [$moduleId, (int) $n['id'], $role]);
                    if (!empty($co['id'])) continue;
                    $this->db->insert('permissions', [
                        'module_id' => $moduleId, 'group_id' => (int) $n['id'], 'role' => $role,
                    ]);
                    $them++;
                }
            }
        }
        echo "  Da cap $them dong quyen.\n";
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

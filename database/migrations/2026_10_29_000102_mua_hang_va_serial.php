<?php
/**
 * MUA HÀNG VÀ SERIAL — theo tài liệu khách gửi 29/10/2026.
 *
 *   Menu "Mua hàng"   Đơn mua + Nhà cung cấp (trỏ sang màn Đối tượng đã có)
 *   Đơn mua           nhà cung cấp, hàng hoá, số lượng, serial,
 *                     link hoá đơn, upload hoá đơn
 *   Serial            số lượng 5 thì cho nhập 5 serial
 *   Tra cứu serial    mua của ai, theo đơn nào, ngày mua — để bảo hành
 *   Nhập kho          từ đơn mua tạo được phiếu nhập, "không cần giá nhập"
 *
 * BA QUYẾT ĐỊNH VỀ CẤU TRÚC, chốt với khách 29/10/2026:
 *
 * 1. SERIAL LÀ TỪNG CHIẾC MỘT, không phải một ô gộp cả dòng. Số lượng 5 thì 5
 *    dòng trong `purchase_serials`. Tra một serial ra đúng chiếc đó — gộp vào
 *    một ô thì tra ra cả dòng năm chiếc, không biết chiếc nào.
 *
 * 2. ĐƠN MUA KHÔNG CÓ GIÁ. Tài liệu ghi "không cần giá nhập". Giá chỉ xuất
 *    hiện lúc chuyển thành phiếu nhập kho, và lấy GIÁ NHẬP GẦN NHẤT của mặt
 *    hàng rồi cho sửa lại — để giá vốn vẫn có số thật, báo cáo lãi gộp không
 *    bị thổi lên vì giá vốn 0.
 *
 * 3. CHƯA THEO DÕI ĐÃ BÁN CHO AI. Phạm vi tài liệu dừng ở "mua của ai, đơn
 *    nào, ngày mua". Cột `goods_receipt_id` đã đủ để sau này lần sang phiếu
 *    xuất nếu cần mở rộng.
 *
 * SỐ ĐƠN MUA KHÔNG TRÙNG TRONG TỪNG GARA — UNIQUE (garage_id, po_no), đúng
 * khuôn mọi chứng từ khác. Sai chỗ này là lỗi đã gặp thật ở `orders`
 * (migration 000099): đánh số theo gara mà chỉ mục duy nhất toàn hệ thống thì
 * gara thứ hai lập phiếu là dính 1062 Duplicate entry, chứng từ biến mất không
 * dấu vết.
 */

use App\core\Migration;

return new class extends Migration {

    const MAN = [
        ['purchase-orders', 'Đơn mua'],
        ['serials',         'Quản lý serial'],
    ];

    public function up(){
        // ================= 1. Đơn mua =================
        $this->run("
            CREATE TABLE IF NOT EXISTS `purchase_orders` (
                `id`            INT AUTO_INCREMENT PRIMARY KEY,
                `po_no`         VARCHAR(50)  NOT NULL,
                `partner_id`    INT DEFAULT NULL,
                /* BẢN CHỤP tên NCC lúc lập. Đổi tên nhà cung cấp sang năm sau
                   không được làm đổi đơn đã lập — cùng lý do với `dt_ten` của
                   phiếu thu / chi. */
                `partner_name`  VARCHAR(255) DEFAULT NULL,
                `order_date`    DATE NOT NULL,

                /* HOÁ ĐƠN CỦA NHÀ CUNG CẤP — tài liệu: \"link hóa đơn\" +
                   \"upload (hóa đơn)\". Hai thứ khác nhau nên hai cột: có nơi
                   gửi link hoá đơn điện tử, có nơi đưa bản chụp. */
                `invoice_no`    VARCHAR(100) DEFAULT NULL,
                `invoice_url`   VARCHAR(500) DEFAULT NULL,
                `invoice_file`  VARCHAR(255) DEFAULT NULL,

                `note`          VARCHAR(255) DEFAULT NULL,
                /* 0 = nháp, 1 = đã chốt. Chốt rồi mới tạo được phiếu nhập kho. */
                `status`        TINYINT(1) NOT NULL DEFAULT 0,
                /* Phiếu nhập kho đã sinh ra từ đơn này — NULL là chưa nhập.
                   Giữ ở đây để không tạo hai lần, và để mở đơn ra lần được
                   sang phiếu nhập. */
                `goods_receipt_id` INT DEFAULT NULL,

                `created_by`    INT DEFAULT NULL,
                `garage_id`     INT NOT NULL,
                `create_at`     DATETIME DEFAULT NULL,
                `update_at`     DATETIME DEFAULT NULL,

                UNIQUE KEY `uq_po_gara_no` (`garage_id`, `po_no`),
                KEY `idx_po_gara_ngay` (`garage_id`, `order_date`),
                KEY `idx_po_partner` (`partner_id`),

                CONSTRAINT `fk_po_garage` FOREIGN KEY (`garage_id`)
                    REFERENCES `garages` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                /* RESTRICT: còn đơn mua của một NCC thì không xoá được NCC đó —
                   lịch sử mua hàng tính từ chính mấy đơn này. */
                CONSTRAINT `fk_po_partner` FOREIGN KEY (`partner_id`)
                    REFERENCES `partners` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk_po_receipt` FOREIGN KEY (`goods_receipt_id`)
                    REFERENCES `goods_receipts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "  Da tao bang `purchase_orders`.\n";

        // ================= 2. Dòng hàng của đơn mua =================
        $this->run("
            CREATE TABLE IF NOT EXISTS `purchase_order_items` (
                `id`        INT AUTO_INCREMENT PRIMARY KEY,
                `po_id`     INT NOT NULL,
                `part_id`   INT NOT NULL,
                `quantity`  DECIMAL(15,3) NOT NULL DEFAULT 0,
                `note`      VARCHAR(255) DEFAULT NULL,

                KEY `idx_poi_po` (`po_id`),
                KEY `idx_poi_part` (`part_id`),

                /* CASCADE: xoá đơn mua thì dòng hàng đi theo. Đơn mua là chứng
                   từ NHÁP cho tới khi thành phiếu nhập, xoá được. */
                CONSTRAINT `fk_poi_po` FOREIGN KEY (`po_id`)
                    REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_poi_part` FOREIGN KEY (`part_id`)
                    REFERENCES `parts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "  Da tao bang `purchase_order_items`.\n";

        // ================= 3. Serial từng chiếc =================
        $this->run("
            CREATE TABLE IF NOT EXISTS `purchase_serials` (
                `id`       INT AUTO_INCREMENT PRIMARY KEY,
                `po_id`    INT NOT NULL,
                `poi_id`   INT DEFAULT NULL,
                `part_id`  INT NOT NULL,
                `serial`   VARCHAR(100) NOT NULL,
                `note`     VARCHAR(255) DEFAULT NULL,
                `garage_id` INT NOT NULL,
                `create_at` DATETIME DEFAULT NULL,

                /* KHÔNG TRÙNG SERIAL TRONG MỘT GARA. Serial là định danh của
                   đúng một chiếc hàng — trùng nghĩa là gõ nhầm, và chốt này
                   bắt được ngay lúc nhập thay vì để tới lúc tra cứu ra hai
                   dòng mà không biết tin dòng nào.
                   Theo gara chứ không toàn hệ thống: hai gara khác nhau mua
                   hàng của hai nơi, số trùng nhau là chuyện của họ. */
                UNIQUE KEY `uq_serial_gara` (`garage_id`, `serial`),
                KEY `idx_serial_po` (`po_id`),
                KEY `idx_serial_part` (`part_id`),
                /* Tra cứu gõ nguyên chuỗi serial -> chỉ mục này phục vụ câu
                   WHERE serial = ? của màn Quản lý serial. */
                KEY `idx_serial_ma` (`serial`),

                CONSTRAINT `fk_ps_po` FOREIGN KEY (`po_id`)
                    REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_ps_poi` FOREIGN KEY (`poi_id`)
                    REFERENCES `purchase_order_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_ps_part` FOREIGN KEY (`part_id`)
                    REFERENCES `parts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk_ps_garage` FOREIGN KEY (`garage_id`)
                    REFERENCES `garages` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "  Da tao bang `purchase_serials`.\n";

        $this->khaiManHinh();
    }

    public function down(){
        foreach (self::MAN as $m){
            $row = $this->db->table('modules')->where('link', '=', $m[0])->first();
            if (empty($row)) continue;
            $this->db->delete('permissions', '`module_id` = ?', [(int) $row['id']]);
            $this->db->delete('modules', '`id` = ?', [(int) $row['id']]);
        }
        /* Xoá theo thứ tự ngược khoá ngoại */
        $this->run("DROP TABLE IF EXISTS `purchase_serials`");
        $this->run("DROP TABLE IF EXISTS `purchase_order_items`");
        $this->run("DROP TABLE IF EXISTS `purchase_orders`");
        echo "  Da go phan mua hang va serial.\n";
    }

    /**
     * QUYỀN: Admin + Manager toàn quyền, Staff chỉ XEM.
     *
     * Thợ cần tra serial để biết phụ tùng còn bảo hành không, nên phải thấy.
     * Còn khai một đơn mua kèm hoá đơn nhà cung cấp là việc của chủ gara.
     */
    private function khaiManHinh(){
        $quyen = [
            'Admin'   => ['view', 'add', 'edit', 'delete'],
            'Manager' => ['view', 'add', 'edit', 'delete'],
            'Staff'   => ['view'],
        ];
        $them = 0;

        foreach (self::MAN as $m){
            list($link, $ten) = $m;
            $row = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($row)){
                $this->db->insert('modules', [
                    'name' => $ten, 'link' => $link, 'create_at' => date('Y-m-d H:i:s'),
                ]);
                $moduleId = (int) $this->db->lastId();
                echo "  Da khai man \"$ten\" (/admin/$link).\n";
            } else {
                $moduleId = (int) $row['id'];
            }

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
        }
        echo "  Da cap $them dong quyen.\n";
    }
};

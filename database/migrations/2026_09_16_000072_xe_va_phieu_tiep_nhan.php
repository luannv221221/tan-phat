<?php
/**
 * MỘT KHÁCH → NHIỀU XE → MỘT XE NHIỀU PHIẾU TIẾP NHẬN.
 *
 * SAI GỐC ĐANG SỬA
 * Biển số và số km trước đây là CHỮ GÕ TAY trên từng báo giá / hoá đơn / phiếu
 * bảo hành. Không có bản ghi "xe" nào nối chúng lại, nên:
 *   - tra lịch sử một chiếc xe là ghép chuỗi, gõ hai kiểu là thành hai xe;
 *   - số khung, số máy, hãng, model, năm không có chỗ lưu;
 *   - không biết một khách có mấy xe.
 * Còn `member_vehicles` thì gắn vào `members` (tài khoản web), trong khi MỌI
 * chứng từ gara lại trỏ `partners` (màn Đối tượng) — xe nằm ở bảng không dùng
 * để bán hàng.
 *
 * MÔ HÌNH MỚI
 *   partners (khách)  1 ─── n  vehicles (xe)  1 ─── n  receptions (phiếu tiếp nhận)
 *   receptions        1 ─── n  báo giá / hoá đơn / phiếu bảo hành
 *
 * - Biển số DUY NHẤT (bien_so_chuan): chống tạo hai lần cùng một xe.
 * - Số khung để trống được, nhưng đã ghi thì không được trùng — hai xe không
 *   thể cùng số khung, và trùng ở đây gần như chắc chắn là gõ nhầm.
 * - Hãng / model / năm nối vào danh mục xe có sẵn (car_brands / car_models /
 *   car_years), KÈM cột chữ để gõ tay khi xe lạ chưa có trong danh mục —
 *   không chặn việc ở quầy.
 * - Cột `bien_so`, `so_km` cũ trên chứng từ GIỮ NGUYÊN làm bản chụp lúc lập:
 *   xe đổi biển hay đổi chủ thì chứng từ cũ vẫn in ra đúng như đã giao khách.
 * - Xoá xe còn phiếu tiếp nhận: CHẶN (RESTRICT). Xoá khách thì xe còn lại,
 *   chỉ mất chủ (SET NULL) — xe vẫn có lịch sử sửa chữa của nó.
 *
 * `member_vehicles` chưa xoá ở đây (giữ đường lùi); màn CSKH sẽ chuyển sang
 * dùng bảng `vehicles` qua liên kết members.partner_id.
 */

use App\core\Migration;

return new class extends Migration {

    /** Màn hình mới + quyền. Staff PHẢI khai xe và tiếp nhận được, không thì
     *  cả quy trình đứng ở quầy; xoá thì để Manager / Admin. */
    const MODULES = [
        'vehicles'   => 'Xe của khách',
        'receptions' => 'Phiếu tiếp nhận',
    ];
    const QUYEN = [
        'Admin'   => ['view', 'add', 'edit', 'delete'],
        'Manager' => ['view', 'add', 'edit', 'delete'],
        'Staff'   => ['view', 'add', 'edit'],
    ];

    public function up(){
        /* ---------- 1. Bảng XE ---------- */
        $this->run("CREATE TABLE IF NOT EXISTS `vehicles` (
            `id`            INT NOT NULL AUTO_INCREMENT,
            `partner_id`    INT DEFAULT NULL,
            `bien_so`       VARCHAR(20) NOT NULL,
            `bien_so_chuan` VARCHAR(20) NOT NULL,
            `so_khung`      VARCHAR(30) DEFAULT NULL,
            `so_may`        VARCHAR(30) DEFAULT NULL,
            `brand_id`      INT DEFAULT NULL,
            `model_id`      INT DEFAULT NULL,
            `car_year_id`   INT DEFAULT NULL,
            `hang_xe`       VARCHAR(60) DEFAULT NULL,
            `model_xe`      VARCHAR(60) DEFAULT NULL,
            `nam_sx`        SMALLINT DEFAULT NULL,
            `phien_ban`     VARCHAR(60) DEFAULT NULL,
            `mau_xe`        VARCHAR(40) DEFAULT NULL,
            `so_km`         INT DEFAULT NULL,
            `ghi_chu`       VARCHAR(255) DEFAULT NULL,
            `status`        TINYINT(1) NOT NULL DEFAULT 1,
            `create_at`     DATETIME DEFAULT NULL,
            `update_at`     DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_vehicles_bien_so` (`bien_so_chuan`),
            UNIQUE KEY `uq_vehicles_so_khung` (`so_khung`),
            KEY `idx_vehicles_partner` (`partner_id`),
            KEY `idx_vehicles_model` (`model_id`),
            CONSTRAINT `fk_vehicles_partner` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_vehicles_brand`   FOREIGN KEY (`brand_id`) REFERENCES `car_brands` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_vehicles_model`   FOREIGN KEY (`model_id`) REFERENCES `car_models` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_vehicles_year`    FOREIGN KEY (`car_year_id`) REFERENCES `car_years` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        /* ---------- 2. Bảng PHIẾU TIẾP NHẬN ---------- */
        $this->run("CREATE TABLE IF NOT EXISTS `receptions` (
            `id`            INT NOT NULL AUTO_INCREMENT,
            `reception_no`  VARCHAR(50) NOT NULL,
            `vehicle_id`    INT NOT NULL,
            `partner_id`    INT DEFAULT NULL,
            `garage_id`     INT DEFAULT NULL,
            `ngay_vao`      DATE NOT NULL,
            `ngay_ra`       DATE DEFAULT NULL,
            `km_vao`        INT DEFAULT NULL,
            `km_ra`         INT DEFAULT NULL,
            `tinh_trang_xe` TEXT,
            `yeu_cau_khach` TEXT,
            `co_van_id`     INT DEFAULT NULL,
            `co_van`        VARCHAR(150) DEFAULT NULL,
            `status`        VARCHAR(20) NOT NULL DEFAULT 'tiep_nhan',
            `note`          VARCHAR(255) DEFAULT NULL,
            `created_by`    INT DEFAULT NULL,
            `create_at`     DATETIME DEFAULT NULL,
            `update_at`     DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_receptions_no` (`reception_no`),
            KEY `idx_receptions_vehicle` (`vehicle_id`),
            KEY `idx_receptions_status` (`status`, `ngay_vao`),
            CONSTRAINT `fk_receptions_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_receptions_partner` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_receptions_garage`  FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_receptions_covan`   FOREIGN KEY (`co_van_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        /* ---------- 3. Cột nối trên chứng từ ---------- */
        foreach (['quotations', 'sales_invoices', 'warranty_requests'] as $bang){
            $this->run("ALTER TABLE `$bang`
                        ADD COLUMN `vehicle_id`   INT DEFAULT NULL AFTER `bien_so_chuan`,
                        ADD COLUMN `reception_id` INT DEFAULT NULL AFTER `vehicle_id`,
                        ADD KEY `idx_{$bang}_vehicle` (`vehicle_id`),
                        ADD KEY `idx_{$bang}_reception` (`reception_id`),
                        ADD CONSTRAINT `fk_{$bang}_vehicle`   FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL,
                        ADD CONSTRAINT `fk_{$bang}_reception` FOREIGN KEY (`reception_id`) REFERENCES `receptions` (`id`) ON DELETE SET NULL");
        }

        /* ---------- 4. Tài khoản web ↔ Đối tượng ----------
           Một người là MỘT khách; trước đây tài khoản web (`members`) và đối
           tượng bán hàng (`partners`) không có đường nối nào, nên xe của khách
           nhìn từ màn CSKH khác với xe nhìn từ chứng từ. */
        $this->run("ALTER TABLE `members`
                    ADD COLUMN `partner_id` INT DEFAULT NULL AFTER `id`,
                    ADD KEY `idx_members_partner` (`partner_id`),
                    ADD CONSTRAINT `fk_members_partner` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE SET NULL");

        /* ---------- 5. Dựng xe từ biển số đã ghi trên chứng từ ----------
           Chỉ những dòng CÓ biển số. Không đoán gì thêm: hãng / model / số
           khung để trống cho người dùng tự điền. */
        $now = date('Y-m-d H:i:s');
        $dungXe = 0; $noiChungTu = 0;

        foreach ([
            ['quotations',        'customer_id'],
            ['sales_invoices',    'customer_id'],
            ['warranty_requests', 'partner_id'],
        ] as $x){
            list($bang, $cotChu) = $x;
            $rows = $this->db->getRaw(
                "SELECT `id`, `bien_so`, `bien_so_chuan`, `so_km`, `$cotChu` AS `chu`
                   FROM `$bang`
                  WHERE `bien_so_chuan` IS NOT NULL AND `bien_so_chuan` <> ''
                  ORDER BY `id`"
            );
            foreach ((array) $rows as $r){
                $xe = $this->db->firstRaw(
                    "SELECT `id`, `partner_id`, `so_km` FROM `vehicles` WHERE `bien_so_chuan` = ?",
                    [$r['bien_so_chuan']]
                );
                if (empty($xe)){
                    $this->db->insert('vehicles', [
                        'partner_id'    => !empty($r['chu']) ? (int) $r['chu'] : null,
                        'bien_so'       => $r['bien_so'],
                        'bien_so_chuan' => $r['bien_so_chuan'],
                        'so_km'         => !empty($r['so_km']) ? (int) $r['so_km'] : null,
                        'ghi_chu'       => 'Dựng tự động từ ' . $bang,
                        'status'        => 1,
                        'create_at'     => $now,
                    ]);
                    $xeId = (int) $this->db->lastId();
                    $dungXe++;
                } else {
                    $xeId = (int) $xe['id'];
                    // Bổ sung chủ xe / số km mới hơn nếu chứng từ có mà xe chưa có
                    $capNhat = [];
                    if (empty($xe['partner_id']) && !empty($r['chu'])) $capNhat['partner_id'] = (int) $r['chu'];
                    if (!empty($r['so_km']) && (int) $r['so_km'] > (int) $xe['so_km']) $capNhat['so_km'] = (int) $r['so_km'];
                    if (!empty($capNhat)){
                        $capNhat['update_at'] = $now;
                        $this->db->update('vehicles', $capNhat, '`id` = ?', [$xeId]);
                    }
                }
                $this->db->update($bang, ['vehicle_id' => $xeId], '`id` = ?', [(int) $r['id']]);
                $noiChungTu++;
            }
        }
        echo "  Da dung $dungXe xe tu bien so cu, noi $noiChungTu chung tu vao xe.\n";

        /* ---------- 6. Khai hai màn hình mới + phân quyền ---------- */
        foreach (self::MODULES as $link => $ten){
            $m = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($m)){
                $this->db->insert('modules', ['name' => $ten, 'link' => $link, 'create_at' => $now]);
                $moduleId = (int) $this->db->lastId();
            } else {
                $moduleId = (int) $m['id'];
            }

            foreach (self::QUYEN as $tenNhom => $roles){
                $nhom = $this->db->table('groups')->where('name', '=', $tenNhom)->first();
                if (empty($nhom)) continue;
                foreach ($roles as $role){
                    $co = $this->db->table('permissions')
                        ->where('module_id', '=', $moduleId)
                        ->where('group_id', '=', $nhom['id'])
                        ->where('role', '=', $role)->first();
                    if (!empty($co)) continue;
                    $this->db->insert('permissions', [
                        'module_id' => $moduleId, 'group_id' => $nhom['id'], 'role' => $role,
                    ]);
                }
            }
            echo "  Da khai man hinh \"$ten\" (/admin/$link).\n";
        }
    }

    public function down(){
        foreach (['quotations', 'sales_invoices', 'warranty_requests'] as $bang){
            $this->run("ALTER TABLE `$bang`
                        DROP FOREIGN KEY `fk_{$bang}_vehicle`,
                        DROP FOREIGN KEY `fk_{$bang}_reception`");
            $this->run("ALTER TABLE `$bang`
                        DROP KEY `idx_{$bang}_vehicle`,
                        DROP KEY `idx_{$bang}_reception`,
                        DROP COLUMN `vehicle_id`,
                        DROP COLUMN `reception_id`");
        }

        $this->run("ALTER TABLE `members` DROP FOREIGN KEY `fk_members_partner`");
        $this->run("ALTER TABLE `members` DROP KEY `idx_members_partner`, DROP COLUMN `partner_id`");

        $this->run("DROP TABLE IF EXISTS `receptions`");
        $this->run("DROP TABLE IF EXISTS `vehicles`");

        foreach (array_keys(self::MODULES) as $link){
            $m = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($m)) continue;
            $this->db->delete('permissions', '`module_id` = ?', [(int) $m['id']]);
            $this->db->delete('modules', '`id` = ?', [(int) $m['id']]);
        }
        echo "  Da go bang vehicles / receptions va hai man hinh.\n";
    }
};

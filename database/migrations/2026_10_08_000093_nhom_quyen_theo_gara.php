<?php
/**
 * NHÓM QUYỀN THEO GARA — để gara tự phân quyền cho nhân viên của mình.
 *
 * TRƯỚC: bảng `groups` và `permissions` không có `garage_id`. Ba nhóm Admin /
 * Manager / Staff dùng chung cho CẢ HỆ THỐNG: 4 người thuộc nhóm Manager trải
 * trên 3 gara, 3 người thuộc nhóm Staff trải trên 3 gara. Mở màn Quản lý nhóm
 * cho gara trong tình trạng đó là một lỗ leo thang: chủ gara Long Biên bỏ tick
 * một quyền của nhóm Manager thì chủ gara Sài Gòn và Đà Nẵng mất quyền đó theo,
 * còn tick thêm quyền thì cả ba gara được thêm.
 *
 * SAU: mỗi gara có BỘ NHÓM RIÊNG.
 *
 *     groups.garage_id IS NULL  -> NHÓM HỆ THỐNG, của Tân Phát với tư cách
 *                                  người vận hành nền tảng. `Admin` là nhóm
 *                                  này. Gara khác không thấy, không sửa.
 *     groups.garage_id = X      -> nhóm của gara X. Chỉ gara X (và gara tổng)
 *                                  thấy và sửa.
 *
 * `permissions` KHÔNG cần `garage_id`: nó khoá theo `group_id`, mà nhóm đã
 * thuộc về một gara. Thêm cột nữa là hai nguồn sự thật cho cùng một việc.
 *
 * NHÓM MẪU LÀ MANAGER / STAFF CỦA GARA TỔNG. Mở gara mới thì nhân bản từ đó
 * (MoGaraModel::nhanBanNhom). Chọn vậy chứ không viết cứng danh sách quyền vào
 * migration: Tân Phát sửa bộ quyền mặc định ở màn Quản lý nhóm là gara mở sau
 * được theo, không phải viết thêm migration.
 *
 * HAI CHỖ HỤT ĐI KÈM, vá luôn trong này vì mở màn Quản lý nhóm là đụng tới:
 *
 *   1. `laToanQuyen()` lấy mốc "có role `permission` trên module `groups`".
 *      Cấp quyền đó cho Manager của gara là Manager thành toàn quyền, gán được
 *      nhóm Admin cho nhân viên. Nay mốc thêm điều kiện "nhóm hệ thống"
 *      (garage_id IS NULL) — xem GroupsModel::laToanQuyen.
 *
 *   2. Xoá nhóm thì `users.group_id` SET NULL (khoá ngoại), và tài khoản không
 *      nhóm thì RoleMiddleware bỏ qua TOÀN BỘ phần kiểm quyền — vào được mọi
 *      màn. Chốt ở Groups::delete (không xoá nhóm còn người) và ở
 *      RoleMiddleware (không nhóm / nhóm rỗng = không có quyền).
 *
 * CHẠY LẠI KHÔNG SINH BẢN SAO: nhân bản kiểm "gara này đã có nhóm tên đó chưa".
 */

use App\core\Migration;

return new class extends Migration {

    /** Nhóm được nhân bản cho từng gara. `Admin` KHÔNG nằm đây — nó là nhóm hệ thống. */
    const NHAN_BAN = ['Manager', 'Staff'];

    public function up(){
        $tong = $this->db->firstRaw("SELECT `id` FROM `garages` WHERE `is_master` = 1 ORDER BY `id` LIMIT 1");
        if (empty($tong['id'])){
            throw new \RuntimeException('Chua co gara tong (is_master = 1) — chay migration 000063 truoc.');
        }
        $tongId = (int) $tong['id'];

        // --- 1. Cột `garage_id` ---
        if (!$this->hasColumn('groups', 'garage_id')){
            $this->run("ALTER TABLE `groups` ADD COLUMN `garage_id` INT DEFAULT NULL");
            $this->run("ALTER TABLE `groups` ADD KEY `idx_groups_garage` (`garage_id`)");
            /* CASCADE: nhóm riêng của gara không còn nghĩa gì khi gara biến mất.
               Thực tế vẫn không xoá được gara còn người dùng — `users.garage_id`
               là RESTRICT, nên tới đây thì nhóm đã rỗng người. */
            $this->run("ALTER TABLE `groups` ADD CONSTRAINT `fk_groups_garage`
                        FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`)
                        ON DELETE CASCADE ON UPDATE CASCADE");
            echo "  Da them `groups`.`garage_id`.\n";
        }

        // --- 2. Manager / Staff hiện có trở thành nhóm CỦA GARA TỔNG (và là nhóm mẫu) ---
        $dau = implode(',', array_fill(0, count(self::NHAN_BAN), '?'));
        $this->db->query("UPDATE `groups` SET `garage_id` = ?
                           WHERE `garage_id` IS NULL AND `name` IN ($dau)",
                         array_merge([$tongId], self::NHAN_BAN));

        $mau = [];
        foreach (self::NHAN_BAN as $ten){
            $r = $this->db->firstRaw("SELECT `id`, `name` FROM `groups`
                                       WHERE `name` = ? AND `garage_id` = ? LIMIT 1", [$ten, $tongId]);
            if (!empty($r['id'])) $mau[$ten] = (int) $r['id'];
        }
        if (empty($mau)){
            echo "  CANH BAO: khong tim thay nhom mau Manager/Staff — bo qua phan nhan ban.\n";
        }
        echo "  Nhom mau cua gara tong: " . implode(', ', array_keys($mau)) . "\n";

        // --- 3. Nhân bản cho từng gara còn lại ---
        $soNhom = $soQuyen = $soNguoi = 0;
        foreach ((array) $this->db->getRaw("SELECT `id`, `code` FROM `garages` WHERE `is_master` = 0 ORDER BY `id`") as $g){
            $gid = (int) $g['id'];
            foreach ($mau as $ten => $mauId){
                $co = $this->db->firstRaw("SELECT `id` FROM `groups` WHERE `name` = ? AND `garage_id` = ? LIMIT 1",
                                          [$ten, $gid]);
                if (!empty($co['id'])){ $moiId = (int) $co['id']; }
                else {
                    $this->db->insert('groups', [
                        'name' => $ten, 'garage_id' => $gid, 'create_at' => date('Y-m-d H:i:s'),
                    ]);
                    $moiId = (int) $this->db->lastId();
                    $soNhom++;
                }
                $soQuyen += $this->chepQuyen($mauId, $moiId);

                /* Chuyển người của gara này sang nhóm MỚI cùng tên. Lọc theo
                   `group_id` của nhóm mẫu chứ không theo tên: tài khoản đang ở
                   nhóm Admin vẫn ở nhóm Admin, đó là nhóm hệ thống. */
                $st = $this->db->query("UPDATE `users` SET `group_id` = ? WHERE `garage_id` = ? AND `group_id` = ?",
                                       [$moiId, $gid, $mauId]);
                $soNguoi += (int) $st->rowCount();
            }
        }
        echo "  Da nhan ban $soNhom nhom, $soQuyen dong quyen, chuyen $soNguoi tai khoan.\n";

        // --- 4. Mở màn Quản lý nhóm cho gara ---
        if ($this->hasColumn('modules', 'chi_tan_phat')){
            $this->db->query("UPDATE `modules` SET `chi_tan_phat` = 0 WHERE `link` = 'groups'");
            echo "  Da mo man Quan ly nhom cho moi gara.\n";
        }

        /* Màn Quản lý module KHÔNG mở. Nó chỉ ĐĂNG KÝ một màn hình đã có sẵn
           trong mã nguồn vào bảng phân quyền — thêm ở đó không sinh ra màn hình
           nào, mà xoá một dòng là gỡ màn đó khỏi phân quyền của CẢ HỆ THỐNG,
           mọi gara mất màn. Không có gì để một gara chủ động ở đấy. */

        // --- 5. Cấp quyền màn Quản lý nhóm cho các nhóm Manager ---
        $this->capQuyenNhom();
    }

    public function down(){
        if ($this->hasColumn('modules', 'chi_tan_phat')){
            $this->db->query("UPDATE `modules` SET `chi_tan_phat` = 1 WHERE `link` = 'groups'");
        }

        /* Trả người về nhóm mẫu TRƯỚC khi xoá nhóm nhân bản: khoá ngoại là
           SET NULL, để nó tự xoá là tài khoản mất nhóm, mà tài khoản không nhóm
           thì RoleMiddleware không gác được gì. */
        $tong = $this->db->firstRaw("SELECT `id` FROM `garages` WHERE `is_master` = 1 ORDER BY `id` LIMIT 1");
        $tongId = !empty($tong['id']) ? (int) $tong['id'] : 0;

        foreach (self::NHAN_BAN as $ten){
            $mau = $this->db->firstRaw("SELECT `id` FROM `groups` WHERE `name` = ? AND `garage_id` = ? LIMIT 1",
                                       [$ten, $tongId]);
            if (empty($mau['id'])) continue;
            $mauId = (int) $mau['id'];

            foreach ((array) $this->db->getRaw(
                        "SELECT `id` FROM `groups` WHERE `name` = ? AND `garage_id` IS NOT NULL AND `id` <> ?",
                        [$ten, $mauId]) as $n){
                $this->db->query("UPDATE `users` SET `group_id` = ? WHERE `group_id` = ?", [$mauId, (int) $n['id']]);
                // `permissions` CASCADE theo nhóm
                $this->db->delete('groups', '`id` = ?', [(int) $n['id']]);
            }
        }

        if ($this->hasColumn('groups', 'garage_id')){
            try { $this->run("ALTER TABLE `groups` DROP FOREIGN KEY `fk_groups_garage`"); } catch (\Throwable $e){}
            try { $this->run("ALTER TABLE `groups` DROP KEY `idx_groups_garage`"); } catch (\Throwable $e){}
            $this->run("ALTER TABLE `groups` DROP COLUMN `garage_id`");
        }
        echo "  Da go nhom quyen theo gara.\n";
    }

    // ===== Helper =====

    /**
     * Chép các dòng `permissions` của nhóm mẫu sang nhóm mới, bỏ dòng đã có.
     * @return int số dòng thêm được
     */
    private function chepQuyen($mauId, $moiId){
        $them = 0;
        foreach ((array) $this->db->getRaw(
                    "SELECT `module_id`, `role` FROM `permissions` WHERE `group_id` = ?", [$mauId]) as $p){
            $co = $this->db->firstRaw("SELECT `id` FROM `permissions`
                                        WHERE `group_id` = ? AND `module_id` = ? AND `role` = ? LIMIT 1",
                                      [$moiId, (int) $p['module_id'], $p['role']]);
            if (!empty($co['id'])) continue;
            $this->db->insert('permissions', [
                'group_id' => $moiId, 'module_id' => (int) $p['module_id'], 'role' => $p['role'],
            ]);
            $them++;
        }
        return $them;
    }

    /**
     * Mọi nhóm Manager (của mọi gara) được xem + phân quyền trên màn Quản lý nhóm.
     *
     * KHÔNG cấp `add` / `delete`: chủ gara đã có sẵn hai nhóm Manager và Staff
     * của mình: thêm nhóm thứ ba hay xoá bớt một nhóm chỉ sinh ra nhóm rỗng
     * người và tài khoản mất nhóm. Việc thật mà chủ gara cần là ĐỔI quyền của
     * nhóm Staff — đúng hai role `view` + `permission`.
     *
     * `edit` (đổi TÊN nhóm) cũng không cấp: tên nhóm là thứ `nhanBanNhom()` dựa
     * vào để nhận ra nhóm mẫu, đổi tên là lần mở gara sau nhân bản sai.
     */
    private function capQuyenNhom(){
        $m = $this->db->firstRaw("SELECT `id`, `chi_tan_phat` FROM `modules` WHERE `link` = 'groups' LIMIT 1");
        if (empty($m['id'])){ echo "  Khong co module `groups` — bo qua cap quyen.\n"; return; }
        if (!empty($m['chi_tan_phat'])){
            echo "  CANH BAO: `groups` van mang co chi_tan_phat — cap quyen khong co tac dung.\n";
        }
        $moduleId = (int) $m['id'];

        $them = 0;
        foreach ((array) $this->db->getRaw(
                    "SELECT `id` FROM `groups` WHERE `name` = 'Manager' AND `garage_id` IS NOT NULL") as $n){
            foreach (['view', 'permission'] as $role){
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
        echo "  Da cap $them dong quyen man Quan ly nhom cho cac nhom Manager.\n";
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

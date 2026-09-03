<?php
/**
 * NHIỀU GARA — tầng 1: khái niệm gara.
 *
 * Hệ thống đang phục vụ một đơn vị. Sắp tới nhiều gara cùng dùng chung một
 * bản cài, nên cần biết mỗi kho, mỗi nhân viên, mỗi báo giá thuộc về gara nào.
 *
 * GARA KHÔNG PHẢI LÀ KHO
 * Hai dòng đang có trong `warehouses` ("Kho tổng", "Kho chi nhánh Miền Nam")
 * đúng là hai cái kho của Tân Phát, không phải hai gara. Cài gara đè lên khái
 * niệm kho thì hai dòng đó bị hiểu sai, và sau này một gara muốn tách kho phụ
 * tùng với kho dầu nhớt là bế tắc. Vì vậy gara là bảng riêng, kho thuộc về gara.
 *
 * `garage_id` Ở ĐÂY CHỈ ĐỂ GHI NHẬN, KHÔNG PHẢI ĐỂ CHẶN XEM
 * Đã chốt: các gara thấy được dữ liệu của nhau (kỹ thuật viên cần biết xe đã
 * thay gì, khi nào — kể cả khi lần trước xe sửa ở chi nhánh khác). Nên cột này
 * dùng để biết ai lập và lấy giá nào, không dùng để lọc quyền.
 *
 * CỘT ĐỂ NULL ĐƯỢC
 * Dữ liệu cũ được gán hết về gara tổng ngay bên dưới, nhưng cột vẫn để NULL
 * được: nếu sau này ai đó xoá một gara, các chứng từ của nó phải còn lại chứ
 * không được biến mất theo (ON DELETE SET NULL).
 */

use App\core\Migration;

return new class extends Migration {

    protected $link  = 'garages';
    protected $name  = 'Quản lý gara';
    protected $roles = ['view', 'add', 'edit', 'delete'];

    /** Các bảng cần gắn chủ sở hữu: bảng => tên khoá ngoại */
    protected $gan = [
        'warehouses'     => 'fk_wh_garage',
        'users'          => 'fk_user_garage',
        'quotations'     => 'fk_quote_garage',
        'sales_invoices' => 'fk_inv_garage',
    ];

    public function up(){
        $now = date('Y-m-d H:i:s');

        $this->run("
            CREATE TABLE IF NOT EXISTS `garages` (
                `id`         INT AUTO_INCREMENT PRIMARY KEY,
                `code`       VARCHAR(30)  NOT NULL,
                `name`       VARCHAR(150) NOT NULL,
                `address`    VARCHAR(255) DEFAULT NULL,
                `phone`      VARCHAR(30)  DEFAULT NULL,
                /* 1 = gara tổng, chủ sở hữu danh mục tổng. Chỉ một dòng được
                   bật cờ này; GaragesModel::clearMasterExcept() lo việc đó. */
                `is_master`  TINYINT(1)   NOT NULL DEFAULT 0,
                `status`     TINYINT(1)   NOT NULL DEFAULT 1,
                `sort_order` INT          NOT NULL DEFAULT 0,
                `create_at`  DATETIME     DEFAULT NULL,
                `update_at`  DATETIME     DEFAULT NULL,
                UNIQUE KEY `uq_garage_code` (`code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Gara tổng — phải có trước khi gán dữ liệu cũ về nó.
        $master = $this->db->table('garages')->where('is_master', '=', 1)->first();
        if (empty($master)){
            $this->db->insert('garages', [
                'code' => 'TP01', 'name' => 'Tân Phát', 'is_master' => 1,
                'status' => 1, 'sort_order' => 0, 'create_at' => $now,
            ]);
            $masterId = (int) $this->db->lastId();
            echo "  Da tao gara tong TP01 — Tan Phat.\n";
        } else {
            $masterId = (int) $master['id'];
        }

        foreach ($this->gan as $bang => $fk){
            if (!$this->hasTable($bang)) continue;

            if (!$this->hasColumn($bang, 'garage_id')){
                $this->run("ALTER TABLE `$bang` ADD COLUMN `garage_id` INT DEFAULT NULL");
                $this->run("ALTER TABLE `$bang` ADD KEY `idx_{$bang}_garage` (`garage_id`)");
                $this->run("ALTER TABLE `$bang`
                            ADD CONSTRAINT `$fk` FOREIGN KEY (`garage_id`)
                            REFERENCES `garages` (`id`) ON DELETE SET NULL ON UPDATE CASCADE");
            }

            /* Dữ liệu cũ về gara tổng. Chỉ đụng dòng đang NULL nên chạy lại
               không ghi đè việc ai đó đã chuyển sang gara khác. */
            $this->db->query("UPDATE `$bang` SET `garage_id` = ? WHERE `garage_id` IS NULL", [$masterId]);
        }
        echo "  Da gan du lieu cu ve gara tong.\n";

        $this->dangKyModule();
    }

    /**
     * Đăng ký màn hình "Quản lý gara" vào `modules` + quyền cho nhóm Admin.
     *
     * Thiếu bước này thì màn hình có mà không hiện trong menu trái (menu dựng
     * từ bảng `modules`), và RoleMiddleware không khớp được `admin/garages/*`
     * nên màn hình đó thành ra KHÔNG được gác. Đã quên đúng chỗ này một lần
     * khi làm màn "Quản lý module".
     */
    private function dangKyModule(){
        $now = date('Y-m-d H:i:s');

        $module = $this->db->table('modules')->where('link', '=', $this->link)->first();
        if (empty($module)){
            $this->db->insert('modules', [
                'name' => $this->name, 'link' => $this->link, 'create_at' => $now,
            ]);
            $moduleId = (int) $this->db->lastId();
        } else {
            $moduleId = (int) $module['id'];
        }

        $admin = $this->db->table('groups')->where('name', '=', 'Admin')->first();
        if (empty($admin)) return;

        foreach ($this->roles as $role){
            $has = $this->db->table('permissions')
                ->where('module_id', '=', $moduleId)
                ->where('group_id', '=', $admin['id'])
                ->where('role', '=', $role)
                ->first();
            if (empty($has)){
                $this->db->insert('permissions', [
                    'module_id' => $moduleId, 'group_id' => $admin['id'], 'role' => $role,
                ]);
            }
        }

        echo "  Da dang ky module \"{$this->name}\" (/admin/{$this->link}).\n";
    }

    /**
     * Cột đã tồn tại chưa.
     *
     * KHÔNG dùng `SHOW COLUMNS FROM x LIKE ?`: MySQL không nhận tham số ở vị
     * trí đó và ném lỗi 1064. Lấy hết rồi lọc bằng PHP.
     */
    protected function hasColumn($bang, $cot){
        try {
            $rows = $this->db->query("SHOW COLUMNS FROM `$bang`")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e){
            return false;
        }
        foreach ($rows as $r){
            if (isset($r['Field']) && $r['Field'] === $cot) return true;
        }
        return false;
    }

    public function down(){
        foreach ($this->gan as $bang => $fk){
            if (!$this->hasTable($bang) || !$this->hasColumn($bang, 'garage_id')) continue;
            try { $this->run("ALTER TABLE `$bang` DROP FOREIGN KEY `$fk`"); } catch (\Throwable $e){}
            $this->run("ALTER TABLE `$bang` DROP COLUMN `garage_id`");
        }

        $module = $this->db->table('modules')->where('link', '=', $this->link)->first();
        if (!empty($module)){
            $this->db->delete('permissions', '`module_id` = ?', [$module['id']]);
            $this->db->delete('modules', '`id` = ?', [$module['id']]);
        }

        $this->run("DROP TABLE IF EXISTS `garages`");
    }
};

<?php
/**
 * NHIỀU GARA — tầng 2: danh mục riêng của gara.
 *
 * Lúc lập báo giá, người dùng chọn nguồn "Kho tổng" hay "Gara hiện tại". Hai
 * nguồn đó khác nhau ở hai điểm, và mỗi điểm cần một thứ riêng:
 *
 *   HÀNG RIÊNG  -> `parts`.`garage_id`
 *     NULL = hàng của danh mục tổng (mọi gara đều thấy).
 *     Có giá trị = hàng chỉ gara đó có: phụ tùng mua ngoài, dịch vụ đặc thù.
 *
 *     Để NULL cho hàng tổng chứ KHÔNG trỏ về gara tổng: 18 dòng đang có giữ
 *     nguyên, không phải cập nhật gì, không sợ sót dòng nào.
 *
 *   GIÁ RIÊNG   -> bảng `garage_part_prices`
 *     Một dòng ở đây mang HAI nghĩa cùng lúc: "gara này có làm mặt hàng đó" và
 *     "với giá này". Giá bỏ trống thì lấy giá tổng. Nhờ vậy không cần thêm một
 *     bảng thứ hai chỉ để tick chọn mặt hàng nào mình làm.
 *
 * BA KHOÁ NGOẠI, BA CÁCH XOÁ KHÁC NHAU — cố ý, không phải quên:
 *
 *   parts.garage_id            -> RESTRICT
 *     Xoá gara mà xoá luôn hàng của nó thì báo giá cũ trỏ vào khoảng không.
 *     SET NULL còn tệ hơn: hàng riêng của một gara bỗng nhảy vào danh mục tổng
 *     và mọi gara khác đều thấy. Nên chặn hẳn, và màn Quản lý gara nói rõ lý do.
 *
 *   garage_part_prices.garage_id -> CASCADE
 *     Bảng giá không có nghĩa gì khi gara không còn. Nhưng vì parts đã RESTRICT
 *     nên thực tế chỉ xoá được gara chưa có hàng riêng.
 *
 *   garage_part_prices.part_id   -> CASCADE
 *     Mặt hàng bị xoá khỏi danh mục tổng thì giá riêng cho nó là rác.
 */

use App\core\Migration;

return new class extends Migration {

    protected $link  = 'garage-catalog';
    protected $name  = 'Danh mục của gara';
    protected $roles = ['view', 'add', 'edit', 'delete'];

    public function up(){
        if (!$this->hasColumn('parts', 'garage_id')){
            $this->run("ALTER TABLE `parts` ADD COLUMN `garage_id` INT DEFAULT NULL");
            $this->run("ALTER TABLE `parts` ADD KEY `idx_parts_garage` (`garage_id`)");
            $this->run("ALTER TABLE `parts`
                        ADD CONSTRAINT `fk_part_garage` FOREIGN KEY (`garage_id`)
                        REFERENCES `garages` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE");
            echo "  Da them `parts`.`garage_id` (NULL = danh muc tong).\n";
        }

        $this->run("
            CREATE TABLE IF NOT EXISTS `garage_part_prices` (
                `id`         INT AUTO_INCREMENT PRIMARY KEY,
                `garage_id`  INT NOT NULL,
                `part_id`    INT NOT NULL,
                /* NULL = dùng giá của danh mục tổng. Khác 0: 0 là giá hợp lệ
                   (hạng mục khuyến mãi, kiểm tra miễn phí). */
                `price`      DECIMAL(15,2) DEFAULT NULL,
                `sale_price` DECIMAL(15,2) DEFAULT NULL,
                `status`     TINYINT(1) NOT NULL DEFAULT 1,
                `create_at`  DATETIME DEFAULT NULL,
                `update_at`  DATETIME DEFAULT NULL,

                /* Một gara chỉ có MỘT giá cho một mặt hàng. Thiếu ràng buộc này
                   thì tick chọn hai lần là hai dòng, và giá nào thắng tuỳ thứ
                   tự truy vấn. */
                UNIQUE KEY `uq_gpp` (`garage_id`, `part_id`),
                KEY `idx_gpp_part` (`part_id`),

                CONSTRAINT `fk_gpp_garage` FOREIGN KEY (`garage_id`)
                    REFERENCES `garages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_gpp_part` FOREIGN KEY (`part_id`)
                    REFERENCES `parts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->dangKyModule();
    }

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
                ->where('role', '=', $role)->first();
            if (empty($has)){
                $this->db->insert('permissions', [
                    'module_id' => $moduleId, 'group_id' => $admin['id'], 'role' => $role,
                ]);
            }
        }
        echo "  Da dang ky module \"{$this->name}\" (/admin/{$this->link}).\n";
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

    public function down(){
        $this->run("DROP TABLE IF EXISTS `garage_part_prices`");

        if ($this->hasColumn('parts', 'garage_id')){
            /* Hàng riêng của các gara phải dọn trước, nếu không thì gỡ cột xong
               chúng nằm lẫn vào danh mục tổng mà không ai biết. */
            $this->db->query("DELETE FROM `parts` WHERE `garage_id` IS NOT NULL");
            try { $this->run("ALTER TABLE `parts` DROP FOREIGN KEY `fk_part_garage`"); } catch (\Throwable $e){}
            $this->run("ALTER TABLE `parts` DROP COLUMN `garage_id`");
        }

        $module = $this->db->table('modules')->where('link', '=', $this->link)->first();
        if (!empty($module)){
            $this->db->delete('permissions', '`module_id` = ?', [$module['id']]);
            $this->db->delete('modules', '`id` = ?', [$module['id']]);
        }
    }
};

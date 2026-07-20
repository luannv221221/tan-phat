<?php
/**
 * KHO-3 — Vị trí lưu trữ trong kho (kho nhiều tầng/kệ).
 *
 * Mở rộng quyết định ban đầu "vị trí ghi text" thành DANH MỤC vị trí có cấu trúc
 * cây (Khu → Tầng → Kệ → Ngăn → Ô, tối đa 5 cấp) để chuẩn hoá chỗ để hàng.
 *   - Tự tham chiếu parent_id (cây trong 1 kho).
 *   - level 1..5, full_path = chuỗi gộp tên các cấp ("Khu A / Tầng 2 / Kệ 3").
 *   - Dòng phiếu nhập vẫn ghi vị trí dạng text; danh mục này cấp gợi ý (datalist).
 *
 * Cũng đăng ký báo cáo "Hàng tồn lâu" (ton-kho-lau) — chỉ xem.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){

        $now = date('Y-m-d H:i:s');

        $this->run("
            CREATE TABLE IF NOT EXISTS `warehouse_locations` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `warehouse_id` INT(11) NOT NULL,
                `parent_id` INT(11) DEFAULT NULL,
                `code` VARCHAR(50) NOT NULL,
                `name` VARCHAR(150) NOT NULL,
                `level` TINYINT(1) NOT NULL DEFAULT 1,
                `full_path` VARCHAR(500) DEFAULT NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_wl_wh` (`warehouse_id`),
                KEY `idx_wl_parent` (`parent_id`),
                CONSTRAINT `fk_wl_warehouse`
                    FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_wl_parent`
                    FOREIGN KEY (`parent_id`) REFERENCES `warehouse_locations` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Đăng ký module + quyền Admin ----------
        $modules = [
            'warehouse-locations' => 'Vị trí trong kho',
            'ton-kho-lau'         => 'Hàng tồn lâu',
        ];
        $viewOnly = ['ton-kho-lau' => true];

        $admin = $this->db->table('groups')->where('name', '=', 'Admin')->first();
        foreach ($modules as $link => $name){
            $ex = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($ex)){
                $this->db->insert('modules', ['name' => $name, 'link' => $link, 'create_at' => $now]);
            }
            $module = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($admin) || empty($module)) continue;

            $roles = isset($viewOnly[$link]) ? ['view'] : ['view', 'add', 'edit', 'delete'];
            foreach ($roles as $role){
                $has = $this->db->table('permissions')
                    ->where('module_id', '=', $module['id'])
                    ->where('group_id', '=', $admin['id'])
                    ->where('role', '=', $role)->first();
                if (empty($has)){
                    $this->db->insert('permissions', [
                        'module_id' => $module['id'], 'group_id' => $admin['id'], 'role' => $role,
                    ]);
                }
            }
        }
    }

    public function down(){
        $this->run("DROP TABLE IF EXISTS `warehouse_locations`");

        foreach (['warehouse-locations', 'ton-kho-lau'] as $link){
            $m = $this->db->table('modules')->where('link', '=', $link)->first();
            if (!empty($m)){
                $this->db->delete('permissions', '`module_id` = ?', [$m['id']]);
                $this->db->delete('modules', '`id` = ?', [$m['id']]);
            }
        }
    }
};

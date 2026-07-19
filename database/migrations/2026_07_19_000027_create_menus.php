<?php
/**
 * CMS — Menu website động (TASK_105-108). Thay nav hardcode ở storefront.
 * menus: cây cha-con (1 cấp submenu), label + url + target + sort + status.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $now = date('Y-m-d H:i:s');

        $this->run("
            CREATE TABLE IF NOT EXISTS `menus` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `parent_id` INT(11) DEFAULT NULL,
                `label` VARCHAR(150) NOT NULL,
                `url` VARCHAR(255) DEFAULT NULL,
                `target` VARCHAR(10) NOT NULL DEFAULT '_self',
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_menu_parent` (`parent_id`),
                CONSTRAINT `fk_menu_parent`
                    FOREIGN KEY (`parent_id`) REFERENCES `menus` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Seed menu mặc định (nếu chưa có menu nào)
        $cnt = $this->db->table('menus')->select('COUNT(*) AS c')->first();
        if ((int) ($cnt['c'] ?? 0) === 0){
            $seed = [
                ['Trang chủ', ''],
                ['Sản phẩm', 'san-pham'],
                ['Khuyến mãi', 'san-pham?promo=1'],
                ['Dự án', 'du-an'],
                ['Thư viện', 'thu-vien'],
                ['Tin tức', 'tin-tuc'],
            ];
            $i = 0;
            foreach ($seed as $s){
                $this->db->insert('menus', [
                    'parent_id' => null, 'label' => $s[0], 'url' => $s[1],
                    'target' => '_self', 'sort_order' => $i++, 'status' => 1, 'create_at' => $now,
                ]);
            }
        }

        $admin = $this->db->table('groups')->where('name', '=', 'Admin')->first();
        $ex = $this->db->table('modules')->where('link', '=', 'menus')->first();
        if (empty($ex)){
            $this->db->insert('modules', ['name' => 'Menu website', 'link' => 'menus', 'create_at' => $now]);
        }
        $module = $this->db->table('modules')->where('link', '=', 'menus')->first();
        if (!empty($admin) && !empty($module)){
            foreach (['view', 'add', 'edit', 'delete'] as $role){
                $has = $this->db->table('permissions')
                    ->where('module_id', '=', $module['id'])->where('group_id', '=', $admin['id'])->where('role', '=', $role)->first();
                if (empty($has)){
                    $this->db->insert('permissions', ['module_id' => $module['id'], 'group_id' => $admin['id'], 'role' => $role]);
                }
            }
        }
    }

    public function down(){
        $this->run("DROP TABLE IF EXISTS `menus`");
        $m = $this->db->table('modules')->where('link', '=', 'menus')->first();
        if (!empty($m)){
            $this->db->delete('permissions', '`module_id` = ?', [$m['id']]);
            $this->db->delete('modules', '`id` = ?', [$m['id']]);
        }
    }
};

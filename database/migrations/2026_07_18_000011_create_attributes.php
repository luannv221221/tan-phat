<?php
/**
 * TASK_90 — Thông số kỹ thuật của phụ tùng (attributes / values).
 *
 * `attributes`            : định nghĩa thông số (Chất liệu, Trọng lượng, Điện áp...)
 * `part_attribute_values` : giá trị thông số theo từng phụ tùng (EAV)
 *
 * Cho phép quản trị viên tự khai báo thông số rồi gán giá trị cho phụ tùng,
 * và lọc phụ tùng theo thông số.
 *
 * Đăng ký luôn module `attributes` + cấp quyền Admin (như migration 000006/000007).
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $this->run("
            CREATE TABLE IF NOT EXISTS `attributes` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(150) NOT NULL,
                `slug` VARCHAR(180) NOT NULL,
                `unit` VARCHAR(30) DEFAULT NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_attributes_slug` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->run("
            CREATE TABLE IF NOT EXISTS `part_attribute_values` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `part_id` INT(11) NOT NULL,
                `attribute_id` INT(11) NOT NULL,
                `value` VARCHAR(255) NOT NULL,
                `create_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_pav_part_attr` (`part_id`, `attribute_id`),
                KEY `idx_pav_attr` (`attribute_id`),
                KEY `idx_pav_value` (`value`),
                CONSTRAINT `fk_pav_part`
                    FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_pav_attr`
                    FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Dữ liệu mồi
        $now = date('Y-m-d H:i:s');
        $seed = [
            ['Chất liệu',   'chat-lieu',   null, 0],
            ['Trọng lượng', 'trong-luong', 'kg', 1],
            ['Kích thước',  'kich-thuoc',  'mm', 2],
            ['Điện áp',     'dien-ap',     'V',  3],
        ];
        foreach ($seed as $s){
            $existed = $this->db->table('attributes')->where('slug', '=', $s[1])->first();
            if (empty($existed)){
                $this->db->insert('attributes', [
                    'name' => $s[0], 'slug' => $s[1], 'unit' => $s[2],
                    'sort_order' => $s[3], 'status' => 1, 'create_at' => $now,
                ]);
            }
        }

        // Đăng ký module + cấp quyền Admin
        $admin = $this->db->table('groups')->where('name', '=', 'Admin')->first();
        $existedM = $this->db->table('modules')->where('link', '=', 'attributes')->first();
        if (empty($existedM)){
            $this->db->insert('modules', ['name' => 'Thông số kỹ thuật', 'link' => 'attributes', 'create_at' => $now]);
        }
        $module = $this->db->table('modules')->where('link', '=', 'attributes')->first();

        if (!empty($admin) && !empty($module)){
            foreach (['view', 'add', 'edit', 'delete'] as $role){
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
        $this->run("DROP TABLE IF EXISTS `part_attribute_values`");
        $this->run("DROP TABLE IF EXISTS `attributes`");

        $m = $this->db->table('modules')->where('link', '=', 'attributes')->first();
        if (!empty($m)){
            $this->db->delete('permissions', '`module_id` = ?', [$m['id']]);
            $this->db->delete('modules', '`id` = ?', [$m['id']]);
        }
    }
};

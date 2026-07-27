<?php
/**
 * Quản lý Banner slider trang chủ storefront.
 *
 * Tạo bảng `banners` + đăng ký module 'banners' vào `modules` và cấp quyền
 * cho nhóm Admin (thiếu bước này RoleMiddleware sẽ chặn về "khong-co-quyen"
 * và menu không hiện link).
 */

use App\core\Migration;

return new class extends Migration {

    protected $roles = ['view', 'add', 'edit', 'delete'];

    public function up(){
        $this->run("
            CREATE TABLE IF NOT EXISTS `banners` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `title` VARCHAR(255) DEFAULT NULL,
                `image` VARCHAR(255) NOT NULL,
                `link` VARCHAR(255) DEFAULT NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_banners_status_sort` (`status`, `sort_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Đăng ký module + cấp quyền cho nhóm Admin
        $now   = date('Y-m-d H:i:s');
        $admin = $this->db->table('groups')->where('name', '=', 'Admin')->first();
        $adminId = !empty($admin) ? $admin['id'] : null;

        $existed = $this->db->table('modules')->where('link', '=', 'banners')->first();
        if (!empty($existed)){
            $moduleId = $existed['id'];
        } else {
            $this->db->insert('modules', ['name' => 'Banner', 'link' => 'banners', 'create_at' => $now]);
            $moduleId = $this->db->lastId();
        }

        if (!empty($adminId)){
            foreach ($this->roles as $role){
                $has = $this->db->table('permissions')
                    ->where('module_id', '=', $moduleId)
                    ->where('group_id', '=', $adminId)
                    ->where('role', '=', $role)->first();
                if (empty($has)){
                    $this->db->insert('permissions', [
                        'module_id' => $moduleId, 'group_id' => $adminId, 'role' => $role,
                    ]);
                }
            }
        }
    }

    public function down(){
        $m = $this->db->table('modules')->where('link', '=', 'banners')->first();
        if (!empty($m)){
            $this->db->delete('permissions', '`module_id` = ?', [$m['id']]);
            $this->db->delete('modules', '`id` = ?', [$m['id']]);
        }
        $this->run("DROP TABLE IF EXISTS `banners`");
    }
};

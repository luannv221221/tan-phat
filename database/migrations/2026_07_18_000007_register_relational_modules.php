<?php
/**
 * Đăng ký 4 danh mục xe có quan hệ (Ưu tiên 1) vào bảng `modules`
 * + cấp quyền cho nhóm Admin.
 *
 * Giống migration 000006 nhưng cho các màn hình phức tạp hơn:
 *   Hãng xe, Model xe, Đời xe, Danh mục phụ tùng.
 *
 * Không có bước này thì RoleMiddleware sẽ đá người dùng về "khong-co-quyen"
 * và menu không hiện link (dù code CRUD đã chạy được).
 */

use App\core\Migration;

return new class extends Migration {

    /** link => tên hiển thị trên menu */
    protected $modules = [
        'car-brands'      => 'Hãng xe',
        'car-models'      => 'Model xe',
        'car-years'       => 'Đời xe',
        'part-categories' => 'Danh mục phụ tùng',
    ];

    protected $roles = ['view', 'add', 'edit', 'delete'];

    public function up(){
        $now = date('Y-m-d H:i:s');

        $admin   = $this->db->table('groups')->where('name', '=', 'Admin')->first();
        $adminId = !empty($admin) ? $admin['id'] : null;

        foreach ($this->modules as $link => $name){

            $existed = $this->db->table('modules')->where('link', '=', $link)->first();

            if (!empty($existed)){
                $moduleId = $existed['id'];
            } else {
                $this->db->insert('modules', [
                    'name' => $name, 'link' => $link, 'create_at' => $now,
                ]);
                $moduleId = $this->db->lastId();
            }

            if (empty($adminId)) continue;

            foreach ($this->roles as $role){
                $has = $this->db->table('permissions')
                    ->where('module_id', '=', $moduleId)
                    ->where('group_id', '=', $adminId)
                    ->where('role', '=', $role)
                    ->first();

                if (empty($has)){
                    $this->db->insert('permissions', [
                        'module_id' => $moduleId,
                        'group_id'  => $adminId,
                        'role'      => $role,
                    ]);
                }
            }
        }
    }

    public function down(){
        foreach (array_keys($this->modules) as $link){
            $m = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($m)) continue;

            $this->db->delete('permissions', '`module_id` = ?', [$m['id']]);
            $this->db->delete('modules', '`id` = ?', [$m['id']]);
        }
    }
};

<?php
/**
 * Đăng ký module `modules` — màn hình "Quản lý module".
 *
 * Vòng luẩn quẩn: màn hình Quản lý module cũng là một màn hình admin, nên nó
 * cũng cần một dòng trong bảng `modules` thì RoleMiddleware mới gác được và
 * nó mới hiện trong bảng phân quyền. Nhưng chính nó lại là công cụ để tạo các
 * dòng đó. Nên dòng đầu tiên phải do migration gieo.
 *
 * KHÔNG tạo bảng, không sửa cấu trúc — chỉ thêm 1 dòng `modules` và 4 dòng
 * `permissions` cho nhóm Admin. Chạy lại nhiều lần không sinh dòng trùng.
 */

use App\core\Migration;

return new class extends Migration {

    protected $link  = 'modules';
    protected $name  = 'Quản lý module';
    protected $roles = ['view', 'add', 'edit', 'delete'];

    public function up(){
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

        // Chỉ thêm quyền còn thiếu -> chạy lại nhiều lần không sinh dòng trùng.
        foreach ($this->roles as $role){
            $has = $this->db->table('permissions')
                ->where('module_id', '=', $moduleId)
                ->where('group_id', '=', $admin['id'])
                ->where('role', '=', $role)
                ->first();

            if (empty($has)){
                $this->db->insert('permissions', [
                    'module_id' => $moduleId,
                    'group_id'  => $admin['id'],
                    'role'      => $role,
                ]);
            }
        }

        echo "  Da dang ky module \"{$this->name}\" (/admin/{$this->link}).\n";
    }

    public function down(){
        $module = $this->db->table('modules')->where('link', '=', $this->link)->first();
        if (empty($module)) return;

        $this->db->delete('permissions', '`module_id` = ?', [$module['id']]);
        $this->db->delete('modules', '`id` = ?', [$module['id']]);
    }
};

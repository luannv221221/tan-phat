<?php
/**
 * Cấp quyền add/edit/delete cho nhóm Admin trên module `products`.
 *
 * Module `products` ("Quản lý hàng hoá") từ dump gốc chỉ có quyền `view`.
 * Nay màn hình này thành CRUD phụ tùng đầy đủ nên cần thêm 3 quyền còn lại.
 */

use App\core\Migration;

return new class extends Migration {

    protected $roles = ['add', 'edit', 'delete'];

    public function up(){
        $admin  = $this->db->table('groups')->where('name', '=', 'Admin')->first();
        $module = $this->db->table('modules')->where('link', '=', 'products')->first();

        if (empty($admin) || empty($module)) return;

        foreach ($this->roles as $role){
            $has = $this->db->table('permissions')
                ->where('module_id', '=', $module['id'])
                ->where('group_id', '=', $admin['id'])
                ->where('role', '=', $role)
                ->first();

            if (empty($has)){
                $this->db->insert('permissions', [
                    'module_id' => $module['id'],
                    'group_id'  => $admin['id'],
                    'role'      => $role,
                ]);
            }
        }
    }

    public function down(){
        $admin  = $this->db->table('groups')->where('name', '=', 'Admin')->first();
        $module = $this->db->table('modules')->where('link', '=', 'products')->first();
        if (empty($admin) || empty($module)) return;

        foreach ($this->roles as $role){
            $this->db->delete('permissions',
                '`module_id` = ? AND `group_id` = ? AND `role` = ?',
                [$module['id'], $admin['id'], $role]);
        }
    }
};

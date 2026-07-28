<?php
/**
 * Đăng ký module `customers` (Khách hàng — bảng `members`) + cấp quyền Admin.
 *
 * Thiếu dòng trong `modules` thì RoleMiddleware đá về "khong-co-quyen" và
 * menu không hiện link, dù code CRUD đã chạy được (xem CRUD_DANH_MUC.md mục 3).
 *
 * Cấp view/edit — KHÔNG cấp add/delete:
 *   - add: khách tự đăng ký ngoài website, admin không tạo hộ.
 *   - delete: khách có thể đã có đơn hàng / đánh giá; khoá ngoại là
 *     ON DELETE SET NULL nên xoá đi thì đơn mất dấu vết người đặt.
 *     Muốn chặn thì khoá tài khoản (status = 0).
 *
 * Chỉ thêm dòng còn thiếu nên chạy lại nhiều lần không sinh trùng.
 */

use App\core\Migration;

return new class extends Migration {

    protected $link  = 'customers';
    protected $name  = 'Khách hàng';
    protected $roles = ['view', 'edit'];

    public function up(){
        $now = date('Y-m-d H:i:s');

        $module = $this->db->table('modules')->where('link', '=', $this->link)->first();
        if (empty($module)){
            $this->db->insert('modules', [
                'name' => $this->name, 'link' => $this->link, 'create_at' => $now,
            ]);
            $moduleId = $this->db->lastId();
        } else {
            $moduleId = $module['id'];
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
                    'module_id' => $moduleId,
                    'group_id'  => $admin['id'],
                    'role'      => $role,
                ]);
            }
        }
    }

    public function down(){
        $module = $this->db->table('modules')->where('link', '=', $this->link)->first();
        if (empty($module)) return;

        $this->db->delete('permissions', '`module_id` = ?', [$module['id']]);
        $this->db->delete('modules', '`id` = ?', [$module['id']]);
    }
};

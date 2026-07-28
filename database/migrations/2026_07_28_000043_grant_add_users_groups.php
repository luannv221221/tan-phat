<?php
/**
 * Cấp quyền `add` cho nhóm Admin ở 2 module `users` và `groups`.
 *
 * Triệu chứng: vào /admin/users/add bị đá về /admin/khong-co-quyen, nên
 * "không thêm được người dùng" dù đang đăng nhập bằng tài khoản nhóm Admin.
 *
 * Nguyên nhân: nhóm Admin có sẵn view/edit/delete cho 2 module này (và cả
 * `permission` với `groups`) nhưng riêng `add` thì chưa bao giờ được cấp.
 * RoleMiddleware khớp URL với modules.link rồi kiểm tra role -> chặn.
 *
 * Các module khác không dính vì được đăng ký bằng migration cấp trọn 4 quyền;
 * `users` và `groups` là 2 module có từ trước cơ chế đó.
 *
 * Chỉ thêm dòng còn thiếu nên chạy lại nhiều lần không sinh trùng.
 */

use App\core\Migration;

return new class extends Migration {

    /** module link => các role cần đảm bảo có */
    protected $need = [
        'users'  => ['add'],
        'groups' => ['add'],
    ];

    public function up(){
        $admin = $this->db->table('groups')->where('name', '=', 'Admin')->first();
        if (empty($admin)) { return; }
        $adminId = $admin['id'];

        foreach ($this->need as $link => $roles){
            $module = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($module)) { continue; }

            foreach ($roles as $role){
                $has = $this->db->table('permissions')
                    ->where('module_id', '=', $module['id'])
                    ->where('group_id', '=', $adminId)
                    ->where('role', '=', $role)
                    ->first();

                if (empty($has)){
                    $this->db->insert('permissions', [
                        'module_id' => $module['id'],
                        'group_id'  => $adminId,
                        'role'      => $role,
                    ]);
                }
            }
        }
    }

    public function down(){
        $admin = $this->db->table('groups')->where('name', '=', 'Admin')->first();
        if (empty($admin)) { return; }

        foreach ($this->need as $link => $roles){
            $module = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($module)) { continue; }

            foreach ($roles as $role){
                $this->db->delete(
                    'permissions',
                    '`module_id` = ? AND `group_id` = ? AND `role` = ?',
                    [$module['id'], $admin['id'], $role]
                );
            }
        }
    }
};

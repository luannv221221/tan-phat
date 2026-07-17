<?php
/**
 * Đăng ký các danh mục xe/phụ tùng vào bảng `modules` + cấp quyền cho nhóm Admin.
 *
 * VÌ SAO CẦN: `RoleMiddleware` khớp URL hiện tại với `modules.link`.
 * Không có dòng trong `modules` thì:
 *   - Menu không hiện link (AppServiceProvider share `listModules` ra view)
 *   - Người dùng vào URL sẽ bị đá về "khong-co-quyen"
 * Nghĩa là code CRUD có chạy cũng KHÔNG AI vào được.
 *
 * Chỉ cấp quyền cho nhóm Admin. Manager/Staff do quản trị viên tự phân sau
 * qua màn hình Phân quyền — không đoán thay nghiệp vụ.
 */

use App\core\Migration;

return new class extends Migration {

    /** link => tên hiển thị trên menu */
    protected $modules = [
        'car-body-types'        => 'Dòng xe (kiểu dáng)',
        'car-fuels'             => 'Nhiên liệu',
        'car-colors'            => 'Màu xe',
        'product-brands'        => 'Thương hiệu phụ tùng',
        'product-origins'       => 'Xuất xứ',
        'product-manufacturers' => 'Hãng sản xuất',
        'product-units'         => 'Đơn vị tính',
    ];

    protected $roles = ['view', 'add', 'edit', 'delete'];

    public function up(){
        $now = date('Y-m-d H:i:s');

        // Nhóm Admin — tìm theo tên vì id khác nhau giữa các môi trường
        $admin = $this->db->table('groups')->where('name', '=', 'Admin')->first();
        $adminId = !empty($admin) ? $admin['id'] : null;

        foreach ($this->modules as $link => $name){

            // Chạy lại migration không được tạo trùng
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

            // Xoá quyền trước rồi mới xoá module
            $this->db->delete('permissions', '`module_id` = ?', [$m['id']]);
            $this->db->delete('modules', '`id` = ?', [$m['id']]);
        }
    }
};

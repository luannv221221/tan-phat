<?php
/**
 * Đăng ký module `services` — màn hình "Dịch vụ" riêng cho gara.
 *
 * VÌ SAO KHÔNG TẠO BẢNG MỚI:
 * Dịch vụ vẫn là dòng trong `parts` với `item_type = 'service'` (migration
 * 000054). Toàn bộ chứng từ — báo giá, hoá đơn, đơn hàng — đều trỏ `part_id`
 * sang `parts`. Tách dịch vụ ra bảng riêng là mọi bảng dòng hàng phải mang
 * thêm cặp (loại, id) và phải sửa lại từng chỗ join; đổi lại chẳng được gì vì
 * dịch vụ và phụ tùng dùng chung y hệt các cột tên/mã/giá.
 *
 * Cái khách cần là MÀN HÌNH NHẬP GỌN (chỉ tên + tiền) và một mục menu riêng,
 * chứ không phải một kho dữ liệu thứ hai. Module này lo đúng phần đó: cùng
 * bảng `parts`, khác giao diện.
 *
 * RoleMiddleware khớp URL với `modules.link`, thiếu dòng ở đây thì vào trang
 * bị đá về "khong-co-quyen" và menu cũng không hiện.
 */

use App\core\Migration;

return new class extends Migration {

    protected $link  = 'services';
    protected $name  = 'Dịch vụ';
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
    }

    public function down(){
        $module = $this->db->table('modules')->where('link', '=', $this->link)->first();
        if (empty($module)) return;

        // Chỉ gỡ MÀN HÌNH. Dữ liệu dịch vụ nằm trong `parts` nên không đụng tới:
        // hạ module xuống mà xoá luôn hàng hoá thì thủng cả chứng từ cũ.
        $this->db->delete('permissions', '`module_id` = ?', [$module['id']]);
        $this->db->delete('modules', '`id` = ?', [$module['id']]);
    }
};

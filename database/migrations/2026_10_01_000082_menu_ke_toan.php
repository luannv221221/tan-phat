<?php
/**
 * KẾ TOÁN — PHIẾU THU / PHIẾU CHI: mới dựng CHỖ ĐỨNG trên menu.
 *
 * Bước này CHỈ khai hai màn hình vào bảng `modules` và cấp quyền, để nhóm
 * "Kế toán" hiện ra ở menu trái. Chưa có bảng số liệu, chưa có nghiệp vụ —
 * bấm vào ra trang "đang xây dựng".
 *
 * VÌ SAO PHẢI CÓ MIGRATION CHỨ KHÔNG VIẾT CỨNG VÀO MENU
 * Menu trái chỉ vẽ những link CÓ trong bảng `modules` VÀ tài khoản có quyền
 * `view` — xem app/views/layouts/admin/sidebar.php. Thêm tên vào $menuGroups
 * mà không khai module thì nút không bao giờ hiện ra.
 *
 * LỊCH SỬ: trước đây từng có 9 module kế toán (accounts, vouchers, journal,
 * so-cai...) nhưng đã bị gỡ khỏi bảng `modules` — hiện CSDL không còn dòng
 * nào, cũng không còn bảng số liệu kế toán nào. Nên đây là làm lại từ đầu,
 * không phải bật lại cái cũ.
 *
 * QUYỀN: Admin và Manager đủ bốn quyền. Staff được `view` + `add` — ở quầy
 * chính nhân viên là người viết phiếu thu khi khách trả tiền; sửa và xoá
 * phiếu tiền thì để cấp quản lý. Muốn khác thì đổi ở Nhóm > Phân quyền,
 * không cần sửa code.
 *
 * KHÔNG đặt cờ `chi_tan_phat`: các gara độc lập nhau, gara nào cũng thu chi
 * tiền của gara đó.
 */

use App\core\Migration;

return new class extends Migration {

    const MODULES = [
        'phieu-thu' => 'Phiếu thu',
        'phieu-chi' => 'Phiếu chi',
    ];

    const QUYEN = [
        'Admin'   => ['view', 'add', 'edit', 'delete'],
        'Manager' => ['view', 'add', 'edit', 'delete'],
        'Staff'   => ['view', 'add'],
    ];

    public function up(){
        $now = date('Y-m-d H:i:s');

        foreach (self::MODULES as $link => $ten){
            $m = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($m)){
                $this->db->insert('modules', ['name' => $ten, 'link' => $link, 'create_at' => $now]);
                $moduleId = (int) $this->db->lastId();
            } else {
                $moduleId = (int) $m['id'];
            }

            foreach (self::QUYEN as $tenNhom => $roles){
                $nhom = $this->db->table('groups')->where('name', '=', $tenNhom)->first();
                if (empty($nhom)) continue;
                foreach ($roles as $role){
                    $co = $this->db->table('permissions')
                        ->where('module_id', '=', $moduleId)
                        ->where('group_id', '=', $nhom['id'])
                        ->where('role', '=', $role)->first();
                    if (!empty($co)) continue;
                    $this->db->insert('permissions', [
                        'module_id' => $moduleId, 'group_id' => $nhom['id'], 'role' => $role,
                    ]);
                }
            }
            echo "  Da khai man hinh \"$ten\" (/admin/$link).\n";
        }
    }

    public function down(){
        foreach (array_keys(self::MODULES) as $link){
            $m = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($m)) continue;
            $this->db->delete('permissions', '`module_id` = ?', [(int) $m['id']]);
            $this->db->delete('modules', '`id` = ?', [(int) $m['id']]);
        }
        echo "  Da go hai man hinh Phieu thu / Phieu chi khoi menu.\n";
    }
};

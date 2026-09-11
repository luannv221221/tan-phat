<?php
/**
 * QUẢN LÝ GARA TỰ THÊM NHÂN VIÊN CHO GARA MÌNH.
 *
 * Cấp cho nhóm Manager view/add/edit trên màn Người dùng. KHÔNG cấp delete:
 * nhân viên nghỉ thì khoá tài khoản (Trạng thái), không xoá — chứng từ cũ
 * vẫn ghi tên người lập.
 *
 * MỞ QUYỀN NÀY LÀ MỞ LẠI ĐÚNG CÁI LỖ 000068 VỪA ĐÓNG — nếu thiếu chốt chặn
 * trong controller: ai thêm được người dùng thì chọn được nhóm cao nhất, tạo
 * xong một tài khoản như thế là vòng qua mọi hạn chế. Chốt nằm ở
 * Users::phamVi() + GroupsModel::nhomGiaoDuoc():
 *   - chỉ gán được nhóm có quyền nằm GỌN TRONG quyền của mình (tập con thực
 *     sự, không rỗng) => với dữ liệu hiện tại, Manager chỉ gán được Staff;
 *   - gara của tài khoản mới luôn là gara của Manager, POST gửi gì cũng vậy;
 *   - chỉ thấy và sửa được tài khoản CÙNG GARA thuộc nhóm mình gán được —
 *     tức là không đụng được Admin, Manager khác, và cả chính mình.
 * Chạy migration này trên máy chưa có phần controller đó là mở toang cửa:
 * phải đẩy code TRƯỚC hoặc cùng lúc, không được chạy SQL trước.
 *
 * CHỈ ĐỤNG nhóm Manager. Chạy lại không sinh dòng trùng.
 */

use App\core\Migration;

return new class extends Migration {

    const NHOM  = 'Manager';
    const LINK  = 'users';
    const ROLES = ['view', 'add', 'edit'];

    public function up(){
        $nhom = $this->db->table('groups')->where('name', '=', self::NHOM)->first();
        $m    = $this->db->table('modules')->where('link', '=', self::LINK)->first();
        if (empty($nhom) || empty($m)){
            echo "  [BO QUA] Khong co nhom Manager hoac module users.\n";
            return;
        }

        $them = 0;
        foreach (self::ROLES as $role){
            $co = $this->db->table('permissions')
                ->where('module_id', '=', $m['id'])
                ->where('group_id', '=', $nhom['id'])
                ->where('role', '=', $role)->first();
            if (!empty($co)) continue;

            $this->db->insert('permissions', [
                'module_id' => $m['id'],
                'group_id'  => $nhom['id'],
                'role'      => $role,
            ]);
            $them++;
        }
        echo "  Da cap $them quyen tren man Nguoi dung cho Manager.\n";
    }

    public function down(){
        $nhom = $this->db->table('groups')->where('name', '=', self::NHOM)->first();
        $m    = $this->db->table('modules')->where('link', '=', self::LINK)->first();
        if (empty($nhom) || empty($m)) return;

        foreach (self::ROLES as $role){
            $this->db->delete('permissions',
                '`module_id` = ? AND `group_id` = ? AND `role` = ?',
                [$m['id'], $nhom['id'], $role]);
        }
        echo "  Da go quyen Nguoi dung cua Manager.\n";
    }
};

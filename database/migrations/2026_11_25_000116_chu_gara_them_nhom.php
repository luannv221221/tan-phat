<?php
/**
 * CHỦ GARA TỰ THÊM / ĐỔI TÊN / XOÁ NHÓM QUYỀN.
 *
 * Khách ghi 25/11/2026 ở dòng "Quản lý tài khoản người dùng": "Thiếu nhóm và
 * phân quyền cho nhóm". Gara có sẵn hai nhóm Manager + Staff; 08/10 (000093)
 * nhóm Manager chỉ được `view` + `permission` trên màn Quản lý nhóm, nên không
 * có nút Thêm nhóm — muốn tách Kế toán / Thợ / Lễ tân là không làm được.
 *
 * Cấp thêm `add` / `edit` / `delete` trên module `groups` cho MỌI nhóm Manager
 * thuộc một gara (garage_id NOT NULL) — kể cả nhóm Manager của gara tổng, vì
 * gara mở mới nhân bản quyền từ đó (MoGaraModel::nhanBanNhom) và thừa hưởng
 * luôn. Nhóm hệ thống (Admin) và nhóm Staff không đụng tới.
 *
 * Các chốt giữ an toàn đã có sẵn, không phụ thuộc vào việc thiếu ba quyền này:
 *   - nhóm rỗng quyền không vào được màn nào (RoleMiddleware);
 *   - chỉ sửa / xoá / phân quyền nhóm YẾU HƠN mình, cùng gara (suaQuyenDuoc);
 *   - không xoá nhóm còn người (Groups::delete);
 *   - không trùng tên nhóm trong một gara (GroupsModel::trungTen, cùng đợt).
 *
 * AN TOÀN KHI CHẠY TRƯỚC KHI ĐẨY CODE: chỉ thêm dòng `permissions`; code cũ
 * hiện nút Thêm nhóm và cho lưu — chỉ thiếu chốt trùng tên tới khi đẩy code.
 * Chạy lại được: dòng nào có rồi thì bỏ qua.
 *
 * Chạy tay trong phpMyAdmin (nếu không chạy migrate.php):
 *
 *   INSERT INTO permissions (group_id, module_id, role)
 *   SELECT g.id, m.id, r.role
 *     FROM `groups` g JOIN modules m ON m.link = 'groups'
 *     JOIN (SELECT 'add' role UNION SELECT 'edit' UNION SELECT 'delete') r
 *    WHERE g.name = 'Manager' AND g.garage_id IS NOT NULL
 *      AND NOT EXISTS (SELECT 1 FROM permissions p
 *                       WHERE p.group_id = g.id AND p.module_id = m.id AND p.role = r.role);
 *
 * Khong doi cau truc CSDL.
 */

use App\core\Migration;

return new class extends Migration {

    const ROLE = ['add', 'edit', 'delete'];

    public function up(){
        $m = $this->db->firstRaw("SELECT `id` FROM `modules` WHERE `link` = 'groups' LIMIT 1");
        if (empty($m['id'])){ echo "  Khong co module `groups` — bo qua.\n"; return; }
        $moduleId = (int) $m['id'];

        $them = 0;
        foreach ((array) $this->db->getRaw(
                    "SELECT `id` FROM `groups` WHERE `name` = 'Manager' AND `garage_id` IS NOT NULL") as $n){
            foreach (self::ROLE as $role){
                $co = $this->db->firstRaw("SELECT `id` FROM `permissions`
                                            WHERE `group_id` = ? AND `module_id` = ? AND `role` = ? LIMIT 1",
                                          [(int) $n['id'], $moduleId, $role]);
                if (!empty($co['id'])) continue;
                $this->db->insert('permissions', [
                    'group_id' => (int) $n['id'], 'module_id' => $moduleId, 'role' => $role,
                ]);
                $them++;
            }
        }
        echo "  Da cap $them dong quyen them / sua / xoa nhom cho cac nhom Manager.\n";
    }

    public function down(){
        $m = $this->db->firstRaw("SELECT `id` FROM `modules` WHERE `link` = 'groups' LIMIT 1");
        if (empty($m['id'])) return;
        $this->db->query("DELETE p FROM `permissions` p JOIN `groups` g ON g.`id` = p.`group_id`
                           WHERE g.`name` = 'Manager' AND g.`garage_id` IS NOT NULL
                             AND p.`module_id` = ? AND p.`role` IN ('add', 'edit', 'delete')",
                         [(int) $m['id']]);
    }
};

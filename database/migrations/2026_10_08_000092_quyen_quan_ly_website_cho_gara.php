<?php
/**
 * CẤP QUYỀN QUẢN LÝ WEBSITE CHO GARA — vá một chỗ hụt của bước 2 và 3.
 *
 * Migration 000086 và 000088 đã GỠ CỜ `chi_tan_phat` khỏi tám màn quản lý
 * website, nhưng QUÊN cấp quyền cho nhóm Manager. Hậu quả: màn đã mở ở tầng
 * module mà nhóm Manager không có dòng `view` nào, nên menu trái không vẽ ra —
 * gara có website riêng nhưng không vào đâu sửa được tên, logo, tin tức,
 * banner, menu của chính mình.
 *
 * Gỡ cờ và cấp quyền là HAI việc khác nhau, dễ tưởng là một:
 *   - cờ `chi_tan_phat`  : màn này có dành cho gara không?
 *   - bảng `permissions` : nhóm này được làm gì trên màn đó?
 * Thiếu vế nào cũng ra cùng một hiện tượng "không thấy màn", nên lúc thử dễ
 * tưởng đã xong.
 *
 * QUYỀN CẤP CHO AI:
 *
 *   Manager (chủ gara) — toàn quyền trên NỘI DUNG website của gara mình, và
 *     `view` + `edit` trên Cấu hình. Không cho `add`/`delete` ở Cấu hình vì đó
 *     là một biểu mẫu khoá-giá trị, thêm hay xoá không có nghĩa gì.
 *
 *   Staff — KHÔNG cấp gì. Viết bài, đổi logo, sửa menu là việc của chủ gara.
 *     Gara nào muốn giao cho nhân viên thì tự cấp ở Nhóm > Phân quyền, không
 *     cần sửa code.
 *
 * Chạy lại không sinh dòng trùng.
 */

use App\core\Migration;

return new class extends Migration {

    /** link màn => các quyền cấp cho Manager */
    const QUYEN = [
        'settings'        => ['view', 'edit'],
        'news'            => ['view', 'add', 'edit', 'delete'],
        'news-categories' => ['view', 'add', 'edit', 'delete'],
        'banners'         => ['view', 'add', 'edit', 'delete'],
        'menus'           => ['view', 'add', 'edit', 'delete'],
        'galleries'       => ['view', 'add', 'edit', 'delete'],
        'du-an'           => ['view', 'add', 'edit', 'delete'],
        /* Đăng ký bản tin: chỉ xem và xoá người trùng / rác. Không "thêm" —
           người ta tự đăng ký trên web, gõ tay vào đây là làm bẩn danh sách. */
        'newsletter'      => ['view', 'delete'],
    ];

    public function up(){
        $nhom = $this->db->table('groups')->where('name', '=', 'Manager')->first();
        if (empty($nhom['id'])){
            echo "  Khong co nhom Manager — bo qua.\n";
            return;
        }
        $nhomId = (int) $nhom['id'];
        $them = 0;

        foreach (self::QUYEN as $link => $roles){
            $m = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($m['id'])){ echo "  Khong co module `$link` — bo qua.\n"; continue; }
            $moduleId = (int) $m['id'];

            /* Màn còn mang cờ `chi_tan_phat` thì cấp quyền cũng vô ích —
               RoleMiddleware chặn trước khi tới phần kiểm quyền. Báo ra để
               người chạy biết mà xem lại, đừng im lặng. */
            if (!empty($m['chi_tan_phat'])){
                echo "  CANH BAO: `$link` van mang co chi_tan_phat — cap quyen khong co tac dung.\n";
            }

            foreach ($roles as $role){
                $co = $this->db->table('permissions')
                    ->where('module_id', '=', $moduleId)
                    ->where('group_id', '=', $nhomId)
                    ->where('role', '=', $role)->first();
                if (!empty($co)) continue;
                $this->db->insert('permissions', [
                    'module_id' => $moduleId, 'group_id' => $nhomId, 'role' => $role,
                ]);
                $them++;
            }
            echo "  `$link`: " . implode(', ', $roles) . "\n";
        }

        echo "  Da them $them dong quyen cho nhom Manager.\n";
    }

    public function down(){
        $nhom = $this->db->table('groups')->where('name', '=', 'Manager')->first();
        if (empty($nhom['id'])) return;
        $nhomId = (int) $nhom['id'];

        foreach (array_keys(self::QUYEN) as $link){
            $m = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($m['id'])) continue;
            $this->db->delete('permissions', '`module_id` = ? AND `group_id` = ?',
                              [(int) $m['id'], $nhomId]);
        }
        echo "  Da go quyen quan ly website cua nhom Manager.\n";
    }
};

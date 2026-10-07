<?php
/**
 * BƯỚC 6: MÀN "ĐẶT HÀNG KHO TỔNG" cho gara.
 *
 * Gara độc lập nhưng vẫn lấy hàng từ công ty. Trước đây việc này làm tay: kho
 * tổng tự lập phiếu xuất, gara tự lập phiếu nhập, hai bên gõ lại số liệu của
 * nhau và không có gì nối hai phiếu lại.
 *
 * Màn mới: gara chọn hàng + số lượng, bấm Đặt. Một lần đặt sinh ĐÚNG HAI phiếu
 * đã có sẵn trong hệ thống, ở trạng thái NHÁP:
 *     kho tổng -> phiếu XUẤT kho
 *     gara     -> phiếu NHẬP kho
 *
 * Hai phiếu ghi số của nhau ở ô Lý do. Không tự ghi sổ — kho tổng ghi sổ phiếu
 * xuất chính là bước duyệt. Xem DatHangNoiBoModel để biết vì sao.
 *
 * KHÔNG đặt cờ `chi_tan_phat`: đây là màn của GARA, không phải của kho tổng.
 * Kho tổng mở ra sẽ thấy câu "kho tổng không đặt hàng của chính mình" —
 * controller chặn, chứ không có cờ nào diễn đạt được "chỉ gara, trừ kho tổng".
 *
 * QUYỀN: Admin và Manager được đặt. Staff chỉ XEM — đặt hàng là cam kết mua,
 * sinh ra công nợ với công ty, nên để cấp quản lý gara bấm.
 */

use App\core\Migration;

return new class extends Migration {

    const LINK = 'dat-hang-kho-tong';
    const TEN  = 'Đặt hàng kho tổng';

    const QUYEN = [
        'Admin'   => ['view', 'add'],
        'Manager' => ['view', 'add'],
        'Staff'   => ['view'],
    ];

    public function up(){
        $now = date('Y-m-d H:i:s');

        $m = $this->db->table('modules')->where('link', '=', self::LINK)->first();
        if (empty($m)){
            $this->db->insert('modules', ['name' => self::TEN, 'link' => self::LINK, 'create_at' => $now]);
            $moduleId = (int) $this->db->lastId();
            echo "  Da khai man hinh \"" . self::TEN . "\" (/admin/" . self::LINK . ").\n";
        } else {
            $moduleId = (int) $m['id'];
            echo "  Man hinh da co san.\n";
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
        echo "  Da cap quyen.\n";
    }

    public function down(){
        $m = $this->db->table('modules')->where('link', '=', self::LINK)->first();
        if (empty($m)) return;
        $this->db->delete('permissions', '`module_id` = ?', [(int) $m['id']]);
        $this->db->delete('modules', '`id` = ?', [(int) $m['id']]);
        echo "  Da go man Dat hang kho tong.\n";
    }
};

<?php
/**
 * KẾ TOÁN — MÀN BÁO CÁO.
 *
 * Năm báo cáo tài liệu khách yêu cầu (14/10/2026), gộp vào MỘT màn bốn thẻ:
 *   1+2. Tổng thu / tổng chi theo ngày, tháng, năm
 *   3.   Khách hàng còn nợ gara bao nhiêu
 *   4.   Gara còn nợ nhà cung cấp bao nhiêu
 *   5.   Báo cáo kinh doanh
 *
 * MỘT MÀN, KHÔNG PHẢI NĂM: cả năm đều lọc theo cùng một khoảng ngày và cùng
 * đọc từ `acc_vouchers` + chứng từ bán / nhập. Tách năm mục menu là người dùng
 * phải nhớ mục nào ở đâu, và đổi khoảng ngày thì phải đổi năm lần.
 *
 * QUYỀN: Admin và Manager. Staff KHÔNG — báo cáo này nói ra toàn bộ doanh thu,
 * giá vốn, lãi gộp và công nợ của gara. Gara nào muốn cho nhân viên xem thì tự
 * cấp ở Nhóm › Phân quyền.
 *
 * CHỈ CÓ `view`: màn này không ghi gì, cấp thêm add/edit/delete là cấp một
 * quyền không gác cái gì — mà nhìn vào bảng phân quyền lại tưởng là có.
 */

use App\core\Migration;

return new class extends Migration {

    const LINK = 'bao-cao-ke-toan';
    const TEN  = 'Báo cáo kế toán';
    const NHOM = ['Admin', 'Manager'];

    public function up(){
        $m = $this->db->table('modules')->where('link', '=', self::LINK)->first();
        if (empty($m)){
            $this->db->insert('modules', [
                'name' => self::TEN, 'link' => self::LINK, 'create_at' => date('Y-m-d H:i:s'),
            ]);
            $moduleId = (int) $this->db->lastId();
            echo "  Da khai man \"" . self::TEN . "\" (/admin/" . self::LINK . ").\n";
        } else {
            $moduleId = (int) $m['id'];
            echo "  Man hinh da co san.\n";
        }

        $them = 0;
        foreach (self::NHOM as $tenNhom){
            /* MỌI nhóm cùng tên: từ migration 000093 mỗi gara có bộ nhóm riêng.
               Cấp cho nhóm đầu tiên là chỉ gara tổng thấy màn. */
            foreach ((array) $this->db->getRaw("SELECT `id` FROM `groups` WHERE `name` = ?", [$tenNhom]) as $n){
                $co = $this->db->firstRaw("SELECT `id` FROM `permissions`
                                            WHERE `module_id` = ? AND `group_id` = ? AND `role` = 'view' LIMIT 1",
                                          [$moduleId, (int) $n['id']]);
                if (!empty($co['id'])) continue;
                $this->db->insert('permissions', [
                    'module_id' => $moduleId, 'group_id' => (int) $n['id'], 'role' => 'view',
                ]);
                $them++;
            }
        }
        echo "  Da cap $them dong quyen `view` (Admin + Manager cua moi gara).\n";
    }

    public function down(){
        $m = $this->db->table('modules')->where('link', '=', self::LINK)->first();
        if (empty($m)) return;
        $this->db->delete('permissions', '`module_id` = ?', [(int) $m['id']]);
        $this->db->delete('modules', '`id` = ?', [(int) $m['id']]);
        echo "  Da go man Bao cao ke toan.\n";
    }
};

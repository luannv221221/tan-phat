<?php
/**
 * MÀN "TỒN KHO ĐẦU KỲ" — số lượng gara đang có lúc BẮT ĐẦU DÙNG PHẦN MỀM.
 *
 * Theo mẫu Excel khách gửi 12/10/2026 (Mau_Excel_Ton_Kho_Dau_Ky_Gara.xlsx):
 * tám cột Mã hàng / Tên hàng hoá / Đơn vị tính / Kho / Số lượng tồn đầu kỳ /
 * Đơn giá vốn / Thành tiền / Ghi chú, mỗi dòng là MỘT mã hàng tại MỘT kho.
 *
 * KHÔNG THÊM BẢNG NÀO. Tồn đầu kỳ là một PHIẾU NHẬP loại `ton_dau_ky`, đi qua
 * đúng bộ máy kho đang có (StocksModel::applyIn -> cập nhật `stocks`, giá vốn
 * bình quân, và ghi một dòng `stock_cards`). Nhờ vậy được ngay ba thứ mà không
 * viết lại dòng nào: tồn kho đúng, thẻ kho có dòng mở đầu, giá vốn đúng.
 *
 * Và quan trọng hơn: nó ĐẢO NGƯỢC ĐƯỢC. Mục 9 của tờ hướng dẫn dặn "chỉ import
 * tồn đầu kỳ một lần để tránh cộng trùng" — nhưng dặn thì vẫn có người bấm hai
 * lần. Là phiếu thì bấm hai lần sinh ra hai phiếu nhìn thấy được, huỷ ghi sổ
 * một cái là xong; còn nếu cộng thẳng vào bảng tồn thì số tự nhân đôi và không
 * ai biết để sửa.
 *
 * TÊN MÀN LÀ "TỒN KHO ĐẦU KỲ", KHÔNG PHẢI "TỒN ĐẦU KỲ". Hai chữ sau đã mang
 * nghĩa khác trong hệ thống: Thẻ kho và Biến động tồn hiện dòng "Tồn đầu kỳ" =
 * số dư tại đầu KHOẢNG NGÀY đang lọc (StocksModel::getBalanceBefore). Cùng tên
 * khác nghĩa là loại nhầm lẫn đắt nhất, nên tách chữ ngay từ đầu.
 *
 * QUYỀN: Admin và Manager. Staff KHÔNG — khai tồn đầu kỳ là việc làm đúng một
 * lần lúc mở sổ, và sai thì sai toàn bộ số liệu kho về sau. Không cấp `delete`
 * cho Manager: xoá phiếu làm ở màn Phiếu nhập kho, nơi đã có chốt "ghi sổ rồi
 * thì phải huỷ ghi sổ trước".
 */

use App\core\Migration;

return new class extends Migration {

    const LINK = 'ton-kho-dau-ky';
    const TEN  = 'Tồn kho đầu kỳ';

    const QUYEN = [
        'Admin'   => ['view', 'add'],
        'Manager' => ['view', 'add'],
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

        /* KHÔNG đặt cờ `chi_tan_phat`: đây là màn của GARA. Chính gara mới biết
           mình đang có bao nhiêu hàng lúc bắt đầu dùng phần mềm. */

        /* CẤP CHO MỌI NHÓM CÙNG TÊN, không phải nhóm đầu tiên tìm thấy. Từ
           migration 000093 mỗi gara có bộ nhóm riêng; lấy nhóm đầu tiên là chỉ
           gara tổng thấy màn, các gara khác mở ra không có gì — mà ở máy lập
           trình (đăng nhập bằng tài khoản Tân Phát) thì nhìn đâu cũng thấy đủ. */
        $them = 0;
        foreach (self::QUYEN as $tenNhom => $roles){
            foreach ((array) $this->db->getRaw("SELECT `id` FROM `groups` WHERE `name` = ?", [$tenNhom]) as $nhom){
                foreach ($roles as $role){
                    $co = $this->db->firstRaw("SELECT `id` FROM `permissions`
                                                WHERE `module_id` = ? AND `group_id` = ? AND `role` = ? LIMIT 1",
                                              [$moduleId, (int) $nhom['id'], $role]);
                    if (!empty($co['id'])) continue;
                    $this->db->insert('permissions', [
                        'module_id' => $moduleId, 'group_id' => (int) $nhom['id'], 'role' => $role,
                    ]);
                    $them++;
                }
            }
        }
        echo "  Da cap $them dong quyen (Admin + Manager cua moi gara).\n";
    }

    public function down(){
        $m = $this->db->table('modules')->where('link', '=', self::LINK)->first();
        if (empty($m)) return;
        $this->db->delete('permissions', '`module_id` = ?', [(int) $m['id']]);
        $this->db->delete('modules', '`id` = ?', [(int) $m['id']]);
        /* KHÔNG đụng vào các phiếu `ton_dau_ky` đã lập: chúng là chứng từ kho
           thật, đã ghi sổ và đã vào thẻ kho. Gỡ màn hình không có nghĩa là xoá
           số liệu — muốn bỏ thì huỷ ghi sổ từng phiếu ở màn Phiếu nhập kho. */
        echo "  Da go man Ton kho dau ky (giu nguyen cac phieu da lap).\n";
    }
};

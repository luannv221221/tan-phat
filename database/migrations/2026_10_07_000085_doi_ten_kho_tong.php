<?php
/**
 * ĐỔI TÊN KHO "Kho tổng" THÀNH "Kho <tên gara tổng>".
 *
 * Chữ "kho" trước đây mang HAI nghĩa trong hệ thống, và đó là nguồn gây hiểu
 * nhầm thật sự:
 *
 *   1. Cái KHO THẬT — nơi chứa hàng, có số lượng (bảng `warehouses`). Gara tổng
 *      có đúng một kho, và nó tên là "Kho tổng".
 *   2. Hai nút chọn BẢNG GIÁ trên form Lập báo giá, trước đây ghi "Kho nhà" /
 *      "Kho tổng" — không phải kho nào cả.
 *
 * Hậu quả: có người đi tìm "Kho nhà" trong ô chọn kho của màn Nhập kho, không
 * thấy, tưởng hệ thống thiếu chức năng. Thực ra kho của gara Tân Phát vẫn chọn
 * được bình thường — chỉ là nó tên "Kho tổng".
 *
 * Hai nút trên form báo giá đã đổi thành "Giá của gara" / "Giá công ty" (bỏ hẳn
 * chữ "kho"). Migration này dọn nốt vế còn lại: kho thật mang tên gara của nó,
 * cho đồng bộ với các gara khác (Kho gara Sài Gòn, Kho gara Đà Nẵng).
 *
 * CHỈ ĐỔI KHI TÊN ĐÚNG LÀ "Kho tổng" và kho đó THUỘC GARA TỔNG. Gara nào tự đặt
 * tên kho của mình là "Kho tổng" thì đó là tên họ chọn, không đụng vào. Chạy
 * lại lần hai cũng không làm gì thêm.
 */

use App\core\Migration;

return new class extends Migration {

    const TEN_CU = 'Kho tổng';

    public function up(){
        $gara = $this->db->table('garages')
                         ->where('is_master', '=', 1)
                         ->first();
        if (empty($gara)){
            echo "  Khong tim thay gara tong — bo qua.\n";
            return;
        }

        $tenMoi = 'Kho ' . trim((string) $gara['name']);

        $kho = $this->db->table('warehouses')
                        ->where('garage_id', '=', (int) $gara['id'])
                        ->where('name', '=', self::TEN_CU)
                        ->first();
        if (empty($kho)){
            echo "  Khong co kho nao ten \"" . self::TEN_CU . "\" o gara tong — bo qua.\n";
            return;
        }

        $this->db->update('warehouses',
            ['name' => $tenMoi, 'update_at' => date('Y-m-d H:i:s')],
            '`id` = ?', [(int) $kho['id']]);

        echo "  Kho #{$kho['id']}: \"" . self::TEN_CU . "\" -> \"$tenMoi\".\n";
    }

    public function down(){
        $gara = $this->db->table('garages')->where('is_master', '=', 1)->first();
        if (empty($gara)) return;

        $tenMoi = 'Kho ' . trim((string) $gara['name']);
        $kho = $this->db->table('warehouses')
                        ->where('garage_id', '=', (int) $gara['id'])
                        ->where('name', '=', $tenMoi)
                        ->first();
        if (empty($kho)) return;

        $this->db->update('warehouses',
            ['name' => self::TEN_CU, 'update_at' => date('Y-m-d H:i:s')],
            '`id` = ?', [(int) $kho['id']]);
        echo "  Da tra lai ten \"" . self::TEN_CU . "\".\n";
    }
};

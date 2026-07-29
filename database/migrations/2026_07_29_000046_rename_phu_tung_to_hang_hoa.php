<?php
/**
 * Đổi thuật ngữ hiển thị: "phụ tùng" -> "hàng hoá".
 *
 * LÝ DO NGHIỆP VỤ: gara bán cả sửa chữa và dịch vụ, website bán cả thiết bị
 * lẫn phụ tùng. "Hàng hoá" là từ bao trùm — một hàng hoá CÓ THỂ là phụ tùng,
 * thiết bị, hoặc dịch vụ. Gọi tất cả là "phụ tùng" là gọi thiếu.
 *
 * Tên menu lấy từ cột `modules`.`name` nên phải sửa bằng migration, không sửa
 * được trong file view. KHÔNG sửa migration cũ đã chạy: trên production chúng
 * đã được ghi nhận và sẽ bị bỏ qua, sửa cũng vô ích.
 *
 * KHÔNG đổi tên bảng/cột/lớp (`parts`, `part_id`, `PartsModel`...) — đó là tên
 * kỹ thuật, người dùng không nhìn thấy. Đổi chúng cần migration đổi tên bảng
 * và sửa toàn bộ mã nguồn, rủi ro cao mà không thêm giá trị nào.
 *
 * Chỉ cập nhật đúng dòng còn mang tên cũ nên chạy lại nhiều lần vẫn an toàn.
 */

use App\core\Migration;

return new class extends Migration {

    /** link module => [tên cũ, tên mới] */
    protected $renames = [
        'part-categories' => ['Danh mục phụ tùng',    'Danh mục hàng hoá'],
        'product-brands'  => ['Thương hiệu phụ tùng', 'Thương hiệu hàng hoá'],
        'products'        => ['Quản lý phụ tùng',     'Quản lý hàng hoá'],
    ];

    public function up(){
        foreach ($this->renames as $link => $pair){
            $this->db->update(
                'modules',
                ['name' => $pair[1], 'update_at' => date('Y-m-d H:i:s')],
                '`link` = ? AND `name` = ?',
                [$link, $pair[0]]
            );
        }
    }

    public function down(){
        foreach ($this->renames as $link => $pair){
            $this->db->update(
                'modules',
                ['name' => $pair[0]],
                '`link` = ? AND `name` = ?',
                [$link, $pair[1]]
            );
        }
    }
};

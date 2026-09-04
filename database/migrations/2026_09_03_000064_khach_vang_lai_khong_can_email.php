<?php
/**
 * KHÁCH VÃNG LAI KHÔNG CẦN EMAIL.
 *
 * Bảng `members` sinh ra cho người TỰ ĐĂNG KÝ trên website, nên email là tên
 * đăng nhập và để NOT NULL. Nhưng giờ màn CSKH còn quản lý xe của khách, mà
 * khách lái xe tới gara thì đa số không có — và cũng không cần — tài khoản
 * đăng nhập. Bắt nhập email nghĩa là bắt nhân viên bịa ra email giả, dữ liệu
 * bẩn hơn là để trống.
 *
 * SỐ ĐIỆN THOẠI mới là thứ nhận ra khách ở gara. Ràng buộc "phải có email
 * HOẶC số điện thoại" đặt ở controller chứ không ở CSDL: CHECK constraint chỉ
 * chạy từ MySQL 8.0.16, mà máy chạy bản nào thì không chắc.
 *
 * VÌ SAO UNIQUE VẪN GIỮ ĐƯỢC
 * MySQL cho phép NHIỀU dòng NULL trong một khoá UNIQUE (NULL không bằng NULL).
 * Nên trăm khách vãng lai cùng để trống email vẫn vào được, mà hai khách có
 * cùng một email thật thì vẫn bị chặn như cũ.
 *
 * PHẢI LƯU NULL, KHÔNG PHẢI CHUỖI RỖNG. Chuỗi rỗng thì bằng chính nó, nên
 * khách vãng lai thứ hai sẽ bị báo trùng email. Chốt ở MembersModel::adminAdd().
 *
 * `password` giữ NOT NULL: khách không có mật khẩu được gán một chuỗi băm ngẫu
 * nhiên không ai đoán ra, tức là có ô mật khẩu hợp lệ nhưng không đăng nhập
 * được. Để trống hay để chuỗi rỗng thì password_verify() có thể khớp bất ngờ.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $this->run("ALTER TABLE `members` MODIFY `email` VARCHAR(150) NULL DEFAULT NULL");
        echo "  `members`.`email` gio de trong duoc (khach vang lai).\n";

        /* Cấp quyền `add` cho module customers.
           Màn hình này trước nay chỉ xem và sửa nên nhóm Admin chỉ có `view` và
           `edit`. Thiếu `add` thì nút "Thêm khách hàng" không hiện (view gọi
           route() để quyết định vẽ hay không) VÀ RoleMiddleware chặn thẳng
           `admin/customers/add`. Code có đủ mà bấm không vào được. */
        $this->capQuyenThem('customers', 'Admin');
    }

    private function capQuyenThem($link, $tenNhom){
        $module = $this->db->table('modules')->where('link', '=', $link)->first();
        $nhom   = $this->db->table('groups')->where('name', '=', $tenNhom)->first();
        if (empty($module) || empty($nhom)) return;

        $co = $this->db->table('permissions')
                       ->where('module_id', '=', $module['id'])
                       ->where('group_id', '=', $nhom['id'])
                       ->where('role', '=', 'add')->first();
        if (!empty($co)) return;

        $this->db->insert('permissions', [
            'module_id' => $module['id'], 'group_id' => $nhom['id'], 'role' => 'add',
        ]);
        echo "  Da cap quyen `add` cho module customers (nhom $tenNhom).\n";
    }

    public function down(){
        $module = $this->db->table('modules')->where('link', '=', 'customers')->first();
        if (!empty($module)){
            $this->db->delete('permissions', '`module_id` = ? AND `role` = ?', [$module['id'], 'add']);
        }

        /* Quay lại NOT NULL thì những khách chưa có email phải được gán một
           giá trị nào đó, nếu không ALTER sẽ hỏng. Dùng địa chỉ vô hại theo id
           để không đụng vào khoá UNIQUE. */
        $this->db->query(
            "UPDATE `members` SET `email` = CONCAT('khach-', `id`, '@local.invalid')
              WHERE `email` IS NULL"
        );
        $this->run("ALTER TABLE `members` MODIFY `email` VARCHAR(150) NOT NULL");
    }
};

<?php

use App\core\Controller;

/**
 * KẾ TOÁN — PHIẾU THU. Mới là CHỖ ĐỨNG, chưa có nghiệp vụ.
 *
 * Chưa có bảng số liệu, chưa có form: màn hình này chỉ để nút trên menu trái
 * bấm vào có chỗ đến. Khi dựng nghiệp vụ thật thì thay nội dung ở đây và bỏ
 * view admin/ke-toan/sap-co.
 *
 * Quyền do RoleMiddleware gác theo module `phieu-thu` (migration 000082).
 */
class Phieuthu extends Controller {

    public function index(){
        $data = [
            'sub_content'          => 'admin/ke-toan/sap-co',
            'page_title'           => 'Phiếu thu',
            'content'              => ['page_name' => 'Phiếu thu'],
        ];
        $this->render('layouts/admin/master_admin', $data);
    }
}

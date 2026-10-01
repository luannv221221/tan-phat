<?php

use App\core\Controller;

/**
 * KẾ TOÁN — PHIẾU CHI. Mới là CHỖ ĐỨNG, chưa có nghiệp vụ.
 *
 * Xem chú thích ở Phieuthu.php — hai màn này đi cùng một cặp và sẽ dựng chung
 * một đợt.
 */
class Phieuchi extends Controller {

    public function index(){
        $data = [
            'sub_content'          => 'admin/ke-toan/sap-co',
            'page_title'           => 'Phiếu chi',
            'content'              => ['page_name' => 'Phiếu chi'],
        ];
        $this->render('layouts/admin/master_admin', $data);
    }
}

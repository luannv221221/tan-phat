<?php

use App\core\Controller;

/**
 * Dữ liệu tỉnh / phường cho các ô chọn địa chỉ trong quản trị.
 *
 * Đi qua server chứ KHÔNG để trình duyệt gọi thẳng API ngoài:
 *   - API ngoài có thể không cho trang khác gọi (CORS) — form sẽ trắng trơn;
 *   - đổi nhà cung cấp API thì sửa đúng một chỗ (dia_gioi_api_goc);
 *   - có nhớ tạm ở server nên nhiều người dùng chỉ tốn một lần gọi.
 */
class Diagioi extends Controller {

    private function ra($data){
        header('Content-Type: application/json; charset=utf-8');
        // Trình duyệt nhớ 1 giờ: danh sách này không đổi trong một phiên làm việc
        header('Cache-Control: private, max-age=3600');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function tinh(){
        $this->ra(dia_gioi_tinh());
    }

    public function xa($tinh = 0){
        $this->ra(dia_gioi_xa((int) $tinh));
    }
}

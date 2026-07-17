<?php

namespace App\app\middlewares;

use App\core\Middleware;
use App\core\Session;

/**
 * Chặn CSRF cho mọi request có thay đổi dữ liệu (POST/PUT/PATCH/DELETE).
 *
 * Vì sao cần:
 *   Cookie phiên được trình duyệt gửi kèm MỌI request tới domain này —
 *   kể cả request phát sinh từ một trang web khác. Nên kẻ tấn công có thể dựng
 *   trang bất kỳ chứa form trỏ về hệ thống, dụ nhân viên đang đăng nhập bấm vào,
 *   và request đó chạy dưới danh nghĩa nhân viên: tạo phiếu chi, sửa giá, xoá dữ liệu.
 *
 * Token trong form chặn được vì trang của kẻ tấn công không đọc được token
 * trong session của nạn nhân.
 */
class CsrfMiddleware extends Middleware {

    /** Các đường dẫn bỏ qua kiểm tra (vd: webhook từ đối tác) */
    protected $except = [
        // 'api/webhook/*',
    ];

    public function handle(){

        $method = isset($_SERVER['REQUEST_METHOD'])
                ? strtoupper($_SERVER['REQUEST_METHOD'])
                : 'GET';

        // GET/HEAD/OPTIONS không được phép đổi dữ liệu => không cần kiểm tra
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)){
            return true;
        }

        if ($this->isExcept()){
            return true;
        }

        $token = isset($_POST['_token']) ? $_POST['_token'] : null;

        // Hỗ trợ cả token gửi qua header (cho AJAX sau này)
        if (empty($token) && isset($_SERVER['HTTP_X_CSRF_TOKEN'])){
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        if (!Session::checkCsrf($token)){
            $this->reject();
            return false;
        }

        return true;
    }

    /** Đường dẫn hiện tại có nằm trong danh sách bỏ qua không */
    protected function isExcept(){
        $path = isset($_GET['module']) ? trim($_GET['module'], '/') : '';

        foreach ($this->except as $pattern){
            $regex = '#^' . str_replace('\*', '.*', preg_quote($pattern, '#')) . '$#';
            if (preg_match($regex, $path)) return true;
        }
        return false;
    }

    /** Từ chối request */
    protected function reject(){
        http_response_code(419); // 419 = token het han (quy uoc pho bien)

        if ($this->wantsJson()){
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error'   => 'CSRF token khong hop le hoac da het han',
                'message' => 'Vui long tai lai trang va thu lai.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo '<h3>Phien lam viec da het han</h3>';
        echo '<p>Yeu cau bi tu choi vi thieu CSRF token hop le. Vui long tai lai trang va thu lai.</p>';
        exit;
    }

    protected function wantsJson(){
        $accept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
        return strpos($accept, 'application/json') !== false;
    }
}

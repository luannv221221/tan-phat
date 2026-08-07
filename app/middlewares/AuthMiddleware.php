<?php
namespace App\app\middlewares;

use App\core\Middleware;
use App\core\Session;
use App\core\Request;
use App\core\Response;
use App\core\Load;

class AuthMiddleware extends Middleware {

    public function handle(){
        /*
         * Nếu link là dạng admin/* => cho phép truy cập vào trang admin nếu tồn tại session
         * Nếu link là dạng dang-nhap => cho phép truy cập vào trang login nếu không tồn tại session
         *
         * */

        $this->removeLoginToken(); //Xoá login token

        $response = new Response();

        $loginTokenModel = Load::model('LoginToken');

        if (!empty(Session::get('dataToken'))){
            $tokenId = Session::get('dataToken');
            $tokenData = $loginTokenModel->getToken($tokenId);

            if (!empty($tokenData)){
                $userId = $tokenData['user_id'];
                Session::set('dataUser', $userId);
            }else{
                Session::remove('dataUser');
                Session::remove('dataToken');
            }
        }else{
            $this->restoreFromRemember($loginTokenModel);
        }

        //trường hợp 1: Kiểm tra trang admin
        if (Request::is('admin/*')) {

            if (!Session::get('dataUser')) {
                $response->redirect('dang-nhap');
            }

            $this->setActivity(); //Lưu thời gian hoạt động cuối cùng của user

            return true;

        }elseif (Request::is('dang-nhap')){
            if (Session::get('dataUser')){
                $response->redirect('admin');
            }
        }
    }

    /**
     * Khôi phục phiên từ cookie "Ghi nhớ đăng nhập".
     *
     * Session PHP hết trước cookie là chuyện bình thường (đóng trình duyệt,
     * file session bị dọn). Có cookie hợp lệ thì dựng lại phiên, không bắt
     * đăng nhập lại.
     */
    private function restoreFromRemember($loginTokenModel){
        $raw = \App\core\Cookie::get(\LoginToken::REMEMBER_COOKIE);
        if (empty($raw)) return;

        $row = $loginTokenModel->findByRemember($raw);
        if (empty($row)){
            // Cookie sai hoặc token đã bị dọn -> vứt cookie đi cho khỏi hỏi lại
            // CSDL ở mọi request sau.
            \App\core\Cookie::remove(\LoginToken::REMEMBER_COOKIE);
            return;
        }

        // Cấp session id mới khi nâng từ cookie lên phiên đăng nhập — cùng lý do
        // với Session::regenerate() lúc đăng nhập thường (chống session fixation).
        Session::regenerate();
        Session::set('dataToken', $row['id']);
        Session::set('dataUser', $row['user_id']);
    }

    public function setActivity(){
        //$userId = Session::get('dataUser');

//        $userModel = Load::model('UsersModel');
//
//        $userModel->edit([
//            'current_activity' => date('Y-m-d H:i:s')
//        ], $userId);

        $loginTokenModel = Load::model('LoginToken');

        if (!empty(Session::get('dataToken'))){
            $tokenId = Session::get('dataToken');

            $loginTokenModel->edit([
                'current_activity' => date('Y-m-d H:i:s')
            ], $tokenId);
        }

    }

    /**
     * Xoá token quá hạn.
     *
     * Bản cũ nạp toàn bộ bảng login_token về PHP rồi xoá từng dòng một
     * => O(n) dòng đọc + O(n) câu DELETE ở MỌI request. Nay là 1 câu DELETE có index.
     * Chi tiết xem LoginToken::removeExpired().
     */
    public function removeLoginToken(){
        $loginTokenModel = Load::model('LoginToken');

        $minutes = defined('_SESSION_IDLE_MINUTES') ? _SESSION_IDLE_MINUTES : 15;

        $loginTokenModel->removeExpired($minutes, \LoginToken::REMEMBER_DAYS);
    }
}
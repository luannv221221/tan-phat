<?php
namespace App\app\providers;

use App\core\ServiceProvider;
use App\core\Session;
use App\core\Load;
use App\core\View;

class AppServiceProvider extends ServiceProvider {

    public function boot(){

        $userId = Session::get('dataUser');

        // boot() chạy TRƯỚC AuthMiddleware — mà 'dataUser' lại do middleware đặt.
        // Nên ở request admin đầu tiên ngay sau khi đăng nhập, 'dataUser' còn rỗng
        // và sidebar hiện ra trống trơn; phải bấm sang trang khác mới đủ menu.
        // Suy ra user từ 'dataToken' (Auth::postLogin đặt ngay lúc đăng nhập).
        if (empty($userId) && !empty(Session::get('dataToken'))){
            $tokenData = Load::model('LoginToken')->getToken(Session::get('dataToken'));
            if (!empty($tokenData)){ $userId = $tokenData['user_id']; }
        }

        if (!empty($userId)){

            $userModel = Load::model('UsersModel');
            $moduleModel = Load::model('ModulesModel');

            $dataUser = $userModel->getDetail($userId);

            $dataShare = [];

            $dataShare['content']['infoUser'] = $dataUser;

            //truy vấn tới bảng modules
            $dataShare['content']['listModules'] = $moduleModel->getLists();

            /* Gara đang làm việc + danh sách gara để đổi, cho thanh đầu trang.
               Chia sẻ ở đây thay vì để từng controller tự nạp: thiếu ở một
               controller là ô đổi gara biến mất đúng ở màn hình đó. */
            $dataShare['content']['garaHienTai'] = gara_hien_tai();
            $dataShare['content']['dsGara']      = Load::model('GaragesModel')->getActive();


            View::share($dataShare);
        }

    }
}
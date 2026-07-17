<?php
namespace App\app\providers;

use App\core\ServiceProvider;
use App\core\Session;
use App\core\Load;
use App\core\View;

class AppServiceProvider extends ServiceProvider {

    public function boot(){

        if (!empty(Session::get('dataUser'))){
            $userId = Session::get('dataUser');

            $userModel = Load::model('UsersModel');
            $moduleModel = Load::model('ModulesModel');

            $dataUser = $userModel->getDetail($userId);

            $dataShare = [];

            $dataShare['content']['infoUser'] = $dataUser;

            //truy vấn tới bảng modules
            $dataShare['content']['listModules'] = $moduleModel->getLists();


            View::share($dataShare);
        }

    }
}
<?php
//Thường gọi là BaseController
//Dùng để load model và load view

namespace App\core; //Khai báo namespace để autoload

use App\core\View;
use App\core\Template;
use App\app\App;

class Controller{

    public function model($model){
        /*
         * Có 2 bước:
         * - require
         * - Tạo object từ class model, trả về object của model tương ứng
         *
         * */
        $path = 'app/models/'.$model.'.php';
        if (file_exists($path)){
            require_once $path;
            if (class_exists($model)){
                $modelObject = new $model();
                return $modelObject;
            }
        }

        return false;
    }

    public function render($view, $data=[]){

        $dataShare = View::$dataShare;


        if (!empty($dataShare)){

            $dataAllow = View::$dataViewAllow;

            if (!empty($dataAllow)){
                if (in_array($view, $dataAllow)){
                    $data = array_merge_recursive($data, $dataShare);
                }
            }else{
                //$data = array_merge($data, $dataShare);
                $data = array_merge_recursive($data, $dataShare);

            }

        }



        if (!empty($data) && is_array($data)){
            extract($data);
        }

        $path = 'app/views/'.$view.'.php';


        if (file_exists($path)){

            if (preg_match('~^layouts/~', $view)){
                require_once $path;
            }else{

                $contentView = file_get_contents($path);

                //Xử lý thay thế template
                //$template = new Template();

               // var_dump(App::$app->template);

                $contentView = App::$app->template->run($contentView, $data);

                echo $contentView;
            }

        }

    }

}
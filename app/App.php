<?php
//file app.php giúp làm việc với các file trong thư mục controllers, models, views dễ đàng hơn

namespace App\app; //Khai báo namespace để autoload

use App\core\Route;
use App\core\Session;
use App\core\Template;

class App{
    private $__controller, $__action, $__params, $__route;
    public $template;
    public static $app;
    function __construct(){
        $this->__controller = 'home'; //controller mặc định (Sau đưa vào config)
        $this->__action = 'index'; //action mặc định
        $this->__params = []; //tham số mặc định

        $this->template = new Template();

        self::$app = $this; //Gán $app = object của class App()å

//        //Thực hiện khởi tạo đối tượng từ lớp Database
//        $db = new Database();
//        var_dump($db);

        //Khởi tạo session
        new Session();

        $this->handleServiceProvider();

        $this->__route = new Route();
        $this->handleUrl();

    }

    function handleUrl(){


        if (!empty($_GET['module'])){
            $url = $_GET['module'];//$url là 1 string có kiểu như: category/add/2 hoăc category/edit/1

            //Xử lý thay thế url Route
            $finalUrl = $this->__route->handleRoute();

            $this->updateConfigRouteMiddleware(); //Gọi update config middleware

            //Gọi Global Middleware
            $this->handleGlobalMiddlewares();

            //Gọi Route Middleware
            $routeUri = $this->__route->getRouteUri();

            $this->handleRouteMiddlewares($routeUri);

            if (!empty($finalUrl)){
                $url = $finalUrl;
            }

            $urlArr = explode('/', $url);//đổi chuỗi $url thành mảng $urlArr=array(0=>category,1=>add,2='')

            //var_dump($urlArr);
            $urlArr = array_filter($urlArr);



            //Xử lý mảng $urlArr để khắc phục lỗi tên Controller nằm trong folder
            $urlPath = 'app/controllers/';
            $controllerName = '';
            if (!empty($urlArr)){
                foreach ($urlArr as $keyUrl => $item){
                    $urlPath.=$item.'/';
                    $filePath = rtrim($urlPath, '/').'.php';
                    $filePathArr = explode('/', $filePath);
                    $filePathArr[count($filePathArr)-1] = ucfirst($filePathArr[count($filePathArr)-1]);
                    $filePath = implode('/', $filePathArr);
                    //echo $filePath.'<br/>';
//                    echo '<pre>';
//                    print_r($filePathArr);
//                    echo '</pre>';

                    //Kiểm tra $filePath tồn tại
                    if (file_exists($filePath)){
                        $controllerName = $item;
                        $urlPath = $filePath;
                        break;
                    }
                }
            }


           // echo $keyUrl;

//            echo $controllerName;
//            echo '<br/>';
       //     echo $urlPath;

            /*
             * Biến $controllerName sẽ dùng để khởi tạo object
             * Biến $urlPath sẽ dùng để require
             * */

            /*
             * Xử lý xoá các phần tử của mảng $urlArr nhỏ hơn key của phần từ
             * $controllerName
             * */
            for ($i = 0; $i<$keyUrl; $i++){
                unset($urlArr[$i]);
            }

            $urlArr = array_values($urlArr);

//            echo '<pre>';
//            print_r($urlArr);
//            echo '</pre>';

            //Xử lý controller
            if (!empty($urlArr[0])){
                $this->__controller = $urlArr[0];
                unset($urlArr[0]);
            }

            //Xử lý action
            if (!empty($urlArr[1])){
                $this->__action = $urlArr[1];
                unset($urlArr[1]);
            }

            $urlArr = array_values($urlArr); //Reset key array để làm gì?
            /*
            Việc reset key để sau này debug $this->__params dễ hiểu hơn
            Ví dụ: Cấu trúc url như sau:
            home/index/thamso1/thamso2/thamso3
            Khi explode sẽ ra mảng sau:
            [0] => home,
            [1] => index
            [2] => thamso1
            [3] => thamso2
            [4] => thamso3

            Mà mình đã xử lý unset phần tử số 0 và số 1 ở phần xử lý controller, action
            Mảng sẽ còn lại như sau:
            [2] => thamso1
            [3] => thamso2
            [4] => thamso3

            Mình sẽ gán mảng còn lại trên cho biến $this->__params, trong quá trình phát triển thêm vẫn cần phải xử lý tiếp mảng $this->__params này. Lúc đó key ở dạng không tuần tự sẽ gây khó hiểu và khó xử lý cho các bài toán nâng cấp sau này => Cần phải reset key để thành mảng tuần tự

            Lưu ý: Việc reset này nếu không thực hiện => Chương trình vẫn chạy bình thường
            */
            //var_dump($urlArr);
            $this->__params = $urlArr;

        }else{

            $this->updateConfigRouteMiddleware(); //Gọi update config middleware

            //Gọi Middleware
            $this->handleGlobalMiddlewares();

            //Gọi Route Middleware
            $routeUri = $this->__route->getRouteUri();

            $this->handleRouteMiddlewares($routeUri);

            $urlPath = 'app/controllers/'.$this->__controller.'.php';

        }

        //Chuyển chữ cái đầu của controller thành chữ hoa
        $this->__controller = ucfirst($this->__controller);

        //Import file controller (require_once)
        //if (file_exists('app/controllers/'.$this->__controller.'.php')){
        if (file_exists($urlPath)){
            //File: app/controllers/Category.php
           // require_once 'app/controllers/'.$this->__controller.'.php';
            require_once $urlPath;
            //Kiểm tra class tồn tại
            if (class_exists($this->__controller)){
                $this->__controller = new $this->__controller();

                $action = $this->__action;
                if (method_exists($this->__controller, $action)){
                    //$this->__controller->$action();
//                    echo '<pre>';
//                    print_r($this->__params);
//                    echo '</pre>';
                    //hàm này để làm gì?
                    call_user_func_array(
                        [$this->__controller, $this->__action],
                        $this->__params
                    );
                    /*
                    Hàm này tương đương với cách gọi:
                    $doituong->phuongthuc($thamso1, $thamso2)

                    Tuy nhiên, với cách gọi cũ sẽ rất khó để truyền tham số vào, mà các tham số đang lưu dưới dạng mảng ($this->__params)

                    Cho nên PHP cung cấp hàm call_user_func_array() để thay cho việc đó

                    Cú pháp hàm: call_user_func_array($callback, $args) 

                    Trong đó:
                    - $callback: Tên hàm cần gọi. Nếu trong lập trình hướng hàm (Hay còn gọi là hướng thủ tục) chỉ cần truyền vào tên hàm, trong lập trình hướng đối tượng phải truyền dưới dạng 1 mảng bao gồm 2 phần tử:

                    + Phần tử 1: Đối tượng cần gọi
                    + Phần tử 2: Tên phương thức (Action) cần gọi

                    - $args: Đây là mảng 1 chiều chứa giá trị các tham số
                    */

                }else{
                    $this->loadError();
                }

            }else{
                $this->loadError();
            }

        }else{
            $this->loadError();
        }
    }

    public function loadError($name='404'){
        require_once 'app/errors/'.$name.'.php';
        exit;
    }

    public function getCurrentController(){
        return $this->__controller;
    }

    public function handleGlobalMiddlewares(){
        global $config;

        if (!empty($config['app']['global_middleware'])){
            $allGlobalMiddleware = $config['app']['global_middleware'];

            foreach ($allGlobalMiddleware as $middleware){
                $middlewareObject = new $middleware;
                $middlewareObject->handle();
            }
        }

    }

    public function handleRouteMiddlewares($routeUri, $check=false){
        global $config;

        if (!empty($config['app']['route_middleware'])){
            $allRouteMiddleware = $config['app']['route_middleware'];

            if (!empty($allRouteMiddleware[$routeUri])){
                $middlewares = $allRouteMiddleware[$routeUri];
                if (is_array($middlewares)){
                    foreach ($middlewares as $middleware){
                        $middlewareObject = new $middleware;

                        if (!empty($check)){
                            $middlewareObject->path = $routeUri;
                        }

                        $status = $middlewareObject->handle();
                        if (!$status){
                            return false;
                        }
                    }
                }else{
                   $middlewareObject = new $middlewares;
                   return $middlewareObject->handle();
                }

            }

        }
    }

    //Update lại config route middleware nếu có tồn tại middleware theo group
    public function updateConfigRouteMiddleware(){

        global $config;

        if (!empty($config['app']['route_middleware'])){
            $allRouteMiddleware = $config['app']['route_middleware'];

            if (!empty($allRouteMiddleware)){

                foreach ($allRouteMiddleware as $key => $middleware){

                    //Kiểm tra key thuộc dạng: chuoi/*
                    if (preg_match('~^(.+)\/\*$~i', $key, $matches)){
                        if (!empty($matches[1])){
                            $groupName = trim($matches[1]);
                            $routeInGroup = $this->__route->getRouteInGroup($groupName);

                            if (!empty($routeInGroup)){
                                foreach ($routeInGroup as $route){
                                    $config['app']['route_middleware'][$route] = $middleware;
                                }
                            }
                        }

                        //Xoá config group route ban đầu
                        unset($config['app']['route_middleware'][$key]);
                    }
                }

            }

        }

    }

    public function handleServiceProvider(){
        global $config;
        if (!empty($config['app']['boot'])){
            foreach ($config['app']['boot'] as $provider){
                $providerObject = new $provider;

                $providerObject->boot();
            }
        }
    }
}
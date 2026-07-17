<?php

namespace App\core; //Khai báo namespace để autoload

use App\app\App;

class Route{

    static private $routes = [];

    private $routeUri = null; //Lưu lại key của route

    static private $pathGroup = null;

    static private $routeInGroup = []; //Lưu trữ các route riêng lẻ thuộc 1 group

    //Phương thức get để thiết lập route trong file web.php và api.php
    //Phương thức get đại diện cho HTTP GET
    /*
     * Ví dụ:
     * Route:get('/san-pham', 'product/index');
     *
     * */
    static public function get($path, $callback){
        //echo $path.'<br/>';
        //echo self::getPath();
        /*
         * So sánh giữa getPath() và $path
         * - Nếu getPath() và $path khớp nhau (biểu thức chính quy) => Lấy ra đường dẫn thật của route tìm được
         * - Nếu getPath() và $path không khớp nhau (biểu thức chính quy) => Trả về 404
         * */

        if (!empty(self::$pathGroup)){
            $path = self::$pathGroup.'/'.$path;
            self::$routeInGroup[self::$pathGroup][] = trim($path, '/');
        }

        $path = trim($path, '/');


        self::$routes['get'][$path] = $callback;

    }

    //Phương thức post để thiết lập route trong file web.php và api.php
    //Phương thức post đại diện cho HTTP POST
    static public function post($path, $callback){

        if (!empty(self::$pathGroup)){
            $path = self::$pathGroup.'/'.$path;
        }

        $path = trim($path, '/');

        self::$routes['post'][$path] = $callback;
    }

    static public function group($path, $callback){

        self::$pathGroup = $path;

        call_user_func($callback);

        self::$pathGroup = null;
    }

    //Phương thức getPath() dùng để lấy url hiện tại đang truy cập
    static public function getPath(){
        if (!empty($_GET['module'])){
            $url = $_GET['module'];
            $url = rtrim($url, '/');
        }else{
            $url = '/';
        }

        return $url;
    }

    //Lấy phương thức HTTP Request
    static public function getMethod(){
        $method = $_SERVER['REQUEST_METHOD'];
        $method = strtolower($method);
        return $method;
    }

    //Phương handleRoute để xử lý Route
    public function handleRoute(){
//        echo '<pre>';
//        print_r(self::$routeInGroup);
//        echo '</pre>';
        /*
         * $key => đại diện cho đường dẫn ảo
         * $value => đại diện cho đường dẫn thật
         * Ý tưởng xử lý:
         * - Đọc từng phần tử của mảng
         * - So sánh các $key với getPath(), nếu khớp nhau thì thay thế thành value của phần tử mảng tìm được
         * */

        $method = self::getMethod(); //Lấy HTTP Method của Request hiện

        $currentUrl = self::getPath(); //Phương thức lấy url hiện tại

        $finalUrl = null;

        if (!empty(self::$routes[$method])){
            foreach (self::$routes[$method] as $key => $value){

                //echo $key.' - '.$value.'<br/>';

                $pattern = '~^'.$key.'$~i';
                //echo $pattern.'<br/>';
                if (preg_match($pattern, $currentUrl)){
                    //echo $key.' - '.$value.'<br/>';
                    $finalUrl = preg_replace($pattern, $value, $currentUrl);

                    $this->routeUri = $key;

                    break;
                }
            }
        }

        return $finalUrl;
    }

    public function getRouteUri(){

        return $this->routeUri;
    }

    public function getRouteInGroup($groupName){
        if (!empty(self::$routeInGroup[$groupName])){
            return self::$routeInGroup[$groupName];
        }

        return false;
    }

    /*
     * Hàm is($path) sẽ kiểm tra xem 1 path có quyền truy cập hay không?
     * Ví dụ: Bạn đang ở trang list (nguoi-dung), trong trang có nút thêm người dùng (nguoi-dung/them)
     * + Kiểm tra link nguoi-dung/them xem có quyền truy cập hay không?
     * + Nếu có quyền truy cập => sẽ hiển thị nút thêm
     * + Nếu không có quyền truy cập => ẩn nút đó đi
     *
     * Các bước xử lý:
     * + Xác định được $path (Ví dụ: nguoi-dung/them)
     * + Chuyển đổi thành path trong route (Có biểu thức chính quy)
     * + Gọi Middleware tương ứng: Thông qua path route
     * + Kiểm tra trạng thái của phương thức handle trong middleware tương ứng và trả về kiểu dữ liệu true, false
     * */

    static public function is($path){
//        global $config;
//        echo '<pre>';
//        print_r($config['app']['route_middleware']);
//        echo '</pre>';

        /*
         * Lấy path route (Có biểu thức chính quy)
         *
         * */
        $method = self::getMethod();

        $pathRoute = null;

        if (!empty(self::$routes[$method])){
            foreach (self::$routes[$method] as $key => $value){
                $pattern = '~^'.$key.'$~i';

                if (preg_match($pattern, $path)){

                    $pathRoute = $key;

                    break;
                }
            }
        }

        /*
         * Gọi đến middleware tương ứng
         *
         * */
        if (!empty($pathRoute)){
            $middleware = App::$app->handleRouteMiddlewares($pathRoute, true);

            //var_dump($middleware);
            if (is_bool($middleware)){
                return $middleware;
            }
        }

        return true;
    }

    static public function getUrl($path){
        $url = _WEB_URL.'/'.$path;
        return $url;
    }

}


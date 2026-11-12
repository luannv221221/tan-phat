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

            /*
             * DÒNG NÀY LÀ PHÂN QUYỀN, KHÔNG PHẢI SỔ SÁCH CHO ĐẸP. Vá 11/11/2026.
             *
             * `'admin/*' => [AuthMiddleware, RoleMiddleware]` trong configs/app.php
             * KHÔNG phải một luật ký tự đại diện được xét lúc request.
             * App::updateConfigRouteMiddleware() BUNG nó ra thành từng tuyến một,
             * và danh sách tuyến của nhóm lấy từ getRouteInGroup() — tức là từ
             * đúng dòng này. Thiếu nó ở post() thì tuyến POST không có tên trong
             * danh sách, nên không được gắn middleware nào.
             *
             * Bảng middleware đánh khoá theo CHUỖI ĐƯỜNG DẪN chứ không theo
             * phương thức HTTP. Nên lỗi này nằm im rất lâu: tuyến POST nào trùng
             * tên với một tuyến GET (phần lớn các form: /add, /edit/5) thì được
             * bảo vệ ké tuyến GET ấy. Chỉ những tuyến CHỈ CÓ POST mới hở — và hở
             * thì hở hoàn toàn, không AuthMiddleware lẫn RoleMiddleware.
             *
             * Đo trên bảng tuyến thật ngày 11/11/2026: 111 tuyến POST của admin,
             * 16 tuyến không được gắn. CsrfMiddleware là global nên vẫn chạy, nên
             * người chưa đăng nhập không lọt; nhưng BẤT KỲ tài khoản đã đăng nhập
             * nào, bất kể vai trò, bất kể thuộc gara nào, đều gọi được 16 tuyến
             * đó. Đã dựng lại thật: tài khoản Staff không có một dòng quyền nào
             * trên module garage-catalog vẫn POST tạo được mặt hàng mới.
             *
             * tests/TuyenPostCoChanTest.php giữ chỗ này.
             */
            self::$routeInGroup[self::$pathGroup][] = trim($path, '/');
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

        /*
         * TÌM Ở BẢNG TUYẾN CỦA PHƯƠNG THỨC HIỆN TẠI, RỒI MỚI ĐẾN BẢNG GET.
         * Thêm phần rơi về 'get' ngày 11/11/2026.
         *
         * Câu hỏi mà is() trả lời là "người này có được vào ĐƯỜNG DẪN kia hay
         * không" — mà bảng middleware đánh khoá theo CHUỖI ĐƯỜNG DẪN, không
         * theo phương thức. Chỉ tìm trong bảng của phương thức hiện tại thì
         * trong lúc xử lý một POST, mọi câu hỏi về một đường dẫn CHỈ CÓ GET đều
         * không tìm thấy gì, và hàm rơi xuống `return true` ở cuối = CHO QUA.
         *
         * Đó là lý do năm cái chốt tự kiểm quyền trong controller chưa bao giờ
         * chặn được ai — chúng chạy trong lúc xử lý POST và hỏi về màn hình
         * (tuyến GET):
         *     Settings::save()          hỏi 'admin/settings'
         *     Baotri::saveConfig()      hỏi 'admin/nhac-bao-tri/edit/0'
         *     Chatadmin::reply()        hỏi 'admin/chat/edit/<id>'
         *     Tonkhodauky::postImport() hỏi 'admin/ton-kho-dau-ky/add'
         *     Dathangkhotong::postDat() hỏi 'admin/dat-hang-kho-tong/add'
         * Cả năm câu đều trả về true cho MỌI tài khoản. Nhìn vào controller thì
         * tưởng có gác, mà thực ra không — tệ hơn là không viết gì, vì người
         * đọc sau sẽ tin là đã có.
         *
         * Rơi về bảng GET cũng là điều đúng về mặt nghĩa: màn hình là tuyến
         * GET, nên "có được vào màn đó không" phải tra ở bảng GET.
         */
        $bangTim = [$method];
        if ($method !== 'get') $bangTim[] = 'get';

        foreach ($bangTim as $bang){
            if (empty(self::$routes[$bang])) continue;

            foreach (self::$routes[$bang] as $key => $value){
                $pattern = '~^'.$key.'$~i';

                if (preg_match($pattern, $path)){

                    $pathRoute = $key;

                    break 2;
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


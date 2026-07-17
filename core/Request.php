<?php

namespace App\core;
use App\core\Database;
use App\app\App;
use App\core\Route;

class Request{

    public $rules, $messages, $errors, $db;

    public function __construct(){
        $this->rules = [];
        $this->messages = [];
        $this->errors = [];
        $this->db = new Database();

    }

    //Phương thức lấy http method
    public function getMethod(){
        $method = $_SERVER['REQUEST_METHOD'];
        $method = strtolower($method);
        return $method;
    }

    public function getFields(){
        $method =  $this->getMethod();

        $result = []; //Lưu lại các giá  trị của $_GET, $_POST sau khi đã xử lý

        //Xử lý lấy dữ liệu với phương thức GET
        if ($method=='get'){

            if (!empty($_GET)){
                foreach ($_GET as $key=>$value){
                    if ($key!=='module'){
                        if (is_array($value)){
                            $result[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FORCE_ARRAY);
                        }else{
                            $result[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
                        }

                    }
                }
            }

        }

        //Xử lý lấy dữ liệu với phương thức POST
        if ($method=='post'){

            if (!empty($_POST)){
                foreach ($_POST as $key=>$value){
                    if ($key!=='module'){
                        if (is_array($value)){
                            $result[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FORCE_ARRAY);
                        }else{
                            $result[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
                        }

                    }
                }
            }

        }

        return $result;
    }

    //Phương thức isPost()
    public function isPost($name=''){
        $method =  $this->getMethod();
        if ($method=='post'){

            if (!empty($name)){
                if (!empty($name) && isset($_POST[$name])) {
                    return true;
                }

                return false;
            }

            return true;
        }

        return false;
    }

    //Phương thức isGet()
    public function isGet($name=''){
        $method =  $this->getMethod();

        if ($method=='get'){
            
            if (!empty($name)){
                if (!empty($name) && isset($_GET[$name])) {
                    return true;
                }
                
                return false;
            }
            
            return true;
        }

        return false;
    }

    //Phương thức rules để set các rules cần validate
    public function rules($rules = []){
        $this->rules = $rules;
    }

    //Phương thức message để thiết lập các thông báo validate
    public function message($messages = []){
        $this->messages = $messages;

    }

    //Phương thức validate để chạy/thực thi validate
    public function validate(){

        $rulesArr = $this->rules;
        $rulesArr = array_filter($rulesArr); //Loại bỏ các phần tử mảng có value rỗng

        $messagesArr = $this->messages;
        $messagesArr = array_filter($messagesArr); //Loại bỏ các phần tử mảng có value rỗng

        if (empty($rulesArr) || empty($messagesArr)){
            if (empty($rulesArr)){
                die('Bạn chưa thiết lập rules. Vui lòng gọi phương thức rules()');
            }else{
                die('Bạn chưa thiết lập message. Vui lòng gọi phương thức message()');
            }
        }


        $fieldData = $this->getFields();

        $checkValidate = true; //Mặc định giả sử validate đúng

        //Đọc từng phần tử mảng $rulesArr để kiểm tra validate với từng field
        foreach ($rulesArr as $fieldName => $ruleItem){
            $fieldName = trim($fieldName); //Loại bỏ khoảng trắng 2 đầu với fieldName
            $ruleItem = trim($ruleItem); //Loại bỏ khoảng trắng 2 đầu với ruleItem

            $ruleItemArr = array_filter(explode('|', $ruleItem));

            if (!empty($ruleItemArr)){
                foreach ($ruleItemArr as $ruleName){
                    $ruleNameArr = array_filter(explode(':', $ruleName));

                    //Nếu mảng $ruleNameArr có số lượng phần tử = 1 => các các rule đơn lẻ:
                    /*
                     * + required
                     * + email
                     *
                    Nếu mảng $ruleNameArr số lượng phần tử > 1 => Các rule có giá trị động
                    + min
                    + max
                    + match

                     * */

                    $ruleValue = null; //Biến để lưu giá trị động của rule
//                    if (count($ruleNameArr)==1){
//                       // echo $ruleName.'<br/>';
//                    }
//
                    if (count($ruleNameArr)>1){
                        //echo $ruleName.'<br/>';
                        $ruleValue = trim(end($ruleNameArr));
                        //echo 'RuleName: '.$ruleName.' - '.$ruleValue.'<br/>';
                    }


                    //Kiểm tra lỗi của từng field tương ứng với từng rule

                    //1. Required
                    if ($ruleName=='required' && empty($fieldData[$fieldName])){
                        //echo 'lỗi required với '.$fieldName;
                        $this->setErrors($fieldName, $ruleName, $this->getMessage($fieldName, $ruleName));

                        $checkValidate = false;
                    }

                    //2. Email
                    if ($ruleName=='email' && !filter_var($fieldData[$fieldName], FILTER_VALIDATE_EMAIL)){

                        $this->setErrors($fieldName, $ruleName, $this->getMessage($fieldName, $ruleName));

                        $checkValidate = false;
                    }

                    //3. Min
                    if ($ruleNameArr[0]=='min'){

                        if (!empty($ruleValue) && mb_strlen($fieldData[$fieldName], 'UTF-8')<$ruleValue){
                            $this->setErrors($fieldName, $ruleNameArr[0], $this->getMessage($fieldName, $ruleNameArr[0]));

                            $checkValidate = false;
                        }

                    }

                    //4. Max
                    if ($ruleNameArr[0]=='max'){

                        if (!empty($ruleValue) && mb_strlen($fieldData[$fieldName], 'UTF-8')>$ruleValue){
                            $this->setErrors($fieldName, $ruleNameArr[0], $this->getMessage($fieldName, $ruleNameArr[0]));

                            $checkValidate = false;
                        }

                    }

                    //5. Match
                    if ($ruleNameArr[0]=='match'){

                        if (!empty($ruleValue) && $fieldData[$fieldName]!=$fieldData[$ruleValue]){

                            $this->setErrors($fieldName, $ruleNameArr[0], $this->getMessage($fieldName, $ruleNameArr[0]));

                            $checkValidate =false;
                        }


                    }

                    //6. Unique
                    if ($ruleNameArr[0]=='unique'){
                        $tableNameDb = null;
                        $fieldNameDb = null;

                        if (!empty($ruleNameArr[1]) && !empty($ruleNameArr[2])){
                            $tableNameDb = trim($ruleNameArr[1]);
                            $fieldNameDb = trim($ruleNameArr[2]);

                            //Truy vấn database
                            if (!empty($fieldData[$fieldName])){

                                //kiểm tra xem có đang validate ở trang sửa không
                                if (!empty($ruleNameArr[3])){
                                    $condition = trim($ruleNameArr[3]);
                                    $condition = str_replace('=', '<>', $condition);

                                    $sql = "SELECT $fieldNameDb FROM $tableNameDb WHERE $fieldNameDb='$fieldData[$fieldName]' AND $condition";

                                }else{
                                    $sql = "SELECT $fieldNameDb FROM $tableNameDb WHERE $fieldNameDb='$fieldData[$fieldName]'";
                                }

                                
                                $query = $this->db->query($sql);
                                
                                if (!empty($query)){
                                    $rowCount = $query->rowCount();
                                    if ($rowCount>0){

                                        $this->setErrors($fieldName, $ruleNameArr[0], $this->getMessage($fieldName, $ruleNameArr[0]));

                                        $checkValidate =false;
                                    }
                                }
                                
                            }
                            
                        }

                    }

                    //6. Callback
                    if (preg_match('/^callback_(.+)/', $ruleName, $callbackMatches)){
                        if (!empty($callbackMatches[1])){

                            //Lấy tên function bên controller
                            $functionName = trim($callbackMatches[1]);

                            //Lấy đối tượng controller đang chạy
                            $currentController = App::$app->getCurrentController();

                            if (!empty($fieldData[$fieldName])){
                                //Gọi hàm callback
                                $resultCallback = call_user_func_array(
                                    [$currentController, $functionName],
                                    [$fieldData[$fieldName]]
                                );

                                //Kiểm tra $resultCallback để thông báo lỗi
                                if (!$resultCallback){
                                    $this->setErrors($fieldName, $ruleNameArr[0], $this->getMessage($fieldName, $ruleNameArr[0]));

                                    $checkValidate =false;
                                }
                            }

                        }
                    }

                }
            }
        }

//        echo '<pre>';
//        print_r($this->errors);
//        echo '</pre>';


        return $checkValidate; //Trả về giá trị để bên controller kiểm tra
    }

    public function setErrors($fieldName, $ruleName, $value){
        $this->errors[$fieldName][$ruleName] = $value;
    }

    public function getMessage($fieldName, $ruleName){
        return $this->messages[$fieldName.'.'.$ruleName];
    }

    public function error($fieldName=''){

        $errorArr = $this->errors;

        if (!empty($errorArr)){
            if (empty($fieldName)){

                foreach ($errorArr as $key=>$error){
                    $errorArr[$key] = reset($error);
                }

                return $errorArr;

            }else {
                return reset($errorArr[$fieldName]); //Lấy phần tử mảng
            }

        }

        return false;

    }

    /*
     * Phương thức is dùng để kiểm tra url hiện có thuộc path muốn kiểm tra hay không?
     * Ví dụ: if (Request::is('nguoi-dung')){
     *  echo 'Bạn đang ở trang danh sách người dùng';
     * }
     *
     * if (Request::is('nguoi-dung/them')){
     *  echo 'Bạn đang ở trang thêm người dùng';
     * }
     *
     *
     * Trường hợp 2: Kiểm tra path tương đối
     * + nguoi-dung
     * + nguoi-dung/them
     * + nguoi-dung/sua/1
     * + nguoi-dung/xoa/1
     *
     * if (Request::is('nguoi-dung/*')){
     *  echo 'Bạn đang ở module người dùng';
     * }
     *
     * */
    static public function is($path, $customPath=''){

        //Lấy path hiện tại
        $currentPath = Route::getPath();

        if (!empty($customPath)){
            $currentPath = $customPath;
        }

        /*
         * Kiểm tra path xem cấu trúc ở dạng nào (Tuyệt đối và tương đối)
         *
         * */
        if (preg_match('~^'.$path.'\/\*~i', $path)){
            if (preg_match('~^'.$path.'~i', $currentPath)){
                return true;
            }else{
                return false;
            }
        }elseif ($currentPath==$path){
            return true;
        }else{
            return false;
        }
    }
}
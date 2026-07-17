<?php

namespace App\core;

class Response{

    //phương thức trả về http response code
    public function httpCode($code){
        http_response_code($code);
    }

    //Phương thức chuyển hướng
    public function redirect($path=''){

        if (!empty($path)){
//            echo _WEB_URL.'<br/>';
//            echo $path.'<br/>';
//            echo $_SERVER['HTTP_HOST'];

            if (!filter_var($path, FILTER_VALIDATE_URL)){
                if ($path!='/'){
                    $fullUrl = _WEB_URL.'/'.$path;
                }else{
                    $fullUrl = _WEB_URL;
                }


            }else{

                $pathArr = parse_url($path);
                if ($pathArr['host']!=$_SERVER['HTTP_HOST']){
                    $fullUrl = $path;
                }
            }

            header("Location: ".$fullUrl);

        }else{
            header("Refresh:0");
        }

        exit;
    }

    //Phương thức json
    public function json($dataArr){
        if (is_array($dataArr)){
            return json_encode($dataArr);
        }

        return false;
    }

    //Phương thức jsonDecode()
    public function jsonDecode($json, $isArray=true){
        $dataArr = json_decode($json, $isArray);
        $jsonError = json_last_error();

        $checkError = false;

        $messageError = null;

        switch ($jsonError){

            case JSON_ERROR_NONE:
                $checkError = false;
                break;
            case JSON_ERROR_DEPTH:
                $messageError = 'Maximum stack depth exceeded';
                $checkError = true;
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $messageError = 'Underflow or the modes mismatch';
                $checkError = true;
                break;
            case JSON_ERROR_CTRL_CHAR:
                $messageError = 'Unexpected control character found';
                $checkError = true;
                break;
            case JSON_ERROR_SYNTAX:
                $messageError = 'Syntax error, malformed JSON';
                $checkError = true;
                break;
            case JSON_ERROR_UTF8:
                $messageError = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                $checkError = true;
                break;
            default:
                echo ' - Unknown error';
                $checkError = true;
                break;
        }

        //Nếu không có lỗi json => trả về $dataArr
        if (!$checkError){
            return $dataArr;
        }

        die($messageError);
    }

    //Phương thức chuyển kiểu nội dung
    public function contentType($type){
        header("Content-Type: ".$type);
    }

    public function toJson(){
        $this->contentType('application/json');
    }

    public function toImage($type='jpg'){
        $this->contentType('image/'.$type);
    }

    public function downloadFile($fileUrl, $fileName){

        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );

        $file = file_get_contents($fileUrl, false, stream_context_create($arrContextOptions));

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $fileName);
        header('Content-Transfer-Encoding: binary');
        header('Connection: Keep-Alive');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');

        echo $file;
    }
}
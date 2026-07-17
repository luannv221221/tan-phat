<?php
//Helper chứa các function mặc định dùng để gọi view

use App\core\Route;
use App\core\Request;
use App\core\Response;

//route($path): Có 2 chức năng:
//1. Kiểm tra $path có quyền truy cập hay không
//2. Trả về đường dẫn đầy đủ

function route($path){

    if (Route::is($path)){
        return Route::getUrl($path);
    }

    return false;
}

//request() trả về object của lớp Request()
function request(){
    $request = new Request();
    return $request;
}

//response() trả về object của lớp Response()
function response(){
    $response = new Response();
    return $response;
}

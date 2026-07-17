<?php

namespace App\core;

class Load{

    public static function model($model){
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

    public static function view($view){

    }
}
<?php

namespace App\core;

class View{

    public static $dataShare = [];
    public static $dataViewAllow = [];

    /*
     * Phương thức share($data, $viewAllow)
     * - $data: Dữ liệu cần share
     * - $viewAllow: Các view muốn share (Nếu để trống share tất cả view)
     *
     * */
    public static function share($data=[], $viewAllow = []){
//        echo '<pre>';
//        print_r($data);
//        echo '</pre>';
//        echo '<pre>';
//        print_r($viewAllow);
//        echo '</pre>';

        self::$dataViewAllow = $viewAllow;
        self::$dataShare = $data;
    }

}
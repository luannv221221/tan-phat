<?php
namespace App\core;

class Cookie{

    //Set cookie
    public static function set($key, $value, $expire){
        setcookie($key, $value, time()+$expire);
    }

    //Get cookie
    public static function get($key=''){

        if (empty($key)){
            return $_COOKIE;
        }else{
            if (isset($_COOKIE[$key])){
                return $_COOKIE[$key];
            }
        }

        return false;
    }

    //Remove cookie
    public static function remove($key){
        setcookie($key, '', time()-86400);
    }
}
<?php
namespace App\core;

class Cookie{

    /**
     * Đặt cookie.
     *
     * Bản cũ: setcookie($key, $value, time()+$expire) — thiếu path, httponly,
     * samesite, secure. Cookie "ghi nhớ đăng nhập" sống 30 ngày và dùng được
     * thẳng để vào tài khoản, thiếu httponly là dính XSS một lần thì mất tài
     * khoản cả tháng. Nay mặc định đóng chặt; ai cần nới thì truyền $options.
     *
     * @param int $expire Số giây tính từ bây giờ
     */
    public static function set($key, $value, $expire, $options = []){
        global $config;

        setcookie($key, $value, array_merge([
            'expires'  => time() + (int) $expire,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($config['session']['secure']),
        ], $options));
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

    /**
     * Xoá cookie.
     * Phải đặt LẠI ĐÚNG path như lúc set, nếu không trình duyệt coi là cookie
     * khác và cookie cũ vẫn còn nguyên.
     */
    public static function remove($key){
        setcookie($key, '', ['expires' => time() - 86400, 'path' => '/']);
        unset($_COOKIE[$key]);
    }
}
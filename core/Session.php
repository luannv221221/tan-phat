<?php

namespace App\core;

class Session{

    /** Tên key lưu CSRF token trong session */
    const CSRF_KEY = '_csrf_token';

    public function __construct(){
        global $config;

        //Setup session_name => Ngăn tình trạng share trong cùng domain
        if (!empty(trim($config['session']['cookie_name']))){
            $cookieName = trim($config['session']['cookie_name']);
            session_name($cookieName);
        }

        //Lưu file session RA NGOÀI public/ (xem giải thích ở configs/session.php)
        if (!empty(trim($config['session']['file']))){
            $fileSession = trim($config['session']['file']);
            session_save_path($fileSession);
        }

        if (empty(session_id())){

            // Bảo vệ cookie session — bản cũ dùng mặc định của PHP, không đặt gì.
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                // httponly: JavaScript không đọc được cookie => giảm thiệt hại khi bị XSS
                'httponly' => true,
                // samesite Lax: cookie không gửi kèm request cross-site => giảm CSRF
                'samesite' => 'Lax',
                // secure: chỉ gửi qua HTTPS
                'secure'   => !empty($config['session']['secure']),
            ]);

            session_start();
        }
    }

    //Set session
    public static function set($key, $value){
        $_SESSION[$key] = $value;
    }

    //Get session
    public static function get($key=''){
        if (empty($key)){
            return $_SESSION;
        }else{
            if (isset($_SESSION[$key])){
                return $_SESSION[$key];
            }
        }

        return false;
    }

    //Remove Session (Không có tham số thì sẽ xoá tất cả session)
    public static function remove($key=''){
        if (empty($key)){
            session_destroy();
        }else{
            if (isset($_SESSION[$key])){
                unset($_SESSION[$key]);
            }
        }
    }

    //Flash Data (Session tự động xoá): Được dùng để hiển thị thông báo
    public static function flash($key, $value=''){

        $key = 'flash_'.$key;

        if (!empty($value)){
            //Set flash data
            self::set($key, $value);
        }else{
            //Get flash data
            $data = self::get($key);
            self::remove($key); //Xoá session sau khi hiển thị
            return $data;
        }
    }

    /**
     * Cấp session id mới nhưng GIỮ nguyên dữ liệu.
     *
     * Phải gọi ngay sau khi đăng nhập thành công để chống session fixation:
     * kẻ tấn công ép nạn nhân dùng một session id do hắn biết trước (qua link),
     * nạn nhân đăng nhập, hắn dùng lại chính id đó và vào được tài khoản.
     * Đổi id lúc đăng nhập làm id cũ trở nên vô dụng.
     */
    public static function regenerate(){
        if (!empty(session_id())){
            session_regenerate_id(true); // true = xoá luôn file session cũ
        }
    }

    // ================= CSRF =================

    /**
     * Lấy CSRF token của phiên hiện tại (chưa có thì sinh mới).
     *
     * Token này phải được nhúng vào MỌI form POST, và kiểm tra ở phía nhận.
     * Không có nó, kẻ tấn công dựng một trang bất kỳ chứa form trỏ về hệ thống,
     * dụ người đang đăng nhập bấm vào, và request được gửi đi kèm cookie hợp lệ
     * — tức là hắn thao tác được dưới danh nghĩa nạn nhân (tạo phiếu chi, xoá dữ liệu...).
     */
    public static function csrfToken(){
        if (empty($_SESSION[self::CSRF_KEY])){
            $_SESSION[self::CSRF_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::CSRF_KEY];
    }

    /**
     * Kiểm tra token gửi lên có khớp token trong session không.
     * Dùng hash_equals để so sánh theo thời gian cố định (chống timing attack).
     */
    public static function checkCsrf($token){
        if (empty($_SESSION[self::CSRF_KEY]) || empty($token)) return false;

        return hash_equals($_SESSION[self::CSRF_KEY], (string) $token);
    }

    /** Xoá token hiện tại (dùng khi đăng xuất) */
    public static function resetCsrf(){
        unset($_SESSION[self::CSRF_KEY]);
    }
}

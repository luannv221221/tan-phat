<?php
/**
 * Hash — băm và kiểm tra mật khẩu.
 *
 * Thay cho md5() của bản cũ:
 *   - md5 không salt => 2 người cùng mật khẩu ra cùng hash (dump `users` cũ nhìn thấy rõ).
 *   - md5 rất nhanh => tra rainbow table ra mật khẩu trong vài giây.
 *
 * Chiến lược chuyển đổi (không bắt toàn bộ user đổi mật khẩu):
 *   - Hash cũ (md5, 32 ký tự hex) vẫn đăng nhập được.
 *   - Ngay khi đăng nhập đúng, hash được nâng cấp lên bcrypt (xem needsRehash).
 *   => Người dùng không bị gián đoạn, dữ liệu tự sạch dần.
 */

namespace App\core;

class Hash {

    /** Băm mật khẩu mới */
    public static function make($plain){
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    /**
     * Kiểm tra mật khẩu.
     * Chấp nhận cả hash bcrypt mới lẫn hash md5 cũ (để chuyển đổi dần).
     */
    public static function check($plain, $hash){
        if (empty($hash)) return false;

        if (self::isLegacyMd5($hash)){
            // hash_equals: so sánh theo thời gian cố định, tránh timing attack
            return hash_equals(strtolower($hash), md5($plain));
        }

        return password_verify($plain, $hash);
    }

    /**
     * Hash này có cần băm lại không?
     * true khi: (a) là md5 cũ, hoặc (b) bcrypt nhưng cost đã lỗi thời.
     */
    public static function needsRehash($hash){
        if (empty($hash)) return true;
        if (self::isLegacyMd5($hash)) return true;

        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }

    /** Nhận diện hash md5 cũ: đúng 32 ký tự hex */
    public static function isLegacyMd5($hash){
        return (bool) preg_match('/^[a-f0-9]{32}$/i', (string) $hash);
    }

    /**
     * Sinh token ngẫu nhiên an toàn về mặt mật mã.
     *
     * Bản cũ dùng md5(uniqid()): uniqid() dựa trên thời gian hệ thống
     * => kẻ tấn công biết thời điểm đăng nhập có thể thu hẹp không gian tìm kiếm
     *    và đoán được token phiên. random_bytes() dùng nguồn ngẫu nhiên của HĐH.
     *
     * @param int $bytes Số byte ngẫu nhiên (32 byte => chuỗi hex 64 ký tự)
     */
    public static function randomToken($bytes = 32){
        return bin2hex(random_bytes($bytes));
    }
}

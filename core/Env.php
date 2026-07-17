<?php
/**
 * Đọc file .env — thay cho việc hardcode credential trong config.php.
 *
 * Không dùng thư viện ngoài (dự án chủ trương không phụ thuộc vendor).
 *
 * Cú pháp hỗ trợ:
 *   KEY=value
 *   KEY="value co dau cach"
 *   KEY='value'
 *   # dòng comment
 *   (dòng trống bị bỏ qua)
 */

namespace App\core;

class Env {

    protected static $loaded = [];

    /**
     * Nạp file .env vào bộ nhớ.
     *
     * @param string $path Đường dẫn tới file .env
     * @param bool   $required Ném exception nếu thiếu file
     */
    public static function load($path, $required = true){

        if (!is_file($path)){
            if ($required){
                die('Thieu file .env. Hay copy .env.example thanh .env va dien thong tin ket noi CSDL.');
            }
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line){
            $line = trim($line);

            // Bỏ qua comment và dòng trống
            if ($line === '' || strpos($line, '#') === 0) continue;

            // Phải có dấu =
            $pos = strpos($line, '=');
            if ($pos === false) continue;

            $key   = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));

            // Bỏ dấu nháy bao ngoài nếu có
            $len = strlen($value);
            if ($len >= 2){
                $first = $value[0];
                $last  = $value[$len - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")){
                    $value = substr($value, 1, -1);
                }
            }

            self::$loaded[$key] = $value;
        }
    }

    /**
     * Lấy giá trị từ .env.
     *
     * @param string $key
     * @param mixed  $default Giá trị mặc định nếu không có key
     */
    public static function get($key, $default = null){

        if (!array_key_exists($key, self::$loaded)){
            return $default;
        }

        $value = self::$loaded[$key];

        // Chuyển các giá trị đặc biệt về đúng kiểu
        switch (strtolower($value)){
            case 'true':  return true;
            case 'false': return false;
            case 'null':  return null;
            case '':      return $default;
        }

        return $value;
    }

    /** Kiểm tra key có tồn tại không (kể cả khi giá trị rỗng) */
    public static function has($key){
        return array_key_exists($key, self::$loaded);
    }
}

<?php
use App\core\Env;

/*
 * Cấu hình session.
 *
 * BẢN CŨ: 'file' => './public/logs/session'
 *   Thư mục này nằm trong public/ và KHÔNG có .htaccess chặn.
 *   .htaccess gốc lại có `RewriteCond %{REQUEST_FILENAME} !-f`
 *   => file có thật được web server phục vụ trực tiếp, không qua index.php.
 *   Nghĩa là bất kỳ ai cũng tải được:
 *       http://host/<duong-dan-app>/public/logs/session/sess_xxxxx
 *   rồi chiếm phiên đăng nhập của người khác.
 *
 * NAY: mặc định đưa ra storage/sessions (ngoài public/) + có .htaccess chặn.
 *   Ở production nên trỏ SESSION_PATH ra HẲN ngoài thư mục web.
 */

$__sessionPath = Env::get('SESSION_PATH', __DIR__ . '/../storage/sessions');

// Tạo thư mục nếu chưa có
if (!is_dir($__sessionPath)){
    @mkdir($__sessionPath, 0770, true);
}

$config['session'] = [
    'cookie_name' => Env::get('SESSION_COOKIE', 'tanphat_fw_session'),
    'file'        => $__sessionPath,
    'left_time'   => (int) Env::get('SESSION_LIFETIME', 86400), //Thời gian tồn tại của session

    // Cookie chỉ gửi qua HTTPS. Bật true khi production đã có SSL.
    'secure'      => Env::get('SESSION_SECURE', false) === true,
];

unset($__sessionPath);

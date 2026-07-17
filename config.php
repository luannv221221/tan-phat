<?php
//File này chứa các config
//
//Credential KHONG con nam trong file nay — doc tu .env (khong commit vao git).
//Xem .env.example de biet cac key can khai bao.

use App\core\Env;

// bootstrap.php da require vendor/autoload.php truoc file nay,
// nen App\core\Env autoload duoc qua PSR-4 (App\ => thu muc goc).
Env::load(__DIR__ . '/.env');

//--- Database ---
define('_HOST', Env::get('DB_HOST', 'localhost'));
define('_PORT', (int) Env::get('DB_PORT', 3306)); //Ban cu khong co, ep cung cong 3306
define('_USER', Env::get('DB_USER', 'root'));
define('_PASS', (string) Env::get('DB_PASS', ''));
define('_DB',   Env::get('DB_NAME', 'tanphat_php'));

//--- Ứng dụng ---
// _DEBUG=true thì in chi tiết lỗi SQL ra màn hình. PHẢI để false ở production.
define('_DEBUG', Env::get('APP_DEBUG', false) === true);

// Số phút không hoạt động thì token đăng nhập bị xoá (bản cũ hardcode 15 trong middleware)
define('_SESSION_IDLE_MINUTES', (int) Env::get('SESSION_IDLE_MINUTES', 15));

//--- URL ---
// Bản cũ hardcode '/Unicode/2021/FRAMEWORK/framework_11_12_2021_fix/' theo máy local
// => đổi máy là hỏng. Nay lấy từ .env, có fallback cho CLI (không có HTTP_HOST).
$__host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$__base = Env::get('APP_BASE_PATH', '/');

$__url = Env::get('APP_URL', 'http://' . $__host . $__base);

/*
 * BỎ dấu "/" ở cuối — QUAN TRỌNG.
 *
 * Bản cũ để _WEB_URL kết thúc bằng "/", trong khi MỌI chỗ dùng đều nối thêm "/":
 *     _WEB_URL.'/admin/users'      (view)
 *     _WEB_URL.'/'.$path           (Response::redirect)
 * => sinh ra URL hai gạch: http://host/app//admin/users
 *
 * Apache bỏ qua được (App::handleUrl dùng array_filter() nên đoạn rỗng bị loại),
 * nên lỗi này nằm im từ đầu. Nhưng nó vỡ ở chỗ khác:
 * parse_url() hiểu "//admin/..." là URL protocol-relative => "admin" thành TÊN MÁY CHỦ,
 * không phải đường dẫn. Bất kỳ proxy/router/thư viện nào dùng parse_url đều hiểu sai URL.
 */
define('_WEB_URL', rtrim($__url, '/'));

unset($__host, $__base, $__url);

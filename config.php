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

/*
 * THỬ NGHIỆM 16/10/2026 — MENU GỌN CHO KHO TỔNG.
 *
 * Bật: trang quản trị của GARA TỔNG (Tân Phát) chỉ còn những nhóm liên quan
 * tới việc kho tổng thật sự làm — Kho, Hàng hoá, Danh mục xe, Hệ thống — và
 * "Quản lý các gara" được kéo ra thành mục riêng ngoài menu thay vì nằm trong
 * Hệ thống. Bốn nhóm Bán hàng / Kế toán / CSKH / Quản lý website bị ẩn.
 *
 * CHỈ ẨN TRÊN MENU, không đụng tới phân quyền: gõ thẳng /admin/quotations thì
 * vẫn vào được. Cố ý như vậy — phân quyền là thứ đang gác thật, sửa nó để thử
 * một bố cục menu là đánh đổi sai chiều.
 *
 * KHÔNG ảnh hưởng gara thường: họ vẫn thấy đủ menu như cũ.
 *
 * ĐỔI VỀ false LÀ QUAY LẠI NGUYÊN TRẠNG, không cần chạy SQL hay migration gì.
 */
define('_KHO_TONG_MENU_GON', true);

//--- URL ---
// Bản cũ hardcode '/Unicode/2021/FRAMEWORK/framework_11_12_2021_fix/' theo máy local
// => đổi máy là hỏng. Nay lấy từ .env, có fallback cho CLI (không có HTTP_HOST).
$__host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$__base = Env::get('APP_BASE_PATH', '/');

/*
 * GIAO THỨC PHẢI SUY RA, KHÔNG GHÉP CỨNG 'http://'.
 *
 * Nền tảng nhiều gara: mỗi gara có thể dùng TÊN MIỀN RIÊNG của họ, và tên
 * miền riêng thì gần như chắc chắn chạy HTTPS. Ghép cứng http:// thì mọi
 * đường dẫn sinh ra (link, chuyển trang, ảnh, CSS) đều là http:// trong một
 * trang https — trình duyệt chặn vì nội dung lẫn, hoặc kéo người dùng tụt
 * xuống http. Mà APP_URL phải để TRỐNG trên máy chủ nhiều gara (xem dưới),
 * nên không vá được bằng cách khai cứng một địa chỉ.
 *
 * BA NGUỒN, theo đúng thứ tự tin cậy:
 *   1. $_SERVER['HTTPS']        — Apache đặt khi chính nó chạy TLS
 *   2. SERVER_PORT = 443        — có cấu hình không đặt HTTPS
 *   3. X-Forwarded-Proto        — CHỈ KHI .env bật TRUST_PROXY
 *
 * (3) PHẢI XIN PHÉP vì nó là header do người gọi gửi lên: máy chủ đứng
 * trực tiếp ra Internet mà tin nó thì ai cũng gửi được
 * "X-Forwarded-Proto: https" để ép sinh link https. Chỉ bật khi thật sự có
 * proxy / CDN đứng trước (Cloudflare, nginx), vì lúc đó proxy ghi đè header
 * này và người ngoài không chạm vào được.
 */
$__https = false;
if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off'){
    $__https = true;
} elseif (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443){
    $__https = true;
} elseif (Env::get('TRUST_PROXY', false) === true
          && !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
          && strtolower(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]) === 'https'){
    $__https = true;
}

/* APP_URL ĐỂ TRỐNG TRÊN MÁY CHỦ NHIỀU GARA — đó là thứ làm mỗi gara mở bằng
   tên miền của mình mà link vẫn ở lại tên miền đó. Khai cứng một địa chỉ là
   mọi gara bị kéo hết về tên miền ấy, mất luôn phiên đăng nhập. */
$__url = Env::get('APP_URL', ($__https ? 'https://' : 'http://') . $__host . $__base);

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

unset($__host, $__base, $__url, $__https);

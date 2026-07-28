<?php
function isRole($moduleId, $role, $permissionData){

    if (!empty($permissionData)){
        foreach ($permissionData as $item){
            if (!empty($item['module_id']) && $item['module_id']==$moduleId && !empty($item['role']) && $item['role']==$role){
                return true;
            }
        }
    }


    return false;
}

function get_client_ip() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

/**
 * Sinh thẻ input ẩn chứa CSRF token.
 *
 * Đặt vào MỌI form POST:
 *   <form method="post">
 *       <?php echo csrf_field(); ?>
 *       ...
 *   </form>
 *
 * Thiếu nó thì CsrfMiddleware sẽ từ chối request (HTTP 419).
 */
function csrf_field(){
    $token = \App\core\Session::csrfToken();
    return '<input type="hidden" name="_token" value="'
         . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/** Lấy giá trị CSRF token (dùng cho AJAX qua header X-CSRF-Token) */
function csrf_token(){
    return \App\core\Session::csrfToken();
}

/**
 * Escape dữ liệu trước khi in ra HTML — chống XSS.
 *
 * View hiện trộn 2 kiểu: {{ }} của Template có htmlentities,
 * còn PHP thuần thì KHÔNG escape gì. Dùng e() cho kiểu thứ hai.
 */
function e($value){
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Chuyển chuỗi tiếng Việt thành slug URL.
 *
 *   slugify('Dầu (Diesel)')      => 'dau-diesel'
 *   slugify('Toyota Vios 2018')  => 'toyota-vios-2018'
 *
 * KHÔNG dùng iconv('UTF-8','ASCII//TRANSLIT') vì kết quả phụ thuộc locale
 * của máy chủ — cùng một chuỗi ra khác nhau trên Windows và Linux.
 * Bảng thay thế tường minh cho kết quả giống nhau ở mọi nơi.
 */
function slugify($str){
    $str = trim(mb_strtolower((string) $str, 'UTF-8'));

    $map = [
        'a' => 'áàảãạăắằẳẵặâấầẩẫậ',
        'e' => 'éèẻẽẹêếềểễệ',
        'i' => 'íìỉĩị',
        'o' => 'óòỏõọôốồổỗộơớờởỡợ',
        'u' => 'úùủũụưứừửữự',
        'y' => 'ýỳỷỹỵ',
        'd' => 'đ',
    ];

    foreach ($map as $ascii => $accents){
        $chars = preg_split('//u', $accents, -1, PREG_SPLIT_NO_EMPTY);
        $str = str_replace($chars, $ascii, $str);
    }

    // Còn lại: chữ, số, khoảng trắng, gạch ngang -> giữ; thứ khác -> bỏ
    $str = preg_replace('/[^a-z0-9\s-]/u', '', $str);

    // Gộp khoảng trắng và gạch ngang thành 1 dấu gạch
    $str = preg_replace('/[\s-]+/', '-', $str);

    return trim($str, '-');
}

/**
 * Upload 1 ảnh an toàn (validate đuôi + kích thước + là ảnh thật).
 * Trả về:
 *   ['status'=>'none']                         không có file gửi lên
 *   ['status'=>'error','message'=>...]         lỗi
 *   ['status'=>'ok','path'=>'public/assets/uploads/<sub>/<file>']  thành công
 */
function upload_image($key, $subDir, $baseName = 'img'){
    $allowed  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $maxBytes = 3 * 1024 * 1024;

    if (empty($_FILES[$key]) || !isset($_FILES[$key]['error'])
        || $_FILES[$key]['error'] === UPLOAD_ERR_NO_FILE){
        return ['status' => 'none'];
    }
    $file = $_FILES[$key];
    if ($file['error'] !== UPLOAD_ERR_OK)  return ['status' => 'error', 'message' => 'Tải ảnh thất bại (mã ' . (int) $file['error'] . ')'];
    if ($file['size'] > $maxBytes)         return ['status' => 'error', 'message' => 'Ảnh vượt quá 3MB'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true))   return ['status' => 'error', 'message' => 'Chỉ chấp nhận ảnh: ' . implode(', ', $allowed)];
    if (getimagesize($file['tmp_name']) === false) return ['status' => 'error', 'message' => 'File không phải ảnh hợp lệ'];

    $dir = 'public/assets/uploads/' . trim($subDir, '/') . '/';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $slug = slugify($baseName); if ($slug === '') $slug = 'img';
    $name = $slug . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)){
        return ['status' => 'error', 'message' => 'Không lưu được ảnh'];
    }
    return ['status' => 'ok', 'path' => $dir . $name];
}

/** URL hiển thị ảnh: giữ nguyên nếu là URL ngoài (http), else ghép _WEB_URL */
function media_url($path){
    if (empty($path)) return '';
    if (preg_match('~^https?://~i', $path)) return $path;
    return _WEB_URL . '/' . ltrim($path, '/');
}

/** Trích YouTube video ID từ URL (youtube.com/watch?v= | youtu.be/ | /embed/). Rỗng nếu không phải YouTube. */
function youtube_id($url){
    if (empty($url)) return '';
    if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})~i', $url, $m)){
        return $m[1];
    }
    return '';
}

/**
 * Chuẩn hoá số điện thoại: bỏ ký tự phân cách, đổi +84/84 thành 0.
 *
 *   normalize_phone('091 234 5678')  => '0912345678'
 *   normalize_phone('+84912345678')  => '0912345678'
 */
function normalize_phone($value){
    $digits = preg_replace('/[\s.\-()]/', '', (string) $value);
    return preg_replace('/^(\+?84)/', '0', $digits);
}

/**
 * Số điện thoại Việt Nam có hợp lệ không.
 *
 *   Di động : 10 số, đầu 03/05/07/08/09   -> 0912345678
 *   Cố định : 11 số, đầu 02 + mã vùng     -> 02438765432 (024 3876 5432)
 *
 * Chuỗi rỗng trả về false — nơi nào cho phép bỏ trống thì tự kiểm tra rỗng trước.
 *
 * Dùng chung cho cả rule `phone` của Request (form admin) lẫn các controller
 * storefront tự validate tay, để mọi nơi cùng một luật.
 */
function is_phone($value){
    $digits = normalize_phone($value);
    return (bool) preg_match('/^(0[35789][0-9]{8}|02[0-9]{9})$/', $digits);
}

/**
 * URL của 1 file tĩnh, kèm ?v=<thời điểm sửa file> để phá cache trình duyệt.
 *
 *   asset('public/assets/storefront/js/script.js')
 *   => http://host/app/public/assets/storefront/js/script.js?v=1769521234
 *
 * VÌ SAO CẦN: sửa xong file JS/CSS rồi deploy, trình duyệt người dùng vẫn
 * dùng bản cũ trong cache — lỗi đã sửa nhưng họ vẫn thấy. Gắn mtime vào URL
 * thì file đổi là URL đổi, trình duyệt buộc phải tải lại; file không đổi thì
 * URL giữ nguyên nên vẫn được cache bình thường.
 *
 * @param string $path Đường dẫn tương đối từ gốc dự án (không có dấu / đầu)
 */
function asset($path){
    $path = ltrim((string) $path, '/');
    $full = __DIR__ . '/../../' . $path;
    $url  = _WEB_URL . '/' . $path;

    return is_file($full) ? $url . '?v=' . filemtime($full) : $url;
}

/**
 * In 1 icon Lucide từ sprite.
 *
 *   icon('shopping-bag')                 => <svg class="ic"><use href="#i-shopping-bag"/></svg>
 *   icon('chevron-down', 'adm-nav__caret')
 *
 * Sprite phải được nhúng 1 lần vào trang bằng icon_sprite() — đặt ngay
 * sau <body>. Không dùng <use href="file.svg#id"> vì tham chiếu SVG ngoài
 * không chạy ổn định trên mọi trình duyệt.
 */
function icon($name, $class = ''){
    $name  = preg_replace('/[^a-z0-9-]/', '', strtolower((string) $name));
    $class = trim('ic ' . $class);
    return '<svg class="' . e($class) . '" aria-hidden="true"><use href="#i-' . $name . '"></use></svg>';
}

/** Nội dung sprite Lucide, nhúng 1 lần mỗi trang. */
function icon_sprite(){
    static $svg = null;
    if ($svg === null){
        $file = __DIR__ . '/../../public/assets/vendor/lucide/lucide.svg';
        $svg  = is_file($file) ? file_get_contents($file) : '';
    }
    return $svg;
}

/** URL cho mục menu web: rỗng->trang chủ; http->giữ; else ghép _WEB_URL */
function nav_url($url){
    if (empty($url)) return _WEB_URL . '/';
    if (preg_match('~^https?://~i', $url)) return $url;
    return _WEB_URL . '/' . ltrim($url, '/');
}

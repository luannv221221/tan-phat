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

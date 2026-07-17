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

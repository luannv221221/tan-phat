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
 * Đơn vị hành chính Việt Nam — 34 tỉnh/thành, 2 cấp (tỉnh → phường/xã).
 *
 * Theo cơ cấu sau sáp nhập 2025: bỏ cấp quận/huyện, dưới tỉnh là phường/xã.
 * Dữ liệu nằm ở public/assets/data/vn-administrative.json, dạng:
 *   [{"c":1,"n":"Thành phố Hà Nội","w":[{"c":4,"n":"Phường Ba Đình"}, ...]}, ...]
 *
 * Đọc từ file thay vì bảng DB: dữ liệu tĩnh, rất ít thay đổi, và deploy chỉ
 * cần copy file — không phải chạy thêm migration seed 3.321 dòng.
 *
 * @return array
 */
function vn_admin_units(){
    static $data = null;
    if ($data === null){
        $file = __DIR__ . '/../../public/assets/data/vn-administrative.json';
        $json = is_file($file) ? file_get_contents($file) : '';
        $data = $json !== '' ? json_decode($json, true) : [];
        if (!is_array($data)) $data = [];
    }
    return $data;
}

/**
 * Tra tên tỉnh + phường từ mã, đồng thời KIỂM TRA phường có thuộc tỉnh đó không.
 *
 * Bắt buộc phải kiểm tra ở server: client gửi lên mã gì cũng được, không thể
 * tin danh sách đã lọc ở trình duyệt.
 *
 * @return array|null ['province' => ..., 'ward' => ...] hoặc null nếu không hợp lệ
 */
function vn_admin_lookup($provinceCode, $wardCode){
    $provinceCode = (int) $provinceCode;
    $wardCode     = (int) $wardCode;
    if ($provinceCode <= 0 || $wardCode <= 0) return null;

    foreach (vn_admin_units() as $p){
        if ((int) $p['c'] !== $provinceCode) continue;

        foreach ($p['w'] as $w){
            if ((int) $w['c'] === $wardCode){
                return ['province' => $p['n'], 'ward' => $w['n']];
            }
        }
        return null; // đúng tỉnh nhưng phường không thuộc tỉnh này
    }
    return null;
}

/**
 * Ghép địa chỉ đầy đủ của đơn hàng: "số nhà, phường/xã, tỉnh/thành".
 *
 * Đơn đặt TRƯỚC khi tách địa chỉ không có province_name/ward_name -> chỉ trả
 * về `address` như cũ, không hiện dấu phẩy thừa.
 *
 * @param array $order dòng bảng `orders`
 */
function order_full_address($order){
    $parts = [];
    if (!empty($order['address']))       $parts[] = $order['address'];
    if (!empty($order['ward_name']))     $parts[] = $order['ward_name'];
    if (!empty($order['province_name'])) $parts[] = $order['province_name'];

    return implode(', ', $parts);
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

/* ==========================================================================
 * PHÂN TRANG DANH SÁCH ADMIN
 *
 * Trước đây chỉ Hàng hoá và Khách hàng có phân trang, mọi màn hình còn lại
 * đổ HẾT bản ghi ra một trang. Danh sách chứng từ (báo giá, hoá đơn, phiếu
 * kho...) chỉ dài thêm chứ không ngắn đi, nên sớm muộn cũng thành trang vài
 * nghìn dòng.
 *
 * Cách cắt ở ĐÂY là cắt trên MẢNG đã lấy về, không phải LIMIT/OFFSET dưới
 * CSDL. Đổi lại chỉ thêm 2 dòng cho mỗi controller thay vì phải sửa chữ ký
 * getLists() của từng model (mỗi model một kiểu tham số). Với quy mô một
 * gara thì truy vấn vẫn y như hiện tại — chỗ này không làm chậm thêm gì so
 * với trước, chỉ cắt bớt phần đem ra vẽ.
 *
 * Màn hình nào ĐÃ cắt dưới CSDL (Hàng hoá, Khách hàng) thì giữ nguyên đường
 * đó, chỉ mượn lại phần giao diện phan_trang_html().
 * ========================================================================== */

/** Các mức số dòng/trang cho ô chọn. 0 = xem tất cả (thêm riêng ở dưới). */
function phan_trang_muc(){
    return [10, 20, 50, 100];
}

/**
 * Cắt $rows theo tham số ?page= và ?per_page= trên URL.
 *
 * @param  array $rows    toàn bộ bản ghi đã lấy về
 * @param  int   $macDinh số dòng/trang khi URL chưa chọn gì
 * @return array ['rows','total','perPage','page','totalPages','from','to']
 *               perPage = 0 nghĩa là đang xem tất cả.
 */
function phan_trang_so_dong($macDinh = 20){
    $raw = isset($_GET['per_page']) ? strtolower(trim((string) $_GET['per_page'])) : '';

    if ($raw === 'all') return 0;                                   // xem tất cả
    if ($raw !== '' && in_array((int) $raw, phan_trang_muc(), true)) return (int) $raw;

    // Giá trị lạ (?per_page=99999) rơi về mặc định chứ không tin theo URL —
    // không thì ai cũng ép được trang đổ hết bảng ra.
    return (int) $macDinh;
}

function phan_trang(array $rows, $macDinh = 20){
    $perPage = phan_trang_so_dong($macDinh);
    $total   = count($rows);

    // ceil(0/20) = 0 -> phải kẹp về 1, không thì $page thành 0 và offset âm,
    // array_slice() với offset âm đếm ngược từ cuối mảng (trang rỗng ra dữ liệu).
    $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;
    if ($totalPages < 1) $totalPages = 1;

    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    if ($page < 1) $page = 1;
    if ($page > $totalPages) $page = $totalPages;

    $offset = ($page - 1) * $perPage;

    return [
        // GIỮ KHOÁ (tham số thứ 4 = true): nhiều bảng đánh số thứ tự bằng
        // {{$key+1}}. Đánh số lại từ 0 thì sang trang 2 cột STT lại chạy từ 1.
        'rows'       => $perPage > 0 ? array_slice($rows, $offset, $perPage, true) : $rows,
        'total'      => $total,
        'perPage'    => $perPage,
        'page'       => $page,
        'totalPages' => $totalPages,
        'from'       => $total > 0 ? $offset + 1 : 0,
        'to'         => $perPage > 0 ? min($offset + $perPage, $total) : $total,
    ];
}

/**
 * Chuỗi query giữ nguyên bộ lọc hiện tại, bỏ các tham số của chính phân trang.
 *
 * `module` là tham số ĐƯỜNG DẪN do rewrite sinh ra (xem .htaccess), không phải
 * bộ lọc — kéo theo là link ra /admin/quotations?module=admin/quotations.
 */
function phan_trang_qs(array $boThem = []){
    $p = $_GET;
    foreach (array_merge(['module', 'page', 'per_page'], $boThem) as $k){
        unset($p[$k]);
    }
    return http_build_query($p);
}

/**
 * Thanh phân trang (đặt trong .card-footer). Trả về chuỗi HTML.
 *
 * @param array  $pg      kết quả phan_trang()
 * @param string $baseUrl URL danh sách. Để trống thì lấy chính URL đang mở —
 *                        đúng cho mọi màn hình danh sách nên không view nào
 *                        phải tự ghép, và không lệ thuộc biến $routeBase (có
 *                        view không đặt biến này).
 * @param string $nhan    danh từ đếm được: "báo giá", "hoá đơn"...
 */
function phan_trang_html(array $pg, $baseUrl = null, $nhan = 'dòng'){
    if ($baseUrl === null){
        $mod = isset($_GET['module']) ? trim((string) $_GET['module'], '/') : 'admin';
        $baseUrl = _WEB_URL . '/' . $mod;
    }
    $qs = phan_trang_qs();

    $lk = function($page, $perPage) use ($baseUrl, $qs){
        $p = [];
        if ($qs !== '') parse_str($qs, $p);
        if ($perPage !== null) $p['per_page'] = $perPage;
        elseif (isset($_GET['per_page'])) $p['per_page'] = $_GET['per_page'];
        if ($page > 1) $p['page'] = $page;
        $s = http_build_query($p);
        return e($baseUrl . ($s !== '' ? '?' . $s : ''));
    };

    $total   = (int) $pg['total'];
    $page    = (int) $pg['page'];
    $pages   = (int) $pg['totalPages'];
    $perPage = (int) $pg['perPage'];

    // Ô chọn số dòng/trang. Là <select> chứ không phải chuỗi link vì có 5 mức,
    // xếp hàng ngang thì chật và dễ bấm nhầm sang số trang ngay cạnh.
    $opts = '';
    foreach (phan_trang_muc() as $m){
        $opts .= '<option value="' . $m . '"' . ($perPage === $m ? ' selected' : '') . '>' . $m . '</option>';
    }
    $opts .= '<option value="all"' . ($perPage === 0 ? ' selected' : '') . '>Tất cả</option>';

    $chon = '<div class="d-flex align-items-center">'
          . '<span class="text-muted small mr-2">Hiển thị</span>'
          . '<select class="form-control form-control-sm js-per-page" style="width:auto"'
          . ' data-base="' . e($baseUrl) . '" data-qs="' . e($qs) . '">' . $opts . '</select>'
          . '<span class="text-muted small ml-2">'
          . ($total > 0 ? (int) $pg['from'] . '–' . (int) $pg['to'] . ' / ' . $total : '0')
          . ' ' . e($nhan) . '</span></div>';

    if ($pages <= 1) return $chon;

    // Cửa sổ 7 số quanh trang hiện tại; hai đầu luôn có trang 1 và trang cuối.
    $start = max(1, $page - 3);
    $end   = min($pages, $page + 3);

    $ds = '<li class="page-item ' . ($page <= 1 ? 'disabled' : '') . '">'
        . '<a class="page-link" href="' . $lk($page - 1, null) . '">&laquo;</a></li>';
    if ($start > 1){
        $ds .= '<li class="page-item"><a class="page-link" href="' . $lk(1, null) . '">1</a></li>'
             . '<li class="page-item disabled"><span class="page-link">…</span></li>';
    }
    for ($i = $start; $i <= $end; $i++){
        $ds .= '<li class="page-item ' . ($i === $page ? 'active' : '') . '">'
             . '<a class="page-link" href="' . $lk($i, null) . '">' . $i . '</a></li>';
    }
    if ($end < $pages){
        $ds .= '<li class="page-item disabled"><span class="page-link">…</span></li>'
             . '<li class="page-item"><a class="page-link" href="' . $lk($pages, null) . '">' . $pages . '</a></li>';
    }
    $ds .= '<li class="page-item ' . ($page >= $pages ? 'disabled' : '') . '">'
         . '<a class="page-link" href="' . $lk($page + 1, null) . '">&raquo;</a></li>';

    return $chon . '<nav><ul class="pagination pagination-sm mb-0">' . $ds . '</ul></nav>';
}

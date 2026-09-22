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

/**
 * Trích YouTube video ID từ URL (youtube.com/watch?v= | youtu.be/ | /embed/).
 * Rỗng nếu không phải YouTube.
 *
 * Nhận luôn MÃ TRẦN 11 ký tự: dữ liệu trong `gallery_items.video_url` có sẵn
 * mấy dòng chỉ lưu đúng mã (không kèm tên miền). Không nhận thì mấy video đó
 * hiện ra khung trống — mà nhìn vào CSDL vẫn thấy có dữ liệu, rất khó lần.
 */
function youtube_id($url){
    if (empty($url)) return '';
    $url = trim($url);

    if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})~i', $url, $m)){
        return $m[1];
    }
    // Mã trần: đúng 11 ký tự hợp lệ và không chứa gì khác
    if (preg_match('~^[A-Za-z0-9_-]{11}$~', $url)){
        return $url;
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

/* ==========================================================================
 * BIỂU MẪU IN (báo giá / hoá đơn)
 * ========================================================================== */

/**
 * Logo dạng data URI để nhúng thẳng vào biểu mẫu in.
 *
 * KHÔNG trả về URL: file Word tải về sẽ được mở ở máy khác, lúc đó
 * <img src="http://localhost:88/..."> chỉ ra ô ảnh vỡ. Nhúng base64 thì logo
 * đi theo file, in ở đâu cũng có. Logo hiện 7KB nên không đáng ngại.
 *
 * Ưu tiên logo đã cấu hình trong Cấu hình chung, chưa đặt thì lấy logo
 * mặc định của giao diện — đúng cái đang hiện ở đầu trang bán hàng.
 */
function logo_in_an(array $settings = []){
    $goc = __DIR__ . '/../../';

    $duongDan = !empty($settings['logo'])
        ? $goc . ltrim($settings['logo'], '/')
        : $goc . 'public/assets/storefront/images/logo.png';

    if (!is_file($duongDan)) return '';

    $duoi = strtolower(pathinfo($duongDan, PATHINFO_EXTENSION));
    $mime = ($duoi === 'jpg' || $duoi === 'jpeg') ? 'image/jpeg'
          : ($duoi === 'svg' ? 'image/svg+xml' : ($duoi === 'gif' ? 'image/gif' : 'image/png'));

    return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($duongDan));
}

/**
 * Số tiền bằng chữ — dòng bắt buộc trên hoá đơn Việt Nam.
 *
 * Luật đọc tiếng Việt có mấy chỗ bẫy, xử lý đủ ở đây:
 *   15    -> "mười lăm"        (không phải "mười năm")
 *   21    -> "hai mươi mốt"    (không phải "hai mươi một")
 *   24    -> "hai mươi tư"     (nhưng 104 vẫn là "một trăm linh bốn")
 *   105   -> "một trăm linh năm"
 *   nhóm giữa phải đọc đủ 3 chữ số: 1.309.000 -> "một triệu ba trăm linh
 *   chín nghìn", bỏ "linh" đi là thành "ba trăm chín nghìn" — sai số.
 */
function doc_so_tien($so){
    $so = (int) round((float) $so);
    if ($so === 0) return 'Không đồng';

    $am = $so < 0;
    $so = abs($so);

    $chuSo = ['không', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];

    /* Đọc một nhóm 3 chữ số. $day = còn nhóm lớn hơn đứng trước, khi đó phải
       đọc đủ cả hàng trăm dù nó bằng 0. */
    $docNhom = function($n, $day) use ($chuSo){
        $tram  = intdiv($n, 100);
        $chuc  = intdiv($n % 100, 10);
        $donVi = $n % 10;

        $s = '';
        if ($tram > 0 || $day) $s .= $chuSo[$tram] . ' trăm';

        if ($chuc === 0){
            if ($donVi > 0) $s .= ($tram > 0 || $day) ? ' linh ' . $chuSo[$donVi] : $chuSo[$donVi];
        } elseif ($chuc === 1){
            $s .= ' mười';
            if ($donVi === 5)      $s .= ' lăm';
            elseif ($donVi > 0)    $s .= ' ' . $chuSo[$donVi];
        } else {
            $s .= ' ' . $chuSo[$chuc] . ' mươi';
            if ($donVi === 1)      $s .= ' mốt';
            elseif ($donVi === 4)  $s .= ' tư';
            elseif ($donVi === 5)  $s .= ' lăm';
            elseif ($donVi > 0)    $s .= ' ' . $chuSo[$donVi];
        }
        return trim($s);
    };

    // Tách thành từng nhóm 3 chữ số, tính từ hàng đơn vị đi lên
    $nhom = [];
    while ($so > 0){ $nhom[] = $so % 1000; $so = intdiv($so, 1000); }

    $donViNhom = ['', ' nghìn', ' triệu', ' tỷ'];
    $phan = [];
    for ($i = count($nhom) - 1; $i >= 0; $i--){
        if ($nhom[$i] === 0) continue;
        // Nhóm vượt quá "tỷ" (>= 1000 tỷ): đọc tiếp bằng "tỷ" ghép, hiếm gặp
        // với hoá đơn nhưng thà đọc dài còn hơn ra chuỗi cụt.
        $dv = isset($donViNhom[$i]) ? $donViNhom[$i] : str_repeat(' tỷ', intdiv($i, 3));
        $phan[] = $docNhom($nhom[$i], $i < count($nhom) - 1) . $dv;
    }

    $ket = implode(' ', $phan) . ' đồng';
    if ($am) $ket = 'Âm ' . $ket;

    return mb_strtoupper(mb_substr($ket, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($ket, 1, null, 'UTF-8');
}

/**
 * Dựng biểu mẫu in cho một chứng từ (báo giá / hoá đơn).
 *
 * KHÔNG đi qua Controller::render(): view này phải ra HTML nguyên vẹn từng
 * byte. render() đẩy view qua Template::run() — bộ này quét {{ }}, @if,
 * @foreach... bằng regex trên toàn văn bản, mà biểu mẫu in có sẵn CSS
 * `@media print { ... }`, `@page`. Nay chưa đụng nhau, nhưng thêm một quy tắc
 * CSS nữa là có ngày trúng. In ấn thì không đáng đánh cược.
 *
 * @param array  $ct       thông tin chứng từ (loai, so, ngay, nhanKy...)
 * @param mixed  $khach    dòng partners hoặc ['name' => 'Khách vãng lai']
 * @param array  $dong     dòng hàng, mỗi dòng cần có item_type + amount
 * @param array  $settings cấu hình chung (tên công ty, địa chỉ, logo...)
 * @param string $urlWord  link tải bản Word
 * @param bool   $laWord   true = đang xuất Word (ẩn thanh công cụ)
 */
function in_chung_tu(array $ct, $khach, array $dong, array $settings, $urlWord = '', $laWord = false){
    /* Tiền hàng hoá / tiền dịch vụ tính LẠI từ dòng, còn cộng-thuế-tổng thì
       lấy số đã lưu ở đầu chứng từ. Số đã lưu mới là số chứng từ thực sự mang
       (hoá đơn ghi sổ rồi thì nó là căn cứ kế toán); tính lại hết là in ra một
       con số khác với con số trong sổ. */
    $tong = ['hang' => 0.0, 'dichvu' => 0.0];
    foreach ($dong as $d){
        $amt = (float) $d['amount'];
        if (isset($d['item_type']) && $d['item_type'] === 'service') $tong['dichvu'] += $amt;
        else                                                        $tong['hang']   += $amt;
    }
    $tong['subtotal'] = (float) $ct['subtotal'];
    $tong['vat_rate'] = (float) $ct['vatRate'];
    $tong['tax']      = (float) $ct['tax'];
    $tong['total']    = (float) $ct['total'];

    require __DIR__ . '/../views/admin/print/chung-tu.php';
}

/** Header để trình duyệt tải về dạng file Word thay vì mở trên trang */
function header_word($tenFile){
    header('Content-Type: application/msword; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '', $tenFile) . '.doc"');
    header('Cache-Control: max-age=0');
}

/**
 * URL đang là trang quản trị (`admin` hoặc `admin/...`) — đọc thẳng
 * $_GET['module'] như App::handleUrl() và menu trái.
 */
function la_request_quan_tri(){
    $m = isset($_GET['module']) ? trim((string) $_GET['module'], '/') : '';
    return $m === 'admin' || strpos($m, 'admin/') === 0;
}

/**
 * Id tài khoản quản trị đang đăng nhập, hoặc null.
 *
 * Không chỉ đọc 'dataUser': AppServiceProvider::boot() chạy TRƯỚC
 * AuthMiddleware — mà 'dataUser' do middleware đặt. Ở request đầu tiên sau khi
 * đăng nhập chỉ có 'dataToken', nên phải suy từ token.
 */
function nguoi_dang_nhap_id(){
    $id = \App\core\Session::get('dataUser');
    if (!empty($id)) return (int) $id;
    $tk = \App\core\Session::get('dataToken');
    if (!empty($tk)){
        $row = \App\core\Load::model('LoginToken')->getToken($tk);
        if (!empty($row['user_id'])) return (int) $row['user_id'];
    }
    return null;
}

/**
 * Gara làm việc của request này — [id, code, name, is_master, ...] hoặc null.
 *
 * GARA ĐỘC LẬP (22/09/2026): mỗi tài khoản thuộc đúng MỘT gara và chỉ làm việc
 * trên gara đó. Không còn ô đổi gara — với các gara độc lập, "đổi gara" chính
 * là xem dữ liệu của doanh nghiệp khác.
 *
 *   Trang quản trị     -> gara ghi trên tài khoản, và gara đó phải đang hoạt
 *                         động. Không có / đang khoá -> null (AuthMiddleware đá ra).
 *   Website, dòng lệnh -> gara tổng: website, giỏ hàng, đơn web là của Tân Phát.
 *
 * KHÔNG đọc session `garage_id` — ô đổi gara cũ ghi vào đó.
 *
 * Chỉ NHỚ kết quả tìm được. Kết quả null không nhớ: ở request khôi phục phiên
 * từ cookie "Ghi nhớ đăng nhập", lúc boot() hỏi thì chưa có phiên, tới lúc
 * AuthMiddleware hỏi thì đã có — nhớ null từ lần đầu là đá oan người dùng ra.
 */
function gara_hien_tai(){
    static $cache = false;
    if ($cache !== false) return $cache;

    $model = \App\core\Load::model('GaragesModel');

    if (PHP_SAPI === 'cli' || !la_request_quan_tri()){
        $master = $model->getMaster();
        if (!empty($master)) $cache = $master;
        return !empty($master) ? $master : null;
    }

    $userId = nguoi_dang_nhap_id();
    if (empty($userId)) return null;

    $u = \App\core\Load::model('UsersModel')->getDetail($userId);
    if (empty($u['garage_id'])) return null;

    $g = $model->getDetail((int) $u['garage_id']);
    if (empty($g) || (int) $g['status'] !== 1) return null;

    return $cache = $g;
}

/** Id của gara đang làm việc, hoặc null — dùng khi lưu chứng từ */
function gara_hien_tai_id(){
    $g = gara_hien_tai();
    return !empty($g['id']) ? (int) $g['id'] : null;
}

/** Gara làm việc là gara tổng (Tân Phát) — mở các màn "chỉ Tân Phát" */
function la_gara_tong(){
    $g = gara_hien_tai();
    return !empty($g) && (int) $g['is_master'] === 1;
}

/**
 * Chuẩn hoá biển số xe: bỏ hết dấu, viết hoa.
 *
 *   "30A-123.45"  -> "30A12345"
 *   " 30a 123 45" -> "30A12345"
 *
 * Ở ĐÂY chứ không phải trong model, vì giờ có ba nơi dùng: màn Xe của khách,
 * form Lập báo giá, form Lập hoá đơn. Model chỉ được nạp khi ai đó gọi
 * $this->model(...), nên controller gọi thẳng static của nó là chết ngay —
 * mà thêm một dòng nạp model chỉ để dùng một hàm thuần tuý là thừa.
 *
 * MỘT bản duy nhất là điều kiện sống còn: lưu chuẩn hoá kiểu này mà tra cứu
 * chuẩn hoá kiểu khác thì tìm mãi không ra, đúng loại lỗi khó đoán nhất.
 * MemberVehiclesModel::chuanHoaBienSo() gọi lại đúng hàm này.
 */
function chuan_hoa_bien_so($s){
    return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $s));
}

/**
 * Hạn bảo trì kế tiếp của MỘT xe / thiết bị — theo tháng HOẶC km, cái nào tới trước.
 *
 *   $lanCuoi  ['ngay' => 'Y-m-d' ngày hoàn tất lần bảo trì cuối, 'km' => int|null]
 *   $docKm    các lần ghi số km của CÙNG xe trên mọi chứng từ: [['ngay' => ..., 'km' => ...], ...]
 *   $cfg      ['thang' => 6, 'km' => 5000]   km = 0 là không nhắc theo km
 *
 * Phần mềm không biết đồng hồ xe HÔM NAY chỉ bao nhiêu — chỉ biết những lần
 * khách mang xe tới và ai đó ghi số km lên báo giá / hoá đơn / phiếu. Nên:
 *   mốc km        = km lúc bảo trì + chu kỳ km
 *   tốc độ        = (km lần ghi mới nhất - km lần ghi cũ nhất) / số ngày giữa hai lần
 *   ngày chạm mốc = ngày ghi mới nhất + (mốc - km mới nhất) / tốc độ
 *   lần ghi mới nhất ĐÃ vượt mốc -> hạn là chính ngày ghi đó (đã quá)
 * Chỉ có một lần ghi, hoặc km không tăng (gõ nhầm) thì không ước — dùng mốc tháng.
 *
 * Trả: theo_thang, moc_km, km_gan_nhat, km_moi_ngay, theo_km, han, ly_do ('thang' | 'km').
 * Hàm thuần — không đọc CSDL, không đọc đồng hồ — nên test được bằng số cố định.
 */
function han_bao_tri(array $lanCuoi, array $docKm, array $cfg){
    $kq = ['theo_thang' => null, 'moc_km' => null, 'km_gan_nhat' => null,
           'km_moi_ngay' => null, 'theo_km' => null, 'han' => null, 'ly_do' => null];

    $ngayHopLe = function($s){
        $s = substr((string) $s, 0, 10);
        $d = \DateTime::createFromFormat('!Y-m-d', $s);
        return ($d && $d->format('Y-m-d') === $s) ? $d : null;   // chặn 2026-02-31
    };

    $d = $ngayHopLe($lanCuoi['ngay'] ?? '');
    if ($d === null) return $kq;
    $ngay = $d->format('Y-m-d');

    /* Cộng tháng KẸP VỀ CUỐI THÁNG: 31/08 + 6 tháng là 28/02, không phải 03/03
       như DateTime::modify('+6 months') tự nhảy sang tháng sau. */
    $thang = max(1, (int) ($cfg['thang'] ?? 6));
    $m = (int) $d->format('n') - 1 + $thang;
    $y = (int) $d->format('Y') + intdiv($m, 12);
    $m = $m % 12 + 1;
    $cuoiThang = (int) date('t', mktime(0, 0, 0, $m, 1, $y));
    $kq['theo_thang'] = sprintf('%04d-%02d-%02d', $y, $m, min((int) $d->format('j'), $cuoiThang));
    $kq['han']   = $kq['theo_thang'];
    $kq['ly_do'] = 'thang';

    $chuKy = (int) ($cfg['km'] ?? 0);
    $kmLan = (isset($lanCuoi['km']) && $lanCuoi['km'] !== '' && $lanCuoi['km'] !== null) ? (int) $lanCuoi['km'] : null;
    if ($chuKy <= 0 || $kmLan === null) return $kq;
    $kq['moc_km'] = $kmLan + $chuKy;

    // Mọi lần ghi hợp lệ, KỂ CẢ chính lần bảo trì
    $ds = [['ngay' => $ngay, 'km' => $kmLan]];
    foreach ($docKm as $r){
        $dr = $ngayHopLe($r['ngay'] ?? '');
        if ($dr === null || !isset($r['km']) || $r['km'] === '' || $r['km'] === null) continue;
        $ds[] = ['ngay' => $dr->format('Y-m-d'), 'km' => (int) $r['km']];
    }
    usort($ds, function($a, $b){ return [$a['ngay'], $a['km']] <=> [$b['ngay'], $b['km']]; });
    $dau  = $ds[0];
    $cuoi = $ds[count($ds) - 1];
    $kq['km_gan_nhat'] = $cuoi;

    if ($cuoi['km'] >= $kq['moc_km']){
        $kq['theo_km'] = $cuoi['ngay'];
    } else {
        $soNgay = (int) $ngayHopLe($dau['ngay'])->diff($ngayHopLe($cuoi['ngay']))->days;
        $tang   = $cuoi['km'] - $dau['km'];
        if ($soNgay > 0 && $tang > 0){
            $kq['km_moi_ngay'] = $tang / $soNgay;
            $can = (int) ceil(($kq['moc_km'] - $cuoi['km']) / $kq['km_moi_ngay']);
            $kq['theo_km'] = $ngayHopLe($cuoi['ngay'])->modify('+' . $can . ' days')->format('Y-m-d');
        }
    }

    if ($kq['theo_km'] !== null && $kq['theo_km'] < $kq['theo_thang']){
        $kq['han']   = $kq['theo_km'];
        $kq['ly_do'] = 'km';
    }
    return $kq;
}

/* ===========================================================================
 * ĐƠN VỊ HÀNH CHÍNH cho các ô chọn địa chỉ — GỌI API NGOÀI, có nhớ tạm.
 *
 * Thứ tự lấy dữ liệu, hỏng chỗ nào thì xuống chỗ dưới:
 *   1. API ngoài (provinces.open-api.vn v2 — 34 tỉnh, 2 cấp sau sáp nhập 2025)
 *   2. bản nhớ tạm đã tải trước đó, kể cả quá hạn — cũ vẫn hơn không có gì
 *   3. file tĩnh public/assets/data/vn-administrative.json (xem vn_admin_units)
 * Mã của API TRÙNG mã trong file tĩnh (Hà Nội = 1, Ba Đình = 4), nên rơi
 * xuống bước 3 không làm lệch dữ liệu đã lưu.
 *
 * Vì sao vẫn nhớ tạm dù "gọi API mỗi lần": mở form nào cũng chờ mạng thì nhập
 * liệu rất nặng, mà danh sách này mấy năm mới đổi một lần.
 * ========================================================================= */

/** Gốc API — đổi được trong test qua $GLOBALS['dia_gioi_api'] */
function dia_gioi_api_goc(){
    return isset($GLOBALS['dia_gioi_api'])
        ? (string) $GLOBALS['dia_gioi_api']
        : 'https://provinces.open-api.vn/api/v2';
}

/** Thư mục nhớ tạm (ngoài webroot) */
function dia_gioi_thu_muc_nho(){
    return __DIR__ . '/../../storage/cache/dia-gioi';
}

/** Xoá nhớ tạm — dùng khi đổi API hoặc trong test */
function dia_gioi_xoa_nho(){
    $n = 0;
    foreach ((array) glob(dia_gioi_thu_muc_nho() . '/*.json') as $f){ if (@unlink($f)) $n++; }
    return $n;
}

/**
 * Gọi một đường dẫn của API, nhớ kết quả ra file.
 *
 * @return array|null Mảng đã giải mã, hoặc null nếu không lấy được ở đâu cả.
 */
function dia_gioi_goi($duong, $ten){
    $thuMuc = dia_gioi_thu_muc_nho();
    $file   = $thuMuc . '/' . $ten . '.json';
    $ttl    = isset($GLOBALS['dia_gioi_ttl']) ? (int) $GLOBALS['dia_gioi_ttl'] : 86400;

    $docNho = function() use ($file){
        if (!is_file($file)) return null;
        $d = json_decode((string) file_get_contents($file), true);
        return (is_array($d) && !empty($d)) ? $d : null;
    };

    // 1. Nhớ tạm còn hạn
    if (is_file($file) && (time() - (int) filemtime($file)) < $ttl){
        $d = $docNho();
        if ($d !== null) return $d;
    }

    // 2. Gọi API
    $body = '';
    if (function_exists('curl_init')){
        $ch = curl_init(rtrim(dia_gioi_api_goc(), '/') . $duong);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $body = (string) curl_exec($ch);
        if ((int) curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200) $body = '';
        curl_close($ch);
    }
    $d = $body !== '' ? json_decode($body, true) : null;
    if (is_array($d) && !empty($d)){
        if (!is_dir($thuMuc)) @mkdir($thuMuc, 0775, true);
        @file_put_contents($file, json_encode($d, JSON_UNESCAPED_UNICODE));
        return $d;
    }

    // 3. Nhớ tạm quá hạn còn hơn không có gì
    return $docNho();
}

/** Danh sách tỉnh / thành: [['c' => 1, 'n' => 'Thành phố Hà Nội'], ...] */
function dia_gioi_tinh(){
    $d = dia_gioi_goi('/', 'tinh');
    if (is_array($d)){
        $ds = [];
        foreach ($d as $p){
            if (!isset($p['code'], $p['name'])) continue;
            $ds[] = ['c' => (int) $p['code'], 'n' => (string) $p['name']];
        }
        if (!empty($ds)) return $ds;
    }
    $ds = [];
    foreach (vn_admin_units() as $p) $ds[] = ['c' => (int) $p['c'], 'n' => (string) $p['n']];
    return $ds;
}

/** Phường / xã của một tỉnh */
function dia_gioi_xa($tinhCode){
    $tinhCode = (int) $tinhCode;
    if ($tinhCode <= 0) return [];

    $d = dia_gioi_goi('/p/' . $tinhCode . '?depth=2', 'xa-' . $tinhCode);
    if (is_array($d) && !empty($d['wards']) && is_array($d['wards'])){
        $ds = [];
        foreach ($d['wards'] as $w){
            if (!isset($w['code'], $w['name'])) continue;
            $ds[] = ['c' => (int) $w['code'], 'n' => (string) $w['name']];
        }
        if (!empty($ds)) return $ds;
    }
    foreach (vn_admin_units() as $p){
        if ((int) $p['c'] !== $tinhCode) continue;
        $ds = [];
        foreach ($p['w'] as $w) $ds[] = ['c' => (int) $w['c'], 'n' => (string) $w['n']];
        return $ds;
    }
    return [];
}

/**
 * Tra tên tỉnh + phường từ mã, ĐỒNG THỜI kiểm tra phường có thuộc tỉnh đó không.
 *
 * Bắt buộc kiểm ở server: trình duyệt gửi lên mã gì cũng được, không thể tin
 * danh sách đã lọc ở client.
 *
 * @return array|null ['province' => ..., 'ward' => ...] hoặc null nếu không hợp lệ
 */
function dia_gioi_tra($tinhCode, $xaCode){
    $tinhCode = (int) $tinhCode;
    $xaCode   = (int) $xaCode;
    if ($tinhCode <= 0 || $xaCode <= 0) return null;

    $tenTinh = null;
    foreach (dia_gioi_tinh() as $p){
        if ($p['c'] === $tinhCode){ $tenTinh = $p['n']; break; }
    }
    if ($tenTinh === null) return null;

    foreach (dia_gioi_xa($tinhCode) as $w){
        if ($w['c'] === $xaCode) return ['province' => $tenTinh, 'ward' => $w['n']];
    }
    return null;   // đúng tỉnh nhưng phường không thuộc tỉnh này
}

/**
 * Ghép địa chỉ đầy đủ: "số nhà, phường/xã, tỉnh/thành".
 * Dùng cho mọi dòng có bốn cột address / ward_name / province_name.
 * Dòng cũ chưa có tỉnh/phường thì chỉ ra `address`, không thừa dấu phẩy.
 */
function dia_chi_day_du($row){
    if (!is_array($row)) return '';   // khách lẻ: bản in truyền null
    $parts = [];
    foreach (['address', 'ward_name', 'province_name'] as $k){
        if (!empty($row[$k])) $parts[] = $row[$k];
    }
    return implode(', ', $parts);
}

/* ===========================================================================
 * NỐI CHỨNG TỪ VÀO XE VÀ PHIẾU TIẾP NHẬN
 *
 * Mô hình: một khách nhiều XE, một xe nhiều PHIẾU TIẾP NHẬN, mỗi phiếu nhiều
 * chứng từ (báo giá / hoá đơn / phiếu bảo hành). Trước đây biển số là chữ gõ
 * tay rời rạc trên từng chứng từ, không có gì nối chúng lại: tra lịch sử một
 * xe là ghép chuỗi, gõ hai kiểu là thành hai xe.
 * ========================================================================= */

/**
 * Phiếu tiếp nhận + xe của nó.
 * @return array|null ['phieu' => ..., 'xe' => ...] hoặc null nếu không có
 */
function phieu_tiep_nhan($id){
    $id = (int) $id;
    if ($id <= 0) return null;
    $p = \App\core\Load::model('ReceptionsModel')->getDetail($id);
    if (empty($p)) return null;
    $xe = \App\core\Load::model('VehiclesModel')->getDetail((int) $p['vehicle_id']);
    return ['phieu' => $p, 'xe' => !empty($xe) ? $xe : null];
}

/** Xe theo biển số — nhận mọi cách gõ (xem chuan_hoa_bien_so) */
function xe_theo_bien_so($bienSo){
    if (chuan_hoa_bien_so($bienSo) === '') return null;
    $xe = \App\core\Load::model('VehiclesModel')->theoBienSo($bienSo);
    return !empty($xe) ? $xe : null;
}

/**
 * Các cột xe của một chứng từ, lấy từ dữ liệu form.
 *
 *   - Lập TỪ PHIẾU TIẾP NHẬN: lấy xe và số km của phiếu. Không tin biển số gõ
 *     tay trên form, vì phiếu mới là thứ quyết định xe nào.
 *   - Lập TAY: biển số gõ vào mà trùng một xe đã có thì vẫn nối vào xe đó —
 *     nhờ vậy lịch sử xe không bị đứt chỉ vì người lập không đi qua phiếu.
 *   - Biển số lạ: vẫn lưu bản chụp `bien_so`, `vehicle_id` để trống (bán lẻ
 *     phụ tùng qua quầy thì không có xe nào cả).
 *   - Số km lớn hơn số đã ghi nhận của xe thì cập nhật cho xe; nhỏ hơn thì bỏ
 *     qua (đồng hồ không quay lui — xem VehiclesModel::capNhatKm).
 *
 * `bien_so` giữ NGUYÊN VĂN và `bien_so_chuan` chuẩn hoá ngay lúc lưu: chứng từ
 * là bản chụp lúc lập, xe sau này đổi biển thì giấy đã giao khách vẫn đúng.
 *
 * @return array [reception_id, vehicle_id, bien_so, bien_so_chuan, so_km]
 */
function lien_ket_xe($f){
    $ra = ['reception_id' => null, 'vehicle_id' => null,
           'bien_so' => null, 'bien_so_chuan' => null, 'so_km' => null];

    $bienSo = isset($f['bien_so']) ? trim((string) $f['bien_so']) : '';
    $km     = isset($f['so_km']) ? (int) preg_replace('/[^\d]/', '', (string) $f['so_km']) : 0;

    $tu = !empty($f['reception_id']) ? phieu_tiep_nhan($f['reception_id']) : null;
    if ($tu !== null){
        $ra['reception_id'] = (int) $tu['phieu']['id'];
        $ra['vehicle_id']   = (int) $tu['phieu']['vehicle_id'];
        if (!empty($tu['xe']['bien_so'])) $bienSo = $tu['xe']['bien_so'];
        if ($km <= 0){
            if ($tu['phieu']['km_vao'] !== null)              $km = (int) $tu['phieu']['km_vao'];
            elseif (!empty($tu['xe']) && $tu['xe']['so_km'] !== null) $km = (int) $tu['xe']['so_km'];
        }
    } elseif ($bienSo !== ''){
        $xe = xe_theo_bien_so($bienSo);
        if ($xe !== null){
            $ra['vehicle_id'] = (int) $xe['id'];
            $bienSo = $xe['bien_so'];   // lấy đúng cách viết đã lưu của xe
        }
    }

    if ($bienSo !== ''){
        $ra['bien_so']       = $bienSo;
        $ra['bien_so_chuan'] = chuan_hoa_bien_so($bienSo);
    }
    if ($km > 0){
        $ra['so_km'] = $km;
        if ($ra['vehicle_id'] !== null){
            \App\core\Load::model('VehiclesModel')->capNhatKm($ra['vehicle_id'], $km);
        }
    }
    return $ra;
}
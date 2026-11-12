<?php
/**
 * Test: MỌI TUYẾN POST CỦA ADMIN ĐỀU PHẢI ĐI QUA PHÂN QUYỀN.
 *
 * Chạy:  C:\xampp\php\php.exe tests\TuyenPostCoChanTest.php
 *
 * ───────────────────────────────────────────────────────────────────────────
 * VÌ SAO CÓ TEST NÀY (11/11/2026) — lỗ hổng đã dựng lại được, không phải lo xa
 * ───────────────────────────────────────────────────────────────────────────
 *
 * `'admin/*' => [AuthMiddleware, RoleMiddleware]` trong configs/app.php KHÔNG
 * phải một luật ký tự đại diện chạy lúc request. App::updateConfigRouteMiddleware()
 * BUNG nó ra thành từng tuyến một, và danh sách tuyến của nhóm lấy từ
 * Route::getRouteInGroup('admin') — mà danh sách đó CHỈ do Route::get() ghi vào.
 *
 * Bảng middleware đánh khoá theo CHUỖI ĐƯỜNG DẪN, không theo phương thức HTTP.
 * Nên một tuyến POST trùng tên với một tuyến GET thì được bảo vệ ké tuyến GET
 * ấy — còn tuyến CHỈ CÓ POST thì rơi ra ngoài, không AuthMiddleware lẫn
 * RoleMiddleware. CsrfMiddleware là global nên vẫn chạy: người chưa đăng nhập
 * không lọt, nhưng BẤT KỲ tài khoản đã đăng nhập nào — bất kể vai trò, bất kể
 * thuộc gara nào — đều gọi được.
 *
 * Đo thật trên bảng tuyến: 111 tuyến POST của admin, 16 tuyến không được gắn.
 *
 * VÀ MỘT TẦNG THỨ HAI. RoleMiddleware chỉ phân biệt bốn hình dạng đường dẫn:
 * `/add`, `/edit/*`, `/delete/*`, `/permission/*`. Đường dẫn hình dạng khác chỉ
 * đòi quyền `view`. Nên chỉ gắn middleware vào `ton-kho-dau-ky/import` là chưa
 * đủ: người chỉ có quyền ĐỌC vẫn import được. 17 tuyến POST nằm dưới một module
 * mà đường dẫn không nói rõ add/edit/delete — và KHÔNG MỘT TUYẾN NÀO trong 17
 * đó là tuyến đọc, tất cả đều ghi dữ liệu. Vì vậy luật là ĐÓNG TRƯỚC:
 * POST vào màn của một module thì phải có quyền GHI trên module đó.
 *
 * VÌ SAO LÀ "CÓ QUYỀN GHI NÀO CŨNG ĐƯỢC" (add | edit | delete) chứ không phải
 * đòi đúng `edit`: đòi `edit` là khoá luôn chính người dùng thật. Trong CSDL
 * đang chạy, module `ton-kho-dau-ky` và `dat-hang-kho-tong` CHỈ CÓ hai quyền
 * `add` + `view` — kể cả nhóm Admin. Đòi `edit` ở `ton-kho-dau-ky/import` là
 * Admin cũng không import được. Mục 4 dưới đây giữ đúng chỗ đó.
 *
 * BA CHỖ DỄ HỎNG KHI VÁ, mỗi chỗ một mục dưới đây:
 *
 *   1. Vá Route::post() rồi QUÊN những đường dẫn KHÔNG khớp module nào.
 *      `admin/them-nhanh/danh-muc` là tuyến chỉ-có-POST và không thuộc module
 *      nào; RoleMiddleware gặp nó thì rơi ra khỏi mọi nhánh và kết thúc, trả về
 *      null. Mà Route::is() đọc null thành "không có quyền" => nút "+ thêm
 *      nhanh" ở mọi màn tắt ngóm. Phải trả lời TƯỜNG MINH cho nhóm đường dẫn
 *      này: màn không thuộc module thì không có gì để kiểm, cho đi tiếp.
 *
 *   2. Chặn quá tay. Nhân viên CÓ quyền thêm xe thì nút "+ thêm xe nhanh" phải
 *      vẫn chạy. Một bản vá làm mọi thứ đỏ đèn thì mọi khẳng định "bị chặn" ở
 *      dưới đều xanh một cách vô nghĩa.
 *
 *   3. Chặn mà dữ liệu vẫn đổi. Mọi khẳng định "bị chặn" dưới đây đều soi lại
 *      CSDL: chặn nghĩa là KHÔNG CÓ DÒNG NÀO được ghi, không phải chỉ là người
 *      dùng thấy trang báo lỗi.
 */

require_once __DIR__ . '/_helpers.php';

/* bootstrap.php require 'routes/web.php' theo đường dẫn TƯƠNG ĐỐI => phải đứng
   ở gốc dự án lúc nạp. Nạp cả bootstrap (chứ không grep routes/web.php như vài
   test khác) vì điều cần khẳng định là BẢNG TUYẾN THẬT sau khi đã bung
   'admin/*' — đọc text file nguồn không thấy được phép bung đó. */
$cwd = getcwd();
chdir(__DIR__ . '/..');
require_once __DIR__ . '/../bootstrap.php';
chdir($cwd);

use App\core\Route;

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

$pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB, _USER, _PASS,
               [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// ---------------------------------------------------------------------------
section('1. Bang tuyen: MOI tuyen POST cua admin deu phai duoc gan middleware');

$ref     = new ReflectionClass(Route::class);
$pRoutes = $ref->getProperty('routes');       $pRoutes->setAccessible(true);
$routes  = $pRoutes->getValue();
$pGroup  = $ref->getProperty('routeInGroup'); $pGroup->setAccessible(true);
$inGroup = $pGroup->getValue();

$duocGan   = array_fill_keys(!empty($inGroup['admin']) ? $inGroup['admin'] : [], true);
$adminPost = array_values(array_filter(array_keys($routes['post']),
                                       function($p){ return strpos($p, 'admin/') === 0; }));
$hoHong    = array_values(array_filter($adminPost,
                                       function($p) use ($duocGan){ return empty($duocGan[$p]); }));

ok(count($adminPost) > 100,
   'Doc duoc bang tuyen that (' . count($adminPost) . ' tuyen POST cua admin)',
   'Khong doc duoc thi ca muc nay vo nghia');

ok(empty($hoHong),
   'KHONG con tuyen POST nao cua admin nam ngoai bang middleware',
   count($hoHong) . ' tuyen khong duoc gan:' . "\n         - " . implode("\n         - ", $hoHong));

/* 16 tuyen da dem duoc ngay 11/11/2026. Liet ke tung ten mot, khong chi dem
   so: them tuyen moi mà quen gan thi con so tong co the van khop. */
$muoiSau = [
    'admin/garage-catalog/chon',                  'admin/garage-catalog/them-rieng',
    'admin/products/images/(\d+)',                'admin/galleries/images/(\d+)',
    'admin/galleries/add-video/(\d+)',            'admin/sales-invoices/einvoice/(\d+)',
    'admin/dat-hang-kho-tong/dat',                'admin/ton-kho-dau-ky/import',
    'admin/purchase-orders/tao-phieu-nhap/(\d+)', 'admin/them-nhanh/danh-muc',
    'admin/vehicles/them-nhanh',                  'admin/warranty/handover-store/(\d+)',
    'admin/nhac-bao-tri/save-config',             'admin/chat/reply/(\d+)',
    'admin/settings/save',                        'admin/part-prices/edit/tinh-lai',
];
$conHo = array_values(array_filter($muoiSau, function($p) use ($duocGan){ return empty($duocGan[$p]); }));
ok(empty($conHo),
   'Ca 16 tuyen tim ra ngay 11/11/2026 deu da duoc gan',
   'Con ho: ' . implode(', ', $conHo));

$maRoute = codeOnly($goc . 'core/Route.php');
ok(preg_match('~function post\([^)]*\)\{.*?routeInGroup~s', $maRoute) === 1,
   'Route::post() ghi duong dan vao $routeInGroup y nhu Route::get()',
   'Day la goc van de — thieu dong nay thi moi thu o tren chi la va ngoai da');

// ---------------------------------------------------------------------------
section('2. Duong dan KHONG thuoc module nao phai duoc DI TIEP, khong bi chan');

/* Va Route::post() xong thi `admin/them-nhanh/danh-muc` moi co middleware, va
   RoleMiddleware bat đầu duoc hoi ve mot duong dan khong thuoc module nao.
   Truoc day no roi ra khoi moi nhanh roi ket thuc (tra null); null di qua
   App::handleRouteMiddlewares() thanh `return false` = KHONG CO QUYEN. Cu de
   nhu vay thi nut "+ them nhanh" va o chon tinh/phuong tat het. */

$maRole = codeOnly($goc . 'app/middlewares/RoleMiddleware.php');

ok(preg_match('~if \(empty\(\$currentModuleId\)\)\s*\{\s*return true;~s', $maRole) === 1,
   'RoleMiddleware tra TRUE tuong minh khi duong dan khong khop module nao',
   'Khong duoc de ham roi xuong het than roi tra null — null bi doc thanh "bi chan"');

/* Than ham khong con ket thuc bang mot duong khong tra gi. Bat bang cach doc
   lenh cuoi cung cua handle(): phai la `return`, khong phai dau dong ngoac. */
ok(preg_match('~return (true|false);\s*\}\s*(private|protected|public|\})~s', $maRole) === 1,
   'handle() ket thuc bang mot cau return, khong roi ra ngoai thanh null',
   'Roi ra ngoai = tra null = App::handleRouteMiddlewares() doc thanh "bi chan"');

// ---------------------------------------------------------------------------
section('3. POST khong noi ro add/edit/delete thi phai doi quyen GHI');

ok(strpos($maRole, 'coQuyenGhi') !== false || strpos($maRole, 'quyenGhi') !== false,
   'RoleMiddleware co nhanh doi quyen GHI cho POST khong-hinh-dang',
   'Thieu nhanh nay thi `ton-kho-dau-ky/import` chi doi quyen `view`');

ok(preg_match('~laRequestGhi\(\).*?REQUEST_METHOD~s', $maRole) === 1
   && strpos($maRole, "'PUT', 'PATCH', 'DELETE'") !== false,
   'Nhanh do xet dung cac phuong thuc GHI (giong danh sach cua CsrfMiddleware)',
   'Xiet ca GET la khoa moi man chi de xem: xem chi tiet, in, bao cao');

// ---------------------------------------------------------------------------
section('3b. Nhan dien module phai khop theo BIEN duong dan, khong theo tien to');

/* Request::is('admin/ton-kho/*', ...) ghep thang tham so vao bieu thuc chinh
   quy, nen `/*` la luong tu "khong hoac nhieu dau gach cheo" — khong phai dau
   sao duong dan. Vay '~^admin/ton-kho/*~' KHOP CA 'admin/ton-kho-dau-ky/...'.
   Bon module co link la tien to cua link khac thi bang quyen cua CHINH NO chua
   bao gio duoc doc:
       'news'    trum 'news-categories' va 'newsletter'
       'ton-kho' trum 'ton-kho-dau-ky'  va 'ton-kho-lau'
   Loi nay nam im vi ca hai ben deu co `view`; den khi nhanh quyen GHI o muc 3
   doc toi dung module thi no mới lam Manager bi chan khoi 'ton-kho-dau-ky'. */

ok(strpos($maRole, 'khopModule') !== false
   && preg_match('~preg_quote\(\$link~', $maRole) === 1,
   'RoleMiddleware nhan dien module bang ham rieng, co preg_quote ten module',
   'Ghep thang ten module vao bieu thuc chinh quy la cho sinh ra loi trum khop');

ok(preg_match('~\(/\|\$\)~', $maRole) === 1,
   'Khop doi sau ten module phai la "/" hoac het chuoi (bien duong dan)',
   'Thieu bien thi admin/ton-kho-dau-ky/import bi nhan la module ton-kho');

ok(strpos($maRole, 'strlen($item[\'link\']) <= strlen($currentLink)') !== false,
   'Lay link DAI NHAT trong so khop, khong lay cai gap truoc',
   'De module con luon thang module cha neu sau nay co them cap tien to moi');

/* Va khang dinh cac cap tien to do co thuc trong CSDL — de neu sau nay co
   nguoi doi link module thi test nay con noi duoc vi sao no ton tai. */
$links = $pdo->query('SELECT link FROM modules')->fetchAll(PDO::FETCH_COLUMN);
$cap = [];
foreach ($links as $a) foreach ($links as $b){
    if ($a !== $b && strpos($b, $a) === 0) $cap[] = "$a -> $b";
}
ok(count($cap) >= 4,
   'CSDL that co it nhat 4 cap module bi trum tien to (ly do muc nay ton tai)',
   'Tim thay: ' . implode(' | ', $cap));

// ---------------------------------------------------------------------------
section('3c. Route::is() phai roi ve bang GET khi dang xu ly POST');

/* Nam chot "tu kiem quyen" trong controller CHUA BAO GIO chan duoc ai: chung
   chay trong luc xu ly POST va hoi ve mot MAN HINH (tuyen GET), ma Route::is()
   chi tim trong bang tuyen cua phuong thuc hien tai => khong thay gi => tra
   TRUE cho moi tai khoan. Nhin controller thi tuong co gac, ma khong co.
       Settings::save()          hoi 'admin/settings'
       Baotri::saveConfig()      hoi 'admin/nhac-bao-tri/edit/0'
       Chatadmin::reply()        hoi 'admin/chat/edit/<id>'
       Tonkhodauky::postImport() hoi 'admin/ton-kho-dau-ky/add'
       Dathangkhotong::postDat() hoi 'admin/dat-hang-kho-tong/add' */

ok(preg_match("~\\\$bangTim\s*=\s*\[\\\$method\]~", $maRoute) === 1
   && preg_match("~\\\$bangTim\[\]\s*=\s*'get'~", $maRoute) === 1,
   'Route::is() tim o bang cua phuong thuc hien tai, roi moi den bang GET',
   'Thieu phan roi ve GET thi moi cau route() hoi trong luc POST deu tra TRUE');

/** Duong dan $d tra ra tuyen nao trong bang $bang (null = khong tuyen nao) */
$traTuyen = function($d, $bang) use ($routes){
    foreach (array_keys($routes[$bang]) as $k){
        if (preg_match('~^' . $k . '$~i', $d)) return $k;
    }
    return null;
};

/* Settings::save() hoi 'admin/settings' — co that trong bang GET va co
   middleware. Nen voi phan roi ve GET o tren, chot nay TU NAY CHAN THAT. */
$tuyenSettings = $traTuyen('admin/settings', 'get');
ok($tuyenSettings !== null && !empty($duocGan[$tuyenSettings]),
   "Settings::save(): 'admin/settings' tra ra tuyen GET co middleware => chot da song",
   'Tra ra: ' . var_export($tuyenSettings, true));

/* CON BON CHOT KIA THI KHONG CUU DUOC BANG CACH NAY — chung hoi nhung duong
   dan KHONG TON TAI trong bang tuyen, ca GET lan POST:
       'admin/nhac-bao-tri/edit/0'  — module nay khong he co tuyen /edit/...
       'admin/chat/edit/<id>'       — chat chi co /view, /set-status, /delete
       'admin/ton-kho-dau-ky/add'   — khong he co tuyen /add
       'admin/dat-hang-kho-tong/add'— khong he co tuyen /add
   route() khong tim thay gi thi tra TRUE, nen bon chot nay la MA CHET: khong
   bao gio chan duoc ai, du sua Route::is() the nao. Thu dang gac bon tuyen do
   bay gio la MIDDLEWARE (muc 1 + muc 3), khong phai bon dong if ay.

   Khang dinh nay ghi lai su that do de khong ai doc controller roi tuong la da
   co gac. Sua tung chot cho dung duong dan la viec rieng, chua lam o lan nay. */
$maChet = ['admin/nhac-bao-tri/edit/0', 'admin/chat/edit/7',
           'admin/ton-kho-dau-ky/add', 'admin/dat-hang-kho-tong/add'];
$laiSong = [];
foreach ($maChet as $d){
    if ($traTuyen($d, 'get') !== null || $traTuyen($d, 'post') !== null) $laiSong[] = $d;
}
ok(count($laiSong) === 0,
   'Bon chot con lai van hoi duong dan khong ton tai (ma chet) — middleware moi la cho gac',
   'Da co tuyen that, nen chot tro lai song: ' . implode(', ', $laiSong)
   . ' — doi khang dinh nay thanh "chot phai chan that"');

/* Va dieu quan trong: bon tuyen POST do PHAI duoc middleware gac, vi khong con
   gi khac gac chung. */
$bonTuyen = ['admin/nhac-bao-tri/save-config', 'admin/chat/reply/(\d+)',
             'admin/ton-kho-dau-ky/import', 'admin/dat-hang-kho-tong/dat'];
$khongGac = array_values(array_filter($bonTuyen, function($p) use ($duocGan){ return empty($duocGan[$p]); }));
ok(empty($khongGac),
   'Bon tuyen co chot ma chet deu nam trong bang middleware',
   'Khong duoc gac: ' . implode(', ', $khongGac));

// ---------------------------------------------------------------------------
section('4. Goi HTTP that: an danh / Staff / Manager');

/* Dia chi lay tu CHINH CHO DAT FILE TEST (web_goc() trong _helpers.php), khong
   go cung '/tan-phat': bo test nay con chay trong git worktree, ma worktree co
   ban ma KHAC ban o thu muc chinh. */
$base = web_goc();

/* VA PHAI DOI CHIEU VOI .env. _WEB_URL do APP_BASE_PATH quyet dinh; neu nó tro
   ve mot ban ma khac thi moi chuyen huong bay sang ban do va test se bao ve
   ban SAI — dung im con hon bao xanh oan. */
$duongDanEnv = trim((string) parse_url(_WEB_URL, PHP_URL_PATH), '/');
$duongDanWeb = trim((string) parse_url($base, PHP_URL_PATH), '/');
if ($duongDanEnv !== $duongDanWeb){
    echo "\n[SKIP] .env APP_BASE_PATH = '/" . $duongDanEnv . "' khong tro vao ban ma nay"
       . " ('/" . $duongDanWeb . "') — bo qua phan HTTP de khong do ban ma khac.\n";
    exit(summary());
}

$MK   = 'Gara@2026';
$jars = [];
register_shutdown_function(function() use (&$jars){ foreach ($jars as $j) @unlink($j); });

$http = function($method, $url, $jar, $data = null){
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 20,
    ]);
    if ($method === 'POST'){
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $raw  = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    $body = $raw === false ? '' : substr($raw, (int) $info['header_size']);
    return ['code' => (int) $info['http_code'], 'loc' => (string) $info['redirect_url'], 'text' => $body];
};
$token = function($h){ return preg_match('~name="_token" value="([^"]+)"~', $h, $mm) ? $mm[1] : ''; };
$moJar = function() use (&$jars){ $j = tempnam(sys_get_temp_dir(), 'zzpost'); $jars[] = $j; return $j; };

/* Mo mot phien. Email rong = khach chua dang nhap.
   @return [jar, token] hoac null neu Apache khong chay */
$moPhien = function($email) use ($http, $token, $moJar, $base, $MK){
    $jar = $moJar();
    $r   = $http('GET', "$base/dang-nhap", $jar);
    if ($r['code'] === 0) return null;                      // Apache khong chay
    $tk = $token($r['text']);
    if ($email !== ''){
        $http('POST', "$base/dang-nhap", $jar, ['email' => $email, 'password' => $MK, '_token' => $tk]);
        /* Token MOI sau khi dang nhap: phien doi thi token cu khong con dung.
           Lay o mot form bat ky ma ca ba vai deu mo duoc. */
        $tk = $token($http('GET', "$base/admin/customers/add", $jar)['text']);
    }
    return ['jar' => $jar, 'tk' => $tk];
};

$thu = $moPhien('');
if ($thu === null){
    echo "\n[SKIP] Apache khong chay (localhost:88) — bo qua phan goi HTTP that.\n"
       . "       Bat bang: C:\\xampp\\apache\\bin\\httpd.exe\n";
    exit(summary());
}

$phien = [
    'an danh' => $thu,
    'Staff'   => $moPhien('covan.sg@gara-mau.test'),
    'Manager' => $moPhien('chugara.sg@gara-mau.test'),
];
foreach ($phien as $vai => $p){
    ok(!empty($p['tk']),
       "Lay duoc CSRF token cho vai '$vai'",
       'Khong co token thi CsrfMiddleware chan truoc, va moi khang dinh "bi chan"'
       . ' duoi day xanh mot cach vo nghia');
}

/** Da bi chan chua? Chan = bi day ve trang dang nhap hoac trang khong-co-quyen. */
$biChan = function($kq){
    return strpos($kq['loc'], 'khong-co-quyen') !== false
        || strpos($kq['loc'], 'dang-nhap') !== false;
};

// --- 4a. Tao hang rieng cho gara: Staff khong co MOT dong quyen nao tren module nay
section('4a. garage-catalog/them-rieng — co ghi du lieu that');

$tenThu = 'ZZ test phan quyen ' . date('His');
$demHang = function() use ($pdo, $tenThu){
    $st = $pdo->prepare('SELECT COUNT(*) FROM parts WHERE name = ?');
    $st->execute([$tenThu]);
    return (int) $st->fetchColumn();
};

try {
    foreach (['an danh', 'Staff'] as $vai){
        $truoc = $demHang();
        $kq = $http('POST', "$base/admin/garage-catalog/them-rieng", $phien[$vai]['jar'],
                    ['name' => $tenThu, 'item_type' => 'service', '_token' => $phien[$vai]['tk']]);
        $sau = $demHang();
        ok($biChan($kq) && $sau === $truoc,
           strtoupper($vai) . ': bi chan, KHONG co mat hang nao duoc tao',
           'HTTP ' . $kq['code'] . ' -> ' . $kq['loc'] . ' | so dong parts: ' . $truoc . ' -> ' . $sau);
    }

    /* DOI CHUNG — khong co khang dinh nay thi ca muc tren xanh chi vi moi thu
       deu bi chan. Manager co du add/edit/delete tren garage-catalog. */
    $truoc = $demHang();
    $kq = $http('POST', "$base/admin/garage-catalog/them-rieng", $phien['Manager']['jar'],
                ['name' => $tenThu, 'item_type' => 'service', '_token' => $phien['Manager']['tk']]);
    $sau = $demHang();
    ok(!$biChan($kq) && $sau === $truoc + 1,
       'MANAGER (co quyen ghi): chay duoc, mat hang DUOC tao',
       'HTTP ' . $kq['code'] . ' -> ' . $kq['loc'] . ' | so dong parts: ' . $truoc . ' -> ' . $sau);

} finally {
    $pdo->prepare('DELETE FROM parts WHERE name = ?')->execute([$tenThu]);
}
ok($demHang() === 0, 'Da xoa sach mat hang thu', 'Con lai ' . $demHang() . ' dong');

// --- 4b. Quyen GHI, khong chi `view` ---------------------------------------
section('4b. Doi quyen GHI chu khong chi `view`');

/* Staff CO quyen `view` tren ca hai module duoi day. Neu chi gan middleware ma
   khong doi quyen ghi thi hai khang dinh nay do: `view` la du. */

$demPhieuNhap = function() use ($pdo){ return (int) $pdo->query('SELECT COUNT(*) FROM goods_receipts')->fetchColumn(); };

$truoc = $demPhieuNhap();
$kq = $http('POST', "$base/admin/ton-kho-dau-ky/import", $phien['Staff']['jar'],
            ['_token' => $phien['Staff']['tk']]);
ok($biChan($kq) && $demPhieuNhap() === $truoc,
   'STAFF (chi `view` tren ton-kho-dau-ky): bi chan khoi import',
   'HTTP ' . $kq['code'] . ' -> ' . $kq['loc']);

/* DOI CHUNG QUAN TRONG — khang dinh nay giu MOT LUC HAI CHO, va da do that
   trong lan chay dau tien cua ban va:
     1. Manager chi co `add` + `view` tren ton-kho-dau-ky (CSDL that khong he
        co dong `edit` cho module nay, ke ca nhom Admin). Doi dung quyen `edit`
        la khoa luon nguoi dung that.
     2. 'ton-kho' la TIEN TO cua 'ton-kho-dau-ky', nen neu nhan dien module con
        khop theo tien to thi duong nay bi nhan la module 'ton-kho' — ma Manager
        chi co `view` tren 'ton-kho' => bi chan oan. Xem muc 3b. */
$kq = $http('POST', "$base/admin/ton-kho-dau-ky/import", $phien['Manager']['jar'],
            ['_token' => $phien['Manager']['tk']]);
ok(!$biChan($kq),
   'MANAGER (co `add`, KHONG co `edit`): van qua duoc cua — luat la "co quyen ghi nao cung duoc"',
   'HTTP ' . $kq['code'] . ' -> ' . $kq['loc']
   . ' | Do neu ban va doi dung quyen `edit`: module nay khong ai co `edit`');

/* purchase-orders: Staff co `view`, khong co quyen ghi. Goi vao mot id khong
   ton tai => khong the doi du lieu, nhung van phan biet duoc hai duong:
   bi chan thi ve khong-co-quyen; lot qua thi controller bao "khong tim thay". */
$kq = $http('POST', "$base/admin/purchase-orders/tao-phieu-nhap/99999999", $phien['Staff']['jar'],
            ['_token' => $phien['Staff']['tk'], 'warehouse_id' => '1']);
ok($biChan($kq),
   'STAFF (chi `view` tren purchase-orders): bi chan khoi tao phieu nhap',
   'HTTP ' . $kq['code'] . ' -> ' . $kq['loc']);

/* settings: Staff KHONG co mot dong quyen nao => chan ngay o cua `view` */
$demSetting = function() use ($pdo){
    return (string) $pdo->query('SELECT COALESCE(GROUP_CONCAT(CONCAT(`skey`, "=", COALESCE(`svalue`, "")) ORDER BY `skey`), "") FROM site_settings')->fetchColumn();
};
$truoc = $demSetting();
$kq = $http('POST', "$base/admin/settings/save", $phien['Staff']['jar'],
            ['site_name' => 'ZZ bi chiem quyen', '_token' => $phien['Staff']['tk']]);
ok($biChan($kq) && $demSetting() === $truoc,
   'STAFF (khong co quyen nao tren settings): bi chan, bang settings KHONG doi',
   'HTTP ' . $kq['code'] . ' -> ' . $kq['loc']);

// --- 4c. KHONG duoc chan qua tay ------------------------------------------
section('4c. Chan qua tay cung la vo — ba duong nay phai VAN CHAY');

/* (1) Nut "+ them nhanh" — tuyen chi-co-POST, KHONG thuoc module nao.
   Day la cho de gay nhat khi va Route::post(). Manager co quyen `add` tren
   part-categories, nen phai them duoc that. */
$tenDM = 'ZZ danh muc thu ' . date('His');
try {
    $kq = $http('POST', "$base/admin/them-nhanh/danh-muc", $phien['Manager']['jar'],
                ['loai' => 'part-cat', 'ten' => $tenDM, '_token' => $phien['Manager']['tk']]);
    $js = json_decode($kq['text'], true);
    ok(!$biChan($kq) && is_array($js) && !empty($js['ok']),
       'MANAGER them nhanh danh muc phu tung: VAN CHAY (tuyen khong thuoc module nao)',
       'HTTP ' . $kq['code'] . ' -> ' . $kq['loc'] . ' | tra ve: ' . substr($kq['text'], 0, 160));
} finally {
    $pdo->prepare('DELETE FROM part_categories WHERE name = ?')->execute([$tenDM]);
}

/* (2) Cung tuyen do, Staff KHONG co quyen `add` tren part-categories: phai bi
   controller tu choi bang JSON `ok:false` — chu KHONG phai bi middleware day
   sang trang HTML "khong co quyen". O chon goi bang AJAX va doc JSON; tra ve
   mot trang HTML la o chon dung im khong hieu vi sao. */
$kq = $http('POST', "$base/admin/them-nhanh/danh-muc", $phien['Staff']['jar'],
            ['loai' => 'part-cat', 'ten' => 'ZZ khong duoc phep', '_token' => $phien['Staff']['tk']]);
$js = json_decode($kq['text'], true);
ok(is_array($js) && isset($js['ok']) && $js['ok'] === false && !$biChan($kq),
   'STAFF them nhanh danh muc bi tu choi bang JSON (controller tu kiem), khong bi day trang',
   'HTTP ' . $kq['code'] . ' -> ' . $kq['loc'] . ' | tra ve: ' . substr($kq['text'], 0, 160));

/* (3) O chon tinh / phuong — tuyen GET, khong thuoc module nao. */
$kq = $http('GET', "$base/admin/dia-gioi/tinh", $phien['Staff']['jar']);
$js = json_decode($kq['text'], true);
ok(!$biChan($kq) && is_array($js) && count($js) > 10,
   'O chon tinh/phuong VAN CHAY cho Staff (tuyen khong thuoc module nao)',
   'HTTP ' . $kq['code'] . ' -> ' . $kq['loc'] . ' | tra ve: ' . substr($kq['text'], 0, 120));

/* (4) Nhan vien CO quyen thi duong phai thong. Staff co add+edit tren
   vehicles => POST phai ĐEN DUOC controller (controller tu bao thieu khach
   hang). Bi day ve khong-co-quyen o day la chan qua tay. */
$kq = $http('POST', "$base/admin/vehicles/them-nhanh", $phien['Staff']['jar'],
            ['_token' => $phien['Staff']['tk']]);
$js = json_decode($kq['text'], true);
ok(!$biChan($kq) && is_array($js),
   'STAFF (co add tren vehicles): "+ them xe nhanh" den duoc controller',
   'HTTP ' . $kq['code'] . ' -> ' . $kq['loc'] . ' | tra ve: ' . substr($kq['text'], 0, 160));

exit(summary());

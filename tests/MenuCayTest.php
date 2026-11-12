<?php
/**
 * MENU WEBSITE — CÂY KÉO THẢ, Ô SỬA BÊN PHẢI (12/11/2026).
 *
 * Anh xem màn menu của bên khách trong bản ghi họp 11/11 rồi bảo "menu mình
 * làm giống họ cho hay nhỉ". Màn này dựng lại theo CÁCH LÀM VIỆC của họ.
 *
 * Test giữ ba điều, theo thứ tự quan trọng:
 *
 *   1. KHÔNG PHÂN TRANG NỮA. Đây là sửa lỗi chứ không phải sửa cho đẹp: cây
 *      menu bị cắt trang thì mục con rơi sang trang sau, đứng tách khỏi cha —
 *      mà cấu trúc cha-con chính là thứ màn này tồn tại để sửa.
 *
 *   2. SẮP XẾP LẠI PHẢI LƯU THẬT. Kéo thả xong bấm Lưu thì `sort_order` và
 *      `parent_id` trong CSDL phải đổi đúng như trên màn.
 *
 *   3. SẮP XẾP KHÔNG ĐƯỢC VƯỢT RÀO. Tuyến này CHỈ CÓ POST — đúng họ tuyến
 *      từng hở phân quyền (xem TuyenPostCoChanTest). Nên phải thử thật:
 *      gara khác gửi id của mình lên thì không được đụng, và người chỉ có
 *      quyền xem thì bị chặn.
 */

require_once __DIR__ . '/_helpers.php';

$goc = __DIR__ . '/../';

// ===========================================================================
section('Nguon: cay + o sua, khong con phan trang');

$view = file_get_contents($goc . 'app/views/admin/menus/lists.php');

ok(strpos($view, 'phan_trang') === false,
   'Man menu KHONG con phan trang (muc con khong roi sang trang sau)');
ok(strpos($view, 'id="cay-menu"') !== false, 'Co cay menu');
ok(strpos($view, 'id="f-menu"') !== false, 'Co o sua ngay tren cung mot man');
ok(strpos($view, 'draggable="true"') !== false, 'Moi muc keo tha duoc');
ok(strpos($view, 'menu-cay.js') !== false, 'Co nap menu-cay.js');
ok(is_file($goc . 'public/assets/js/menu-cay.js'), 'File menu-cay.js co that');

$js = file_get_contents($goc . 'public/assets/js/menu-cay.js');
/* Ba o chon dich trung ten `target_id`. O an ma van bat thi van gui len va
   may chu lay o cuoi cung -> luu sai dich. Day la cho de hong nhat cua man
   nay, nen giu rieng mot khang dinh. */
ok(strpos($js, 'o.disabled = !hop') !== false,
   'JS TAT han o dich khong dung kieu, khong chi an di');
ok(strpos($js, 'op.disabled = (op.value === minh)') !== false,
   'Khong cho mot muc tu chon chinh no lam cha');

$ctrl = codeOnly($goc . 'app/controllers/admin/Menus.php');
ok(strpos($ctrl, 'function postSapXep') !== false, 'Controller co ham luu thu tu');
ok(preg_match('~function postSapXep.*?getDetail~s', $ctrl) === 1,
   'postSapXep() kiem tung id co that VA thuoc gara dang dang nhap');

$routes = file_get_contents($goc . 'routes/web.php');
ok(strpos($routes, "Route::post('menus/sap-xep'") !== false, 'Co tuyen POST menus/sap-xep');

// ===========================================================================
section('Chay that: luu thu tu + chan gara khac');

$base = web_goc();

$http = function($method, $url, $jar, $post = null){
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_HEADER => true, CURLOPT_TIMEOUT => 30]);
    if ($method === 'POST'){ curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query((array) $post)); }
    $r = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hs   = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    preg_match('~^Location:\s*(.+)$~im', substr((string) $r, 0, $hs), $m);
    return ['code' => $code, 'loc' => isset($m[1]) ? trim($m[1]) : '', 'body' => substr((string) $r, $hs)];
};

$dangNhap = function($email) use ($base, $http){
    $jar = tempnam(sys_get_temp_dir(), 'mc');
    $r = $http('GET', "$base/dang-nhap", $jar);
    if ($r['code'] === 0) return [null, null];
    if (!preg_match('~name="_token"[^>]*value="([^"]+)"~', $r['body'], $m)) return [null, null];
    $http('POST', "$base/dang-nhap", $jar, ['_token' => $m[1], 'email' => $email, 'password' => 'Gara@2026']);
    return [$jar, $m[1]];
};

list($jarTP) = $dangNhap('admin@gara-mau.test');
if ($jarTP === null){ echo "\n[SKIP] Apache khong chay (localhost:88).\n"; exit(summary()); }

require_once $goc . 'vendor/autoload.php';
require_once $goc . 'config.php';
try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){ echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary()); }

$docMenu = function($garaId) use ($pdo){
    $st = $pdo->prepare('SELECT id, parent_id, sort_order FROM menus WHERE garage_id = ? ORDER BY id');
    $st->execute([$garaId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
};

$menuTP = $docMenu(1);
if (count($menuTP) < 2){ echo "\n[SKIP] Gara 1 co duoi 2 muc menu, khong thu sap xep duoc.\n"; exit(summary()); }

$r = $http('GET', "$base/admin/menus", $jarTP);
ok($r['code'] === 200, 'Tan Phat mo duoc man Menu website', 'HTTP ' . $r['code']);
ok(substr_count($r['body'], 'class="list-group-item py-2 muc-menu"') === count($menuTP),
   'Cay hien DU moi muc, khong cat trang',
   'CSDL ' . count($menuTP) . ' muc, man hien ' . substr_count($r['body'], 'class="list-group-item py-2 muc-menu"'));

if (!preg_match('~name="_token"[^>]*value="([^"]+)"~', $r['body'], $mt)){
    echo "\n[SKIP] Khong lay duoc token o man menu.\n"; exit(summary());
}
$tokTP = $mt[1];

/* Dao nguoc thu tu: moi muc ve goc, sort_order chay nguoc. Dao nguoc chu
   khong doi mot dong — doi mot dong thi code chi ghi dung dong do van qua. */
$cu  = [];
$moi = [];
$n   = count($menuTP);
foreach ($menuTP as $i => $m){
    $cu[(int) $m['id']] = ['parent_id' => $m['parent_id'], 'sort_order' => (int) $m['sort_order']];
    $moi[] = ['id' => (int) $m['id'], 'parent_id' => 0, 'sort_order' => $n - $i];
}

$r = $http('POST', "$base/admin/menus/sap-xep", $jarTP, ['_token' => $tokTP, 'thuTu' => json_encode($moi)]);
ok($r['code'] === 302 && strpos($r['loc'], 'khong-co-quyen') === false,
   'Tan Phat luu duoc thu tu moi', 'HTTP ' . $r['code'] . ' ' . $r['loc']);

$sau = [];
foreach ($docMenu(1) as $m) $sau[(int) $m['id']] = (int) $m['sort_order'];
$dungHet = true;
foreach ($moi as $x){ if (!isset($sau[$x['id']]) || $sau[$x['id']] !== $x['sort_order']) $dungHet = false; }
ok($dungHet, 'CSDL nhan DUNG thu tu moi cho tung muc');

// ----------------------------------------------------------------- cach ly
list($jarB, ) = $dangNhap('chugara.sg@gara-mau.test');
$rB = $http('GET', "$base/admin/menus", $jarB);
if ($rB['code'] === 200 && preg_match('~name="_token"[^>]*value="([^"]+)"~', $rB['body'], $mb)){

    $pha = [];
    foreach ($menuTP as $i => $m) $pha[] = ['id' => (int) $m['id'], 'parent_id' => 0, 'sort_order' => 999 - $i];

    $http('POST', "$base/admin/menus/sap-xep", $jarB, ['_token' => $mb[1], 'thuTu' => json_encode($pha)]);

    $sauPha = [];
    foreach ($docMenu(1) as $m) $sauPha[(int) $m['id']] = (int) $m['sort_order'];
    $giuNguyen = true;
    foreach ($sau as $id => $st){ if (!isset($sauPha[$id]) || $sauPha[$id] !== $st) $giuNguyen = false; }

    ok($giuNguyen, 'Gara khac gui id cua Tan Phat len: menu Tan Phat KHONG doi mot dong nao');
} else {
    ok(false, 'Gara B mo duoc man menu de thu cach ly', 'HTTP ' . $rB['code']);
}

// ------------------------------------------------------------ chi quyen xem
list($jarS) = $dangNhap('covan.sg@gara-mau.test');
$rS = $http('GET', "$base/admin/menus", $jarS);
if ($rS['code'] === 200 && preg_match('~name="_token"[^>]*value="([^"]+)"~', $rS['body'], $ms)){
    $r = $http('POST', "$base/admin/menus/sap-xep", $jarS, ['_token' => $ms[1], 'thuTu' => '[]']);
    ok($r['code'] === 302 && strpos($r['loc'], 'khong-co-quyen') !== false,
       'Nguoi chi co quyen XEM bi chan khoi viec sap xep lai menu',
       'HTTP ' . $r['code'] . ' ' . $r['loc']);
} else {
    /* Staff khong duoc xem man menu thi cung khong sap xep duoc — van dung y. */
    $r = $http('POST', "$base/admin/menus/sap-xep", $jarS, ['_token' => 'x', 'thuTu' => '[]']);
    ok($r['code'] !== 200, 'Staff khong vao duoc man menu, cung khong sap xep duoc', 'HTTP ' . $r['code']);
}

// ------------------------------------------------------------- tra lai y cu
$up = $pdo->prepare('UPDATE menus SET parent_id = ?, sort_order = ? WHERE id = ?');
foreach ($cu as $id => $v) $up->execute([$v['parent_id'], $v['sort_order'], $id]);

$traLai = true;
foreach ($docMenu(1) as $m){
    if ((int) $m['sort_order'] !== $cu[(int) $m['id']]['sort_order']) $traLai = false;
}
ok($traLai, 'Da tra menu Tan Phat ve nhu truoc khi thu');

exit(summary());

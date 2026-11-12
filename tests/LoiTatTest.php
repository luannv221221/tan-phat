<?php
/**
 * LOI TAT TREN THANH TREN CUNG — MOI NGUOI TU DAT (khach yeu cau 12/11/2026).
 *
 * "lam ca cai nut shortcut tren menu cho anh nguoi ta co the chu dong set".
 * Truoc do thanh tren co ba nut viet cung (Ban hang / Mua hang / Cham soc).
 *
 * Day la tinh nang GHI vao du lieu cua tung nguoi, va lối tắt in ra thanh <a
 * href> tren MOI trang cua ho — nen phan quan trong nhat la cac phep thu tan
 * cong, khong phai duong chay dung:
 *
 *   1. Sua ho nguoi khac (gui tay user_id)
 *   2. Nhet URL la (javascript:, duong ra ngoai) vao cho ho bam nhieu nhat
 *   3. Ghim man khong co quyen (nut bam vao an trang tu choi)
 *   4. Vuot so luong (tich 7, luu, muc thu 7 im lang bien mat)
 *   5. Quyen doi SAU khi ghim (nut van con tren thanh)
 *   6. Gop NULL va '[]' (bo het nut -> mo lai thay ba nut mac dinh moc ve)
 */

require_once __DIR__ . '/_helpers.php';

$goc = __DIR__ . '/../';

/* Nap bang tuyen THAT (can chdir vi bootstrap require routes/web.php tuong doi). */
$cu = getcwd();
chdir($goc);
ob_start();
require_once $goc . 'bootstrap.php';
ob_end_clean();
chdir($cu);

// ===========================================================================
section('Danh muc: moi khoa co tuyen that, moi icon co that');

$tuyen = array_flip((new \App\core\Route())->getRouteInGroup('admin'));
$dm    = loi_tat_danh_muc(null);

ok(count($dm) >= 10, 'Danh muc co du nhom "Lap nhanh"', count($dm) . ' muc');

$khongTuyen = [];
foreach ($dm as $k => $tt){ if (!isset($tuyen['admin/' . $k])) $khongTuyen[] = $k; }
ok(empty($khongTuyen),
   'Moi muc trong danh muc deu la TUYEN GET THAT (route() tra "duoc phep" ca voi duong khong ton tai)',
   empty($khongTuyen) ? '' : 'khong co tuyen: ' . implode(', ', $khongTuyen));

$sprite = file_get_contents($goc . 'public/assets/vendor/lucide/lucide.svg');
$thieuIcon = [];
foreach ($dm as $k => $tt){ if (strpos($sprite, 'id="i-' . $tt['icon'] . '"') === false) $thieuIcon[] = $tt['icon']; }
foreach (['star', 'settings'] as $i){ if (strpos($sprite, 'id="i-' . $i . '"') === false) $thieuIcon[] = $i; }
ok(empty($thieuIcon),
   'Moi icon dung deu co trong sprite (icon() thieu hinh thi in o trong chu khong bao loi)',
   empty($thieuIcon) ? '' : 'thieu: ' . implode(', ', array_unique($thieuIcon)));

foreach (loi_tat_mac_dinh() as $k){
    ok(isset(loi_tat_danh_muc([['link' => 'customers', 'name' => 'Khach hang']])[$k]),
       "Bo mac dinh `$k` nam trong danh muc");
}

/* Module tu bang `modules` chi len danh muc neu co tuyen chi muc that. */
$giaLap = loi_tat_danh_muc([
    ['link' => 'customers',      'name' => 'Khach hang'],
    ['link' => 'khong-co-tuyen', 'name' => 'Man ma'],
]);
ok(isset($giaLap['customers']) && !isset($giaLap['khong-co-tuyen']),
   'Module KHONG co tuyen chi muc khong mon thanh nut dan toi 404');
ok($giaLap['customers']['ngan'] === 'Chăm sóc',
   'Man Khach hang giu nhan "Cham soc" (ten nut tu 05/11, nguoi quen khong bi doi ten)');

// ===========================================================================
section('Giai ma cot: ba trang thai KHAC NHAU');

ok(loi_tat_giai_ma(null) === loi_tat_mac_dinh(), 'NULL = chua tuy chinh -> bo mac dinh');
ok(loi_tat_giai_ma('')   === loi_tat_mac_dinh(), 'Chuoi rong -> bo mac dinh');
ok(loi_tat_giai_ma('[]') === [], "'[]' = chu dong bo het -> KHONG nut nao (khong phai mac dinh)");
ok(loi_tat_giai_ma('["a","b"]') === ['a', 'b'], 'Danh sach duoc doc dung');
ok(loi_tat_giai_ma('{hong') === loi_tat_mac_dinh(), 'JSON hong -> ve mac dinh, khong lam chet ca trang');
ok(loi_tat_giai_ma('"chuoi"') === loi_tat_mac_dinh(), 'JSON khong phai mang -> ve mac dinh');

// ===========================================================================
section('Nguon: ba dieu khong duoc sai');

$ctrl = file_get_contents($goc . 'app/controllers/admin/Loitat.php');
$code = codeOnly($goc . 'app/controllers/admin/Loitat.php');

ok(strpos($code, 'nguoi_dang_nhap()') !== false, 'Ma nguoi dung lay tu PHIEN dang nhap');
ok(strpos($code, "'user_id'") === false && strpos($code, '"user_id"') === false && strpos($code, "\$f['id']") === false,
   'Controller KHONG doc ma nguoi dung tu form (gui tay user_id khong sua ho duoc ai)');
ok(strpos($code, 'in_array($khoa, $chon, true)') !== false,
   'Chi nhan khoa nam trong danh muc DONG (duyet theo danh muc, khong tin mang gui len)');
ok(strpos($code, 'route(\'admin/\' . $khoa)') !== false,
   'Chi nhan man ma nguoi do CO QUYEN vao');

$mod = $routesTxt = file_get_contents($goc . 'routes/web.php');
ok(preg_match("~Route::get\('loi-tat'~", $mod) === 1 && preg_match("~Route::post\('loi-tat'~", $mod) === 1,
   'Co tuyen GET + POST loi-tat');

$hdr = file_get_contents($goc . 'app/views/layouts/admin/header.php');
ok(strpos($hdr, 'loi_tat_cua_toi(') !== false, 'Header ve nut tu loi_tat_cua_toi() (khong con ba nut viet cung)');
ok(strpos($hdr, "'admin/quotations/add',      'shopping-cart'") === false,
   'Ba nut viet cung da duoc go khoi header');
ok(strpos($hdr, 'admin/loi-tat') !== false, 'Header co nut banh rang toi man dat loi tat');

// ===========================================================================
section('Chay that: dat, quen, tan cong');

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
$token = function($b){ return preg_match('~name="_token"[^>]*value="([^"]+)"~', $b, $m) ? $m[1] : ''; };

$dangNhap = function($email) use ($base, $http, $token){
    $jar = tempnam(sys_get_temp_dir(), 'lt');
    $r = $http('GET', "$base/dang-nhap", $jar);
    if ($r['code'] === 0) return null;
    $http('POST', "$base/dang-nhap", $jar, ['_token' => $token($r['body']), 'email' => $email, 'password' => 'Gara@2026']);
    return $jar;
};

$jarM = $dangNhap('chugara.sg@gara-mau.test');
if ($jarM === null){ echo "\n[SKIP] Apache khong chay (localhost:88).\n"; exit(summary()); }
$jarS = $dangNhap('covan.sg@gara-mau.test');

require_once $goc . 'vendor/autoload.php';
require_once $goc . 'config.php';
try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){ echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary()); }

$layId = function($email) use ($pdo){
    $st = $pdo->prepare('SELECT id FROM users WHERE email = ?'); $st->execute([$email]);
    return (int) $st->fetchColumn();
};
$idM = $layId('chugara.sg@gara-mau.test');
$idS = $layId('covan.sg@gara-mau.test');
$docCot = function($id) use ($pdo){
    $st = $pdo->prepare('SELECT shortcuts FROM users WHERE id = ?'); $st->execute([$id]);
    return $st->fetchColumn();
};
$datCot = function($id, $v) use ($pdo){
    $pdo->prepare('UPDATE users SET shortcuts = ? WHERE id = ?')->execute([$v, $id]);
};

/* Chup truoc, tra lai sau — du lieu that cua tai khoan thu. */
$chupM = $docCot($idM);
$chupS = $docCot($idS);

/* Nhan cua cac nut tren thanh, KHONG tinh nut banh rang. */
$nutTrenThanh = function($body){
    if (!preg_match('~<nav class="adm-topbar__nhanh".*?</nav>~s', $body, $m)) return null;
    preg_match_all('~<a href="[^"]*" title="([^"]*)">~', $m[0], $t);
    return $t[1];
};
$coBanhRang = function($body){ return strpos($body, 'adm-topbar__nhanh-cfg') !== false; };

try {
    // --------------------------------------------------- mac dinh khong doi
    $datCot($idM, null);
    $r = $http('GET', "$base/admin", $jarM);
    $nut = $nutTrenThanh($r['body']);
    ok($nut === ['Bán hàng', 'Mua hàng', 'Chăm sóc'],
       'Chua tuy chinh (NULL): van thay DUNG ba nut cu — khong ai bi doi gi sau lung',
       json_encode($nut, JSON_UNESCAPED_UNICODE));
    ok($coBanhRang($r['body']), 'Co nut banh rang tuy chinh loi tat');

    // ---------------------------------------------------------- man dat
    $r = $http('GET', "$base/admin/loi-tat", $jarM);
    ok($r['code'] === 200, 'Mo duoc man Loi tat cua toi', 'HTTP ' . $r['code']);
    $soHop = substr_count($r['body'], 'name="loi_tat[]"');
    ok($soHop >= 8, 'Man dat liet ke cac man chu gara duoc phep', "$soHop o");
    /* Dung cau DAC TRUNG cua khoi thong bao ("Ban chua tuy chinh ..."), khong do
       chu "bo mac dinh": nut "Ve bo mac dinh" o chan trang cung chua dung chu do,
       nen do chu do thi khang dinh luon xanh du khoi thong bao co hay khong. */
    ok(strpos($r['body'], 'chưa tuỳ chỉnh') !== false, 'Nguoi chua tuy chinh duoc noi ro dang dung bo mac dinh');
    $tok = $token($r['body']);

    // ----------------------------------------------------- dat + hien dung
    $r = $http('POST', "$base/admin/loi-tat", $jarM, ['_token' => $tok, 'loi_tat' => ['phieu-thu/add', 'goods-receipts/add']]);
    ok($r['code'] === 302 && strpos($r['loc'], 'admin/loi-tat') !== false, 'Luu duoc', 'HTTP ' . $r['code'] . ' ' . $r['loc']);
    // thu tu = thu tu DANH MUC (nhap kho dung truoc phieu thu), khong phai thu tu gui
    ok($docCot($idM) === '["goods-receipts/add","phieu-thu/add"]',
       'CSDL luu dung, theo thu tu danh muc', var_export($docCot($idM), true));

    $nut = $nutTrenThanh($http('GET', "$base/admin", $jarM)['body']);
    ok($nut === ['Nhập kho', 'Phiếu thu'],
       'Thanh tren hien DUNG nhung gi da chon — Ban hang / Mua hang / Cham soc bien mat',
       json_encode($nut, JSON_UNESCAPED_UNICODE));

    // ------------------------------------- (2)(3) nhet khoa la + man khong quyen
    $r = $http('GET', "$base/admin/loi-tat", $jarM);
    $tok = $token($r['body']);
    $http('POST', "$base/admin/loi-tat", $jarM, ['_token' => $tok, 'loi_tat' => [
        'javascript:alert(1)', 'https://evil.example/', '../../etc/passwd', 'admin/settings', '//evil.example',
        'khong-co-khoa-nay', 'quotations/add']]);
    ok($docCot($idM) === '["quotations/add"]',
       'Khoa la / URL / duong ra ngoai bi BO QUA; chi khoa hop le duoc luu',
       var_export($docCot($idM), true));
    $html = $http('GET', "$base/admin", $jarM)['body'];
    ok(stripos($html, 'javascript:alert') === false && stripos($html, 'evil.example') === false,
       'Khong co `javascript:` hay duong ra ngoai trong HTML thanh tren');

    // -------- (3) Staff chi co quyen XEM: khong ghim duoc man can quyen THEM
    if ($jarS !== null){
        $rs = $http('GET', "$base/admin/loi-tat", $jarS);
        if ($rs['code'] === 200){
            $tS = $token($rs['body']);
            $http('POST', "$base/admin/loi-tat", $jarS, ['_token' => $tS, 'loi_tat' => ['purchase-orders/add', 'quotations/add', 'products/add']]);
            $luu = json_decode((string) $docCot($idS), true);
            ok(!in_array('purchase-orders/add', (array) $luu, true) && !in_array('products/add', (array) $luu, true),
               'Staff (chi co quyen xem) KHONG ghim duoc man lap moi ma minh khong co quyen',
               var_export($docCot($idS), true));
            ok(strpos($rs['body'], 'value="purchase-orders/add"') === false,
               'Man dat cua Staff khong cho chon man khong co quyen');
        } else {
            ok(true, '(Staff khong mo duoc man Loi tat — bo qua phep thu quyen) HTTP ' . $rs['code']);
        }
    }

    // ----------------------------------------------- (4) vuot so luong
    $datCot($idM, '["quotations/add"]');
    $r = $http('GET', "$base/admin/loi-tat", $jarM);
    preg_match_all('~name="loi_tat\[\]"[^>]*value="([^"]+)"~', $r['body'], $mk);
    $tatCa = $mk[1];
    if (count($tatCa) >= 8){
        $http('POST', "$base/admin/loi-tat", $jarM, ['_token' => $token($r['body']), 'loi_tat' => array_slice($tatCa, 0, 7)]);
        ok($docCot($idM) === '["quotations/add"]',
           'Chon 7 (toi da 6): TU CHOI, khong luu — khong lang le cat bo muc thu 7',
           var_export($docCot($idM), true));
        $thongBao = $http('GET', "$base/admin/loi-tat", $jarM)['body'];
        ok(strpos($thongBao, 'tối đa') !== false, 'Nguoi dung duoc NOI ro vi sao khong luu');

        $http('POST', "$base/admin/loi-tat", $jarM, ['_token' => $token($thongBao), 'loi_tat' => array_slice($tatCa, 0, 6)]);
        $luu = json_decode((string) $docCot($idM), true);
        ok(is_array($luu) && count($luu) === 6, 'Chon dung 6 thi luu duoc', var_export($docCot($idM), true));
    } else {
        ok(true, '(it hon 8 muc duoc phep — bo qua phep thu vuot so luong)');
    }

    // ------------------------------------------------ (6) bo het != mac dinh
    $r = $http('GET', "$base/admin/loi-tat", $jarM);
    $http('POST', "$base/admin/loi-tat", $jarM, ['_token' => $token($r['body'])]);   // khong tich gi
    ok($docCot($idM) === '[]', "Bo het -> luu '[]', KHONG phai NULL", var_export($docCot($idM), true));
    $r = $http('GET', "$base/admin", $jarM);
    ok($nutTrenThanh($r['body']) === [], 'Bo het: thanh tren KHONG con nut nao (khong mọc lai ba nut mac dinh)');
    ok($coBanhRang($r['body']), 'Bo het roi VAN con nut banh rang — lối duy nhat de quay lai dat');
    $man = $http('GET', "$base/admin/loi-tat", $jarM)['body'];
    ok(strpos($man, 'chưa tuỳ chỉnh') === false, "Da bo het chu dong: man dat khong noi la 'chua tuy chinh / dang dung mac dinh'");

    // --------------------------------------------------- ve bo mac dinh
    $http('POST', "$base/admin/loi-tat/mac-dinh", $jarM, ['_token' => $token($man)]);
    ok($docCot($idM) === null, 'Ve mac dinh -> cot ve NULL');
    ok($nutTrenThanh($http('GET', "$base/admin", $jarM)['body']) === ['Bán hàng', 'Mua hàng', 'Chăm sóc'],
       'Ve mac dinh -> ba nut cu quay lai');

    // ----------------------------------------------- (1) sua ho nguoi khac
    $datCot($idS, null);
    $r = $http('GET', "$base/admin/loi-tat", $jarM);
    $http('POST', "$base/admin/loi-tat", $jarM, ['_token' => $token($r['body']),
        'user_id' => $idS, 'id' => $idS, 'loi_tat' => ['phieu-chi/add']]);
    ok($docCot($idS) === null,
       'Gui tay user_id cua nguoi khac: loi tat cua NGUOI DO khong bi dong toi',
       var_export($docCot($idS), true));
    ok($docCot($idM) === '["phieu-chi/add"]', '...chi dong cua chinh nguoi dang nhap duoc sua',
       var_export($docCot($idM), true));

    // ------------------------------------------- khong dang nhap -> khong duoc
    $jarA = tempnam(sys_get_temp_dir(), 'lt');
    $http('GET', "$base/dang-nhap", $jarA);
    $truoc = $docCot($idM);
    $r = $http('POST', "$base/admin/loi-tat", $jarA, ['loi_tat' => ['quotations/add']]);
    ok($r['code'] !== 200 || strpos($r['body'], 'Đã lưu') === false, 'Chua dang nhap: POST khong duoc chap nhan', 'HTTP ' . $r['code']);
    ok($docCot($idM) === $truoc, '...va khong dong toi du lieu cua ai');
    @unlink($jarA);

    // ------------------------ (5)(2) quyen doi SAU khi ghim + khoa la trong CSDL
    if ($jarS !== null){
        $datCot($idS, '["purchase-orders/add","zzz-khong-co","javascript:alert(1)","customers"]');
        $htmlS = $http('GET', "$base/admin", $jarS)['body'];
        $nutS  = $nutTrenThanh($htmlS);
        ok(is_array($nutS) && !in_array('Mua hàng', $nutS, true),
           'Quyen khong con (Staff khong co quyen lap don mua): nut BIEN KHOI khoi thanh du da ghim truoc do',
           json_encode($nutS, JSON_UNESCAPED_UNICODE));
        ok(stripos($htmlS, 'javascript:alert') === false,
           'Khoa la nam san trong CSDL cung khong bao gio duoc in ra thanh');
    }
} finally {
    $datCot($idM, $chupM);
    $datCot($idS, $chupS);
}

ok($docCot($idM) === $chupM && $docCot($idS) === $chupS, 'Da tra loi tat cua hai tai khoan thu ve nhu cu');

@unlink($jarM);
if ($jarS !== null) @unlink($jarS);
exit(summary());

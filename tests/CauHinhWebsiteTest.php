<?php
/**
 * CAU HINH CHUNG / CAU HINH WEBSITE — MOI O CHI O MOT NOI, VA LUU MAN NAY KHONG
 * DUOC LAM MAT O CUA MAN KIA.
 *
 * Khach yeu cau 12/11/2026, hai lan:
 *   - "phan cau hinh chung nhung thu lien quan den website day het len Quan ly
 *      website"
 *   - (kem anh hai the "Thong tin chung" va "Lien he"): "cai nay phai o ben
 *      quan ly website chu"
 *
 * Lan dau em giu ten gara, logo, hotline, dia chi o Cau hinh chung voi ly do
 * "chung con in tren bao gia va hoa don". Ly do dung ve ky thuat nhung tra loi
 * sai cau hoi: trang khach hien thi chinh tam o do (ten 3 cho, hotline 3,
 * email 7, dia chi 5 trong view storefront). Noi sua khong can trung noi dung.
 *
 * HAI RUI RO, test nay giu ca hai:
 *
 *   1. MOT KHOA KHONG CO O NHAP NAO. $keys la whitelist day du; chia no cho
 *      hai man ma thieu mot khoa o ca hai form thi khoa do khong ai sua duoc —
 *      va khong bao loi, vi form khong gui thi save() cung khong ghi.
 *
 *   2. LUU MAN NAY XOA TRANG MAN KIA. save() cu duyet CA whitelist va gan ''
 *      cho o vang mat. Tach form lam hai ma khong chia khoa thi bam Luu o man
 *      website la XOA TRANG so tai khoan ngan hang, va bam Luu o man chung la
 *      XOA TRANG hotline. Phai thu that CA HAI CHIEU.
 */

require_once __DIR__ . '/_helpers.php';

$goc = __DIR__ . '/../';

$ctrl = file_get_contents($goc . 'app/controllers/admin/Settings.php');
$fChung = file_get_contents($goc . 'app/views/admin/settings/form.php');
$fWeb   = file_get_contents($goc . 'app/views/admin/cau-hinh-website/form.php');

/* Rut danh sach khoa tu CHINH controller — khong tu ghi lai o day, khong thi
   test nay chi kiem ban sao cua no. */
$bocMang = function($ten) use ($ctrl){
    if (!preg_match('~private\s+\$' . $ten . '\s*=\s*\[(.*?)\];~s', $ctrl, $m)) return [];
    $s = preg_replace('~/\*.*?\*/~s', '', $m[1]);
    $s = preg_replace('~//[^\n]*~', '', $s);
    preg_match_all("~'([a-z_]+)'~", $s, $k);
    return $k[1];
};
$tatCa = $bocMang('keys');
$web   = $bocMang('khoaWebsite');
$chung = array_values(array_diff($tatCa, $web));

// ===========================================================================
section('Chia khoa: moi khoa o DUNG MOT man');

ok(count($tatCa) >= 20, 'Doc duoc whitelist cua Settings', count($tatCa) . ' khoa');
ok(count($web) >= 15, 'Doc duoc danh sach khoa cua man website', count($web) . ' khoa');

$la = array_diff($web, $tatCa);
ok(empty($la), 'Moi khoa cua man website deu nam trong whitelist day du',
   empty($la) ? '' : 'khoa la: ' . implode(', ', $la));

/* Cac o NHAP trong form. Bo file/hidden an toan: chi lay ten, khong lay duoi
   `_file` (o tai len) vi no khong phai khoa cau hinh. */
$oNhap = function($src){
    preg_match_all('~name="([a-z_]+)"~', $src, $m);
    $ds = array_unique($m[1]);
    return array_values(array_filter($ds, function($x){ return substr($x, -5) !== '_file'; }));
};
$oChung = $oNhap($fChung);
$oWeb   = $oNhap($fWeb);

$moCoi = [];
foreach ($tatCa as $k){
    if (!in_array($k, $oChung, true) && !in_array($k, $oWeb, true)) $moCoi[] = $k;
}
ok(empty($moCoi), 'Khong khoa nao MO COI (khong o nhap nao o ca hai form)',
   empty($moCoi) ? '' : 'khong sua duoc: ' . implode(', ', $moCoi));

$trung = [];
foreach ($tatCa as $k){
    if (in_array($k, $oChung, true) && in_array($k, $oWeb, true)) $trung[] = $k;
}
ok(empty($trung), 'Khong khoa nao co o nhap o CA HAI form',
   empty($trung) ? '' : 'trung: ' . implode(', ', $trung));

/* CHIEU NGUOC CUA "MO COI": o nhap co trong form ma KHONG co trong whitelist.
   Go xong bam Luu cung khong luu, va khong bao loi nao — save() chi ghi khoa
   nam trong danh sach. Day la cach mot o "chuyen man" bi hong im lang. */
$khongLuuDuoc = [];
foreach (array_merge($oChung, $oWeb) as $k){
    if (!in_array($k, $tatCa, true)) $khongLuuDuoc[] = $k;
}
ok(empty($khongLuuDuoc), 'Moi o nhap trong hai form deu co trong whitelist (go xong bam Luu la luu)',
   empty($khongLuuDuoc) ? '' : 'khong luu duoc: ' . implode(', ', array_unique($khongLuuDuoc)));

$sai = [];
foreach ($web as $k){ if (in_array($k, $oChung, true)) $sai[] = $k; }
foreach ($chung as $k){ if (in_array($k, $oWeb, true)) $sai[] = $k; }
ok(empty($sai), 'O nhap nam DUNG man cua khoa do (form khop voi danh sach trong controller)',
   empty($sai) ? '' : 'lech: ' . implode(', ', $sai));

// ---------------------------------------------------------------------------
section('Tam o khach chi ra nam o man website');

foreach (['site_name', 'site_slogan', 'site_short_name', 'logo',
          'hotline', 'email', 'address', 'tax_code', 'facebook', 'zalo', 'messenger'] as $k){
    ok(in_array($k, $oWeb, true) && !in_array($k, $oChung, true),
       "`$k` o man Cau hinh website, khong con o Cau hinh chung");
}

/* Nhung o quyet dinh GARA LAM VIEC THE NAO: khong hien tren trang khach, o lai. */
/* `price_schedule` KHONG con o day: lich tu cap nhat gia chuyen sang man Bang tinh gia
   (13/11/2026, LichBangGiaTest). No cung khong duoc nam trong whitelist cua Settings. */
foreach (['linh_vuc', 'dien_tich_xuong', 'cost_method',
          'bank_name', 'bank_account', 'bank_holder'] as $k){
    ok(in_array($k, $oChung, true) && !in_array($k, $oWeb, true),
       "`$k` o lai Cau hinh chung (khong phai thu trang khach hien thi)");
}

/* Hai form tro duong sang nhau — nguoi dung dang tim o nao thi khong bi lac. */
ok(strpos($fChung, 'admin/cau-hinh-website') !== false, 'Cau hinh chung chi duong sang Cau hinh website');
ok(strpos($fWeb, 'admin/settings') !== false, 'Cau hinh website chi duong sang Cau hinh chung');

/* Logo can enctype multipart, khong thi chon file xong khong gui duoc gi. */
ok(preg_match('~<form[^>]*cau-hinh-website/save[^>]*enctype="multipart/form-data"~s', $fWeb) === 1,
   'Form website co enctype multipart (logo tai len duoc)');

// ===========================================================================
section('Chay that: luu man nay KHONG lam mat o cua man kia');

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

$jar = tempnam(sys_get_temp_dir(), 'ch');
$r = $http('GET', "$base/dang-nhap", $jar);
if ($r['code'] === 0){ echo "\n[SKIP] Apache khong chay (localhost:88).\n"; @unlink($jar); exit(summary()); }
if (!preg_match('~name="_token"[^>]*value="([^"]+)"~', $r['body'], $m)){ echo "\n[SKIP] Khong lay duoc token dang nhap.\n"; @unlink($jar); exit(summary()); }
$http('POST', "$base/dang-nhap", $jar, ['_token' => $m[1], 'email' => 'admin@gara-mau.test', 'password' => 'Gara@2026']);

require_once $goc . 'vendor/autoload.php';
require_once $goc . 'config.php';
try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){ echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; @unlink($jar); exit(summary()); }

/* Admin Tan Phat ghi vao `site_settings` (cau hinh CHUNG) — day la du lieu
   that. CHUP CA BANG, khong chi cac khoa trong whitelist cua controller.

   Truoc day chup theo whitelist, va do la mot loi that cua chinh test nay:
   khi em pha thu controller (bo `hotline` khoi whitelist de xem test co keu
   khong), test van GHI duoc hotline qua danh sach khoa cua man website, nhung
   luc don lai thi duyet whitelist da bi pha nen khong biet toi khoa do. Ket
   qua la gia tri thu `thu-web-7636` nam lai trong cau hinh that cua Tan Phat
   — hotline hien ra tren trang khach. Dung dung cai test dung de bat loi
   whitelist thi lai khong duoc phep phu thuoc vao whitelist. */
$doc = function() use ($pdo){
    $o = [];
    foreach ($pdo->query('SELECT skey, svalue FROM site_settings')->fetchAll(PDO::FETCH_ASSOC) as $x) $o[$x['skey']] = $x['svalue'];
    return $o;
};
$chup = $doc();

$tokenCua = function($url) use ($http, $jar, $base){
    $r = $http('GET', "$base/$url", $jar);
    return [$r['code'], preg_match('~name="_token"[^>]*value="([^"]+)"~', $r['body'], $m) ? $m[1] : ''];
};

list($c1, $tokW) = $tokenCua('admin/cau-hinh-website');
list($c2, $tokC) = $tokenCua('admin/settings');
ok($c1 === 200 && $tokW !== '', 'Admin mo duoc man Cau hinh website', "HTTP $c1");
ok($c2 === 200 && $tokC !== '', 'Admin mo duoc man Cau hinh chung', "HTTP $c2");

/* LOI VAO TREN MENU. Cau hinh website nam trong nhom Quan ly website, ma ban
   thu "menu gon cho kho tong" (16/10/2026) AN ca nhom do cua Tan Phat. Chuyen o
   sang ma khong de lai loi vao la Tan Phat mat cho sua logo / hotline cua chinh
   minh — da thay tren man hinh that chu khong phai suy luan. Dung duong dan
   chinh xac, vi `/admin/cau-hinh-website` khong trung dau voi module nao khac. */
$trangChu = $http('GET', "$base/admin", $jar);
ok(strpos($trangChu['body'], "$base/admin/cau-hinh-website\"") !== false,
   'Tan Phat (kho tong) CO loi vao Cau hinh website tren menu — du menu gon dang bat hay tat');

if ($tokW !== '' && $tokC !== ''){

    /* ---- CHIEU 1: luu man WEBSITE, o cua man CHUNG phai nguyen ---- */
    $dau = $doc();
    $dauuu = 'thu-web-' . random_int(1000, 9999);

    $post = ['_token' => $tokW];
    foreach ($web as $k){
        if ($k === 'og_image') continue;             // o anh xu ly rieng — de nguyen
        $post[$k] = isset($dau[$k]) ? $dau[$k] : '';
    }
    $post['hotline'] = $dauuu;
    $post['logo']    = isset($dau['logo']) ? $dau['logo'] : '';
    $http('POST', "$base/admin/cau-hinh-website/save", $jar, $post);

    $sau = $doc();
    ok(isset($sau['hotline']) && $sau['hotline'] === $dauuu, 'Luu man website: hotline doi dung');

    $mat = [];
    foreach ($chung as $k){
        $a = isset($dau[$k]) ? $dau[$k] : null;
        $b = isset($sau[$k]) ? $sau[$k] : null;
        if ($a !== $b) $mat[] = "$k: " . var_export($a, true) . ' -> ' . var_export($b, true);
    }
    ok(empty($mat), 'Luu man WEBSITE khong dong vao mot o nao cua man CHUNG (ngan hang, mst, gia von...)',
       empty($mat) ? count($chung) . ' khoa nguyen' : implode(' | ', $mat));

    /* ---- CHIEU 2: luu man CHUNG, o cua man WEBSITE phai nguyen ---- */
    $dau2 = $doc();
    $post = ['_token' => $tokC];
    foreach ($chung as $k) $post[$k] = isset($dau2[$k]) ? $dau2[$k] : '';
    $moi = 'Thu Ngan Hang ' . random_int(1000, 9999);
    $post['bank_name'] = $moi;
    $http('POST', "$base/admin/settings/save", $jar, $post);

    $sau2 = $doc();
    ok(isset($sau2['bank_name']) && $sau2['bank_name'] === $moi, 'Luu man chung: ngan hang doi dung');

    $mat2 = [];
    foreach ($web as $k){
        $a = isset($dau2[$k]) ? $dau2[$k] : null;
        $b = isset($sau2[$k]) ? $sau2[$k] : null;
        if ($a !== $b) $mat2[] = "$k: " . var_export($a, true) . ' -> ' . var_export($b, true);
    }
    ok(empty($mat2), 'Luu man CHUNG khong dong vao mot o nao cua man WEBSITE (ten, logo, hotline, lien he...)',
       empty($mat2) ? count($web) . ' khoa nguyen' : implode(' | ', $mat2));
}

// ----------------------------------------------------------- tra lai y cu
$pdo->beginTransaction();
$hienCo = $doc();
foreach ($chup as $k => $v){
    if (array_key_exists($k, $hienCo)){
        if ($hienCo[$k] !== $v) $pdo->prepare('UPDATE site_settings SET svalue = ? WHERE skey = ?')->execute([$v, $k]);
    } else {
        $pdo->prepare('INSERT INTO site_settings (skey, svalue) VALUES (?, ?)')->execute([$k, $v]);
    }
}
foreach ($hienCo as $k => $v){
    if (!array_key_exists($k, $chup)) $pdo->prepare('DELETE FROM site_settings WHERE skey = ?')->execute([$k]);
}
$pdo->commit();

$sauTra = $doc();
$lech = [];
foreach (array_unique(array_merge(array_keys($chup), array_keys($sauTra))) as $k){
    $a = isset($chup[$k]) ? $chup[$k] : null;
    $b = isset($sauTra[$k]) ? $sauTra[$k] : null;
    if ($a !== $b) $lech[] = $k;
}
ok(empty($lech), 'Da tra CA BANG cau hinh ve nhu truoc khi thu (khong chi cac khoa trong whitelist)',
   empty($lech) ? count($chup) . ' dong' : implode(', ', $lech));

@unlink($jar);
exit(summary());

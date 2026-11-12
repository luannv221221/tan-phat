<?php
/**
 * QUAN LY WEBSITE › SAN PHAM — CHI HANG HIEN THI TREN WEB, THEM MOI MAC DINH HIEN THI.
 *
 * Khach yeu cau 12/11/2026: "phan san pham cho quan ly website y minh chi cho
 * nhung san pham hien thi o website xuong, va khi them moi o do mac dinh la san
 * pham hien thi tren web, chu hien tai chi co menu mo tab duoi bat tab tren".
 *
 * Ban dau em ke `products` vao nhom Quan ly website nhu mot LOI TAT. Bam vao thi
 * ca hai nhom menu cung sang, danh sach van la toan bo hang hoa, them xong lai
 * van ve man Hang hoa thuong. Nay la mot NGU CANH rieng: `?nguon=web`.
 *
 * LA THAM SO, KHONG PHAI DUONG DAN / MODULE MOI. Duong admin khong thuoc module
 * nao thi RoleMiddleware CHO QUA (Fix B, 11/11/2026) — dung mot duong dan moi
 * ma quen dang ky module la mo toang cua ghi. Test nay giu ca hai y:
 *   - hanh vi dung (loc, mac dinh, o lai ngu canh sau khi luu)
 *   - KHONG sinh duong dan / module / quyen moi
 *
 * Khach cung CHOT 12/11/2026: "bo cai danh muc hang hoa o quan ly website di,
 * no ko hop ly". Test giu viec do da go that.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../app/helpers/functions.php';

$goc = __DIR__ . '/../';

// ===========================================================================
section('Ham tro giup: doc ngu canh tu GET lan POST');

$_GET = []; $_POST = [];
ok(ngu_canh_website() === false, 'Khong co tham so -> khong phai ngu canh website');
ok(ngu_canh_qs() === '', 'Khong ngu canh -> hau to rong (khong lam ban moi lien ket)');

$_GET = ['nguon' => 'web'];
ok(ngu_canh_website() === true, '?nguon=web -> la ngu canh website');
ok(ngu_canh_qs() === '?nguon=web', 'Hau to dung dang ?nguon=web');
ok(ngu_canh_qs('&') === '&nguon=web', 'Noi vao URL da co tham so thi dung &');

$_GET = []; $_POST = ['nguon' => 'web'];
ok(ngu_canh_website() === true, 'O an `nguon` trong form POST cung duoc nhan');

/* Gia tri la khong duoc coi la ngu canh — tham so tu go khong duoc mo kieu
   hien thi nao ngoai mot kieu duy nhat. */
$_GET = ['nguon' => 'tat-ca']; $_POST = [];
ok(ngu_canh_website() === false, 'Gia tri la -> khong phai ngu canh website');
$_GET = []; $_POST = [];

// ===========================================================================
section('Nguon: chi mot cho doi, khong sinh module / duong dan moi');

$sb   = file_get_contents($goc . 'app/views/layouts/admin/sidebar.php');
$prod = codeOnly($goc . 'app/controllers/admin/Products.php');
$lst  = file_get_contents($goc . 'app/views/admin/products/lists.php');
$web  = file_get_contents($goc . 'routes/web.php');

ok(strpos($sb, "'products@web'") !== false, 'Menu co loi vao `products@web` trong Quan ly website');
ok(preg_match("~'Quản lý website'\s*=>\s*\[[^\]]*'products@web'~u", $sb) === 1,
   'Loi vao nam trong NHOM Quan ly website');
ok(preg_match("~'Hàng hoá'\s*=>\s*\[[^\]]*'products'~u", $sb) === 1,
   'Hang hoa van o nhom Hang hoa (khong nhac khoi do)');

ok(preg_match('~Route::(get|post)\([^)]*(san-pham-website|products-web|sanpham)~', $web) !== 1,
   'KHONG co duong dan moi nao cho san pham website (cung duong dan, cung module)');

/* DANH MUC KHONG CO MAT Ô QUAN LY WEBSITE — khach chot 12/11/2026. */
ok(preg_match("~'Quản lý website'\s*=>\s*\[[^\]]*part-categories~u", $sb) !== 1,
   'Danh muc hang hoa KHONG co trong nhom Quan ly website (khach: "ko hop ly")');
ok(strpos($sb, "'part-categories@web'") === false, 'Khong con loi vao ao cho danh muc');
$pcc = file_get_contents($goc . 'app/controllers/admin/Partcategories.php');
ok(strpos($pcc, 'ngu_canh_website') === false, 'Man Danh muc khong con ngu canh website (da go het)');

ok(strpos($prod, 'ngu_canh_website()') !== false, 'Products dung ngu canh website');
ok(strpos($prod, 'self::TAB_WEB') !== false && preg_match('~ngu_canh_website\(\)\)\s*\$tab\s*=\s*self::TAB_WEB~', $prod) === 1,
   'Ngu canh website KHOA tab "san pham website" (khong de nguoi dung bam sang tab khac)');

/* Moi lan chuyen huong ve man san pham phai giu ngu canh, khong thi them xong,
   sua xong, xoa xong la ve man Hang hoa thuong — dung cai khach che. */
preg_match_all("~redirect\('admin/' \. \\\$this->routeBase([^;]*)\);~", $prod, $rd);
$khongGiu = [];
foreach ($rd[1] as $i => $tail){
    if (strpos($tail, '/import') !== false) continue;
    if (strpos($tail, 'ngu_canh_qs()') === false) $khongGiu[] = trim($tail);
}
ok(empty($khongGiu), 'Moi chuyen huong ve man san pham deu giu ngu canh website',
   empty($khongGiu) ? count($rd[1]) . ' cho' : 'thieu o: ' . implode(' | ', $khongGiu));

ok(strpos($lst, "ngu_canh_website()") !== false, 'Danh sach co nhanh rieng cho ngu canh website');
ok(strpos($lst, 'name="nguon"') !== false, 'O tim kiem giu ngu canh (khong thi tim xong ve man thuong)');

// ===========================================================================
section('Chay that: loc, mac dinh, o lai ngu canh');

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
$token = function($body){ return preg_match('~name="_token"[^>]*value="([^"]+)"~', $body, $m) ? $m[1] : ''; };

/* CHI SOI THAN BANG, KHONG DO CA TRANG. Trang con doi lai tu khoa o cho khac
   (o tim kiem, thong bao), nen do ca trang cho ket qua SAI THEO CA HAI HUONG:
   "khong thay hang" co the sai vi tu khoa duoc doi lai, "thay hang" cung vay.
   Lan dau test nay do ca trang va bao mot loi gia ("bo loc khong chay") — thuc
   ra bo loc chay dung, tu khoa moi la thu bi nhin nham. */
$trongBang = function($body, $ten){
    if (!preg_match('~<tbody>(.*?)</tbody>~s', $body, $m)) return false;
    return mb_strpos($m[1], $ten) !== false;
};

$jar = tempnam(sys_get_temp_dir(), 'nc');
$r = $http('GET', "$base/dang-nhap", $jar);
if ($r['code'] === 0){ echo "\n[SKIP] Apache khong chay (localhost:88).\n"; @unlink($jar); exit(summary()); }
$http('POST', "$base/dang-nhap", $jar, ['_token' => $token($r['body']), 'email' => 'chugara.sg@gara-mau.test', 'password' => 'Gara@2026']);

require_once $goc . 'vendor/autoload.php';
require_once $goc . 'config.php';
try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){ echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; @unlink($jar); exit(summary()); }

$MA = 'ZZ-NGUCANH-' . random_int(1000, 9999);
$TEN = 'Hang thu ngu canh ' . random_int(1000, 9999);

try {
    // ----------------------------------------------------- menu: chi mot lối sáng
    $r = $http('GET', "$base/admin/products?nguon=web", $jar);
    ok($r['code'] === 200, 'Mo duoc Quan ly website › San pham', 'HTTP ' . $r['code']);

    $sang = function($body){
        preg_match_all('~<a href="([^"]+)"\s+class="adm-nav__link is-active"~', $body, $m);
        return $m[1];
    };
    $lSang = array_values(array_filter($sang($r['body']), function($h){ return strpos($h, '/admin/products') !== false; }));
    ok(count($lSang) === 1 && strpos($lSang[0], 'nguon=web') !== false,
       'Dang o ngu canh website: CHI loi vao website sang (khong sang ca hai nhu ban loi tat)',
       json_encode($lSang));

    $r2 = $http('GET', "$base/admin/products", $jar);
    $lSang2 = array_values(array_filter($sang($r2['body']), function($h){ return strpos($h, '/admin/products') !== false; }));
    ok(count($lSang2) === 1 && strpos($lSang2[0], 'nguon=web') === false,
       'Dang o man Hang hoa thuong: CHI loi Hang hoa sang',
       json_encode($lSang2));

    ok(strpos($r['body'], 'nav-tabs') === false, 'Ngu canh website: khong con hang tab Phu tung / Thiet bi / Dich vu');
    ok(strpos($r['body'], 'hiển thị trên website') !== false, 'Co dong noi ro dang xem hang hien thi tren website');
    ok(strpos($r2['body'], 'nav-tabs') !== false, 'Man Hang hoa thuong van co du cac tab (khong bi anh huong)');

    // --------------------------------------------------- chi hang hien thi tren web
    /* TU DUNG MOT HANG AN (show_on_web = 0), khong trong vao du lieu co san: lan
       dau em chon mau la "Cong tho thay dau (SG)" — mot DICH VU, ma man Hang hoa
       thuong khong liet ke dich vu (co man rieng) nen cap khang dinh gay vi mau
       sai chu khong phai vi code sai. Gara 2 vi gara dang dang nhap. */
    $MA_AN  = 'ZZ-NGUCANH-AN-' . random_int(1000, 9999);
    $TEN_AN = 'Hang an tren web ' . random_int(1000, 9999);
    $pdo->prepare("INSERT INTO parts (code, name, slug, price, status, show_on_web, item_type, garage_id)
                   VALUES (?, ?, ?, 1000, 1, 0, 'part', 2)")->execute([$MA_AN, $TEN_AN, 'zz-' . strtolower($MA_AN)]);

    $rw = $http('GET', "$base/admin/products?nguon=web&keyword=" . urlencode($TEN_AN), $jar);
    $rt = $http('GET', "$base/admin/products?keyword=" . urlencode($TEN_AN), $jar);
    ok($trongBang($rt['body'], $TEN_AN), 'Man Hang hoa thuong THAY hang dang an tren web', $TEN_AN);
    ok(!$trongBang($rw['body'], $TEN_AN), 'Ngu canh website KHONG thay hang dang an tren web', $TEN_AN);

    $MA_HIEN  = 'ZZ-NGUCANH-HIEN-' . random_int(1000, 9999);
    $TEN_HIEN = 'Hang hien tren web ' . random_int(1000, 9999);
    $pdo->prepare("INSERT INTO parts (code, name, slug, price, status, show_on_web, item_type, garage_id)
                   VALUES (?, ?, ?, 1000, 1, 1, 'part', 2)")->execute([$MA_HIEN, $TEN_HIEN, 'zz-' . strtolower($MA_HIEN)]);
    $rw = $http('GET', "$base/admin/products?nguon=web&keyword=" . urlencode($TEN_HIEN), $jar);
    ok($trongBang($rw['body'], $TEN_HIEN), 'Ngu canh website THAY hang dang hien tren web (doi chung, de loc khong loc thua)', $TEN_HIEN);

    // ----------------------------------------------------- them moi mac dinh hien thi
    $ra = $http('GET', "$base/admin/products/add?nguon=web", $jar);
    ok($ra['code'] === 200, 'Mo duoc form Them hang trong ngu canh website', 'HTTP ' . $ra['code']);
    ok(preg_match('~name="show_on_web"[^>]*value="1"[^>]*checked~', $ra['body']) === 1,
       'O "Hien thi website" MAC DINH DUOC TICH khi them tu Quan ly website');
    ok(strpos($ra['body'], 'name="nguon" value="web"') !== false, 'Form them mang theo ngu canh (o an nguon=web)');
    ok(preg_match('~href="[^"]*/admin/products\?nguon=web"[^>]*>\s*<i[^>]*></i>\s*Quay lại~u', $ra['body']) === 1,
       'Nut Quay lai ve dung ngu canh website');

    $tok = $token($ra['body']);
    $rp = $http('POST', "$base/admin/products/add?nguon=web", $jar, [
        '_token' => $tok, 'nguon' => 'web', 'code' => $MA, 'name' => $TEN, 'price' => '123000',
        'show_on_web' => '1', 'status' => '1', 'item_type' => 'part']);
    ok($rp['code'] === 302 && strpos($rp['loc'], 'products/edit/') !== false && strpos($rp['loc'], 'nguon=web') !== false,
       'Them xong: chuyen sang trang Sua VA o lai ngu canh website',
       'HTTP ' . $rp['code'] . ' ' . $rp['loc']);

    $st = $pdo->prepare('SELECT id, show_on_web, garage_id FROM parts WHERE code = ?');
    $st->execute([$MA]);
    $moi = $st->fetch(PDO::FETCH_ASSOC);
    ok(!empty($moi) && (int) $moi['show_on_web'] === 1, 'Hang vua them LA hang hien thi tren web (show_on_web = 1)');

    if (!empty($moi)){
        $rw = $http('GET', "$base/admin/products?nguon=web&keyword=" . urlencode($TEN), $jar);
        ok($trongBang($rw['body'], $TEN), 'Hang vua them XUAT HIEN ngay trong danh sach website');

        // ------------------------------- bo tich hien thi -> bien khoi danh sach website
        $re = $http('GET', "$base/admin/products/edit/" . (int) $moi['id'] . '?nguon=web', $jar);
        ok($re['code'] === 200 && strpos($re['body'], 'name="nguon" value="web"') !== false,
           'Form Sua trong ngu canh website cung mang theo ngu canh');
        $rs = $http('POST', "$base/admin/products/edit/" . (int) $moi['id'] . '?nguon=web', $jar, [
            '_token' => $token($re['body']), 'nguon' => 'web', 'code' => $MA, 'name' => $TEN, 'price' => '123000',
            'status' => '1', 'item_type' => 'part']);   // KHONG gui show_on_web = bo tich
        ok($rs['code'] === 302 && strpos($rs['loc'], 'nguon=web') !== false && strpos($rs['loc'], '/edit/') === false,
           'Sua xong: ve DANH SACH (khong phai quay lai form do bi tu choi) va o lai ngu canh website',
           'HTTP ' . $rs['code'] . ' ' . $rs['loc']);
        /* Mot lan sua bi validation tu choi cung la 302 chuyen huong, nen chi
           kiem Location khong chung minh duoc gi — doc thang CSDL. */
        $st = $pdo->prepare('SELECT show_on_web FROM parts WHERE id = ?');
        $st->execute([(int) $moi['id']]);
        ok((string) $st->fetchColumn() === '0', 'Lan sua da THAT SU luu: show_on_web trong CSDL = 0');

        $rw2 = $http('GET', "$base/admin/products?nguon=web&keyword=" . urlencode($TEN), $jar);
        $rt2 = $http('GET', "$base/admin/products?keyword=" . urlencode($TEN), $jar);
        ok(!$trongBang($rw2['body'], $TEN), 'Bo tich Hien thi website -> BIEN KHOI danh sach website');
        ok($trongBang($rt2['body'], $TEN), '...nhung van con o man Hang hoa thuong (khong bi xoa)');
    }
} finally {
    // don sach hang thu, du test dung o dau
    $pdo->prepare('DELETE FROM part_images WHERE part_id IN (SELECT id FROM parts WHERE code = ?)')->execute([$MA]);
    $pdo->prepare('DELETE FROM stock_reservations WHERE part_id IN (SELECT id FROM parts WHERE code = ?)')->execute([$MA]);
    $pdo->prepare('DELETE FROM parts WHERE code = ?')->execute([$MA]);
    $pdo->prepare('DELETE FROM parts WHERE code LIKE ?')->execute(['ZZ-NGUCANH-%']);
}
$st = $pdo->prepare('SELECT COUNT(*) FROM parts WHERE code = ?');
$st->execute([$MA]);
ok((int) $st->fetchColumn() === 0, 'Da don sach hang thu');

@unlink($jar);
exit(summary());

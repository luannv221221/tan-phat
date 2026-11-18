<?php
/**
 * NUT "GUI THANH TOAN" O DANH SACH HOA DON — anh yeu cau 18/11/2026:
 * "cho anh cai nut gui link qrcode luon khong phai vao in" (kem anh danh sach
 * hoa don co cot QR + nut gui).
 *
 * Gom ba phan:
 *   1. Cot QR nho + nut "Gui" tren danh sach hoa don (khong phai mo trang in).
 *   2. Link thanh toan CONG KHAI /thanh-toan/<ma> — khach mo khong can dang
 *      nhap, thay ma QR + so TK + so tien + noi dung. Ma la chuoi ngau nhien
 *      32 ky tu hex (128 bit) luu o `sales_invoices`.`pay_token`, KHONG phai id:
 *      id tang dan, doan id la xem duoc hoa don cua nguoi khac.
 *   3. Tao link qua tuyen admin co phan quyen: gara A khong lay duoc link
 *      hoa don cua gara B; chua dang nhap thi khong lay duoc gi.
 *
 * Trang cong khai doc tai khoan cua GARA LAP HOA DON (cau_hinh_in_an theo
 * garage_id cua hoa don), khong theo ten mien dang mo — tien phai ve dung gara.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

$goc = __DIR__ . '/../';

$tlv = function($s){
    $o = []; $i = 0; $n = strlen((string) $s);
    while ($i + 4 <= $n){
        $len = substr($s, $i + 2, 2);
        if (!ctype_digit($len)) return null;
        $o[substr($s, $i, 2)] = substr($s, $i + 4, (int) $len);
        $i += 4 + (int) $len;
    }
    return $i === $n ? $o : null;
};
$crcDung = function($s){
    if (strlen((string) $s) < 8 || substr($s, -8, 4) !== '6304') return false;
    return \App\core\VietQr::crc16(substr($s, 0, -4)) === substr($s, -4);
};
/* Moi chuoi VietQR trong trang, kem noi dung (62/08) de tim dung dong. */
$cacQr = function($html) use ($tlv){
    preg_match_all('~data-vietqr="([^"]+)"~', (string) $html, $m);
    $o = [];
    foreach ($m[1] as $q){
        $q = html_entity_decode($q, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = $tlv($q);
        $t62 = is_array($t) && isset($t['62']) ? $tlv($t['62']) : null;
        $o[] = ['chuoi' => $q, 't' => $t, 'noi_dung' => is_array($t62) && isset($t62['08']) ? $t62['08'] : null];
    }
    return $o;
};

// ===========================================================================
section('Nguon');

$mig = glob($goc . 'database/migrations/*_link_thanh_toan_hoa_don.php');
ok(count($mig) === 1, 'Co migration them cot `pay_token` (…_link_thanh_toan_hoa_don.php)');
$routes = file_get_contents($goc . 'routes/web.php');
ok(strpos($routes, "Route::get('thanh-toan/([a-f0-9]{32})'") !== false, 'Tuyen cong khai /thanh-toan/<32 hex>');
ok(strpos($routes, "Route::get('sales-invoices/link-thanh-toan/(\\d+)'") !== false,
   'Tuyen admin lay link: sales-invoices/link-thanh-toan/<id> (GET -> tu qua Auth + quyen xem)');
$ds = file_get_contents($goc . 'app/views/admin/sales-invoices/lists.php');
ok(strpos($ds, 'data-vietqr') !== false, 'Danh sach hoa don co cot ma QR');
ok(strpos($ds, 'link-thanh-toan') !== false, 'Danh sach hoa don co nut Gui thanh toan');
ok(is_file($goc . 'public/assets/js/vietqr.js'), 'Ma JS ve QR + gui link nam o MOT file tinh (public/assets/js/vietqr.js)');

// ===========================================================================
section('CSDL');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){ echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary()); }

$cotCo = in_array('pay_token', $pdo->query('SHOW COLUMNS FROM sales_invoices')->fetchAll(PDO::FETCH_COLUMN), true);
ok($cotCo, 'Bang `sales_invoices` co cot `pay_token` (chay migration)');
if (!$cotCo){ exit(summary()); }
$idx = $pdo->query("SHOW INDEX FROM sales_invoices WHERE Column_name = 'pay_token'")->fetchAll(PDO::FETCH_ASSOC);
ok(!empty($idx) && (int) $idx[0]['Non_unique'] === 0, '`pay_token` la UNIQUE (hai hoa don khong the chung mot link)');

$so = function($sql, $b = []) use ($pdo){ $st = $pdo->prepare($sql); $st->execute($b); return $st->fetchColumn(); };
$G2 = (int) $so("SELECT garage_id FROM users WHERE email = 'chugara.sg@gara-mau.test'");
$G3 = (int) $so("SELECT garage_id FROM users WHERE email = 'chugara.dn@gara-mau.test'");
if (!$G2 || !$G3 || $G2 === $G3){ echo "\n[SKIP] Thieu tai khoan thu chugara.sg / chugara.dn tren may local.\n"; exit(summary()); }

/* Chup cau hinh ngan hang that cua gara 2, gara 3 — tra lai khi xong. */
$truoc = $pdo->query("SELECT garage_id, skey, svalue, update_at FROM garage_settings
                       WHERE garage_id IN ($G2, $G3) AND skey LIKE 'bank%'")->fetchAll(PDO::FETCH_ASSOC);
$jars = [];
$traLai = function() use ($pdo, $G2, $G3, $truoc, &$jars){
    try {
        $pdo->exec("DELETE FROM garage_settings WHERE garage_id IN ($G2, $G3) AND skey LIKE 'bank%'");
        $st = $pdo->prepare("INSERT INTO garage_settings (garage_id, skey, svalue, update_at) VALUES (?, ?, ?, ?)");
        foreach ($truoc as $r) $st->execute([$r['garage_id'], $r['skey'], $r['svalue'], $r['update_at']]);
        $pdo->exec("DELETE FROM sales_invoices WHERE invoice_no LIKE 'ZZLK-%'");
    } catch (\Throwable $e){ echo "  (tra lai loi: " . $e->getMessage() . ")\n"; }
    foreach ($jars as $j) @unlink($j);
};
register_shutdown_function($traLai);
$pdo->exec("DELETE FROM sales_invoices WHERE invoice_no LIKE 'ZZLK-%'");

$stk2 = '1903' . random_int(10000000, 99999999);
$pdo->exec("DELETE FROM garage_settings WHERE garage_id IN ($G2, $G3) AND skey LIKE 'bank%'");
$now = date('Y-m-d H:i:s');
$st = $pdo->prepare("INSERT INTO garage_settings (garage_id, skey, svalue, update_at) VALUES (?, ?, ?, ?)");
$st->execute([$G2, 'bank_bin',     '970407',          $now]);
$st->execute([$G2, 'bank_name',    'Techcombank',     $now]);
$st->execute([$G2, 'bank_account', $stk2,             $now]);
$st->execute([$G2, 'bank_holder',  'ZZLK GARA HAI',   $now]);

/* Khach cua gara 2 co so dien thoai -> nut Zalo / SMS dien san so. */
$khach2 = $pdo->prepare("SELECT id, phone FROM partners WHERE garage_id = ? AND phone IS NOT NULL AND phone <> '' ORDER BY id LIMIT 1");
$khach2->execute([$G2]);
$khach2 = $khach2->fetch(PDO::FETCH_ASSOC) ?: null;

/* Kho cua chinh gara: danh sach hoa don JOIN `warehouses` (khong phai LEFT JOIN),
   hoa don khong kho thi khong hien tren danh sach. */
$taoHd = function($g, $soHd, $tong, $khachId = null) use ($pdo){
    $kho = $pdo->prepare('SELECT id FROM warehouses WHERE garage_id = ? ORDER BY id LIMIT 1');
    $kho->execute([$g]);
    $pdo->prepare("INSERT INTO sales_invoices (invoice_no, customer_id, warehouse_id, invoice_date, vat_rate, subtotal, tax_amount, total_amount,
                   status, garage_id, bien_so, create_at) VALUES (?, ?, ?, CURDATE(), 0, ?, 0, ?, 0, ?, '51K-123.45', NOW())")
        ->execute([$soHd, $khachId, ($k = $kho->fetchColumn()) ? (int) $k : null, $tong, $tong, $g]);
    return (int) $pdo->lastInsertId();
};
$hd2 = $taoHd($G2, 'ZZLK-0002', 1954800, $khach2 ? (int) $khach2['id'] : null);
$hd3 = $taoHd($G3, 'ZZLK-0003', 378000);

// ---------------------------------------------------------------------------
section('Model: ma thanh toan ngau nhien, rieng tung gara');

\App\core\Load::model('SalesInvoicesModel');
$m = new SalesInvoicesModel();
$trong = function($g, $fn){ return \App\core\Model::trongGara($g, $fn); };

$ma2 = $trong($G2, function() use ($m, $hd2){ return $m->maThanhToan($hd2); });
ok(is_string($ma2) && preg_match('~^[a-f0-9]{32}$~', $ma2) === 1, 'Gara 2 lay duoc ma cho hoa don cua minh: 32 ky tu hex', var_export($ma2, true));
ok($trong($G2, function() use ($m, $hd2){ return $m->maThanhToan($hd2); }) === $ma2, 'Goi lai -> CUNG ma (link da gui khong chet)');
ok($trong($G3, function() use ($m, $hd2){ return $m->maThanhToan($hd2); }) === null, 'Gara 3 KHONG lay duoc ma cua hoa don gara 2');
$ma3 = $trong($G3, function() use ($m, $hd3){ return $m->maThanhToan($hd3); });
ok(is_string($ma3) && $ma3 !== $ma2, 'Hai hoa don -> hai ma khac nhau');

$tim = $m->theoMaThanhToan($ma2);
ok(!empty($tim) && (int) $tim['id'] === $hd2 && (int) $tim['garage_id'] === $G2, 'Tra nguoc tu ma ra dung hoa don, dung gara (khong phu thuoc gara dang mo)');
foreach (['' => 'rong', 'abc' => 'qua ngan', str_repeat('0', 32) => '32 so 0 (khong co that)',
          "' OR '1'='1" => 'chuoi tiem SQL', strtoupper($ma2) => 'viet HOA (ma chi chu thuong)'] as $sai => $ten){
    ok($m->theoMaThanhToan($sai) === null, "Ma sai ($ten) -> khong ra hoa don nao");
}

// ===========================================================================
section('HTTP — danh sach hoa don + lay link (co dang nhap)');

if (!function_exists('curl_init')){ echo "\n[SKIP] PHP khong co curl.\n"; exit(summary()); }
$base = web_goc();
$http = function($url, $jar, $host = null){
    $ch = curl_init($url);
    $o = [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false,
          CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 30];
    if ($host !== null) $o[CURLOPT_HTTPHEADER] = ["Host: $host"];
    curl_setopt_array($ch, $o);
    $raw = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    $hdr  = $raw === false ? '' : substr($raw, 0, (int) $info['header_size']);
    $body = $raw === false ? '' : substr($raw, (int) $info['header_size']);
    return ['code' => (int) $info['http_code'], 'hdr' => $hdr, 'body' => $body,
            'text' => html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8')];
};
$post = function($url, $jar, $data){
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 30]);
    curl_exec($ch); curl_close($ch);
};
$moiJar = function() use (&$jars){ $j = tempnam(sys_get_temp_dir(), 'zzlk'); $jars[] = $j; return $j; };
$dangNhap = function($email) use ($http, $post, $base, $moiJar){
    $jar = $moiJar();
    $r = $http("$base/dang-nhap", $jar);
    if ($r['code'] === 0) return null;
    $tk = preg_match('~name="_token"[^>]*value="([^"]+)"~', $r['body'], $mm) ? $mm[1] : '';
    $post("$base/dang-nhap", $jar, ['_token' => $tk, 'email' => $email, 'password' => 'Gara@2026']);
    return $jar;
};

$jar2 = $dangNhap('chugara.sg@gara-mau.test');
if ($jar2 === null){ echo "\n[SKIP] Apache khong chay (localhost:88).\n"; exit(summary()); }
$jar3 = $dangNhap('chugara.dn@gara-mau.test');

$ds2 = $http("$base/admin/sales-invoices", $jar2);
ok($ds2['code'] === 200, 'Gara 2 mo duoc danh sach hoa don', 'HTTP ' . $ds2['code']);
$dong = null;
foreach ($cacQr($ds2['body']) as $q){ if ($q['noi_dung'] === 'ZZLK-0002') $dong = $q; }
ok($dong !== null && $crcDung($dong['chuoi']), 'Dong ZZLK-0002 co ma QR nho, CRC dung');
ok($dong !== null && $dong['t']['54'] === '1954800', 'Ma QR o danh sach mang dung tong tien 1.954.800');
ok(strpos($ds2['body'], 'data-link-thanh-toan="' . $hd2 . '"') !== false, 'Dong ZZLK-0002 co nut Gui thanh toan');
ok(strpos($ds2['body'], 'public/assets/js/vietqr.js') !== false && strpos($ds2['body'], 'qrcode-generator/1.4.4/qrcode.min.js') !== false,
   'Danh sach nap thu vien ve QR (cdnjs 1.4.4) + vietqr.js');

$ds3 = $http("$base/admin/sales-invoices", $jar3);
ok($ds3['code'] === 200 && empty($cacQr($ds3['body'])), 'Gara 3 (chua khai tai khoan): danh sach KHONG co ma QR nao');
ok(strpos($ds3['body'], 'admin/settings') !== false && mb_strpos($ds3['text'], 'Khai tài khoản') !== false,
   'Gara 3: cot thanh toan chi duong sang Cau hinh de khai tai khoan');
ok(strpos($ds3['text'], $stk2) === false, 'Danh sach gara 3 khong lo so TK gara 2');

$j = $http("$base/admin/sales-invoices/link-thanh-toan/$hd2", $jar2);
$jj = json_decode($j['body'], true);
ok($j['code'] === 200 && is_array($jj) && !empty($jj['link']), 'Gara 2 lay duoc link thanh toan (JSON)', 'HTTP ' . $j['code'] . ' ' . substr($j['body'], 0, 200));
if (is_array($jj) && !empty($jj['link'])){
    ok(substr($jj['link'], -strlen("/thanh-toan/$ma2")) === "/thanh-toan/$ma2", 'Link tro toi /thanh-toan/<ma cua hoa don>', $jj['link']);
    ok(mb_strpos($jj['tinNhan'], 'ZZLK-0002') !== false && mb_strpos($jj['tinNhan'], '1.954.800') !== false
       && mb_strpos($jj['tinNhan'], $jj['link']) !== false, 'Tin nhan soan san: so hoa don + so tien + link');
    ok(isset($jj['qr']['chuoi']) && $crcDung($jj['qr']['chuoi']), 'JSON kem chuoi QR dung CRC (de ve to trong hop thoai)');
    if ($khach2){
        ok(isset($jj['sdt']) && $jj['sdt'] === preg_replace('~[^0-9+]~', '', $khach2['phone']), 'JSON kem so dien thoai khach (nut Zalo / SMS)');
    }
}

$j3 = $http("$base/admin/sales-invoices/link-thanh-toan/$hd2", $jar3);
ok($j3['code'] !== 200 && strpos($j3['body'], $ma2) === false, 'Gara 3 KHONG lay duoc link hoa don gara 2', 'HTTP ' . $j3['code']);
$j33 = $http("$base/admin/sales-invoices/link-thanh-toan/$hd3", $jar3);
$jj33 = json_decode($j33['body'], true);
ok($j33['code'] !== 200 && is_array($jj33) && !empty($jj33['error']), 'Gara 3 chua khai tai khoan: tu choi tao link, kem loi noi ro', substr($j33['body'], 0, 200));

$anon = $moiJar();
$ja = $http("$base/admin/sales-invoices/link-thanh-toan/$hd2", $anon);
ok($ja['code'] !== 200 && strpos($ja['body'], $ma2) === false, 'Chua dang nhap: khong lay duoc link', 'HTTP ' . $ja['code']);

// ===========================================================================
section('HTTP — trang thanh toan cong khai (khach mo, khong dang nhap)');

$p = $http("$base/thanh-toan/$ma2", $moiJar());
ok($p['code'] === 200, 'Khach mo link (chua dang nhap) -> 200', 'HTTP ' . $p['code']);
$q = $cacQr($p['body']);
ok(count($q) === 1 && $crcDung($q[0]['chuoi']), 'Co dung MOT ma QR, CRC dung');
if (count($q) === 1){
    ok($q[0]['t']['54'] === '1954800', 'QR: so tien = tong hoa don');
    ok($q[0]['noi_dung'] === 'ZZLK-0002', 'QR: noi dung = so hoa don');
    $t38 = $tlv($q[0]['t']['38']); $t3801 = is_array($t38) ? $tlv($t38['01']) : null;
    ok(is_array($t3801) && $t3801['00'] === '970407' && $t3801['01'] === $stk2, 'QR: vao tai khoan gara 2');
}
foreach (['Techcombank', $stk2, 'ZZLK GARA HAI', '1.954.800', 'ZZLK-0002'] as $chu){
    ok(mb_strpos($p['text'], $chu) !== false, "Trang thanh toan ghi: $chu");
}
ok(stripos($p['body'], 'noindex') !== false, 'Trang thanh toan co noindex (khong len Google)');
ok(stripos($p['hdr'], 'no-store') !== false, 'Trang thanh toan khong cho luu dem (Cache-Control: no-store)');
ok(stripos($p['body'], 'name="referrer" content="no-referrer"') !== false, 'Khong de lo link (co ma) qua Referer khi nap thu vien ngoai');
ok(strpos($p['body'], 'adm-sidebar') === false && strpos($p['body'], '/admin/') === false, 'Khong lo giao dien / duong dan quan tri');
if ($khach2) ok(strpos($p['text'], $khach2['phone']) === false, 'Khong in so dien thoai khach (link co the bi chuyen tiep)');

$sai = $http("$base/thanh-toan/" . bin2hex(random_bytes(16)), $moiJar());
ok($sai['code'] === 404 && empty($cacQr($sai['body'])), 'Ma bia -> 404, khong ra gi', 'HTTP ' . $sai['code']);

/* Mo link gara 2 tren ten mien cua gara 3: tien van phai ve gara 2. */
$khacMien = $http("$base/thanh-toan/$ma2", $moiJar(), 'dmdn.localhost');
$qk = $cacQr($khacMien['body']);
$t38 = count($qk) === 1 ? $tlv($qk[0]['t']['38']) : null; $t3801 = is_array($t38) ? $tlv($t38['01']) : null;
ok(is_array($t3801) && $t3801['01'] === $stk2, 'Mo tren ten mien gara khac: van chuyen vao tai khoan gara LAP HOA DON');

$p3 = $http("$base/thanh-toan/$ma3", $moiJar());
$dt3 = (string) $so("SELECT phone FROM garages WHERE id = $G3");
ok($p3['code'] === 200 && empty($cacQr($p3['body'])), 'Hoa don gara chua khai tai khoan: trang mo duoc, KHONG co QR');
ok(mb_strpos($p3['text'], 'liên hệ') !== false && ($dt3 === '' || strpos($p3['text'], $dt3) !== false),
   'Thay vao do: loi nhac lien he gara + so dien thoai gara', $dt3);
ok(strpos($p3['text'], $stk2) === false, 'Khong lo so TK cua gara khac');

/* Gara 2 thoi khai tai khoan -> link cu khong con in so TK cu. */
$pdo->exec("DELETE FROM garage_settings WHERE garage_id = $G2 AND skey LIKE 'bank%'");
$p2b = $http("$base/thanh-toan/$ma2", $moiJar());
ok($p2b['code'] === 200 && empty($cacQr($p2b['body'])) && strpos($p2b['text'], $stk2) === false,
   'Gara go tai khoan: link da gui doc lai ngay, khong con QR / so TK cu');

exit(summary());

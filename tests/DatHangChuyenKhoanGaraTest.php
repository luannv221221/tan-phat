<?php
/**
 * TRANG "DAT HANG THANH CONG" PHAI HIEN TAI KHOAN CUA GARA DANG BAN.
 *
 * Phat hien 16/11/2026 (luc lam ma QR tren hoa don), anh bao "sua luon":
 * Cart::orderDone() doc `SettingsModel->map()` — Cau hinh CHUNG, tuc la cua Tan
 * Phat. Khach dat hang tren website cua gara B (dmdn.<ten mien>), chon chuyen
 * khoan, thi trang hoan tat bao chuyen tien vao tai khoan Tan Phat. Khach lam
 * dung theo huong dan la chuyen nham nguoi.
 *
 * Test nay giu:
 *   1. Web gara nao hien tai khoan cua chinh gara do (nhan gara theo ten mien).
 *   2. Gara chua khai tai khoan: KHONG hien so TK cua ai ca, thay bang loi nhac
 *      lien he gara (kem so dien thoai cua gara).
 *   3. Gara da khai du: co ma QR VietQR dung so tien + ma don, cung nguon voi
 *      ma QR tren hoa don (App\core\VietQr).
 *   4. Web cua Tan Phat van hien tai khoan cua Tan Phat nhu truoc.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

$goc = __DIR__ . '/../';

// ===========================================================================
section('Nguon: orderDone() khong doc Cau hinh chung cho tai khoan');

$cart = codeOnly($goc . 'app/controllers/Cart.php');
$a = strpos($cart, 'function orderDone()');
$than = $a === false ? '' : substr($cart, $a);
ok($than !== '', 'Tach duoc than ham Cart::orderDone()');
ok(strpos($than, '__settings->map()') === false,
   'orderDone() KHONG con lay `SettingsModel->map()` (Cau hinh chung = tai khoan Tan Phat)');
ok(strpos($than, 'cau_hinh_in_an(') !== false,
   'orderDone() lay tai khoan qua cau_hinh_in_an() — cung nguon voi hoa don, khong thua huong Tan Phat');

$view = file_get_contents($goc . 'app/views/storefront/order_done.php');
ok(strpos($view, "\$settings['bank_account']") === false, 'order_done.php khong con doc $settings[\'bank_account\']');
ok(strpos($view, 'data-vietqr') !== false, 'order_done.php co cho ve ma QR (data-vietqr)');

// ===========================================================================
section('Chay that: dat hang chuyen khoan tren web cua tung gara');

if (!function_exists('curl_init')){ echo "\n[SKIP] PHP khong co curl.\n"; exit(summary()); }

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){ echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary()); }

$base = web_goc();
$garaCuaHost = function($host) use ($pdo){
    $st = $pdo->prepare('SELECT garage_id FROM garage_domains WHERE host = ?');
    $st->execute([$host]);
    return (int) $st->fetchColumn();
};
$G2 = $garaCuaHost('dmsg.localhost');
$G3 = $garaCuaHost('dmdn.localhost');
$G1 = $garaCuaHost('tp01.localhost');
if (!$G2 || !$G3 || !$G1){ echo "\n[SKIP] Chua khai ten mien dmsg/dmdn/tp01.localhost tren may.\n"; exit(summary()); }

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

/* Chup cau hinh ngan hang that, tra lai y nguyen khi xong (ke ca khi chet giua chung). */
$truoc = [
    'gara'  => $pdo->query("SELECT garage_id, skey, svalue, update_at FROM garage_settings
                             WHERE garage_id IN ($G1, $G2, $G3) AND skey LIKE 'bank%'")->fetchAll(PDO::FETCH_ASSOC),
    'chung' => $pdo->query("SELECT skey, svalue FROM site_settings WHERE skey LIKE 'bank%'")->fetchAll(PDO::FETCH_KEY_PAIR),
];
$maxDon = (int) $pdo->query('SELECT COALESCE(MAX(id), 0) FROM orders')->fetchColumn();
$jars = [];
$traLai = function() use ($pdo, $G1, $G2, $G3, $truoc, $maxDon, &$jars){
    try {
        $pdo->exec("DELETE FROM garage_settings WHERE garage_id IN ($G1, $G2, $G3) AND skey LIKE 'bank%'");
        $st = $pdo->prepare("INSERT INTO garage_settings (garage_id, skey, svalue, update_at) VALUES (?, ?, ?, ?)");
        foreach ($truoc['gara'] as $r) $st->execute([$r['garage_id'], $r['skey'], $r['svalue'], $r['update_at']]);
        $pdo->exec("DELETE FROM site_settings WHERE skey LIKE 'bank%'");
        $st = $pdo->prepare("INSERT INTO site_settings (skey, svalue) VALUES (?, ?)");
        foreach ($truoc['chung'] as $k => $v) $st->execute([$k, $v]);
        /* Don thu — chi nhung don tao SAU moc va mang ten khach thu. */
        $ids = $pdo->query("SELECT id FROM orders WHERE id > $maxDon AND customer_name = 'ZZQR Khach Thu'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($ids as $id){
            $pdo->prepare('DELETE FROM order_items WHERE order_id = ?')->execute([(int) $id]);
            $pdo->prepare('DELETE FROM stock_reservations WHERE order_id = ?')->execute([(int) $id]);
            $pdo->prepare('DELETE FROM orders WHERE id = ?')->execute([(int) $id]);
        }
    } catch (\Throwable $e){ echo "  (tra lai loi: " . $e->getMessage() . ")\n"; }
    foreach ($jars as $j) @unlink($j);
};
register_shutdown_function($traLai);

/* Dung san: Tan Phat da chon Vietcombank (so TK cua Tan Phat giu nguyen nhu
   tren may), gara 2 khai tai khoan RIENG, gara 3 CHUA khai gi. */
$stkTP = (string) $pdo->query("SELECT svalue FROM site_settings WHERE skey = 'bank_account'")->fetchColumn();
if ($stkTP === ''){
    $stkTP = '0011000' . random_int(100000, 999999);
    $pdo->prepare("REPLACE INTO site_settings (skey, svalue) VALUES ('bank_account', ?)")->execute([$stkTP]);
}
$pdo->prepare("REPLACE INTO site_settings (skey, svalue) VALUES ('bank_bin', '970436')")->execute();

$stk2 = '1903' . random_int(10000000, 99999999);
$pdo->exec("DELETE FROM garage_settings WHERE garage_id IN ($G2, $G3) AND skey LIKE 'bank%'");
$now = date('Y-m-d H:i:s');
$st = $pdo->prepare("INSERT INTO garage_settings (garage_id, skey, svalue, update_at) VALUES (?, ?, ?, ?)");
$st->execute([$G2, 'bank_bin',     '970407',               $now]);
$st->execute([$G2, 'bank_name',    'Techcombank',          $now]);
$st->execute([$G2, 'bank_account', $stk2,                  $now]);
$st->execute([$G2, 'bank_holder',  'ZZQR GARA DAT HANG',   $now]);

$units = json_decode((string) file_get_contents($goc . 'public/assets/data/vn-administrative.json'), true);
if (!is_array($units) || empty($units[0]['w'][0])){ echo "\n[SKIP] Khong doc duoc danh sach tinh/phuong.\n"; exit(summary()); }
$tinh = $units[0]; $xa = $tinh['w'][0];

/* Dat mot don chuyen khoan tren web cua $host, tra ve trang "Dat hang thanh
   cong" + dong don vua tao. */
$datHang = function($host, $gara) use ($base, $pdo, $tinh, $xa, &$jars){
    $jar = tempnam(sys_get_temp_dir(), 'zzdh');
    $jars[] = $jar;
    $http = function($url, $post = null) use ($jar, $host){
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => 30, CURLOPT_HTTPHEADER => ["Host: $host"]]);
        if ($post !== null){ curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post)); }
        $r = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $hs   = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        preg_match('~^Location:\s*(.+)$~im', substr((string) $r, 0, $hs), $m);
        $body = substr((string) $r, $hs);
        return ['code' => $code, 'loc' => isset($m[1]) ? trim($m[1]) : '', 'body' => $body,
                'text' => html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8')];
    };
    $tok = function($b){ return preg_match('~name="_token"[^>]*value="([^"]+)"~', $b, $m) ? $m[1] : ''; };

    $r = $http("$base/");
    if ($r['code'] === 0) return null;

    /* Hang nao len web cua gara nao do chiHangLenWeb() quyet — khong doan bang
       SQL, thu that: them vao gio roi xem gio co nhan khong. */
    $st = $pdo->prepare("SELECT id, name FROM parts WHERE status = 1 AND (garage_id IS NULL OR garage_id = ?) ORDER BY id LIMIT 60");
    $st->execute([$gara]);
    $hang = null;
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $uv){
        $http("$base/gio-hang/them", ['_token' => $tok($http("$base/")['body']), 'part_id' => $uv['id'], 'qty' => 1]);
        $g = $http("$base/gio-hang");
        if ($g['code'] === 200 && mb_strpos($g['body'], $uv['name']) !== false){ $hang = $uv; break; }
        $http("$base/gio-hang/xoa/" . (int) $uv['id']);
    }
    if ($hang === null) return ['loi' => 'khong them duoc mat hang nao vao gio'];

    $r = $http("$base/dat-hang");
    $http("$base/dat-hang", ['_token' => $tok($r['body']), 'name' => 'ZZQR Khach Thu', 'phone' => '0366583569',
        'email' => '', 'province_code' => $tinh['c'], 'ward_code' => $xa['c'],
        'address' => 'So 1 duong Thu', 'note' => '', 'payment_method' => 'bank_transfer']);
    $xong = $http("$base/dat-hang/hoan-tat");

    $st = $pdo->prepare("SELECT id, order_no, total_amount, garage_id FROM orders
                          WHERE customer_name = 'ZZQR Khach Thu' AND garage_id = ? ORDER BY id DESC LIMIT 1");
    $st->execute([$gara]);
    return ['trang' => $xong, 'don' => $st->fetch(PDO::FETCH_ASSOC) ?: null];
};
$layQr = function($html){
    return preg_match('~data-vietqr="([^"]+)"~', $html, $m) ? html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8') : null;
};

// ---------------------------------------------------------------------------
section('Web gara 2 (da khai tai khoan rieng)');

$k2 = $datHang('dmsg.localhost', $G2);
if ($k2 === null){ echo "\n[SKIP] Apache khong chay (localhost:88).\n"; exit(summary()); }
ok(empty($k2['loi']) && !empty($k2['don']), 'Dat duoc don chuyen khoan tren web gara 2',
   isset($k2['loi']) ? $k2['loi'] : '');
if (!empty($k2['don'])){
    $p = $k2['trang'];
    ok($p['code'] === 200 && mb_strpos($p['text'], 'Đặt hàng thành công') !== false, 'Mo duoc trang Dat hang thanh cong', 'HTTP ' . $p['code']);
    ok(strpos($p['text'], $stk2) !== false, 'Hien so tai khoan CUA GARA 2');
    ok(mb_strpos($p['text'], 'ZZQR GARA DAT HANG') !== false && mb_strpos($p['text'], 'Techcombank') !== false,
       'Hien chu tai khoan + ngan hang cua gara 2');
    ok(strpos($p['text'], $stkTP) === false, 'KHONG hien so tai khoan cua Tan Phat');

    $q = $layQr($p['body']);
    $t = $tlv($q);
    ok($q !== null && $crcDung($q), 'Co ma QR VietQR, CRC dung', var_export($q, true));
    ok(is_array($t) && $t['54'] === (string) (int) round((float) $k2['don']['total_amount']),
       'QR mang dung tong tien cua don', 'don: ' . $k2['don']['total_amount'] . ' / QR: ' . (is_array($t) && isset($t['54']) ? $t['54'] : '-'));
    $t62 = is_array($t) ? $tlv($t['62']) : null;
    ok(is_array($t62) && $t62['08'] === \App\core\VietQr::noiDung($k2['don']['order_no']),
       'Noi dung QR = ma don', $k2['don']['order_no']);
    $t38 = is_array($t) ? $tlv($t['38']) : null;
    $t3801 = is_array($t38) ? $tlv($t38['01']) : null;
    ok(is_array($t3801) && $t3801['00'] === '970407' && $t3801['01'] === $stk2, 'QR chuyen vao tai khoan gara 2');
    ok(strpos($p['body'], 'qrcode-generator/1.4.4/qrcode.min.js') !== false, 'Nap thu vien ve QR (cdnjs, 1.4.4)');
}

// ---------------------------------------------------------------------------
section('Web gara 3 (CHUA khai tai khoan)');

$k3 = $datHang('dmdn.localhost', $G3);
ok(!empty($k3) && empty($k3['loi']) && !empty($k3['don']), 'Dat duoc don chuyen khoan tren web gara 3',
   isset($k3['loi']) ? $k3['loi'] : '');
if (!empty($k3['don'])){
    $p = $k3['trang'];
    ok($p['code'] === 200 && mb_strpos($p['text'], 'Đặt hàng thành công') !== false, 'Mo duoc trang Dat hang thanh cong', 'HTTP ' . $p['code']);
    ok(strpos($p['text'], $stkTP) === false, 'KHONG hien so tai khoan cua Tan Phat (loi cu)');
    ok(strpos($p['text'], $stk2) === false, 'KHONG hien so tai khoan cua gara 2');
    ok($layQr($p['body']) === null, 'Khong co ma QR');
    /* Cau rieng cua khoi chuyen khoan — khong dung "lien he" suong: trang da co
       san "Chung toi se lien he xac nhan" o cuoi, test se dung oan. */
    ok(mb_strpos($p['text'], 'để hướng dẫn thanh toán') !== false, 'Co loi nhac: gara se lien he de huong dan thanh toan');
    $dt3 = (string) $pdo->query("SELECT phone FROM garages WHERE id = $G3")->fetchColumn();
    ok($dt3 === '' || strpos($p['text'], $dt3) !== false, 'Loi nhac kem so dien thoai CUA GARA 3', $dt3);
    ok(!preg_match('~(Warning|Notice|Fatal error|Deprecated)\b~', $p['body']), 'Khong co loi PHP tren trang');
}

// ---------------------------------------------------------------------------
section('Web Tan Phat (gara tong) — van nhu truoc');

$k1 = $datHang('tp01.localhost', $G1);
ok(!empty($k1) && empty($k1['loi']) && !empty($k1['don']), 'Dat duoc don chuyen khoan tren web Tan Phat',
   isset($k1['loi']) ? $k1['loi'] : '');
if (!empty($k1['don'])){
    $p = $k1['trang'];
    ok(strpos($p['text'], $stkTP) !== false, 'Web Tan Phat hien so tai khoan cua Tan Phat');
    ok(strpos($p['text'], $stk2) === false, 'Web Tan Phat khong hien tai khoan gara 2');
    $t = $tlv($layQr($p['body']));
    ok(is_array($t) && $t['54'] === (string) (int) round((float) $k1['don']['total_amount']),
       'Tan Phat da chon ngan hang: co QR dung so tien');
}

exit(summary());

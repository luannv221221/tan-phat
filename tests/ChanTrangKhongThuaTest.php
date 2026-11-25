<?php
/**
 * Test CHÂN TRANG WEBSITE KHÔNG THỪA.
 *
 * Chạy:  C:\xampp\php\php.exe tests\ChanTrangKhongThuaTest.php
 *
 * Khách ghi 25/11/2026 ở dòng "Thiết lập tài khoản mạng xã hội, cửa hàng…":
 * "Phần footer thì thừa chân trang hiện tại (bỏ đi)". Hai chỗ thừa:
 *
 *   1. Gara tự soạn chân trang (Cấu hình website › Chân trang website) thì khối
 *      soạn được in THÊM vào, còn logo + tên + Địa chỉ / Liên hệ / Giới thiệu
 *      mặc định vẫn in phía dưới — cùng một thông tin hai lần. Có chân trang tự
 *      soạn thì nó THAY khối mặc định.
 *   2. Ba cột menu chân trang lặp link: cột đầu viết cứng Sản phẩm / Dự án /
 *      Thư viện, cột ba lấy từ menu chính cũng có Sản phẩm / Dự án / Thư viện.
 *      Đo trên eteksofts.vn 25/11: ba link đó in hai lần.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$base = web_goc();
$lay = function($url){
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
    $b = (string) curl_exec($ch); $c = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, $b];
};
$chanTrang = function($html){
    return preg_match('~<footer\b.*?</footer>~s', $html, $m) ? $m[0] : '';
};

/* Trang khách ở localhost chạy theo gara tổng (cửa thoát host nội bộ). Ghi
   chân trang thử vào đúng chỗ trang đọc, giữ lại giá trị cũ để trả về. */
$idTong = (int) $pdo->query("SELECT id FROM garages WHERE is_master = 1 LIMIT 1")->fetchColumn();
$cuSite = $pdo->query("SELECT svalue FROM site_settings WHERE skey = 'footer_html'")->fetchColumn();
$cuGara = $pdo->query("SELECT svalue FROM garage_settings WHERE skey = 'footer_html' AND garage_id = $idTong")->fetchColumn();
$traLai = function() use ($pdo, $idTong, $cuSite, $cuGara){
    if ($cuGara === false) $pdo->exec("DELETE FROM garage_settings WHERE skey = 'footer_html' AND garage_id = $idTong");
    else $pdo->prepare("UPDATE garage_settings SET svalue = ? WHERE skey = 'footer_html' AND garage_id = ?")
             ->execute([$cuGara, $idTong]);
    if ($cuSite !== false) $pdo->prepare("UPDATE site_settings SET svalue = ? WHERE skey = 'footer_html'")->execute([$cuSite]);
};
register_shutdown_function($traLai);
$datChanTrang = function($html) use ($pdo, $idTong){
    $pdo->exec("DELETE FROM garage_settings WHERE skey = 'footer_html' AND garage_id = $idTong");
    $pdo->prepare("INSERT INTO garage_settings (garage_id, skey, svalue) VALUES (?, 'footer_html', ?)")
        ->execute([$idTong, $html]);
    $pdo->prepare("UPDATE site_settings SET svalue = ? WHERE skey = 'footer_html'")->execute([$html]);
};

// ---------------------------------------------------------------------------
section('Menu chan trang khong lap link');

$datChanTrang('');
list($c, $h) = $lay($base . '/');
$ft = $chanTrang($h);
ok($c === 200 && $ft !== '', 'Trang chu co chan trang', "HTTP $c");
/* Chỉ đếm trong khối menu: logo trỏ về trang chủ và mục "Trang chủ" là hai
   thứ khác nhau, không phải lặp. */
$menuFt = preg_match('~footer__inner--menu.*?</div>~s', $ft, $mm) ? $mm[0] : '';
preg_match_all('~<a href="([^"]+)"~', $menuFt, $m);
ok(count($m[1]) >= 5, 'Menu chan trang co link', (string) count($m[1]));
$dem = array_count_values(array_map(function($u){ return rtrim($u, '/'); }, $m[1]));
$lap = array_filter($dem, function($n){ return $n > 1; });
ok(empty($lap), 'Moi link trong menu chan trang chi in mot lan', json_encode($lap));

// ---------------------------------------------------------------------------
section('Chua tu soan chan trang -> van co khoi dia chi / lien he mac dinh');

ok(strpos($ft, 'footer__address') !== false, 'Co khoi Dia chi / Lien he khi chua tu soan');

// ---------------------------------------------------------------------------
section('Da tu soan chan trang -> khoi tu soan THAY khoi mac dinh');

$the = 'ZZFOOT-' . bin2hex(random_bytes(3));
$datChanTrang('<p><strong>' . $the . '</strong> 12 Duong Thu</p>');
list($c2, $h2) = $lay($base . '/');
$ft2 = $chanTrang($h2);
ok(strpos($ft2, $the) !== false, 'Chan trang tu soan hien ra');
ok(strpos($ft2, 'footer__address') === false, 'Khoi Dia chi / Lien he mac dinh KHONG in them');
ok(strpos($ft2, 'footer__logo') === false, 'Logo + ten mac dinh KHONG in them');
ok(strpos($ft2, 'footer__inner--menu') !== false, 'Menu chan trang + dang ky ban tin van con');

$traLai();
exit(summary());

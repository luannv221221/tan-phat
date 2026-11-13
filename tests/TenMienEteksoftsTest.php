<?php
/**
 * Test CHUYỂN SANG TÊN MIỀN eteksofts.vn (khách yêu cầu 13/11/2026; anh đã trỏ DNS xong).
 *
 * Chạy:  C:\xampp\php\php.exe tests\TenMienEteksoftsTest.php
 *
 * Bản ghi DNS anh đã tạo (ảnh 13/11):
 *     @       A   103.74.101.148      -> eteksofts.vn
 *     *.etek  A   103.74.101.148      -> <mã gara>.etek.eteksofts.vn
 *     www     CNAME eteksofts.vn.
 *
 * Máy chủ đã nhận request cho các host đó, nhưng hệ thống trả "Không tìm thấy
 * gara" vì bảng `garage_domains` chưa có dòng nào cho chúng. Migration 000112 khai.
 *
 * BA ĐIỀU TEST NÀY GÁC:
 *
 *   1. KHAI ĐỦ VÀ ĐÚNG GARA. eteksofts.vn thuộc GARA TỔNG (trang đang chạy thật);
 *      mỗi gara có <mã>.etek.eteksofts.vn của riêng mình, không lẫn sang gara khác.
 *
 *   2. CHỈ THÊM, KHÔNG ĐỔI GÌ ĐANG CHẠY. Tên miền cũ vẫn vào được (link đã phát ra
 *      không chết), và host CHÍNH của mỗi gara KHÔNG bị đổi: tenMienGoc() suy gốc
 *      cho gara mở sau từ host chính, nên đổi chính là đổi luôn địa chỉ của mọi gara
 *      mở sau — việc đó phải là một cú bấm có chủ ý ở màn Tên miền, sau khi đã thấy
 *      tên miền mới chạy, không phải tác dụng phụ của một lần chạy migration.
 *
 *   3. CHẠY LẠI KHÔNG SINH DÒNG TRÙNG — máy chủ có thể chạy migration hai lần, hoặc
 *      nhập nguyên CSDL máy này lên đã chứa sẵn các dòng đó.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc  = __DIR__ . '/../';
$base = web_goc();

$file = $goc . 'database/migrations/2026_11_13_000112_ten_mien_eteksofts.php';

// ---------------------------------------------------------------------------
section('Migration co mat va chi them');

ok(is_file($file), 'Co file migration 000112_ten_mien_eteksofts');
$ma = is_file($file) ? codeOnly($file) : '';
ok(strpos($ma, 'eteksofts.vn') !== false, 'Migration noi den eteksofts.vn');
ok(strpos($ma, 'etek.eteksofts.vn') !== false, 'Gara con theo ma: <ma>.etek.eteksofts.vn (khop DNS *.etek)');

$viTriUp   = (int) strpos($ma, 'function up');
$viTriDown = (int) strpos($ma, 'function down');
$thanUp    = $ma === '' ? '' : substr($ma, $viTriUp, $viTriDown - $viTriUp);
ok($thanUp !== '' && preg_match('~\bUPDATE\b|\bDELETE\b|\bDROP\b|->update\(|->delete\(~i', $thanUp) === 0,
   'up() khong sua / xoa dong nao dang co');
ok($thanUp !== '' && strpos($thanUp, "'is_primary' => 1") === false,
   'up() KHONG dat host moi lam host chinh (doi chinh la viec co chu y o man Ten mien)',
   'Doi chinh -> tenMienGoc() doi -> moi gara mo sau nhan dia chi moi ngay khi chay migration');

// ---------------------------------------------------------------------------
try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}
require_once $goc . 'app/models/GarageDomainsModel.php';
$M = new GarageDomainsModel();

// ---------------------------------------------------------------------------
section('Tra gara theo host moi');

$tong = $pdo->query("SELECT id, code FROM garages WHERE is_master = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$r = $M->theoHost('eteksofts.vn');
ok(!empty($r) && (int) $r['is_master'] === 1, 'eteksofts.vn ra GARA TONG',
   'Trang khach dang vao la trang Tan Phat — khai nham gara khac la khach thay trang gara do');
$r = $M->theoHost('www.eteksofts.vn');
ok(!empty($r) && (int) $r['is_master'] === 1, 'www.eteksofts.vn cung ra gara tong (chuan hoa bo www.)');
$r = $M->theoHost('WWW.EteksOfts.VN:443');
ok(!empty($r) && (int) $r['is_master'] === 1, 'Viet hoa + cong + www van khop');

foreach ($pdo->query("SELECT id, code FROM garages WHERE status = 1")->fetchAll(PDO::FETCH_ASSOC) as $g){
    $host = slugify($g['code']) . '.etek.eteksofts.vn';
    $r = $M->theoHost($host);
    ok(!empty($r) && (int) $r['id'] === (int) $g['id'], "$host ra dung gara {$g['code']}",
       'tra ve: ' . json_encode($r));
}
ok($M->theoHost('khong-co.etek.eteksofts.vn') === [], 'Subdomain khong thuoc gara nao van BI CHAN (khong roi ve gara tong)');
ok($M->theoHost('etek.eteksofts.vn') === [], 'etek.eteksofts.vn tran khong khai (DNS *.etek khong phu ten nay)');

// ---------------------------------------------------------------------------
section('Khong doi gi dang chay');

foreach (['etek.rikkeiedu.org', 'tp01.etek.rikkeiedu.org', 'dmsg.etek.rikkeiedu.org'] as $h){
    ok(!empty($M->theoHost($h)), "Ten mien cu `$h` van vao duoc");
}
$st = $pdo->prepare("SELECT host FROM garage_domains WHERE garage_id = ? AND is_primary = 1");
$st->execute([(int) $tong['id']]);
$chinh = $st->fetchAll(PDO::FETCH_COLUMN);
ok($chinh === ['tp01.etek.rikkeiedu.org'], 'Host CHINH cua gara tong KHONG bi doi', json_encode($chinh));
$st = $pdo->query("SELECT garage_id, COUNT(*) c FROM garage_domains WHERE is_primary = 1 GROUP BY garage_id HAVING c <> 1");
ok($st->fetch() === false, 'Moi gara van co dung MOT host chinh');

// ---------------------------------------------------------------------------
section('Chay lai khong sinh dong trung');

/* Chay up() MOT LAN truoc de chot trang thai (tuong duong `php migrate.php`),
   roi moi dem — nhu vay test dung duoc ca khi migration chua tung chay tren
   may nay, va lan do khong bi tinh la "them dong". */
$db  = new App\core\Database();
$mig = is_file($file) ? (require $file)->setDb($db) : null;
if ($mig){ ob_start(); $mig->up(); ob_end_clean(); }
$truoc = (int) $pdo->query("SELECT COUNT(*) FROM garage_domains")->fetchColumn();
if ($mig){ ob_start(); $mig->up(); $mig->up(); ob_end_clean(); }
$sau = (int) $pdo->query("SELECT COUNT(*) FROM garage_domains")->fetchColumn();
ok($sau === $truoc, 'Chay up() them hai lan: so dong khong doi', "truoc=$truoc sau=$sau");
$dup = $pdo->query("SELECT host, COUNT(*) c FROM garage_domains GROUP BY host HAVING c > 1")->fetch();
ok($dup === false, 'Khong co host nao xuat hien hai lan');

// ---------------------------------------------------------------------------
section('Chay that qua HTTP voi Host moi');

if (!function_exists('curl_init')){ echo "  [SKIP] PHP khong co curl.\n"; exit(summary()); }
$http = function($url, $host){
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 30, CURLOPT_HTTPHEADER => ['Host: ' . $host]]);
    $raw = curl_exec($ch); $info = curl_getinfo($ch); curl_close($ch);
    return ['code' => (int) $info['http_code'],
            'body' => $raw === false ? '' : substr($raw, (int) $info['header_size'])];
};
$r = $http("$base/", 'localhost:88');
if ($r['code'] === 0){ echo "  [SKIP] Apache khong chay (localhost:88).\n"; exit(summary()); }

$r = $http("$base/", 'eteksofts.vn');
ok($r['code'] === 200, 'http://eteksofts.vn/ -> 200 (HTTP ' . $r['code'] . ')', 'Truoc migration: 404 "Khong tim thay gara"');
ok(mb_strpos($r['body'], 'Không tìm thấy gara') === false, 'Khong con trang "Khong tim thay gara"');
$r = $http("$base/", 'www.eteksofts.vn');
ok($r['code'] === 200, 'http://www.eteksofts.vn/ -> 200 (HTTP ' . $r['code'] . ')');
$r = $http("$base/", 'tp01.etek.eteksofts.vn');
ok($r['code'] === 200, 'http://tp01.etek.eteksofts.vn/ -> 200 (HTTP ' . $r['code'] . ')');
$r = $http("$base/", 'dmsg.etek.eteksofts.vn');
ok($r['code'] === 200, 'http://dmsg.etek.eteksofts.vn/ -> 200 (HTTP ' . $r['code'] . ')');
$r = $http("$base/", 'bay-ba.etek.eteksofts.vn');
ok($r['code'] === 404, 'Subdomain la van bi 404 (HTTP ' . $r['code'] . ')');

exit(summary());

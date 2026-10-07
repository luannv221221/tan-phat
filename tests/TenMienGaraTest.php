<?php
/**
 * Test NHẬN GARA THEO TÊN MIỀN — bước 1 của nền tảng nhiều gara.
 *
 * Chạy:  C:\xampp\php\php.exe tests\TenMienGaraTest.php
 *
 * Trước đây gara làm việc suy ra từ TÀI KHOẢN (trang quản trị) và luôn là gara
 * tổng (trang người dùng) — cả hệ thống chỉ có một website. Nay tên miền quyết
 * định đang phục vụ gara nào.
 *
 * BỐN CHỖ HỎNG ÂM THẦM mà test này gác:
 *
 *   1. KHỚP KIỂU "CHỨA" THAY VÌ KHỚP ĐÚNG. Dùng LIKE thì "a.etek.vn" khớp
 *      nhầm cả "gara-a.etek.vn" — gara này đọc được dữ liệu gara kia. Phải so
 *      BẰNG, sau khi hạ chữ thường / bỏ cổng / bỏ www.
 *
 *   2. TÀI KHOẢN GARA A VÀO ĐƯỢC ĐỊA CHỈ GARA B. Mọi lớp lọc phía dưới đều tin
 *      vào gara_hien_tai(); sai ở đây là sai toàn hệ thống.
 *
 *   3. HOST LẠ RƠI VỀ GARA TỔNG. Rơi về nghĩa là bất kỳ tên miền nào trỏ bừa
 *      vào máy chủ cũng xem được dữ liệu Tân Phát.
 *
 *   4. BẬT LÊN LÀ SẬP TRANG ĐANG CHẠY. Hai cửa thoát cố ý: bảng tên miền còn
 *      rỗng (chưa cấu hình) và host nội bộ (máy chạy thử) thì giữ nguyên cách
 *      cũ. Và chính tên miền gốc etek.rikkeiedu.org phải được khai cho gara
 *      tổng, không thì đẩy code lên là web sập.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc  = __DIR__ . '/../';
$base = 'http://localhost:88/tan-phat';

/* Model khong tu nap: cac test khac cung require tay nhu vay. */
require_once $goc . 'app/models/GarageDomainsModel.php';

// ---------------------------------------------------------------------------
section('Chuan hoa host');

ok(GarageDomainsModel::chuanHoaHost('GaraA.Etek.VN') === 'garaa.etek.vn', 'Ha chu thuong');
ok(GarageDomainsModel::chuanHoaHost('garaa.etek.vn:443') === 'garaa.etek.vn', 'Bo cong');
ok(GarageDomainsModel::chuanHoaHost('www.garaa.etek.vn') === 'garaa.etek.vn', 'Bo www.');
ok(GarageDomainsModel::chuanHoaHost('  garaa.etek.vn  ') === 'garaa.etek.vn', 'Bo khoang trang');
ok(GarageDomainsModel::chuanHoaHost('[::1]:88') === '[::1]', 'IPv6 giu nguyen dau ngoac, bo cong');
ok(GarageDomainsModel::chuanHoaHost('') === '', 'Rong -> rong');

// ---------------------------------------------------------------------------
section('Host noi bo (may chay thu) duoc bo qua luat ten mien');

foreach (['localhost', 'localhost:88', '127.0.0.1', '[::1]', 'may-cua-toi',
          'app.localhost', 'tanphat.test'] as $h){
    ok(la_host_noi_bo($h) === true, "`$h` la host noi bo");
}
foreach (['etek.rikkeiedu.org', 'tp01.etek.rikkeiedu.org', 'garaa.vn'] as $h){
    ok(la_host_noi_bo($h) === false, "`$h` KHONG phai host noi bo");
}

// ---------------------------------------------------------------------------
try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$donSach = function() use ($pdo){
    $pdo->exec("DELETE FROM garage_domains WHERE host LIKE 'zztm-%'");
};
$donSach();

// ---------------------------------------------------------------------------
section('Migration da khai ten mien cho moi gara');

$gara = $pdo->query("SELECT id, code, is_master FROM garages WHERE status = 1")->fetchAll(PDO::FETCH_ASSOC);
foreach ($gara as $g){
    $st = $pdo->prepare("SELECT COUNT(*) FROM garage_domains WHERE garage_id = ? AND is_primary = 1");
    $st->execute([(int) $g['id']]);
    ok((int) $st->fetchColumn() === 1, "Gara {$g['code']} co dung MOT ten mien chinh");
}

/* Tên miền gốc PHẢI thuộc gara tổng: trang đang chạy thật nằm ở đó. */
$st = $pdo->prepare("SELECT g.is_master FROM garage_domains d JOIN garages g ON g.id = d.garage_id
                     WHERE d.host = 'etek.rikkeiedu.org'");
$st->execute();
ok((int) $st->fetchColumn() === 1,
   'Ten mien goc etek.rikkeiedu.org tro ve GARA TONG',
   'Khong khai thi day code len la trang dang chay thanh "host la" va bi chan');

// ---------------------------------------------------------------------------
section('Tra gara theo host');

$M = new GarageDomainsModel();

$tp = $M->theoHost('tp01.etek.rikkeiedu.org');
ok(!empty($tp) && (int) $tp['is_master'] === 1, 'tp01... ra gara tong');

$sg = $M->theoHost('dmsg.etek.rikkeiedu.org');
ok(!empty($sg) && !empty($tp) && (int) $sg['id'] !== (int) $tp['id'],
   'dmsg... ra MOT GARA KHAC, khong phai gara tong');

ok($M->theoHost('TP01.Etek.RikkeiEdu.ORG:443') && (int) $M->theoHost('TP01.Etek.RikkeiEdu.ORG:443')['id'] === (int) $tp['id'],
   'Viet hoa + cong van ra dung gara do');

ok($M->theoHost('khong-co-that.example.com') === [], 'Host chua khai -> rong');
ok($M->theoHost('') === [], 'Host rong -> rong');

/* BẪY CHÍNH: khớp kiểu "chứa" thì tên miền dài hơn sẽ nuốt tên miền ngắn. */
$st = $pdo->prepare("INSERT INTO garage_domains (garage_id, host, is_primary, status, create_at)
                     VALUES (?, 'zztm-rieng.etek.rikkeiedu.org', 0, 1, NOW())");
$st->execute([(int) $sg['id']]);
ok($M->theoHost('rieng.etek.rikkeiedu.org') === [],
   'Host la hau to cua mot ten mien da khai -> KHONG khop',
   'Khop kieu LIKE thi gara nay doc duoc du lieu gara kia');
ok(!empty($M->theoHost('zztm-rieng.etek.rikkeiedu.org')),
   'Nhung chinh ten mien do thi van khop');

/* Gara bị khoá thì tên miền của nó cũng thôi phục vụ. */
$pdo->prepare("UPDATE garages SET status = 0 WHERE id = ?")->execute([(int) $sg['id']]);
ok($M->theoHost('dmsg.etek.rikkeiedu.org') === [],
   'Gara bi khoa -> ten mien cua no khong tra ve gara nao');
$pdo->prepare("UPDATE garages SET status = 1 WHERE id = ?")->execute([(int) $sg['id']]);

// ---------------------------------------------------------------------------
section('Middleware duoc dang ky va dung THU TU');

$cfg = file_get_contents($goc . 'configs/app.php');
ok(strpos($cfg, 'TenMienMiddleware::class') !== false, 'TenMienMiddleware nam trong global_middleware');
$viTriTenMien = strpos($cfg, 'TenMienMiddleware::class');
$viTriCsrf    = strpos($cfg, 'CsrfMiddleware::class,');
ok($viTriTenMien !== false && $viTriCsrf !== false && $viTriTenMien < $viTriCsrf,
   'Chay TRUOC cac middleware khac',
   'Chay sau thi middleware khac da kip lam viec voi gara sai');

// ---------------------------------------------------------------------------
section('Chay that qua HTTP');

if (!function_exists('curl_init')){ echo "  [SKIP] PHP khong co curl.\n"; $donSach(); exit(summary()); }

$http = function($url, $host, $jar = null, $data = null){
    $ch = curl_init($url);
    $opt = [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 25, CURLOPT_HTTPHEADER => ['Host: ' . $host]];
    if ($jar !== null){ $opt[CURLOPT_COOKIEJAR] = $jar; $opt[CURLOPT_COOKIEFILE] = $jar; }
    if ($data !== null){ $opt[CURLOPT_POST] = true; $opt[CURLOPT_POSTFIELDS] = http_build_query($data); }
    curl_setopt_array($ch, $opt);
    $raw  = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    $body = $raw === false ? '' : substr($raw, (int) $info['header_size']);
    return ['code' => (int) $info['http_code'], 'body' => $body];
};

$r = $http("$base/", 'localhost:88');
if ($r['code'] === 0){ echo "  [SKIP] Apache khong chay (localhost:88).\n"; $donSach(); exit(summary()); }
ok($r['code'] === 200, 'Host noi bo van vao duoc binh thuong (HTTP ' . $r['code'] . ')',
   'May chay thu va bo test khong co ten mien gara nao tro ve');

$r = $http("$base/", 'tp01.etek.rikkeiedu.org');
ok($r['code'] === 200, 'Ten mien cua gara tong vao duoc (HTTP ' . $r['code'] . ')');

$r = $http("$base/", 'dmsg.etek.rikkeiedu.org');
ok($r['code'] === 200, 'Ten mien cua gara khac cung vao duoc (HTTP ' . $r['code'] . ')');

$r = $http("$base/", 'khong-co-that.example.com');
ok($r['code'] === 404, 'Host LA bi chan voi ma 404 (HTTP ' . $r['code'] . ')',
   '404 chu khong phai 500: dia chi khong co that, khong phai may chu hong');
ok(mb_strpos($r['body'], 'Không tìm thấy gara') !== false,
   'Va noi ro la khong tim thay gara cho dia chi do');
ok(mb_strpos($r['body'], 'khong-co-that.example.com') !== false,
   'Trang bao in ra chinh dia chi dang go',
   'Gap trang nay gan nhu luon la go nham ten mien phu hoac DNS chua tro xong');

// ---------------------------------------------------------------------------
section('Tai khoan gara nay KHONG vao duoc dia chi gara kia');

$MK = 'Gara@2026';
$taiKhoanSG = $pdo->query("SELECT email FROM users WHERE garage_id = " . (int) $sg['id']
                        . " AND status = 1 AND email LIKE '%@gara-mau.test' LIMIT 1")->fetchColumn();
if (empty($taiKhoanSG)){
    echo "  [SKIP] Khong co tai khoan mau cua gara nay de thu.\n";
} else {
    $jar = tempnam(sys_get_temp_dir(), 'zztm');
    $hSG = 'dmsg.etek.rikkeiedu.org';
    $r   = $http("$base/dang-nhap", $hSG, $jar);
    preg_match('~name="_token" value="([^"]+)"~', $r['body'], $m);
    $http("$base/dang-nhap", $hSG, $jar, ['email' => $taiKhoanSG, 'password' => $MK, '_token' => $m[1] ?? '']);

    $r = $http("$base/admin", $hSG, $jar);
    ok($r['code'] === 200,
       'Vao duoc trang quan tri tai DUNG dia chi gara minh (HTTP ' . $r['code'] . ')',
       'Tai khoan thu: ' . $taiKhoanSG);

    $r = $http("$base/admin", 'tp01.etek.rikkeiedu.org', $jar);
    ok($r['code'] !== 200,
       'Cung phien do, mo dia chi GARA KHAC thi bi da ra (HTTP ' . $r['code'] . ')',
       'Khong chan thi nhan vien gara nay lam viec tren du lieu gara kia');

    @unlink($jar);
}

$donSach();
ok((int) $pdo->query("SELECT COUNT(*) FROM garage_domains WHERE host LIKE 'zztm-%'")->fetchColumn() === 0,
   'Da don sach du lieu test');

exit(summary());

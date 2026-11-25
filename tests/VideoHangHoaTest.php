<?php
/**
 * Test VIDEO CHO HÀNG HOÁ.
 *
 * Chạy:  C:\xampp\php\php.exe tests\VideoHangHoaTest.php
 *
 * Khách ghi 25/11/2026 ở dòng "Cập nhật nhanh giá hàng hóa": "Thêm thông số cơ
 * bản, video". Thông số kỹ thuật đã có (thẻ Thông số kỹ thuật ở form hàng hoá,
 * tab ở trang khách). Video thì chưa: `parts` không có cột nào.
 *
 * Nay: một link YouTube cho mỗi mặt hàng — khai ở form hàng hoá và ở file Excel
 * nhập / xuất (dòng khách ghi là dòng "cập nhật nhanh"), trang chi tiết sản phẩm
 * có tab Video nhúng qua youtube-nocookie (như video trang chủ / thư viện).
 *
 * Chỉ nhận link YouTube: nhúng một link bất kỳ vào <iframe> là để người khác
 * quyết định trang khách chạy cái gì. Lưu dạng chuẩn https://www.youtube.com/watch?v=<mã>.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Chuan hoa link');

ok(function_exists('link_youtube'), 'Co ham link_youtube()');
if (function_exists('link_youtube')){
    ok(link_youtube('https://youtu.be/dQw4w9WgXcQ?t=5') === 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
       'youtu.be -> link chuan');
    ok(link_youtube('https://www.youtube.com/watch?v=dQw4w9WgXcQ&list=x') === 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
       'watch?v= kem tham so -> link chuan');
    ok(link_youtube('https://evil.example/x.mp4') === '', 'Link khong phai YouTube -> rong');
    ok(link_youtube('javascript:alert(1)') === '' && link_youtube('') === '', 'Rac -> rong');
}

// ---------------------------------------------------------------------------
section('Form + duong luu + Excel');

foreach (['add', 'edit'] as $f){
    $v = file_get_contents($goc . "app/views/admin/products/$f.php");
    ok(strpos($v, 'name="video_url"') !== false, "products/$f.php co o Video (link YouTube)");
}
$ctl = codeOnly($goc . 'app/controllers/admin/Products.php');
ok(preg_match("~'video_url'\s*=>\s*.*link_youtube\(~", $ctl) === 1, 'Luu qua link_youtube() (chi nhan YouTube, dang chuan)');
ok(preg_match("~\\\$errors\['video_url'\]~", $ctl) === 1, 'Link sai -> bao loi, khong lang le bo');
ok(preg_match("~'video_url'\s*=>\s*\[[^\]]*'video'~", $ctl) === 1, 'Nhap Excel nhan cot video_url / video');
ok(preg_match("~isset\(\\\$colMap\['video_url'\]\)~", $ctl) === 1,
   'Nhap Excel CHI ghi video khi file co cot do (file cu khong xoa mat video)');
ok(preg_match("~\\\$headers = \[[^\]]*'video_url'~s", $ctl) === 1, 'File mau co cot video_url');
ok(preg_match("~fputcsv\(\\\$out, \[[^\]]*'video_url'~s", $ctl) === 1, 'File xuat co cot video_url');

// ---------------------------------------------------------------------------
section('Trang khach');

$d = file_get_contents($goc . 'app/views/storefront/detail.php');
ok(strpos($d, 'data-bs-target="#tab-video"') !== false && strpos($d, 'id="tab-video"') !== false,
   'Trang chi tiet co tab Video');
ok(strpos($d, 'youtube-nocookie.com/embed/') !== false, 'Nhung qua youtube-nocookie');
ok(strpos($d, "youtube_id(\$part['video_url']") !== false, 'Chi nhung MA video (youtube_id), khong in nguyen link vao iframe');

// ---------------------------------------------------------------------------
try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}
section('CSDL + trang khach that');

$cot = $pdo->query("SHOW COLUMNS FROM parts LIKE 'video_url'")->fetch(PDO::FETCH_ASSOC);
ok(!empty($cot) && stripos($cot['Type'], 'varchar') === 0 && $cot['Null'] === 'YES', 'parts.video_url VARCHAR NULL (migration 000120)');

if (!empty($cot)){
    $p = $pdo->query("SELECT id, slug, video_url FROM parts WHERE status = 1 AND garage_id IS NULL
                      AND slug IS NOT NULL AND slug <> '' ORDER BY id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($p){
        $cu = $p['video_url'];
        register_shutdown_function(function() use ($pdo, $p, $cu){
            $pdo->prepare('UPDATE parts SET video_url = ? WHERE id = ?')->execute([$cu, $p['id']]);
        });
        $lay = function($url){
            $ch = curl_init($url); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
            $b = (string) curl_exec($ch); curl_close($ch); return $b;
        };
        $pdo->prepare('UPDATE parts SET video_url = ? WHERE id = ?')
            ->execute(['https://www.youtube.com/watch?v=dQw4w9WgXcQ', $p['id']]);
        $h = $lay(web_goc() . '/san-pham/' . rawurlencode($p['slug']));
        ok(strpos($h, 'youtube-nocookie.com/embed/dQw4w9WgXcQ') !== false, 'Co video -> trang san pham nhung dung video');

        $pdo->prepare('UPDATE parts SET video_url = NULL WHERE id = ?')->execute([$p['id']]);
        $h2 = $lay(web_goc() . '/san-pham/' . rawurlencode($p['slug']));
        ok(strpos($h2, 'id="tab-video"') === false, 'Khong co video -> khong hien tab Video trong');
    }
}

exit(summary());

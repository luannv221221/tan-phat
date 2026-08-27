<?php
/**
 * Test KHỐI VIDEO ở trang chủ.
 *
 * Chạy:  C:\xampp\php\php.exe tests\VideoTrangChuTest.php
 *
 * Nguồn dữ liệu là thư viện ảnh/video có sẵn (`gallery_items`), KHÔNG tạo bảng
 * mới: đăng video vẫn làm ở admin > Thư viện như cũ.
 *
 * Hai chỗ dễ hỏng, đều có test riêng bên dưới:
 *   1. Album còn NHÁP mà lọt lên trang chủ -> đăng hộ thứ người ta chưa muốn
 *      công khai.
 *   2. `caption` là ô không bắt buộc; dữ liệu đang có 2/3 dòng bỏ trống. Không
 *      có đường lui thì danh sách bên phải hiện ra mấy dòng trắng.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('youtube_id() — nhan du cac dang dang co trong CSDL');

$dung = [
    'https://www.youtube.com/watch?v=dQw4w9WgXcQ' => 'dQw4w9WgXcQ',
    'https://youtu.be/dQw4w9WgXcQ'                => 'dQw4w9WgXcQ',
    'https://www.youtube.com/embed/dQw4w9WgXcQ'   => 'dQw4w9WgXcQ',
    'http://youtube.com/watch?a=1&v=dQw4w9WgXcQ'  => 'dQw4w9WgXcQ',
    // MÃ TRẦN — 2/3 dòng trong gallery_items dang luu kieu nay
    'dQw4w9WgXcQ'                                 => 'dQw4w9WgXcQ',
    '  dQw4w9WgXcQ  '                             => 'dQw4w9WgXcQ',
];
foreach ($dung as $vao => $ra){
    ok(youtube_id($vao) === $ra, 'Rut duoc ma tu: ' . trim($vao), var_export(youtube_id($vao), true));
}

$sai = ['', 'https://vimeo.com/123456', 'khong-phai-ma', 'dQw4w9WgXc', 'dQw4w9WgXcQQ', 'https://example.com/'];
foreach ($sai as $v){
    ok(youtube_id($v) === '', 'Tra ve rong voi: ' . ($v === '' ? '(chuoi rong)' : $v), var_export(youtube_id($v), true));
}

// ---------------------------------------------------------------------------
section('Trang chu co nhan du lieu video');

$home = codeOnly($goc . 'app/controllers/Home.php');
ok(strpos($home, "GalleryItemsModel") !== false, 'Home nap GalleryItemsModel');
ok(strpos($home, "getVideosPublished") !== false, 'Home goi getVideosPublished()');
ok(strpos($home, "['videos']") !== false, 'Home truyen bien $videos xuong view');

// ---------------------------------------------------------------------------
section('View: nhung an toan + khong tu chay');

$v = file_get_contents($goc . 'app/views/storefront/home.php');

ok(strpos($v, 'youtube-nocookie.com/embed/') !== false,
   'Nhung qua youtube-nocookie',
   'Ban khong dat cookie theo doi khi khach chua bam gi');

ok(preg_match('~src="https://www\.youtube-nocookie\.com/embed/\{\{\$dsVideo\[0\]\[\x27ma\x27\]\}\}"~', $v) === 1,
   'Khung lon luc tai trang KHONG co autoplay',
   'Video tu chay khi vua vao trang la lam phien, tren 3G con ngon du lieu cua khach');

ok(strpos($v, "'?autoplay=1'") !== false || strpos($v, "+ '?autoplay=1'") !== false,
   'Chi bat autoplay trong ham xu ly CU BAM');

ok(strpos($v, 'youtube_id($v[\'video_url\'])') !== false,
   'Loc qua youtube_id truoc khi dung');
ok(strpos($v, "if (\$ma === '') continue;") !== false,
   'Bo qua dong khong rut duoc ma',
   'De lot thi hien ra khung den khong noi dung');

ok(strpos($v, "!empty(\$v['caption']) ? \$v['caption'] : \$v['gallery_name']") !== false,
   'Caption trong thi lui ve ten album',
   'caption la o khong bat buoc, 2/3 dong du lieu dang bo trong');

ok(strpos($v, 'loading="lazy"') !== false, 'Anh dai dien tai kieu lazy');
ok(strpos($v, 'closest(\'.videos__muc\')') !== false,
   'Bat su kien bang closest()',
   'Bam trung vao ANH ben trong nut van phai doi duoc video');

// ---------------------------------------------------------------------------
section('CSS');

$css = file_get_contents($goc . 'public/assets/storefront/css/theme.css');
foreach (['.videos__khung', '.videos__ds', '.videos__muc', '.videos__anh', '.videos__ten'] as $c){
    ok(strpos($css, $c) !== false, "Co luat CSS cho $c");
}
ok(preg_match('~\.videos__khung\{[^}]*aspect-ratio:16/9~s', $css) === 1,
   'Khung video giu ti le 16/9');
ok(preg_match('~\.videos__ten\{[^}]*-webkit-line-clamp:3~s', $css) === 1,
   'Ten video cat o dong thu 3',
   'Ten dai khong duoc day cac muc khac lech nhip');

// ---------------------------------------------------------------------------
section('Chi lay video cua album DA DANG');

try {
    $pdo = new PDO(
        'mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
        _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL — bo qua phan kiem CSDL.\n";
    exit(summary());
}

require_once $goc . 'app/models/GalleryItemsModel.php';

$don = function() use ($pdo){
    $ids = $pdo->query("SELECT id FROM galleries WHERE slug LIKE 'vd-test-%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $id){ $pdo->exec("DELETE FROM gallery_items WHERE gallery_id = " . (int) $id); }
    $pdo->exec("DELETE FROM galleries WHERE slug LIKE 'vd-test-%'");
};
$don();

$now = date('Y-m-d H:i:s');
$taoAlbum = function($ten, $slug, $dang) use ($pdo, $now){
    $pdo->prepare("INSERT INTO galleries (name,slug,is_published,sort_order,create_at) VALUES (?,?,?,0,?)")
        ->execute([$ten, $slug, $dang, $now]);
    return (int) $pdo->lastInsertId();
};
$taoVideo = function($gid, $url, $caption) use ($pdo, $now){
    $pdo->prepare("INSERT INTO gallery_items (gallery_id,media_type,video_url,caption,sort_order,create_at) VALUES (?,'video',?,?,0,?)")
        ->execute([$gid, $url, $caption, $now]);
};

$daDang  = $taoAlbum('VD Test da dang', 'vd-test-da-dang', 1);
$conNhap = $taoAlbum('VD Test con nhap', 'vd-test-con-nhap', 0);

$taoVideo($daDang,  'https://youtu.be/aaaaaaaaaaa', 'VD Test video cong khai');
$taoVideo($conNhap, 'https://youtu.be/bbbbbbbbbbb', 'VD Test video con nhap');
// Ảnh trong album đã đăng — không được lọt vào khối video
$pdo->prepare("INSERT INTO gallery_items (gallery_id,media_type,image,caption,sort_order,create_at) VALUES (?,'image',?,?,0,?)")
    ->execute([$daDang, 'vd-test.jpg', 'VD Test anh', $now]);

$ds = (new GalleryItemsModel())->getVideosPublished(50);
$caption = array_map(function($x){ return (string) $x['caption']; }, $ds);
$loai    = array_unique(array_map(function($x){ return $x['media_type']; }, $ds));

ok(in_array('VD Test video cong khai', $caption, true),
   'Lay video cua album da dang');
ok(!in_array('VD Test video con nhap', $caption, true),
   'KHONG lay video cua album con nhap',
   'Album nhap lot len trang chu la dang ho thu nguoi ta chua muon cong khai');
ok(!in_array('VD Test anh', $caption, true), 'KHONG lay anh, chi lay video');
ok($loai === ['video'] || $loai === [], 'Moi dong tra ve deu la media_type=video', implode(',', $loai));

$coTenAlbum = true;
foreach ($ds as $x){ if (!array_key_exists('gallery_name', $x)) $coTenAlbum = false; }
ok($coTenAlbum, 'Kem theo ten album de lam tieu de du phong');

ok(count((new GalleryItemsModel())->getVideosPublished(1)) <= 1, 'Ton trong tham so gioi han');

$don();

exit(summary());

<?php
/**
 * Test cho helper — slugify() và _WEB_URL.
 *
 * Chạy:  C:\xampp\php\php.exe tests\HelpersTest.php
 */

require_once __DIR__ . '/_helpers.php';

// vendor/autoload.php da nap app/helpers/functions.php qua muc "files" cua composer.
// Require functions.php mot lan nua se loi "Cannot redeclare isRole()".
require_once __DIR__ . '/../vendor/autoload.php';

$_SERVER['HTTP_HOST'] = 'localhost';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";

// ================================================================
section('slugify() — tieng Viet co dau');

$cases = [
    'Xăng'              => 'xang',
    'Dầu (Diesel)'      => 'dau-diesel',
    'Điện'              => 'dien',
    'Bán tải'           => 'ban-tai',
    'Ắc quy'            => 'ac-quy',
    'Đèn'               => 'den',
    'Nhật Bản'          => 'nhat-ban',
    'Hàn Quốc'          => 'han-quoc',
    'Việt Nam'          => 'viet-nam',
    'Hệ thống phanh'    => 'he-thong-phanh',
    'Má phanh'          => 'ma-phanh',
    'Lọc gió'           => 'loc-gio',
    'Dây curoa'         => 'day-curoa',
    'Giảm xóc'          => 'giam-xoc',
    'Lò xo'             => 'lo-xo',
    'Máy phát'          => 'may-phat',
];

foreach ($cases as $in => $expect){
    $got = slugify($in);
    ok($got === $expect, "slugify('$in') = '$expect'", "thuc te: '$got'");
}

// ⭐ Quan trong: slug tu sinh phai KHOP slug ma migration da seed.
// Neu lech, nguoi dung them "Xăng" se khong bi bao trung ma tao ban ghi thu 2.
ok(slugify('Dầu (Diesel)') === 'dau-diesel',
   'slugify khop slug da seed trong migration (khong tao trung)');

// ================================================================
section('slugify() — truong hop bien');

ok(slugify('Toyota Vios 2018') === 'toyota-vios-2018', 'Giu chu so');
ok(slugify('  Nhieu   khoang   trang  ') === 'nhieu-khoang-trang', 'Gop khoang trang');
// Ky tu dac biet bi BO HAN (khong doi thanh gach ngang) — giong Laravel Str::slug.
// Nen 'dac!!!tu' -> 'dactu'. Muon tach thi trong ten phai co khoang trang.
ok(slugify('Ky-tu--dac!!!@#$%^&*()tu') === 'ky-tu-dactu', 'Bo ky tu dac biet, gop gach ngang',
   slugify('Ky-tu--dac!!!@#$%^&*()tu'));
ok(slugify('Dầu (Diesel)') === 'dau-diesel',
   'Ngoac don bi bo, khoang trang van tach tu -> dau-diesel');
ok(slugify('---abc---') === 'abc', 'Cat gach ngang dau/cuoi');
ok(slugify('ĐÈN XE HOA') === 'den-xe-hoa', 'Chu HOA -> chu thuong');
ok(slugify('') === '', 'Chuoi rong -> rong');
ok(slugify('###') === '', 'Toan ky tu dac biet -> rong (controller phai chan truong hop nay)');
ok(slugify('123') === '123', 'Toan so van hop le');
ok(slugify(null) === '', 'null -> rong (khong loi)');

// D hoa va thuong
ok(slugify('Đường') === 'duong', 'Đ hoa -> d');
ok(slugify('đường') === 'duong', 'đ thuong -> d');

// ================================================================
section('slugify() — khong phu thuoc locale may chu');

/*
 * Vi sao khong dung iconv('UTF-8','ASCII//TRANSLIT'): ket qua phu thuoc locale
 * cua HE DIEU HANH. Cung chuoi 'Đ' co the ra 'D', 'DJ' hoac '?' tuy may.
 * => dev Windows va server Linux sinh slug khac nhau => du lieu lech.
 * Bang thay the tuong minh cho ket qua GIONG NHAU o moi noi.
 */
// codeOnly(): bo comment truoc khi grep — chinh docblock cua slugify() co nhac
// 'ASCII//TRANSLIT' de giai thich vi sao KHONG dung no. Grep tho se bao fail oan.
$src = codeOnly(__DIR__ . '/../app/helpers/functions.php');
ok(strpos($src, 'ASCII//TRANSLIT') === false,
   'slugify KHONG dung iconv ASCII//TRANSLIT (ket qua phu thuoc locale may chu)');

// Chay 2 lan phai ra y het
ok(slugify('Dầu (Diesel)') === slugify('Dầu (Diesel)'), 'Chay 2 lan cho ket qua giong nhau');

// slugify cua slug phai la chinh no (idempotent)
foreach (['dau-diesel', 'he-thong-phanh', 'toyota-vios-2018'] as $slug){
    ok(slugify($slug) === $slug, "slugify('$slug') = chinh no (idempotent)");
}

// ================================================================
section('_WEB_URL — khong duoc co dau / o cuoi');

/*
 * MOI cho dung deu noi them '/':
 *     _WEB_URL.'/admin/users'    (view)
 *     _WEB_URL.'/'.$path         (Response::redirect)
 * Neu _WEB_URL co '/' cuoi -> sinh URL hai gach: http://host/app//admin/users
 * parse_url() se hieu '//admin' la URL protocol-relative => 'admin' thanh TEN MAY CHU.
 */
ok(substr(_WEB_URL, -1) !== '/', '_WEB_URL khong ket thuc bang /', _WEB_URL);
ok(strpos(_WEB_URL . '/admin/users', '//admin') === false,
   'Noi them /admin KHONG sinh dau gach doi', _WEB_URL . '/admin/users');

// Chung minh vi sao gach doi la nguy hiem
$bad = parse_url('//admin/car-fuels', PHP_URL_PATH);
ok($bad !== '/admin/car-fuels',
   '(chung minh) parse_url("//admin/car-fuels") KHONG tra ve duong dan dung',
   'tra ve: ' . var_export($bad, true) . ' — vi hieu //admin la ten may chu');

exit(summary());

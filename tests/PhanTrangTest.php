<?php
/**
 * Test PHAN TRANG danh sach admin.
 *
 * Chay:  C:\xampp\php\php.exe tests\PhanTrangTest.php
 *
 * Trong tam:
 *   - Cat dung so dong, KHONG danh so lai khoa (cot STT phai chay tiep sang
 *     trang 2 chu khong quay ve 1).
 *   - Tham so ?per_page= la ai cung sua duoc tren URL -> gia tri la phai roi
 *     ve mac dinh, khong tin theo.
 *   - Trang vuot qua so trang thuc te phai kep lai, khong duoc de offset am
 *     (array_slice offset am dem nguoc tu cuoi mang -> trang rong ra du lieu).
 *   - Moi man hinh danh sach admin deu phai co thanh phan trang.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/helpers/functions.php';

echo 'PHP ' . PHP_VERSION . "\n";

$goc = __DIR__ . '/../';

/** Dat lai $_GET roi goi phan_trang() */
function catTrang(array $rows, array $get, $macDinh = 20){
    $_GET = $get;
    return phan_trang($rows, $macDinh);
}

$data = [];
for ($i = 1; $i <= 95; $i++) $data[] = ['id' => $i];

// ---------------------------------------------------------------------------
section('Cat trang co ban');

$pg = catTrang($data, []);
ok($pg['perPage'] === 20,      'Khong truyen gi -> lay mac dinh 20');
ok(count($pg['rows']) === 20,  'Trang 1 co dung 20 dong', 'co ' . count($pg['rows']));
ok($pg['total'] === 95,        'total la TONG tat ca chu khong phai so dong tren trang');
ok($pg['totalPages'] === 5,    'ceil(95/20) = 5 trang', 'duoc ' . $pg['totalPages']);
ok($pg['from'] === 1 && $pg['to'] === 20, 'Trang 1 hien 1-20');

$pg = catTrang($data, ['page' => 2]);
$dau = reset($pg['rows']);
ok((int) $dau['id'] === 21,    'Trang 2 bat dau tu ban ghi thu 21');
ok($pg['from'] === 21 && $pg['to'] === 40, 'Trang 2 hien 21-40');

$pg = catTrang($data, ['page' => 5]);
ok(count($pg['rows']) === 15,  'Trang cuoi chi con 15 dong');
ok($pg['to'] === 95,           'Trang cuoi ket thuc dung o 95');

// ---------------------------------------------------------------------------
section('Cot STT phai chay lien mach giua cac trang');

$pg = catTrang($data, ['page' => 3]);
$khoa = array_keys($pg['rows']);
ok($khoa[0] === 40, 'Khoa mang GIU NGUYEN (trang 3 bat dau tu khoa 40)',
   'duoc ' . $khoa[0] . ' — danh so lai thi {{$key+1}} se in "1" o dau moi trang');

// ---------------------------------------------------------------------------
section('Xem tat ca');

$pg = catTrang($data, ['per_page' => 'all']);
ok($pg['perPage'] === 0,        '"all" -> perPage = 0');
ok(count($pg['rows']) === 95,   'Chon Tat ca thi lay het 95 dong');
ok($pg['totalPages'] === 1,     'Tat ca -> dung 1 trang');
ok($pg['from'] === 1 && $pg['to'] === 95, 'Tat ca hien 1-95');

// ---------------------------------------------------------------------------
section('Gia tri la tren URL khong duoc tin theo');

foreach (['99999', '-5', '0', 'abc', '15'] as $la){
    $pg = catTrang($data, ['per_page' => $la]);
    ok($pg['perPage'] === 20, "?per_page=$la roi ve mac dinh 20", 'duoc ' . $pg['perPage']);
}
foreach (phan_trang_muc() as $m){
    $pg = catTrang($data, ['per_page' => (string) $m]);
    ok($pg['perPage'] === $m, "?per_page=$m duoc chap nhan");
}

// ---------------------------------------------------------------------------
section('Trang vuot nguong / danh sach rong');

$pg = catTrang($data, ['page' => 999]);
ok($pg['page'] === 5,          'Trang vuot qua cuoi thi kep ve trang cuoi', 'duoc ' . $pg['page']);
ok(count($pg['rows']) === 15,  'Va van ra dung du lieu trang cuoi');

$pg = catTrang($data, ['page' => -3]);
ok($pg['page'] === 1,          'Trang am kep ve 1');
ok(count($pg['rows']) === 20,  'Trang am van ra trang 1, khong phai cat nguoc tu cuoi mang');

$pg = catTrang([], []);
ok($pg['total'] === 0,         'Danh sach rong: total = 0');
ok($pg['totalPages'] === 1,    'Danh sach rong van la 1 trang (khong phai 0)',
   'totalPages = 0 se lam $page = 0 roi offset am');
ok($pg['from'] === 0,          'Danh sach rong: from = 0 chu khong phai 1');
ok($pg['rows'] === [],         'Danh sach rong tra ve mang rong');

// ---------------------------------------------------------------------------
section('Chuoi query giu bo loc, bo tham so cua chinh phan trang');

$_GET = ['module' => 'admin/quotations', 'page' => '3', 'per_page' => '50',
         'status' => 'sent', 'from' => '2026-01-01'];
$qs = phan_trang_qs();
parse_str($qs, $p);
ok(!isset($p['module']),   'Bo `module` (do rewrite sinh ra, khong phai bo loc)');
ok(!isset($p['page']),     'Bo `page`');
ok(!isset($p['per_page']), 'Bo `per_page`');
ok(($p['status'] ?? '') === 'sent',       'Giu bo loc `status`');
ok(($p['from'] ?? '') === '2026-01-01',   'Giu bo loc `from`');

// ---------------------------------------------------------------------------
section('Thanh phan trang (HTML)');

$_GET = ['module' => 'admin/quotations', 'status' => 'sent'];
$pgv  = phan_trang($data, 20);
$html = phan_trang_html($pgv, 'http://x/admin/quotations', 'báo giá');

ok(strpos($html, 'js-per-page') !== false,   'Co o chon so dong/trang');
ok(strpos($html, '>Tất cả<') !== false,      'O chon co muc "Tat ca"');
ok(strpos($html, 'status=sent') !== false,   'Link trang giu nguyen bo loc dang ap');
ok(strpos($html, 'page=2') !== false,        'Co link sang trang 2');
ok(strpos($html, 'module=') === false,       'Link KHONG keo theo tham so `module`');
ok(strpos($html, '1–20 / 95') !== false,     'Co dong "1-20 / 95"');

$html1 = phan_trang_html(phan_trang(array_slice($data, 0, 5), 20), 'http://x/admin/a');
ok(strpos($html1, '<ul class="pagination') === false,
   'Chi 1 trang thi khong ve day so trang (nhung van con o chon so dong)');
ok(strpos($html1, 'js-per-page') !== false, '...o chon so dong van con');

$_GET = ['module' => 'admin/partners'];
$html2 = phan_trang_html(phan_trang($data, 20));
ok(strpos($html2, 'admin/partners') !== false,
   'Khong truyen baseUrl thi tu lay URL dang mo');

// XSS: baseUrl / bo loc di thang vao href
$_GET = ['module' => 'admin/a', 'q' => '"><script>alert(1)</script>'];
$html3 = phan_trang_html(phan_trang($data, 20), 'http://x/admin/a');
ok(strpos($html3, '<script>alert(1)') === false,
   'Gia tri bo loc doc hai bi escape truoc khi vao href');

// ---------------------------------------------------------------------------
section('Moi man hinh danh sach admin deu co phan trang');

$views = glob($goc . 'app/views/admin/*/lists.php');
ok(count($views) >= 35, 'Quet duoc >= 35 view danh sach', 'thay ' . count($views));

$thieu = [];
foreach ($views as $v){
    $s = file_get_contents($v);
    // View khong dung $dataList (chat, groups, users) co cau truc rieng — bo qua
    if (strpos($s, 'dataList') === false) continue;
    if (strpos($s, 'phan_trang') === false) $thieu[] = basename(dirname($v));
}
ok(empty($thieu), 'Khong view nao con thieu thanh phan trang',
   'thieu: ' . implode(', ', $thieu));

// Hai man hinh cat duoi CSDL phai truyen perPage tu URL xuong, khong dung so cung
foreach (['Products', 'Customers'] as $c){
    $code = codeOnly($goc . 'app/controllers/admin/' . $c . '.php');
    ok(strpos($code, 'phan_trang_so_dong') !== false,
       "$c doc so dong/trang tu URL (?per_page=)");
    ok(strpos($code, '$this->perPage)') !== false || strpos($code, '$this->perPage;') !== false,
       "$c van giu gia tri mac dinh cua rieng no");
}

// LIMIT 0 trong MySQL la KHONG dong nao -> "Tat ca" phai quy ra so lon
$codeKh = codeOnly($goc . 'app/controllers/admin/Customers.php');
ok(strpos($codeKh, 'PHP_INT_MAX') !== false,
   'Customers: chon "Tat ca" khong truyen LIMIT 0 xuong MySQL');

// ---------------------------------------------------------------------------
section('JS xu ly o chon so dong/trang');

$js = file_get_contents($goc . 'public/assets/js/admin.js');
ok(strpos($js, 'js-per-page') !== false,     'admin.js co bat o chon so dong/trang');
ok(strpos($js, "p.delete('page')") !== false,
   'Doi so dong/trang thi ve trang 1 (trang 9 cua muc 10 khong ton tai o muc 100)');

exit(summary());

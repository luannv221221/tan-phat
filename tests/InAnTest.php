<?php
/**
 * Test BIEU MAU IN bao gia / hoa don (19/08/2026).
 *
 * Chay:  C:\xampp\php\php.exe tests\InAnTest.php
 *
 * Trong tam:
 *   - doc_so_tien(): dong bat buoc tren hoa don Viet Nam, luat doc co nhieu
 *     ngoai le (muoi lam / hai muoi mot / hai muoi tu / mot tram linh bon).
 *     Sai o day la sai giay to dua cho khach.
 *   - Logo phai NHUNG vao file chu khong phai link: file Word tai ve se duoc
 *     mo o may khac, luc do <img src="http://localhost/..."> chi ra o anh vo.
 *   - Bao gia KHONG in so tai khoan (chua phai chung tu doi tien), hoa don thi co.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/helpers/functions.php';

$goc = __DIR__ . '/../';
echo 'PHP ' . PHP_VERSION . "\n";

// ---------------------------------------------------------------------------
section('doc_so_tien() — so tien bang chu');

$ca = [
    0        => 'Không đồng',
    5        => 'Năm đồng',
    10       => 'Mười đồng',
    15       => 'Mười lăm đồng',          // khong phai "muoi nam"
    21       => 'Hai mươi mốt đồng',      // khong phai "hai muoi mot"
    24       => 'Hai mươi tư đồng',
    25       => 'Hai mươi lăm đồng',
    100      => 'Một trăm đồng',
    104      => 'Một trăm linh bốn đồng', // "bon" chu khong phai "tu"
    105      => 'Một trăm linh năm đồng',
    1000     => 'Một nghìn đồng',
    10000    => 'Mười nghìn đồng',
    100000   => 'Một trăm nghìn đồng',
    590000   => 'Năm trăm chín mươi nghìn đồng',
    1180000  => 'Một triệu một trăm tám mươi nghìn đồng',
    2000000  => 'Hai triệu đồng',
    1000000000 => 'Một tỷ đồng',
];
foreach ($ca as $n => $mong){
    ok(doc_so_tien($n) === $mong, "doc_so_tien($n)", 'duoc: ' . doc_so_tien($n) . ' | mong: ' . $mong);
}

/* Nhom giua phai doc DU 3 chu so. 1.309.000 ma bo "linh" di thi thanh
   "ba tram chin nghin" — lech han mot bac. */
ok(doc_so_tien(1309000) === 'Một triệu ba trăm linh chín nghìn đồng',
   'Nhom giua doc du 3 chu so (1.309.000)', 'duoc: ' . doc_so_tien(1309000));

ok(doc_so_tien(12548800) === 'Mười hai triệu năm trăm bốn mươi tám nghìn tám trăm đồng',
   'So nhieu bac (12.548.800)', 'duoc: ' . doc_so_tien(12548800));

/* Nhom giua co hang tram = 0 thi VAN phai doc "khong tram". Day moi la ca
   bat duoc loi bo mat nhanh $day — 1.309.000 khong bat duoc vi hang tram
   cua nhom giua khac 0 nen doc dung ca khi thieu nhanh do. */
ok(doc_so_tien(1050000) === 'Một triệu không trăm năm mươi nghìn đồng',
   'Nhom giua khong co hang tram (1.050.000)', 'duoc: ' . doc_so_tien(1050000));
ok(doc_so_tien(1005000) === 'Một triệu không trăm linh năm nghìn đồng',
   'Nhom giua chi co hang don vi (1.005.000)', 'duoc: ' . doc_so_tien(1005000));
ok(doc_so_tien(1000005) === 'Một triệu không trăm linh năm đồng',
   'Nhom cuoi chi co hang don vi (1.000.005)', 'duoc: ' . doc_so_tien(1000005));

// Lam tron ve dong, khong de ra "0.5 dong"
ok(doc_so_tien(1000.4) === 'Một nghìn đồng', 'Lam tron xuong');
ok(doc_so_tien(999.6)  === 'Một nghìn đồng', 'Lam tron len');

ok(mb_substr(doc_so_tien(15), 0, 1, 'UTF-8') === 'M', 'Viet hoa chu cai dau');

// ---------------------------------------------------------------------------
section('logo_in_an() — logo nhung vao chung tu');

$logo = logo_in_an([]);
ok(strpos($logo, 'data:image/') === 0,
   'Tra ve data URI chu khong phai duong dan',
   'File Word mo o may khac ma tro URL http://localhost thi mat logo');
ok(strlen($logo) > 1000, 'Co du lieu anh that', 'do dai: ' . strlen($logo));

// Logo da cau hinh thi phai duoc uu tien
$mac = logo_in_an([]);
ok(logo_in_an(['logo' => 'khong-co-file-nay.png']) === '',
   'Duong dan cau hinh sai -> tra ve rong, khong vo trang');
ok(logo_in_an(['logo' => 'public/assets/storefront/images/logo.png']) === $mac,
   'Duong dan cau hinh dung -> doc dung file do');

// ---------------------------------------------------------------------------
section('Man hinh Cau hinh dat duoc logo va ma so thue');

/* Truoc day layout trang ban hang doc $settings['logo'] nhung KHONG man hinh
   nao dat duoc -> luon rong. tax_code thi in tren hoa don ma muon sua phai
   vao thang CSDL. */
$ctrl = codeOnly($goc . 'app/controllers/admin/Settings.php');
foreach (['logo', 'tax_code'] as $k){
    ok(preg_match("~'" . $k . "'~", $ctrl) === 1, "Settings: khoa `$k` nam trong whitelist");
}
ok(strpos($ctrl, "upload_image('logo_file'") !== false, 'Settings: nhan upload file logo');
ok(preg_match("~elseif \(isset\(\\\$f\['logo'\]\)\)~", $ctrl) === 1,
   'Settings: chi ghi de logo khi that su co anh moi / co gia tri gui len',
   'Ghi de vo dieu kien la moi lan bam Luu lai xoa trang logo da tai len');

$form = file_get_contents($goc . 'app/views/admin/settings/form.php');
ok(strpos($form, 'name="logo_file"') !== false, 'Form cau hinh co o tai logo');
ok(strpos($form, 'name="tax_code"') !== false,  'Form cau hinh co o ma so thue');

// ---------------------------------------------------------------------------
section('Bieu mau in');

$mau = file_get_contents($goc . 'app/views/admin/print/chung-tu.php');

ok(strpos($mau, '@page') !== false && strpos($mau, 'A4') !== false, 'Dat kho giay A4');
ok(strpos($mau, '@media print') !== false, 'Co CSS rieng cho luc in');
ok(preg_match('~@media print\s*\{[^}]*\}[\s\S]*?\.thanh-cong-cu\s*\{\s*display:\s*none~', $mau) === 1
   || strpos($mau, '.thanh-cong-cu { display: none !important; }') !== false,
   'In ra KHONG co thanh cong cu (nut In / Tai Word)');
ok(strpos($mau, 'display: table-header-group') !== false,
   'Bang dai qua 1 trang thi lap lai tieu de cot o trang sau');
ok(strpos($mau, 'logo_in_an(') !== false, 'Nhung logo qua logo_in_an()');
ok(strpos($mau, 'doc_so_tien(') !== false, 'Co dong "So tien bang chu"');
ok(strpos($mau, 'echo e($logo)') !== false, 'Duong dan logo duoc escape truoc khi vao thuoc tinh');

// Khoi ky ten — giay to dua cho khach phai co cho ky
ok(strpos($mau, "\$ct['nhanKy'][0]") !== false && strpos($mau, "\$ct['nhanKy'][1]") !== false,
   'Co hai o ky ten, nhan do chung tu quyet dinh');

// ---------------------------------------------------------------------------
section('Controller xuat bieu mau');

foreach ([['Quotations', 'BÁO GIÁ', 'false'], ['Salesinvoices', 'HOÁ ĐƠN BÁN HÀNG', 'true']] as $c){
    $code = codeOnly($goc . 'app/controllers/admin/' . $c[0] . '.php');

    ok(preg_match('~function\s+inAn\s*\(~', $code) === 1, "$c[0]: co ham inAn()");
    ok(strpos($code, "'loai'         => '" . $c[1] . "'") !== false,
       "$c[0]: tieu de chung tu la \"$c[1]\"");
    ok(strpos($code, "'hienNganHang' => " . $c[2]) !== false,
       "$c[0]: " . ($c[2] === 'true' ? 'CO' : 'KHONG') . " in so tai khoan",
       'Bao gia chua phai chung tu doi tien nen khong in so tai khoan');
    ok(strpos($code, 'header_word(') !== false, "$c[0]: xuat duoc ban Word");
    ok(strpos($code, "!empty(\$f['in'])") !== false,
       "$c[0]: chi tu bat hop thoai In khi den tu nut In (?in=1)",
       'Mo thang link ma tu nhay hop thoai in thi khong kip soat lai chung tu');
}

// ---------------------------------------------------------------------------
section('Route + nut bam');

$routes = file_get_contents($goc . 'routes/web.php');
ok(strpos($routes, "quotations/print/(\\d+)") !== false,     'Co route in bao gia');
ok(strpos($routes, "sales-invoices/print/(\\d+)") !== false,  'Co route in hoa don');

foreach (['quotations/edit', 'sales-invoices/edit'] as $v){
    $s = file_get_contents($goc . 'app/views/admin/' . $v . '.php');
    ok(strpos($s, "/print/'.\$item['id'].'?in=1'") !== false, "$v: co nut In / Luu PDF");
    ok(strpos($s, "/print/'.\$item['id'].'?word=1'") !== false, "$v: co nut Tai Word");
}

// Hoa don da ghi so van phai in duoc — do moi la luc khach can to giay
$hd = file_get_contents($goc . 'app/views/admin/sales-invoices/edit.php');
ok(substr_count($hd, "?in=1") >= 2,
   'Hoa don: in duoc ca khi con nhap lan khi da ghi so',
   'Ghi so xong moi la luc dua chung tu cho khach');

// ---------------------------------------------------------------------------
section('Khong keo them thu vien nang');

$composer = json_decode(file_get_contents($goc . 'composer.json'), true);
ok(empty($composer['require']),
   'Van khong phu thuoc goi composer nao',
   'Dung trinh duyet in ra PDF thay vi nhung dompdf/mPDF (~20-30MB + font Unicode rieng)');

exit(summary());

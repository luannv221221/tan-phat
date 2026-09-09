<?php
/**
 * Test BIỂN SỐ XE + SỐ KM trên báo giá và hoá đơn bán.
 *
 * Chạy:  C:\xampp\php\php.exe tests\XeTrenChungTuTest.php
 *
 * Gara sửa xe thì chứng từ phải nói rõ nó cho CHIẾC XE NÀO. Bốn chỗ hỏng sẽ
 * âm thầm — mỗi chỗ một khẳng định riêng:
 *
 *   1. Chuẩn hoá biển số bị làm HAI BẢN khác nhau.
 *      Màn CSKH lưu "30A12345", chứng từ lưu "30a-123.45" — tra không ra nhau,
 *      mà chẳng có lỗi nào bật ra. Phải dùng chung ĐÚNG MỘT hàm.
 *
 *   2. Chuyển báo giá sang hoá đơn làm rơi mất xe.
 *      Báo giá ghi rõ biển số, còn hoá đơn — thứ khách thực sự cầm về — trống
 *      trơn. convert() phải chép cả ba cột.
 *
 *   3. Số km "100.000" bị đọc thành 100.
 *      Người Việt gõ dấu chấm ngăn nghìn. Ép (int) thẳng là ra 100.
 *
 *   4. Có trong CSDL nhưng KHÔNG in ra giấy.
 *      Gara cầm tờ phiếu lên mà không biết nó của xe nào thì lưu để làm gì.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Chuan hoa bien so — MOT ban duy nhat');

require_once $goc . 'app/helpers/functions.php';

ok(function_exists('chuan_hoa_bien_so'), 'Co helper chuan_hoa_bien_so()');
foreach ([
    '40G-474.89' => '40G47489',
    ' 40g 474 89' => '40G47489',
    '40G47489'   => '40G47489',
    '30A-123.45' => '30A12345',
    ''           => '',
] as $vao => $ra){
    ok(chuan_hoa_bien_so($vao) === $ra,
       'chuan_hoa_bien_so("' . $vao . '") = "' . $ra . '"',
       'Ra "' . chuan_hoa_bien_so($vao) . '"');
}

/* Model PHẢI gọi lại helper, không được tự cài lại. Hai bản khác nhau thì màn
   CSKH và chứng từ chuẩn hoá lệch nhau, tra mãi không ra. */
$mv = codeOnly($goc . 'app/models/MemberVehiclesModel.php');
ok(strpos($mv, 'chuan_hoa_bien_so') !== false,
   'MemberVehiclesModel goi lai helper chung');
ok(!preg_match('~function chuanHoaBienSo.*?preg_replace~s', $mv),
   'MemberVehiclesModel KHONG tu cai lai phep chuan hoa',
   'Hai ban khac nhau la CSKH va chung tu tra khong ra nhau');

require_once $goc . 'app/models/MemberVehiclesModel.php';
ok(MemberVehiclesModel::chuanHoaBienSo('40g-474.89') === chuan_hoa_bien_so('40g-474.89'),
   'Model va helper cho ra CUNG mot ket qua');

// ---------------------------------------------------------------------------
section('Migration dung hinh');

$mg = glob($goc . 'database/migrations/*_bien_so_so_km_tren_chung_tu.php');
ok(!empty($mg), 'Co migration them bien so / so km');
$src = !empty($mg) ? file_get_contents($mg[0]) : '';
ok(strpos($src, "'quotations'") !== false && strpos($src, "'sales_invoices'") !== false,
   'Them cho CA HAI: bao gia va hoa don',
   'Chi mot ben thi chuyen bao gia sang hoa don la roi mat xe');
ok(strpos($src, 'bien_so_chuan') !== false, 'Co cot chuan hoa rieng');
ok(strpos($src, 'idx_') !== false && strpos($src, 'bien_so_chuan') !== false,
   'Chi muc dat tren cot CHUAN HOA',
   'Dat tren cot goc thi tra cuu khong dung duoc chi muc');

// ---------------------------------------------------------------------------
section('Controller luu va chep dung');

foreach (['Quotations', 'Salesinvoices'] as $ten){
    $ctl = codeOnly($goc . 'app/controllers/admin/' . $ten . '.php');
    $than = '';
    if (preg_match('~private function headerData\(\$f\)\s*\{(.*?)\n    \}~s', $ctl, $m)) $than = $m[1];

    ok($than !== '', "$ten: doc duoc headerData()");
    ok(strpos($than, "'bien_so'") !== false && strpos($than, "'so_km'") !== false,
       "$ten: headerData() luu bien so va so km");
    ok(strpos($than, 'chuan_hoa_bien_so') !== false,
       "$ten: chuan hoa bien so NGAY LUC LUU",
       'Chuan hoa luc doc thi tra cuu phai quet ca bang, khong dung duoc chi muc');
    ok(preg_match("~preg_replace\('/\[\^\\\\d\]/'~", $than) === 1,
       "$ten: so km bo dau ngan nghin truoc khi ep so",
       'Ep (int) thang thi "100.000" thanh 100');
}

/* convert() phải chép xe sang hoá đơn */
$q = codeOnly($goc . 'app/controllers/admin/Quotations.php');
$thanConvert = '';
if (preg_match('~public function convert\(\$id\)\s*\{(.*?)\n    \}~s', $q, $m)) $thanConvert = $m[1];
ok($thanConvert !== '', 'Doc duoc than ham convert()');
ok(strpos($thanConvert, "'bien_so'") !== false && strpos($thanConvert, "'so_km'") !== false,
   'convert() chep xe sang hoa don',
   'Bao gia ghi ro xe ma hoa don — thu khach cam ve — lai trong tron');

// ---------------------------------------------------------------------------
section('In ra giay');

$in = file_get_contents($goc . 'app/views/admin/print/chung-tu.php');
ok(strpos($in, "\$ct['bienSo']") !== false, 'Mau in co in bien so');
ok(strpos($in, "\$ct['soKm']") !== false,   'Mau in co in so km');
ok(strpos($in, "!empty(\$ct['bienSo']) || !empty(\$ct['soKm'])") !== false,
   'Ca hai trong thi khong ve dong nao',
   'Ban le phu tung qua quay thi khong co xe — dung in mot dong rong');

foreach (['Quotations', 'Salesinvoices'] as $ten){
    $ctl = codeOnly($goc . 'app/controllers/admin/' . $ten . '.php');
    ok(strpos($ctl, "'bienSo'") !== false && strpos($ctl, "'soKm'") !== false,
       "$ten: truyen bien so / so km sang mau in");
}

// ---------------------------------------------------------------------------
section('Bon form deu co o nhap');

foreach ([
    'quotations/add', 'quotations/edit',
    'sales-invoices/add', 'sales-invoices/edit',
] as $v){
    $noiDung = file_get_contents($goc . 'app/views/admin/' . $v . '.php');
    ok(strpos($noiDung, 'name="bien_so"') !== false && strpos($noiDung, 'name="so_km"') !== false,
       "Form $v co du hai o");
}

// ---------------------------------------------------------------------------
section('Chay that tren MySQL');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

foreach (['quotations', 'sales_invoices'] as $bang){
    $cot = $pdo->query("SHOW COLUMNS FROM `$bang`")->fetchAll(PDO::FETCH_COLUMN);
    foreach (['bien_so', 'bien_so_chuan', 'so_km'] as $c){
        ok(in_array($c, $cot, true), "Bang `$bang` co cot `$c`");
    }
    $idx = $pdo->query("SHOW INDEX FROM `$bang`")->fetchAll(PDO::FETCH_ASSOC);
    $co = false;
    foreach ($idx as $i) if ($i['Column_name'] === 'bien_so_chuan') $co = true;
    ok($co, "Bang `$bang` co chi muc tren bien_so_chuan");
}

require_once $goc . 'app/models/QuotationsModel.php';
$Q = new QuotationsModel();

/* Lưu đúng như controller làm: giữ nguyên văn + chuẩn hoá + km bỏ dấu chấm */
$goVao  = '40g-474.89';
$kmGo   = '100.000';
$id = $Q->add([
    'quote_no'      => 'ZZ-XE-' . time(),
    'customer_id'   => null,
    'quote_date'    => date('Y-m-d'),
    'vat_rate'      => 10,
    'subtotal'      => 0, 'tax_amount' => 0, 'total_amount' => 0,
    'status'        => 'draft',
    'bien_so'       => $goVao,
    'bien_so_chuan' => chuan_hoa_bien_so($goVao),
    'so_km'         => (int) preg_replace('/[^\d]/', '', $kmGo),
]);
ok($id > 0, 'Luu duoc bao gia co xe');

$r = $Q->getDetail($id);
ok($r['bien_so'] === $goVao,
   'Giu NGUYEN VAN bien so nguoi go (de in ra cho dep)');
ok($r['bien_so_chuan'] === '40G47489',
   'Luu kem ban chuan hoa de tra cuu', 'Dang la "' . $r['bien_so_chuan'] . '"');
ok((int) $r['so_km'] === 100000,
   'So km "100.000" doc thanh 100000, khong phai 100',
   'Dang la ' . $r['so_km']);

/* Tra theo biển số gõ kiểu khác vẫn phải ra */
foreach (['40G47489', '40g47489', '40G-474.89', '40g 474 89'] as $go){
    $st = $pdo->prepare("SELECT COUNT(*) FROM `quotations` WHERE `bien_so_chuan` = ? AND `id` = ?");
    $st->execute([chuan_hoa_bien_so($go), $id]);
    ok((int) $st->fetchColumn() === 1, 'Tra bang "' . $go . '" ra dung phieu do');
}

/* Để trống phải ra NULL, không phải chuỗi rỗng — cột có chỉ mục, chuỗi rỗng
   làm bẩn chỉ mục và khiến "phiếu không có xe" lẫn với "xe biển rỗng". */
$id2 = $Q->add([
    'quote_no'      => 'ZZ-XE2-' . time(),
    'customer_id'   => null,
    'quote_date'    => date('Y-m-d'),
    'vat_rate'      => 0,
    'subtotal'      => 0, 'tax_amount' => 0, 'total_amount' => 0,
    'status'        => 'draft',
    'bien_so'       => null, 'bien_so_chuan' => null, 'so_km' => null,
]);
$r2 = $Q->getDetail($id2);
ok($r2['bien_so'] === null && $r2['so_km'] === null,
   'Ban le khong co xe thi ba cot deu NULL');

// Dọn sạch
$pdo->prepare("DELETE FROM `quotations` WHERE id IN (?, ?)")->execute([$id, $id2]);
ok(empty($Q->getDetail($id)) && empty($Q->getDetail($id2)), 'Da don sach du lieu test');

exit(summary());

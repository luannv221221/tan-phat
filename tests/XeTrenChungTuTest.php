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
    /* Chuẩn hoá biển số + bỏ dấu ngăn nghìn giờ nằm trong lien_ket_xe():
       MỘT bản dùng cho báo giá, hoá đơn và phiếu bảo hành, kèm việc nối
       chứng từ vào bản ghi xe. Thân hàm đó được soi riêng ở dưới. */
    ok(strpos($than, 'lien_ket_xe($f)') !== false,
       "$ten: headerData() goi lien_ket_xe() — mot cho duy nhat dung bien so",
       'Hai noi chuan hoa hai kieu la tra khong ra nhau');
    ok(strpos($than, "'vehicle_id'") !== false,
       "$ten: luu ca vehicle_id — chung tu noi vao ban ghi xe",
       'Chi luu chuoi bien so thi tra lich su xe la ghep chuoi');
}

/* Thân hàm nối dùng chung: nơi duy nhất chuẩn hoá biển số và đọc số km */
$hlp   = codeOnly($goc . 'app/helpers/functions.php');
$thanLK = '';
if (preg_match('~function lien_ket_xe\(\$f\)\s*\{(.*?)\n\}~s', $hlp, $m)) $thanLK = $m[1];
ok($thanLK !== '', 'Doc duoc than ham lien_ket_xe()');
ok(strpos($thanLK, 'chuan_hoa_bien_so') !== false,
   'lien_ket_xe(): chuan hoa bien so NGAY LUC LUU',
   'Chuan hoa luc doc thi tra cuu phai quet ca bang, khong dung duoc chi muc');
ok(preg_match("~preg_replace\('/\[\^\\\\d\]/'~", $thanLK) === 1,
   'lien_ket_xe(): so km bo dau ngan nghin truoc khi ep so',
   'Ep (int) thang thi "100.000" thanh 100');
ok(strpos($thanLK, 'capNhatKm') !== false,
   'lien_ket_xe(): so km moi hon thi cap nhat cho XE',
   'Khong cap nhat thi moc nhac bao tri cua xe dung im o so cu');

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
/* Gara độc lập: chứng từ phải thuộc một gara (garage_id NOT NULL). Dòng lệnh
   không có gara làm việc nên lớp Model không tự ghi — ép gara tổng. */
\App\core\Model::epGara((int) $pdo->query("SELECT id FROM garages WHERE is_master = 1 ORDER BY id LIMIT 1")->fetchColumn());

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

// ---------------------------------------------------------------------------
section('O chon xe theo khach — mot khach nhieu xe');

/* Một khách nhiều xe: gõ tay biển số là sai chính tả lúc nào không biết, và
   chứng từ không gắn được vào xe nào. Chọn khách xong phải chọn được xe TRONG
   DANH SÁCH XE CỦA KHÁCH ĐÓ. Số km thì KHÔNG điền hộ — số trên chứng từ là số
   đọc trên đồng hồ lúc xe vào, chỉ nhắc xe đang ghi bao nhiêu. */

require_once $goc . 'app/models/VehiclesModel.php';
require_once $goc . 'app/models/PartnersModel.php';
$XE = new VehiclesModel();
$KH = new PartnersModel();

$khId = (int) $KH->add(['code' => 'ZZ-XEK-' . time(), 'name' => 'ZZ Khach nhieu xe', 'type' => 'customer',
                        'phone' => '0900000111', 'status' => 1, 'sort_order' => 0]);
$xe1 = (int) $XE->add(['partner_id' => $khId, 'bien_so' => '30A-777.11', 'bien_so_chuan' => chuan_hoa_bien_so('30A-777.11'),
                       'hang_xe' => 'Toyota', 'model_xe' => 'Vios', 'nam_sx' => 2019, 'so_km' => 41000, 'status' => 1]);
$xe2 = (int) $XE->add(['partner_id' => $khId, 'bien_so' => '30A-777.22', 'bien_so_chuan' => chuan_hoa_bien_so('30A-777.22'),
                       'hang_xe' => 'Kia', 'model_xe' => 'Morning', 'nam_sx' => 2021, 'so_km' => null, 'status' => 1]);
$khTrong = (int) $KH->add(['code' => 'ZZ-XEK0-' . time(), 'name' => 'ZZ Khach chua khai xe', 'type' => 'customer',
                           'phone' => '0900000222', 'status' => 1, 'sort_order' => 0]);

$ds = $XE->chonTheoChu($khId);
ok(count($ds) === 2, 'chonTheoChu() tra ve dung 2 xe cua khach', 'ra ' . count($ds) . ' dong');
$bienSo = array_column($ds, 'c');
ok(in_array('30A-777.11', $bienSo, true) && in_array('30A-777.22', $bienSo, true),
   'Gia tri cua o chon la BIEN SO — lien_ket_xe() tra ra xe tu bien so',
   json_encode($bienSo, JSON_UNESCAPED_UNICODE));
$mot = null;
foreach ($ds as $x) if ($x['c'] === '30A-777.11') $mot = $x;
ok($mot !== null && strpos($mot['n'], '30A-777.11') === 0 && strpos($mot['n'], 'Toyota Vios 2019') !== false,
   'Nhan hien ra co bien so + hang / model / nam', $mot ? $mot['n'] : 'khong thay');
ok($mot !== null && $mot['km'] === 41000, 'Kem so km dang ghi de NHAC nguoi lap', $mot ? var_export($mot['km'], true) : '-');
foreach ($ds as $x) if ($x['c'] === '30A-777.22') ok($x['km'] === null, 'Xe chua ghi km thi km = null, khong phai 0');

ok($XE->chonTheoChu($khTrong) === [], 'Khach chua khai xe: tra ve rong (form quay ve o go tay)');
ok($XE->chonTheoChu(0) === [], 'Khong chon khach: tra ve rong');

/* Chặn theo gara: xe của gara khác không được lọt vào ô chọn */
$garaKhac = (int) $pdo->query("SELECT id FROM garages WHERE is_master = 0 ORDER BY id LIMIT 1")->fetchColumn();
if ($garaKhac > 0){
    $pdo->prepare("UPDATE `vehicles` SET `garage_id` = ? WHERE `id` = ?")->execute([$garaKhac, $xe2]);
    ok(count($XE->chonTheoChu($khId)) === 1,
       'Xe da chuyen sang gara khac thi khong con trong o chon',
       'O chon phai loc theo gara nhu moi truy van xe khac');
    $pdo->prepare("UPDATE `vehicles` SET `garage_id` = (SELECT id FROM garages WHERE is_master = 1 ORDER BY id LIMIT 1) WHERE `id` = ?")
        ->execute([$xe2]);
}

/* Đường JSON phải có route, không thì ô chọn nạp về trang 404 */
$rt = codeOnly($goc . 'routes/web.php');
ok(strpos($rt, 'vehicles/xe-theo-khach') !== false, 'Co route JSON vehicles/xe-theo-khach');
ok(strpos(codeOnly($goc . 'app/controllers/admin/Vehicles.php'), 'function xeTheoKhach') !== false,
   'Controller co ham xeTheoKhach()');

/* Sáu form phải có ô chọn, và CHỈ MỘT ô gửi biển số lên server */
foreach (['quotations/add', 'quotations/edit', 'sales-invoices/add', 'sales-invoices/edit',
          'warranty/add', 'warranty/edit'] as $v){
    $src = file_get_contents($goc . 'app/views/admin/' . $v . '.php');
    ok(strpos($src, 'data-xe-khach') !== false, "$v: co o chon xe theo khach");
    ok(strpos($src, 'js-xe-list') !== false && strpos($src, 'js-xe-go') !== false, "$v: co ca o chon va o go tay");
    ok(strpos($src, 'xe-cua-khach.js') !== false, "$v: co nap xe-cua-khach.js");
    ok(substr_count($src, 'name="bien_so"') === 1,
       "$v: CHI MOT o gui bien so len server",
       'Hai o cung ten la gui hai gia tri, gia tri sau de mat gia tri truoc');
    ok(strpos($src, 'js-xe-km') !== false, "$v: co cho nhac so km dang ghi cua xe");
}

$js = file_get_contents($goc . 'public/assets/js/xe-cua-khach.js');
ok(strpos($js, 'reception_id') !== false,
   'JS bo qua khi lap tu phieu tiep nhan — xe do phieu quyet dinh');
ok(!preg_match('~\.name\s*=~', $js),
   'JS KHONG gan name cho o chon — o go tay van la o duy nhat gui len');
ok(strpos($js, 'oGo.value = oList.value') !== false,
   'Chon xe thi dien bien so vao o gui len server');
ok(!preg_match('~oKm\.value\s*=~', $js),
   'JS KHONG dien ho so km — nguoi lap go so hien tai');

// Dọn sạch
$pdo->prepare("DELETE FROM `vehicles` WHERE id IN (?, ?)")->execute([$xe1, $xe2]);
$pdo->prepare("DELETE FROM `partners` WHERE id IN (?, ?)")->execute([$khId, $khTrong]);
ok(empty($XE->getDetail($xe1)) && empty($KH->getDetail($khId)), 'Da don sach khach / xe test');

exit(summary());

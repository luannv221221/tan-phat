<?php
/**
 * Test BIỂN SỐ XE + SỐ KM trên phiếu bảo hành.
 *
 * Chạy:  C:\xampp\php\php.exe tests\XeTrenBaoHanhTest.php
 *
 * VÌ SAO BẢO HÀNH CẦN CÁI NÀY CÒN HƠN CẢ BÁO GIÁ
 * `warranty_requests` có `technician`, `diagnosis`, `received_date`,
 * `appointment_date` — xe vào xưởng, thợ chẩn đoán, hẹn ngày trả. Bảo hành
 * một cái đĩa phanh mà không biết nó lắp trên xe nào thì gần như vô nghĩa.
 * Khi khách quay lại, BIỂN SỐ mới là thứ người ta đọc — không ai nhớ số
 * serial của phụ tùng đã thay sáu tháng trước.
 *
 * BỐN CHỖ HỎNG SẼ ÂM THẦM:
 *
 *   1. Lẫn `serial_no` với biển số. Serial là của PHỤ TÙNG, biển số là của
 *      XE. Hai thứ khác nhau hoàn toàn, không cột nào thay được cột nào.
 *
 *   2. Tra cứu so thẳng cột gốc. Người nhập mỗi lần một kiểu, gõ "30a12345"
 *      không ra "30A-123.45" — đúng xe đó mà máy báo không tìm thấy.
 *
 *   3. Từ khoá không có chữ số nào ("---") chuẩn hoá ra chuỗi rỗng, mà
 *      LIKE '%%' khớp MỌI dòng — tìm một dấu gạch ra cả bảng.
 *
 *   4. Có trong CSDL nhưng KHÔNG in ra biên bản. Khách giữ một bản; thiếu
 *      biển số thì sau sáu tháng không ai đối chiếu được nó của xe nào.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';
require_once $goc . 'app/helpers/functions.php';

// ---------------------------------------------------------------------------
section('Migration dung hinh');

$mg = glob($goc . 'database/migrations/*_bien_so_so_km_tren_bao_hanh.php');
ok(!empty($mg), 'Co migration them xe vao phieu bao hanh');
$src = !empty($mg) ? file_get_contents($mg[0]) : '';
ok(strpos($src, 'warranty_requests') !== false, 'Migration nham dung bang warranty_requests');
ok(strpos($src, 'bien_so_chuan') !== false, 'Co cot chuan hoa rieng');
ok(strpos($src, 'idx_wr_bien_so') !== false && strpos($src, '(`bien_so_chuan`)') !== false,
   'Chi muc dat tren cot CHUAN HOA',
   'Dat tren cot goc thi tra cuu khong dung duoc chi muc');

// ---------------------------------------------------------------------------
section('Controller luu dung');

$ctl = codeOnly($goc . 'app/controllers/admin/Warranty.php');
$than = '';
if (preg_match('~private function buildData\(\)\s*\{(.*?)\n    \}~s', $ctl, $m)) $than = $m[1];
ok($than !== '', 'Doc duoc than ham buildData()');
ok(strpos($than, "'bien_so'") !== false && strpos($than, "'so_km'") !== false,
   'buildData() luu bien so va so km');
ok(strpos($than, 'chuan_hoa_bien_so') !== false,
   'Chuan hoa bien so NGAY LUC LUU, dung ham chung');

/* serial_no PHẢI còn nguyên — biển số là cột THÊM, không phải cột thay */
ok(strpos($than, "'serial_no'") !== false,
   '`serial_no` VAN CON — bien so la cot them, khong phai cot thay',
   'Serial la cua PHU TUNG, bien so la cua XE');

ok(strpos($ctl, 'function soKm') !== false, 'Co ham doc so km rieng');
ok(preg_match("~preg_replace\('/\[\^\\\\d\]/'~", $ctl) === 1,
   'So km bo dau ngan nghin truoc khi ep so',
   'Ep (int) thang thi "85.000" thanh 85');

// ---------------------------------------------------------------------------
section('Tra cuu theo bien so');

$mdl = codeOnly($goc . 'app/models/WarrantyRequestsModel.php');
ok(strpos($mdl, 'bien_so_chuan') !== false,
   'getLists() tra tren cot CHUAN HOA');
ok(strpos($mdl, 'chuan_hoa_bien_so($keyword)') !== false,
   'Tu khoa cung duoc chuan hoa truoc khi so',
   'Chi chuan hoa mot ben thi go "30a12345" khong ra "30A-123.45"');
ok(strpos($mdl, '"\x00"') !== false || strpos($mdl, "\\x00") !== false,
   'Tu khoa khong co chu so nao thi KHONG khop moi dong',
   'chuan hoa "---" ra chuoi rong, LIKE \'%%\' khop ca bang');

/* Ba cách tra cũ không được mất */
foreach (['request_no', 'customer_name', 'phone', 'serial_no'] as $c){
    ok(strpos($mdl, 'warranty_requests.' . $c) !== false,
       "Van tra duoc theo `$c`");
}

// ---------------------------------------------------------------------------
section('Giao dien');

foreach (['add', 'edit'] as $v){
    $noiDung = file_get_contents($goc . 'app/views/admin/warranty/' . $v . '.php');
    ok(strpos($noiDung, 'name="bien_so"') !== false && strpos($noiDung, 'name="so_km"') !== false,
       "Form $v co du hai o");
    ok(strpos($noiDung, 'khác biển số xe') !== false,
       "Form $v noi ro serial KHAC bien so",
       'Hai o canh nhau, khong noi ro la nguoi dung go lan');
}

$ds = file_get_contents($goc . 'app/views/admin/warranty/lists.php');
ok(strpos($ds, '>Xe</th>') !== false, 'Danh sach co cot Xe');
ok(strpos($ds, "\$item['bien_so']") !== false, 'Cot Xe in bien so');
ok(strpos($ds, 'BIỂN SỐ XE') !== false,
   'Nhan o tim kiem noi ro tra duoc theo bien so',
   'Khong noi thi khong ai biet la tra duoc');
ok(strpos($ds, 'colspan="8"') !== false,
   'Dong "khong co du lieu" da noi rong theo cot moi',
   'Them cot ma quen colspan la bang lech han mot o');

$bb = file_get_contents($goc . 'app/views/admin/warranty/handover-print.php');
ok(strpos($bb, "\$item['bien_so']") !== false, 'Bien ban ban giao in bien so');
ok(strpos($bb, "!empty(\$item['bien_so']) || !empty(\$item['so_km'])") !== false,
   'Ca hai trong thi khong ve dong nao',
   'Bao hanh thiet bi cam tay khong co xe — dung in mot dong rong');

// ---------------------------------------------------------------------------
section('Chay that tren MySQL');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$cot = $pdo->query("SHOW COLUMNS FROM `warranty_requests`")->fetchAll(PDO::FETCH_COLUMN);
foreach (['bien_so', 'bien_so_chuan', 'so_km'] as $c){
    ok(in_array($c, $cot, true), "Bang co cot `$c`");
}
ok(in_array('serial_no', $cot, true), 'Cot `serial_no` cu VAN CON');
if (!in_array('bien_so', $cot, true)){
    echo "\n[SKIP] Chua chay migration.\n"; exit(summary());
}

$idx = $pdo->query("SHOW INDEX FROM `warranty_requests`")->fetchAll(PDO::FETCH_ASSOC);
$coIdx = false;
foreach ($idx as $i) if ($i['Column_name'] === 'bien_so_chuan') $coIdx = true;
ok($coIdx, 'Co chi muc tren bien_so_chuan');

require_once $goc . 'app/models/WarrantyRequestsModel.php';
$W = new WarrantyRequestsModel();

$demTruoc = count($W->getLists());

$goVao = '30a-123.45';
$id = $W->add([
    'request_no'    => 'ZZ-BH-' . time(),
    'customer_name' => 'ZZ Khach test xe',
    'phone'         => '0900111222',
    'product_name'  => 'ZZ Dia phanh',
    'serial_no'     => 'SN-ZZ-TEST',
    'received_date' => date('Y-m-d'),
    'status'        => 'received',
    'fee'           => 0,
    'bien_so'       => $goVao,
    'bien_so_chuan' => chuan_hoa_bien_so($goVao),
    'so_km'         => (int) preg_replace('/[^\d]/', '', '85.000'),
]);
ok($id > 0, 'Luu duoc phieu bao hanh co xe');

$r = $W->getDetail($id);
ok($r['bien_so'] === $goVao, 'Giu NGUYEN VAN bien so nguoi go');
ok($r['bien_so_chuan'] === '30A12345', 'Luu kem ban chuan hoa',
   'Dang la "' . $r['bien_so_chuan'] . '"');
ok((int) $r['so_km'] === 85000, 'So km "85.000" doc thanh 85000, khong phai 85',
   'Dang la ' . $r['so_km']);
ok($r['serial_no'] === 'SN-ZZ-TEST', 'Serial phu tung KHONG bi bien so ghi de');

/* --- Tra cứu: mọi cách gõ biển số đều ra --- */
$timThay = function($ds, $id){
    foreach ($ds as $x) if ((int) $x['id'] === (int) $id) return true;
    return false;
};
foreach (['30A-123.45', '30a12345', '30A 123 45', '30a-123.45'] as $go){
    ok($timThay($W->getLists('', '', '', $go), $id),
       'Tra bang "' . $go . '" ra dung phieu');
}

/* Ba cách tra cũ vẫn phải hoạt động */
ok($timThay($W->getLists('', '', '', '0900111222'), $id), 'Tra theo SDT van chay');
ok($timThay($W->getLists('', '', '', 'SN-ZZ-TEST'), $id), 'Tra theo serial van chay');
ok($timThay($W->getLists('', '', '', 'ZZ Khach test xe'), $id), 'Tra theo ten khach van chay');

/* --- Bẫy: từ khoá không có chữ số nào --- */
$raHet = $W->getLists('', '', '', '---');
ok(count($raHet) === 0,
   'Go "---" KHONG ra ca bang',
   'Ra ' . count($raHet) . ' dong — chuan hoa ra chuoi rong roi LIKE \'%%\' khop tat ca');
ok(!$timThay($W->getLists('', '', '', '99Z99999'), $id), 'Bien so khong ton tai thi khong ra');

/* --- Phiếu không có xe: ba cột NULL --- */
$id2 = $W->add([
    'request_no'    => 'ZZ-BH2-' . time(),
    'customer_name' => 'ZZ Khach khong xe',
    'product_name'  => 'ZZ May khoan cam tay',
    'received_date' => date('Y-m-d'),
    'status'        => 'received',
    'fee'           => 0,
    'bien_so'       => null, 'bien_so_chuan' => null, 'so_km' => null,
]);
$r2 = $W->getDetail($id2);
ok($r2['bien_so'] === null && $r2['so_km'] === null,
   'Bao hanh thiet bi cam tay thi ba cot deu NULL');
ok(!$timThay($W->getLists('', '', '', '30A12345'), $id2),
   'Phieu khong co xe KHONG lot vao ket qua tra bien so');

// Dọn sạch
$pdo->prepare("DELETE FROM `warranty_requests` WHERE id IN (?, ?)")->execute([$id, $id2]);
ok(count($W->getLists()) === $demTruoc, 'Da don sach du lieu test');

exit(summary());

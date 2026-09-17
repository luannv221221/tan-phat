<?php
/**
 * Test MÔ HÌNH: 1 KHÁCH → NHIỀU XE → 1 XE NHIỀU PHIẾU TIẾP NHẬN → chứng từ.
 *
 * Chạy:  C:\xampp\php\php.exe tests\XeVaPhieuTest.php
 *
 * SAI GỐC ĐANG SỬA
 * Biển số và số km từng là CHỮ GÕ TAY rời rạc trên từng báo giá / hoá đơn /
 * phiếu bảo hành. Không có bản ghi xe nào nối chúng lại, nên tra lịch sử một
 * chiếc xe là ghép chuỗi, và gõ hai kiểu là thành hai xe. Xe cũng từng gắn vào
 * `members` (tài khoản web) trong khi mọi chứng từ gara trỏ `partners`.
 *
 * SÁU CHỖ HỎNG SẼ ÂM THẦM:
 *
 *   1. Hai bản ghi cho cùng một xe. Biển số không duy nhất thì mỗi lần gõ khác
 *      nhau lại sinh một xe mới, lịch sử chia làm nhiều mảnh.
 *   2. Chứng từ không nối vào xe. Có bản ghi xe mà báo giá vẫn chỉ lưu chuỗi
 *      biển số thì vẫn không tra được lịch sử.
 *   3. Model của hãng khác / phường của tỉnh khác — ô chọn dây chuyền chỉ lọc ở
 *      trình duyệt, không kiểm ở server là lưu được dữ liệu không tồn tại.
 *   4. Đồng hồ km quay lui. Gõ thiếu một chữ số lúc tiếp nhận là kéo tụt mốc
 *      nhắc bảo trì của xe.
 *   5. Xoá xe / xoá phiếu đang có dữ liệu treo vào -> mất lịch sử sửa chữa.
 *   6. Sửa chứng từ làm nó rơi khỏi phiếu tiếp nhận (mã phiếu chỉ ghi lúc lập).
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc  = __DIR__ . '/../';
$base = 'http://localhost:88/tan-phat';

// ---------------------------------------------------------------------------
section('Migration + CSDL');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$bang = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
ok(in_array('vehicles', $bang, true), 'Co bang `vehicles` (xe cua khach)');
ok(in_array('receptions', $bang, true), 'Co bang `receptions` (phieu tiep nhan)');
if (!in_array('vehicles', $bang, true)){ echo "\n[SKIP] Chua chay migration 000072.\n"; exit(summary()); }

$idx = [];
foreach ($pdo->query("SHOW INDEX FROM vehicles") as $r) $idx[$r['Key_name']] = (int) $r['Non_unique'] === 0;
ok(!empty($idx['uq_vehicles_bien_so']), 'Bien so (chuan hoa) la DUY NHAT',
   'Khong duy nhat thi moi lan go khac nhau lai sinh mot xe moi');
ok(!empty($idx['uq_vehicles_so_khung']), 'So khung la duy nhat (cho phep nhieu NULL)');

$fk = [];
foreach ($pdo->query(
    "SELECT k.TABLE_NAME t, k.COLUMN_NAME c, k.REFERENCED_TABLE_NAME rt, r.DELETE_RULE d
       FROM information_schema.KEY_COLUMN_USAGE k
       JOIN information_schema.REFERENTIAL_CONSTRAINTS r
         ON r.CONSTRAINT_NAME = k.CONSTRAINT_NAME AND r.CONSTRAINT_SCHEMA = k.TABLE_SCHEMA
      WHERE k.TABLE_SCHEMA = DATABASE() AND k.TABLE_NAME IN ('vehicles','receptions')") as $r){
    $fk[$r['t'] . '.' . $r['c']] = ['den' => $r['rt'], 'xoa' => $r['d']];
}
ok(($fk['vehicles.partner_id']['den'] ?? '') === 'partners', 'Xe gan vao `partners` (Doi tuong) — noi moi chung tu gara tro vao');
ok(($fk['vehicles.partner_id']['xoa'] ?? '') === 'SET NULL', 'Xoa khach thi xe con lai, chi mat chu');
ok(($fk['receptions.vehicle_id']['den'] ?? '') === 'vehicles'
   && ($fk['receptions.vehicle_id']['xoa'] ?? '') === 'RESTRICT',
   'Phieu tiep nhan gan vao xe, xoa xe con phieu bi CHAN');

foreach (['quotations', 'sales_invoices', 'warranty_requests'] as $b){
    $cot = $pdo->query("SHOW COLUMNS FROM `$b`")->fetchAll(PDO::FETCH_COLUMN);
    ok(in_array('vehicle_id', $cot, true) && in_array('reception_id', $cot, true),
       "`$b` co cot noi ve xe va phieu tiep nhan");
}
ok(in_array('partner_id', $pdo->query("SHOW COLUMNS FROM members")->fetchAll(PDO::FETCH_COLUMN), true),
   'Tai khoan web (`members`) noi duoc vao Doi tuong',
   'Mot nguoi la MOT khach — truoc day hai bang khach khong co duong noi nao');

$quyen = [];
foreach ($pdo->query(
    "SELECT m.link, g.name nhom, GROUP_CONCAT(p.role ORDER BY p.role) roles
       FROM modules m JOIN permissions p ON p.module_id = m.id JOIN `groups` g ON g.id = p.group_id
      WHERE m.link IN ('vehicles','receptions') GROUP BY m.link, g.name") as $r){
    $quyen[$r['link'] . '|' . $r['nhom']] = $r['roles'];
}
ok(($quyen['vehicles|Staff'] ?? '') === 'add,edit,view', 'Staff khai duoc xe (khong xoa)',
   'Khong cho Staff khai xe thi ca quy trinh dung o quay: ' . ($quyen['vehicles|Staff'] ?? 'khong co quyen nao'));
ok(($quyen['receptions|Staff'] ?? '') === 'add,edit,view', 'Staff tiep nhan xe duoc (khong xoa)');
ok(strpos((string) ($quyen['receptions|Admin'] ?? ''), 'delete') !== false, 'Admin xoa duoc phieu');

$lac = (int) $pdo->query("SELECT COUNT(*) FROM warranty_requests
                          WHERE bien_so_chuan IS NOT NULL AND bien_so_chuan <> '' AND vehicle_id IS NULL")->fetchColumn();
ok($lac === 0, 'Bien so cu tren phieu bao hanh da duoc dung thanh xe va noi lai',
   "Con $lac dong co bien so ma khong tro vao xe nao");

// ---------------------------------------------------------------------------
section('Model xe + phieu');

require_once $goc . 'app/models/VehiclesModel.php';
require_once $goc . 'app/models/ReceptionsModel.php';
$V = new VehiclesModel();
$R = new ReceptionsModel();

ok(VehiclesModel::tenXe(['hang_dm' => 'Toyota', 'model_dm' => 'Vios', 'nam_dm' => '2019', 'phien_ban' => '1.5G'])
   === 'Toyota Vios 2019 1.5G', 'tenXe() ghep ten tu danh muc');
ok(VehiclesModel::tenXe(['hang_xe' => 'ZZ Hang la', 'model_xe' => 'ZZ Model la', 'nam_sx' => 2015])
   === 'ZZ Hang la ZZ Model la 2015', 'tenXe() dung chu go tay khi xe khong co trong danh muc');
ok(VehiclesModel::tenXe(['hang_dm' => 'Toyota', 'hang_xe' => 'GO TAY']) === 'Toyota',
   'Co ten danh muc thi KHONG dung chu go tay',
   'Hai nguon ten cho cung mot thu la bat dau lech nhau');
ok(VehiclesModel::tenXe([]) === '', 'Khong co gi -> chuoi rong');

ok(empty($V->theoSoKhung('')) && empty($V->theoSoKhung('   ')),
   'So khung rong thi KHONG tra cuu', "'' khong phai mot so khung");
ok($V->getLists(['q' => '---']) === [] || count($V->getLists(['q' => '---'])) === 0,
   'Tu khoa khong co chu so nao -> khong ra ca bang');

ok(ReceptionsModel::statusHopLe('dang_sua') === 'dang_sua'
   && ReceptionsModel::statusHopLe('hack') === 'tiep_nhan',
   'Trang thai la tren URL roi ve mac dinh');
ok(strpos($R->nextNo(), 'TN-') === 0, 'So phieu tiep nhan theo day TN-', 'Ra ' . $R->nextNo());

$hang = $pdo->query("SELECT id FROM car_brands WHERE status=1 ORDER BY id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$mdl  = $pdo->query("SELECT id, brand_id FROM car_models WHERE brand_id = " . (int) $hang['id'] . " LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$mdlLa= $pdo->query("SELECT id FROM car_models WHERE brand_id <> " . (int) $hang['id'] . " LIMIT 1")->fetchColumn();
$nam  = $pdo->query("SELECT id FROM car_years WHERE model_id = " . (int) $mdl['id'] . " LIMIT 1")->fetchColumn();
ok($V->modelThuocHang((int) $mdl['id'], (int) $hang['id']) === true, 'Model dung hang -> hop le');
ok($V->modelThuocHang((int) $mdlLa, (int) $hang['id']) === false, 'Model cua hang KHAC -> khong hop le',
   'O chon day chuyen chi loc o trinh duyet, phai kiem o server');
ok($V->modelThuocHang(0, 0) === true, 'Khong chon model thi khong can kiem');
ok($V->namThuocModel((int) $nam, (int) $mdl['id']) === true, 'Nam dung model -> hop le');
ok($V->namThuocModel((int) $nam, (int) $mdlLa) === false, 'Nam cua model khac -> khong hop le');

// ---------------------------------------------------------------------------
section('HTTP that: khach -> xe -> phieu -> chung tu');

if (!function_exists('curl_init')){ echo "\n[SKIP] PHP khong co curl.\n"; exit(summary()); }

$donSach = function() use ($pdo){
    $pdo->exec("DELETE FROM quotations WHERE customer_name LIKE 'ZZXP%'
                 OR reception_id IN (SELECT id FROM receptions WHERE note = 'ZZXP')
                 OR vehicle_id IN (SELECT id FROM vehicles WHERE bien_so_chuan LIKE 'ZZXP%')");
    $pdo->exec("DELETE FROM warranty_requests WHERE customer_name LIKE 'ZZXP%'");
    $pdo->exec("DELETE FROM receptions WHERE note = 'ZZXP'");
    $pdo->exec("DELETE FROM vehicles WHERE bien_so_chuan LIKE 'ZZXP%'");
    $pdo->exec("DELETE FROM partners WHERE code LIKE 'ZZXP%'");
    $pdo->exec("DELETE t FROM login_tokens t JOIN users u ON u.id = t.user_id WHERE u.email = 'zz-xp@local.test'");
    $pdo->exec("DELETE FROM users WHERE email = 'zz-xp@local.test'");
};
$donSach();

$MK = 'ZzXePhieu#2026';
$nhomAdmin = (int) $pdo->query("SELECT id FROM `groups` WHERE name='Admin'")->fetchColumn();
$pdo->prepare("INSERT INTO users (name,email,password,group_id,status,create_at)
               VALUES ('ZZ Admin xe','zz-xp@local.test',?,?,1,NOW())")
    ->execute([\App\core\Hash::make($MK), $nhomAdmin]);

$http = function($m, $url, $jar, $d = null){
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 25]);
    if ($m === 'POST'){ curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d)); }
    $raw = curl_exec($ch); $i = curl_getinfo($ch); curl_close($ch);
    $body = $raw === false ? '' : substr($raw, (int) $i['header_size']);
    return ['code' => (int) $i['http_code'], 'loc' => (string) $i['redirect_url'], 'body' => $body,
            'text' => html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8')];
};
$token = function($h){ return preg_match('~name="_token" value="([^"]+)"~', $h, $m) ? $m[1] : ''; };

$jar = tempnam(sys_get_temp_dir(), 'zzxp');
$r = $http('GET', "$base/dang-nhap", $jar);
if ($r['code'] === 0){ echo "\n[SKIP] Apache khong chay (localhost:88).\n"; $donSach(); exit(summary()); }
$tk = $token($r['body']);
$http('POST', "$base/dang-nhap", $jar, ['email' => 'zz-xp@local.test', 'password' => $MK, '_token' => $tk]);
/* Mở lại form sau mỗi lần POST LỖI, như trình duyệt bị đẩy về: dữ liệu cũ nằm
   tạm trong phiên đến lần mở form kế tiếp và sẽ đè lên phần điền sẵn. */
$tieuFlash = function($duong) use ($http, $base, $jar){ $http('GET', "$base/$duong", $jar); };

/* --- Khách --- */
$r  = $http('GET', "$base/admin/partners/add", $jar);
$tk = $token($r['body']) ?: $tk;
ok(strpos($r['text'], 'Xe của khách') !== false,
   'Man Them doi tuong noi truoc la luu xong se khai xe');
$http('POST', "$base/admin/partners/add", $jar, ['_token' => $tk, 'code' => 'ZZXP-KH', 'name' => 'ZZXP Khach nhieu xe',
    'type' => 'customer', 'address' => '1 ZZ', 'sort_order' => 0, 'status' => 1]);
$kh = $pdo->query("SELECT * FROM partners WHERE code='ZZXP-KH'")->fetch(PDO::FETCH_ASSOC);
ok(!empty($kh), 'Them duoc doi tuong khach');

/* --- Hai xe cho CÙNG một khách --- */
$r  = $http('GET', "$base/admin/vehicles/add?ve=partner&partner_id=" . (int) $kh['id'], $jar);
$tk = $token($r['body']) ?: $tk;
$lapXe = function($bienSo, $them = []) use ($http, $base, $jar, &$tk, $kh){
    return $http('POST', "$base/admin/vehicles/add", $jar, array_merge([
        '_token' => $tk, 'partner_id' => (int) $kh['id'], 'bien_so' => $bienSo, 'status' => 1,
    ], $them));
};
$lapXe('zzxp-11.111', ['so_khung' => 'zzxpvin1', 'so_may' => 'zzxpmay1', 'so_km' => '10.000',
                       'brand_id' => (int) $hang['id'], 'model_id' => (int) $mdl['id'], 'car_year_id' => (int) $nam]);
$lapXe('ZZXP-22.222', ['hang_xe' => 'ZZ Hang la', 'model_xe' => 'ZZ Model la', 'nam_sx' => '2016']);
$xe1 = $pdo->query("SELECT * FROM vehicles WHERE bien_so_chuan='ZZXP11111'")->fetch(PDO::FETCH_ASSOC);
$xe2 = $pdo->query("SELECT * FROM vehicles WHERE bien_so_chuan='ZZXP22222'")->fetch(PDO::FETCH_ASSOC);
ok(!empty($xe1) && !empty($xe2), 'MOT khach khai duoc NHIEU xe');
ok(!empty($xe1) && $xe1['so_khung'] === 'ZZXPVIN1' && (int) $xe1['brand_id'] === (int) $hang['id']
   && (int) $xe1['car_year_id'] === (int) $nam,
   'Xe luu so khung (hoa), hang / model / nam tu danh muc');
ok(!empty($xe1) && $xe1['hang_xe'] === null, 'Chon danh muc thi KHONG luu chu go tay');
ok(!empty($xe2) && $xe2['hang_xe'] === 'ZZ Hang la' && (int) $xe2['nam_sx'] === 2016,
   'Xe la: go tay hang / model / nam van luu duoc');

/* Biển số trùng (gõ kiểu khác) và số khung trùng: phải bị chặn */
$lapXe('ZZXP 11 111');
ok((int) $pdo->query("SELECT COUNT(*) FROM vehicles WHERE bien_so_chuan='ZZXP11111'")->fetchColumn() === 1,
   'Bien so go kieu khac van la MOT xe (khong tao ban ghi thu hai)');
$tieuFlash('admin/vehicles/add');
$lapXe('zzxp-33.333', ['so_khung' => 'ZZXPVIN1']);
ok((int) $pdo->query("SELECT COUNT(*) FROM vehicles WHERE bien_so_chuan='ZZXP33333'")->fetchColumn() === 0,
   'So khung trung -> khong luu duoc');
$tieuFlash('admin/vehicles/add');
$lapXe('zzxp-44.444', ['brand_id' => (int) $hang['id'], 'model_id' => (int) $mdlLa]);
ok((int) $pdo->query("SELECT COUNT(*) FROM vehicles WHERE bien_so_chuan='ZZXP44444'")->fetchColumn() === 0,
   'Model cua hang khac -> khong luu duoc');
$tieuFlash('admin/vehicles/add');

/* Khối "Xe của khách" trên màn Đối tượng */
$r = $http('GET', "$base/admin/partners/edit/" . (int) $kh['id'], $jar);
ok(strpos($r['text'], 'Xe của khách') !== false
   && strpos($r['body'], 'ZZXP-22.222') !== false && strpos($r['body'], 'zzxp-11.111') !== false,
   'Man Doi tuong hien DU hai xe cua khach do');

/* --- Phiếu tiếp nhận --- */
$r  = $http('GET', "$base/admin/receptions/add?vehicle_id=" . (int) $xe1['id'], $jar);
$tk = $token($r['body']) ?: $tk;
ok($r['code'] === 200 && strpos($r['body'], 'value="10000"') !== false,
   'Form tiep nhan dien san so km dang ghi nhan cua xe');

$lapPhieu = function($them = []) use ($http, $base, $jar, &$tk, $xe1){
    return $http('POST', "$base/admin/receptions/add", $jar, array_merge([
        '_token' => $tk, 'vehicle_id' => (int) $xe1['id'], 'ngay_vao' => date('Y-m-d'),
        'status' => 'tiep_nhan', 'note' => 'ZZXP',
    ], $them));
};
$lapPhieu(['km_vao' => '12.500', 'yeu_cau_khach' => 'ZZXP Bao duong + kiem tra phanh',
           'tinh_trang_xe' => 'ZZXP Xuoc can sau']);
$tn = $pdo->query("SELECT * FROM receptions WHERE note='ZZXP' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
ok(!empty($tn) && strpos($tn['reception_no'], 'TN-') === 0, 'Lap duoc phieu tiep nhan');
ok(!empty($tn) && (int) $tn['vehicle_id'] === (int) $xe1['id'] && (int) $tn['partner_id'] === (int) $kh['id'],
   'Phieu gan dung xe, va chup lai chu xe luc tiep nhan');
ok((int) $pdo->query("SELECT so_km FROM vehicles WHERE id=" . (int) $xe1['id'])->fetchColumn() === 12500,
   'Km vao cap nhat lai so km cua xe');

$lapPhieu(['km_vao' => '500']);
ok((int) $pdo->query("SELECT COUNT(*) FROM receptions WHERE note='ZZXP'")->fetchColumn() === 1,
   'Km vao NHO HON so km da ghi nhan -> bi chan',
   'Dong ho khong quay lui; go thieu chu so la keo tut moc nhac bao tri');
$tieuFlash('admin/receptions/add');

/* Một xe NHIỀU phiếu */
$lapPhieu(['km_vao' => '13.000']);
ok((int) $pdo->query("SELECT COUNT(*) FROM receptions WHERE vehicle_id=" . (int) $xe1['id'])->fetchColumn() === 2,
   'MOT xe co NHIEU phieu tiep nhan');

/* --- Chứng từ lập TỪ phiếu --- */
$partId = (int) $pdo->query("SELECT id FROM parts WHERE item_type <> 'service' AND status = 1 ORDER BY id LIMIT 1")->fetchColumn();
$r  = $http('GET', "$base/admin/quotations/add?reception_id=" . (int) $tn['id'], $jar);
$tk = $token($r['body']) ?: $tk;
ok(strpos($r['text'], 'Lập từ phiếu tiếp nhận') !== false, 'Form bao gia noi ro dang lap tu phieu nao');
ok(stripos($r['body'], 'zzxp-11.111') !== false, 'Form bao gia dien san bien so cua xe trong phieu');

$http('POST', "$base/admin/quotations/add", $jar, ['_token' => $tk, 'reception_id' => (int) $tn['id'],
    'customer_id' => (int) $kh['id'], 'customer_name' => 'ZZXP Khach', 'quote_date' => date('Y-m-d'),
    'vat_rate' => 10, 'bien_so' => 'zzxp-11.111', 'so_km' => '12.500',
    'line_part' => [$partId], 'line_qty' => [2], 'line_price' => [150000], 'line_disc' => [0], 'line_note' => ['']]);
/* Bang bao gia KHONG luu customer_name khi da chon khach tu danh muc — tra
   theo ma phieu tiep nhan moi dung. */
$bg = $pdo->query("SELECT * FROM quotations WHERE reception_id = " . (int) $tn['id'] . " ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
ok(!empty($bg) && (int) $bg['reception_id'] === (int) $tn['id'] && (int) $bg['vehicle_id'] === (int) $xe1['id'],
   'Bao gia gan vao dung PHIEU va dung XE',
   'Day la thu truoc day khong co: chung tu chi luu chuoi bien so');
ok(!empty($bg) && $bg['bien_so_chuan'] === 'ZZXP11111' && (int) $bg['so_km'] === 12500,
   'Bao gia van giu ban chup bien so + so km luc lap');

$r  = $http('GET', "$base/admin/warranty/add?loai=bao_tri&reception_id=" . (int) $tn['id'], $jar);
$tk = $token($r['body']) ?: $tk;
$http('POST', "$base/admin/warranty/add", $jar, ['_token' => $tk, 'loai' => 'bao_tri',
    'reception_id' => (int) $tn['id'], 'customer_name' => 'ZZXP Khach', 'bien_so' => 'zzxp-11.111',
    'so_km' => '12.500', 'received_date' => date('Y-m-d'), 'fee' => 0]);
$bt = $pdo->query("SELECT * FROM warranty_requests WHERE customer_name='ZZXP Khach' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
ok(!empty($bt) && (int) $bt['reception_id'] === (int) $tn['id'] && (int) $bt['vehicle_id'] === (int) $xe1['id'],
   'Phieu bao tri cung gan vao phieu tiep nhan va xe');

/* Lập TAY, không qua phiếu: gõ biển số đã có xe thì vẫn phải nối vào xe đó */
$r  = $http('GET', "$base/admin/quotations/add", $jar);
$tk = $token($r['body']) ?: $tk;
$http('POST', "$base/admin/quotations/add", $jar, ['_token' => $tk,
    'customer_id' => (int) $kh['id'], 'customer_name' => 'ZZXP Khach tay', 'quote_date' => date('Y-m-d'),
    'vat_rate' => 0, 'bien_so' => 'ZZXP 22 222', 'so_km' => '',
    'line_part' => [$partId], 'line_qty' => [1], 'line_price' => [100000], 'line_disc' => [0], 'line_note' => ['']]);
$bg2 = $pdo->query("SELECT * FROM quotations WHERE vehicle_id = " . (int) $xe2['id'] . " AND reception_id IS NULL ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
ok(!empty($bg2) && (int) $bg2['vehicle_id'] === (int) $xe2['id'] && $bg2['reception_id'] === null,
   'Lap tay: bien so trung xe da co -> van noi vao xe (khong co phieu tiep nhan)',
   'Nho vay lich su xe khong dut chi vi nguoi lap khong di qua phieu');

/* --- Hai màn tổng hợp --- */
$r = $http('GET', "$base/admin/receptions/edit/" . (int) $tn['id'], $jar);
ok(strpos($r['text'], $bg['quote_no']) !== false && strpos($r['text'], $bt['request_no']) !== false,
   'Man phieu tiep nhan gom du chung tu cua lan vao xuong do');
$r = $http('GET', "$base/admin/vehicles/edit/" . (int) $xe1['id'], $jar);
ok(strpos($r['text'], $tn['reception_no']) !== false && strpos($r['text'], $bg['quote_no']) !== false,
   'Man xe hien lich su: cac lan vao xuong + chung tu');

/* --- Chốt chặn xoá --- */
$http('GET', "$base/admin/receptions/delete/" . (int) $tn['id'], $jar);
ok((int) $pdo->query("SELECT COUNT(*) FROM receptions WHERE id=" . (int) $tn['id'])->fetchColumn() === 1,
   'Phieu dang co chung tu -> KHONG xoa duoc');
$http('GET', "$base/admin/vehicles/delete/" . (int) $xe1['id'], $jar);
ok((int) $pdo->query("SELECT COUNT(*) FROM vehicles WHERE id=" . (int) $xe1['id'])->fetchColumn() === 1,
   'Xe dang co phieu tiep nhan -> KHONG xoa duoc',
   'Xoa di la mat lich su sua chua ma chung tu van con tro vao');

/* --- Sửa chứng từ không được làm nó rơi khỏi phiếu --- */
$r  = $http('GET', "$base/admin/quotations/edit/" . (int) $bg['id'], $jar);
$tk = $token($r['body']) ?: $tk;
$http('POST', "$base/admin/quotations/edit/" . (int) $bg['id'], $jar, ['_token' => $tk,
    'customer_id' => (int) $kh['id'], 'customer_name' => 'ZZXP Khach', 'quote_date' => date('Y-m-d'),
    'vat_rate' => 10, 'bien_so' => 'zzxp-11.111', 'so_km' => '12.600',
    'line_part' => [$partId], 'line_qty' => [3], 'line_price' => [150000], 'line_disc' => [0], 'line_note' => ['']]);
$bgSau = $pdo->query("SELECT * FROM quotations WHERE id=" . (int) $bg['id'])->fetch(PDO::FETCH_ASSOC);
ok((int) $bgSau['reception_id'] === (int) $tn['id'],
   'Sua bao gia van giu nguyen phieu tiep nhan',
   'Ma phieu chi ghi luc LAP — sua form khong duoc lam chung tu roi khoi lan vao xuong');
ok((int) $bgSau['vehicle_id'] === (int) $xe1['id'], 'Sua bao gia van giu dung xe');
ok((int) $pdo->query("SELECT so_km FROM vehicles WHERE id=" . (int) $xe1['id'])->fetchColumn() === 13000,
   'So km cua xe khong bi keo tut khi chung tu ghi so nho hon',
   'Xe dang o 13.000 km, bao gia ghi 12.600 -> giu 13.000');

@unlink($jar);
$donSach();
ok((int) $pdo->query("SELECT COUNT(*) FROM vehicles WHERE bien_so_chuan LIKE 'ZZXP%'")->fetchColumn() === 0
   && (int) $pdo->query("SELECT COUNT(*) FROM receptions WHERE note='ZZXP'")->fetchColumn() === 0,
   'Da don sach du lieu test');

exit(summary());

<?php
/**
 * Test XE CỦA KHÁCH trên màn CSKH › Khách hàng — tra khách theo biển số / VIN.
 *
 * Chạy:  C:\xampp\php\php.exe tests\XeCuaKhachTest.php
 *
 * Từ 22/09/2026 (gara độc lập) khách của gara là bảng `partners`, xe là bảng
 * `vehicles` đầy đủ (biển số, VIN, số máy, hãng / model / năm) — dùng chung với
 * màn Đối tượng và Xe của khách. Bảng cũ `member_vehicles` chỉ có biển số + chữ
 * gõ tay và KHÔNG lập được phiếu tiếp nhận: chuỗi khách -> xe -> phiếu đứt ngay
 * ở màn này. Bảng đó không dùng nữa, dữ liệu cũ đã chuyển (migration 000077).
 *
 * HAI CHỖ DỄ HỎNG (giữ nguyên từ bản cũ):
 *   1. Người nhập biển số mỗi lần một kiểu: "30A-123.45", "30a 123 45",
 *      "30A12345". So thẳng chuỗi gốc thì tìm không ra -> so trên cột chuẩn hoá.
 *   2. Tra theo biển số ở danh sách khách phải dùng EXISTS chứ không JOIN:
 *      JOIN nhân dòng, khách 3 xe hiện ra 3 lần.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Chuan hoa bien so');

$cap = [
    '30A-123.45'   => '30A12345',
    '30a 123 45'   => '30A12345',
    ' 30A12345 '   => '30A12345',
    '30a-12345'    => '30A12345',
    '51F-678.90'   => '51F67890',
    '---'          => '',
    ''             => '',
];
foreach ($cap as $vao => $ra){
    ok(chuan_hoa_bien_so($vao) === $ra, 'Chuan hoa "' . $vao . '" -> "' . $ra . '"',
       var_export(chuan_hoa_bien_so($vao), true));
}
ok(chuan_hoa_bien_so('30A-123.45') === chuan_hoa_bien_so('30a 123 45'),
   'Ba kieu go cua CUNG mot bien so deu ra mot chuoi', 'Day la ca ly do co cot bien_so_chuan');

// ---------------------------------------------------------------------------
section('Man Khach hang dung bang xe DAY DU');

$routes = file_get_contents($goc . 'routes/web.php');
foreach (['customers/xe-them', 'customers/xe-sua', 'customers/xe-xoa'] as $r){
    ok(strpos($routes, $r) === false, 'Khong con route cu ' . $r,
       'Xe khai o man Xe cua khach (vehicles/add?ve=customer) — bang day du, lap duoc phieu');
}

$ctrl = codeOnly($goc . 'app/controllers/admin/Customers.php');
ok(strpos($ctrl, "model('PartnersModel')") !== false && strpos($ctrl, "model('VehiclesModel')") !== false,
   'Controller doc khach tu partners, xe tu vehicles');
ok(strpos($ctrl, 'MemberVehiclesModel') === false && strpos($ctrl, 'MembersModel') === false,
   'Controller KHONG con dung bang cu (members / member_vehicles)');
ok(strpos($ctrl, 'theoNhieuChu') !== false, 'Danh sach lay xe MOT lan cho ca trang',
   'Hoi lai theo tung dong la 20 truy van thua cho mot trang 20 khach');

$vList = file_get_contents($goc . 'app/views/admin/customers/lists.php');
ok(strpos($vList, 'BIỂN SỐ') !== false && strpos($vList, 'VIN') !== false, 'O tim noi ro la tim duoc ca bien so va VIN');
$soCot = preg_match('~<thead>(.*?)</thead>~s', $vList, $mh) ? substr_count($mh[1], '<th') : 0;
ok($soCot > 0 && strpos($vList, 'colspan="' . $soCot . '"') !== false,
   'Dong "khong co du lieu" rong dung bang so cot (' . $soCot . ')',
   'Them cot ma quen colspan la bang lech han mot o');

$vEdit = file_get_contents($goc . 'app/views/admin/customers/edit.php');
ok(strpos($vEdit, 'Xe của khách') !== false, 'Ho so khach co khoi Xe cua khach');
ok(strpos($vEdit, 'vehicles/add?ve=customer&partner_id=') !== false, 'Nut them xe mo man khai xe day du, luu xong quay ve khach');
ok(strpos($vEdit, 'receptions/add?vehicle_id=') !== false, 'Moi xe co nut lap phieu tiep nhan');
ok(strpos($vEdit, 'so_khung') !== false && strpos($vEdit, 'so_may') !== false, 'Khoi xe hien so khung (VIN) va so may');

$mg = glob($goc . 'database/migrations/*_khach_va_xe_theo_gara.php');
ok(count($mg) === 1, 'Co migration chuyen khach / xe cu (000077)');

// ---------------------------------------------------------------------------
section('Chay that tren MySQL');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$don = function() use ($pdo){
    foreach ([
        "DELETE FROM vehicles WHERE garage_id IN (SELECT id FROM (SELECT id FROM garages WHERE code = 'ZZXK') t)",
        "DELETE FROM partners WHERE garage_id IN (SELECT id FROM (SELECT id FROM garages WHERE code = 'ZZXK') t)",
        "DELETE FROM garages WHERE code = 'ZZXK'",
        "DELETE FROM vehicles WHERE bien_so_chuan = 'ZZ799999'",
        "DELETE FROM partners WHERE name = 'XE Test Vang lai'",
        "DELETE v FROM member_vehicles v JOIN members m ON m.id = v.member_id WHERE m.name = 'XE Test Vang lai'",
        "DELETE FROM members WHERE name = 'XE Test Vang lai'",
    ] as $c){ try { $pdo->exec($c); } catch (\Throwable $e){} }
};
$don();
register_shutdown_function($don);

/* Gara tạm, và chỉ làm việc trong gara đó: khách / xe của gara khác không được
   lẫn vào kết quả (xem CachLyGaraTest). */
$pdo->exec("INSERT INTO garages (code, name, is_master, status, sort_order, create_at) VALUES ('ZZXK', 'ZZ Gara xe khach', 0, 1, 99, NOW())");
$gara = (int) $pdo->lastInsertId();
\App\core\Model::epGara($gara);

require_once $goc . 'app/models/PartnersModel.php';
require_once $goc . 'app/models/VehiclesModel.php';
$P = new PartnersModel();
$V = new VehiclesModel();

$khA = (int) $P->add(['code' => 'XK-1', 'name' => 'XE Test Nguyen Van A', 'type' => 'customer', 'phone' => '0900000001', 'status' => 1]);
$khB = (int) $P->add(['code' => 'XK-2', 'name' => 'XE Test Tran Thi B',   'type' => 'customer', 'phone' => '0900000002', 'status' => 1]);
$xe  = function($kh, $bs, $vin = null) use ($V){
    return (int) $V->add(['partner_id' => $kh, 'bien_so' => $bs, 'bien_so_chuan' => chuan_hoa_bien_so($bs), 'so_khung' => $vin, 'status' => 1]);
};
// Khách A có BA xe — đúng tình huống trong ghi chú nghiệp vụ
$xe($khA, '30A-123.45', 'XKVIN0000000000A1');
$xe($khA, '30A-678.90');
$xe($khA, '30A-111.11');
$xe($khB, '51F-222.22');

ok((int) $pdo->query("SELECT garage_id FROM partners WHERE id = $khA")->fetchColumn() === $gara,
   'Khach them moi tu ghi vao gara lam viec');

$nhieu = $V->theoNhieuChu([$khA, $khB]);
ok(count($nhieu[$khA] ?? []) === 3 && count($nhieu[$khB] ?? []) === 1, 'Mot khach giu duoc NHIEU xe, khach khac khong bi lan xe');

$ten = function($loc) use ($P){ return array_column((array) $P->khachHang($loc), 'name'); };

$t = $ten(['q' => '30A-678.90']);
ok(in_array('XE Test Nguyen Van A', $t, true), 'Danh sach khach: tim "30A-678.90" ra dung chu xe');
ok(!in_array('XE Test Tran Thi B', $t, true), 'Khong keo theo khach khong lien quan');
ok(in_array('XE Test Nguyen Van A', $ten(['q' => '30a 678 90']), true), 'Go "30a 678 90" (khac dinh dang) van ra dung khach');
ok(in_array('XE Test Nguyen Van A', $ten(['q' => 'XKVIN0000000000A1']), true), 'Tim duoc theo SO KHUNG (VIN)');

$demA = count(array_filter($ten(['q' => '30A']), function($x){ return $x === 'XE Test Nguyen Van A'; }));
ok($demA === 1, 'Khach 3 xe cung chi hien MOT dong trong danh sach',
   'Dung JOIN thay vi EXISTS la khach hien ra 3 lan; dem duoc ' . $demA . ' dong');
ok($P->demKhachHang(['q' => '30A-678.90']) === count($P->khachHang(['q' => '30A-678.90'])),
   'demKhachHang khop voi so dong khachHang tra ve', 'Lech thi phan trang bao sai so trang');
ok(count($P->khachHang([], 1, 0)) === 1 && $P->demKhachHang([]) === 2, 'Phan trang cat dung trong cau truy van');
ok(!in_array('XE Test Nguyen Van A', $ten(['q' => '---']), true), 'Go "---" KHONG lam khop moi bien so',
   'Chuan hoa ra chuoi rong -> LIKE "%%" se khop tat ca');
ok($ten(['q' => 'XE Test Tran Thi B']) === ['XE Test Tran Thi B'], 'Van tim duoc theo TEN');
ok($ten(['q' => '0900000002']) === ['XE Test Tran Thi B'], 'Van tim duoc theo SDT');

/* Nhà cung cấp thuần không phải khách */
$P->add(['code' => 'XK-3', 'name' => 'XE Test NCC', 'type' => 'supplier', 'status' => 1]);
ok(!in_array('XE Test NCC', $ten([]), true), 'Nha cung cap thuan KHONG hien o man Khach hang');

\App\core\Model::epGara(null);

// ---------------------------------------------------------------------------
section('Chuyen du lieu cu (000077): member_vehicles -> vehicles');

$pdo->prepare("INSERT INTO members (email, password, name, phone, status, create_at) VALUES (NULL, 'x', 'XE Test Vang lai', '0900000009', 1, NOW())")->execute();
$mem = (int) $pdo->lastInsertId();
$pdo->prepare("INSERT INTO member_vehicles (member_id, bien_so, bien_so_chuan, hang_xe, model_xe, so_km, create_at)
               VALUES (?, 'ZZ7-999.99', 'ZZ799999', 'Toyota', 'Vios', 45000, NOW())")->execute([$mem]);

$chay = function() use ($mg){
    $m = require $mg[0];
    $m->setDb(new \App\core\Database());
    ob_start();
    try { $m->up(); $loi = ''; } catch (\Throwable $e){ $loi = $e->getMessage(); }
    ob_end_clean();
    return $loi;
};
$loi = $chay();
ok($loi === '', 'Chay migration 000077 khong loi', $loi);
$pid = (int) $pdo->query("SELECT partner_id FROM members WHERE id = $mem")->fetchColumn();
$kh  = $pdo->query("SELECT * FROM partners WHERE id = $pid")->fetch(PDO::FETCH_ASSOC);
ok(!empty($kh) && $kh['name'] === 'XE Test Vang lai' && $kh['phone'] === '0900000009' && $kh['type'] === 'customer',
   'Tai khoan khong email (khach vang lai cu) thanh khach o man Khach hang', json_encode($kh));
$xeMoi = $pdo->query("SELECT * FROM vehicles WHERE bien_so_chuan = 'ZZ799999'")->fetchAll(PDO::FETCH_ASSOC);
ok(count($xeMoi) === 1 && (int) $xeMoi[0]['partner_id'] === $pid && (int) $xeMoi[0]['so_km'] === 45000
   && $xeMoi[0]['hang_xe'] === 'Toyota', 'Xe trong bang cu chuyen sang `vehicles`, dung chu, giu so km + hang xe', json_encode($xeMoi));
$chay();
ok((int) $pdo->query("SELECT COUNT(*) FROM partners WHERE name = 'XE Test Vang lai'")->fetchColumn() === 1
   && (int) $pdo->query("SELECT COUNT(*) FROM vehicles WHERE bien_so_chuan = 'ZZ799999'")->fetchColumn() === 1,
   'Chay lai migration KHONG nhan doi khach / xe');

$don();
ok((int) $pdo->query("SELECT COUNT(*) FROM garages WHERE code = 'ZZXK'")->fetchColumn() === 0, 'Da don sach du lieu test');

exit(summary());

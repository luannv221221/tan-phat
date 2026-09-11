<?php
/**
 * Test BỘ LỌC màn Đối tượng (khách / NCC).
 *
 * Chạy:  C:\xampp\php\php.exe tests\PartnersLocTest.php
 *
 * Trước đây màn này không có bộ lọc nào — getLists() không nhận tham số.
 *
 * CHỖ DỄ HỎNG NHẤT: loại "Cả hai" (type = 'both').
 * Lọc "Khách hàng" mà so `type = 'customer'` thì đối tác vừa mua vừa bán biến
 * mất khỏi danh sách khách — và cũng biến mất khỏi danh sách NCC. Họ không
 * thuộc danh sách nào dù là CẢ HAI. Lỗi này không báo gì, chỉ âm thầm thiếu
 * người. Dữ liệu hiện tại chưa có đối tác "Cả hai" nào nên nhìn màn hình thì
 * không bao giờ thấy — test phải tự dựng một cái.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Controller chong gia tri la tren URL');

$ctl = codeOnly($goc . 'app/controllers/admin/Partners.php');
$than = '';
if (preg_match('~public function index\(\)\s*\{(.*?)\n    \}~s', $ctl, $m)) $than = $m[1];
ok($than !== '', 'Doc duoc than ham index()');
ok(strpos($than, 'PartnersModel::$types[$f[\'type\']]') !== false,
   'Loai chi nhan gia tri co trong danh sach loai',
   'Nhan bua thi ?type=abc van di vao truy van');
ok(strpos($than, "\$f['group'] === 'none'") !== false,
   'Nhom chi nhan "none" hoac so duong');
ok(strpos($than, "\$f['status'] === '1' || \$f['status'] === '0'") !== false,
   'Trang thai chi nhan 1 hoac 0');
ok(strpos($than, "getLists(\$loc)") !== false, 'index() truyen bo loc vao model');

// ---------------------------------------------------------------------------
section('Giao dien');

$v = file_get_contents($goc . 'app/views/admin/partners/lists.php');
ok(preg_match('~<form method="get"~', $v) === 1,
   'Form loc dung GET',
   'POST thi dan link khong ai thay dung danh sach, sang trang 2 mat bo loc');
foreach (['q', 'type', 'group', 'status'] as $o){
    ok(strpos($v, 'name="' . $o . '"') !== false, "Co o loc `$o`");
}
ok(strpos($v, 'value="none"') !== false, 'Co lua chon "Chua xep nhom"');
ok(strpos($v, '$tongTatCa') !== false && strpos($v, "\$pg['total']") !== false,
   'Hien "dang xem x / y" khi loc',
   'Khong co thi nguoi dung tuong he thong chi co tung ay doi tuong');
ok(strpos($v, 'Không có đối tượng nào khớp bộ lọc') !== false,
   'Phan biet "khong khop bo loc" voi "chua co du lieu"',
   'Cung mot cau thi nguoi dung tuong mat sach doi tuong');

// ---------------------------------------------------------------------------
section('Chay that tren MySQL');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

require_once $goc . 'app/models/PartnersModel.php';
$P = new PartnersModel();

$tongBang = (int) $pdo->query("SELECT COUNT(*) FROM partners")->fetchColumn();

/* --- Tuong thich nguoc: goi khong tham so = lay het nhu cu --- */
ok(count($P->getLists()) === $tongBang,
   'getLists() khong tham so van lay het (' . $tongBang . ' dong)',
   'Doi chu ky ma lam doi hanh vi mac dinh la vo cho goi cu');
ok($P->demTatCa() === $tongBang, 'demTatCa() khop so dong that');

/* --- Du lieu thu: mot khach, mot NCC, mot "Ca hai" --- */
$pdo->exec("DELETE FROM partners WHERE code LIKE 'ZZL-%'");
$them = $pdo->prepare("INSERT INTO partners (code,name,type,group_id,phone,tax_code,status,sort_order,create_at)
                       VALUES (?,?,?,?,?,?,?,0,NOW())");
$nhom = $pdo->query("SELECT id FROM customer_groups ORDER BY id LIMIT 1")->fetchColumn();
$them->execute(['ZZL-KH',   'ZZL Khach thu',    'customer', $nhom ?: null, '0911000001', 'ZZMST001', 1]);
$them->execute(['ZZL-NCC',  'ZZL NCC thu',      'supplier', null,          '0911000002', 'ZZMST002', 1]);
$them->execute(['ZZL-BOTH', 'ZZL Vua mua vua ban', 'both',  null,          '0911000003', 'ZZMST003', 1]);
$them->execute(['ZZL-AN',   'ZZL Da an',        'customer', null,          '0911000004', 'ZZMST004', 0]);

$co = function($ds, $ma){ return in_array($ma, array_column($ds, 'code'), true); };

/* --- BAY CHINH: "Ca hai" phai co mat o ca hai bo loc --- */
$kh  = $P->getLists(['type' => 'customer']);
$ncc = $P->getLists(['type' => 'supplier']);
ok($co($kh,  'ZZL-BOTH'), 'Doi tac "Ca hai" CO trong bo loc Khach hang',
   'So `type = customer` thi nguoi vua mua vua ban bien mat khoi danh sach khach');
ok($co($ncc, 'ZZL-BOTH'), 'Doi tac "Ca hai" CO trong bo loc Nha cung cap');
ok($co($kh, 'ZZL-KH') && !$co($kh, 'ZZL-NCC'),  'Bo loc Khach hang khong lan NCC');
ok($co($ncc, 'ZZL-NCC') && !$co($ncc, 'ZZL-KH'), 'Bo loc NCC khong lan khach');

$chiHai = $P->getLists(['type' => 'both']);
ok($co($chiHai, 'ZZL-BOTH') && !$co($chiHai, 'ZZL-KH') && !$co($chiHai, 'ZZL-NCC'),
   'Chon dich danh "Ca hai" chi ra dung nhom do');

/* --- Tim kiem tren ca bon cot --- */
foreach (['ZZL-NCC' => 'ma', 'ZZL NCC thu' => 'ten', '0911000002' => 'SDT', 'ZZMST002' => 'MST'] as $tu => $cot){
    ok($co($P->getLists(['q' => $tu]), 'ZZL-NCC'), "Tim duoc theo $cot (\"$tu\")");
}
ok(count($P->getLists(['q' => 'khong-ai-co-chuoi-nay-zz'])) === 0, 'Tu khoa khong khop thi ra rong');

/* --- Nhom, trang thai --- */
$chuaNhom = $P->getLists(['group' => 'none']);
ok($co($chuaNhom, 'ZZL-NCC') && !$co($chuaNhom, 'ZZL-KH') === (bool) $nhom,
   'Loc "Chua xep nhom" ra dung doi tuong khong co nhom');
if ($nhom){
    ok($co($P->getLists(['group' => (string) $nhom]), 'ZZL-KH'), 'Loc theo mot nhom cu the');
}
ok($co($P->getLists(['status' => '0']), 'ZZL-AN') && !$co($P->getLists(['status' => '0']), 'ZZL-KH'),
   'Loc "Da an" ra dung doi tuong bi an');
ok(!$co($P->getLists(['status' => '1']), 'ZZL-AN'), 'Loc "Dang dung" khong lot doi tuong bi an');

/* --- Ket hop nhieu dieu kien la AND, khong phai OR --- */
$ketHop = $P->getLists(['type' => 'customer', 'status' => '0']);
ok($co($ketHop, 'ZZL-AN') && !$co($ketHop, 'ZZL-KH') && !$co($ketHop, 'ZZL-BOTH'),
   'Ket hop "Khach hang" + "Da an" la AND',
   'OR thi ket hop hai dieu kien lai ra NHIEU dong hon');

/* --- Tu khoa doc hai --- */
$dem = count($P->getLists(['q' => "' OR 1=1 --"]));
ok($dem === 0, "Tu khoa \"' OR 1=1 --\" khong pha truy van (ra $dem dong)");
ok((int) $pdo->query("SELECT COUNT(*) FROM partners")->fetchColumn() === $tongBang + 4,
   'Bang partners van nguyen sau khi thu injection');

// Don sach
$pdo->exec("DELETE FROM partners WHERE code LIKE 'ZZL-%'");
ok((int) $pdo->query("SELECT COUNT(*) FROM partners")->fetchColumn() === $tongBang, 'Da don sach du lieu test');

exit(summary());

<?php
/**
 * Test XE CỦA KHÁCH — biển số + số km, và tra cứu theo biển số ở CSKH.
 *
 * Chạy:  C:\xampp\php\php.exe tests\XeCuaKhachTest.php
 *
 * ĐIỂM THIẾT KẾ QUAN TRỌNG NHẤT
 * Một khách có NHIỀU xe. Nếu nhét `bien_so` thành một cột trên `members` thì
 * xe thứ hai trở đi không có chỗ ghi, và tra theo biển số sẽ ra sai người.
 * Vì vậy có bảng riêng `member_vehicles` (1 khách — N xe).
 *
 * HAI CHỖ DỄ HỎNG:
 *   1. Người nhập biển số mỗi lần một kiểu: "30A-123.45", "30a 123 45",
 *      "30A12345". So thẳng chuỗi gốc thì tìm không ra. Nên có cột
 *      `bien_so_chuan` (chỉ chữ + số, viết hoa) dùng chung cho cả lưu lẫn tra.
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

require_once $goc . 'app/models/MemberVehiclesModel.php';

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
    ok(MemberVehiclesModel::chuanHoaBienSo($vao) === $ra,
       'Chuan hoa "' . $vao . '" -> "' . $ra . '"',
       var_export(MemberVehiclesModel::chuanHoaBienSo($vao), true));
}

ok(MemberVehiclesModel::chuanHoaBienSo('30A-123.45') === MemberVehiclesModel::chuanHoaBienSo('30a 123 45'),
   'Ba kieu go cua CUNG mot bien so deu ra mot chuoi',
   'Day la ca ly do co cot bien_so_chuan');

// ---------------------------------------------------------------------------
section('Migration + model + route + giao dien');

$mg = glob($goc . 'database/migrations/*_them_bang_xe_cua_khach.php');
ok(!empty($mg), 'Co migration tao bang member_vehicles');
if (!empty($mg)){
    $src = file_get_contents($mg[0]);
    ok(strpos($src, 'ON DELETE CASCADE') !== false,
       'Xoa khach thi xe cua ho di theo (CASCADE)',
       'Khong thi con lai dong xe tro toi khach khong ton tai');
    ok(strpos($src, 'idx_mv_bien_so') !== false,
       'Co index tren bien_so_chuan',
       'Tra theo bien so la viec CSKH lam nhieu nhat; thieu index la quet ca bang');
    // Chuyện UNIQUE kiểm ở phần MySQL bên dưới, đọc chỉ mục THẬT của bảng.
    // Bản đầu của test này grep chữ "UNIQUE" trong mã nguồn và báo đỏ oan:
    // nó trúng ngay đoạn CHÚ THÍCH giải thích vì sao KHÔNG dùng UNIQUE.
}

ok(is_file($goc . 'app/models/MemberVehiclesModel.php'), 'Co MemberVehiclesModel');

$routes = file_get_contents($goc . 'routes/web.php');
foreach (['customers/xe-them', 'customers/xe-sua', 'customers/xe-xoa'] as $r){
    ok(strpos($routes, $r) !== false, 'Co route ' . $r);
}

$ctrl = codeOnly($goc . 'app/controllers/admin/Customers.php');
ok(strpos($ctrl, 'MemberVehiclesModel') !== false, 'Controller nap MemberVehiclesModel');
ok(substr_count($ctrl, "route('admin/' . \$this->routeBase . '/edit/'") >= 3,
   'Ca ba hanh dong xe deu kiem quyen `edit` cua customers');
ok(strpos($ctrl, 'theoNhieuKhach') !== false,
   'Danh sach lay xe MOT lan cho ca trang',
   'Hoi lai theo tung dong la 20 truy van thua cho mot trang 20 khach');

$vList = file_get_contents($goc . 'app/views/admin/customers/lists.php');
ok(strpos($vList, 'BIỂN SỐ XE') !== false, 'O tim noi ro la tim duoc ca bien so');
ok(strpos($vList, 'colspan="9"') !== false,
   'Dong "khong co du lieu" da noi rong theo cot moi',
   'Them cot ma quen colspan la bang lech han mot o');

$vEdit = file_get_contents($goc . 'app/views/admin/customers/edit.php');
ok(strpos($vEdit, 'Xe của khách') !== false, 'Man Sua khach hang co khoi quan ly xe');
ok(strpos($vEdit, 'form="fx') !== false,
   'Moi dong xe la mot form rieng (dung thuoc tinh form= cua HTML5)',
   'HTML khong cho long form; nhet vao trong thi nut Luu xe thanh nut luu khach');

/* Thẻ <form> phải nằm NGOÀI bảng, và token CSRF phải nằm THẲNG trong nó.
   Trước đây form đặt giữa <tr> và <td> còn token nối vào bằng form="fxN" —
   Chrome vẫn chạy, nhưng đó là vùng trình duyệt xử lý theo luật riêng của
   bảng, và SecurityTest (soi 300 ký tự sau thẻ <form>) báo thiếu token. */
if (preg_match('~<form[^>]*id="fx[^"]*"[^>]*>(.{0,200})~s', $vEdit, $mForm)){
    ok(strpos($mForm[1], 'csrf_field') !== false,
       'Token CSRF nam THANG trong the <form> cua dong xe',
       'Noi token bang form="fxN" thi no khong nam trong form, kho soi va de mat');
} else {
    ok(false, 'Tim thay the <form> cua dong xe de kiem tra token');
}
$viTriForm  = strpos($vEdit, '<form action="{{_WEB_URL.\'/admin/\'.$routeBase.\'/xe-sua/');
$viTriTable = strpos($vEdit, '<table class="table table-sm');
ok($viTriForm !== false && $viTriTable !== false && $viTriForm < $viTriTable,
   'Form sua xe khai TRUOC the <table>, tuc la ngoai bang',
   'De trong <tr> thi trinh duyet da ra ngoai bang, thu gi nhet vao cung roi ra theo');

// ---------------------------------------------------------------------------
section('Chay that tren MySQL');

try {
    $pdo = new PDO(
        'mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
        _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n";
    exit(summary());
}

require_once $goc . 'app/models/MembersModel.php';

// --- Chỉ mục THẬT của bảng (đọc từ MySQL, không grep mã nguồn) ---
$idx = $pdo->query("SHOW INDEX FROM `member_vehicles`")->fetchAll(PDO::FETCH_ASSOC);
$coIdxBienSo = false;
$uniqueBienSo = false;
foreach ($idx as $i){
    if ($i['Column_name'] !== 'bien_so_chuan') continue;
    $coIdxBienSo = true;
    if ((int) $i['Non_unique'] === 0) $uniqueBienSo = true;
}
ok($coIdxBienSo, 'Bang that CO index tren bien_so_chuan');
ok(!$uniqueBienSo,
   'Va index do KHONG phai UNIQUE',
   'Xe sang tay: chu cu va chu moi deu tung mang xe do toi gara, hai dong cung '
   . 'bien so khac chu la binh thuong. Ep UNIQUE thi nguoi nhap bi chan ma khong '
   . 'hieu vi sao');

$don = function() use ($pdo){
    $ids = $pdo->query("SELECT id FROM members WHERE email LIKE 'xe-test-%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $id){ $pdo->exec("DELETE FROM member_vehicles WHERE member_id = " . (int) $id); }
    $pdo->exec("DELETE FROM members WHERE email LIKE 'xe-test-%'");
};
$don();

$now = date('Y-m-d H:i:s');
$pdo->prepare("INSERT INTO members (email,password,name,phone,status,create_at) VALUES (?,?,?,?,1,?)")
    ->execute(['xe-test-a@tanphat.vn', 'x', 'XE Test Nguyen Van A', '0900000001', $now]);
$khA = (int) $pdo->lastInsertId();

$pdo->prepare("INSERT INTO members (email,password,name,phone,status,create_at) VALUES (?,?,?,?,1,?)")
    ->execute(['xe-test-b@tanphat.vn', 'x', 'XE Test Tran Thi B', '0900000002', $now]);
$khB = (int) $pdo->lastInsertId();

$xe = new MemberVehiclesModel();

// Khách A có BA xe — đúng tình huống trong ghi chú nghiệp vụ
$xe->add(['member_id' => $khA, 'bien_so' => '30A-123.45', 'hang_xe' => 'Toyota', 'model_xe' => 'Camry', 'so_km' => 120000]);
$xe->add(['member_id' => $khA, 'bien_so' => '30A-678.90', 'hang_xe' => 'Ford',   'model_xe' => 'Ranger', 'so_km' => 80000]);
$xe->add(['member_id' => $khA, 'bien_so' => '30A-111.11', 'hang_xe' => 'Mazda',  'model_xe' => 'CX5']);
// Khách B một xe
$xe->add(['member_id' => $khB, 'bien_so' => '51F-222.22', 'hang_xe' => 'Kia',    'model_xe' => 'Morning']);

ok(count($xe->getByMember($khA)) === 3, 'Mot khach giu duoc NHIEU xe (3 xe)');
ok(count($xe->getByMember($khB)) === 1, 'Khach khac khong bi lan xe');

$mot = $xe->getByMember($khA)[0];
ok($mot['bien_so'] === '30A-123.45',      'Giu nguyen bien so nhu nguoi nhap (de in ra)');
ok($mot['bien_so_chuan'] === '30A12345',  'Va luu them ban chuan hoa de tra cuu');
ok((int) $mot['so_km'] === 120000,        'Luu duoc so km');

// --- Tra theo biển số, gõ kiểu gì cũng phải ra ---
foreach (['30A-123.45', '30a 123 45', '30A12345', '30a12345'] as $go){
    $kq = $xe->timTheoBienSo($go);
    $ok = false;
    foreach ($kq as $r){ if ((int) $r['member_id'] === $khA && $r['bien_so_chuan'] === '30A12345') $ok = true; }
    ok($ok, 'timTheoBienSo("' . $go . '") ra dung xe cua khach A');
}
$kq = $xe->timTheoBienSo('30A-123.45');
ok(!empty($kq) && $kq[0]['ten_khach'] === 'XE Test Nguyen Van A',
   'Ket qua kem luon ten chu xe',
   'CSKH nhan dien thoai chi co bien so, can biet ngay ai la chu');

// --- Tìm ở DANH SÁCH khách hàng theo biển số ---
$mm = new MembersModel();

$ds = $mm->adminList('30A-678.90', '', 50, 0);
$ten = array_map(function($x){ return $x['name']; }, $ds);
ok(in_array('XE Test Nguyen Van A', $ten, true),
   'Danh sach khach: tim "30A-678.90" ra dung chu xe');
ok(!in_array('XE Test Tran Thi B', $ten, true), 'Khong keo theo khach khong lien quan');

// Gõ kiểu khác vẫn ra
$ds2 = $mm->adminList('30a 678 90', '', 50, 0);
ok(in_array('XE Test Nguyen Van A', array_map(function($x){ return $x['name']; }, $ds2), true),
   'Go "30a 678 90" (khac dinh dang) van ra dung khach');

// KHÔNG nhân dòng: khách A có 3 xe nhưng chỉ hiện 1 lần
$ds3 = $mm->adminList('30A', '', 50, 0);
$demA = 0;
foreach ($ds3 as $x){ if ($x['name'] === 'XE Test Nguyen Van A') $demA++; }
ok($demA === 1,
   'Khach 3 xe cung chi hien MOT dong trong danh sach',
   'Dung JOIN thay vi EXISTS la khach hien ra 3 lan; dem duoc ' . $demA . ' dong');

// Đếm phải khớp với danh sách
ok($mm->adminCount('30A-678.90', '') === count($mm->adminList('30A-678.90', '', 50, 0)),
   'adminCount khop voi so dong adminList tra ve',
   'Lech thi phan trang bao sai so trang');

// Từ khoá không có chữ/số nào không được biến thành "khớp mọi biển số"
$dsRac = $mm->adminList('---', '', 50, 0);
$tenRac = array_map(function($x){ return $x['name']; }, $dsRac);
ok(!in_array('XE Test Nguyen Van A', $tenRac, true),
   'Go "---" KHONG lam khop moi bien so',
   'Chuan hoa ra chuoi rong -> LIKE "%%" se khop tat ca');

// Tên/email/SĐT vẫn tìm được như cũ
ok(count($mm->adminList('XE Test Tran Thi B', '', 50, 0)) === 1, 'Van tim duoc theo TEN');
ok(count($mm->adminList('0900000002', '', 50, 0)) === 1,          'Van tim duoc theo SDT');

// --- Cập nhật số km (việc làm mỗi lần xe vào gara) ---
$xe->edit(['bien_so' => '30A-123.45', 'so_km' => 125000], (int) $mot['id']);
$sau = $xe->getDetail((int) $mot['id']);
ok((int) $sau['so_km'] === 125000, 'Cap nhat duoc so km');
ok($sau['bien_so_chuan'] === '30A12345', 'Sua xong ban chuan hoa van dung');

// --- Xoá khách thì xe đi theo ---
$pdo->exec("DELETE FROM members WHERE id = " . $khB);
$conXe = $pdo->prepare("SELECT COUNT(*) FROM member_vehicles WHERE member_id = ?");
$conXe->execute([$khB]);
ok((int) $conXe->fetchColumn() === 0,
   'Xoa khach -> xe cua ho bi xoa theo (CASCADE)',
   'Con lai la dong xe mo coi, tra bien so ra chu khong ton tai');

$don();

exit(summary());

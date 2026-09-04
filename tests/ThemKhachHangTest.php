<?php
/**
 * Test THÊM KHÁCH HÀNG TẠI GARA.
 *
 * Chạy:  C:\xampp\php\php.exe tests\ThemKhachHangTest.php
 *
 * Màn CSKH → Khách hàng ban đầu chỉ để XEM và KHOÁ tài khoản khách tự đăng ký
 * trên web, nên cố tình không có nút Thêm. Từ khi nó gánh thêm việc quản lý xe
 * của khách thì thiếu hẳn một nửa: khách vãng lai lái xe tới gara không tạo
 * được hồ sơ, nên cũng không khai được biển số.
 *
 * BA CHỖ DỄ HỎNG:
 *
 *   1. Email trống lưu thành CHUỖI RỖNG thay vì NULL.
 *      Cột có khoá UNIQUE. MySQL cho nhiều dòng NULL, nhưng chuỗi rỗng thì
 *      bằng chính nó — khách vãng lai THỨ HAI sẽ bị báo trùng email. Lỗi này
 *      chỉ lộ ra ở khách thứ hai, nên thử một lần là thấy chạy tốt.
 *
 *   2. Không có mật khẩu mà để trống ô `password`.
 *      password_verify() với chuỗi rỗng có thể khớp ngoài ý muốn. Phải gán một
 *      chuỗi băm ngẫu nhiên: có mật khẩu hợp lệ nhưng không ai đăng nhập được.
 *
 *   3. Quên cấp quyền `add` cho module customers.
 *      Nút không hiện (view hỏi route() trước khi vẽ) VÀ RoleMiddleware chặn
 *      thẳng URL. Code đủ cả mà bấm không vào được.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Man hinh duoc noi vao he thong');

$routes = file_get_contents($goc . 'routes/web.php');
ok(strpos($routes, "'customers/add'") !== false, 'Co route customers/add');
ok(is_file($goc . 'app/views/admin/customers/add.php'), 'Co view customers/add.php');

$lists = file_get_contents($goc . 'app/views/admin/customers/lists.php');
ok(strpos($lists, "route('admin/'.\$routeBase.'/add')") !== false,
   'Nut Them an theo quyen `add`');
ok(strpos($lists, 'Thêm khách hàng') !== false, 'Danh sach co nut Them khach hang');

$ctl = codeOnly($goc . 'app/controllers/admin/Customers.php');
ok(strpos($ctl, 'public function postAdd') !== false, 'Controller co postAdd()');
ok(strpos($ctl, 'adminAdd') !== false, 'postAdd() dung MembersModel::adminAdd()');

/* Vẫn KHÔNG có xoá: khách có thể đã phát sinh đơn hàng, đánh giá, xe. */
ok(strpos($ctl, 'public function delete') === false,
   'Van KHONG co chuc nang xoa khach',
   'Khach da phat sinh don hang / danh gia / xe — chan thi khoa tai khoan, khong xoa');

// ---------------------------------------------------------------------------
section('Migration noi rang buoc email');

$mg  = glob($goc . 'database/migrations/*_khach_vang_lai_khong_can_email.php');
ok(!empty($mg), 'Co migration noi rang buoc email');
$src = !empty($mg) ? file_get_contents($mg[0]) : '';
ok(strpos($src, 'MODIFY `email` VARCHAR(150) NULL') !== false, 'Migration cho email de trong');
ok(strpos($src, "'add'") !== false && strpos($src, 'permissions') !== false,
   'Migration cap quyen `add` cho module customers',
   'Thieu thi nut khong hien va RoleMiddleware chan thang URL');

// ---------------------------------------------------------------------------
section('Chay that tren MySQL');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

/* Cột email phải NULL được — đây là điều kiện của mọi khẳng định bên dưới */
$cot = null;
foreach ($pdo->query("SHOW COLUMNS FROM `members`")->fetchAll(PDO::FETCH_ASSOC) as $c){
    if ($c['Field'] === 'email') $cot = $c;
}
ok(!empty($cot) && $cot['Null'] === 'YES', 'Cot `members`.`email` de trong duoc');
if (empty($cot) || $cot['Null'] !== 'YES'){
    echo "\n[SKIP] Chua chay migration noi rang buoc email.\n"; exit(summary());
}

/* Quyền `add` phải có thật trong CSDL, không chỉ có trong file migration */
$soAdd = (int) $pdo->query(
    "SELECT COUNT(*) FROM permissions p JOIN modules m ON m.id = p.module_id
      WHERE m.link = 'customers' AND p.role = 'add'")->fetchColumn();
ok($soAdd >= 1, 'Quyen `add` cua module customers CO trong CSDL');

require_once $goc . 'app/models/MembersModel.php';
$M = new MembersModel();

$demTruoc = (int) $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();

/* --- 1. HAI khách vãng lai, cả hai đều không có email --- */
$id1 = $M->adminAdd(['name' => 'ZZ Khach Mot', 'phone' => '0900000001']);
ok($id1 > 0, 'Tao duoc khach vang lai thu nhat');

$id2 = 0; $loi = '';
try { $id2 = $M->adminAdd(['name' => 'ZZ Khach Hai', 'phone' => '0900000002']); }
catch (\Throwable $e){ $loi = $e->getMessage(); }
ok($id2 > 0, 'Tao duoc khach vang lai THU HAI (cung khong email)',
   'Luu chuoi rong thay vi NULL thi khoa UNIQUE chan o day: ' . $loi);

$e1 = $pdo->query("SELECT email FROM members WHERE id = $id1")->fetchColumn();
$e2 = $pdo->query("SELECT email FROM members WHERE id = $id2")->fetchColumn();
ok($e1 === null && $e2 === null, 'Email trong luu NULL, khong phai chuoi rong');
ok((int) $pdo->query("SELECT COUNT(*) FROM members WHERE email = ''")->fetchColumn() === 0,
   'Khong dong nao co email la chuoi rong');

/* --- 2. Mật khẩu không đoán được, và không đăng nhập được --- */
$mk = $pdo->query("SELECT password FROM members WHERE id = $id1")->fetchColumn();
ok(is_string($mk) && strlen($mk) >= 55, 'Khach khong mat khau van co chuoi bam hop le');
ok(!password_verify('', $mk),         'Chuoi rong KHONG dang nhap duoc');
ok(!password_verify('123456', $mk),   'Mat khau thuong gap KHONG dang nhap duoc');
ok($M->checkLogin('', '') === null,   'Dang nhap bang email rong tra ve null');
ok($M->checkLogin(null, '') === null, 'Dang nhap bang email null tra ve null');

/* --- 3. Có email thì vẫn hoạt động như cũ --- */
$id3 = $M->adminAdd(['name' => 'ZZ Khach Ba', 'phone' => '0900000003',
                     'email' => 'zz-khachba@local.test', 'password' => 'MatKhau123']);
$m3  = $M->getDetail($id3);
ok($m3['email'] === 'zz-khachba@local.test', 'Khach co email luu dung');
ok($M->checkLogin('zz-khachba@local.test', 'MatKhau123') !== null,
   'Khach co mat khau van dang nhap duoc');
ok($M->checkLogin('zz-khachba@local.test', 'SaiRoi') === null, 'Mat khau sai bi tu choi');

/* Trùng email vẫn phải bị chặn — nới NULL không được làm hỏng chuyện này */
ok(!empty($M->findByEmail('zz-khachba@local.test')), 'findByEmail() tim ra khach da co');
$trungEmail = false;
try { $M->adminAdd(['name' => 'ZZ Trung Mail', 'email' => 'zz-khachba@local.test']); }
catch (\Throwable $e){ $trungEmail = true; }
ok($trungEmail, 'Trung email THAT van bi khoa UNIQUE chan',
   'Noi NULL khong duoc lam mat rang buoc nay');

/* --- 4. Tra theo số điện thoại (canh báo tạo trùng người) --- */
$co = $M->findByPhone('0900000001');
ok(!empty($co) && (int) $co['id'] === (int) $id1, 'findByPhone() tim dung khach');
ok($M->findByPhone('') === null, 'findByPhone() voi chuoi rong tra ve null',
   'Khong chan thi moi khach khong sdt deu bi bao trung nhau');
ok(empty($M->findByPhone('0999999999')), 'So chua ai dung thi khong tim ra ai');

/* --- 5. Khách vãng lai gắn được xe — đúng lý do màn hình này ra đời --- */
require_once $goc . 'app/models/MemberVehiclesModel.php';
$XE = new MemberVehiclesModel();
$xeId = $XE->add(['member_id' => $id1, 'bien_so' => '30A-999.99', 'so_km' => 50000]);
ok($xeId > 0, 'Khach vang lai khai duoc bien so xe');
$tim = $XE->timTheoBienSo('30a99999');
ok(!empty($tim), 'Tra bien so ra dung khach vua tao');

// Dọn sạch
$pdo->prepare("DELETE FROM members WHERE id IN (?, ?, ?)")->execute([$id1, $id2, $id3]);
$demSau = (int) $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
ok($demSau === $demTruoc, 'Da don sach du lieu test');

exit(summary());

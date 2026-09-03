<?php
/**
 * Test NHIỀU GARA — tầng 1: khái niệm gara.
 *
 * Chạy:  C:\xampp\php\php.exe tests\NhieuGaraTest.php
 *
 * BA CHỖ DỄ HỎNG NHẤT, và vì sao có khẳng định riêng cho từng chỗ:
 *
 *   1. Dữ liệu cũ mất chủ. Migration thêm cột `garage_id` rồi phải gán hết dữ
 *      liệu đang có về gara tổng. Sót một bảng thì kho / báo giá cũ thành vô
 *      chủ, và mọi báo cáo theo gara sau này thiếu đúng phần dữ liệu lịch sử.
 *
 *   2. Có hai gara tổng, hoặc không có cái nào. Cờ `is_master` quyết định ai
 *      sở hữu danh mục tổng. Hai cái thì getMaster() trả về tuỳ thứ tự truy
 *      vấn; không cái nào thì nó phải đoán bừa.
 *
 *   3. Đổi gara ghi nhầm vào CSDL. Ô đổi gara chỉ được ghi session của chính
 *      người bấm. Ghi vào `users.garage_id` thì giám đốc xem hộ chi nhánh một
 *      lát là đổi luôn nơi làm việc của chính mình.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Migration dung hinh');

$mg = glob($goc . 'database/migrations/*_them_bang_gara.php');
ok(!empty($mg), 'Co migration tao bang gara');

$src = !empty($mg) ? file_get_contents($mg[0]) : '';
ok(strpos($src, 'CREATE TABLE IF NOT EXISTS `garages`') !== false,
   'Migration tao bang `garages`');
ok(strpos($src, 'ON DELETE SET NULL') !== false,
   'Khoa ngoai dat ON DELETE SET NULL',
   'CASCADE thi xoa mot gara la keo theo ca bao gia, hoa don cua no');
ok(substr_count($src, "'warehouses'") >= 1 && substr_count($src, "'users'") >= 1
   && substr_count($src, "'quotations'") >= 1 && substr_count($src, "'sales_invoices'") >= 1,
   'Gan garage_id cho ca 4 bang: warehouses, users, quotations, sales_invoices');
ok(strpos($src, 'SHOW COLUMNS FROM `$bang`') !== false,
   'Kiem cot bang SHOW COLUMNS roi loc bang PHP',
   'SHOW COLUMNS ... LIKE ? bi MySQL tu choi (loi 1064) va try/catch nuot mat');

// Đăng ký module: quên bước này thì màn hình có mà không ai vào được.
ok(strpos($src, "'garages'") !== false && strpos($src, 'permissions') !== false,
   'Migration dang ky module `garages` + quyen cho nhom Admin',
   'Thieu dong trong `modules` thi menu khong hien va RoleMiddleware khong gac duoc');

// ---------------------------------------------------------------------------
section('Man hinh duoc noi vao he thong');

$routes = file_get_contents($goc . 'routes/web.php');
foreach (['garages', 'garages/add', 'garages/edit/(\d+)', 'garages/delete/(\d+)', 'garages/doi/(\d+)'] as $r){
    ok(strpos($routes, "'" . $r . "'") !== false, "Co route $r");
}

$sidebar = file_get_contents($goc . 'app/views/layouts/admin/sidebar.php');
ok(preg_match("~'Hệ thống'\s*=>\s*\[[^\]]*'garages'~u", $sidebar) === 1,
   'Menu trai co muc gara trong nhom He thong',
   'Man hinh khong nam trong $menuGroups thi khong bao gio hien ra');

foreach (['lists', 'add', 'edit'] as $v){
    ok(is_file($goc . 'app/views/admin/garages/' . $v . '.php'), "Co view garages/$v.php");
}
ok(is_file($goc . 'app/controllers/admin/Garages.php'), 'Co controller Garages');
ok(is_file($goc . 'app/models/GaragesModel.php'), 'Co GaragesModel');

// ---------------------------------------------------------------------------
section('Chot chan trong controller');

$ctl = codeOnly($goc . 'app/controllers/admin/Garages.php');
ok(strpos($ctl, 'is_master') !== false && strpos($ctl, 'dangDungODau') !== false,
   'Controller co kiem gara tong va kiem rang buoc truoc khi xoa');
/* Soi RIÊNG thân hàm doi() chứ không soi cả file: khẳng định "cả controller
   không nhắc tới users" là vô dụng — chỉ cần ai đó viết `UsersModel` (chữ U
   hoa) là lọt, mà đó đúng là cách người ta sẽ viết. */
$than = '';
if (preg_match('~public function doi\([^)]*\)\s*\{(.*?)\n    \}~s', $ctl, $mD)) $than = $mD[1];
ok($than !== '', 'Doc duoc than ham doi()');
ok(strpos($than, "Session::set('garage_id'") !== false,
   'doi() ghi gara vao SESSION');
ok(stripos($than, 'usersmodel') === false && stripos($than, '->edit(') === false
   && stripos($than, '->update') === false,
   'doi() KHONG ghi gi xuong CSDL',
   'Ghi vao users.garage_id thi xem ho chi nhanh mot lat la doi luon noi lam viec');

// Chỉ nhận đường dẫn nội bộ khi quay lại sau lúc đổi gara.
ok(strpos($ctl, 'HTTP_REFERER') !== false && strpos($ctl, '_WEB_URL') !== false,
   'Quay lai sau khi doi gara chi nhan duong dan noi bo',
   'Nhan bua Referer la mo duong cho link day nguoi dung sang trang ngoai');

// ---------------------------------------------------------------------------
section('O chon gara tren cac form');

$vUser = file_get_contents($goc . 'app/views/admin/users/edit.php');
ok(strpos($vUser, 'name="garage_id"') !== false, 'Man Nguoi dung co o chon gara');
$vUserAdd = file_get_contents($goc . 'app/views/admin/users/add.php');
ok(strpos($vUserAdd, 'name="garage_id"') !== false, 'Man Them nguoi dung co o chon gara');

$vKho = file_get_contents($goc . 'app/views/admin/warehouses/edit.php');
ok(strpos($vKho, 'name="garage_id"') !== false, 'Man Kho co o chon gara');

/* Đếm đúng CÂU GHI (`'garage_id' => ...`), không đếm mọi lần chuỗi
   "garage_id" xuất hiện: hàm đọc giá trị từ form đã nhắc tên cột 2 lần rồi,
   nên đếm suông thì bỏ hẳn một chiều ghi vẫn đủ số và vẫn xanh. */
$ctlUser = codeOnly($goc . 'app/controllers/admin/Users.php');
ok(substr_count($ctlUser, "'garage_id' => \$this->garaTuForm()") === 2,
   'Users controller ghi garage_id ca luc them LAN luc sua',
   'Chi ghi mot chieu thi o chon hien ra nhung bam Luu khong an gi');

// ---------------------------------------------------------------------------
section('Thanh dau trang');

$header = file_get_contents($goc . 'app/views/layouts/admin/header.php');
ok(strpos($header, "route('admin/garages')") !== false,
   'O doi gara chi hien khi co quyen xem module gara');
ok(strpos($header, 'count($dsGara) > 1') !== false,
   'An o doi gara khi ca he thong chi co mot gara');
ok(strpos($header, '@if') === false && strpos($header, '@foreach') === false,
   'Header viet bang PHP thuan, khong dung cu phap template',
   'Layout KHONG di qua Template::run() nen @if/{{ }} se in ra nguyen van');

$provider = file_get_contents($goc . 'app/providers/AppServiceProvider.php');
ok(strpos($provider, 'garaHienTai') !== false && strpos($provider, 'dsGara') !== false,
   'Gara hien tai duoc chia se cho moi man hinh admin',
   'De tung controller tu nap thi thieu o mot cho la o doi gara bien mat dung o do');

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

$co = $pdo->query("SHOW TABLES LIKE 'garages'")->fetchAll();
if (empty($co)){
    echo "\n[SKIP] Chua chay migration tao bang `garages`.\n";
    exit(summary());
}

/* --- 1. Không còn dòng nào vô chủ --- */
foreach (['warehouses', 'users', 'quotations', 'sales_invoices'] as $bang){
    $cot = $pdo->query("SHOW COLUMNS FROM `$bang`")->fetchAll(PDO::FETCH_COLUMN);
    ok(in_array('garage_id', $cot, true), "Bang `$bang` co cot garage_id");

    $trong = (int) $pdo->query("SELECT COUNT(*) FROM `$bang` WHERE garage_id IS NULL")->fetchColumn();
    ok($trong === 0, "Khong con dong nao vo chu trong `$bang`",
       "Con $trong dong garage_id IS NULL — migration gan sot");
}

/* --- 2. Đúng MỘT gara tổng --- */
$soMaster = (int) $pdo->query("SELECT COUNT(*) FROM garages WHERE is_master = 1")->fetchColumn();
ok($soMaster === 1, 'Co dung MOT gara tong', "Dang co $soMaster — getMaster() se phai doan bua");

/* --- 3. Khoá ngoại phải là SET NULL, không phải CASCADE --- */
$fk = $pdo->query("SELECT TABLE_NAME, DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS
                    WHERE CONSTRAINT_SCHEMA = DATABASE()
                      AND REFERENCED_TABLE_NAME = 'garages'")->fetchAll(PDO::FETCH_ASSOC);
ok(count($fk) === 4, 'Ca 4 bang deu co khoa ngoai tro ve garages', 'Dang co ' . count($fk));
$sai = [];
foreach ($fk as $f) if ($f['DELETE_RULE'] !== 'SET NULL') $sai[] = $f['TABLE_NAME'] . '=' . $f['DELETE_RULE'];
ok(empty($sai), 'Moi khoa ngoai deu ON DELETE SET NULL', implode(', ', $sai));

/* --- 4. Model chạy thật --- */
require_once $goc . 'app/models/GaragesModel.php';
$m = new GaragesModel();

$master = $m->getMaster();
ok(!empty($master) && (int) $master['is_master'] === 1, 'getMaster() tra ve dung gara tong');

$soTruoc = count($m->getLists());
$idA = $m->add(['code' => 'ZZA', 'name' => 'Gara thu A', 'is_master' => 0, 'status' => 1, 'sort_order' => 90]);
$idB = $m->add(['code' => 'ZZB', 'name' => 'Gara thu B', 'is_master' => 0, 'status' => 0, 'sort_order' => 91]);
ok(count($m->getLists()) === $soTruoc + 2, 'Them duoc gara moi');

$maCode = $m->findByCode('ZZA');
ok(!empty($maCode) && (int) $maCode['id'] === (int) $idA, 'Tim duoc gara theo ma');

/* Gara đang tắt KHÔNG được hiện trong ô chọn: chọn phải nó thì lập chứng từ
   cho một chi nhánh đã đóng cửa. */
$ids = array_map(function($g){ return (int) $g['id']; }, $m->getActive());
ok(in_array((int) $idA, $ids, true),  'Gara dang bat CO trong o chon');
ok(!in_array((int) $idB, $ids, true), 'Gara dang tat KHONG co trong o chon');

/* Đánh dấu gara tổng mới -> gara tổng cũ phải tự nhường */
$m->edit(['is_master' => 1], $idA);
$m->clearMasterExcept($idA);
$soMaster2 = (int) $pdo->query("SELECT COUNT(*) FROM garages WHERE is_master = 1")->fetchColumn();
ok($soMaster2 === 1, 'Van chi co MOT gara tong sau khi chuyen');
$mm = $m->getMaster();
ok(!empty($mm) && (int) $mm['id'] === (int) $idA, 'Gara tong moi dung la gara vua danh dau');

// Trả lại nguyên trạng
$m->edit(['is_master' => 1], $master['id']);
$m->clearMasterExcept($master['id']);

/* dangDungODau(): gara rỗng thì không có ràng buộc nào */
ok($m->dangDungODau($idB) === [], 'Gara chua co gi thi khong bao rang buoc');

$pdo->prepare("UPDATE warehouses SET garage_id = ? WHERE id = (SELECT id FROM (SELECT MIN(id) AS id FROM warehouses) t)")
    ->execute([$idA]);
$dung = $m->dangDungODau($idA);
ok(isset($dung['kho']) && $dung['kho'] >= 1, 'Gara dang giu kho thi bao ro la co kho');

// Trả kho về gara tổng
$pdo->prepare("UPDATE warehouses SET garage_id = ? WHERE garage_id = ?")->execute([$master['id'], $idA]);

/* --- 5. Xoá gara KHÔNG được kéo theo dữ liệu --- */
$pdo->prepare("UPDATE warehouses SET garage_id = ? WHERE id = (SELECT id FROM (SELECT MIN(id) AS id FROM warehouses) t)")
    ->execute([$idA]);
$khoTruoc = (int) $pdo->query("SELECT COUNT(*) FROM warehouses")->fetchColumn();
$m->remove($idA);
$khoSau = (int) $pdo->query("SELECT COUNT(*) FROM warehouses")->fetchColumn();
ok($khoSau === $khoTruoc, 'Xoa gara KHONG lam mat kho cua no',
   'ON DELETE CASCADE nham cho la xoa mot gara keo theo ca kho va chung tu');
$moCoi = (int) $pdo->query("SELECT COUNT(*) FROM warehouses WHERE garage_id IS NULL")->fetchColumn();
ok($moCoi >= 1, 'Kho cua gara da xoa tro thanh vo chu (SET NULL), khong bien mat');

// Dọn sạch
$pdo->prepare("UPDATE warehouses SET garage_id = ? WHERE garage_id IS NULL")->execute([$master['id']]);
$m->remove($idB);
ok(count($m->getLists()) === $soTruoc, 'Da don sach du lieu test');

exit(summary());

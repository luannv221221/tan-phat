<?php
/**
 * Test MÀN HÌNH QUẢN LÝ MODULE.
 *
 * Chạy:  C:\xampp\php\php.exe tests\QuanLyModuleTest.php
 *
 * BỐI CẢNH
 * Bảng `modules` trước đây chỉ nạp được bằng migration: muốn đưa một màn hình
 * vào bảng phân quyền là phải sửa mã nguồn rồi chạy migration. Nay có màn hình
 * đăng ký bằng giao diện.
 *
 * ĐIỂM DỄ HỎNG NHẤT — và là lý do ô "Màn hình" phải là DANH SÁCH CHỌN:
 * RoleMiddleware ghép 'admin/'.$link.'/*' rồi so với URL đang mở. Nếu không
 * khớp module nào thì $currentModuleId = 0 và nó BỎ QUA luôn phần kiểm quyền
 * — nghĩa là gõ sai đường dẫn không tạo ra "màn hình bị khoá", mà tạo ra
 * "màn hình KHÔNG AI GÁC". Sai kiểu im lặng, nguy hơn sai kiểu báo lỗi.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
        _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL. Dien DB_* trong .env de chay test nay.\n";
    exit(0);
}

require_once __DIR__ . '/../app/models/ModulesModel.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---- Don du lieu test cu ----
$don = function() use ($pdo){
    $ids = $pdo->query("SELECT id FROM modules WHERE link LIKE 'md-test-%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $id){ $pdo->exec("DELETE FROM permissions WHERE module_id = " . (int) $id); }
    $pdo->exec("DELETE FROM modules WHERE link LIKE 'md-test-%'");
};
$don();

$m = new ModulesModel();

// ---------------------------------------------------------------------------
section('Man hinh + route + view co du');

ok(is_file($goc . 'app/controllers/admin/Modules.php'), 'Co controller admin/Modules.php');
foreach (['lists', 'add', 'edit'] as $v){
    ok(is_file($goc . "app/views/admin/modules/$v.php"), "Co view modules/$v.php");
}

$routes = file_get_contents($goc . 'routes/web.php');
foreach ([
    "Route::get('modules'",
    "Route::get('modules/add'",
    "Route::post('modules/add'",
    "Route::get('modules/edit/(\\d+)'",
    "Route::post('modules/edit/(\\d+)'",
    "Route::get('modules/delete/(\\d+)'",
] as $r){
    ok(strpos($routes, $r) !== false, 'Co route: ' . $r);
}

// ---------------------------------------------------------------------------
section('Module cua chinh man hinh nay da duoc gieo');

$tuNo = $m->findByLink('modules');
ok(!empty($tuNo), 'Co dong modules.link = "modules"',
   'Man hinh Quan ly module cung phai la mot module thi RoleMiddleware moi gac duoc');

if (!empty($tuNo)){
    $q = $pdo->prepare("SELECT COUNT(DISTINCT role) FROM permissions WHERE module_id = ?");
    $q->execute([$tuNo['id']]);
    ok((int) $q->fetchColumn() === 4, 'Nhom Admin co du 4 quyen view/add/edit/delete');
}

// ---------------------------------------------------------------------------
section('Danh sach cho CHON — khong phai go tay');

$tren = $m->manHinhTrenDia();
ok(!empty($tren), 'Doc duoc danh sach man hinh tren dia', count($tren) . ' man hinh');
ok(!in_array('print', $tren, true),
   'Loai thu muc `print` khoi danh sach',
   'print la mau in dung chung cho bao gia/hoa don, khong co URL rieng nen '
   . 'khong the la mot module');

$daDangKy = [];
foreach ($m->getLists() as $x){ if (!empty($x['link'])) $daDangKy[] = strtolower($x['link']); }
$chua = $m->manHinhChuaDangKy();

$lot = array_intersect($chua, $daDangKy);
ok(empty($lot), 'Danh sach chon KHONG chua man hinh da co module',
   'lot: ' . implode(',', $lot));

foreach ($chua as $c){
    // mọi mục trong danh sách chọn phải là thư mục có thật
    if (!in_array($c, $tren, true)){ ok(false, "Muc \"$c\" khong co tren dia"); }
}
ok(true, 'Moi muc trong danh sach chon deu la man hinh co that');

// ---------------------------------------------------------------------------
section('Phat hien module mo coi');

// Dựng một module trỏ tới màn hình không tồn tại
$pdo->prepare("INSERT INTO modules (name, link, create_at) VALUES (?,?,?)")
    ->execute(['MD Test mo coi', 'md-test-khong-ton-tai', date('Y-m-d H:i:s')]);

$mc = (new ModulesModel())->moduleMoCoi();
$link = array_map(function($x){ return $x['link']; }, $mc);
ok(in_array('md-test-khong-ton-tai', $link, true),
   'Bat duoc module tro toi man hinh khong con ton tai',
   'Quyen cap cho no la quyen chet — bang phan quyen van tick nhung chang gac gi');

ok(!in_array('modules', $link, true),
   'Module co man hinh that KHONG bi bao la mo coi');

// ---------------------------------------------------------------------------
section('Go module thi go LUON phan quyen cua no');

$idMoCoi = (int) (new ModulesModel())->findByLink('md-test-khong-ton-tai')['id'];
$nhom = $pdo->query("SELECT id FROM `groups` LIMIT 1")->fetchColumn();

if (!empty($nhom)){
    foreach (['view', 'add'] as $r){
        $pdo->prepare("INSERT INTO permissions (module_id, group_id, role) VALUES (?,?,?)")
            ->execute([$idMoCoi, $nhom, $r]);
    }
    $q = $pdo->prepare("SELECT COUNT(*) FROM permissions WHERE module_id = ?");
    $q->execute([$idMoCoi]);
    ok((int) $q->fetchColumn() === 2, 'Da gan 2 quyen cho module test');

    (new ModulesModel())->remove($idMoCoi);

    $q->execute([$idMoCoi]);
    ok((int) $q->fetchColumn() === 0,
       'Xoa module -> phan quyen cua no bi xoa theo',
       'Con sot lai la bang permissions tro toi module_id khong ton tai: nhin '
       . 'vao tuong dang co quyen ma thuc te chang gac gi');

    $con = $pdo->prepare("SELECT COUNT(*) FROM modules WHERE id = ?");
    $con->execute([$idMoCoi]);
    ok((int) $con->fetchColumn() === 0, 'Va dong module cung bi xoa');
} else {
    echo "  [SKIP] Khong co nhom nao de gan quyen test.\n";
}

// ---------------------------------------------------------------------------
section('Cac chot trong controller');

$ctrl = codeOnly($goc . 'app/controllers/admin/Modules.php');

ok(strpos($ctrl, "preg_match('~^[a-z0-9][a-z0-9-]*\$~', \$link)") !== false
   || preg_match('~preg_match\(.~\^\[a-z0-9\]\[a-z0-9-\]\*\\\$~.~', $ctrl) === 1,
   'Chan ky tu la trong duong dan',
   'RoleMiddleware ghep thang link vao bieu thuc so khop; ky tu la lam hong ca bieu thuc');

ok(strpos($ctrl, 'findByLink') !== false, 'Kiem trung duong dan truoc khi luu');

ok(strpos($ctrl, "\$item['link'] === \$this->routeBase") !== false,
   'Chan xoa chinh module "Quan ly module"',
   'Xoa xong la mat luon duong vao de dang ky lai, phai sua thang CSDL moi cuu duoc');

ok(strpos($ctrl, 'KHÔNG còn được kiểm quyền') !== false,
   'Bao ro hau qua khi go module',
   'Go xong man hinh do khong con ai gac — nguoi bam can biet dieu do');

// ---------------------------------------------------------------------------
section('Giao dien noi ro "dang ky" chu khong phai "tao"');

$vAdd = file_get_contents($goc . 'app/views/admin/modules/add.php');
ok(strpos($vAdd, 'không tạo ra màn hình mới') !== false
   || strpos($vAdd, 'Không tạo ra màn hình mới') !== false,
   'Form them noi ro no KHONG tao ra man hinh moi',
   'Nguoi moi rat de tuong bam "Them module" la de ra mot man hinh');

ok(strpos($vAdd, '<select name="link"') !== false,
   'Duong dan la O CHON, khong phai o go tu do');

$vList = file_get_contents($goc . 'app/views/admin/modules/lists.php');
ok(strpos($vList, 'Nhóm') !== false && strpos($vList, 'Phân quyền') !== false,
   'Danh sach chi duong sang buoc tiep theo (Nhom > Phan quyen)');

// ---------------------------------------------------------------------------
section('Co duong vao tu menu trai');

// Lan dau lam man hinh nay tui QUEN buoc nay: controller, view, route,
// migration deu xong va /admin/modules vao duoc — nhung menu trai khong co
// muc nao tro toi, nen thuc te khong ai tim ra. Lam xong mot man hinh ma
// khong noi vao menu thi coi nhu chua lam.
$sb = file_get_contents($goc . 'app/views/layouts/admin/sidebar.php');

ok(preg_match("~'Hệ thống'\s*=>\s*\[[^\]]*'modules'~s", $sb) === 1,
   'sidebar.php co "modules" trong nhom He thong',
   'Menu trai dung tu \$menuGroups; link nao khong nam trong do thi khong bao '
   . 'gio hien ra');

// Đứng ngay sau groups: hai màn hình này đi liền một mạch (đăng ký module ->
// phân quyền cho nhóm). Tách xa nhau thì người dùng không thấy được mạch đó.
if (preg_match("~'Hệ thống'\s*=>\s*\[([^\]]*)\]~s", $sb, $m)){
    $ds = array_map(function($x){ return trim($x, " '\"\t\r\n"); }, explode(',', $m[1]));
    $ds = array_values(array_filter($ds));
    $vg = array_search('groups', $ds, true);
    $vm = array_search('modules', $ds, true);
    ok($vg !== false && $vm !== false && $vm === $vg + 1,
       'Muc "modules" dung NGAY SAU "groups"',
       'thu tu thuc te: ' . implode(', ', $ds));
}

// ---------------------------------------------------------------------------
section('Migration');

$mg = glob($goc . 'database/migrations/*_them_module_quan_ly_module.php');
ok(!empty($mg), 'Co migration dang ky module "modules"');
if (!empty($mg)){
    $src = file_get_contents($mg[0]);
    ok(strpos($src, 'CREATE TABLE') === false && strpos($src, 'ALTER TABLE') === false,
       'Migration KHONG dung toi cau truc bang, chi them du lieu');
    ok(strpos($src, 'if (empty($has))') !== false,
       'Chi them quyen con thieu -> chay lai khong sinh dong trung');
}

// ---- Don dep ----
$don();

exit(summary());

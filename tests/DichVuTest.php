<?php
/**
 * Test MAN HINH DICH VU + TAB "Hang hoa / Dich vu" trong bao gia (19/08/2026).
 *
 * Chay:  C:\xampp\php\php.exe tests\DichVuTest.php
 *
 * Trong tam:
 *   - Dich vu KHONG co bang rieng: van la dong trong `parts` voi
 *     item_type='service'. Nho vay bao gia / hoa don van tro part_id nhu cu.
 *   - Man hinh Dich vu chi duoc dung toi dong dich vu. Khong chan thi
 *     /admin/services/delete/<id phu tung> xoa duoc hang hoa that.
 *   - Hai bang dong hang trong bao gia phai dung HAI bo ten o khac nhau.
 *     Dung chung ten thi thu tu phan tu phu thuoc thu tu DOM — doi cho tab
 *     mot cai la so luong nhay sang mat hang khac ma khong bao loi gi.
 *   - Tong bao gia = tien hang hoa + tien dich vu.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

$goc = __DIR__ . '/../';

try {
    $pdo = new PDO(
        'mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
        _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n";
    exit(0);
}

echo 'PHP ' . PHP_VERSION . "\n";

// ---------------------------------------------------------------------------
section('Module `services` da dang ky (thieu la RoleMiddleware da ve khong-co-quyen)');

$mod = $pdo->query("SELECT * FROM `modules` WHERE `link` = 'services'")->fetch(PDO::FETCH_ASSOC);
ok(!empty($mod), 'Co dong `services` trong bang modules');

if (!empty($mod)){
    ok($mod['name'] === 'Dịch vụ', 'Ten hien tren menu la "Dịch vụ"', 'dang la: ' . $mod['name']);

    $st = $pdo->prepare("SELECT p.role FROM `permissions` p
                         JOIN `groups` g ON g.id = p.group_id
                         WHERE p.module_id = ? AND g.name = 'Admin'");
    $st->execute([$mod['id']]);
    $roles = $st->fetchAll(PDO::FETCH_COLUMN);
    foreach (['view', 'add', 'edit', 'delete'] as $r){
        ok(in_array($r, $roles, true), "Nhom Admin co quyen `$r`");
    }
}

// ---------------------------------------------------------------------------
section('Dich vu van nam trong bang `parts`, khong tach bang rieng');

$bang = $pdo->query("SHOW TABLES LIKE 'services'")->fetchAll();
ok(empty($bang), 'KHONG tao bang `services` rieng',
   'Tach bang la moi bang dong hang phai mang them cap (loai, id)');

$col = $pdo->query("SHOW COLUMNS FROM `parts` LIKE 'item_type'")->fetch(PDO::FETCH_ASSOC);
ok(!empty($col), 'Cot `parts`.`item_type` van con');
ok(!empty($col) && strpos($col['Type'], "'service'") !== false, "item_type co gia tri 'service'");

// ---------------------------------------------------------------------------
section('Controller Services');

$f = $goc . 'app/controllers/admin/Services.php';
ok(is_file($f), 'Co file app/controllers/admin/Services.php');
$code = codeOnly($f);

ok(strpos($code, "'item_type'   => PartsModel::LOAI_DICH_VU") !== false
   || strpos($code, 'PartsModel::LOAI_DICH_VU') !== false,
   'Luon ghi item_type = service');

// Quan trong: KHONG duoc doc item_type tu form
ok(strpos($code, "\$f['item_type']") === false,
   'KHONG nhan item_type tu POST',
   'Nhan tu POST la mo duong cho mot dong "part" khong co ton lot vao bang cua sau');

ok(strpos($code, 'layDichVu') !== false, 'Co ham chan theo loai (layDichVu)');
ok(preg_match('/item_type\'\]\s*!==\s*PartsModel::LOAI_DICH_VU/', $code) === 1,
   'layDichVu() tu choi ban ghi khong phai dich vu');

foreach (['index', 'add', 'postAdd', 'edit', 'postEdit', 'delete'] as $h){
    ok(preg_match('/function\s+' . $h . '\s*\(/', $code) === 1, "Co ham $h()");
}

// Sua / xoa deu phai qua cua chan loai
foreach (['edit', 'postEdit', 'delete'] as $h){
    ok(preg_match('/function\s+' . $h . '\s*\([^)]*\)\s*\{\s*\$item\s*=\s*\$this->layDichVu/', $code) === 1,
       "$h() goi layDichVu() ngay dau ham");
}

// ---------------------------------------------------------------------------
section('Route + menu');

$routes = file_get_contents($goc . 'routes/web.php');
foreach (['services', 'services/add', 'services/edit/(\d+)', 'services/delete/(\d+)'] as $r){
    ok(strpos($routes, "'" . $r . "'") !== false, "Co route $r");
}
ok(strpos($routes, "Route::post('services/add'") !== false,  'Co route POST services/add');
ok(strpos($routes, "Route::post('services/edit/(\\d+)'") !== false, 'Co route POST services/edit');

$sidebar = file_get_contents($goc . 'app/views/layouts/admin/sidebar.php');
ok(preg_match("/'Hàng hoá'\s*=>\s*\[[^\]]*'services'/u", $sidebar) === 1,
   'Menu "Hang hoa" co muc `services`');

// ---------------------------------------------------------------------------
section('Man hinh Dich vu gon hon man hinh Hang hoa');

$add = file_get_contents($goc . 'app/views/admin/services/add.php');

foreach (['name', 'price'] as $o){
    ok(strpos($add, 'name="' . $o . '"') !== false, "Co o `$o`");
}
// Nhung o CUA HANG HOA khong duoc xuat hien o day
foreach (['oem_code', 'brand_id', 'manufacturer_id', 'origin_id', 'sale_price', 'fitment'] as $o){
    ok(strpos($add, 'name="' . $o . '"') === false, "KHONG co o `$o` (khong thuoc dich vu)");
}
ok(strpos($add, 'name="item_type"') === false,
   'Khong co o chon Loai — man hinh nay chi tao dich vu');
ok(strpos($add, 'name="slug"') === false,
   'Khong bat nhap slug (tu sinh, tu ne trung)');

ok(preg_match('/name="price"[^>]*type="number"|type="number"[^>]*name="price"/', $add) === 1,
   'O Gia dung type="number"');

// ---------------------------------------------------------------------------
section('Ma va slug tu sinh');

$parts = codeOnly($goc . 'app/models/PartsModel.php');
ok(strpos($parts, 'function nextCode') !== false,  'PartsModel::nextCode()');
ok(strpos($parts, 'function slugTrong') !== false, 'PartsModel::slugTrong()');
ok(strpos($parts, 'findByCode($ma)') !== false,
   'nextCode() kiem tra lai ma da ton tai chua (phong ma nhap tay chen vao)');

require_once $goc . 'app/models/LookupModel.php';
require_once $goc . 'app/models/PartsModel.php';
require_once $goc . 'app/helpers/functions.php';

$pm = new PartsModel();
$ma = $pm->nextCode('DV-');
ok(preg_match('/^DV-\d{4,}$/', $ma) === 1, "nextCode('DV-') ra dang DV-0001", 'duoc: ' . $ma);
$st = $pdo->prepare('SELECT COUNT(*) FROM `parts` WHERE `code` = ?');
$st->execute([$ma]);
ok((int) $st->fetchColumn() === 0, 'Ma sinh ra chua ai dung');

// ---------------------------------------------------------------------------
section('Bao gia: hai tab dung HAI bo ten o');

foreach (['add', 'edit'] as $v){
    $s = file_get_contents($goc . 'app/views/admin/quotations/' . $v . '.php');

    ok(strpos($s, "data-pane=\"hang\"") !== false || strpos($s, "'hang'") !== false,
       "quotations/$v: co tab Hang hoa");
    ok(strpos($s, 'dichvu') !== false, "quotations/$v: co tab Dich vu");

    ok(strpos($s, "tienTo + 'part[]'") !== false,
       "quotations/$v: ten o dong hang dung theo tien to cua tab");
    ok(strpos($s, "'line_'") !== false && strpos($s, "'sv_'") !== false,
       "quotations/$v: hai tien to `line_` va `sv_` deu co");

    ok(strpos($s, 'LOAI_DICH_VU') !== false,
       "quotations/$v: chia mat hang ve tab theo item_type");

    // Tong tien phai co du 3 dong
    foreach (['tong-hang', 'tong-dichvu', 'grand-total'] as $id){
        ok(strpos($s, 'id="' . $id . '"') !== false, "quotations/$v: co o tong `$id`");
    }
    ok(strpos($s, 'tHang + tDichVu') !== false,
       "quotations/$v: cong chua thue = tien hang hoa + tien dich vu");
}

// ---------------------------------------------------------------------------
section('Controller bao gia gop dong cua ca hai tab');

$q = codeOnly($goc . 'app/controllers/admin/Quotations.php');
ok(strpos($q, "docDong('line_')") !== false && strpos($q, "docDong('sv_')") !== false,
   'buildLines() doc CA HAI bo ten o');
ok(strpos($q, 'array_merge($this->docDong') !== false,
   'Hai bo duoc gop lai thanh mot danh sach dong');

// O chon hang cua bao gia phai kem item_type de chia tab
$qi = codeOnly($goc . 'app/models/QuotationItemsModel.php');
ok(strpos($qi, 'item_type') !== false,
   'getByQuotation() lay kem item_type (de sua bao gia cu xep dung tab)');

$pmCode = codeOnly($goc . 'app/models/PartsModel.php');
ok(strpos($pmCode, '`parts`.`item_type`') !== false,
   'getForSelect() tra ve item_type');

// ---------------------------------------------------------------------------
section('Danh muc dich vu');

$cat = codeOnly($goc . 'app/models/PartCategoriesModel.php');
ok(strpos($cat, 'function nhanhTheoSlug') !== false, 'PartCategoriesModel::nhanhTheoSlug()');

require_once $goc . 'app/models/PartCategoriesModel.php';
$cm     = new PartCategoriesModel();
$nhanh  = $cm->nhanhTheoSlug('dich-vu');
ok(!empty($nhanh), 'Lay duoc nhanh danh muc "dich-vu"');
if (!empty($nhanh)){
    ok((int) $nhanh[0]['depth'] === 0, 'Goc nhanh co depth = 0 (tinh lai tu goc nhanh)');
    ok($nhanh[0]['slug'] === 'dich-vu', 'Phan tu dau la chinh goc nhanh');

    $tenNhanh = array_column($nhanh, 'slug');
    ok(!in_array('he-thong-phanh', $tenNhanh, true),
       'Nhanh dich vu KHONG chua danh muc cua phu tung');
}

// ---------------------------------------------------------------------------
section('Loi cu: ham don truong theo loai bi bo qua vi return som');

$prod = codeOnly($goc . 'app/controllers/admin/Products.php');
ok(preg_match('/\$data\s*=\s*\[/', $prod) === 1,
   'buildData() gan vao $data truoc khi tra ve');
ok(preg_match('/return\s+\$this->boTruongKhongThuocLoai\(\$data\);/', $prod) === 1,
   'buildData() THUC SU goi boTruongKhongThuocLoai()',
   'Truoc day co "return [...]" dung ngay tren nen ham don truong khong bao gio chay');

exit(summary());

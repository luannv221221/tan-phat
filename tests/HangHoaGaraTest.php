<?php
/**
 * HÀNG HOÁ ĐẦY ĐỦ CHO GARA — migration 000094, 08/10/2026.
 *
 * Chạy:  C:\xampp\php\php.exe tests\HangHoaGaraTest.php
 *
 * VIỆC: gara khai hàng hoá của mình bằng chính tám màn nhóm Hàng hoá, như gara
 * tổng khai hàng của kho tổng. Trước đó tám màn đó mang cờ `chi_tan_phat`; gara
 * chỉ có biểu mẫu gọn ở "Danh mục của gara" — đúng bốn ô tên / loại / đơn vị /
 * giá, không khai được mã OEM, danh mục, thương hiệu, ảnh, thông số, bảo hành.
 *
 * NĂM CHỖ HỎNG SẼ ÂM THẦM:
 *
 *   1. HÀNG CỦA GARA RƠI VÀO KHO TỔNG. `parts`.`garage_id` để NULL nghĩa là
 *      hàng của kho tổng — mà biểu mẫu Hàng hoá không có ô gara. Thiếu một
 *      dòng gán gara ở tầng Model là gara thêm hàng xong CẢ HỆ THỐNG nhìn
 *      thấy, và gara khác bán được. Không ai thấy ngay: màn của chính gara đó
 *      vẫn hiện mặt hàng vừa thêm.
 *
 *   2. GARA SỬA ĐƯỢC HÀNG KHO TỔNG. Chốt 07/10/2026 là KHÔNG. Mở màn Hàng hoá
 *      ra mà quên chốt này là gara đổi giá / đổi tên một mặt hàng mà mọi gara
 *      đang bán.
 *
 *   3. DANH MỤC CỦA GARA NÀY LỌT SANG GARA KHÁC. Sáu bảng danh mục được thêm
 *      `garage_id` kiểu chung-và-riêng. Truy vấn TỰ VIẾT trong model (cây danh
 *      mục, FIND_IN_SET thông số) không được cờ ở lớp cha chặn, phải tự ghép
 *      điều kiện — quên một chỗ là rò.
 *
 *   4. TRÙNG SLUG CHẶN GARA KHAI HÀNG. `slug` duy nhất TOÀN BẢNG (nó là địa
 *      chỉ trên website). Gara gõ "Bosch" mà kho tổng đã có là báo lỗi "đường
 *      dẫn đã tồn tại" — người dùng không hiểu vì sao, vì màn của họ chẳng có
 *      dòng "Bosch" nào mang nút sửa.
 *
 *   5. MỞ MÀN MÀ QUÊN CẤP QUYỀN. Gỡ cờ `chi_tan_phat` và cấp `permissions` là
 *      hai việc khác nhau, thiếu vế nào cũng ra cùng một hiện tượng "không thấy
 *      màn". Đã mắc đúng lỗi này ở migration 000086/000088 (vá ở 000092).
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use App\core\Model;

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

/** Tám màn được mở, và sáu bảng danh mục được chia theo gara */
$MAN  = ['products', 'services', 'part-categories', 'attributes',
         'product-brands', 'product-origins', 'product-manufacturers', 'product-units'];
$BANG = ['part_categories', 'part_brands', 'part_origins', 'part_units',
         'part_manufacturers', 'part_attributes'];

// ---------------------------------------------------------------------------
section('Migration dung hinh');

$mg = glob($goc . 'database/migrations/*_hang_hoa_day_du_cho_gara.php');
ok(count($mg) === 1, 'Co migration hang hoa day du cho gara');
$src = count($mg) === 1 ? file_get_contents($mg[0]) : '';

foreach ($BANG as $b){
    ok(strpos($src, "'$b'") !== false, "Migration them garage_id cho `$b`");
}
foreach ($MAN as $l){
    ok(strpos($src, "'$l'") !== false, "Migration mo man `$l`");
}
ok(strpos($src, "UPDATE `modules` SET `chi_tan_phat` = 0") !== false, 'Migration go co chi_tan_phat');
ok(strpos($src, "`groups` WHERE `name` = ?") !== false,
   'Migration cap quyen cho MOI nhom cung ten (moi gara mot nhom rieng)',
   'Cap cho mot nhom Manager la cac gara khac mo man ra van khong thay');

// ---------------------------------------------------------------------------
section('Chay that tren MySQL');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$so  = function($sql, $bd = []) use ($pdo){ $st = $pdo->prepare($sql); $st->execute($bd); return (int) $st->fetchColumn(); };
$cot = function($b) use ($pdo){ return $pdo->query("SHOW COLUMNS FROM `$b`")->fetchAll(PDO::FETCH_COLUMN); };

foreach ($BANG as $b){
    ok(in_array('garage_id', $cot($b), true), "`$b` co cot garage_id");
}
if (!in_array('garage_id', $cot('part_brands'), true)){
    echo "\n[SKIP] Chua chay migration 000094.\n"; exit(summary());
}

/* `slug` PHAI con duy nhat TOAN BANG. Doi thanh (garage_id, slug) la mat rang
   buoc dang co: MySQL coi cac NULL la KHAC NHAU, nen hai dong danh muc tong
   trung slug se lot qua. */
foreach ($BANG as $b){
    $n = $so("SELECT COUNT(*) FROM information_schema.STATISTICS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'slug'
                 AND NON_UNIQUE = 0", [$b]);
    ok($n >= 1, "`$b`.`slug` van duy nhat toan bang",
       'Doi sang UNIQUE(garage_id, slug) la hai dong danh muc tong trung slug lot qua (NULL != NULL)');
}

/* Khoa ngoai RESTRICT: xoa gara ma xoa luon danh muc rieng cua no thi hang hoa
   dang tro vao danh muc do mat danh muc. SET NULL con te hon — danh muc rieng
   nhay vao danh muc tong va moi gara deu thay. */
$luat = [];
foreach ($pdo->query("SELECT TABLE_NAME, DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS
                       WHERE CONSTRAINT_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = 'garages'") as $r){
    $luat[$r['TABLE_NAME']] = $r['DELETE_RULE'];
}
foreach ($BANG as $b){
    ok(isset($luat[$b]) && $luat[$b] === 'RESTRICT', "Khoa ngoai `$b` -> garages la RESTRICT",
       isset($luat[$b]) ? 'Dang la ' . $luat[$b] : 'Khong co khoa ngoai');
}

// --- 5. Mở màn thì phải cấp quyền ---
section('5. Man da mo thi MOI nhom Manager phai co quyen');

$dau = "'" . implode("','", $MAN) . "'";
$conCo = $pdo->query("SELECT link FROM modules WHERE chi_tan_phat = 1 AND link IN ($dau)")->fetchAll(PDO::FETCH_COLUMN);
ok(empty($conCo), 'Tam man nhom Hang hoa da mo cho gara', 'Con co: ' . implode(', ', $conCo));

$thieu = [];
foreach ($pdo->query("SELECT g.id, g.name, COALESCE(ga.code, 'he-thong') ma
                        FROM `groups` g LEFT JOIN garages ga ON ga.id = g.garage_id
                       WHERE g.name IN ('Manager', 'Staff')")->fetchAll(PDO::FETCH_ASSOC) as $n){
    $co = $pdo->query("SELECT m.link FROM modules m WHERE m.link IN ($dau)
                        AND NOT EXISTS (SELECT 1 FROM permissions p WHERE p.module_id = m.id
                                         AND p.group_id = " . (int) $n['id'] . " AND p.role = 'view')")
              ->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($co)) $thieu[] = $n['name'] . '/' . $n['ma'] . ': ' . implode(',', $co);
}
ok(empty($thieu), 'Moi nhom Manager va Staff cua moi gara deu XEM duoc tam man do',
   implode(' | ', $thieu));

/* Manager sua duoc, Staff chi xem. Doi logo, khai hang la viec chu gara. */
$quyenCua = function($ten, $link) use ($pdo){
    return $pdo->query("SELECT DISTINCT p.role FROM permissions p
                          JOIN `groups` g ON g.id = p.group_id JOIN modules m ON m.id = p.module_id
                         WHERE g.name = " . $pdo->quote($ten) . " AND m.link = " . $pdo->quote($link))
               ->fetchAll(PDO::FETCH_COLUMN);
};
foreach ($MAN as $l){
    $qm = $quyenCua('Manager', $l);
    ok(!array_diff(['view', 'add', 'edit', 'delete'], $qm), "Manager toan quyen tren `$l`", implode(',', $qm));
    $qs = $quyenCua('Staff', $l);
    ok(in_array('view', $qs, true) && !array_intersect(['add', 'edit', 'delete'], $qs),
       "Staff CHI XEM `$l`", implode(',', $qs));
}

// --- 1 + 2 + 3. Thử thật bằng Model ---
section('1+2+3. Thu that: hang va danh muc cua gara');

require_once $goc . 'app/models/PartsModel.php';
require_once $goc . 'app/models/LookupModel.php';
require_once $goc . 'app/models/ProductBrandsModel.php';
require_once $goc . 'app/models/PartCategoriesModel.php';

$tongId = $so("SELECT id FROM garages WHERE is_master = 1 ORDER BY id LIMIT 1");
$dsKhac = $pdo->query("SELECT id, code FROM garages WHERE is_master = 0 AND status = 1 ORDER BY id LIMIT 2")
              ->fetchAll(PDO::FETCH_ASSOC);
if ($tongId === 0 || count($dsKhac) < 2){
    echo "  [SKIP] Can gara tong + hai gara khac (chay tools/tao-du-lieu-gara.php).\n";
    echo "\n"; exit(summary());
}
$gA = (int) $dsKhac[0]['id']; $gB = (int) $dsKhac[1]['id'];

$donSach = function() use ($pdo){
    /* `parts` TRUOC: `parts`.`brand_id` / `category_id` la SET NULL nen khong
       chan, nhung xoa hang truoc cho sach y. */
    $pdo->exec("DELETE FROM parts WHERE code LIKE 'ZZHH-%'");
    $pdo->exec("DELETE FROM part_brands WHERE name LIKE 'ZZHH%'");
    /* `part_categories`.`parent_id` tu tham chieu va KHONG co ON DELETE —
       mac dinh la RESTRICT. Xoa ca mot luot la do o dong cha. Xoa tu la len
       goc: moi vong bo duoc mot cap, ba vong du cho cay thu nay. */
    for ($i = 0; $i < 5; $i++){
        $n = $pdo->exec("DELETE FROM part_categories WHERE name LIKE 'ZZHH%'
                          AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM part_categories) c
                                           WHERE c.parent_id = part_categories.id)");
        if (!$n) break;
    }
};
$donSach();
register_shutdown_function($donSach);

$P  = new PartsModel();
$B  = new ProductBrandsModel();
$C  = new PartCategoriesModel();

// --- 1. Hàng gara thêm thuộc về gara đó, KHÔNG rơi vào kho tổng ---
Model::epGara($gA);
$idHangA = $P->add(['code' => 'ZZHH-A1', 'name' => 'ZZHH Hang cua A', 'item_type' => 'part',
                    'slug' => 'zzhh-hang-cua-a', 'price' => 100000, 'status' => 1, 'show_on_web' => 0]);
Model::epGara(null);

$ganA = $pdo->query("SELECT garage_id FROM parts WHERE code = 'ZZHH-A1'")->fetchColumn();
ok((int) $ganA === $gA, 'Hang gara A them -> garage_id = gara A (khong roi vao kho tong)',
   'Dang la: ' . var_export($ganA, true) . ' — NULL nghia la hang kho tong, ca he thong ban duoc');

// Hàng kho tổng để thử chiều ngược lại
Model::epGara(null);
$idHangTong = $P->add(['code' => 'ZZHH-T1', 'name' => 'ZZHH Hang kho tong', 'item_type' => 'part',
                       'slug' => 'zzhh-hang-kho-tong', 'price' => 200000, 'status' => 1, 'show_on_web' => 0]);
$ganT = $pdo->query("SELECT garage_id FROM parts WHERE code = 'ZZHH-T1'")->fetchColumn();
ok($ganT === null, 'Dong lenh chua ep gara -> hang vao kho tong (giu nep cu)');

// --- 2. Gara KHÔNG sửa / xoá được hàng kho tổng ---
Model::epGara($gA);
ok(!empty($P->getDetail($idHangTong)),
   'Gara A DOC duoc hang kho tong (de lap bao gia, in phieu, kiem ton)');
ok(empty($P->cuaToi($idHangTong)),
   'Gara A KHONG so huu hang kho tong (cuaToi() tra ve rong)');
ok(!empty($P->cuaToi($idHangA)), 'Gara A so huu hang cua chinh minh');

$P->edit(['name' => 'ZZHH BI SUA TRAI PHEP'], $idHangTong);
$tenT = $pdo->query("SELECT name FROM parts WHERE code = 'ZZHH-T1'")->fetchColumn();
ok($tenT === 'ZZHH Hang kho tong', 'Gara A SUA hang kho tong -> khong an gi',
   'Dang la: ' . $tenT);

$P->remove($idHangTong);
ok($so("SELECT COUNT(*) FROM parts WHERE code = 'ZZHH-T1'") === 1,
   'Gara A XOA hang kho tong -> khong an gi');

$P->edit(['name' => 'ZZHH Hang cua A (da sua)'], $idHangA);
ok($pdo->query("SELECT name FROM parts WHERE code = 'ZZHH-A1'")->fetchColumn() === 'ZZHH Hang cua A (da sua)',
   'Gara A sua duoc hang cua chinh minh');

// --- Màn Quản lý hàng hoá chỉ liệt kê hàng của gara đang làm việc ---
$tenTrongDs = function($ds){ return array_column((array) $ds, 'code'); };
$dsA = $tenTrongDs($P->getLists([], 'ZZHH'));
ok(in_array('ZZHH-A1', $dsA, true),  'Man Hang hoa cua gara A co hang cua A');
ok(!in_array('ZZHH-T1', $dsA, true), 'Man Hang hoa cua gara A KHONG co hang kho tong',
   'Bay ra danh sach phan lon la dong chi doc thi nguoi dung bam Sua roi moi biet la khong duoc');

Model::epGara($gB);
$dsB = $tenTrongDs($P->getLists([], 'ZZHH'));
ok(!in_array('ZZHH-A1', $dsB, true), 'Man Hang hoa cua gara B KHONG co hang cua gara A');

Model::epGara($tongId);
$dsT = $tenTrongDs($P->getLists([], 'ZZHH'));
ok(in_array('ZZHH-T1', $dsT, true),  'Man Hang hoa cua gara tong co hang kho tong');
ok(!in_array('ZZHH-A1', $dsT, true), 'Man Hang hoa cua gara tong KHONG co hang rieng cua gara A',
   'Tan Phat khong xem du lieu ben trong gara (gara doc lap, 22/09/2026)');

// --- 3. Danh mục: thấy phần chung, không thấy phần riêng của gara khác ---
section('3. Danh muc: thay phan chung, khong thay phan rieng cua gara khac');

Model::epGara(null);
$idThTong = $B->add(['name' => 'ZZHH Thuong hieu tong', 'slug' => 'zzhh-th-tong', 'sort_order' => 99, 'status' => 1]);
Model::epGara($gA);
$idThA = $B->add(['name' => 'ZZHH Thuong hieu A', 'slug' => 'zzhh-th-a', 'sort_order' => 99, 'status' => 1]);
Model::epGara($gB);
$idThB = $B->add(['name' => 'ZZHH Thuong hieu B', 'slug' => 'zzhh-th-b', 'sort_order' => 99, 'status' => 1]);

ok((int) $pdo->query("SELECT garage_id FROM part_brands WHERE slug = 'zzhh-th-a'")->fetchColumn() === $gA,
   'Thuong hieu gara A them -> garage_id = gara A');
ok($pdo->query("SELECT garage_id FROM part_brands WHERE slug = 'zzhh-th-tong'")->fetchColumn() === null,
   'Thuong hieu them o dong lenh chua ep gara -> danh muc tong');

$slugTrongDs = function($ds){ return array_column((array) $ds, 'slug'); };

Model::epGara($gA);
$dsA = $slugTrongDs($B->getLists());
ok(in_array('zzhh-th-tong', $dsA, true), 'Gara A THAY thuong hieu cua danh muc tong (de chon khi khai hang)');
ok(in_array('zzhh-th-a', $dsA, true),    'Gara A thay thuong hieu cua chinh minh');
ok(!in_array('zzhh-th-b', $dsA, true),   'Gara A KHONG thay thuong hieu rieng cua gara B');

ok(!empty($B->getDetail($idThTong)), 'Gara A doc duoc thuong hieu cua danh muc tong');
ok(empty($B->getDetail($idThB)),     'Gara A KHONG doc duoc thuong hieu rieng cua gara B (theo id)');

$B->edit(['name' => 'ZZHH BI SUA TRAI PHEP'], $idThTong);
ok($pdo->query("SELECT name FROM part_brands WHERE slug = 'zzhh-th-tong'")->fetchColumn() === 'ZZHH Thuong hieu tong',
   'Gara A SUA thuong hieu cua danh muc tong -> khong an gi');
$B->remove($idThB);
ok($so("SELECT COUNT(*) FROM part_brands WHERE slug = 'zzhh-th-b'") === 1,
   'Gara A XOA thuong hieu cua gara B -> khong an gi');

Model::epGara($tongId);
$dsT = $slugTrongDs($B->getLists());
ok(in_array('zzhh-th-tong', $dsT, true), 'Gara tong thay thuong hieu cua danh muc tong');
ok(!in_array('zzhh-th-a', $dsT, true) && !in_array('zzhh-th-b', $dsT, true),
   'Gara tong KHONG thay thuong hieu rieng cua cac gara');

/* CAY DANH MUC: getTree() la truy van TU VIET nen co o lop cha khong voi toi. */
Model::epGara(null);
$idDmTong = $C->add(['name' => 'ZZHH Danh muc tong', 'slug' => 'zzhh-dm-tong', 'parent_id' => null,
                     'sort_order' => 99, 'status' => 1]);
Model::epGara($gA);
$idDmA = $C->add(['name' => 'ZZHH Danh muc A', 'slug' => 'zzhh-dm-a', 'parent_id' => $idDmTong,
                  'sort_order' => 99, 'status' => 1]);
Model::epGara($gB);
$idDmB = $C->add(['name' => 'ZZHH Danh muc B', 'slug' => 'zzhh-dm-b', 'parent_id' => $idDmTong,
                  'sort_order' => 99, 'status' => 1]);

Model::epGara($gA);
$cayA = $slugTrongDs($C->getTree());
ok(in_array('zzhh-dm-tong', $cayA, true), 'Cay danh muc cua gara A co nhanh cua danh muc tong');
ok(in_array('zzhh-dm-a', $cayA, true),    'Cay danh muc cua gara A co nhanh cua chinh minh');
ok(!in_array('zzhh-dm-b', $cayA, true),   'Cay danh muc cua gara A KHONG co nhanh rieng cua gara B',
   'getTree() la truy van tu viet — co $_chungVaRieng o lop cha khong voi toi');

/* Danh muc con cua gara treo duoi cha cua danh muc tong: phai VAN hien ra, va
   hien dung mot cap duoi cha. Danh mat node vi khong thay cha la loi khong ai
   doan duoc — no khong bao loi, chi mat khoi cay. */
$cayDayDu = (array) $C->getTree();
$viTri = null;
foreach ($cayDayDu as $i => $n){ if ($n['slug'] === 'zzhh-dm-a') $viTri = $i; }
ok($viTri !== null, 'Danh muc rieng cua gara treo duoi danh muc tong VAN hien trong cay');

Model::epGara(null);

// --- 4. Trùng slug không chặn gara khai hàng ---
section('4. Trung slug khong chan gara khai hang');

foreach (['Productbrands', 'Productorigins', 'Productunits', 'Productmanufacturers',
          'Attributes', 'Partcategories'] as $c){
    $s = codeOnly($goc . 'app/controllers/admin/' . $c . '.php');
    ok(strpos($s, 'slugRanh') !== false,
       "$c tu ne trung slug khi nguoi dung de trong o slug",
       'Trung ten giua cac gara la chuyen thuong ("Bosch"); bao loi la chan gara khai hang');
}

Model::epGara($gA);
$slugMoi = $B->slugRanh('zzhh-th-tong');
ok($slugMoi !== 'zzhh-th-tong' && strpos($slugMoi, 'zzhh-th-tong-') === 0,
   'slugRanh() tra ve slug co duoi khi slug goc da co nguoi giu', $slugMoi);
/* TRA KHAP BANG, khong loc gara: slug la duy nhat toan bang, nen slug cua gara
   B cung phai tinh la da co. Loc theo gara o day la bao "ranh" roi INSERT dam
   vao UNIQUE KEY — trang do ra loi CSDL. */
$slugB = $B->slugRanh('zzhh-th-b');
ok($slugB !== 'zzhh-th-b',
   'slugRanh() tinh ca slug cua GARA KHAC (slug duy nhat toan bang)',
   'Loc theo gara o day la INSERT dam vao UNIQUE KEY, trang do ra loi CSDL');
Model::epGara(null);

// --- Chốt ở tầng Model, không chỉ ở controller ---
section('Chot nam o tang Model');

$pmSrc = codeOnly($goc . 'app/models/PartsModel.php');
ok(preg_match('~\$_chungVaRieng\s*=\s*true~', $pmSrc) === 1,
   'PartsModel bat $_chungVaRieng (addNew / updateById / deleteById tu loc)');
foreach (['ProductBrandsModel', 'ProductOriginsModel', 'ProductUnitsModel',
          'ProductManufacturersModel', 'AttributesModel', 'PartCategoriesModel'] as $m){
    $s = codeOnly($goc . 'app/models/' . $m . '.php');
    ok(preg_match('~\$_chungVaRieng\s*=\s*true~', $s) === 1, "$m bat \$_chungVaRieng");
}

$lm = codeOnly($goc . 'app/models/LookupModel.php');
ok(strpos($lm, 'locChungVaRieng') !== false,
   'LookupModel::getLists() tu ghep dieu kien gara (truy van tu viet)');
$am = codeOnly($goc . 'app/models/AttributesModel.php');
ok(strpos($am, 'dkChungVaRieng') !== false,
   'AttributesModel::getForItemType() tu ghep dieu kien gara (FIND_IN_SET, getRaw)');

/* Nut Sua / Xoa phai AN o dong cua kho tong. An nut khong phai chot, nhung
   khong an thi nguoi dung bam vao roi moi nhan cau tu choi. */
foreach (['product-brands', 'product-origins', 'product-units', 'product-manufacturers',
          'attributes', 'part-categories'] as $d){
    $v = file_get_contents($goc . 'app/views/admin/' . $d . '/lists.php');
    ok(strpos($v, 'dong_cua_gara') !== false && strpos($v, 'nhan_kho_tong') !== false,
       "View `$d` an nut sua/xoa va danh nhan o dong cua kho tong");
}

$pSrc = codeOnly($goc . 'app/controllers/admin/Products.php');
ok(strpos($pSrc, 'layHangCuaToi') !== false && strpos($pSrc, 'cuaToi') !== false,
   'Products controller dung cuaToi() cho sua / xoa / anh');
/* BON DUONG GHI deu phai qua layHangCuaToi(). getDetail() tra ve ca hang kho
   tong, nen dung no o day la mo form sua ra roi luu khong an gi — nguoi dung
   nhan "cap nhat thanh cong" ma khong co gi doi. (layHangCuaToi() co goi
   getDetail() mot lan, nhung chi de noi dung cau tu choi.) */
foreach (['edit', 'postEdit', 'delete', 'postImages'] as $ham){
    ok(preg_match('~function\s+' . $ham . '\s*\(\$id[^)]*\)\s*\{\s*[^}]{0,200}?layHangCuaToi~s', $pSrc) === 1,
       "Products::$ham() vao bang layHangCuaToi()",
       'Dung getDetail() o day la gara mo duoc form sua hang kho tong');
}

$sSrc = codeOnly($goc . 'app/controllers/admin/Services.php');
ok(strpos($sSrc, 'cuaToi') !== false, 'Services controller dung cuaToi() cho sua / xoa');

echo "\n";
exit(summary());

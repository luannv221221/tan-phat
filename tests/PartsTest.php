<?php
/**
 * Test PHỤ TÙNG + liên kết xe trên MySQL THẬT — TASK_86, 87, 90, 91, 93.
 *
 * Chạy:  C:\xampp\php\php.exe tests\PartsTest.php
 *
 * Dựng một kịch bản gần thật:
 *   Toyota Vios đời 2014-2017 và 2018-nay, Honda City 2018-nay
 *   Lọc gió Vios cũ / Lọc gió Vios mới / Má phanh dùng chung / Lọc gió City
 * rồi kiểm tra "chọn xe -> ra đúng phụ tùng".
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

foreach (['LookupModel','CarBrandsModel','CarBodyTypesModel','CarModelsModel','CarYearsModel',
          'ProductBrandsModel','ProductOriginsModel','ProductUnitsModel',
          'PartsModel','PartFitmentsModel'] as $m){
    require_once __DIR__ . '/../app/models/' . $m . '.php';
}

echo 'PHP ' . PHP_VERSION . ' | MySQL ' . $pdo->query('SELECT VERSION()')->fetchColumn() . "\n";

// ---- Don du lieu test cu ----
$pdo->exec("DELETE FROM parts WHERE code LIKE 'PT-TEST-%'");
$pdo->exec("DELETE FROM car_models WHERE slug LIKE 'pt-test-%'");
$pdo->exec("DELETE FROM car_brands WHERE slug LIKE 'pt-test-%'");

// ---- Dung kich ban ----
$bm = new CarBrandsModel();
$toyotaId = $bm->add(['name' => 'PT Toyota', 'slug' => 'pt-test-toyota', 'status' => 1]);
$hondaId  = (new CarBrandsModel())->add(['name' => 'PT Honda', 'slug' => 'pt-test-honda', 'status' => 1]);

$mm = new CarModelsModel();
$viosId = $mm->add(['brand_id' => $toyotaId, 'name' => 'PT Vios', 'slug' => 'pt-test-vios', 'status' => 1]);
$cityId = (new CarModelsModel())->add(['brand_id' => $hondaId, 'name' => 'PT City', 'slug' => 'pt-test-city', 'status' => 1]);

$ym = new CarYearsModel();
$viosCu  = $ym->add(['model_id' => $viosId, 'year_from' => 2014, 'year_to' => 2017, 'name' => 'Vios 2014-2017', 'status' => 1]);
$viosMoi = (new CarYearsModel())->add(['model_id' => $viosId, 'year_from' => 2018, 'year_to' => null, 'name' => 'Vios 2018-nay', 'status' => 1]);
$cityMoi = (new CarYearsModel())->add(['model_id' => $cityId, 'year_from' => 2018, 'year_to' => null, 'name' => 'City 2018-nay', 'status' => 1]);

$locGio = (new CarBodyTypesModel())->getLists(); // cham vao de chac model chung chay
$catLocGio = $pdo->query("SELECT id FROM part_categories WHERE slug='loc-gio'")->fetchColumn();
$catMaPhanh = $pdo->query("SELECT id FROM part_categories WHERE slug='ma-phanh'")->fetchColumn();
$bosch = (new ProductBrandsModel())->findBySlug('bosch');
$denso = (new ProductBrandsModel())->findBySlug('denso');
$cai   = (new ProductUnitsModel())->findBySlug('cai');

// ================================================================
section('Du lieu moi tu migration 000005');

ok(count((new ProductUnitsModel())->getLists()) >= 6, 'Co it nhat 6 don vi tinh');
ok(count((new ProductOriginsModel())->getLists()) >= 6, 'Co it nhat 6 xuat xu');
ok(count((new ProductBrandsModel())->getLists()) >= 6, 'Co it nhat 6 thuong hieu phu tung');
ok(!empty($catLocGio) && !empty($catMaPhanh), 'Cay danh muc phu tung co "Loc gio" va "Ma phanh"');

$nhatBan = (new ProductOriginsModel())->findBySlug('nhat-ban');
ok(!empty($nhatBan) && $nhatBan['name'] === 'Nhật Bản', 'Tieng Viet co dau dung', $nhatBan['name'] ?? '');

// Danh muc phai co phan cap
$root = $pdo->query("SELECT COUNT(*) FROM part_categories WHERE parent_id IS NULL")->fetchColumn();
$child = $pdo->query("SELECT COUNT(*) FROM part_categories WHERE parent_id IS NOT NULL")->fetchColumn();
ok($root >= 4 && $child >= 12, "Danh muc co phan cap: $root goc, $child con");

// ================================================================
section('TASK_86 — tao phu tung');

$pm = new PartsModel();
$p1 = $pm->add([
    'code' => 'PT-TEST-001', 'oem_code' => '17801-0Y040',
    'name' => 'Lọc gió động cơ Vios đời cũ', 'slug' => 'pt-test-loc-gio-vios-cu',
    'category_id' => $catLocGio, 'brand_id' => $bosch['id'], 'unit_id' => $cai['id'],
    'price' => 150000, 'status' => 1,
]);
ok($p1 > 0, 'add() tao duoc phu tung', "id=$p1");

$p2 = (new PartsModel())->add([
    'code' => 'PT-TEST-002', 'oem_code' => '17801-0Y050',
    'name' => 'Lọc gió động cơ Vios đời mới', 'slug' => 'pt-test-loc-gio-vios-moi',
    'category_id' => $catLocGio, 'brand_id' => $denso['id'], 'unit_id' => $cai['id'],
    'price' => 180000, 'status' => 1,
]);
$p3 = (new PartsModel())->add([
    'code' => 'PT-TEST-003', 'name' => 'Má phanh trước (dùng chung)', 'slug' => 'pt-test-ma-phanh',
    'category_id' => $catMaPhanh, 'brand_id' => $bosch['id'], 'unit_id' => $cai['id'],
    'price' => 450000, 'status' => 1,
]);
$p4 = (new PartsModel())->add([
    'code' => 'PT-TEST-004', 'name' => 'Lọc gió Honda City', 'slug' => 'pt-test-loc-gio-city',
    'category_id' => $catLocGio, 'brand_id' => $denso['id'], 'unit_id' => $cai['id'],
    'price' => 160000, 'status' => 1,
]);
ok($p2 > 0 && $p3 > 0 && $p4 > 0, 'Tao du 4 phu tung');

// Ma phu tung phai duy nhat
$dup = false;
try { (new PartsModel())->add(['code' => 'PT-TEST-001', 'name' => 'Trung ma', 'slug' => 'pt-test-trung', 'status' => 1]); }
catch (\Throwable $e){ $dup = true; }
ok($dup, 'Ma phu tung trung -> bi tu choi (UNIQUE)');

// Gia tien phai la DECIMAL, khong duoc lam tron sai
$pd = (new PartsModel())->getDetail($p1);
ok($pd['price'] == 150000, 'Gia luu dung (DECIMAL)', $pd['price']);

// ================================================================
section('TASK_86 — gan phu tung cho doi xe');

$fm = new PartFitmentsModel();
ok($fm->attach($p1, $viosCu) > 0, 'attach() gan loc gio cu cho Vios 2014-2017');
ok((new PartFitmentsModel())->attach($p2, $viosMoi) > 0, 'attach() gan loc gio moi cho Vios 2018-nay');

// Ma phanh dung chung ca 2 doi Vios + City
(new PartFitmentsModel())->attach($p3, $viosCu);
(new PartFitmentsModel())->attach($p3, $viosMoi);
(new PartFitmentsModel())->attach($p3, $cityMoi);
(new PartFitmentsModel())->attach($p4, $cityMoi);

// Gan trung -> tra ve false, khong nem exception
$again = (new PartFitmentsModel())->attach($p1, $viosCu);
ok($again === false, 'attach() trung -> tra ve false (khong nem exception)');

$cnt = $pdo->query("SELECT COUNT(*) FROM part_fitments WHERE part_id = $p1")->fetchColumn();
ok($cnt == 1, 'Khong tao ban ghi trung', "so dong: $cnt");

// ================================================================
section('⭐ TASK_87 — CHON XE SE LOC RA CAC PHU TUNG');

$viosCuParts = (new PartsModel())->getByCarYear($viosCu);
$names = array_column($viosCuParts, 'name');
ok(count($viosCuParts) === 2, 'Vios 2014-2017 -> 2 phu tung', implode(' | ', $names));
ok(in_array('Lọc gió động cơ Vios đời cũ', $names, true), '  co loc gio doi CU');
ok(!in_array('Lọc gió động cơ Vios đời mới', $names, true), '  KHONG co loc gio doi MOI (dung doi)');
ok(in_array('Má phanh trước (dùng chung)', $names, true), '  co ma phanh dung chung');

$viosMoiParts = (new PartsModel())->getByCarYear($viosMoi);
$names2 = array_column($viosMoiParts, 'name');
ok(count($viosMoiParts) === 2, 'Vios 2018-nay -> 2 phu tung', implode(' | ', $names2));
ok(in_array('Lọc gió động cơ Vios đời mới', $names2, true), '  co loc gio doi MOI');
ok(!in_array('Lọc gió động cơ Vios đời cũ', $names2, true), '  KHONG co loc gio doi CU');

$cityParts = (new PartsModel())->getByCarYear($cityMoi);
ok(count($cityParts) === 2, 'Honda City 2018-nay -> 2 phu tung');
ok(!in_array('Lọc gió động cơ Vios đời mới', array_column($cityParts, 'name'), true),
   '  KHONG lot phu tung cua Toyota sang Honda');

// getLists co join lay duoc ten
ok(!empty($viosCuParts[0]['category_name']), 'getByCarYear() lay duoc ten danh muc',
   $viosCuParts[0]['category_name'] ?? '');
ok(!empty($viosCuParts[0]['brand_name']), 'getByCarYear() lay duoc ten thuong hieu',
   $viosCuParts[0]['brand_name'] ?? '');

// Loc them theo danh muc (TASK_90)
$onlyLocGio = (new PartsModel())->getByCarYear($viosCu, ['parts.category_id' => $catLocGio]);
ok(count($onlyLocGio) === 1, 'Loc them theo danh muc "Loc gio" -> 1 phu tung');

// ================================================================
section('⭐ TASK_93 — tim phu tung theo MODEL + NAM');

$r2015 = (new PartsModel())->getByModelAndYear($viosId, 2015);
ok(count($r2015) === 2, 'Vios nam 2015 -> ra phu tung cua doi 2014-2017', count($r2015) . ' phu tung');
ok(in_array('Lọc gió động cơ Vios đời cũ', array_column($r2015, 'name'), true), '  dung loc gio doi cu');

$r2020 = (new PartsModel())->getByModelAndYear($viosId, 2020);
ok(in_array('Lọc gió động cơ Vios đời mới', array_column($r2020, 'name'), true),
   'Vios nam 2020 -> ra loc gio doi moi');

$r2010 = (new PartsModel())->getByModelAndYear($viosId, 2010);
ok($r2010 === [], 'Vios nam 2010 -> khong co doi nao -> mang rong (khong loi)');

// Bien
$r2017 = (new PartsModel())->getByModelAndYear($viosId, 2017);
ok(in_array('Lọc gió động cơ Vios đời cũ', array_column($r2017, 'name'), true), 'Nam bien 2017 -> doi cu');
$r2018 = (new PartsModel())->getByModelAndYear($viosId, 2018);
ok(in_array('Lọc gió động cơ Vios đời mới', array_column($r2018, 'name'), true), 'Nam bien 2018 -> doi moi');

// Tat ca doi cua 1 model
$allVios = (new PartsModel())->getByModel($viosId);
$allNames = array_column($allVios, 'name');
ok(count($allVios) === 3, 'getByModel(Vios) -> 3 phu tung (ca 2 doi, khong trung ma phanh)',
   implode(' | ', $allNames));
ok(count(array_unique($allNames)) === count($allNames),
   'Ma phanh lap ca 2 doi nhung KHONG bi tra ve 2 lan (groupBy)');

// ================================================================
section('TASK_91 — tim kiem');

$byName = (new PartsModel())->getLists([], 'Lọc gió');
ok(count($byName) >= 3, 'Tim theo ten tieng Viet co dau', count($byName) . ' ket qua');

$byCode = (new PartsModel())->getLists([], 'PT-TEST-002');
ok(count($byCode) === 1, 'Tim theo ma phu tung');

$byOem = (new PartsModel())->getLists([], '17801-0Y040');
ok(count($byOem) === 1, 'Tim theo ma OEM');

// Loc + tim ket hop -> nhom dieu kien phai boc ngoac dung
$combo = (new PartsModel())->getLists(['parts.category_id' => $catMaPhanh], 'Lọc gió');
ok(count($combo) === 0, 'Loc "Ma phanh" + tim "Loc gio" -> 0 (nhom OR duoc boc ngoac dung)');

// Injection
$evil = (new PartsModel())->getLists([], "'; DROP TABLE parts;--");
ok(count($evil) === 0, 'Payload DROP -> khong lot');
ok($pdo->query('SELECT COUNT(*) FROM parts')->fetchColumn() > 0, 'Bang parts VAN CON');

// ================================================================
section('Chi tiet phu tung — lap cho xe nao');

$fits = (new PartFitmentsModel())->getCarYearsByPart($p3);
ok(count($fits) === 3, 'Ma phanh lap cho 3 doi xe', count($fits) . ' doi');
ok(!empty($fits[0]['brand_name']) && !empty($fits[0]['model_name']),
   'getCarYearsByPart() join 3 cap (fitment -> year -> model -> brand)',
   ($fits[0]['brand_name'] ?? '') . ' ' . ($fits[0]['model_name'] ?? ''));

ok((new PartFitmentsModel())->countPartsByCarYear($viosCu) === 2, 'countPartsByCarYear() dem dung');

// ================================================================
section('syncForPart() — thay toan bo lien ket, co transaction');

$n = (new PartFitmentsModel())->syncForPart($p3, [$viosCu]);
ok($n === 1, 'syncForPart() gan lai chi 1 doi', "n=$n");

$fitsAfter = (new PartFitmentsModel())->getCarYearsByPart($p3);
ok(count($fitsAfter) === 1, 'Lien ket cu da bi thay the hoan toan');

// City gio khong con ma phanh
$cityAfter = (new PartsModel())->getByCarYear($cityMoi);
ok(count($cityAfter) === 1, 'City chi con 1 phu tung sau khi go ma phanh');

// sync voi id doi xe khong ton tai -> rollback, khong de lai gi
$failed = false;
try {
    (new PartFitmentsModel())->syncForPart($p3, [$viosCu, 999999]);
} catch (\Throwable $e){ $failed = true; }
ok($failed, 'sync voi doi xe khong ton tai -> nem exception (khoa ngoai chan)');

$fitsRollback = (new PartFitmentsModel())->getCarYearsByPart($p3);
ok(count($fitsRollback) === 1,
   'ROLLBACK dung: lien ket cu VAN CON nguyen, khong bi xoa nua chung',
   count($fitsRollback) . ' doi');

// ================================================================
section('Khoa ngoai — xoa phu tung thi lien ket tu xoa (CASCADE)');

$before = $pdo->query("SELECT COUNT(*) FROM part_fitments WHERE part_id = $p1")->fetchColumn();
ok($before == 1, 'Truoc khi xoa: co 1 lien ket');

(new PartsModel())->remove($p1);
$after = $pdo->query("SELECT COUNT(*) FROM part_fitments WHERE part_id = $p1")->fetchColumn();
ok($after == 0, 'Xoa phu tung -> lien ket tu xoa theo (CASCADE)');

// Xoa doi xe -> lien ket cung tu xoa
$beforeY = $pdo->query("SELECT COUNT(*) FROM part_fitments WHERE car_year_id = $cityMoi")->fetchColumn();
ok($beforeY > 0, 'Truoc khi xoa doi City: con lien ket');
(new CarYearsModel())->remove($cityMoi);
$afterY = $pdo->query("SELECT COUNT(*) FROM part_fitments WHERE car_year_id = $cityMoi")->fetchColumn();
ok($afterY == 0, 'Xoa doi xe -> lien ket tu xoa theo (CASCADE)');

// Phu tung KHONG bi xoa lay khi xoa doi xe
ok(!empty((new PartsModel())->getDetail($p4)), 'Xoa doi xe -> phu tung VAN CON (chi mat lien ket)');

// ================================================================
// Don dep
$pdo->exec("DELETE FROM parts WHERE code LIKE 'PT-TEST-%'");
$pdo->exec("DELETE FROM car_models WHERE slug LIKE 'pt-test-%'");
$pdo->exec("DELETE FROM car_brands WHERE slug LIKE 'pt-test-%'");

exit(summary());

<?php
/**
 * Test CÂY DANH MỤC XE trên MySQL THẬT.
 *
 * Chạy:  C:\xampp\php\php.exe tests\CarCatalogTest.php
 *
 * Dung MySQL that (khong phai SQLite) vi cay nay phu thuoc KHOA NGOAI —
 * ma SQLite mac dinh KHONG bat khoa ngoai (phai PRAGMA foreign_keys=ON).
 * Test khoa ngoai tren SQLite se PASS oan.
 *
 * Tu skip neu khong ket noi duoc DB.
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

require_once __DIR__ . '/../app/models/LookupModel.php';
require_once __DIR__ . '/../app/models/CarBrandsModel.php';
require_once __DIR__ . '/../app/models/CarBodyTypesModel.php';
require_once __DIR__ . '/../app/models/CarFuelsModel.php';
require_once __DIR__ . '/../app/models/CarColorsModel.php';
require_once __DIR__ . '/../app/models/CarModelsModel.php';
require_once __DIR__ . '/../app/models/CarYearsModel.php';

echo 'PHP ' . PHP_VERSION . ' | MySQL ' . $pdo->query('SELECT VERSION()')->fetchColumn() . "\n";

// Don sach du lieu test cu (giu du lieu moi cua migration)
$pdo->exec("DELETE FROM car_years WHERE model_id IN (SELECT id FROM car_models WHERE slug LIKE 'test-%')");
$pdo->exec("DELETE FROM car_models WHERE slug LIKE 'test-%'");
$pdo->exec("DELETE FROM car_brands WHERE slug LIKE 'test-%'");

// ================================================================
section('Du lieu moi tu migration');

ok(count((new CarBrandsModel())->getLists()) >= 6, 'Co it nhat 6 hang xe');
ok(count((new CarBodyTypesModel())->getLists()) >= 6, 'Co it nhat 6 kieu dang');
ok(count((new CarFuelsModel())->getLists()) >= 4, 'Co it nhat 4 loai nhien lieu');
ok(count((new CarColorsModel())->getLists()) >= 6, 'Co it nhat 6 mau xe');

$toyota = (new CarBrandsModel())->findBySlug('toyota');
ok(!empty($toyota) && $toyota['name'] === 'Toyota', 'findBySlug("toyota") tra ve Toyota');

// Tieng Viet co dau — nho migration utf8mb4
$dau = (new CarFuelsModel())->findBySlug('dau-diesel');
ok(!empty($dau) && $dau['name'] === 'Dầu (Diesel)', 'Tieng Viet co dau luu/doc dung', $dau['name'] ?? '');

// ================================================================
section('Hang xe — CRUD');

$bm = new CarBrandsModel();
$brandId = $bm->add(['name' => 'Test Hãng', 'slug' => 'test-hang', 'country' => 'Việt Nam', 'status' => 1]);
ok($brandId > 0, 'add() tao duoc hang xe', "id=$brandId");

$b = (new CarBrandsModel())->getDetail($brandId);
ok($b['name'] === 'Test Hãng', 'getDetail() doc dung');
ok(!empty($b['create_at']), 'add() tu dien create_at');

(new CarBrandsModel())->edit(['name' => 'Test Hãng Sửa'], $brandId);
$b2 = (new CarBrandsModel())->getDetail($brandId);
ok($b2['name'] === 'Test Hãng Sửa', 'edit() sua duoc');
ok(!empty($b2['update_at']), 'edit() tu dien update_at');

// slug phai duy nhat
$dupFailed = false;
try {
    (new CarBrandsModel())->add(['name' => 'Trung slug', 'slug' => 'test-hang', 'status' => 1]);
} catch (\Throwable $e){ $dupFailed = true; }
ok($dupFailed, 'slug trung -> bi tu choi (UNIQUE KEY)');

// ================================================================
section('Model xe — thuoc hang');

$sedan = (new CarBodyTypesModel())->findBySlug('sedan');
$mm = new CarModelsModel();
$modelId = $mm->add([
    'brand_id' => $brandId, 'body_type_id' => $sedan['id'],
    'name' => 'Test Model', 'slug' => 'test-model', 'status' => 1,
]);
ok($modelId > 0, 'add() tao duoc model xe', "id=$modelId");

$list = (new CarModelsModel())->getLists(['car_models.id' => $modelId]);
ok(count($list) === 1, 'getLists() tra ve model vua tao');
ok($list[0]['brand_name'] === 'Test Hãng Sửa', 'getLists() joinOn(car_brands) lay duoc ten hang',
   $list[0]['brand_name'] ?? '');
ok($list[0]['body_type_name'] === 'Sedan', 'getLists() leftJoinOn(car_body_types) lay duoc kieu dang',
   $list[0]['body_type_name'] ?? '');

$byBrand = (new CarModelsModel())->getByBrand($brandId);
ok(count($byBrand) === 1, 'getByBrand() loc dung theo hang');

// Tim kiem theo tu khoa
$found = (new CarModelsModel())->getLists([], 'Test Mod');
ok(count($found) >= 1, 'getLists() tim theo tu khoa chay duoc');

// Injection qua tu khoa
$evil = (new CarModelsModel())->getLists([], "'; DROP TABLE car_models;--");
ok(count($evil) === 0, 'Payload DROP trong tu khoa -> khong lot');
$still = $pdo->query('SELECT COUNT(*) FROM car_models')->fetchColumn();
ok($still > 0, 'Bang car_models VAN CON');

// ================================================================
section('Khoa ngoai — ON DELETE RESTRICT (khong xoa hang con model)');

$removed = (new CarBrandsModel())->remove($brandId);
ok($removed === false, 'remove() hang con model -> tra ve false (khong nem exception)');

$stillThere = (new CarBrandsModel())->getDetail($brandId);
ok(!empty($stillThere), 'Hang VAN CON sau khi remove() bi tu choi');

ok((new CarBrandsModel())->countModels($brandId) === 1, 'countModels() dem dung');

// Xoa thang o tang DB phai bi MySQL chan
$fkBlocked = false;
try {
    $pdo->exec("DELETE FROM car_brands WHERE id = $brandId");
} catch (\PDOException $e){
    $fkBlocked = (strpos($e->getMessage(), 'foreign key constraint') !== false);
}
ok($fkBlocked, 'MySQL CHAN xoa hang con model (RESTRICT) — chung minh o tang DB');

// ================================================================
section('Doi xe — thuoc model');

$ym = new CarYearsModel();
$y1 = $ym->add(['model_id' => $modelId, 'year_from' => 2014, 'year_to' => 2017, 'name' => 'Đời 1', 'status' => 1]);
$y2 = (new CarYearsModel())->add(['model_id' => $modelId, 'year_from' => 2018, 'year_to' => null, 'name' => 'Đời 2', 'status' => 1]);
ok($y1 > 0 && $y2 > 0, 'Tao duoc 2 doi xe');

$years = (new CarYearsModel())->getByModel($modelId);
ok(count($years) === 2, 'getByModel() tra ve 2 doi');
ok($years[0]['year_from'] == 2018, 'Sap xep doi moi nhat truoc', $years[0]['year_from'] ?? '');

$yl = (new CarYearsModel())->getLists(['car_years.model_id' => $modelId]);
ok($yl[0]['model_name'] === 'Test Model' && $yl[0]['brand_name'] === 'Test Hãng Sửa',
   'getLists() join 2 cap (year -> model -> brand)');

// findByModelAndYear — dung khi khach chon "Vios doi 2020"
$f2015 = (new CarYearsModel())->findByModelAndYear($modelId, 2015);
ok(!empty($f2015) && $f2015['id'] == $y1, 'Nam 2015 -> tim ra Doi 1 (2014-2017)');

$f2020 = (new CarYearsModel())->findByModelAndYear($modelId, 2020);
ok(!empty($f2020) && $f2020['id'] == $y2, 'Nam 2020 -> tim ra Doi 2 (2018-nay, year_to NULL)');

$f2010 = (new CarYearsModel())->findByModelAndYear($modelId, 2010);
ok(empty($f2010), 'Nam 2010 -> khong doi nao khop');

$f2017 = (new CarYearsModel())->findByModelAndYear($modelId, 2017);
ok(!empty($f2017) && $f2017['id'] == $y1, 'Nam 2017 (bien tren) -> van thuoc Doi 1');
$f2018 = (new CarYearsModel())->findByModelAndYear($modelId, 2018);
ok(!empty($f2018) && $f2018['id'] == $y2, 'Nam 2018 (bien duoi) -> thuoc Doi 2');

// ================================================================
section('Khoa ngoai — ON DELETE CASCADE (xoa model thi doi xe tu xoa)');

ok((new CarModelsModel())->countYears($modelId) === 2, 'countYears() dem dung truoc khi xoa');

(new CarModelsModel())->remove($modelId);

$yearsLeft = $pdo->query("SELECT COUNT(*) FROM car_years WHERE model_id = $modelId")->fetchColumn();
ok($yearsLeft == 0, 'Xoa model -> doi xe con TU DONG xoa theo (CASCADE)', "con: $yearsLeft");

// Gio xoa hang duoc roi vi het model
$removedNow = (new CarBrandsModel())->remove($brandId);
ok($removedNow !== false, 'Het model -> xoa hang duoc');
ok(empty((new CarBrandsModel())->getDetail($brandId)), 'Hang da bi xoa that');

// ================================================================
section('ON DELETE SET NULL — xoa kieu dang thi model khong mat');

$bId = (new CarBrandsModel())->add(['name' => 'Test H2', 'slug' => 'test-h2', 'status' => 1]);
$btId = (new CarBodyTypesModel())->add(['name' => 'Test Kiểu', 'slug' => 'test-kieu', 'status' => 1]);
$mId = (new CarModelsModel())->add(['brand_id' => $bId, 'body_type_id' => $btId,
                                    'name' => 'Test M2', 'slug' => 'test-m2', 'status' => 1]);

(new CarBodyTypesModel())->remove($btId);

$m = (new CarModelsModel())->getDetail($mId);
ok(!empty($m), 'Xoa kieu dang -> model VAN CON (khong bi xoa lay)');
ok($m['body_type_id'] === null, 'body_type_id thanh NULL (SET NULL)', var_export($m['body_type_id'], true));

// Don dep
$pdo->exec("DELETE FROM car_models WHERE id = $mId");
$pdo->exec("DELETE FROM car_brands WHERE id = $bId");

exit(summary());

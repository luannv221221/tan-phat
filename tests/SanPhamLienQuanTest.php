<?php
/**
 * Test SAN PHAM LIEN QUAN tren trang chi tiet (MySQL that).
 *
 * Chay:  C:\xampp\php\php.exe tests\SanPhamLienQuanTest.php
 *
 * PartsModel::lienQuan() xep 3 muc, lay dan cho du limit roi dung:
 *   1. Cung danh muc VA lap chung it nhat mot doi xe
 *   2. Cung danh muc
 *   3. Cung thuong hieu
 *
 * Trong tam:
 *   - KHONG bao gio tu goi y chinh no.
 *   - Thu tu chinh la muc do lien quan -> muc 1 phai dung TRUOC muc 2.
 *     whereIn tra ve theo thu tu MySQL thay tien chu khong theo thu tu minh
 *     truyen vao, nen phai xep lai bang tay; day la cho de hong nhat.
 *   - Mot phu tung lap cho NHIEU doi xe -> join part_fitments ra nhieu dong,
 *     khong duoc hien trung the.
 *   - Hang tat (status=0) hoac khong cho len web phai bi loai — day la trang
 *     cong khai.
 *   - $loaiTru: khoi "phu kien di kem" ngay phia tren da hien roi thi khoi
 *     nay khong duoc hien lai, khong thi thanh hai khoi giong het nhau.
 *   - Rong thi tra ve mang rong (view dua vao day de khong in tieu de tro troi).
 *
 * Kich ban dung du lieu RIENG (tien to lq-test-) chu khong muon danh muc that,
 * de ket qua khong doi khi khach them hang.
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

require_once __DIR__ . '/../app/models/PartsModel.php';

echo 'PHP ' . PHP_VERSION . ' | MySQL ' . $pdo->query('SELECT VERSION()')->fetchColumn() . "\n";

// ---------------------------------------------------------------------------
// Don du lieu cu (neu lan chay truoc chet giua chung)
$donDep = function() use ($pdo){
    $pdo->exec("DELETE FROM parts WHERE code LIKE 'LQ-TEST-%'");
    $pdo->exec("DELETE FROM car_models WHERE slug LIKE 'lq-test-%'");
    $pdo->exec("DELETE FROM car_brands WHERE slug LIKE 'lq-test-%'");
    $pdo->exec("DELETE FROM part_categories WHERE slug LIKE 'lq-test-%'");
    $pdo->exec("DELETE FROM part_brands WHERE slug LIKE 'lq-test-%'");
};
$donDep();

// ---------------------------------------------------------------------------
// Dung kich ban
$now = date('Y-m-d H:i:s');

$chen = function($bang, array $cot) use ($pdo, $now){
    $cot['create_at'] = $now;
    $ten = array_keys($cot);
    $sql = "INSERT INTO `$bang` (`" . implode('`,`', $ten) . "`) VALUES ("
         . implode(',', array_fill(0, count($ten), '?')) . ")";
    $pdo->prepare($sql)->execute(array_values($cot));
    return (int) $pdo->lastInsertId();
};

$hangXe = $chen('car_brands',  ['name' => 'LQ Toyota', 'slug' => 'lq-test-toyota', 'status' => 1]);
$model  = $chen('car_models',  ['brand_id' => $hangXe, 'name' => 'LQ Vios', 'slug' => 'lq-test-vios', 'status' => 1]);
$doi1   = $chen('car_years',   ['model_id' => $model, 'year_from' => 2018, 'name' => 'LQ Vios 2018', 'status' => 1]);
$doi2   = $chen('car_years',   ['model_id' => $model, 'year_from' => 2022, 'name' => 'LQ Vios 2022', 'status' => 1]);

$catA = $chen('part_categories', ['name' => 'LQ Danh muc A', 'slug' => 'lq-test-cat-a', 'status' => 1]);
$catB = $chen('part_categories', ['name' => 'LQ Danh muc B', 'slug' => 'lq-test-cat-b', 'status' => 1]);
$brX  = $chen('part_brands',     ['name' => 'LQ Hang X', 'slug' => 'lq-test-br-x', 'status' => 1]);
$brY  = $chen('part_brands',     ['name' => 'LQ Hang Y', 'slug' => 'lq-test-br-y', 'status' => 1]);

/** Tao 1 phu tung test */
$taoHang = function($ma, $ten, $cat, $br, $them = []) use ($chen){
    return $chen('parts', array_merge([
        'code'        => $ma,
        'name'        => $ten,
        'slug'        => strtolower(str_replace('LQ-TEST-', 'lq-test-hang-', $ma)),
        'category_id' => $cat,
        'brand_id'    => $br,
        'price'       => 100000,
        'status'      => 1,
        'show_on_web' => 1,
    ], $them));
};

// G = hang dang xem. A* cung danh muc, B* khac danh muc.
//
// THU TU TAO O DAY LA CO CHU DICH: B1 (muc 3 — chi cung thuong hieu) phai co
// id NHO HON A1/A2/A3 (muc 1, 2). Nho vay thu tu id tu nhien cua MySQL
// (B1,A1,A2,A3) KHAC han thu tu xep hang dung (A2,A1,A3,B1).
//
// Neu tao B1 sau cung thi hai thu tu do trung nhau, va bo test se PASS ca khi
// lienQuan() quen xep lai ket qua theo muc — da thu co tinh pha ham xep lai va
// bo test cu van bao PASS het.
$G    = $taoHang('LQ-TEST-G',    'LQ Goc dang xem',          $catA, $brX);
$B1   = $taoHang('LQ-TEST-B1',   'LQ Khac cat, cung hang X', $catB, $brX);
$B2   = $taoHang('LQ-TEST-B2',   'LQ Khong lien quan gi',    $catB, $brY);
$A1   = $taoHang('LQ-TEST-A1',   'LQ Cung cat + chung xe',   $catA, $brY);
$A2   = $taoHang('LQ-TEST-A2',   'LQ Cung cat + 2 doi xe',   $catA, $brY);
$A3   = $taoHang('LQ-TEST-A3',   'LQ Chi cung cat',          $catA, $brY);
$TAT  = $taoHang('LQ-TEST-OFF',  'LQ Da tat',                $catA, $brY, ['status' => 0]);
$AN   = $taoHang('LQ-TEST-HIDE', 'LQ Khong len web',         $catA, $brY, ['show_on_web' => 0]);

// Fitment: G lap doi1. A1 lap doi1. A2 lap CA doi1 lan doi2 (de thu trung the).
foreach ([[$G, $doi1], [$A1, $doi1], [$A2, $doi1], [$A2, $doi2]] as $f){
    $chen('part_fitments', ['part_id' => $f[0], 'car_year_id' => $f[1]]);
}

$pm   = new PartsModel();
$goc  = $pm->getDetail($G);
$doiG = [$doi1];

/** Lay mang ma hang cho de doc khi bao loi */
$ma = function($rows){
    return implode(',', array_map(function($r){ return $r['code']; }, $rows));
};
/** Vi tri cua 1 id trong ket qua, -1 neu khong co */
$viTri = function($rows, $id){
    foreach ($rows as $i => $r){ if ((int) $r['id'] === (int) $id) return $i; }
    return -1;
};

// ===========================================================================
section('Khong tu goi y chinh no');

$kq = $pm->lienQuan($goc, $doiG, 10);
ok($viTri($kq, $G) === -1, 'Hang dang xem KHONG nam trong goi y', $ma($kq));
ok(!empty($kq), 'Co goi y', $ma($kq));

// ===========================================================================
section('Thu tu = muc do lien quan');

ok($viTri($kq, $A1) >= 0 && $viTri($kq, $A2) >= 0, 'Co hang cung danh muc + chung xe', $ma($kq));
ok($viTri($kq, $A3) >= 0, 'Co hang chi cung danh muc', $ma($kq));

ok($viTri($kq, $A1) < $viTri($kq, $A3),
   'Lap chung xe dung TRUOC chi-cung-danh-muc',
   'thu tu thuc te: ' . $ma($kq));
ok($viTri($kq, $A2) < $viTri($kq, $A3),
   'Hang lap 2 doi xe cung dung truoc',
   'thu tu thuc te: ' . $ma($kq));

ok($viTri($kq, $B1) > $viTri($kq, $A3),
   'Cung thuong hieu la muc CUOI, sau het hang cung danh muc',
   'thu tu thuc te: ' . $ma($kq));

// Chot han ca day. B1 duoc tao TRUOC A1/A2/A3 nen neu quen xep lai theo muc,
// MySQL se tra B1 len dau va dong nay bat duoc ngay.
$thuTu = array_map(function($r){ return $r['code']; }, $kq);
ok($thuTu === ['LQ-TEST-A2', 'LQ-TEST-A1', 'LQ-TEST-A3', 'LQ-TEST-B1'],
   'Ca day dung thu tu: chung xe -> cung danh muc -> cung thuong hieu',
   'thuc te: ' . implode(',', $thuTu));

// ===========================================================================
section('Khong trung the');

$demA2 = 0;
foreach ($kq as $r){ if ((int) $r['id'] === $A2) $demA2++; }
ok($demA2 === 1,
   'Hang lap NHIEU doi xe chi hien MOT the',
   "hien $demA2 lan — join part_fitments dang de lot ban trung");

$idHet = array_map(function($r){ return (int) $r['id']; }, $kq);
ok(count($idHet) === count(array_unique($idHet)), 'Khong co id nao lap lai', $ma($kq));

// ===========================================================================
section('Chi lay hang cong khai');

ok($viTri($kq, $TAT) === -1, 'Hang da tat (status=0) bi loai', $ma($kq));
ok($viTri($kq, $AN)  === -1, 'Hang khong cho len web bi loai', $ma($kq));

// ===========================================================================
section('Cung thuong hieu de vet, nhung khong vet bua');

ok($viTri($kq, $B1) >= 0,
   'Khac danh muc nhung cung thuong hieu -> van duoc goi y',
   $ma($kq));
ok($viTri($kq, $B2) === -1,
   'Khac ca danh muc lan thuong hieu -> KHONG duoc goi y',
   'B2 lot vao nghia la dang vet hang bua: ' . $ma($kq));

// ===========================================================================
section('Loai khoi trung voi "phu kien di kem"');

$kqLoai = $pm->lienQuan($goc, $doiG, 10, [$A1, $B1]);
ok($viTri($kqLoai, $A1) === -1, 'Ma trong $loaiTru bi bo (A1)', $ma($kqLoai));
ok($viTri($kqLoai, $B1) === -1, 'Ma trong $loaiTru bi bo (B1)', $ma($kqLoai));
ok($viTri($kqLoai, $A2) >= 0,  'Cac ma khac van con', $ma($kqLoai));

// ===========================================================================
section('Gioi han so the');

ok(count($pm->lienQuan($goc, $doiG, 2)) === 2, 'limit=2 -> dung 2 the');
ok(count($pm->lienQuan($goc, $doiG, 1)) === 1, 'limit=1 -> dung 1 the');
ok($pm->lienQuan($goc, $doiG, 0) === [], 'limit=0 -> mang rong');

$hai = $pm->lienQuan($goc, $doiG, 2);
ok($viTri($hai, $A1) >= 0 || $viTri($hai, $A2) >= 0,
   'Cat bot thi giu lai muc GAN nhat, khong phai muc xa',
   $ma($hai));

// ===========================================================================
section('Chua khai xe thi van chay');

$khongXe = $pm->lienQuan($goc, [], 10);
ok($viTri($khongXe, $A3) >= 0,
   'Khong truyen doi xe -> van ra hang cung danh muc',
   $ma($khongXe));
ok($viTri($khongXe, $G) === -1, 'Van khong tu goi y chinh no', $ma($khongXe));

// ===========================================================================
section('Truong hop rong');

$treoLo = $taoHang('LQ-TEST-ORPHAN', 'LQ Khong cat khong hang', null, null);
$rong   = $pm->lienQuan($pm->getDetail($treoLo), [], 10);
ok($rong === [],
   'Khong danh muc, khong thuong hieu -> mang rong',
   'view dua vao day de khong in tieu de tro troi: ' . $ma($rong));

ok($pm->lienQuan([], [], 10) === [], 'Truyen mang rong -> khong no, tra ve rong');

// ===========================================================================
section('Du cot de dung the san pham');

$mot = $kq[0];
foreach (['id', 'code', 'name', 'slug', 'price', 'sale_price', 'brand_name'] as $cot){
    ok(array_key_exists($cot, $mot), "The co cot `$cot`");
}

// ---------------------------------------------------------------------------
$donDep();

exit(summary());

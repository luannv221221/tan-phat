<?php
/**
 * Test CAC CHOT AN TOAN CUA KHO (sua 04/08/2026).
 *
 * Chay:  C:\xampp\php\php.exe tests\StockGuardTest.php
 *
 * Phu 4 thu vua sua:
 *   1. applyOut chan ton am (luoi cuoi khi hai nguoi ghi so cung luc)
 *   2. transaction() long nhau duoc
 *   3. getBalanceBefore(kho = 0) cong du moi kho
 *   4. avgCostAnyWarehouse() lay gia von o kho khac cho hang thua kiem ke
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
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n";
    exit(0);
}

foreach (['LookupModel','ProductUnitsModel','PartsModel','StocksModel','WarehousesModel'] as $m){
    require_once __DIR__ . '/../app/models/' . $m . '.php';
}

echo 'PHP ' . PHP_VERSION . ' | MySQL ' . $pdo->query('SELECT VERSION()')->fetchColumn() . "\n";

$pdo->exec("DELETE FROM parts WHERE code LIKE 'SG-TEST-%'");

$whs = (new WarehousesModel())->getLists();
if (count($whs) < 2){ echo "[SKIP] Can it nhat 2 kho de thu.\n"; exit(0); }
$whA = (int) $whs[0]['id'];
$whB = (int) $whs[1]['id'];

$unit = (new ProductUnitsModel())->findBySlug('cai');
$pid  = (new PartsModel())->add(['code' => 'SG-TEST-001', 'name' => 'SG hang thu', 'slug' => 'sg-test-1',
                                 'unit_id' => $unit['id'], 'price' => 100000, 'status' => 1]);
$st = new StocksModel();

// ================================================================
section('applyOut CHAN ton am');

$st->applyIn($whA, $pid, 10, 300000, 'receipt', 95001, 'SG-IN-1', date('Y-m-d'), null);
ok(abs($st->available($whA, $pid) - 10) < 1e-9, 'Ton dau la 10');

// Xuat vua du -> phai chay binh thuong
$st->applyOut($whA, $pid, 10, 'issue', 95002, 'SG-OUT-1', date('Y-m-d'), null);
ok(abs($st->available($whA, $pid)) < 1e-9, 'Xuat vua du 10 -> ton 0, khong bi chan');

// Xuat qua ton -> phai NEM exception
$st->applyIn($whA, $pid, 5, 300000, 'receipt', 95003, 'SG-IN-2', date('Y-m-d'), null);
$nem = false;
try {
    $st->applyOut($whA, $pid, 6, 'issue', 95004, 'SG-OUT-2', date('Y-m-d'), null);
} catch (\Throwable $e){
    $nem = true;
}
ok($nem, 'Xuat 6 khi chi con 5 -> nem exception');
ok(abs($st->available($whA, $pid) - 5) < 1e-9, 'Ton VAN LA 5, khong bi tut xuong am', $st->available($whA, $pid));

$amConLai = (int) $pdo->query("SELECT COUNT(*) FROM stocks WHERE part_id=$pid AND quantity < 0")->fetchColumn();
ok($amConLai === 0, 'Khong co dong ton am nao');

// ================================================================
section('transaction() LONG NHAU duoc');

// Truoc day PDO nem "There is already an active transaction" -> cho nao can
// gop nhieu buoc vao 1 transaction thi khong dam gop.
$chay = 0;
$loi  = '';
try {
    $st->transaction(function($db) use ($st, &$chay){
        $chay++;
        $st->transaction(function($db2) use (&$chay){
            $chay++;
            (new StocksModel())->transaction(function($db3) use (&$chay){ $chay++; });
        });
    });
} catch (\Throwable $e){ $loi = $e->getMessage(); }

ok($loi === '', 'Long 3 lop khong nem loi', $loi);
ok($chay === 3, 'Ca 3 lop deu chay', 'chay=' . $chay);

// Lop trong nem loi -> lop ngoai cung phai ROLLBACK tat ca
$pdo->exec("UPDATE parts SET name='SG truoc rollback' WHERE id=$pid");
$daNem = false;
try {
    $st->transaction(function($db) use ($pid, $st){
        (new PartsModel())->edit(['name' => 'SG da doi'], $pid);
        $st->transaction(function($db2){
            throw new \RuntimeException('loi o lop trong');
        });
    });
} catch (\Throwable $e){ $daNem = true; }

$ten = $pdo->query("SELECT name FROM parts WHERE id=$pid")->fetchColumn();
ok($daNem, 'Loi o lop trong noi len duoc lop ngoai');
ok($ten === 'SG truoc rollback', 'Lop ngoai da ROLLBACK thay doi cua lop trong', $ten);

// ================================================================
section('getBalanceBefore(kho = 0) phai CONG du moi kho');

$pdo->exec("DELETE FROM stock_cards WHERE part_id=$pid");
$pdo->exec("DELETE FROM stocks WHERE part_id=$pid");

$hom_qua = date('Y-m-d', strtotime('-2 day'));
$homNay  = date('Y-m-d');
$st->applyIn($whA, $pid, 7,  200000, 'receipt', 95010, 'SG-A', $hom_qua, null);
$st->applyIn($whB, $pid, 4,  200000, 'receipt', 95011, 'SG-B', $hom_qua, null);

$a = $st->getBalanceBefore($pid, $whA, $homNay);
$b = $st->getBalanceBefore($pid, $whB, $homNay);
$tong = $st->getBalanceBefore($pid, 0, $homNay);

ok(abs($a['qty'] - 7) < 1e-9, 'Kho A truoc hom nay = 7', $a['qty']);
ok(abs($b['qty'] - 4) < 1e-9, 'Kho B truoc hom nay = 4', $b['qty']);
ok(abs($tong['qty'] - 11) < 1e-9, 'Kho = 0 -> 11 (CONG ca hai kho, khong phai lay 1 kho)', $tong['qty']);
ok(abs($tong['value'] - ($a['value'] + $b['value'])) < 0.01, 'Gia tri cung duoc cong du');

// ================================================================
section('avgCostAnyWarehouse — gia von cho hang thua kiem ke');

// Kho A: 7 cai gia 200k. Kho B: 4 cai gia 200k -> binh quan chung 200k
ok(abs($st->avgCostAnyWarehouse($pid) - 200000) < 0.01,
   'Lay duoc binh quan tu cac kho dang co hang', $st->avgCostAnyWarehouse($pid));

// Kho thu 3 chua tung co ma nay -> avgCost tai do = 0, nhung fallback van ra 200k
$whMoi = 999999;
ok($st->avgCost($whMoi, $pid) == 0.0, 'Kho chua tung co ma nay -> avgCost = 0');
ok(abs($st->avgCostAnyWarehouse($pid) - 200000) < 0.01,
   'Fallback cho kiem ke: van co gia von de nhap hang thua, khong nhap gia 0');

// Ma hoan toan khong co ton o dau -> tra 0 (Stocktakes se canh bao)
$pid2 = (new PartsModel())->add(['code' => 'SG-TEST-002', 'name' => 'SG chua co ton', 'slug' => 'sg-test-2',
                                 'unit_id' => $unit['id'], 'price' => 50000, 'status' => 1]);
ok($st->avgCostAnyWarehouse($pid2) == 0.0, 'Ma chua co ton o dau -> 0 (Stocktakes phai canh bao)');

$sSrc = codeOnly(__DIR__ . '/../app/controllers/admin/Stocktakes.php');
ok(strpos($sSrc, 'avgCostAnyWarehouse') !== false, 'Stocktakes co dung fallback gia von');
ok(strpos($sSrc, 'khongCoGiaVon') !== false, 'Stocktakes co canh bao khi khong tim duoc gia von');

// ================================================================
section('Orders::invoice() boc transaction');

$oSrc = codeOnly(__DIR__ . '/../app/controllers/admin/Orders.php');
ok(preg_match('~function invoice\(.*?transaction\(function~s', $oSrc) === 1,
   'invoice() boc 4 thao tac ghi trong 1 transaction');
ok(preg_match('~function invoice\(.*?syncForInvoice~s', $oSrc) === 1,
   'syncForInvoice nam trong transaction do (chay long duoc)');

// ---- Don dep ----
$pdo->exec("DELETE FROM stock_cards WHERE part_id IN ($pid, $pid2)");
$pdo->exec("DELETE FROM stocks WHERE part_id IN ($pid, $pid2)");
$pdo->exec("DELETE FROM parts WHERE code LIKE 'SG-TEST-%'");

exit(summary());

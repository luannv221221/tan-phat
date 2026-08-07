<?php
/**
 * Test ĐƠN HÀNG TRỪ/CỘNG KHO THEO TRẠNG THÁI (chốt 04/08/2026).
 *
 * Chạy:  C:\xampp\php\php.exe tests\OrderStockTest.php
 *
 * Trọng tâm là vòng "Hoàn thành -> Hoàn hàng": cộng lại sai giá vốn là phá
 * bình quân gia quyền của cả mã hàng, và sai kiểu đó rất khó phát hiện vì
 * số lượng vẫn đúng.
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

$pdo->exec("DELETE FROM parts WHERE code LIKE 'OS-TEST-%'");

$wh   = (new WarehousesModel())->getDefault();
$whId = (int) $wh['id'];
$unit = (new ProductUnitsModel())->findBySlug('cai');

$pm = new PartsModel();
$pid = $pm->add(['code' => 'OS-TEST-001', 'name' => 'OS Ma phanh test', 'slug' => 'os-test-ma-phanh',
                 'unit_id' => $unit['id'], 'price' => 500000, 'status' => 1]);

$st = new StocksModel();

// ================================================================
section('Nhap 2 lo khac gia -> binh quan gia quyen');

$st->applyIn($whId, $pid, 10, 400000, 'receipt', 90001, 'OS-IN-1', date('Y-m-d'), null);
$st->applyIn($whId, $pid, 10, 500000, 'receipt', 90002, 'OS-IN-2', date('Y-m-d'), null);

$qty0 = $st->available($whId, $pid);
$avg0 = $st->avgCost($whId, $pid);
ok(abs($qty0 - 20) < 1e-9, 'Ton 20 sau 2 lo nhap', $qty0);
ok(abs($avg0 - 450000) < 0.01, 'Binh quan = (10*400k + 10*500k)/20 = 450k', $avg0);

// ================================================================
section('Don "Hoan thanh" -> tru kho, chot gia von');

$banRa = 6;
$avgLucXuat = $st->applyOut($whId, $pid, $banRa, 'order', 90010, 'DH-OS-001', date('Y-m-d'), 'Don hang test');

ok(abs($avgLucXuat - 450000) < 0.01, 'applyOut tra ve gia von 450k de chot vao dong hang', $avgLucXuat);
ok(abs($st->available($whId, $pid) - 14) < 1e-9, 'Ton con 14', $st->available($whId, $pid));
ok(abs($st->avgCost($whId, $pid) - 450000) < 0.01, 'Xuat KHONG lam doi binh quan', $st->avgCost($whId, $pid));

// ================================================================
section('Don "Hoan hang" -> cong lai dung gia von da chot');

$st->applyIn($whId, $pid, $banRa, $avgLucXuat, 'order_return', 90010, 'DH-OS-001', date('Y-m-d'), 'Hoan hang');

$qty1 = $st->available($whId, $pid);
$avg1 = $st->avgCost($whId, $pid);
ok(abs($qty1 - $qty0) < 1e-9, 'So luong ve dung nhu truoc khi ban', "$qty1 vs $qty0");
ok(abs($avg1 - $avg0) < 0.01, 'Binh quan ve DUNG nhu cu, khong bi lech', "$avg1 vs $avg0");

// ================================================================
section('Neu hoan sai gia von thi binh quan HONG — chung minh vi sao phai chot');

// Gia su lap trinh sai: cong lai bang GIA BAN (500k) thay vi gia von (450k)
$st->applyOut($whId, $pid, $banRa, 'order', 90011, 'DH-OS-002', date('Y-m-d'), null);
$st->applyIn($whId, $pid, $banRa, 500000, 'order_return', 90011, 'DH-OS-002', date('Y-m-d'), null);
$avgSai = $st->avgCost($whId, $pid);
ok(abs($avgSai - $avg0) > 1, 'Cong lai bang gia ban lam binh quan lech khoi 450k', 'thanh ' . $avgSai);

// Tra lai hien trang cho phan sau
$pdo->exec("DELETE FROM stock_cards WHERE part_id=$pid");
$pdo->exec("DELETE FROM stocks WHERE part_id=$pid");

// ================================================================
section('Thẻ kho ghi lai ca hai chieu de truy vet');

$st->applyIn($whId, $pid, 5, 400000, 'receipt', 90020, 'OS-IN-3', date('Y-m-d'), null);
$a = $st->applyOut($whId, $pid, 2, 'order', 90021, 'DH-OS-003', date('Y-m-d'), null);
$st->applyIn($whId, $pid, 2, $a, 'order_return', 90021, 'DH-OS-003', date('Y-m-d'), null);

$types = $pdo->query("SELECT doc_type, COUNT(*) n FROM stock_cards WHERE part_id=$pid GROUP BY doc_type")->fetchAll(PDO::FETCH_KEY_PAIR);
ok(isset($types['order']) && $types['order'] == 1, 'Co the kho doc_type=order (luc tru)');
ok(isset($types['order_return']) && $types['order_return'] == 1, 'Co the kho doc_type=order_return (luc hoan)');

// Hoan hang KHONG duoc xoa dau vet — day la ly do dung applyIn chu khong reverseDoc
$conOrder = $pdo->query("SELECT COUNT(*) FROM stock_cards WHERE part_id=$pid AND doc_type='order'")->fetchColumn();
ok($conOrder == 1, 'The kho luc tru VAN CON sau khi hoan hang (khong xoa dau vet)');

// ================================================================
section('Cot moi va rang buoc chong tru hai lan');

$cols = array_column($pdo->query('SHOW COLUMNS FROM orders')->fetchAll(PDO::FETCH_ASSOC), 'Field');
ok(in_array('stock_applied', $cols), 'orders.stock_applied ton tai');
ok(in_array('warehouse_id', $cols), 'orders.warehouse_id ton tai');

$icols = array_column($pdo->query('SHOW COLUMNS FROM order_items')->fetchAll(PDO::FETCH_ASSOC), 'Field');
ok(in_array('unit_cost', $icols), 'order_items.unit_cost ton tai');
ok(in_array('cost_amount', $icols), 'order_items.cost_amount ton tai');

$def = $pdo->query("SELECT COLUMN_DEFAULT FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='stock_applied'")->fetchColumn();
ok((int) $def === 0, 'stock_applied mac dinh 0 — don cu coi nhu chua tru kho', var_export($def, true));

$ctrl = codeOnly(__DIR__ . '/../app/controllers/admin/Orders.php');

// Cot moc: cho tru khi CHUA tung tru. Suy tu trang thai thay vi dung co la
// tru trung moi lan bam lai "Hoan thanh".
ok(strpos($ctrl, "\$st === 'completed' && !\$applied") !== false,
   'Chi tru kho khi don CHUA tung tru (dua vao co, khong dua vao trang thai)');
ok(strpos($ctrl, "\$st === 'returned'") !== false, 'Co nhanh xu ly Hoan hang');
ok(strpos($ctrl, "\$st === 'cancelled' && \$applied") !== false,
   'Chan huy thang khi da tru kho (bat di qua Hoan hang)');
ok(strpos($ctrl, 'stock_applied') !== false && strpos($ctrl, "'stock_applied' => 1") !== false,
   'Bat co sau khi tru');
ok(strpos($ctrl, "'stock_applied' => 0") !== false, 'Ha co sau khi hoan hang');

// Chieu nguoc lai: don da tru kho ma con xuat hoa don thi hoa don tru them lan nua
ok(preg_match('~function invoice\(.*?stock_applied~s', $ctrl) === 1,
   'Chan tao hoa don khi don da tru kho (chong tru hai lan)');

// Gop so luong cung ma hang truoc khi so ton
ok(strpos($ctrl, '$need[(int) $it[\'part_id\']] = ($need[(int) $it[\'part_id\']] ?? 0)') !== false,
   'Gop so luong cung ma hang truoc khi kiem tra ton');

// ================================================================
section('Trang thai hien thi');

require_once __DIR__ . '/../app/models/OrdersModel.php';
$sts = OrdersModel::$statuses;
ok(count($sts) === 6, 'Co du 6 trang thai', implode(', ', array_keys($sts)));
ok(($sts['new'] ?? '') === 'Chờ xử lý', 'new -> "Cho xu ly"');
ok(($sts['completed'] ?? '') === 'Hoàn thành', 'completed -> "Hoan thanh"');
ok(isset($sts['returned']) && $sts['returned'] === 'Hoàn hàng', 'Co trang thai Hoan hang');

// Ma trang thai cu phai giu nguyen, khong thi du lieu don dang co thanh mo coi
$dbSts = array_column($pdo->query('SELECT DISTINCT status FROM orders')->fetchAll(PDO::FETCH_ASSOC), 'status');
$la = array_diff($dbSts, array_keys($sts));
ok(empty($la), 'Moi trang thai dang co trong DB deu con hop le', implode(',', $la));

// ---- Don dep ----
$pdo->exec("DELETE FROM stock_cards WHERE part_id=$pid");
$pdo->exec("DELETE FROM stocks WHERE part_id=$pid");
$pdo->exec("DELETE FROM parts WHERE code LIKE 'OS-TEST-%'");

exit(summary());

<?php
/**
 * Test CÁCH LY GARA — các gara độc lập không thấy dữ liệu của nhau.
 *
 * Chạy:  C:\xampp\php\php.exe tests\CachLyGaraTest.php
 *
 * Thiết kế: docs/superpowers/specs/2026-09-22-gara-doc-lap-tren-nen-tang-design.md
 *
 * Dựng ba gara tạm: ZZGA, ZZGB (đang hoạt động) và ZZGK (bị khoá), cùng các
 * tài khoản tạm `zz-cl-*@local.test`. Dọn sạch khi xong — kể cả khi test chết
 * giữa chừng (register_shutdown_function).
 *
 * Bước 1 (nền) có: migration 000076, gara làm việc theo tài khoản, đóng khi
 * thiếu gara, lớp Model gốc, cờ "chỉ Tân Phát". Các bước sau phủ thêm từng
 * nhóm màn hình vào đây.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc  = __DIR__ . '/../';
$base = 'http://localhost:88/tan-phat';

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$mot = function($sql, $b = []) use ($pdo){ $st = $pdo->prepare($sql); $st->execute($b); return $st->fetch(PDO::FETCH_ASSOC) ?: []; };
$so  = function($sql, $b = []) use ($pdo){ $st = $pdo->prepare($sql); $st->execute($b); return (int) $st->fetchColumn(); };
$cot = function($bang) use ($pdo){ return $pdo->query("SHOW COLUMNS FROM `$bang`")->fetchAll(PDO::FETCH_COLUMN); };

/* Dọn rác — chạy trước (lần trước chết giữa chừng) và khi kết thúc */
$donSach = function() use ($pdo, $cot){
    $pdo->exec("DELETE t FROM login_tokens t JOIN users u ON u.id = t.user_id WHERE u.email LIKE 'zz-cl-%@local.test'");
    $pdo->exec("DELETE FROM users WHERE email LIKE 'zz-cl-%@local.test'");
    $pdo->exec("DELETE FROM goods_receipts WHERE receipt_no LIKE 'ZZCL-%'");
    $ds = "SELECT id FROM (SELECT id FROM garages WHERE code LIKE 'ZZG%') t";
    foreach (['partners', 'warehouses', 'garage_part_prices'] as $b){
        if (in_array('garage_id', $cot($b), true)) $pdo->exec("DELETE FROM `$b` WHERE garage_id IN ($ds)");
    }
    $pdo->exec("DELETE FROM warehouses WHERE code LIKE 'ZZCL-%'");
    $pdo->exec("DELETE FROM garages WHERE code LIKE 'ZZG%'");
};
$donSach();
register_shutdown_function($donSach);

$taoGara = function($ma, $ten, $trangThai = 1) use ($pdo){
    $pdo->prepare("INSERT INTO garages (code, name, is_master, status, sort_order, create_at)
                   VALUES (?, ?, 0, ?, 99, NOW())")->execute([$ma, $ten, $trangThai]);
    return (int) $pdo->lastInsertId();
};
$TP = $so("SELECT id FROM garages WHERE is_master = 1 ORDER BY id LIMIT 1");
if (!$TP){ echo "\n[SKIP] Chua co gara tong.\n"; exit(summary()); }
$GA = $taoGara('ZZGA', 'ZZ Gara A');
$GB = $taoGara('ZZGB', 'ZZ Gara B');
$GK = $taoGara('ZZGK', 'ZZ Gara Khoa', 0);

// ---------------------------------------------------------------------------
section('Migration 000076 — nen gara doc lap');

$bangMoi = [
    'partners'            => 'fk_partner_garage',
    'customer_groups'     => 'fk_cgroup_garage',
    'vehicles'            => 'fk_vehicle_garage',
    'warranty_requests'   => 'fk_warranty_garage',
    'warranty_handovers'  => 'fk_handover_garage',
    'goods_receipts'      => 'fk_receipt_garage',
    'goods_issues'        => 'fk_issue_garage',
    'stock_takes'         => 'fk_take_garage',
    'warehouse_transfers' => 'fk_transfer_garage',
];
$bangCu = ['warehouses', 'users', 'quotations', 'sales_invoices', 'receptions'];

$mg = glob($goc . 'database/migrations/*_nen_gara_doc_lap.php');
ok(count($mg) === 1, 'Co migration nen gara doc lap');

if (count($mg) === 1){
    /* Chứng từ kho lấy gara THEO KHO: dựng một kho của gara B và một phiếu
       nhập chưa có gara trên kho đó, chạy lại migration rồi xem phiếu về đâu. */
    $pdo->prepare("INSERT INTO warehouses (code, name, is_default, sort_order, status, garage_id, create_at)
                   VALUES ('ZZCL-KHOB', 'ZZ Kho B', 0, 99, 1, ?, NOW())")->execute([$GB]);
    $khoB = (int) $pdo->lastInsertId();
    if (in_array('garage_id', $cot('goods_receipts'), true)){
        $pdo->prepare("INSERT INTO goods_receipts (receipt_no, warehouse_id, receipt_date, garage_id, create_at)
                       VALUES ('ZZCL-PN1', ?, CURDATE(), NULL, NOW())")->execute([$khoB]);
    }

    $db = new \App\core\Database();
    $m  = require $mg[0];
    $m->setDb($db);
    ob_start();
    try { $m->up(); $loi = ''; } catch (\Throwable $e){ $loi = $e->getMessage(); }
    ob_end_clean();
    ok($loi === '', 'Chay lai migration khong loi (chay nhieu lan duoc)', $loi);

    foreach (array_merge(array_keys($bangMoi), $bangCu) as $b){
        $co = in_array('garage_id', $cot($b), true);
        ok($co, "`$b` co cot garage_id");
        $trong = $co ? $so("SELECT COUNT(*) FROM `$b` WHERE garage_id IS NULL") : -1;
        ok($trong === 0, "Migration gan het dong cua `$b` vao mot gara", "Con $trong dong garage_id IS NULL");
    }

    $pn = $mot("SELECT garage_id FROM goods_receipts WHERE receipt_no = 'ZZCL-PN1'");
    ok(!empty($pn) && (int) $pn['garage_id'] === $GB, 'Phieu nhap lay gara THEO KHO cua no, khong do het ve gara tong',
       'Dang: ' . json_encode($pn));

    $luat = [];
    foreach ($pdo->query("SELECT TABLE_NAME, DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS
                          WHERE CONSTRAINT_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = 'garages'") as $r){
        $luat[$r['TABLE_NAME']] = $r['DELETE_RULE'];
    }
    foreach ($bangMoi as $b => $fk){
        ok(isset($luat[$b]) && $luat[$b] === 'RESTRICT', "Khoa ngoai `$b` -> garages la RESTRICT",
           'SET NULL thi xoa gara xong chung tu thanh "khong cua ai" — khong ai thay nua');
    }

    $coCo = in_array('chi_tan_phat', $cot('modules'), true);
    ok($coCo, '`modules` co cot chi_tan_phat');
    $dsCo = $coCo ? $pdo->query("SELECT link FROM modules WHERE chi_tan_phat = 1")->fetchAll(PDO::FETCH_COLUMN) : [];
    $mongDoi = ['attributes', 'banners', 'car-body-types', 'car-brands', 'car-colors', 'car-fuels', 'car-models',
                'car-years', 'chat', 'contact-messages', 'du-an', 'galleries', 'garages', 'groups', 'menus', 'modules',
                'news', 'news-categories', 'newsletter', 'orders', 'part-categories', 'product-brands',
                'product-manufacturers', 'product-origins', 'product-units', 'products', 'reviews', 'services',
                'settings', 'thong-ke'];
    sort($dsCo); sort($mongDoi);
    ok($dsCo === $mongDoi, 'Dung ' . count($mongDoi) . ' man chi Tan Phat',
       'Thua: ' . implode(',', array_diff($dsCo, $mongDoi)) . ' | Thieu: ' . implode(',', array_diff($mongDoi, $dsCo)));
    foreach (['customers', 'partners', 'quotations', 'vehicles', 'receptions', 'garage-catalog', 'users'] as $l){
        ok(!in_array($l, $dsCo, true), "`$l` la man cua gara, KHONG phai chi Tan Phat");
    }

    foreach (['tax_code', 'email', 'logo'] as $c){
        ok(in_array($c, $cot('garages'), true), "`garages` co cot $c (in len phieu)");
    }
    ok($so("SELECT COUNT(*) FROM garages WHERE code IN ('DMSG', 'DMDN') AND name LIKE 'Tân Phát%'") === 0,
       'Hai gara mau khong con mang ten "Tan Phat ..." (khoi bi hieu la chi nhanh)');
}

require_once $goc . 'app/models/GaragesModel.php';
$GM = new GaragesModel();
ok($GM->dangDungODau($GA) === [], 'Gara moi chua co gi thi khong bao rang buoc');
if (in_array('garage_id', $cot('partners'), true)){
    $pdo->prepare("INSERT INTO partners (code, name, type, status, garage_id, create_at)
                   VALUES ('ZZCL-KH1', 'ZZ Khach A', 'customer', 1, ?, NOW())")->execute([$GA]);
    $dung = $GM->dangDungODau($GA);
    ok(isset($dung['đối tượng']) && $dung['đối tượng'] === 1,
       'Gara co doi tuong thi dangDungODau() bao ro — khong de khoa ngoai RESTRICT bao loi CSDL kho hieu',
       json_encode($dung, JSON_UNESCAPED_UNICODE));
} else {
    ok(false, 'Gara co doi tuong thi dangDungODau() bao ro', '`partners` chua co cot garage_id');
}

// ==== [CLI] ====

// ==== [HTTP] ====

exit(summary());

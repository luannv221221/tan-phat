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

/* Dọn rác — chạy trước (lần trước chết giữa chừng) và khi kết thúc.
   Xoá theo thứ tự con trước cha (khoá ngoại RESTRICT). Mỗi câu bọc try: bảng /
   cột chưa có (CSDL chưa migrate tới bước đó) thì bỏ qua câu đó. */
$donSach = function() use ($pdo){
    $g  = "SELECT id FROM (SELECT id FROM garages WHERE code LIKE 'ZZG%') zg";
    $cau = [];
    /* Chứng từ do tài khoản tạm lập — dọn TRƯỚC khi xoá tài khoản (cần email để
       nhận ra). Khi chặn gara đang hỏng (thử phá), chứng từ gara B lập có thể
       không mang gara nào; không dọn ở đây thì lần chạy lại migration 000076
       gán chúng về gara tổng — lẫn vào dữ liệu thật của Tân Phát. */
    foreach (['warranty_handovers', 'warranty_requests', 'sales_invoices', 'quotations', 'receptions',
              'goods_receipts', 'goods_issues', 'stock_takes', 'warehouse_transfers'] as $b){
        $cau[] = "DELETE x FROM `$b` x JOIN users u ON u.id = x.created_by WHERE u.email LIKE 'zz-cl-%@local.test'";
    }
    $cau = array_merge($cau, [
        "DELETE t FROM login_tokens t JOIN users u ON u.id = t.user_id WHERE u.email LIKE 'zz-cl-%@local.test'",
        "DELETE FROM users WHERE email LIKE 'zz-cl-%@local.test'",
        /* Chứng từ trỏ vào kho / hàng riêng của gara tạm mà KHÔNG mang gara tạm
           (chỉ có khi chặn gara đang hỏng — thử phá): không dọn thì khoá ngoại
           giữ kho / hàng / gara tạm lại và lần chạy sau không dựng được gara. */
        "DELETE i FROM sales_invoice_items i JOIN parts p ON p.id = i.part_id WHERE p.garage_id IN ($g)",
        "DELETE i FROM quotation_items i JOIN parts p ON p.id = i.part_id WHERE p.garage_id IN ($g)",
        "DELETE i FROM sales_invoice_items i JOIN sales_invoices x ON x.id = i.invoice_id JOIN warehouses w ON w.id = x.warehouse_id WHERE w.garage_id IN ($g) OR w.code LIKE 'ZZCL-%'",
        "DELETE x FROM sales_invoices x JOIN warehouses w ON w.id = x.warehouse_id WHERE w.garage_id IN ($g) OR w.code LIKE 'ZZCL-%'",
        "DELETE FROM warranty_handovers WHERE garage_id IN ($g)",
        "DELETE FROM warranty_requests WHERE garage_id IN ($g)",
        "DELETE i FROM sales_invoice_items i JOIN sales_invoices x ON x.id = i.invoice_id WHERE x.garage_id IN ($g)",
        "DELETE FROM sales_invoices WHERE garage_id IN ($g)",
        "DELETE i FROM quotation_items i JOIN quotations x ON x.id = i.quotation_id WHERE x.garage_id IN ($g)",
        "DELETE FROM quotations WHERE garage_id IN ($g)",
        "DELETE FROM receptions WHERE garage_id IN ($g)",
        "DELETE FROM vehicles WHERE garage_id IN ($g)",
        "DELETE FROM goods_receipts WHERE receipt_no LIKE 'ZZCL-%'",
        "DELETE FROM goods_receipts WHERE garage_id IN ($g)",
        "DELETE FROM goods_issues WHERE garage_id IN ($g)",
        "DELETE FROM stock_takes WHERE garage_id IN ($g)",
        "DELETE FROM warehouse_transfers WHERE garage_id IN ($g)",
        "DELETE s FROM stock_cards s JOIN warehouses w ON w.id = s.warehouse_id WHERE w.garage_id IN ($g) OR w.code LIKE 'ZZCL-%'",
        "DELETE s FROM stocks s JOIN warehouses w ON w.id = s.warehouse_id WHERE w.garage_id IN ($g) OR w.code LIKE 'ZZCL-%'",
        "DELETE l FROM warehouse_locations l JOIN warehouses w ON w.id = l.warehouse_id WHERE w.garage_id IN ($g) OR w.code LIKE 'ZZCL-%'",
        "DELETE FROM warehouses WHERE garage_id IN ($g)",
        "DELETE FROM warehouses WHERE code LIKE 'ZZCL-%'",
        "DELETE FROM partners WHERE garage_id IN ($g)",
        "DELETE FROM customer_groups WHERE garage_id IN ($g)",
        "DELETE FROM garage_part_prices WHERE garage_id IN ($g)",
        "DELETE FROM parts WHERE garage_id IN ($g)",
        "DELETE FROM garage_settings WHERE garage_id IN ($g)",
        "DELETE FROM garages WHERE code LIKE 'ZZG%'",
        /* Dòng test tạo ra mà KHÔNG mang gara ZZ — chỉ xảy ra khi chặn gara đang
           hỏng (thử phá), và lần chạy lại migration 000076 gán chúng về gara
           tổng. Dọn theo đúng dấu nhận biết của test này. */
        "DELETE FROM warranty_requests WHERE customer_name = 'ZZ Khach le B'",
        "DELETE FROM receptions WHERE vehicle_id IN (SELECT id FROM (SELECT id FROM vehicles WHERE bien_so_chuan IN ('ZZ911111', 'ZZ822222')) t)",
        "DELETE FROM vehicles WHERE bien_so_chuan IN ('ZZ911111', 'ZZ822222')",
        "DELETE FROM partners WHERE name IN ('ZZ Khach cua A', 'ZZ Khach moi B', 'ZZ Khach trung sdt A', 'ZZ Khach xep nhom A', 'ZZ bi gara B sua')",
    ]);
    foreach ($cau as $c){ try { $pdo->exec($c); } catch (\Throwable $e){} }
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
                'settings', 'thong-ke',
                'tai-khoan-web'];   // 000077 — tài khoản website tách khỏi màn Khách hàng
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

// ---------------------------------------------------------------------------
section('SQL trien khai cho 000076');

$sqlTk = (string) shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($goc . 'tools/xuat-sql-thay-doi.php') . ' --chi-cau-truc');
ok(strpos($sqlTk, "'2026_09_22_000076_nen_gara_doc_lap'") !== false, 'SQL danh dau da chay migration 000076');
foreach ($bangMoi as $b => $fk){
    ok(strpos($sqlTk, "ALTER TABLE `$b` ADD COLUMN `garage_id` INT DEFAULT NULL") !== false
       && strpos($sqlTk, "CONSTRAINT `$fk`") !== false,
       "SQL them garage_id + khoa ngoai cho `$b`");
}
ok(strpos($sqlTk, 'ALTER TABLE `modules` ADD COLUMN `chi_tan_phat`') !== false, 'SQL them cot chi_tan_phat');
ok(preg_match("~UPDATE `modules` SET `chi_tan_phat` = 1 WHERE `link` IN \(([^)]*)\)~", $sqlTk, $mm) === 1
   && substr_count($mm[1], "'") === 2 * count($mongDoi),
   'SQL bat co cho dung ' . count($mongDoi) . ' man chi Tan Phat (chep tu may nay)');
ok(strpos($sqlTk, 'UPDATE `goods_receipts` x JOIN `warehouses` w') !== false,
   'SQL gan phieu nhap theo kho truoc khi do ve gara tong');
/* Chỉ bắt câu ĐỔI cột có sẵn thành NOT NULL. `garage_part_prices` tạo mới với
   `garage_id INT NOT NULL` từ 000065 là đúng — bảng đó luôn có gara. */
ok(!preg_match('~MODIFY\s+(COLUMN\s+)?`garage_id`~i', $sqlTk),
   'SQL buoc 1 KHONG doi garage_id sang NOT NULL', 'Dat som la form cua model chua ghi gara sap');

/* Phần 18 — 000077 */
ok(strpos($sqlTk, "'2026_09_22_000077_khach_va_xe_theo_gara'") !== false, 'SQL danh dau da chay migration 000077');
ok(strpos($sqlTk, 'CREATE TABLE IF NOT EXISTS `garage_settings`') !== false, 'SQL tao bang cau hinh rieng tung gara');
ok(strpos($sqlTk, "'tai-khoan-web', 1,") !== false, 'SQL dang ky man Tai khoan website, co co chi Tan Phat');
foreach (['uq_partners_gara_code', 'uq_vehicles_gara_bien_so', 'uq_vehicles_gara_so_khung', 'uq_receptions_gara_no',
          'uq_warranty_gara_no', 'uq_handover_gara_no'] as $ix){
    ok(strpos($sqlTk, "ADD UNIQUE KEY `$ix`") !== false, "SQL them chi muc khong trung THEO GARA: $ix");
}
ok(strpos($sqlTk, 'INSERT INTO `vehicles`') !== false && strpos($sqlTk, 'FROM `member_vehicles` v') !== false,
   'SQL chuyen xe tu bang cu sang `vehicles`');
ok(!preg_match('~GROUP BY v\.`bien_so_chuan`~', $sqlTk),
   'SQL chuyen xe KHONG dung GROUP BY cot khong gop', 'ONLY_FULL_GROUP_BY (mac dinh MySQL 5.7+) bao loi');

/* Phần 19 — 000078 */
ok(strpos($sqlTk, "'2026_09_22_000078_ban_hang_theo_gara'") !== false, 'SQL danh dau da chay migration 000078');
foreach (['uq_quote_gara_no', 'uq_invoice_gara_no', 'uq_parts_gara_code'] as $ix){
    ok(strpos($sqlTk, "ADD UNIQUE KEY `$ix`") !== false, "SQL them chi muc khong trung THEO GARA: $ix");
}

// ---------------------------------------------------------------------------
section('Gara lam viec — theo tai khoan, khong doi duoc');

$g = gara_hien_tai();
ok(!empty($g) && (int) $g['id'] === $TP, 'Dong lenh: gara lam viec la gara tong');
ok(function_exists('la_gara_tong') && la_gara_tong(), 'la_gara_tong() dung o dong lenh');

/* Không một file nào trong app/ còn đọc / ghi session `garage_id`: ô đổi gara cũ
   ghi vào đó, và phiên nào còn sót giá trị ấy thì vẫn "đổi" được gara. */
$conSession = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($goc . 'app', FilesystemIterator::SKIP_DOTS));
foreach ($rii as $f){
    if ($f->getExtension() !== 'php') continue;
    if (preg_match("~Session::(get|set)\(\s*'garage_id'~", codeOnly($f->getPathname()))) $conSession[] = $f->getFilename();
}
ok(empty($conSession), 'Khong con cho nao doc / ghi session garage_id', implode(', ', $conSession));
ok(strpos(file_get_contents($goc . 'routes/web.php'), 'garages/doi') === false, 'Khong con route doi gara');
ok(!preg_match('~function\s+doi\s*\(~', codeOnly($goc . 'app/controllers/admin/Garages.php')),
   'Controller Garages khong con ham doi()');
ok(strpos(file_get_contents($goc . 'app/views/layouts/admin/header.php'), 'garages/doi') === false,
   'Dau trang khong con o doi gara');
ok(strpos(codeOnly($goc . 'app/providers/AppServiceProvider.php'), 'dsGara') === false,
   'Khong con chia se danh sach gara de doi');

// ---------------------------------------------------------------------------
section('Lop Model goc — chan theo gara');

/* Model thử trên bảng `warehouses` (đã có garage_id). Bước 1 chưa bật cờ cho
   model thật nào, nên thử cơ chế bằng một lớp tạm. */
$khoModel = new class extends \App\core\Model {
    protected $_table = 'warehouses'; protected $_fields = '*'; protected $_primary = 'id';
    protected $_theoGara = true;
};
$pdo->prepare("INSERT INTO warehouses (code, name, is_default, sort_order, status, garage_id, create_at)
               VALUES ('ZZCL-KHOA', 'ZZ Kho A', 0, 99, 1, ?, NOW())")->execute([$GA]);
$khoA = (int) $pdo->lastInsertId();
$khoB = $so("SELECT id FROM warehouses WHERE code = 'ZZCL-KHOB'");
$tenKho = function($id) use ($mot){ return $mot("SELECT name, garage_id FROM warehouses WHERE id = ?", [$id]); };

ok(method_exists('\App\core\Model', 'epGara'), 'Model co epGara() de test / cong cu dong lenh ep gara');
if (method_exists('\App\core\Model', 'epGara')){
    \App\core\Model::epGara($GB);

    ok(empty($khoModel->getFirst($khoA)), 'getFirst() theo ID cua gara A tu gara B: KHONG thay');
    ok(!empty($khoModel->getFirst($khoB)), 'getFirst() ID cua chinh gara B: thay');
    $ma = array_column($khoModel->getList(), 'code');
    ok(in_array('ZZCL-KHOB', $ma, true) && !in_array('ZZCL-KHOA', $ma, true), 'getList() chi ra kho cua gara B');
    $ma = array_column($khoModel->getList('`code` LIKE ?', ['ZZCL-%']), 'code');
    ok($ma === ['ZZCL-KHOB'], 'getList() co dieu kien rieng: van ghep dieu kien gara (dung thu tu bind)', json_encode($ma));
    $ma = array_column($khoModel->getLimit(1000), 'code');
    ok(in_array('ZZCL-KHOB', $ma, true) && !in_array('ZZCL-KHOA', $ma, true), 'getLimit() chi ra kho cua gara B');

    $khoModel->updateById(['name' => 'ZZ bi sua'], $khoA);
    ok($tenKho($khoA)['name'] === 'ZZ Kho A', 'updateById() vao ID cua gara A: KHONG sua duoc');
    $khoModel->deleteById($khoA);
    ok(!empty($tenKho($khoA)), 'deleteById() vao ID cua gara A: KHONG xoa duoc');

    $khoModel->updateById(['garage_id' => $GA, 'name' => 'ZZ Kho B2'], $khoB);
    $b = $tenKho($khoB);
    ok($b['name'] === 'ZZ Kho B2' && (int) $b['garage_id'] === $GB,
       'updateById() khong chuyen duoc du lieu sang gara khac (bo garage_id tren form)');

    $khoModel->addNew(['code' => 'ZZCL-KHOM', 'name' => 'ZZ Kho moi', 'is_default' => 0, 'sort_order' => 99,
                       'status' => 1, 'garage_id' => $GA, 'create_at' => date('Y-m-d H:i:s')]);
    $moi = $mot("SELECT garage_id FROM warehouses WHERE code = 'ZZCL-KHOM'");
    ok(!empty($moi) && (int) $moi['garage_id'] === $GB, 'addNew() luon ghi gara lam viec, bo qua garage_id form gui len');

    ok($khoModel->dkGara('w') === ['`w`.`garage_id` = ?', [$GB]], 'dkGara(alias) cho truy van tu viet');

    \App\core\Model::epGara(0);   // giả lập web không xác định được gara
    ok(empty($khoModel->getFirst($khoB)) && $khoModel->getList() === [], 'Khong xac dinh duoc gara: KHONG tra dong nao');
    $nem = false;
    try { $khoModel->addNew(['code' => 'ZZCL-KHOX', 'name' => 'x']); } catch (\RuntimeException $e){ $nem = true; }
    ok($nem, 'Khong xac dinh duoc gara: addNew() tu choi ghi');
    ok($khoModel->dkGara() === ['1 = 0', []], 'dkGara() dong khi khong xac dinh duoc gara');

    \App\core\Model::epGara(null);   // dòng lệnh chưa ép: migrate, gieo dữ liệu, xuất SQL
    ok(!empty($khoModel->getFirst($khoA)), 'Dong lenh chua ep gara: khong chan (migrate / cong cu van chay)');
    ok($khoModel->dkGara('w') === ['1 = 1', []], 'dkGara() khong loc khi dong lenh chua ep gara');

    $thuong = new class extends \App\core\Model {
        protected $_table = 'warehouses'; protected $_fields = '*'; protected $_primary = 'id';
    };
    \App\core\Model::epGara($GB);
    ok(!empty($thuong->getFirst($khoA)), 'Model KHONG bat _theoGara thi khong bi anh huong');
    \App\core\Model::epGara(null);
}

// ---------------------------------------------------------------------------
section('Man rieng gara — danh sach phai du');

/* Mọi module KHÔNG có cờ chi_tan_phat là màn riêng của gara. Số bên phải là
   bước thi công sẽ phủ màn đó vào test dò rò rỉ. Thêm module mới mà quên ghi
   vào đây là đỏ — không thì màn mới lọt khỏi test cách ly. */
$manRiengGara = [
    'bao-cao-ban-hang' => 5, 'bao-cao-cskh' => 5, 'bien-dong-ton' => 4, 'customer-groups' => 2,
    'customers' => 2, 'garage-catalog' => 3, 'goods-issues' => 4, 'goods-receipts' => 4,
    'lich-bao-hanh' => 2, 'nhac-bao-tri' => 2, 'partners' => 2, 'quotations' => 3, 'receptions' => 2,
    'sales-invoices' => 3, 'stock-takes' => 4, 'the-kho' => 4, 'ton-kho' => 4, 'ton-kho-lau' => 4,
    'transfers' => 4, 'users' => 5, 'vehicles' => 2, 'warehouse-locations' => 4, 'warehouses' => 4,
    'warranty' => 2,
];
if (in_array('chi_tan_phat', $cot('modules'), true)){
    $that = $pdo->query("SELECT link FROM modules WHERE chi_tan_phat = 0")->fetchAll(PDO::FETCH_COLUMN);
    $ds   = array_keys($manRiengGara);
    sort($that); sort($ds);
    ok($that === $ds, 'Moi man rieng gara deu co ten trong danh sach test',
       'Chua co trong test: ' . implode(',', array_diff($that, $ds)) . ' | Khong con la man gara: ' . implode(',', array_diff($ds, $that)));
}

// ---------------------------------------------------------------------------
section('Cho quen — bang co garage_id thi model phai bat _theoGara');

/* Bảng có cột `garage_id` mà model của nó chưa bật cờ là bảng KHÔNG được chặn.
   `$chuaLam` ghi bước sẽ bật; bật rồi thì phải xoá khỏi danh sách (test bắt cả
   chiều đó), nên danh sách chỉ ngắn dần. */
$ngoaiLe = ['users' => 'dang nhap tim khap cac gara; chan tay o Users::phamVi',
            'parts' => 'NULL = kho tong, loc bang dieu kien rieng'];
$chuaLam = [
    'goods_receipts' => 4, 'goods_issues' => 4, 'stock_takes' => 4, 'warehouse_transfers' => 4,
];
$modelCua = [];
foreach (glob($goc . 'app/models/*.php') as $f){
    $src = codeOnly($f);
    if (preg_match('~\$_table\s*=\s*[\'"]([a-z_]+)[\'"]~', $src, $mm)){
        $modelCua[$mm[1]][] = ['file' => basename($f), 'bat' => (bool) preg_match('~\$_theoGara\s*=\s*true~', $src)];
    }
}
$bangCoGara = $pdo->query("SELECT TABLE_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
                           AND COLUMN_NAME = 'garage_id' ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_COLUMN);
foreach ($bangCoGara as $b){
    if (isset($ngoaiLe[$b]) || $b === 'garages') continue;
    $ms  = isset($modelCua[$b]) ? $modelCua[$b] : [];
    $bat = !empty($ms) && !in_array(false, array_column($ms, 'bat'), true);
    if (isset($chuaLam[$b])){
        ok(!$bat, "`$b` chua bat _theoGara (buoc {$chuaLam[$b]}) — bat roi thi xoa khoi \$chuaLam");
        continue;
    }
    ok($bat, "Model cua `$b` bat _theoGara",
       empty($ms) ? 'Khong tim thay model nao co $_table = ' . $b : 'Chua bat: ' . implode(', ', array_column($ms, 'file')));
}

// ==== [CLI] ====

// ==== [HTTP] ====

// ---------------------------------------------------------------------------
section('HTTP that — tai khoan tam');

if (!function_exists('curl_init')){ echo "\n[SKIP] PHP khong co curl.\n"; exit(summary()); }

$MK   = 'ZzCachLy#2026';
$hash = \App\core\Hash::make($MK);
$nhom = function($ten) use ($so){ return $so("SELECT id FROM `groups` WHERE name = ?", [$ten]); };
$A = $nhom('Admin'); $M = $nhom('Manager'); $S = $nhom('Staff');
if (!$A || !$M || !$S){ echo "\n[SKIP] Thieu nhom Admin/Manager/Staff.\n"; exit(summary()); }

$taoUser = function($email, $ten, $nhomId, $gara) use ($pdo, $hash){
    $pdo->prepare("INSERT INTO users (name, email, password, group_id, status, garage_id, create_at)
                   VALUES (?, ?, ?, ?, 1, ?, NOW())")->execute([$ten, $email, $hash, $nhomId, $gara]);
    return (int) $pdo->lastInsertId();
};
$taoUser('zz-cl-b@local.test',     'ZZ Quan ly B',     $M, $GB);
$taoUser('zz-cl-tp@local.test',    'ZZ Quan ly TP',    $M, $TP);
$taoUser('zz-cl-ad@local.test',    'ZZ Admin TP',      $A, $TP);
$taoUser('zz-cl-trong@local.test', 'ZZ Chua gan gara', $S, null);
$taoUser('zz-cl-khoa@local.test',  'ZZ Gara bi khoa',  $S, $GK);

$http = function($method, $url, $jar, $data = null){
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 20,
    ]);
    if ($method === 'POST'){
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $raw  = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    $body = $raw === false ? '' : substr($raw, (int) $info['header_size']);
    /* `text` = HTML đã giải mã: {{ }} của Template mã hoá chữ có dấu thành
       entity (á -> &aacute;), so chuỗi tiếng Việt trên `body` thô là trượt. */
    return ['code' => (int) $info['http_code'], 'loc' => (string) $info['redirect_url'],
            'body' => $body, 'text' => html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8')];
};
$token = function($html){ return preg_match('~name="_token" value="([^"]+)"~', $html, $m) ? $m[1] : ''; };
$jars  = [];
$dangNhap = function($email) use ($http, $token, $base, $MK, &$jars){
    $jar = tempnam(sys_get_temp_dir(), 'zzcl');
    $jars[] = $jar;
    $r = $http('GET', "$base/dang-nhap", $jar);
    if ($r['code'] === 0) return null;
    $tk = $token($r['body']);
    $http('POST', "$base/dang-nhap", $jar, ['email' => $email, 'password' => $MK, '_token' => $tk]);
    return [$jar, $tk];
};
register_shutdown_function(function() use (&$jars){ foreach ($jars as $j) @unlink($j); });
$dauTrang = function($r){ return preg_match('~<header class="adm-topbar">.*?</header>~s', $r['text'], $m) ? $m[0] : ''; };
$menuTrai = function($r){ return preg_match('~<aside class="adm-sidebar">.*?</aside>~s', $r['body'], $m) ? $m[0] : ''; };

$phien = $dangNhap('zz-cl-b@local.test');
if ($phien === null){ echo "\n[SKIP] Apache khong chay (localhost:88).\n"; exit(summary()); }
list($jarB) = $phien;

// ---------------------------------------------------------------------------
section('HTTP — gara lam viec');

$r = $http('GET', "$base/admin", $jarB);
ok($r['code'] === 200, 'Tai khoan gara B vao duoc trang quan tri (HTTP ' . $r['code'] . ')', $r['loc']);
ok(strpos($dauTrang($r), 'ZZ Gara B') !== false, 'Dau trang hien ten gara cua tai khoan');
ok(strpos($r['body'], 'garages/doi') === false, 'Trang KHONG co link doi gara');

$http('GET', "$base/admin/garages/doi/$GA", $jarB);
$r = $http('GET', "$base/admin", $jarB);
ok(strpos($dauTrang($r), 'ZZ Gara B') !== false && strpos($dauTrang($r), 'ZZ Gara A') === false,
   'Go thang URL doi gara cu van KHONG sang duoc gara A');

// ---------------------------------------------------------------------------
section('HTTP — man chi Tan Phat');

$r  = $http('GET', "$base/admin", $jarB);
$mn = $menuTrai($r);
ok(strpos($mn, "$base/admin/quotations\"") !== false, 'Menu gara B co Bao gia');
ok(strpos($mn, "$base/admin/customers\"") !== false, 'Menu gara B co Khach hang');
/* Chọn đúng những màn nhóm Manager ĐANG có quyền xem — không phải màn mà
   Manager vốn không có quyền, kẻo test xanh vì lý do khác. */
foreach (['orders', 'garages', 'products', 'reviews', 'part-categories', 'chat', 'contact-messages'] as $l){
    ok(strpos($mn, "$base/admin/$l\"") === false, "Menu gara B KHONG co `$l` (chi Tan Phat)");
}
foreach (['orders', 'garages', 'products'] as $l){
    $r = $http('GET', "$base/admin/$l", $jarB);
    ok($r['code'] === 302 && strpos($r['loc'], 'khong-co-quyen') !== false,
       "Go thang /admin/$l tu gara B bi chan", 'HTTP ' . $r['code'] . ' ' . $r['loc']);
}
$r = $http('GET', "$base/admin/garages/edit/$GA", $jarB);
ok($r['code'] === 302 && strpos($r['loc'], 'khong-co-quyen') !== false, 'Gara B KHONG mo duoc trang sua gara A');

list($jarTP) = $dangNhap('zz-cl-tp@local.test');
$mn = $menuTrai($http('GET', "$base/admin", $jarTP));
ok(strpos($mn, "$base/admin/orders\"") !== false, 'Manager cua Tan Phat VAN thay Don hang web');
ok($http('GET', "$base/admin/orders", $jarTP)['code'] === 200, 'Manager cua Tan Phat VAN mo duoc Don hang web');

// ---------------------------------------------------------------------------
section('HTTP — buoc 2: khach, xe, phieu cua gara A khong lot sang gara B');

$bayGio = date('Y-m-d H:i:s');
$homNay = date('Y-m-d');
$ins = function($bang, array $d) use ($pdo){
    $c = array_keys($d);
    $pdo->prepare("INSERT INTO `$bang` (`" . implode('`, `', $c) . "`) VALUES (" . implode(', ', array_fill(0, count($c), '?')) . ")")
        ->execute(array_values($d));
    return (int) $pdo->lastInsertId();
};

/* Một bộ dữ liệu ĐỦ LOẠI của gara A, mỗi thứ mang dấu nhận biết riêng */
$nhomA = $ins('customer_groups', ['name' => 'ZZ Nhom cua A', 'discount_percent' => 5, 'status' => 1,
                                  'garage_id' => $GA, 'create_at' => $bayGio]);
$khA   = $ins('partners', ['code' => 'ZZCL-KHA', 'name' => 'ZZ Khach cua A', 'type' => 'customer', 'phone' => '0911000111',
                           'group_id' => $nhomA, 'status' => 1, 'garage_id' => $GA, 'create_at' => $bayGio]);
$xeA   = $ins('vehicles', ['partner_id' => $khA, 'bien_so' => 'ZZ9-111.11', 'bien_so_chuan' => 'ZZ911111',
                           'so_khung' => 'ZZVINGARAA000001', 'so_km' => 1000, 'status' => 1,
                           'garage_id' => $GA, 'create_at' => $bayGio]);
$tnA   = $ins('receptions', ['reception_no' => 'ZZCL-TN-A', 'vehicle_id' => $xeA, 'partner_id' => $khA,
                             'ngay_vao' => $homNay, 'status' => 'tiep_nhan', 'garage_id' => $GA, 'create_at' => $bayGio]);
$ngayCu = date('Y-m-d', strtotime('-7 months'));
$btA   = $ins('warranty_requests', ['request_no' => 'ZZCL-BT-A', 'loai' => 'bao_tri', 'partner_id' => $khA,
                                    'customer_name' => 'ZZ Khach cua A', 'vehicle_id' => $xeA, 'bien_so' => 'ZZ9-111.11',
                                    'bien_so_chuan' => 'ZZ911111', 'so_km' => 1000, 'received_date' => $ngayCu,
                                    'completed_date' => $ngayCu, 'status' => 'done', 'garage_id' => $GA, 'create_at' => $bayGio]);
$bbA   = $ins('warranty_handovers', ['handover_no' => 'ZZCL-BB-A', 'warranty_id' => $btA, 'type' => 'receive',
                                     'handover_date' => $homNay, 'garage_id' => $GA, 'create_at' => $bayGio]);

$dauA   = ['ZZ Khach cua A', 'ZZ Nhom cua A', 'ZZ9-111.11', 'ZZVINGARAA000001', 'ZZCL-TN-A', 'ZZCL-BT-A', 'ZZCL-BB-A', '0911000111'];
/* $boQua: từ khoá người dùng vừa gõ — ô tìm kiếm in lại nó, đó không phải lộ */
$loDauA = function($r, $boQua = '') use ($dauA){
    $ra = [];
    foreach ($dauA as $d) if ($d !== $boQua && strpos($r['text'], $d) !== false) $ra[] = $d;
    return $ra;
};

/* 1. Danh sách + tìm kiếm */
foreach (['partners', 'customers', 'customers?q=ZZ9-111.11', 'customers?q=0911000111', 'customer-groups',
          'vehicles', 'vehicles?q=ZZ9', 'receptions', 'receptions?q=ZZ9', 'warranty', 'warranty?q=ZZ9',
          'lich-bao-hanh', 'nhac-bao-tri?mode=all'] as $url){
    $r  = $http('GET', "$base/admin/$url", $jarB);
    $tu = preg_match('~[?&]q=([^&]*)~', $url, $mq) ? urldecode($mq[1]) : '';
    ok($r['code'] === 200 && $loDauA($r, $tu) === [], "Gara B mo /admin/$url: khong lo du lieu gara A",
       'HTTP ' . $r['code'] . ' ' . $r['loc'] . ' | lo: ' . implode(', ', $loDauA($r, $tu)));
}

/* 2. Mở theo ID của gara A (sửa, in, lập từ) */
foreach (["partners/edit/$khA", "customers/edit/$khA", "customer-groups/edit/$nhomA", "vehicles/edit/$xeA",
          "receptions/edit/$tnA", "warranty/edit/$btA", "warranty/handover-add/$btA", "warranty/handover-print/$bbA",
          "receptions/add?vehicle_id=$xeA", "warranty/add?tu=$btA", "warranty/add?reception_id=$tnA"] as $url){
    $r = $http('GET', "$base/admin/$url", $jarB);
    ok($loDauA($r) === [], "Gara B mo /admin/$url (ID cua gara A): khong lo gi",
       'HTTP ' . $r['code'] . ' | lo: ' . implode(', ', $loDauA($r)));
}

/* 3. Sửa / xoá / đổi trạng thái theo ID của gara A — dữ liệu A phải KHÔNG đổi */
$tkB  = $token($http('GET', "$base/admin/customers/add", $jarB)['body']);
$post = function($url, array $d) use ($http, $base, $jarB, &$tkB){
    return $http('POST', "$base/admin/$url", $jarB, array_merge(['_token' => $tkB], $d));
};
$get  = function($url) use ($http, $base, $jarB){ return $http('GET', "$base/admin/$url", $jarB); };
$cot1 = function($bang, $c, $id) use ($mot){ $r = $mot("SELECT `$c` FROM `$bang` WHERE id = ?", [$id]); return $r ? $r[$c] : null; };

$post("partners/edit/$khA", ['code' => 'ZZCL-KHA', 'name' => 'ZZ bi gara B sua', 'type' => 'customer', 'status' => 1]);
$post("customers/edit/$khA", ['name' => 'ZZ bi gara B sua', 'phone' => '0911000111']);
ok($cot1('partners', 'name', $khA) === 'ZZ Khach cua A', 'POST sua khach cua gara A (Doi tuong + Khach hang): KHONG sua duoc');
$get("customers/toggle/$khA");
ok((int) $cot1('partners', 'status', $khA) === 1, 'Tat khach cua gara A tu gara B: KHONG duoc');
$get("partners/delete/$khA");
ok($cot1('partners', 'id', $khA) !== null, 'Xoa khach cua gara A tu gara B: KHONG xoa duoc');
$post("customer-groups/edit/$nhomA", ['name' => 'ZZ bi sua', 'status' => 1]);
$get("customer-groups/delete/$nhomA");
ok($cot1('customer_groups', 'name', $nhomA) === 'ZZ Nhom cua A', 'Sua / xoa nhom khach cua gara A: KHONG duoc');
$post("vehicles/edit/$xeA", ['bien_so' => 'ZZ9-111.11', 'so_km' => 999999, 'status' => 1]);
$get("vehicles/delete/$xeA");
ok((int) $cot1('vehicles', 'so_km', $xeA) === 1000, 'Sua / xoa xe cua gara A tu gara B: KHONG duoc');
$get("receptions/set-status/$tnA?status=huy");
$get("receptions/delete/$tnA");
ok($cot1('receptions', 'status', $tnA) === 'tiep_nhan', 'Doi trang thai / xoa phieu tiep nhan cua gara A: KHONG duoc');
$get("warranty/set-status/$btA?status=cancelled");
$get("warranty/delete/$btA");
ok($cot1('warranty_requests', 'status', $btA) === 'done', 'Doi trang thai / xoa phieu bao tri cua gara A: KHONG duoc');
$post("warranty/handover-store/$btA", ['type' => 'return', 'handover_date' => $homNay]);
$get("warranty/handover-delete/$bbA");
ok($so("SELECT COUNT(*) FROM warranty_handovers WHERE warranty_id = ?", [$btA]) === 1,
   'Lap / xoa bien ban giao nhan tren phieu cua gara A: KHONG duoc');
$get("nhac-bao-tri/mark/$btA");
ok($cot1('warranty_requests', 'reminded_at', $btA) === null, 'Danh dau "da nhac" phieu cua gara A: KHONG duoc');

/* 4. Lưu chứng từ của B kèm ID của A — phải bị từ chối / không nối vào */
$post('vehicles/add', ['partner_id' => $khA, 'bien_so' => 'ZZ8-222.22', 'status' => 1]);
ok($so("SELECT COUNT(*) FROM vehicles WHERE bien_so_chuan = 'ZZ822222'") === 0, 'Gara B khai xe gan cho khach cua gara A: bi tu choi');
$post('receptions/add', ['vehicle_id' => $xeA, 'ngay_vao' => $homNay, 'status' => 'tiep_nhan']);
ok($so("SELECT COUNT(*) FROM receptions WHERE vehicle_id = ? AND garage_id = ?", [$xeA, $GB]) === 0,
   'Gara B lap phieu tiep nhan cho xe cua gara A: bi tu choi');
$post('warranty/add', ['loai' => 'bao_hanh', 'reception_id' => $tnA, 'partner_id' => $khA, 'customer_name' => 'ZZ Khach le B',
                       'received_date' => $homNay, 'product_name' => 'ZZ may rua xe']);
$w = $mot("SELECT * FROM warranty_requests WHERE customer_name = 'ZZ Khach le B'");
ok(!empty($w) && (int) $w['garage_id'] === $GB && $w['reception_id'] === null && $w['partner_id'] === null && $w['vehicle_id'] === null,
   'Phieu bao hanh cua gara B KHONG noi duoc vao phieu tiep nhan / khach / xe cua gara A', json_encode($w));
$post('customers/add', ['name' => 'ZZ Khach xep nhom A', 'phone' => '0933000333', 'group_id' => $nhomA]);
ok($so("SELECT COUNT(*) FROM partners WHERE name = 'ZZ Khach xep nhom A'") === 0, 'Gara B xep khach vao nhom khach cua gara A: bi tu choi');

/* 5. Mỗi gara tự đánh số, tự có "không trùng" riêng */
$post('customers/add', ['name' => 'ZZ Khach moi B', 'phone' => '0922000222']);
$khB = $mot("SELECT * FROM partners WHERE name = 'ZZ Khach moi B' AND garage_id = ?", [$GB]);
ok(!empty($khB) && (int) $khB['garage_id'] === $GB && $khB['code'] === 'KH-0001',
   'Khach dau tien cua gara B: ma KH-0001 (danh so rieng tung gara)', json_encode($khB));
$post('customers/add', ['name' => 'ZZ Khach trung sdt A', 'phone' => '0911000111']);
ok($so("SELECT COUNT(*) FROM partners WHERE name = 'ZZ Khach trung sdt A' AND garage_id = ?", [$GB]) === 1,
   'Trung SDT voi khach cua gara A: KHONG canh bao (khac gara, khong duoc lo ten khach A)');
$khBId = !empty($khB) ? (int) $khB['id'] : 0;
$r = $post('vehicles/add', ['partner_id' => $khBId, 'bien_so' => 'ZZ9-111.11', 'so_khung' => 'ZZVINGARAA000001',
                            'status' => 1, 've' => 'customer']);
$xeB = $mot("SELECT * FROM vehicles WHERE bien_so_chuan = 'ZZ911111' AND garage_id = ?", [$GB]);
ok(!empty($xeB) && (int) $xeB['partner_id'] === $khBId,
   'Gara B khai duoc xe TRUNG bien so + so khung voi xe cua gara A (hai ho so rieng)');
ok(strpos($r['loc'], "admin/customers/edit/$khBId") !== false, 'Khai xe tu man Khach hang xong quay ve dung ho so khach', $r['loc']);
$xeBId = !empty($xeB) ? (int) $xeB['id'] : 0;
$post('receptions/add', ['vehicle_id' => $xeBId, 'ngay_vao' => $homNay, 'status' => 'tiep_nhan', 'km_vao' => 500]);
$tnB = $mot("SELECT * FROM receptions WHERE vehicle_id = ?", [$xeBId]);
ok(!empty($tnB) && $tnB['reception_no'] === 'TN-000001' && (int) $tnB['garage_id'] === $GB,
   'Phieu tiep nhan dau tien cua gara B: TN-000001 (khong noi tiep so cua gara khac)', json_encode($tnB));

/* 6. Chuỗi khách -> xe -> phiếu ngay trên màn Khách hàng */
$r = $get("customers/edit/$khBId");
ok(strpos($r['text'], 'ZZ9-111.11') !== false && strpos($r['text'], 'ZZVINGARAA000001') !== false,
   'Ho so khach hien xe kem bien so + so khung (VIN)');
ok(strpos($r['body'], 'receptions/add?vehicle_id=' . $xeBId) !== false, 'Tu ho so khach lap duoc phieu tiep nhan cho tung xe');
ok(strpos($get('customers?q=ZZVINGARAA000001')['text'], 'ZZ Khach moi B') !== false, 'Tim khach theo SO KHUNG (VIN)');
ok(strpos($get('customers?q=' . rawurlencode('zz9 11111'))['text'], 'ZZ Khach moi B') !== false, 'Tim khach theo bien so go kieu khac');
ok(strpos($get('partners?q=' . rawurlencode('ZZ Khach moi B'))['text'], 'ZZ Khach moi B') !== false,
   'Khach them o man Khach hang hien ngay o man Doi tuong (cung mot ban ghi)');

/* 7. Tài khoản website là của Tân Phát */
$r = $get('tai-khoan-web');
ok($r['code'] === 302 && strpos($r['loc'], 'khong-co-quyen') !== false, 'Gara B KHONG vao duoc man Tai khoan website');
ok($http('GET', "$base/admin/tai-khoan-web", $jarTP)['code'] === 200, 'Tan Phat vao duoc man Tai khoan website');

/* 8. Chu kỳ bảo trì là cấu hình RIÊNG của gara */
$truoc = $pdo->query("SELECT svalue FROM site_settings WHERE skey = 'maintenance_interval_km'")->fetchColumn();
$tkNhac = $token($get('nhac-bao-tri')['body']) ?: $tkB;
$http('POST', "$base/admin/nhac-bao-tri/save-config", $jarB, ['_token' => $tkNhac, 'interval' => 3, 'km' => 1234, 'window' => 9]);
ok($pdo->query("SELECT svalue FROM site_settings WHERE skey = 'maintenance_interval_km'")->fetchColumn() === $truoc,
   'Gara B luu chu ky bao tri: KHONG doi Cau hinh chung (cua Tan Phat)');
ok($so("SELECT COUNT(*) FROM garage_settings WHERE garage_id = ? AND skey = 'maintenance_interval_km' AND svalue = '1234'", [$GB]) === 1,
   'Chu ky luu vao cau hinh RIENG cua gara B');

// ---------------------------------------------------------------------------
section('HTTP — buoc 3: bao gia, hoa don, danh muc cua gara A khong lot sang gara B');

$ptTong = $so("SELECT id FROM parts WHERE garage_id IS NULL AND status = 1 AND item_type <> 'service' ORDER BY id LIMIT 1");
$tenTong = (string) ($mot("SELECT name FROM parts WHERE id = ?", [$ptTong])['name'] ?? '');
$khoAId = $so("SELECT id FROM warehouses WHERE code = 'ZZCL-KHOA'");
$khoBId = $so("SELECT id FROM warehouses WHERE code = 'ZZCL-KHOB'");
$hrA = $ins('parts', ['code' => 'ZZCL-HRA', 'name' => 'ZZ Hang rieng A', 'slug' => 'zzcl-hang-rieng-a', 'item_type' => 'part',
                      'price' => 777000, 'status' => 1, 'show_on_web' => 0, 'garage_id' => $GA, 'create_at' => $bayGio]);
$ins('garage_part_prices', ['garage_id' => $GA, 'part_id' => $ptTong, 'price' => 654321, 'status' => 1, 'create_at' => $bayGio]);
$bgA = $ins('quotations', ['quote_no' => 'ZZCL-BG-A', 'customer_id' => $khA, 'quote_date' => $homNay, 'status' => 'draft',
                           'subtotal' => 111000, 'total_amount' => 111000, 'garage_id' => $GA, 'create_at' => $bayGio]);
$ins('quotation_items', ['quotation_id' => $bgA, 'part_id' => $hrA, 'quantity' => 1, 'unit_price' => 111000, 'amount' => 111000]);
$hdA = $ins('sales_invoices', ['invoice_no' => 'ZZCL-HD-A', 'customer_id' => $khA, 'warehouse_id' => $khoAId, 'invoice_date' => $homNay,
                               'status' => 0, 'subtotal' => 222000, 'total_amount' => 222000, 'garage_id' => $GA, 'create_at' => $bayGio]);
$ins('sales_invoice_items', ['invoice_id' => $hdA, 'part_id' => $hrA, 'quantity' => 1, 'unit_price' => 222000, 'amount' => 222000]);

$dau3   = ['ZZCL-BG-A', 'ZZCL-HD-A', 'ZZ Hang rieng A', 'ZZCL-HRA', '654.321', '654321', 'ZZ Kho A', 'ZZ Khach cua A'];
$loDau3 = function($r) use ($dau3){ $ra = []; foreach ($dau3 as $d) if (strpos($r['text'], $d) !== false) $ra[] = $d; return $ra; };

/* 1. Danh sách, form lập (ô chọn hàng / kho / khách), JSON chép dòng */
foreach (['quotations', 'sales-invoices', 'garage-catalog', 'quotations/add', 'sales-invoices/add', 'warranty/add',
          'quotations/copy-list', 'sales-invoices/copy-list?tu=hoadon', 'sales-invoices/copy-list?tu=baogia'] as $url){
    $r = $get($url);
    ok($r['code'] === 200 && $loDau3($r) === [], "Gara B mo /admin/$url: khong lo bao gia / hoa don / hang rieng / gia rieng / kho cua A",
       'HTTP ' . $r['code'] . ' ' . $r['loc'] . ' | lo: ' . implode(', ', $loDau3($r)));
}

/* 2. Theo ID của gara A: sửa, in, XML hoá đơn điện tử, chép dòng */
foreach (["quotations/edit/$bgA", "quotations/print/$bgA", "sales-invoices/edit/$hdA", "sales-invoices/print/$hdA",
          "sales-invoices/einvoice-xml/$hdA", "quotations/copy-lines/$bgA", "sales-invoices/copy-lines/$hdA?tu=hoadon",
          "sales-invoices/copy-lines/$bgA?tu=baogia"] as $url){
    $r = $get($url);
    ok($loDau3($r) === [], "Gara B mo /admin/$url (ID cua gara A): khong lo gi", 'HTTP ' . $r['code'] . ' | lo: ' . implode(', ', $loDau3($r)));
}

/* 3. Thao tác theo ID của A — dữ liệu A không đổi */
$get("quotations/set-status/$bgA?status=sent");
$get("quotations/convert/$bgA");
$get("quotations/delete/$bgA");
ok($cot1('quotations', 'status', $bgA) === 'draft' && $so("SELECT COUNT(*) FROM sales_invoices WHERE quotation_id = ?", [$bgA]) === 0,
   'Doi trang thai / chuyen hoa don / xoa bao gia cua gara A tu gara B: KHONG duoc');
$get("sales-invoices/post/$hdA");
$get("sales-invoices/delete/$hdA");
ok((int) $cot1('sales_invoices', 'status', $hdA) === 0, 'Ghi so / xoa hoa don cua gara A tu gara B: KHONG duoc');
$get("garage-catalog/xoa-rieng/$hrA");
$post('garage-catalog/chon', ['co_mat' => [$ptTong]]);   // "bỏ tick" hàng tổng — chỉ được đụng bảng giá của B
ok($cot1('parts', 'id', $hrA) !== null
   && $so("SELECT COUNT(*) FROM garage_part_prices WHERE garage_id = ? AND part_id = ?", [$GA, $ptTong]) === 1,
   'Xoa hang rieng / bo chon hang trong danh muc cua gara A tu gara B: KHONG duoc');

/* 4. Lưu chứng từ của B kèm ID của A */
$dongHang = function($pid, $gia) use ($homNay){
    return ['quote_date' => $homNay, 'invoice_date' => $homNay, 'vat_rate' => 0,
            'line_part' => [$pid], 'line_qty' => [1], 'line_price' => [$gia], 'line_disc' => [0], 'line_note' => ['']];
};
$post('quotations/add', $dongHang($hrA, 5000));
ok($so("SELECT COUNT(*) FROM quotation_items i JOIN quotations q ON q.id = i.quotation_id WHERE q.garage_id = ? AND i.part_id = ?", [$GB, $hrA]) === 0,
   'Gara B lap bao gia voi hang rieng cua gara A: bi tu choi');
$post('sales-invoices/add', array_merge($dongHang($ptTong, 5000), ['warehouse_id' => $khoAId]));
ok($so("SELECT COUNT(*) FROM sales_invoices WHERE warehouse_id = ? AND garage_id = ?", [$khoAId, $GB]) === 0,
   'Gara B lap hoa don xuat tu kho cua gara A: bi tu choi');

/* 5. Số chứng từ riêng từng gara */
$post('quotations/add', $dongHang($ptTong, 5000));
$bgB = $mot("SELECT * FROM quotations WHERE garage_id = ? ORDER BY id DESC LIMIT 1", [$GB]);
ok(!empty($bgB) && $bgB['quote_no'] === 'BG-000001', 'Bao gia dau tien cua gara B: BG-000001', json_encode($bgB ? $bgB['quote_no'] : null));
$post('sales-invoices/add', array_merge($dongHang($ptTong, 5000), ['warehouse_id' => $khoBId]));
$hdB = $mot("SELECT * FROM sales_invoices WHERE garage_id = ? ORDER BY id DESC LIMIT 1", [$GB]);
ok(!empty($hdB) && $hdB['invoice_no'] === 'HD-000001' && (int) $hdB['warehouse_id'] === $khoBId,
   'Hoa don dau tien cua gara B: HD-000001, xuat tu kho cua B', json_encode($hdB ? [$hdB['invoice_no'], $hdB['warehouse_id']] : null));

/* 6. Kho tổng = nguồn tham khảo; danh mục của gara B lên được báo giá lẫn hoá đơn */
$post('garage-catalog/them-rieng', ['name' => 'ZZ Dich vu rieng B', 'item_type' => 'service', 'price' => '150000']);
$hrB = $so("SELECT id FROM parts WHERE name = 'ZZ Dich vu rieng B' AND garage_id = ?", [$GB]);
ok($hrB > 0, 'Gara B them duoc dich vu rieng vao danh muc cua minh');
$fBg = $get('quotations/add'); $fHd = $get('sales-invoices/add');
ok($tenTong !== '' && strpos($fBg['text'], $tenTong) !== false, 'Form bao gia cua B co hang KHO TONG de tham khao');
ok(strpos($fBg['text'], 'ZZ Dich vu rieng B') !== false && strpos($fHd['text'], 'ZZ Dich vu rieng B') !== false,
   'Dich vu rieng cua B len duoc CA bao gia lan hoa don', 'Truoc day form hoa don chi co kho tong');
ok(strpos($fBg['text'], 'giá tham khảo') !== false, 'Nguon Kho tong ghi ro la gia tham khao');

/* 7. Phiếu in mang thông tin CỦA GARA B */
$pdo->prepare("UPDATE garages SET tax_code = 'ZZMST-B-0101', address = 'ZZ 12 Duong B', phone = '0299000222' WHERE id = ?")->execute([$GB]);
$cd = $pdo->query("SELECT skey, svalue FROM site_settings WHERE skey IN ('site_name', 'hotline', 'bank_account', 'tax_code')")->fetchAll(PDO::FETCH_KEY_PAIR);
$bgBId = !empty($bgB) ? (int) $bgB['id'] : 0; $hdBId = !empty($hdB) ? (int) $hdB['id'] : 0;
foreach (["quotations/print/$bgBId" => 'bao gia', "sales-invoices/print/$hdBId" => 'hoa don'] as $url => $ten){
    $r = $get($url);
    ok(strpos($r['text'], 'ZZ Gara B') !== false && strpos($r['text'], 'ZZMST-B-0101') !== false && strpos($r['text'], '0299000222') !== false,
       "Phieu in $ten cua gara B: ten, MST, SDT cua gara B");
    $lo = [];
    foreach (['hotline', 'bank_account', 'tax_code'] as $k){ if (!empty($cd[$k]) && strpos($r['text'], $cd[$k]) !== false) $lo[] = $k; }
    ok($lo === [], "Phieu in $ten cua gara B KHONG mang hotline / so tai khoan / MST cua Tan Phat", 'lo: ' . implode(', ', $lo));
}

/* 8. Tân Phát không thấy hàng riêng của gara trong kho tổng */
$r = $http('GET', "$base/admin/products?keyword=" . rawurlencode('ZZ Hang rieng'), $jarTP);
ok($r['code'] === 200 && strpos($r['text'], 'ZZ Hang rieng A') === false, 'Man Hang hoa cua Tan Phat KHONG hien hang rieng cua gara A');

/* 9. Quản lý gara: thông tin in phiếu, gara có dữ liệu chỉ khoá được */
list($jarAD) = $dangNhap('zz-cl-ad@local.test');
$tkAD = $token($http('GET', "$base/admin/garages/edit/$GB", $jarAD)['body']);
$http('POST', "$base/admin/garages/edit/$GB", $jarAD, ['_token' => $tkAD, 'code' => 'ZZGB', 'name' => 'ZZ Gara B', 'status' => 1,
       'address' => 'ZZ 12 Duong B', 'phone' => '0299000222', 'tax_code' => 'ZZMST-B-0202', 'email' => 'zz-gara-b@local.test']);
$gB = $mot("SELECT tax_code, email FROM garages WHERE id = ?", [$GB]);
ok(($gB['tax_code'] ?? '') === 'ZZMST-B-0202' && ($gB['email'] ?? '') === 'zz-gara-b@local.test', 'Quan ly gara luu duoc MST + email cua gara');
$ds = $http('GET', "$base/admin/garages", $jarAD);
ok(strpos($ds['body'], "garages/toggle/$GA") !== false && strpos($ds['body'], "garages/delete/$GA") === false,
   'Gara da co du lieu: co nut Khoa, KHONG co nut Xoa');
$http('GET', "$base/admin/garages/delete/$GA", $jarAD);
ok($so("SELECT COUNT(*) FROM garages WHERE id = ?", [$GA]) === 1, 'Go thang URL xoa gara co du lieu: KHONG xoa duoc');
$http('GET', "$base/admin/garages/toggle/$GA", $jarAD);
ok((int) $cot1('garages', 'status', $GA) === 0, 'Khoa gara A: gara chuyen sang khoa');
$http('GET', "$base/admin/garages/toggle/$GA", $jarAD);
ok((int) $cot1('garages', 'status', $GA) === 1, 'Mo khoa lai gara A');

// ==== [HTTP-2] ====

// ---------------------------------------------------------------------------
section('HTTP — khong co gara thi khong vao duoc');

foreach (['zz-cl-trong@local.test' => 'chua gan gara', 'zz-cl-khoa@local.test' => 'thuoc gara dang khoa'] as $email => $ly){
    list($jar) = $dangNhap($email);
    $r = $http('GET', "$base/admin", $jar);
    ok($r['code'] === 302 && strpos($r['loc'], 'dang-nhap') !== false,
       "Tai khoan $ly KHONG vao duoc trang quan tri", 'HTTP ' . $r['code'] . ' ' . $r['loc']);
    $r = $http('GET', "$base/dang-nhap", $jar);
    ok(strpos($r['text'], 'chưa được gán gara') !== false, "Trang dang nhap bao ro ly do ($ly)");
}

/* Khoá gara GIỮA CHỪNG: phiên đang mở phải bị huỷ ở request kế tiếp */
list($jarB2) = $dangNhap('zz-cl-b@local.test');
ok($http('GET', "$base/admin", $jarB2)['code'] === 200, 'Gara B dang hoat dong: vao duoc');
$pdo->prepare("UPDATE garages SET status = 0 WHERE id = ?")->execute([$GB]);
$r = $http('GET', "$base/admin", $jarB2);
ok($r['code'] === 302 && strpos($r['loc'], 'dang-nhap') !== false,
   'Khoa gara giua chung: bi dua ra trang dang nhap ngay request ke tiep', 'HTTP ' . $r['code'] . ' ' . $r['loc']);
$pdo->prepare("UPDATE garages SET status = 1 WHERE id = ?")->execute([$GB]);
$r = $http('GET', "$base/admin", $jarB2);
ok($r['code'] === 302, 'Mo khoa lai thi phien cu van da bi huy — phai dang nhap lai', 'HTTP ' . $r['code']);

/* Admin tạo tài khoản: bắt buộc chọn gara */
list($jarAD) = $dangNhap('zz-cl-ad@local.test');
$them = function($email, $gara) use ($http, $token, $base, $jarAD, $S, $MK){
    $f = $http('GET', "$base/admin/users/add", $jarAD);   // mở form: xoá flash `old`, lấy token
    return $http('POST', "$base/admin/users/add", $jarAD, [
        '_token' => $token($f['body']), 'name' => 'ZZ Tai khoan moi', 'email' => $email,
        'password' => $MK, 'confirm_password' => $MK, 'group_id' => $S, 'status' => 1, 'garage_id' => $gara,
    ]);
};
$them('zz-cl-moi1@local.test', '');
ok($so("SELECT COUNT(*) FROM users WHERE email = 'zz-cl-moi1@local.test'") === 0,
   'Admin KHONG tao duoc tai khoan khong co gara', 'Tai khoan khong gara thi khong dang nhap duoc — tao ra chi de nam do');
$f = $http('GET', "$base/admin/users/add", $jarAD);
ok(strpos($f['text'], 'Chưa chọn gara') !== false, 'Form bao loi "Chua chon gara"');
$them('zz-cl-moi2@local.test', (string) $GB);
$moi = $mot("SELECT garage_id FROM users WHERE email = 'zz-cl-moi2@local.test'");
ok(!empty($moi) && (int) $moi['garage_id'] === $GB, 'Admin tao tai khoan cho gara B: tai khoan thuoc gara B');

exit(summary());

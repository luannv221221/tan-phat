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
   && substr_count($mm[1], "'") === 2 * 30,
   'SQL bat co cho dung 30 man chi Tan Phat (chep tu may nay)');
ok(strpos($sqlTk, 'UPDATE `goods_receipts` x JOIN `warehouses` w') !== false,
   'SQL gan phieu nhap theo kho truoc khi do ve gara tong');
/* Chỉ bắt câu ĐỔI cột có sẵn thành NOT NULL. `garage_part_prices` tạo mới với
   `garage_id INT NOT NULL` từ 000065 là đúng — bảng đó luôn có gara. */
ok(!preg_match('~MODIFY\s+(COLUMN\s+)?`garage_id`~i', $sqlTk),
   'SQL buoc 1 KHONG doi garage_id sang NOT NULL', 'Dat som la form cua model chua ghi gara sap');

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

<?php
/**
 * Test TÊN MIỀN CHÍNH = <mã>.eteksofts.vn, MẶC ĐỊNH CHO MỌI CÔNG TY.
 *
 * Chạy:  C:\xampp\php\php.exe tests\TenMienChinhMaEteksoftsTest.php
 *
 * Anh hỏi 25/11/2026 (sau khi mở công ty TEST, vào test.eteksofts.vn ra "Không
 * tìm thấy công ty"): "sao em không làm mặc định khi thêm gara thì nó thành tên
 * miền theo dạng mã.eteksofts.vn luôn".
 *
 * Gốc: công ty mở mới nhận `<mã>.<tên miền gốc>`, tên miền gốc suy từ tên CHÍNH
 * của Tân Phát (MoGaraModel::tenMienGoc). Tên chính của Tân Phát vẫn là
 * tp01.etek.rikkeiedu.org (000112 cố ý không đổi, chờ DNS `*`) — nên TEST nhận
 * test.etek.rikkeiedu.org. DNS `*.eteksofts.vn` đã có, đo 25/11: cổng 80 vào tới
 * ứng dụng. Migration 000122 làm luôn bước 6.4 thay anh:
 *   - Tân Phát: tên chính `tp01.eteksofts.vn`;
 *   - mọi công ty: tên chính `<mã>.eteksofts.vn`;
 *   - tên cũ GIỮ NGUYÊN, thành tên phụ — link đã gửi không chết.
 * Từ đó công ty mở mới tự nhận `<mã>.eteksofts.vn` (không phải sửa code gán).
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/MoGaraModel.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

$mg = glob($goc . 'database/migrations/*_000122_*.php');
ok(count($mg) === 1, 'Co migration 000122');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$tong = $pdo->query("SELECT id, code FROM garages WHERE is_master = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$coGoc = (int) $pdo->query("SELECT COUNT(*) FROM garage_domains WHERE garage_id = " . (int) $tong['id']
                           . " AND host = 'eteksofts.vn'")->fetchColumn() > 0;
if (!$coGoc){ echo "\n[SKIP] Cong ty tong chua co eteksofts.vn (chua chay 000112).\n"; exit(summary()); }

$chinhCua = function($gid) use ($pdo){
    $st = $pdo->prepare('SELECT host FROM garage_domains WHERE garage_id = ? AND is_primary = 1');
    $st->execute([(int) $gid]);
    return $st->fetchAll(PDO::FETCH_COLUMN);
};

// ---------------------------------------------------------------------------
section('Sau migration: moi cong ty chinh = <ma>.eteksofts.vn');

$maTong = slugify($tong['code']);
ok($chinhCua($tong['id']) === [$maTong . '.eteksofts.vn'], 'Cong ty tong: ten chinh ' . $maTong . '.eteksofts.vn',
   json_encode($chinhCua($tong['id'])));
ok((new MoGaraModel())->tenMienGoc() === 'eteksofts.vn', 'Ten mien goc cho cong ty mo moi = eteksofts.vn',
   (new MoGaraModel())->tenMienGoc());

$sai = [];
foreach ($pdo->query("SELECT id, code FROM garages WHERE is_master = 0 AND code NOT LIKE 'ZZ%'")->fetchAll(PDO::FETCH_ASSOC) as $g){
    $muon = slugify($g['code']) . '.eteksofts.vn';
    $ai = $pdo->prepare('SELECT garage_id FROM garage_domains WHERE host = ?'); $ai->execute([$muon]);
    $chu = $ai->fetchColumn();
    if ($chu !== false && (int) $chu !== (int) $g['id']) continue;    // tên đã thuộc công ty khác: bỏ qua là đúng
    if ($chinhCua($g['id']) !== [$muon]) $sai[] = $g['code'] . ' -> ' . json_encode($chinhCua($g['id']));
}
ok(empty($sai), 'Moi cong ty: ten chinh <ma>.eteksofts.vn', implode(' | ', $sai));

$st = $pdo->query("SELECT garage_id, COUNT(*) c FROM garage_domains WHERE is_primary = 1 GROUP BY garage_id HAVING c <> 1");
ok($st->fetch() === false, 'Moi cong ty dung MOT ten chinh');

foreach (['tp01.etek.rikkeiedu.org', 'etek.rikkeiedu.org', 'eteksofts.vn', 'tp01.etek.eteksofts.vn'] as $h){
    $c = $pdo->prepare('SELECT status FROM garage_domains WHERE host = ?'); $c->execute([$h]);
    $s = $c->fetchColumn();
    if ($s === false) continue;   // local có thể không có đủ
    ok((int) $s === 1, "Ten cu `$h` van bat (link da gui khong chet)");
}

// ---------------------------------------------------------------------------
section('Chay lai migration khong sinh ban sao');

$truoc = (int) $pdo->query('SELECT COUNT(*) FROM garage_domains')->fetchColumn();
$m = require $mg[0];
$m->setDb(new \App\core\Database());
ob_start(); $loi = ''; try { $m->up(); } catch (\Throwable $e){ $loi = $e->getMessage(); } ob_end_clean();
ok($loi === '', 'Chay lai khong loi', $loi);
ok((int) $pdo->query('SELECT COUNT(*) FROM garage_domains')->fetchColumn() === $truoc, 'Chay lai khong them dong nao');

// ---------------------------------------------------------------------------
section('Cong ty MO MOI tu nhan <ma>.eteksofts.vn (dung cau anh hoi)');

$pdo->exec("DELETE FROM garage_domains WHERE host LIKE 'zztm%'");
foreach ($pdo->query("SELECT id FROM garages WHERE code LIKE 'ZZTM%'")->fetchAll(PDO::FETCH_COLUMN) as $x){
    $x = (int) $x;
    foreach (['garage_domains', 'garage_settings', 'customer_groups', 'warehouses', 'users'] as $b) $pdo->exec("DELETE FROM $b WHERE garage_id = $x");
    foreach ($pdo->query("SELECT id FROM `groups` WHERE garage_id = $x")->fetchAll(PDO::FETCH_COLUMN) as $n) $pdo->exec('DELETE FROM permissions WHERE group_id = ' . (int) $n);
    $pdo->exec("DELETE FROM `groups` WHERE garage_id = $x");
    $pdo->exec("DELETE FROM garages WHERE id = $x");
}
$pdo->prepare("INSERT INTO garages (code, name, is_master, status, sort_order, create_at) VALUES ('ZZTM1', 'ZZ Cong ty moi', 0, 1, 99, NOW())")->execute();
$idMoi = (int) $pdo->lastInsertId();
register_shutdown_function(function() use ($pdo, $idMoi){
    foreach (['garage_domains', 'garage_settings', 'customer_groups', 'warehouses', 'users'] as $b) $pdo->exec("DELETE FROM $b WHERE garage_id = $idMoi");
    foreach ($pdo->query("SELECT id FROM `groups` WHERE garage_id = $idMoi")->fetchAll(PDO::FETCH_COLUMN) as $n) $pdo->exec('DELETE FROM permissions WHERE group_id = ' . (int) $n);
    $pdo->exec("DELETE FROM `groups` WHERE garage_id = $idMoi");
    $pdo->exec("DELETE FROM garages WHERE id = $idMoi");
});
(new MoGaraModel())->dungBoKhung($idMoi, []);
ok($chinhCua($idMoi) === ['zztm1.eteksofts.vn'], 'Cong ty mo moi: ten CHINH zztm1.eteksofts.vn', json_encode($chinhCua($idMoi)));
$phu = $pdo->query("SELECT host FROM garage_domains WHERE garage_id = $idMoi AND is_primary = 0")->fetchAll(PDO::FETCH_COLUMN);
ok(in_array('zztm1.etek.rikkeiedu.org', $phu, true), 'Van kem ten phu kieu cu (zztm1.etek.rikkeiedu.org)', json_encode($phu));

exit(summary());

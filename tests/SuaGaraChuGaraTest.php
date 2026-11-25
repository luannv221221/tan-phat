<?php
/**
 * Test SỬA GARA: tài khoản chủ gara phải thấy và sửa được.
 *
 * Chạy:  C:\xampp\php\php.exe tests\SuaGaraChuGaraTest.php
 *
 * LỖI THẬT 25/11/2026 — anh mở gara "lotus" trên máy chủ, có khai tài khoản chủ
 * gara (họ tên, email, mật khẩu). Mở lại màn Sửa gara thì phần đó biến mất:
 * "tạo mới thì có mà sửa thì không thấy đâu". Tài khoản vẫn được tạo, nhưng
 * không còn chỗ nào trên màn gara cho biết chủ gara đăng nhập bằng email gì,
 * cũng không đặt lại được mật khẩu khi chủ gara quên.
 *
 * Mật khẩu đã mã hoá nên KHÔNG hiện lại được (đúng, không phải lỗi) — màn sửa
 * hiện họ tên + email, và ô "mật khẩu mới" bỏ trống thì giữ nguyên.
 *
 * Kèm theo: ô "Đây là gara tổng" mất ô tích từ 22/09 (b525f98 thay nhầm dòng
 * <input>), chỉ còn cái nhãn. Lưu gara tổng thì luôn hiện cảnh báo đỏ "không bỏ
 * đánh dấu được" dù người dùng không đụng vào gì.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/MoGaraModel.php';

use App\core\Hash;

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Man Sua gara co phan tai khoan chu gara');

$v   = file_get_contents($goc . 'app/views/admin/garages/edit.php');
$ctl = codeOnly($goc . 'app/controllers/admin/Garages.php');

foreach (['chu_name', 'chu_email', 'chu_password'] as $o){
    ok(strpos($v, 'name="' . $o . '"') !== false, "Form sua co o `$o`");
}
ok(preg_match('~name="chu_password"[^>]*autocomplete="new-password"~', $v) === 1,
   'O mat khau moi co autocomplete="new-password" — trinh duyet khong tu dien mat khau cua nguoi dang sua');
ok(strpos($ctl, 'chuGara(') !== false, 'Garages::edit lay tai khoan chu gara ra form');
ok(strpos($ctl, 'luuChuGara(') !== false, 'Garages::postEdit luu tai khoan chu gara');

// ---------------------------------------------------------------------------
section('Khong con nhan "gara tong" mo coi');

foreach (['add', 'edit'] as $f){
    $h = file_get_contents($goc . "app/views/admin/garages/$f.php");
    $coNhan = strpos($h, 'for="is_master"') !== false;
    $coO    = strpos($h, 'id="is_master"') !== false;
    ok(!$coNhan || $coO, "$f.php: khong co nhan for=\"is_master\" ma thieu o tich");
}
ok(strpos($ctl, 'gara tổng duy nhất nên không bỏ đánh dấu được') === false,
   'Luu gara tong khong con bao loi do vo co');

// ---------------------------------------------------------------------------
try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$donSach = function() use ($pdo){
    $pdo->exec("DELETE FROM users WHERE email LIKE 'zzsg-%@local.test'");
    foreach ($pdo->query("SELECT id FROM garages WHERE code LIKE 'ZZSG%'")->fetchAll(PDO::FETCH_COLUMN) as $g){
        $g = (int) $g;
        $pdo->exec("DELETE FROM garage_domains  WHERE garage_id = $g");
        $pdo->exec("DELETE FROM garage_settings WHERE garage_id = $g");
        $pdo->exec("DELETE FROM customer_groups WHERE garage_id = $g");
        $pdo->exec("DELETE FROM warehouses      WHERE garage_id = $g");
        $pdo->exec("DELETE FROM users           WHERE garage_id = $g");
        $ids = $pdo->query("SELECT id FROM `groups` WHERE garage_id = $g")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($ids as $n) $pdo->exec('DELETE FROM permissions WHERE group_id = ' . (int) $n);
        $pdo->exec("DELETE FROM `groups`        WHERE garage_id = $g");
        $pdo->exec("DELETE FROM garages         WHERE id = $g");
    }
};
$donSach();
register_shutdown_function(function() use ($donSach){ $donSach(); });

$taoGara = function($ma) use ($pdo){
    $pdo->prepare("INSERT INTO garages (code, name, is_master, status, sort_order, create_at)
                   VALUES (?, ?, 0, 1, 99, NOW())")->execute([$ma, 'Gara ' . $ma]);
    return (int) $pdo->lastInsertId();
};
$M = new MoGaraModel();
ok(method_exists($M, 'chuGara') && method_exists($M, 'luuChuGara'),
   'MoGaraModel co chuGara() va luuChuGara()');
if (!method_exists($M, 'chuGara') || !method_exists($M, 'luuChuGara')){ $donSach(); exit(summary()); }

$hash = function($mail) use ($pdo){
    $s = $pdo->prepare('SELECT password FROM users WHERE email = ?'); $s->execute([$mail]);
    return (string) $s->fetchColumn();
};

// ---------------------------------------------------------------------------
section('Gara mo kem tai khoan chu: man sua THAY tai khoan');

$id = $taoGara('ZZSG1');
$M->dungBoKhung($id, ['name' => 'ZZ Chu Mot', 'email' => 'zzsg-chu@local.test', 'password' => 'MatKhau@1']);
$chu = $M->chuGara($id);
ok(!empty($chu) && $chu['email'] === 'zzsg-chu@local.test' && $chu['name'] === 'ZZ Chu Mot',
   'chuGara() tra ve dung ten + email', json_encode($chu));
ok(!isset($chu['password']), 'chuGara() KHONG tra hash mat khau ra form');

// ---------------------------------------------------------------------------
section('Sua ten / email, de trong mat khau -> giu mat khau cu');

$hCu = $hash('zzsg-chu@local.test');
$kq  = $M->luuChuGara($id, 'ZZ Chu Doi Ten', 'zzsg-chu2@local.test', '');
ok($kq['loi'] === '', 'Luu duoc', $kq['loi']);
$chu = $M->chuGara($id);
ok($chu['name'] === 'ZZ Chu Doi Ten' && $chu['email'] === 'zzsg-chu2@local.test',
   'Ten + email da doi', json_encode($chu));
ok($hash('zzsg-chu2@local.test') === $hCu, 'Mat khau bo trong -> GIU NGUYEN mat khau cu');
ok((int) $pdo->query("SELECT COUNT(*) FROM users WHERE garage_id = $id")->fetchColumn() === 1,
   'Sua chu khong sinh them tai khoan');

// ---------------------------------------------------------------------------
section('Dat mat khau moi (chu gara quen mat khau)');

$kq = $M->luuChuGara($id, 'ZZ Chu Doi Ten', 'zzsg-chu2@local.test', 'MoiMoi@2');
ok($kq['loi'] === '', 'Dat mat khau moi duoc', $kq['loi']);
ok(Hash::check('MoiMoi@2', $hash('zzsg-chu2@local.test')), 'Dang nhap duoc bang mat khau MOI');
ok(!Hash::check('MatKhau@1', $hash('zzsg-chu2@local.test')), 'Mat khau CU het dung');
ok(mb_stripos($kq['da'], 'mật khẩu') !== false, 'Bao ra la da dat mat khau moi', $kq['da']);

// ---------------------------------------------------------------------------
section('Chan du lieu sai — khong doi gi khi loi');

$truoc = $M->chuGara($id);
$hTruoc = $hash('zzsg-chu2@local.test');
$kq = $M->luuChuGara($id, 'ZZ X', 'zzsg-chu2@local.test', '123');
ok(mb_stripos($kq['loi'], '6 ký tự') !== false, 'Mat khau ngan -> bao ro', $kq['loi']);
ok($hash('zzsg-chu2@local.test') === $hTruoc && $M->chuGara($id)['name'] === $truoc['name'],
   'Mat khau ngan -> KHONG doi gi ca (ke ca ten)');

$kq = $M->luuChuGara($id, 'ZZ X', 'khong-phai-email', '');
ok(mb_stripos($kq['loi'], 'định dạng') !== false, 'Email sai dinh dang -> bao ro', $kq['loi']);

$kq = $M->luuChuGara($id, '', 'zzsg-chu2@local.test', '');
ok(mb_stripos($kq['loi'], 'họ tên') !== false, 'Xoa trang ho ten -> bao ro, khong de tai khoan mat ten', $kq['loi']);

/* Email là tên đăng nhập của CẢ HỆ THỐNG: đổi sang email người khác đang dùng
   là hai người cùng một tên đăng nhập. */
$pdo->prepare("INSERT INTO users (name, email, password, status, group_id, garage_id, create_at)
               SELECT 'ZZ Nguoi Khac', 'zzsg-khac@local.test', 'x', 1, group_id, garage_id, NOW()
               FROM users WHERE email = 'zzsg-chu2@local.test'")->execute();
$kq = $M->luuChuGara($id, 'ZZ Chu Doi Ten', 'zzsg-khac@local.test', '');
ok(mb_stripos($kq['loi'], 'đã có người dùng') !== false, 'Email cua nguoi khac -> bao ro', $kq['loi']);
ok($M->chuGara($id)['email'] === 'zzsg-chu2@local.test', 'Va email chu gara khong bi doi');
$pdo->exec("DELETE FROM users WHERE email = 'zzsg-khac@local.test'");

// ---------------------------------------------------------------------------
section('Gara chua co tai khoan chu: man sua TAO duoc');

$id2 = $taoGara('ZZSG2');
$M->dungBoKhung($id2, []);
ok(empty($M->chuGara($id2)), 'Gara mo khong kem tai khoan -> chuGara() rong');

$kq = $M->luuChuGara($id2, '', '', '');
ok($kq['loi'] === '' && $kq['da'] === '', 'Bo trong ca ba o -> khong lam gi, khong bao loi', json_encode($kq));
ok((int) $pdo->query("SELECT COUNT(*) FROM users WHERE garage_id = $id2")->fetchColumn() === 0,
   'Va khong tao tai khoan rong');

$kq = $M->luuChuGara($id2, 'ZZ Chu Hai', 'zzsg-chu-hai@local.test', 'MatKhau@2');
ok($kq['loi'] === '', 'Khai du -> tao duoc', $kq['loi']);
$u = $pdo->query("SELECT u.email, g.name nhom, g.garage_id ng FROM users u JOIN `groups` g ON g.id = u.group_id
                  WHERE u.garage_id = $id2")->fetch(PDO::FETCH_ASSOC);
ok(!empty($u) && $u['email'] === 'zzsg-chu-hai@local.test' && $u['nhom'] === 'Manager'
   && (int) $u['ng'] === $id2,
   'Tai khoan moi thuoc nhom Manager CUA CHINH GARA DO', json_encode($u));

$kq = $M->luuChuGara($id2, 'ZZ Chu Hai', 'zzsg-chu-hai@local.test', '');
ok($kq['loi'] === '' && (int) $pdo->query("SELECT COUNT(*) FROM users WHERE garage_id = $id2")->fetchColumn() === 1,
   'Luu lai lan nua -> sua tai khoan do, KHONG tao them');

// ---------------------------------------------------------------------------
section('Chu gara = Manager cua chinh gara, khong phai nhan vien');

$idMgr = (int) $pdo->query("SELECT id FROM `groups` WHERE name = 'Manager' AND garage_id = $id2")->fetchColumn();
$idNv  = (int) $pdo->query("SELECT id FROM `groups` WHERE name <> 'Manager' AND garage_id = $id2 LIMIT 1")->fetchColumn();
if ($idNv){
    /* Nhân viên tạo TRƯỚC (id nhỏ hơn) vẫn không được coi là chủ */
    $pdo->exec("UPDATE users SET create_at = '2020-01-01' WHERE garage_id = $id2");
    $pdo->prepare("INSERT INTO users (name, email, password, status, group_id, garage_id, create_at)
                   VALUES ('ZZ Nhan Vien', 'zzsg-nv@local.test', 'x', 1, ?, ?, '2019-01-01')")
        ->execute([$idNv, $id2]);
    ok($M->chuGara($id2)['email'] === 'zzsg-chu-hai@local.test', 'Co them nhan vien -> van lay dung chu (Manager)');
}

$donSach();
ok((int) $pdo->query("SELECT COUNT(*) FROM garages WHERE code LIKE 'ZZSG%'")->fetchColumn() === 0,
   'Da don sach du lieu test');

exit(summary());

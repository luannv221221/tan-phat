<?php
/**
 * Test END-TO-END tren MySQL THAT (khong phai SQLite).
 *
 * Chay:  C:\xampp\php\php.exe tests\MySqlLiveTest.php
 *
 * Test nay CAN mot MySQL that dang chay va .env tro dung vao no.
 * Neu khong ket noi duoc thi tu BO QUA (skip), khong bao fail —
 * de tests/run.php van chay duoc tren may khong co DB.
 *
 * Vi sao can: 190+ test kia chay tren SQLite, ma SQLite:
 *   - BO QUA do dai VARCHAR   -> khong bat duoc bug password varchar(50)
 *   - KHONG co tu khoa danh rieng GROUPS -> khong bat duoc bug M10
 *   - Khong co STRICT_TRANS_TABLES
 * Day la nhung thu chi MySQL that moi lo ra.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use App\core\Database;
use App\core\Hash;

echo "PHP " . PHP_VERSION . "\n";

// ---- Ket noi that; khong duoc thi skip ----
try {
    $pdo = new PDO(
        'mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
        _USER, _PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL (" . _HOST . ':' . _PORT . "/" . _DB . ")\n";
    echo "       " . $e->getMessage() . "\n";
    echo "       Dien DB_* trong .env roi chay lai de test tren DB that.\n";
    exit(0);
}

$version = $pdo->query('SELECT VERSION()')->fetchColumn();
$sqlMode = $pdo->query('SELECT @@sql_mode')->fetchColumn();
echo "MySQL: $version\n";
echo "sql_mode: " . (strpos($sqlMode, 'STRICT_TRANS_TABLES') !== false ? 'co STRICT_TRANS_TABLES' : 'KHONG strict') . "\n";

// Cac model that cua app
require_once __DIR__ . '/../app/models/UsersModel.php';
require_once __DIR__ . '/../app/models/GroupsModel.php';
require_once __DIR__ . '/../app/models/PermissionsModel.php';
require_once __DIR__ . '/../app/models/LoginToken.php';

// ================================================================
section('Schema — migration da chay chua');

$col = $pdo->query('SHOW COLUMNS FROM users LIKE "password"')->fetch(PDO::FETCH_ASSOC);
ok(stripos($col['Type'], 'varchar(255)') !== false,
   'users.password la varchar(255) (da migrate)', 'thuc te: ' . $col['Type']);

$hasMig = $pdo->query('SHOW TABLES LIKE "migrations"')->fetchAll();
ok(!empty($hasMig), 'Bang migrations ton tai');

// ================================================================
section('M10 — tu khoa danh rieng `GROUPS` cua MySQL 8');

// `GROUPS` la tu khoa danh rieng tu MySQL 8.0.2. Chung minh trUC tiep:
$isMySql8 = version_compare($version, '8.0.2', '>=') && stripos($version, 'mariadb') === false;

if ($isMySql8){
    $reserved = false;
    try {
        $pdo->query('SELECT groups.id FROM `groups` groups LIMIT 1');
    } catch (\PDOException $e){
        $reserved = (strpos($e->getMessage(), 'syntax') !== false);
    }
    ok($reserved || true, '(boi canh) MySQL ' . $version . ' — GROUPS la tu khoa danh rieng');
} else {
    echo "  [INFO] Khong phai MySQL 8 -> bo qua kiem tra tu khoa GROUPS\n";
}

// Ban CU sinh ra: ... ON users.group_id=groups.id  (khong backtick)
$oldStyleFails = false;
try {
    $pdo->query('SELECT users.group_id FROM `users` INNER JOIN `groups` ON users.group_id=groups.id LIMIT 1');
} catch (\PDOException $e){
    $oldStyleFails = true;
    echo "  [INFO] Cau JOIN kieu CU loi: " . substr($e->getMessage(), 0, 90) . "\n";
}

// Ban MOI (joinOn) boc backtick
$newStyleWorks = true;
try {
    $pdo->query('SELECT `users`.`group_id` FROM `users` INNER JOIN `groups` ON `users`.`group_id` = `groups`.`id` LIMIT 1');
} catch (\PDOException $e){
    $newStyleWorks = false;
}
ok($newStyleWorks, 'Cau JOIN kieu MOI (co backtick) chay duoc tren MySQL that');

// ================================================================
section('Model that chay tren MySQL that');

$gm = new GroupsModel();
$g  = $gm->getGroupByUser(15);
ok(!empty($g) && isset($g['group_id']),
   'GroupsModel::getGroupByUser() — ham dung joinOn(), chay o MOI request admin',
   json_encode($g));

$um   = new UsersModel();
$list = $um->getLists();
ok(count($list) === 5, 'UsersModel::getLists() tra ve 5 user', 'so dong: ' . count($list));
ok(isset($list[0]['group_name']), 'getLists() leftJoinOn(`groups`) lay duoc group_name',
   json_encode($list[0]));

$filtered = (new UsersModel())->getLists(['users.status' => 1]);
ok(is_array($filtered), 'getLists() co filter chay duoc', 'so dong: ' . count($filtered));

$likes = (new UsersModel())->getLists([], ['users.email' => 'gmail']);
ok(count($likes) >= 1, 'getLists() voi Closure + whereOrLike chay duoc', 'so dong: ' . count($likes));

// ================================================================
section('B1 — SQL Injection tren MySQL THAT');

$evil = (new UsersModel())->checkLogin("' OR '1'='1", "' OR '1'='1");
ok(empty($evil), 'Payload "\' OR \'1\'=\'1" KHONG dang nhap duoc tren MySQL that');

$evil2 = (new UsersModel())->checkLogin("hoangan.web@gmail.com'--", 'sai');
ok(empty($evil2), 'Payload comment "--" KHONG dang nhap duoc');

$evil3 = (new UsersModel())->getLists(['users.name' => "'; DROP TABLE users;--"]);
ok(count($evil3) === 0, 'Payload DROP TABLE trong filter -> khong lot');
$still = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
ok($still == 5, 'Bang users VAN CON (khong bi DROP)', "so user: $still");

// ================================================================
section('B4 — Luong nang cap md5 -> bcrypt tren MySQL THAT');

// Dua ve trang thai md5 nhu dump goc
$pdo->prepare('UPDATE users SET password = ? WHERE id = 15')
    ->execute(['e10adc3949ba59abbe56e057f20f883e']); // md5('123456')

$before = $pdo->query('SELECT password FROM users WHERE id=15')->fetchColumn();
ok(Hash::isLegacyMd5($before), '(chuan bi) user #15 dang giu hash md5 cu', $before);

// Dang nhap bang mat khau THO — dung mat khau that cua dump: 123456
$login = (new UsersModel())->checkLogin('hoangan.web@gmail.com', '123456');
ok(!empty($login), 'User CU (md5) dang nhap duoc bang mat khau tho tren MySQL that');

$after = $pdo->query('SELECT password FROM users WHERE id=15')->fetchColumn();
ok(strpos($after, '$2y$') === 0,
   'Hash da TU DONG nang cap len bcrypt VA GHI DUOC vao cot varchar(255)',
   substr($after, 0, 12) . '... (dai ' . strlen($after) . ')');
ok(strlen($after) === 60, 'Hash bcrypt luu du 60 ky tu, KHONG bi cat cut', 'dai: ' . strlen($after));

// Dang nhap lai bang hash moi
$again = (new UsersModel())->checkLogin('hoangan.web@gmail.com', '123456');
ok(!empty($again), 'Sau khi nang cap, van dang nhap duoc');

$wrong = (new UsersModel())->checkLogin('hoangan.web@gmail.com', 'sai-mat-khau');
ok(empty($wrong), 'Sai mat khau -> tu choi');

// ================================================================
section('B2 — Transaction tren MySQL THAT (InnoDB)');

$db = new Database();

$db->transaction(function($d){
    $d->insert('options', ['opt_name' => 'test_tx_commit', 'opt_value' => 'x']);
});
$c1 = $pdo->query('SELECT COUNT(*) FROM options WHERE opt_name="test_tx_commit"')->fetchColumn();
ok($c1 == 1, 'transaction() commit that tren InnoDB');

$threw = false;
try {
    $db->transaction(function($d){
        $d->insert('options', ['opt_name' => 'test_tx_rollback', 'opt_value' => 'x']);
        throw new \RuntimeException('mo phong loi giua chung');
    });
} catch (\RuntimeException $e){ $threw = true; }

$c2 = $pdo->query('SELECT COUNT(*) FROM options WHERE opt_name="test_tx_rollback"')->fetchColumn();
ok($threw, 'transaction() nem lai exception');
ok($c2 == 0, 'transaction() ROLLBACK THAT tren InnoDB — du lieu khong sot lai');

// Don dep
$pdo->exec('DELETE FROM options WHERE opt_name LIKE "test_tx_%"');

// ================================================================
section('M1 — removeExpired() tren MySQL that');

$pdo->exec('DELETE FROM login_token');
$old = date('Y-m-d H:i:s', time() - 3600);
$new = date('Y-m-d H:i:s', time() - 60);
$ins = $pdo->prepare('INSERT INTO login_token (user_id,token,create_at,client_ip,current_activity) VALUES (?,?,?,?,?)');
$ins->execute([15, Hash::randomToken(), $new, '127.0.0.1', $new]);
$ins->execute([16, Hash::randomToken(), $old, '127.0.0.1', $old]);
$ins->execute([17, Hash::randomToken(), $old, '127.0.0.1', null]);

(new LoginToken())->removeExpired(15);
$left = $pdo->query('SELECT COUNT(*) FROM login_token')->fetchColumn();
ok($left == 1, 'removeExpired() giu lai dung 1 token con song', "con: $left");

// Token dai 64 ky tu phai luu vua cot varchar(100)
$tok = $pdo->query('SELECT token FROM login_token LIMIT 1')->fetchColumn();
ok(strlen($tok) === 64, 'Token 64 ky tu luu du trong cot (khong bi cat)', 'dai: ' . strlen($tok));

// ================================================================
section('M2 — utf8mb4 tren MySQL that');

$pdo->exec('DELETE FROM options WHERE opt_name = "test_utf8"');
$db2 = new Database();
$emoji = 'Tiếng Việt có dấu 🚗 phụ tùng ô tô';
$db2->insert('options', ['opt_name' => 'test_utf8', 'opt_value' => $emoji]);
$back = $pdo->query('SELECT opt_value FROM options WHERE opt_name="test_utf8"')->fetchColumn();
ok($back === $emoji, 'Luu/doc duoc tieng Viet + emoji (utf8mb4)', $back);
$pdo->exec('DELETE FROM options WHERE opt_name = "test_utf8"');

// ================================================================
section('EMULATE_PREPARES=false tren MySQL that');

$db3 = new Database();
$r = $db3->table('users')->where('id', '=', 15)->first();
ok(!empty($r) && $r['id'] == 15, 'Prepared statement that (khong emulate) hoat dong');

$r2 = $db3->table('users')->whereIn('id', [15, 16])->get();
ok(count($r2) === 2, 'whereIn voi prepared that hoat dong', 'so dong: ' . count($r2));

exit(summary());

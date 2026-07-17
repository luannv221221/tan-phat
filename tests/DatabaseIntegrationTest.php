<?php
/**
 * Integration test — chạy PDO THẬT (SQLite in-memory), không phải stub.
 *
 * Chạy:  C:\xampp\php\php.exe tests\DatabaseIntegrationTest.php
 *
 * Muc dich: chung minh insert/update/delete/select + transaction that su chay dung
 * voi bound parameters, va payload injection that su khong bypass duoc dang nhap.
 *
 * Dung SQLite vi no khong can server. Cu phap backtick + placeholder `?`
 * giong MySQL nen du de kiem chung tang QueryBuilder/Database.
 */

// Thu tu require rat quan trong: _FakeConnection dinh nghia App\core\Connection
// truoc, nen Database.php se dung Connection SQLite thay vi Connection MySQL that.
require_once __DIR__ . '/_FakeConnection.php';
require_once __DIR__ . '/../core/QueryBuilder.php';
require_once __DIR__ . '/../core/Database.php';

$GLOBALS['pass'] = 0;
$GLOBALS['fail'] = 0;

function ok($condition, $name, $detail = ''){
    if ($condition){ $GLOBALS['pass']++; echo "  [PASS] $name\n"; }
    else { $GLOBALS['fail']++; echo "  [FAIL] $name\n"; if ($detail!=='') echo "         $detail\n"; }
}
function section($t){ echo "\n=== $t ===\n"; }

echo "PHP " . PHP_VERSION . " | driver: sqlite (in-memory)\n";

$db = new App\core\Database();
$pdo = $db->pdo();

// Dung schema toi thieu giong bang users that
$pdo->exec("CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT, email TEXT, password TEXT, status INTEGER, group_id INTEGER
)");

// ================================================================
section('insert() — chay that voi bound parameters');

$db->insert('users', [
    'name' => "Nguyen Van A",
    'email' => 'a@tanphat.vn',
    'password' => password_hash('matkhau123', PASSWORD_DEFAULT),
    'status' => 1,
    'group_id' => 1,
]);
$id = $db->lastId();
ok($id == 1, 'insert() chay duoc tren PHP '.PHP_VERSION.' (ban cu se fatal o day)', "lastId=$id");

// Gia tri chua dau nhay don — ban cu dung addslashes, nay bind that
$db->insert('users', [
    'name' => "O'Brien \"quoted\" \\backslash",
    'email' => 'b@tanphat.vn',
    'password' => 'x', 'status' => 1, 'group_id' => 2,
]);
$row = $db->table('users')->where('email','=','b@tanphat.vn')->first();
ok($row['name'] === "O'Brien \"quoted\" \\backslash",
   'Gia tri co dau nhay/backslash luu va doc lai nguyen ven', var_export($row['name'], true));

// ================================================================
section('B1 — Injection that su KHONG bypass duoc dang nhap');

// Mo phong dung luong Auth: checkLogin(email, password)
function checkLogin($db, $email, $password){
    return $db->table('users')
              ->where('email', '=', $email)
              ->where('password', '=', $password)
              ->first();
}

$evil = checkLogin($db, "' OR '1'='1", "' OR '1'='1");
ok(empty($evil), 'Payload "\' OR \'1\'=\'1" KHONG dang nhap duoc', var_export($evil, true));

$evil2 = checkLogin($db, "a@tanphat.vn'--", 'sai');
ok(empty($evil2), 'Payload comment "--" KHONG dang nhap duoc');

// Dang nhap dung van phai chay
$real = $db->table('users')->where('email','=','a@tanphat.vn')->first();
ok(!empty($real) && password_verify('matkhau123', $real['password']),
   'Dang nhap dung van hoat dong (password_verify)');

// ================================================================
section('H1 — tai dung instance khong lam sai ket qua');

$r1 = $db->table('users')->where('id','=',1)->first();
$r2 = $db->table('users')->where('id','=',2)->first();
ok(!empty($r1) && $r1['id'] == 1, 'Lan 1 lay dung id=1');
ok(!empty($r2) && $r2['id'] == 2, 'Lan 2 lay dung id=2 (khong bi dinh WHERE id=1)',
   'ban cu se ra WHERE id=1 AND id=2 => rong');

// ================================================================
section('update() — goi 2 lan khong tron cot');

$db->update('users', ['name' => 'Ten Moi 1'], '`id` = ?', [1]);
$db->update('users', ['status' => 0], '`id` = ?', [2]);

$u1 = $db->table('users')->where('id','=',1)->first();
$u2 = $db->table('users')->where('id','=',2)->first();

ok($u1['name'] === 'Ten Moi 1', 'update() lan 1 dung');
ok($u2['status'] == 0 && $u2['name'] === "O'Brien \"quoted\" \\backslash",
   'update() lan 2 KHONG keo theo cot cua lan 1',
   'ban cu: $__update_set khong reset => lan 2 set ca name');

// ================================================================
section('B2 — transaction commit / rollback');

$db->transaction(function($d){
    $d->insert('users', ['name'=>'Trong TX','email'=>'tx@tanphat.vn','password'=>'x','status'=>1,'group_id'=>1]);
});
$txRow = $db->table('users')->where('email','=','tx@tanphat.vn')->first();
ok(!empty($txRow), 'transaction() commit thanh cong');

$threw = false;
try {
    $db->transaction(function($d){
        $d->insert('users', ['name'=>'Se rollback','email'=>'rb@tanphat.vn','password'=>'x','status'=>1,'group_id'=>1]);
        throw new \RuntimeException('loi giua chung — mo phong phieu dieu chuyen kho dut');
    });
} catch (\RuntimeException $e){ $threw = true; }

$rbRow = $db->table('users')->where('email','=','rb@tanphat.vn')->first();
ok($threw, 'transaction() nem lai exception cho caller');
ok(empty($rbRow), 'transaction() ROLLBACK — du lieu khong con sot lai',
   'day la thu bat buoc cho nhap kho / phieu thu / dieu chuyen kho');

// Kich ban THAT: mot cau QUERY loi giua transaction (khong phai exception tu nem).
// Truoc day Database::query() goi die() khi loi => khoi catch cua transaction()
// khong bao gio chay => rollBack() khong duoc goi.
$threwSql = false;
try {
    $db->transaction(function($d){
        $d->insert('users', ['name'=>'Truoc loi SQL','email'=>'sqlerr@tanphat.vn','password'=>'x','status'=>1,'group_id'=>1]);
        // Bang khong ton tai -> PDO nem loi -> query() phai NEM (khong duoc die)
        $d->insert('bang_khong_ton_tai', ['x' => 1]);
    });
} catch (\Throwable $e){ $threwSql = true; }

$sqlErrRow = $db->table('users')->where('email','=','sqlerr@tanphat.vn')->first();
ok($threwSql, 'Query loi trong transaction -> NEM exception (khong die)');
ok(empty($sqlErrRow),
   'Query loi -> ROLLBACK ca cau insert truoc do',
   'neu query() con die() thi dong nay se sot lai');
ok(!$db->inTransaction(), 'Sau rollback, khong con trong transaction');

// Sau khi loi, ket noi van dung duoc binh thuong
$stillWorks = $db->table('users')->where('email','=','tx@tanphat.vn')->first();
ok(!empty($stillWorks), 'Sau loi + rollback, ket noi van hoat dong binh thuong');

// ================================================================
section('delete() — bat buoc co WHERE');

$threw = false;
try { $db->delete('users', ''); } catch (\InvalidArgumentException $e){ $threw = true; }
ok($threw, 'delete() khong co WHERE -> nem exception (chan xoa sach bang)');

$before = count($db->table('users')->get());
$db->delete('users', '`id` = ?', [1]);
$after = count($db->table('users')->get());
ok($after === $before - 1, 'delete() co WHERE xoa dung 1 ban ghi', "truoc=$before sau=$after");

// ================================================================
section('whereIn chay that');

$db->insert('users', ['name'=>'C','email'=>'c@x.vn','password'=>'x','status'=>1,'group_id'=>3]);
$rows = $db->table('users')->whereIn('group_id', [2,3])->get();
ok(count($rows) >= 2, 'whereIn tra dung ban ghi', 'so dong: '.count($rows));

// ================================================================
echo "\n" . str_repeat('-', 50) . "\n";
echo "PASS: {$GLOBALS['pass']}   FAIL: {$GLOBALS['fail']}\n";
echo str_repeat('-', 50) . "\n";
exit($GLOBALS['fail'] > 0 ? 1 : 0);

<?php
/**
 * Test cho QueryBuilder sau khi vá (Phase 0 — B1, B3, H1).
 *
 * Chạy:  C:\xampp\php\php.exe tests\QueryBuilderTest.php
 *
 * Test này KHÔNG cần kết nối MySQL — nó chặn lại câu SQL + mảng bindings
 * do QueryBuilder sinh ra, rồi kiểm tra.
 *
 * Điều cần chứng minh: giá trị người dùng KHÔNG BAO GIỜ xuất hiện trong chuỗi SQL.
 */

require_once __DIR__ . '/../core/QueryBuilder.php';

// ---------- Khung test tối giản ----------
$GLOBALS['pass'] = 0;
$GLOBALS['fail'] = 0;

function ok($condition, $name, $detail = ''){
    if ($condition){
        $GLOBALS['pass']++;
        echo "  [PASS] $name\n";
    } else {
        $GLOBALS['fail']++;
        echo "  [FAIL] $name\n";
        if ($detail !== '') echo "         $detail\n";
    }
}

function section($title){
    echo "\n=== $title ===\n";
}

function norm($sql){
    return trim(preg_replace('/\s+/', ' ', $sql));
}

/** Stub: dùng trait QueryBuilder độc lập, chặn SQL thay vì chạy thật */
class SqlSpy {
    use App\core\QueryBuilder;

    public $sql = '';
    public $bound = [];

    public function getRaw($sql, array $bindings = []){
        $this->sql = norm($sql); $this->bound = $bindings; return [];
    }
    public function firstRaw($sql, array $bindings = []){
        $this->sql = norm($sql); $this->bound = $bindings; return [];
    }
}

echo "PHP " . PHP_VERSION . "\n";

// ================================================================
section('B1 — SQL Injection phai bi chan');

// Đây chính là payload đã bypass được đăng nhập trên base cũ
$evil = "' OR '1'='1";
$s = new SqlSpy();
$s->table('users')->where('email', '=', $evil)->first();

ok(strpos($s->sql, "OR '1'='1") === false,
   'Payload khong lot vao chuoi SQL',
   'SQL: ' . $s->sql);

ok(strpos($s->sql, '?') !== false,
   'SQL dung placeholder ?',
   'SQL: ' . $s->sql);

ok($s->bound === [$evil],
   'Payload nam trong bindings (duoc bind, khong noi chuoi)',
   'bindings: ' . json_encode($s->bound));

ok($s->sql === 'SELECT * FROM `users` WHERE `email` = ?',
   'SQL sinh ra dung nhu mong doi',
   'SQL: ' . $s->sql);

// Payload dạng khác: đóng ngoặc rồi DROP
$s = new SqlSpy();
$s->table('users')->where('name', '=', "x'); DROP TABLE users;--")->get();
ok(strpos($s->sql, 'DROP') === false,
   'Payload DROP TABLE khong lot vao SQL',
   'SQL: ' . $s->sql);

// ================================================================
section('B1b — Ten cot va toan tu phai duoc kiem tra');

$s = new SqlSpy();
$threw = false;
try { $s->table('users')->where('id = 1 OR 1=1 --', '=', 'x')->get(); }
catch (\InvalidArgumentException $e){ $threw = true; }
ok($threw, 'Ten cot chua ky tu la -> nem exception');

$s = new SqlSpy();
$threw = false;
try { $s->table('users')->where('id', 'UNION SELECT', 1)->get(); }
catch (\InvalidArgumentException $e){ $threw = true; }
ok($threw, 'Toan tu ngoai whitelist -> nem exception');

$s = new SqlSpy();
$s->table('users')->where('users.id', '=', 1)->get();
ok($s->sql === 'SELECT * FROM `users` WHERE `users`.`id` = ?',
   'Ten cot dang bang.cot van hoat dong',
   'SQL: ' . $s->sql);

// ================================================================
section('H1 — State phai reset giua 2 lan goi');

$s = new SqlSpy();
$s->table('users')->where('id', '=', 1)->get();
$first = $s->sql;
$s->table('users')->where('id', '=', 2)->get();
$second = $s->sql;

ok($first === 'SELECT * FROM `users` WHERE `id` = ?', 'Lan 1 dung', $first);
ok($second === 'SELECT * FROM `users` WHERE `id` = ?',
   'Lan 2 KHONG dinh dieu kien cua lan 1', $second);
ok($s->bound === [2], 'Lan 2 bind dung gia tri moi', json_encode($s->bound));

// ================================================================
section('Thu tu bindings phai khop thu tu placeholder');

$s = new SqlSpy();
$s->table('users')
  ->where('email', '=', 'a@b.c')
  ->where('status', '=', 1)
  ->whereLike('name', '%an%')
  ->get();

ok($s->sql === 'SELECT * FROM `users` WHERE `email` = ? AND `status` = ? AND `name` LIKE ?',
   'Ghep nhieu dieu kien dung', $s->sql);
ok($s->bound === ['a@b.c', 1, '%an%'],
   'Bindings dung thu tu', json_encode($s->bound));

// ================================================================
section('Nhom dieu kien long nhau (Closure)');

$s = new SqlSpy();
$s->table('users')
  ->where('status', '=', 1)
  ->where(function($q){
      $q->whereOrLike('users.name', '%an%');
      $q->whereOrLike('users.email', '%an%');
  })
  ->get();

ok($s->sql === 'SELECT * FROM `users` WHERE `status` = ? AND (`users`.`name` LIKE ? OR `users`.`email` LIKE ?)',
   'Nhom long nhau sinh dung dau ngoac', $s->sql);
ok($s->bound === [1, '%an%', '%an%'],
   'Bindings trong nhom long nhau dung thu tu', json_encode($s->bound));

// ================================================================
section('Gia tri chua chu "WHERE" khong lam lech logic');

// Base cu dung strpos($whereQuery,'WHERE') tren chuoi DA chua gia tri nguoi dung
// => gia tri chua chu WHERE co the lam sai tu noi. Nay gia tri khong con trong SQL.
$s = new SqlSpy();
$s->table('users')->where('name', '=', 'WHERE')->where('id', '=', 5)->get();
ok($s->sql === 'SELECT * FROM `users` WHERE `name` = ? AND `id` = ?',
   'Gia tri "WHERE" khong pha logic tu noi', $s->sql);

// ================================================================
section('whereIn');

$s = new SqlSpy();
$s->table('users')->whereIn('id', [1,2,3])->get();
ok($s->sql === 'SELECT * FROM `users` WHERE `id` IN(?,?,?)', 'whereIn sinh dung so placeholder', $s->sql);
ok($s->bound === [1,2,3], 'whereIn bind dung', json_encode($s->bound));

// whereIn với dữ liệu độc
$s = new SqlSpy();
$s->table('users')->whereIn('id', ["1); DROP TABLE users;--"])->get();
ok(strpos($s->sql, 'DROP') === false, 'whereIn chan duoc payload', $s->sql);

// whereIn rỗng không được sinh SQL hỏng
$s = new SqlSpy();
$s->table('users')->whereIn('id', [])->get();
ok(strpos($s->sql, 'IN()') === false, 'whereIn rong khong sinh IN() loi cu phap', $s->sql);

// ================================================================
section('orderBy / limit');

$s = new SqlSpy();
$s->table('users')->orderBy('name', 'DESC')->limit(10, 20)->get();
ok(strpos($s->sql, 'ORDER BY `name` DESC') !== false, 'orderBy dung', $s->sql);
ok(strpos($s->sql, 'LIMIT 20, 10') !== false, 'limit dung', $s->sql);

// orderBy với hướng sắp xếp độc hại
$s = new SqlSpy();
$s->table('users')->orderBy('name', 'ASC; DROP TABLE users')->get();
ok(strpos($s->sql, 'DROP') === false, 'orderBy chan duoc payload o huong sap xep', $s->sql);

// limit với giá trị độc hại
$s = new SqlSpy();
$s->table('users')->limit('10; DROP TABLE users', 0)->get();
ok(strpos($s->sql, 'DROP') === false, 'limit chan duoc payload (ep kieu int)', $s->sql);

// ================================================================
section('M10 — joinOn() boc backtick, chiu duoc tu khoa danh rieng MySQL 8');

$s = new SqlSpy();
$s->table('users')->joinOn('groups', 'users.group_id', 'groups.id')->get();
ok($s->sql === 'SELECT * FROM `users` INNER JOIN `groups` ON `users`.`group_id` = `groups`.`id`',
   'joinOn() boc backtick moi dinh danh', $s->sql);

// `groups` la tu khoa danh rieng tu MySQL 8.0.2 (window function GROUPS)
ok(strpos($s->sql, '`groups`.`id`') !== false,
   '`groups` duoc backtick => khong loi cu phap tren MySQL 8', $s->sql);
ok(preg_match('/[^`]groups\./', $s->sql) === 0,
   'Khong con chuoi groups. nao KHONG duoc backtick', $s->sql);

$s = new SqlSpy();
$s->table('users')->leftJoinOn('groups', 'users.group_id', 'groups.id')->get();
ok(strpos($s->sql, 'LEFT JOIN `groups`') !== false, 'leftJoinOn() sinh LEFT JOIN', $s->sql);

$s = new SqlSpy();
$threw = false;
try { $s->table('users')->joinOn('groups', 'users.group_id', 'groups.id', 'EVIL JOIN')->get(); }
catch (\InvalidArgumentException $e){ $threw = true; }
ok($threw, 'Kieu JOIN ngoai whitelist -> nem exception');

$s = new SqlSpy();
$threw = false;
try { $s->table('users')->joinOn('groups', 'users.id = 1 OR 1=1 --', 'groups.id')->get(); }
catch (\InvalidArgumentException $e){ $threw = true; }
ok($threw, 'Payload trong dieu kien ON -> nem exception (bi wrapField chan)');

// ================================================================
section('B3 — khong con phu thuoc get_magic_quotes_gpc');

$src = file_get_contents(__DIR__ . '/../core/Database.php');
ok(strpos($src, 'get_magic_quotes_gpc') === false,
   'Database.php khong con goi get_magic_quotes_gpc (da bi xoa khoi PHP 8)');
ok(strpos($src, 'addslashes') === false,
   'Database.php khong con dung addslashes');

$srcQb = file_get_contents(__DIR__ . '/../core/QueryBuilder.php');
// Mau nguy hiem cua ban cu: "$field $compare '$value'" — noi thang gia tri vao SQL.
ok(strpos($srcQb, "'\$value'") === false,
   "QueryBuilder khong con mau noi chuoi '\$value'");

// ================================================================
section('B5 — catch dung \PDOException');

ok(strpos($src, 'catch (\PDOException') !== false,
   'Database.php bat \PDOException (khong phai App\core\Exception)');

$srcConn = file_get_contents(__DIR__ . '/../core/Connection.php');
ok(strpos($srcConn, 'catch (\\') !== false || strpos($srcConn, 'catch (\PDOException') !== false,
   'Connection.php bat exception co dau \\ (global namespace)');

// ================================================================
echo "\n" . str_repeat('-', 50) . "\n";
echo "PASS: {$GLOBALS['pass']}   FAIL: {$GLOBALS['fail']}\n";
echo str_repeat('-', 50) . "\n";

exit($GLOBALS['fail'] > 0 ? 1 : 0);

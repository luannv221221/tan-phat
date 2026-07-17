<?php
/**
 * Smoke test cho MODEL THẬT của app (UsersModel, GroupsModel, PermissionsModel, LoginToken)
 * sau khi vá QueryBuilder/Database/Model.
 *
 * Chạy:  C:\xampp\php\php.exe tests\ModelsSmokeTest.php
 *
 * Muc dich: chung minh refactor khong lam vo code nghiep vu dang co —
 * dac biet UsersModel::getLists() dung Closure + leftJoin (duong phuc tap nhat).
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/_FakeConnection.php';
require_once __DIR__ . '/../core/QueryBuilder.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../core/Hash.php';
require_once __DIR__ . '/../app/models/UsersModel.php';
require_once __DIR__ . '/../app/models/GroupsModel.php';
require_once __DIR__ . '/../app/models/PermissionsModel.php';
require_once __DIR__ . '/../app/models/LoginToken.php';

$GLOBALS['pass'] = 0;
$GLOBALS['fail'] = 0;

function ok($condition, $name, $detail = ''){
    if ($condition){ $GLOBALS['pass']++; echo "  [PASS] $name\n"; }
    else { $GLOBALS['fail']++; echo "  [FAIL] $name\n"; if ($detail!=='') echo "         $detail\n"; }
}
function section($t){ echo "\n=== $t ===\n"; }

echo "PHP " . PHP_VERSION . " | model that cua app + sqlite\n";

// Dung schema giong dump tanphat_php
$boot = new App\core\Database();
$pdo  = $boot->pdo();
$pdo->exec("CREATE TABLE groups (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");
$pdo->exec("CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT, password TEXT,
    status INTEGER, group_id INTEGER, current_activity TEXT
)");
$pdo->exec("CREATE TABLE permissions (id INTEGER PRIMARY KEY AUTOINCREMENT, module_id INTEGER, group_id INTEGER, role TEXT)");
$pdo->exec("CREATE TABLE login_token (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, token TEXT, create_at TEXT, client_ip TEXT, current_activity TEXT)");

$pdo->exec("INSERT INTO groups (name) VALUES ('Admin'),('Manager'),('Staff')");

// Mat khau phai luu duoi dang hash — checkLogin() nay nhan mat khau THO
// va verify bang bcrypt (truoc day nhan md5 da bam san).
$ins = $pdo->prepare("INSERT INTO users (name,email,password,status,group_id) VALUES (?,?,?,?,?)");
$ins->execute(['Hoang An',  'an@tanphat.vn',    App\core\Hash::make('pw1'), 1, 1]);
$ins->execute(['Tran Binh', 'binh@tanphat.vn',  App\core\Hash::make('pw2'), 1, 2]);
$ins->execute(['Le Cuong',  'cuong@tanphat.vn', App\core\Hash::make('pw3'), 0, 3]);

// ================================================================
section('UsersModel');

$um = new UsersModel();

$u = $um->getDetail(1);
ok(!empty($u) && $u['name'] === 'Hoang An', 'getDetail() chay', json_encode($u['name'] ?? null));

$login = $um->checkLogin('an@tanphat.vn', 'pw1');
ok(!empty($login), 'checkLogin() dung thong tin -> tra ve user');

$bad = $um->checkLogin("' OR '1'='1", "' OR '1'='1");
ok(empty($bad), 'checkLogin() voi payload injection -> KHONG dang nhap duoc');

// getLists khong loc
$all = $um->getLists();
ok(count($all) === 3, 'getLists() khong loc tra ve 3 user', 'so dong: '.count($all));
ok(array_key_exists('group_name', $all[0]), 'getLists() leftJoin lay duoc group_name',
   json_encode($all[0]));

// getLists co filter
$filtered = $um->getLists(['users.status' => 1]);
ok(count($filtered) === 2, 'getLists() loc theo status=1 tra ve 2 user', 'so dong: '.count($filtered));

// getLists co likes -> di qua Closure + whereOrLike
$likes = $um->getLists([], ['users.name' => 'An']);
ok(count($likes) >= 1, 'getLists() voi $likes (Closure + whereOrLike) chay duoc',
   'so dong: '.count($likes));

// getLists ket hop filter + likes -> Closure long trong WHERE da co dieu kien
// Dung 'Hoang' chu khong phai 'An': LIKE khong phan biet hoa thuong nen '%An%'
// khop ca "Tr[an] Binh" — dung nhung khong phan biet duoc truong hop can test.
$combo = $um->getLists(['users.status' => 1], ['users.name' => 'Hoang']);
ok(count($combo) === 1, 'getLists() ket hop filter + likes dung', 'so dong: '.count($combo));

// Chung minh Closure duoc boc ngoac dung: status=0 AND (name LIKE %Cuong%) -> 1 dong.
// Neu thieu ngoac, OR se lam bung ket qua ra toan bo bang.
$paren = $um->getLists(['users.status' => 0], ['users.name' => 'Cuong']);
ok(count($paren) === 1, 'Closure duoc boc ngoac dung (khong bi OR lam bung ket qua)',
   'so dong: '.count($paren));

// Injection qua gia tri filter
$evilFilter = $um->getLists(['users.name' => "' OR '1'='1"]);
ok(count($evilFilter) === 0, 'getLists() payload trong gia tri filter -> khong lot',
   'so dong: '.count($evilFilter));

// Injection qua TEN COT filter -> phai nem exception
$threw = false;
try { $um->getLists(['users.name = 1 OR 1=1 --' => 'x']); }
catch (\InvalidArgumentException $e){ $threw = true; }
ok($threw, 'getLists() payload trong TEN COT -> nem exception');

// ================================================================
section('UsersModel — ghi du lieu');

$um2 = new UsersModel();
$um2->add(['name'=>'Moi','email'=>'moi@tanphat.vn','password'=>'pw','status'=>1,'group_id'=>3]);
$after = (new UsersModel())->getLists();
ok(count($after) === 4, 'add() them duoc user', 'so dong: '.count($after));

$um3 = new UsersModel();
$um3->edit(['name'=>'Da Sua'], 1);
$edited = (new UsersModel())->getDetail(1);
ok($edited['name'] === 'Da Sua', 'edit() sua duoc user');

$um4 = new UsersModel();
$um4->remove(4);
$afterDel = (new UsersModel())->getLists();
ok(count($afterDel) === 3, 'remove() xoa duoc user', 'so dong: '.count($afterDel));

// ================================================================
section('GroupsModel');

$gm = new GroupsModel();
$groups = $gm->getLists();
ok(count($groups) === 3, 'getLists() tra ve 3 group', 'so dong: '.count($groups));

$gm2 = new GroupsModel();
$g = $gm2->getGroupByUser(1);
ok(!empty($g), 'getGroupByUser() (join) chay duoc', json_encode($g));

// ================================================================
section('PermissionsModel');

$pm = new PermissionsModel();
$pm->add(['module_id'=>1,'group_id'=>1,'role'=>'view']);
$pm2 = new PermissionsModel();
$pm2->add(['module_id'=>2,'group_id'=>1,'role'=>'edit']);
$pm3 = new PermissionsModel();
$pm3->add(['module_id'=>1,'group_id'=>2,'role'=>'view']);

$perm1 = (new PermissionsModel())->getPermission(1);
ok(count($perm1) === 2, 'getPermission() lay dung quyen cua group 1', 'so dong: '.count($perm1));

(new PermissionsModel())->remove(1);
$permAfter = (new PermissionsModel())->getPermission(1);
ok(count($permAfter) === 0, 'remove() xoa quyen theo group_id');
$permOther = (new PermissionsModel())->getPermission(2);
ok(count($permOther) === 1, 'remove() KHONG xoa nham group khac');

// ================================================================
section('LoginToken');

$lt = new LoginToken();
$tokenId = $lt->add(['user_id'=>1,'token'=>md5('x'),'create_at'=>time(),'client_ip'=>'127.0.0.1','current_activity'=>time()]);
ok($tokenId > 0, 'add() tra ve lastId', "id=$tokenId");

$t = (new LoginToken())->getToken($tokenId);
ok(!empty($t) && $t['user_id'] == 1, 'getToken() lay dung token');

(new LoginToken())->edit(['current_activity'=>time()+10], $tokenId);
(new LoginToken())->removeByUser(1);
$left = (new LoginToken())->getLists();
ok(count($left) === 0, 'removeByUser() xoa token theo user_id');

// ================================================================
section('M1 — removeExpired() don token qua han bang 1 cau DELETE');

$now    = time();
$moi    = date('Y-m-d H:i:s', $now - 60);        // hoat dong 1 phut truoc
$cu     = date('Y-m-d H:i:s', $now - 3600);      // hoat dong 60 phut truoc -> qua han

$mk = $pdo->prepare("INSERT INTO login_token (user_id,token,create_at,client_ip,current_activity) VALUES (?,?,?,?,?)");
$mk->execute([1, 'token_con_song', $moi, '127.0.0.1', $moi]);
$mk->execute([2, 'token_qua_han',  $cu,  '127.0.0.1', $cu]);
// Truong hop ban CU BO SOT: current_activity = NULL (dang nhap xong khong vao admin)
$mk->execute([3, 'token_null_cu',  $cu,  '127.0.0.1', null]);
$mk->execute([4, 'token_null_moi', $moi, '127.0.0.1', null]);

ok(count((new LoginToken())->getLists()) === 4, 'Da tao 4 token de thu');

(new LoginToken())->removeExpired(15);

$conLai = (new LoginToken())->getLists();
$tokens = array_column($conLai, 'token');

ok(in_array('token_con_song', $tokens, true), 'Token con hoat dong -> GIU lai');
ok(!in_array('token_qua_han', $tokens, true), 'Token qua 15 phut -> XOA');
ok(!in_array('token_null_cu', $tokens, true),
   'Token current_activity=NULL va tao lau roi -> XOA (ban cu BO SOT truong hop nay)');
ok(in_array('token_null_moi', $tokens, true),
   'Token current_activity=NULL nhung vua tao -> GIU (chua kip vao admin)');
ok(count($conLai) === 2, 'Con dung 2 token', 'con: '.implode(', ', $tokens));

// AuthMiddleware khong con goi getLists()
$mwSrc = codeOnly(__DIR__ . '/../app/middlewares/AuthMiddleware.php');
ok(strpos($mwSrc, 'getLists()') === false,
   'AuthMiddleware khong con nap toan bo bang token moi request');
ok(strpos($mwSrc, 'removeExpired') !== false,
   'AuthMiddleware dung removeExpired() (1 cau DELETE)');

// ================================================================
echo "\n" . str_repeat('-', 50) . "\n";
echo "PASS: {$GLOBALS['pass']}   FAIL: {$GLOBALS['fail']}\n";
echo str_repeat('-', 50) . "\n";
exit($GLOBALS['fail'] > 0 ? 1 : 0);

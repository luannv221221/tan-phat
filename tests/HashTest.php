<?php
/**
 * Test cho Hash + luồng đăng nhập mới (B4).
 *
 * Chạy:  C:\xampp\php\php.exe tests\HashTest.php
 *
 * Diem quan trong nhat: user cu (hash md5) van dang nhap duoc,
 * va hash cua ho tu dong nang cap len bcrypt.
 */

require_once __DIR__ . '/_FakeConnection.php';
require_once __DIR__ . '/../core/QueryBuilder.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../core/Hash.php';
require_once __DIR__ . '/../app/models/UsersModel.php';

$GLOBALS['pass'] = 0;
$GLOBALS['fail'] = 0;

function ok($condition, $name, $detail = ''){
    if ($condition){ $GLOBALS['pass']++; echo "  [PASS] $name\n"; }
    else { $GLOBALS['fail']++; echo "  [FAIL] $name\n"; if ($detail!=='') echo "         $detail\n"; }
}
function section($t){ echo "\n=== $t ===\n"; }

use App\core\Hash;

echo "PHP " . PHP_VERSION . "\n";

// ================================================================
section('Hash — bam va kiem tra');

$h = Hash::make('matkhau123');
ok(strpos($h, '$2y$') === 0, 'make() sinh hash bcrypt', substr($h, 0, 7));
ok(Hash::check('matkhau123', $h) === true, 'check() dung mat khau -> true');
ok(Hash::check('sai', $h) === false, 'check() sai mat khau -> false');

// Co salt: 2 lan bam cung 1 mat khau phai ra 2 hash KHAC nhau
$h2 = Hash::make('matkhau123');
ok($h !== $h2, 'Bam 2 lan ra 2 hash khac nhau (co salt ngau nhien)');
ok(Hash::check('matkhau123', $h2) === true, 'Ca 2 hash deu verify duoc');

// ================================================================
section('Hash — tuong thich nguoc voi md5 cu');

$legacy = md5('matkhau123');
ok(Hash::isLegacyMd5($legacy) === true, 'Nhan dien duoc hash md5 cu');
ok(Hash::isLegacyMd5($h) === false, 'Khong nham hash bcrypt thanh md5');
ok(Hash::check('matkhau123', $legacy) === true, 'User cu (md5) van dang nhap duoc');
ok(Hash::check('sai', $legacy) === false, 'User cu nhap sai -> false');
ok(Hash::needsRehash($legacy) === true, 'Hash md5 -> can nang cap');
ok(Hash::needsRehash($h) === false, 'Hash bcrypt moi -> khong can nang cap');
ok(Hash::check('x', '') === false, 'Hash rong -> false (khong loi)');

// ================================================================
section('Hash — token ngau nhien');

$t1 = Hash::randomToken();
$t2 = Hash::randomToken();
ok(strlen($t1) === 64, 'randomToken() dai 64 ky tu hex (32 byte)', 'len='.strlen($t1));
ok($t1 !== $t2, '2 token khac nhau');
ok(preg_match('/^[a-f0-9]+$/', $t1) === 1, 'Token chi gom hex');

// md5(uniqid()) cua ban cu chi 32 ky tu va doan duoc theo thoi gian
ok(strlen($t1) > strlen(md5(uniqid())), 'Token moi dai hon md5(uniqid()) cu');

// ================================================================
section('Luong dang nhap that (UsersModel)');

$boot = new App\core\Database();
$pdo  = $boot->pdo();
$pdo->exec("CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT,
    password TEXT, status INTEGER, group_id INTEGER
)");

// User CU: hash md5 (giong du lieu trong dump tanphat_php)
$pdo->exec("INSERT INTO users (name,email,password,status,group_id)
            VALUES ('User Cu','cu@tanphat.vn','" . md5('matkhaucu') . "',1,1)");

// User MOI: hash bcrypt
$newHash = Hash::make('matkhaumoi');
$stmt = $pdo->prepare("INSERT INTO users (name,email,password,status,group_id) VALUES (?,?,?,?,?)");
$stmt->execute(['User Moi','moi@tanphat.vn',$newHash,1,1]);

$um = new UsersModel();

// --- User cu dang nhap ---
$r = $um->checkLogin('cu@tanphat.vn', 'matkhaucu');
ok(!empty($r), 'User CU (md5) dang nhap duoc bang mat khau tho');

// --- Hash phai duoc nang cap sau khi dang nhap ---
$after = (new UsersModel())->findByEmail('cu@tanphat.vn');
ok(strpos($after['password'], '$2y$') === 0,
   'Hash md5 da TU DONG nang cap len bcrypt sau khi dang nhap',
   substr($after['password'], 0, 10));
ok(!Hash::isLegacyMd5($after['password']), 'Khong con la md5 nua');

// --- Dang nhap lai bang hash moi van phai duoc ---
$r2 = (new UsersModel())->checkLogin('cu@tanphat.vn', 'matkhaucu');
ok(!empty($r2), 'Sau khi nang cap, van dang nhap duoc nhu thuong');

// --- User moi ---
$r3 = (new UsersModel())->checkLogin('moi@tanphat.vn', 'matkhaumoi');
ok(!empty($r3), 'User MOI (bcrypt) dang nhap duoc');

// --- Sai mat khau ---
$r4 = (new UsersModel())->checkLogin('moi@tanphat.vn', 'sai');
ok(empty($r4), 'Sai mat khau -> khong dang nhap duoc');

// --- Email khong ton tai ---
$r5 = (new UsersModel())->checkLogin('khongco@tanphat.vn', 'gi cung duoc');
ok(empty($r5), 'Email khong ton tai -> khong dang nhap duoc');

// --- Injection van phai bi chan ---
$r6 = (new UsersModel())->checkLogin("' OR '1'='1", "' OR '1'='1");
ok(empty($r6), 'Payload injection -> khong dang nhap duoc');

// --- Mat khau rong ---
$r7 = (new UsersModel())->checkLogin('moi@tanphat.vn', '');
ok(empty($r7), 'Mat khau rong -> khong dang nhap duoc');

// ================================================================
section('Khong con md5() trong code nghiep vu');

function codeOnly($path){
    $tokens = token_get_all(file_get_contents($path));
    $out = '';
    foreach ($tokens as $t){
        if (is_array($t)){
            if ($t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT) continue;
            $out .= $t[1];
        } else { $out .= $t; }
    }
    return $out;
}

$files = [
    'app/controllers/Auth.php',
    'app/controllers/admin/Users.php',
    'app/models/UsersModel.php',
];
foreach ($files as $f){
    $code = codeOnly(__DIR__ . '/../' . $f);
    ok(strpos($code, 'md5(') === false, "$f khong con goi md5()");
}

// ================================================================
echo "\n" . str_repeat('-', 50) . "\n";
echo "PASS: {$GLOBALS['pass']}   FAIL: {$GLOBALS['fail']}\n";
echo str_repeat('-', 50) . "\n";
exit($GLOBALS['fail'] > 0 ? 1 : 0);

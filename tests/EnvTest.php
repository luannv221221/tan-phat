<?php
/**
 * Test cho Env + xác nhận credential đã rời khỏi config.php (H4/M8)
 * và Connection dùng chung 1 kết nối (M7).
 *
 * Chạy:  C:\xampp\php\php.exe tests\EnvTest.php
 */

require_once __DIR__ . '/../core/Env.php';

$GLOBALS['pass'] = 0;
$GLOBALS['fail'] = 0;

function ok($condition, $name, $detail = ''){
    if ($condition){ $GLOBALS['pass']++; echo "  [PASS] $name\n"; }
    else { $GLOBALS['fail']++; echo "  [FAIL] $name\n"; if ($detail!=='') echo "         $detail\n"; }
}
function section($t){ echo "\n=== $t ===\n"; }

/**
 * Trả về mã PHP của file sau khi BỎ HẾT comment.
 *
 * Cần thiết vì các file này có comment trích lại code cũ để giải thích
 * ("bản cũ viết if (!$this->_conn)..."). Grep thẳng vào text nguồn sẽ
 * trúng comment và báo fail oan — chính xác là chuyện đã xảy ra.
 */
function codeOnly($path){
    $tokens = token_get_all(file_get_contents($path));
    $out = '';
    foreach ($tokens as $t){
        if (is_array($t)){
            if ($t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT) continue;
            $out .= $t[1];
        } else {
            $out .= $t;
        }
    }
    return $out;
}

use App\core\Env;

echo "PHP " . PHP_VERSION . "\n";

// ================================================================
section('Env — doc file');

$tmp = sys_get_temp_dir() . '/env_test_' . getmypid() . '.env';
file_put_contents($tmp, <<<ENV
# day la comment
DB_HOST=localhost
DB_NAME=tanphat_php
DB_USER=root
DB_PASS=

QUOTED_DOUBLE="co dau cach"
QUOTED_SINGLE='cung the'
BOOL_TRUE=true
BOOL_FALSE=false
NULL_VAL=null
HAS_EQUALS=abc=def=ghi
PASS_SPECIAL=p@ss#w0rd!\$%

# dong tren co dau # nhung khong o dau dong -> van la gia tri
ENV
);

Env::load($tmp);

ok(Env::get('DB_HOST') === 'localhost', 'Doc gia tri thuong');
ok(Env::get('QUOTED_DOUBLE') === 'co dau cach', 'Bo dau nhay kep', var_export(Env::get('QUOTED_DOUBLE'), true));
ok(Env::get('QUOTED_SINGLE') === 'cung the', 'Bo dau nhay don', var_export(Env::get('QUOTED_SINGLE'), true));
ok(Env::get('BOOL_TRUE') === true, 'true -> bool true');
ok(Env::get('BOOL_FALSE') === false, 'false -> bool false');
ok(Env::get('NULL_VAL') === null, 'null -> null');
ok(Env::get('HAS_EQUALS') === 'abc=def=ghi', 'Gia tri chua dau = khong bi cat', var_export(Env::get('HAS_EQUALS'), true));
ok(Env::get('PASS_SPECIAL') === 'p@ss#w0rd!$%', 'Mat khau co ky tu dac biet doc dung',
   var_export(Env::get('PASS_SPECIAL'), true));

// DB_PASS rong -> tra ve default
ok(Env::get('DB_PASS', 'MACDINH') === 'MACDINH', 'Gia tri rong -> tra ve default');
ok(Env::has('DB_PASS') === true, 'has() thay key du gia tri rong');
ok(Env::get('KHONG_TON_TAI', 'fallback') === 'fallback', 'Key khong co -> tra ve default');
ok(Env::has('KHONG_TON_TAI') === false, 'has() voi key khong co -> false');

// Comment khong bi doc thanh key
ok(Env::get('# day la comment') === null, 'Dong comment khong thanh key');

@unlink($tmp);

// ================================================================
section('H4/M8 — credential khong con trong config.php');

// Bo comment truoc khi kiem tra — comment co trich code cu de giai thich.
$cfg = codeOnly(__DIR__ . '/../config.php');

ok(strpos($cfg, "define('_PASS', '')") === false && strpos($cfg, 'define("_PASS", "")') === false,
   'config.php khong con hardcode _PASS');
ok(strpos($cfg, 'Env::get') !== false, 'config.php doc tu Env');
ok(strpos($cfg, '/Unicode/2021/FRAMEWORK/') === false,
   'config.php khong con hardcode duong dan may local');

$envExample = __DIR__ . '/../.env.example';
ok(is_file($envExample), '.env.example ton tai (de nguoi moi biet can khai bao gi)');

$gitignore = __DIR__ . '/../.gitignore';
ok(is_file($gitignore), '.gitignore ton tai');
if (is_file($gitignore)){
    $gi = file_get_contents($gitignore);
    ok(preg_match('/^\.env\s*$/m', $gi) === 1, '.gitignore co chan .env');
    ok(strpos($gi, 'public/logs/session') !== false, '.gitignore co chan file session');
}

// ================================================================
section('M7 — Connection dung chung 1 ket noi');

$src = codeOnly(__DIR__ . '/../core/Connection.php');
ok(strpos($src, 'static $_shared') !== false, 'Connection co bien static de dung chung');
ok(preg_match('/if\s*\(\s*!\s*\$this->_conn\s*\)/', $src) === 0,
   'Da bo `if (!$this->_conn)` — dieu kien luon dung nen moi model mo 1 ket noi moi');

// Chung minh bang hanh vi: 2 instance phai tra ve CUNG mot object PDO
require_once __DIR__ . '/_FakeConnection.php';
$c1 = new App\core\Connection();
$c2 = new App\core\Connection();
ok($c1->pdo() === $c2->pdo(), '2 instance dung CHUNG mot PDO (khong mo them ket noi)');

// ================================================================
echo "\n" . str_repeat('-', 50) . "\n";
echo "PASS: {$GLOBALS['pass']}   FAIL: {$GLOBALS['fail']}\n";
echo str_repeat('-', 50) . "\n";
exit($GLOBALS['fail'] > 0 ? 1 : 0);

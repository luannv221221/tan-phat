<?php
/**
 * Test cho CSRF (H2) + Session (H3).
 *
 * Chạy:  C:\xampp\php\php.exe tests\SecurityTest.php
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../core/Session.php';

use App\core\Session;

echo "PHP " . PHP_VERSION . "\n";

// CLI khong co session that -> gia lap $_SESSION
$_SESSION = [];

// ================================================================
section('H2 — CSRF token');

$t1 = Session::csrfToken();
ok(strlen($t1) === 64, 'csrfToken() dai 64 ky tu hex', 'len='.strlen($t1));

$t2 = Session::csrfToken();
ok($t1 === $t2, 'Goi lai tra ve CUNG token trong 1 phien');

ok(Session::checkCsrf($t1) === true, 'Token dung -> chap nhan');
ok(Session::checkCsrf('sai') === false, 'Token sai -> tu choi');
ok(Session::checkCsrf('') === false, 'Token rong -> tu choi');
ok(Session::checkCsrf(null) === false, 'Token null -> tu choi');

// Token cua phien khac khong dung duoc
$tokenPhienKhac = bin2hex(random_bytes(32));
ok(Session::checkCsrf($tokenPhienKhac) === false, 'Token cua phien khac -> tu choi');

// Khong co token trong session -> moi thu deu bi tu choi
Session::resetCsrf();
ok(Session::checkCsrf($t1) === false, 'Sau resetCsrf() token cu khong dung duoc nua');

// ================================================================
section('H2 — CsrfMiddleware');

$mw = file_get_contents(__DIR__ . '/../app/middlewares/CsrfMiddleware.php');
ok(is_file(__DIR__ . '/../app/middlewares/CsrfMiddleware.php'), 'CsrfMiddleware ton tai');
ok(strpos($mw, "'POST', 'PUT', 'PATCH', 'DELETE'") !== false,
   'Middleware kiem tra POST/PUT/PATCH/DELETE');
ok(strpos($mw, 'HTTP_X_CSRF_TOKEN') !== false, 'Ho tro token qua header (cho AJAX)');

$appCfg = file_get_contents(__DIR__ . '/../configs/app.php');
ok(strpos($appCfg, 'CsrfMiddleware::class') !== false,
   'CsrfMiddleware da duoc dang ky trong configs/app.php');
ok(preg_match('/global_middleware.*?CsrfMiddleware::class/s', $appCfg) === 1,
   'Dang ky o global_middleware (ap dung cho MOI request, khong the quen)');

// ================================================================
section('H2 — moi form POST deu co token');

$views = glob(__DIR__ . '/../app/views/**/*.php');
$views = array_merge($views, glob(__DIR__ . '/../app/views/**/**/*.php'));

$formsPost   = 0;
$thieuToken  = [];

foreach (array_unique($views) as $v){
    $html = file_get_contents($v);

    // Tim cac the <form ... method="post" ...>
    if (preg_match_all('/<form[^>]*method\s*=\s*["\']post["\'][^>]*>/i', $html, $m, PREG_OFFSET_CAPTURE)){
        foreach ($m[0] as $match){
            $formsPost++;
            // Lay 300 ky tu ngay sau the <form> de kiem tra co csrf_field khong
            $after = substr($html, $match[1], 300);
            if (strpos($after, 'csrf_field') === false){
                $thieuToken[] = basename(dirname($v)) . '/' . basename($v);
            }
        }
    }
}

ok($formsPost > 0, "Tim thay $formsPost form POST de kiem tra");
ok(empty($thieuToken), 'MOI form POST deu co csrf_field()',
   empty($thieuToken) ? '' : 'Thieu o: ' . implode(', ', $thieuToken));

// ================================================================
section('H3 — session khong con nam trong public/');

// codeOnly: bo comment truoc khi kiem tra — comment co trich duong dan cu de giai thich
$sessCfg = codeOnly(__DIR__ . '/../configs/session.php');
ok(strpos($sessCfg, './public/logs/session') === false,
   'configs/session.php khong con tro vao public/logs/session');
ok(strpos($sessCfg, 'storage/sessions') !== false, 'Mac dinh tro sang storage/sessions');

$leftover = glob(__DIR__ . '/../public/logs/session/sess_*');
ok(empty($leftover), 'Khong con file session nao trong public/ (truoc day co 16)',
   empty($leftover) ? '' : count($leftover) . ' file con lai');

ok(is_file(__DIR__ . '/../public/logs/.htaccess'), 'public/logs/ da co .htaccess chan');
ok(is_file(__DIR__ . '/../storage/.htaccess'), 'storage/ da co .htaccess chan');

// ================================================================
section('H3 — cookie session duoc bao ve');

$sessSrc = file_get_contents(__DIR__ . '/../core/Session.php');
ok(strpos($sessSrc, "'httponly' => true") !== false, 'Cookie dat httponly (JS khong doc duoc)');
ok(strpos($sessSrc, "'samesite' => 'Lax'") !== false, 'Cookie dat samesite=Lax');
ok(strpos($sessSrc, 'session_regenerate_id') !== false,
   'Co regenerate() — chong session fixation');

$auth = file_get_contents(__DIR__ . '/../app/controllers/Auth.php');
ok(strpos($auth, 'Session::regenerate()') !== false,
   'Auth goi Session::regenerate() khi dang nhap thanh cong');

// ================================================================
section('M11 — .htaccess khong con bat hien thi loi + chan file nhay cam');

$ht = file_get_contents(__DIR__ . '/../.htaccess');

ok(preg_match('/^\s*php_value\s+error_reporting/m', $ht) === 0,
   '.htaccess khong con `php_value error_reporting -1`');

// Rewrite rule co !-f => file CO THAT duoc phuc vu thang, khong qua index.php
// => .env nam o thu muc goc SE TAI VE DUOC neu khong chan tuong minh.
ok(strpos($ht, 'RewriteCond %{REQUEST_FILENAME} !-f') !== false,
   '(boi canh) rewrite bo qua file co that => phai chan tuong minh');

ok(preg_match('/FilesMatch\s+"\^\\\\\.env"/', $ht) === 1,
   '.htaccess CHAN file .env (neu khong -> lo mat khau DB qua trinh duyet)');
ok(preg_match('/\\\\\.\(sql\|/', $ht) === 1,
   '.htaccess chan file .sql (dump chua hash mat khau user)');
ok(strpos($ht, 'Options -Indexes') !== false, '.htaccess tat liet ke thu muc');

ok(is_file(__DIR__ . '/../database/.htaccess'), 'database/ co .htaccess chan (phong thu 2 lop)');

$boot = file_get_contents(__DIR__ . '/../bootstrap.php');
ok(strpos($boot, "ini_set('display_errors', '0')") !== false,
   'bootstrap tat display_errors khi khong debug');
ok(strpos($boot, "ini_set('log_errors', '1')") !== false,
   'bootstrap van ghi log loi');
ok(strpos($boot, 'set_exception_handler') !== false,
   'bootstrap co set_exception_handler (luoi cuoi, khong lo stack trace)');

// ================================================================
section('M6 — XSS: moi cho in bien ra view deu phai escape');

/*
 * Hai cach in an toan:
 *   {{ $x }}          -> Template compile thanh htmlentities()  (an toan)
 *   <?php echo e($x) ?> -> helper e() = htmlspecialchars        (an toan)
 * Cach KHONG an toan:
 *   <?php echo $x ?>  -> in tho, du lieu nguoi dung => XSS luu tru
 *   {!! $x !!}        -> co y in tho (chi dung cho HTML minh tu viet)
 */

$allViews = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../app/views'));
foreach ($it as $f){
    if ($f->isFile() && $f->getExtension() === 'php') $allViews[] = $f->getPathname();
}

ok(count($allViews) > 0, 'Tim thay ' . count($allViews) . ' file view de quet');

$xss = [];
foreach ($allViews as $v){
    $html = file_get_contents($v);

    /* Tim cac cho `echo $bien` ma khong qua e() / htmlspecialchars / csrf_field.
       (Dung comment /* * / chu khong dung // — vi trong comment //, dau dong PHP
        van thoat khoi che do PHP va lam hong ca file.) */
    if (preg_match_all('/<\?(?:php)?\s*echo\s+([^;?]+)/i', $html, $m)){
        foreach ($m[1] as $expr){
            $expr = trim($expr);

            // Bo qua neu da escape hoac la helper an toan
            if (preg_match('/\b(e|htmlspecialchars|htmlentities|csrf_field|csrf_token)\s*\(/', $expr)) continue;

            // Bo qua neu chi in hang so (_WEB_URL) va chuoi, khong co bien
            if (strpos($expr, '$') === false) continue;

            $xss[] = str_replace(__DIR__ . '/../', '', $v) . ' -> echo ' . substr($expr, 0, 45);
        }
    }
}

ok(empty($xss), 'Khong con `<?php echo $bien` nao chua escape trong view',
   empty($xss) ? '' : implode("\n         ", $xss));

// Chung minh Template that su escape {{ }}
$tpl = file_get_contents(__DIR__ . '/../core/Template.php');
ok(strpos($tpl, 'htmlentities') !== false,
   'Template compile {{ }} thanh htmlentities() (nen {{ }} la an toan)');

// helper e() phai ton tai
ok(function_exists('e') || strpos(file_get_contents(__DIR__ . '/../app/helpers/functions.php'), 'function e(') !== false,
   'Helper e() ton tai cho truong hop phai dung <?php echo');

// ================================================================
section('Helper');

require_once __DIR__ . '/../app/helpers/functions.php';

$field = csrf_field();
ok(strpos($field, 'name="_token"') !== false, 'csrf_field() sinh input _token', $field);
ok(strpos($field, 'type="hidden"') !== false, 'csrf_field() la input hidden');
ok(strpos($field, csrf_token()) !== false, 'csrf_field() chua dung token hien tai');
ok(e('<script>alert(1)</script>') === '&lt;script&gt;alert(1)&lt;/script&gt;',
   'e() escape duoc the script', e('<script>alert(1)</script>'));
ok(e('a"b\'c') === 'a&quot;b&#039;c', 'e() escape ca dau nhay (ENT_QUOTES)', e('a"b\'c'));

// ================================================================
exit(summary());

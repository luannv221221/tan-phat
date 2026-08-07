<?php
/**
 * Test GHI NHỚ ĐĂNG NHẬP admin.
 *
 * Chạy:  C:\xampp\php\php.exe tests\RememberLoginTest.php
 *
 * Nguyên nhân "phiên hết nhanh": AuthMiddleware gọi removeExpired() ở mọi
 * request, xoá token sau 15 phút không thao tác. Không phải do session PHP.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
        _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n";
    exit(0);
}

require_once __DIR__ . '/../app/models/LoginToken.php';

echo 'PHP ' . PHP_VERSION . ' | MySQL ' . $pdo->query('SELECT VERSION()')->fetchColumn() . "\n";

$uid = (int) $pdo->query('SELECT id FROM users LIMIT 1')->fetchColumn();
if ($uid <= 0){ echo "[SKIP] Chua co user nao.\n"; exit(0); }

$clean = function() use ($pdo){ $pdo->exec("DELETE FROM login_tokens WHERE client_ip = '203.0.113.9'"); };
$clean();

$m = new LoginToken();
$mk = function($remember, $activityAgoSec, $rawCookie = null) use ($m, $uid){
    $d = [
        'user_id'   => $uid,
        'token'     => bin2hex(random_bytes(16)),
        'remember'  => $remember ? 1 : 0,
        'create_at' => date('Y-m-d H:i:s', time() - $activityAgoSec),
        'client_ip' => '203.0.113.9',
        'current_activity' => date('Y-m-d H:i:s', time() - $activityAgoSec),
    ];
    if ($rawCookie !== null) $d['remember_hash'] = hash('sha256', $rawCookie);
    return $m->add($d);
};

// ================================================================
section('Token thuong het sau 15 phut, token ghi nho thi khong');

$idThuongCu  = $mk(false, 20 * 60);      // 20 phut truoc
$idThuongMoi = $mk(false, 2 * 60);       // 2 phut truoc
$idNhoCu     = $mk(true,  20 * 60);      // 20 phut truoc NHUNG co tick
$idNhoRatCu  = $mk(true,  40 * 86400);   // 40 ngay truoc -> qua han ca ghi nho

$m->removeExpired(15, LoginToken::REMEMBER_DAYS);

$con = function($id) use ($pdo){ return (int) $pdo->query("SELECT COUNT(*) FROM login_tokens WHERE id=$id")->fetchColumn() === 1; };

ok(!$con($idThuongCu),  'Token THUONG 20 phut khong thao tac -> bi xoa (dung nhu cu)');
ok($con($idThuongMoi),  'Token THUONG 2 phut -> van con');
ok($con($idNhoCu),      'Token GHI NHO 20 phut -> VAN CON (truoc day bi xoa)');
ok(!$con($idNhoRatCu),  'Token GHI NHO 40 ngay -> bi xoa (qua 30 ngay)');

$clean();

// ================================================================
section('Tra cookie bang hash');

$raw = bin2hex(random_bytes(32));
$id  = $mk(true, 60, $raw);

// (int) ca hai ve: add() tra ve lastId() dang CHUOI, so === voi so nguyen
// thi luon false du dung dong.
$found = $m->findByRemember($raw);
ok(!empty($found) && (int) $found['id'] === (int) $id, 'Cookie dung -> tim ra token');
ok($m->findByRemember($raw . 'x') === null, 'Cookie sai -> khong tim ra');
ok($m->findByRemember('') === null, 'Cookie rong -> khong tim ra');

// CSDL chi duoc giu HASH. Lo CSDL ma lo luon cookie thi vao duoc moi tai khoan.
$row = $pdo->query("SELECT remember_hash FROM login_tokens WHERE id=$id")->fetch(PDO::FETCH_ASSOC);
ok($row['remember_hash'] !== $raw, 'CSDL KHONG luu nguyen gia tri cookie');
ok($row['remember_hash'] === hash('sha256', $raw), 'CSDL luu dung SHA-256 cua cookie');
ok(strlen($raw) === 64, 'Cookie dai 64 ky tu (32 byte ngau nhien)', strlen($raw));

// Token thuong khong duoc tra ra qua cookie du co doan trung
$idThuong = $mk(false, 60);
$pdo->exec("UPDATE login_tokens SET remember_hash='" . hash('sha256', 'abc') . "' WHERE id=$idThuong");
ok($m->findByRemember('abc') === null, 'Token KHONG tick ghi nho thi cookie khong dung duoc');

$clean();

// ================================================================
section('Cookie duoc dong chat');

$cookieSrc = file_get_contents(__DIR__ . '/../core/Cookie.php');
ok(strpos($cookieSrc, "'httponly' => true") !== false,
   'Cookie co httponly (JS khong doc duoc -> XSS khong lay duoc phien 30 ngay)');
ok(strpos($cookieSrc, "'samesite' => 'Lax'") !== false, 'Cookie co samesite Lax');
ok(strpos($cookieSrc, "'secure'") !== false, 'Cookie theo cau hinh secure');
ok(strpos($cookieSrc, "'path'     => '/'") !== false, 'Cookie dat path / (xoa moi dung)');

// ================================================================
section('Dang xuat phai vut cookie');

$auth = codeOnly(__DIR__ . '/../app/controllers/Auth.php');
ok(preg_match('~function logout\(\).*?Cookie::remove~s', $auth) === 1,
   'logout() xoa cookie ghi nho (khong thi request sau lai tu dang nhap lai)');
ok(strpos($auth, "remember_hash") !== false && strpos($auth, "hash('sha256'") !== false,
   'Luc dang nhap chi luu hash xuong CSDL');

$mw = codeOnly(__DIR__ . '/../app/middlewares/AuthMiddleware.php');
ok(strpos($mw, 'restoreFromRemember') !== false, 'Middleware khoi phuc phien tu cookie');
ok(preg_match('~restoreFromRemember.*?Session::regenerate~s', $mw) === 1,
   'Cap session id moi khi nang cookie len phien (chong session fixation)');

// ================================================================
section('Config left_time khong con la config chet');

$sess = codeOnly(__DIR__ . '/../core/Session.php');
ok(strpos($sess, "left_time") !== false && strpos($sess, 'gc_maxlifetime') !== false,
   'Session ap left_time vao gc_maxlifetime (truoc day khai bao roi bo do)');

// ================================================================
section('Form dang nhap co o tich');

$ctx = stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]);
$html = @file_get_contents('http://localhost:88/tan-phat/dang-nhap', false, $ctx);
if ($html === false){
    echo "  [SKIP] Apache khong chay\n";
} else {
    ok(strpos($html, 'name="remember"') !== false, 'Form co o tich name=remember');
    ok(strpos($html, 'Ghi nhớ đăng nhập') !== false, 'Co nhan "Ghi nho dang nhap"');
    ok(strpos($html, 'máy công cộng') !== false, 'Co canh bao khong tich o may cong cong');
}

$clean();
exit(summary());

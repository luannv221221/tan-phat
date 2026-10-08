<?php
/**
 * TẮT MỘT TÀI KHOẢN THÌ PHẢI THỰC SỰ KHOÁ ĐƯỢC NGƯỜI ĐÓ — vá 08/10/2026.
 *
 * Chạy:  C:\xampp\php\php.exe tests\TaiKhoanTatTest.php
 *
 * CHỖ HỎNG: cột `users`.`status` có từ đầu, màn Người dùng có nút tắt, danh
 * sách hiện "Ngừng" — nhưng KHÔNG AI KIỂM CỜ ĐÓ.
 *     UsersModel::checkLogin()  chỉ verify mật khẩu
 *     Auth::postLogin()         chỉ kiểm gara (tai_khoan_co_gara)
 *     AuthMiddleware            cũng chỉ kiểm gara
 * Nên tắt tài khoản của người vừa nghỉ việc xong, họ vẫn đăng nhập và làm việc
 * bình thường. Đây là loại lỗi không ai phát hiện, vì màn hình nói rằng đã xong.
 *
 * BA TRƯỜNG HỢP, và trường hợp thứ hai mới là nguy hiểm nhất:
 *
 *   1. Đăng nhập bằng tài khoản đã tắt -> không vào được.
 *   2. ĐANG CÓ PHIÊN rồi mới bị tắt -> đá ra NGAY ở request sau. Chỉ kiểm lúc
 *      đăng nhập thì người đó làm việc tiếp cho tới khi phiên hết — mà tích
 *      "Ghi nhớ đăng nhập" giữ phiên tới 30 ngày.
 *   3. Có cookie "Ghi nhớ đăng nhập" của tài khoản đã tắt -> cookie dựng lại
 *      được phiên, nhưng vẫn bị đá ra.
 *
 * Và chiều ngược lại: tài khoản BẬT thì vào được bình thường — không thì test
 * này xanh chỉ vì mọi thứ đều bị chặn.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc  = __DIR__ . '/../';
$base = 'http://localhost:88/tan-phat';

// ---------------------------------------------------------------------------
section('Co chot trong ma nguon');

$auth = codeOnly($goc . 'app/controllers/Auth.php');
ok(strpos($auth, 'tai_khoan_dang_bat') !== false,
   'Auth::postLogin() kiem co `status`',
   'Khong kiem thi tai khoan da tat van dang nhap duoc');

$amw = codeOnly($goc . 'app/middlewares/AuthMiddleware.php');
ok(strpos($amw, 'tai_khoan_dang_bat') !== false,
   'AuthMiddleware kiem co `status` o MOI request admin',
   'Chi kiem luc dang nhap thi tat tai khoan khong da duoc nguoi dang mo may ra');

$fn = codeOnly($goc . 'app/helpers/functions.php');
ok(strpos($fn, 'function tai_khoan_dang_bat') !== false, 'Co helper tai_khoan_dang_bat()');
ok(strpos($fn, 'function nguoi_dang_nhap') !== false,
   'Co helper nguoi_dang_nhap() (nho dong `users` trong request)',
   'AuthMiddleware va gara_hien_tai() deu can dong do — hai lan doc la mot truy van thua');

/* nguoi_dang_nhap() KHONG duoc nho ket qua null: o request khoi phuc phien tu
   cookie, luc boot() hoi thi chua co phien, toi luc AuthMiddleware hoi thi da
   co — nho null tu lan dau la da oan nguoi dung ra. Cung ly do voi
   gara_hien_tai(). */
$i = strpos($fn, 'function nguoi_dang_nhap(');
$than = $i === false ? '' : substr($fn, $i, 600);
ok($than !== '' && preg_match('~return\s+null;~', $than) === 1
   && preg_match('~\$cache\s*=\s*null~', $than) !== 1,
   'nguoi_dang_nhap() KHONG nho ket qua null',
   'Nho null o request khoi phuc phien tu cookie la da oan nguoi dung ra');

// ---------------------------------------------------------------------------
section('Chay that qua HTTP');

if (!function_exists('curl_init')){ echo "\n[SKIP] PHP khong co curl.\n"; exit(summary()); }

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$MK   = 'ZzTatTk#2026';
$hash = \App\core\Hash::make($MK);

$donSach = function() use ($pdo){
    $pdo->exec("DELETE t FROM login_tokens t JOIN users u ON u.id = t.user_id
                 WHERE u.email LIKE 'zz-tk-%@local.test'");
    $pdo->exec("DELETE FROM users WHERE email LIKE 'zz-tk-%@local.test'");
};
$donSach();
register_shutdown_function($donSach);

$nhom  = (int) $pdo->query("SELECT id FROM `groups` WHERE name = 'Admin' LIMIT 1")->fetchColumn();
$garaT = (int) $pdo->query("SELECT id FROM garages WHERE is_master = 1 ORDER BY id LIMIT 1")->fetchColumn();
if (!$nhom || !$garaT){ echo "\n[SKIP] Thieu nhom Admin / gara tong.\n"; exit(summary()); }

$pdo->prepare("INSERT INTO users (name, email, password, group_id, status, garage_id, create_at)
               VALUES ('ZZ Tai khoan tat', 'zz-tk-1@local.test', ?, ?, 1, ?, NOW())")
    ->execute([$hash, $nhom, $garaT]);
$uid = (int) $pdo->lastInsertId();

$jars = [];
$http = function($method, $url, $jar, $data = null){
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 20,
    ]);
    if ($method === 'POST'){
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $raw  = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    $body = $raw === false ? '' : substr($raw, (int) $info['header_size']);
    return ['code' => (int) $info['http_code'], 'loc' => (string) $info['redirect_url'],
            'text' => html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8')];
};
$token = function($h){ return preg_match('~name="_token" value="([^"]+)"~', $h, $m) ? $m[1] : ''; };

$moJar = function() use (&$jars){ $j = tempnam(sys_get_temp_dir(), 'zztk'); $jars[] = $j; return $j; };
register_shutdown_function(function() use (&$jars){ foreach ($jars as $j) @unlink($j); });

$dangNhap = function($jar, $ghiNho = false) use ($http, $token, $base, $MK){
    $r = $http('GET', "$base/dang-nhap", $jar);
    if ($r['code'] === 0) return null;
    $d = ['email' => 'zz-tk-1@local.test', 'password' => $MK, '_token' => $token($r['text'])];
    if ($ghiNho) $d['remember'] = 1;
    return $http('POST', "$base/dang-nhap", $jar, $d);
};
$bat = function($v) use ($pdo, $uid){
    $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([(int) $v, $uid]);
};

// --- Chiều ngược: tài khoản BẬT thì vào được ---
$jar = $moJar();
if ($dangNhap($jar) === null){ echo "\n[SKIP] Apache khong chay (localhost:88).\n"; exit(summary()); }
$r = $http('GET', "$base/admin", $jar);
ok($r['code'] === 200, 'Tai khoan DANG BAT vao duoc trang quan tri (HTTP ' . $r['code'] . ')',
   'Khong co khang dinh nay thi ca file xanh chi vi moi thu deu bi chan');

// --- 2. Tắt giữa chừng, phiên đang mở ---
$bat(0);
$r = $http('GET', "$base/admin", $jar);
ok($r['code'] === 302 && strpos($r['loc'], 'dang-nhap') !== false,
   'TAT giua chung -> request admin NGAY SAU bi da ve trang dang nhap',
   'HTTP ' . $r['code'] . ' ' . $r['loc']
   . ' — chi kiem luc dang nhap thi nguoi dang mo may lam viec tiep toi 30 ngay (cookie ghi nho)');

$r = $http('GET', "$base/dang-nhap", $jar);
ok(strpos($r['text'], 'đã bị tắt') !== false,
   'Trang dang nhap noi RO ly do: tai khoan da bi tat',
   'Da ra ma khong noi ly do thi nguoi dung goi quan tri doi gan lai gara');

/* Phien phai bi HUY, khong chi bi chan: con token thi doi cho status bat lai la
   vao duoc ngay voi phien cu. */
$conToken = (int) $pdo->query("SELECT COUNT(*) FROM login_tokens WHERE user_id = $uid")->fetchColumn();
ok($conToken === 0, 'Phien bi HUY (xoa token), khong chi bi chan', "Con $conToken token");

// --- 1. Đăng nhập khi đã tắt ---
$jar2 = $moJar();
$dangNhap($jar2);
$r = $http('GET', "$base/admin", $jar2);
ok($r['code'] === 302 && strpos($r['loc'], 'dang-nhap') !== false,
   'Dang nhap bang tai khoan DA TAT -> khong vao duoc',
   'HTTP ' . $r['code'] . ' ' . $r['loc']);
ok((int) $pdo->query("SELECT COUNT(*) FROM login_tokens WHERE user_id = $uid")->fetchColumn() === 0,
   'Dang nhap that bai thi KHONG cap token');

// --- 3. Cookie "Ghi nhớ đăng nhập" của tài khoản đã tắt ---
$bat(1);
$jar3 = $moJar();
$dangNhap($jar3, true);
$r = $http('GET', "$base/admin", $jar3);
ok($r['code'] === 200, 'Dang nhap co tich "Ghi nho" -> vao duoc (HTTP ' . $r['code'] . ')');

/* Xoa session PHP o phia trinh duyet: giu nguyen cookie ghi nho, bo cookie
   phien. Request sau se phai khoi phuc phien TU COOKIE — duong di nguy hiem
   nhat, vi no khong qua Auth::postLogin(). */
$noiDung = (string) @file_get_contents($jar3);
$giuLai  = [];
foreach (explode("\n", $noiDung) as $dong){
    if ($dong === '' || strpos($dong, 'PHPSESSID') !== false) continue;
    $giuLai[] = $dong;
}
@file_put_contents($jar3, implode("\n", $giuLai) . "\n");

$bat(0);
$r = $http('GET', "$base/admin", $jar3);
ok($r['code'] === 302 && strpos($r['loc'], 'dang-nhap') !== false,
   'Cookie "Ghi nho" cua tai khoan DA TAT -> khoi phuc phien roi van bi da ra',
   'HTTP ' . $r['code'] . ' ' . $r['loc']
   . ' — duong nay khong qua Auth::postLogin(), chi co AuthMiddleware gac');

$donSach();
ok((int) $pdo->query("SELECT COUNT(*) FROM users WHERE email LIKE 'zz-tk-%@local.test'")->fetchColumn() === 0,
   'Da don sach tai khoan test');

echo "\n";
exit(summary());

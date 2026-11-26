<?php
/**
 * Test CHAT TỰ NHẬN TIN, KHÔNG PHẢI F5 + HIỆN "ĐANG NHẬP…".
 *
 * Chạy:  C:\xampp\php\php.exe tests\ChatTuNhanTinTest.php
 *
 * Khách nhắn anh 26/11/2026, kèm ảnh màn Hội thoại trên điện thoại (dmsg): "cái
 * chat cứ phải f5 mới nhận tin". Trong chính cuộc chat thử đó khách gõ "Nhưng
 * ko thấy typing".
 *
 * GỐC: phía khách (khung chat trên web) có hỏi tin mới mỗi 4 giây, nhưng màn
 * Hội thoại của nhân viên (admin/chat/view) là trang tĩnh: không hỏi gì cả, trả
 * lời thì gửi form rồi tải lại cả trang. Tin khách gửi tới chỉ hiện khi F5.
 *
 * Chốt:
 *   - admin/chat/poll/<id>?after=<id tin cuối>: trả JSON các tin mới (cả tin
 *     nhân viên gửi từ máy khác), màn Hội thoại tự hỏi vài giây một lần.
 *   - Trả lời bằng AJAX (X-Requested-With): trả JSON, không tải lại trang.
 *   - "Đang nhập…" hai chiều: bên nào gõ thì báo (chat/dang-go,
 *     admin/chat/dang-go/<id>), poll của bên kia trả `typing`; gửi tin xong thì tắt.
 *   - Tự hỏi KHÔNG được gia hạn phiên đăng nhập: phiên tự thoát sau 15 phút
 *     không thao tác — để màn chat mở cả ngày thì không bao giờ thoát.
 *   - Hộp thư (admin/chat) tự làm mới danh sách.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Nguon');

$v = file_get_contents($goc . 'app/views/admin/chat/view.php');
ok(strpos($v, 'data-poll=') !== false, 'Man Hoi thoai co duong tu hoi tin moi (data-poll)');
ok(strpos($v, 'setInterval') !== false || strpos($v, 'setTimeout') !== false, 'Man Hoi thoai tu hoi theo nhip');
ok(strpos($v, 'X-Requested-With') !== false, 'Tra loi bang AJAX, khong tai lai trang');
ok(mb_strpos($v, 'đang nhập') !== false, 'Co dong "... đang nhập…" o man nhan vien');
$l = file_get_contents($goc . 'app/views/admin/chat/lists.php');
ok(strpos($l, 'id="hop-thu"') !== false && strpos($l, 'setInterval') !== false, 'Hop thu tu lam moi danh sach');
$sf = file_get_contents($goc . 'app/views/layouts/storefront/master.php');
ok(strpos($sf, '/chat/dang-go') !== false, 'Khung chat khach bao "dang go"');
ok(mb_strpos($sf, 'đang trả lời') !== false, 'Khung chat khach hien "Nhân viên đang trả lời…"');
$r = file_get_contents($goc . 'routes/web.php');
ok(strpos($r, "Route::get('chat/poll/(\\d+)'") !== false, 'Co tuyen admin chat/poll/<id>');
ok(strpos($r, "Route::post('chat/dang-go/(\\d+)'") !== false && strpos($r, "Route::post('chat/dang-go'") !== false,
   'Co tuyen bao dang go cho ca hai phia');
ok(count(glob($goc . 'database/migrations/*_000123_*.php')) === 1, 'Co migration 000123 (cot dang go)');

// ---------------------------------------------------------------------------
try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){ echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary()); }

$coCot = function($cot) use ($pdo){
    return (bool) $pdo->query("SHOW COLUMNS FROM chat_conversations LIKE " . $pdo->quote($cot))->fetch();
};
ok($coCot('customer_typing_at') && $coCot('staff_typing_at'), 'CSDL co hai cot dang go (da chay 000123)');

$donSach = function() use ($pdo){
    $ids = $pdo->query("SELECT DISTINCT conversation_id FROM chat_messages WHERE body LIKE 'zztn-%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $c){ $c = (int) $c; $pdo->exec("DELETE FROM chat_messages WHERE conversation_id = $c"); $pdo->exec("DELETE FROM chat_conversations WHERE id = $c"); }
};
$donSach();
register_shutdown_function($donSach);

// ---------------------------------------------------------------------------
section('Chay that: khach gui, nhan vien tu nhan — khong F5');

$base = web_goc();
$lam = function($jar){
    return function($method, $url, $data = null, $ajax = false) use ($jar){
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 20]);
        if ($ajax) curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest']);
        if ($method === 'POST'){ curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query((array) $data)); }
        $b = curl_exec($ch); $c = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        return [$c, (string) $b];
    };
};
$jarK = tempnam(sys_get_temp_dir(), 'tnk'); $jarA = tempnam(sys_get_temp_dir(), 'tna');
register_shutdown_function(function() use ($jarK, $jarA){ @unlink($jarK); @unlink($jarA); });
$khach = $lam($jarK); $nv = $lam($jarA);

list($c, $trang) = $khach('GET', $base . '/');
if ($c === 0){ echo "\n[SKIP] Apache khong chay (localhost:88).\n"; exit(summary()); }
$tkK = preg_match('~var TOKEN = "([^"]+)"~', $trang, $m) ? $m[1] : '';

$the = 'zztn-' . bin2hex(random_bytes(4));
list($c, $b) = $khach('POST', $base . '/chat/send', ['_token' => $tkK, 'body' => "$the tin 1", 'name' => 'ZZ Khach', 'phone' => '0909123456']);
$j = json_decode($b, true);
ok(!empty($j['ok']), 'Khach gui tin dau', mb_substr($b, 0, 150));
$cid = (int) $pdo->query("SELECT conversation_id FROM chat_messages WHERE body = " . $pdo->quote("$the tin 1"))->fetchColumn();

/* Nhan vien dang nhap (tai khoan thu local; mat khau lay tu hang MK_MAU). */
preg_match("~const MK_MAU = '([^']+)'~", file_get_contents($goc . 'tools/tao-du-lieu-gara-mau.php'), $mk);
list(, $b) = $nv('GET', $base . '/dang-nhap');
$tkDn = preg_match('~name="_token"[^>]*value="([^"]+)"~', $b, $m) ? $m[1] : '';
$nv('POST', $base . '/dang-nhap', ['_token' => $tkDn, 'email' => 'admin@gara-mau.test', 'password' => $mk[1] ?? '']);
list($c, $man) = $nv('GET', $base . "/admin/chat/view/$cid");
if ($c !== 200){ echo "\n[SKIP] Khong dang nhap duoc admin@gara-mau.test (HTTP $c).\n"; exit(summary()); }
$tkA = preg_match('~name="_token"[^>]*value="([^"]+)"~', $man, $m) ? $m[1] : '';
ok(strpos($man, "$the tin 1") !== false, 'Man Hoi thoai hien tin dau');

$hoi = function($after) use ($nv, $base, $cid){
    list($c, $b) = $nv('GET', $base . "/admin/chat/poll/$cid?after=$after", null, true);
    return [$c, json_decode($b, true), $b];
};
list($c, $j0, $raw) = $hoi(0);
ok($c === 200 && is_array($j0) && isset($j0['messages']), 'admin/chat/poll tra JSON', "HTTP $c " . mb_substr($raw, 0, 150));
$cuoi = 0; foreach ((array) ($j0['messages'] ?? []) as $x) $cuoi = max($cuoi, (int) $x['id']);

/* Khach gui them — nhan vien hoi tiep thi THAY, khong can tai lai trang. */
$khach('POST', $base . '/chat/send', ['_token' => $tkK, 'body' => "$the tin 2"]);
list($c, $j1) = $hoi($cuoi);
$moi = array_column((array) ($j1['messages'] ?? []), 'body');
ok($moi === ["$the tin 2"], 'Tin khach vua gui toi DUNG mot lan qua poll (khong F5)', json_encode($moi, JSON_UNESCAPED_UNICODE));
$cuoi = max($cuoi, ...array_map('intval', array_column((array) ($j1['messages'] ?? [['id' => 0]]), 'id')));
ok((int) $pdo->query("SELECT unread FROM chat_conversations WHERE id = $cid")->fetchColumn() === 0,
   'Dang mo hoi thoai thi tin moi coi nhu da doc');

// ---------------------------------------------------------------------------
section('Nhan vien tra loi bang AJAX — khach nhan qua poll');

list($c, $b) = $nv('POST', $base . "/admin/chat/reply/$cid", ['_token' => $tkA, 'body' => "$the tra loi"], true);
$jr = json_decode($b, true);
ok($c === 200 && !empty($jr['ok']) && !empty($jr['id']), 'Tra loi AJAX tra JSON (khong chuyen trang)', "HTTP $c " . mb_substr($b, 0, 150));
list(, $b) = $khach('GET', $base . '/chat/poll?after=0');
ok(in_array("$the tra loi", array_column((array) (json_decode($b, true)['messages'] ?? []), 'body'), true), 'Khach nhan duoc tra loi');
list($c, $jx) = $hoi($cuoi);
ok(in_array("$the tra loi", array_column((array) ($jx['messages'] ?? []), 'body'), true),
   'Poll nhan vien cung tra tin nhan vien (tab / may khac thay)');

/* Form thuong (khong AJAX) van chay nhu cu: chuyen ve man hoi thoai. */
list($c) = $nv('POST', $base . "/admin/chat/reply/$cid", ['_token' => $tkA, 'body' => "$the form thuong"]);
ok($c === 302, 'Gui form thuong (tat JS) van chuyen trang nhu cu', "HTTP $c");

// ---------------------------------------------------------------------------
section('Dang nhap… hai chieu');

list($c, $b) = $khach('POST', $base . '/chat/dang-go', ['_token' => $tkK]);
ok($c === 200, 'Khach bao dang go', "HTTP $c " . mb_substr($b, 0, 100));
list(, $jt) = $hoi(PHP_INT_MAX);
ok(!empty($jt['typing']), 'Nhan vien thay "Khách đang nhập…"');
$khach('POST', $base . '/chat/send', ['_token' => $tkK, 'body' => "$the tin 3"]);
list(, $jt) = $hoi(PHP_INT_MAX);
ok(empty($jt['typing']), 'Khach gui xong thi tat "dang nhap"');

list($c) = $nv('POST', $base . "/admin/chat/dang-go/$cid", ['_token' => $tkA], true);
ok($c === 200, 'Nhan vien bao dang go', "HTTP $c");
list(, $b) = $khach('GET', $base . '/chat/poll?after=' . PHP_INT_MAX);
ok(!empty(json_decode($b, true)['typing']), 'Khach thay "Nhân viên đang trả lời…"', mb_substr($b, 0, 150));
$pdo->exec("UPDATE chat_conversations SET staff_typing_at = " . $pdo->quote(date("Y-m-d H:i:s", time() - 30)) . " WHERE id = $cid");
list(, $b) = $khach('GET', $base . '/chat/poll?after=' . PHP_INT_MAX);
ok(empty(json_decode($b, true)['typing']), 'Ngung go vai giay thi tu tat');

// ---------------------------------------------------------------------------
section('Tu hoi KHONG gia han phien (phien van tu thoat sau 15 phut)');

$uid = (int) $pdo->query("SELECT id FROM users WHERE email = 'admin@gara-mau.test'")->fetchColumn();
$tok = (int) $pdo->query("SELECT id FROM login_tokens WHERE user_id = $uid ORDER BY id DESC LIMIT 1")->fetchColumn();
$pdo->exec("UPDATE login_tokens SET current_activity = DATE_SUB(NOW(), INTERVAL 5 MINUTE) WHERE id = $tok");
$cu = $pdo->query("SELECT current_activity FROM login_tokens WHERE id = $tok")->fetchColumn();
$hoi(0);
ok($pdo->query("SELECT current_activity FROM login_tokens WHERE id = $tok")->fetchColumn() === $cu,
   'Poll khong tinh la thao tac (khong day moc 15 phut)');
$nv('GET', $base . "/admin/chat/view/$cid");
ok($pdo->query("SELECT current_activity FROM login_tokens WHERE id = $tok")->fetchColumn() !== $cu,
   'Mo trang that van tinh la thao tac');

$k = $lam(tempnam(sys_get_temp_dir(), 'tnx'));
list($c) = $k('GET', $base . "/admin/chat/poll/$cid?after=0", null, true);
ok($c !== 200, 'Chua dang nhap / het phien: poll KHONG tra tin', "HTTP $c");

exit(summary());

<?php
/**
 * Test WEBCHAT SAU KHI TÁCH GARA.
 *
 * Chạy:  C:\xampp\php\php.exe tests\ChatTheoGaraTest.php
 *
 * LỖI THẬT 25/11/2026 — khách báo "cái chat cũng không chạy".
 *
 * Từ 07/10 (migration 000089) hội thoại chat có cột `garage_id` và model bật
 * `$_theoGara`: đọc ra thì chỉ thấy hội thoại của gara đang mở. Nhưng hàm tạo
 * hội thoại vẫn ghi bằng insert() thô, KHÔNG điền gara — dòng mới mang
 * `garage_id` NULL. Hệ quả, đúng thứ tự khách gặp:
 *
 *   1. Tin đầu tiên: báo gửi được (tin hiện trong khung chat).
 *   2. Admin mở Hỗ trợ / Chat: không thấy hội thoại nào (lọc theo gara).
 *   3. Khách không bao giờ thấy trả lời: poll tìm hội thoại theo gara, không ra.
 *   4. Tin thứ hai: lại không tìm thấy hội thoại nên tạo lần nữa -> đụng khoá
 *      duy nhất `session_key` -> lỗi, khung chat báo "Không gửi được".
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/ChatConversationsModel.php';
require_once __DIR__ . '/../app/models/ChatMessagesModel.php';

use App\core\Model;

echo 'PHP ' . PHP_VERSION . "\n";

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$donSach = function() use ($pdo){
    $pdo->exec("DELETE FROM chat_conversations WHERE session_key LIKE 'zzchat-%'");
    $ids = $pdo->query("SELECT DISTINCT conversation_id FROM chat_messages WHERE body LIKE 'zzchat-%'")
               ->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $c){
        $c = (int) $c;
        $pdo->exec("DELETE FROM chat_messages WHERE conversation_id = $c");
        $pdo->exec("DELETE FROM chat_conversations WHERE id = $c");
    }
};
$donSach();
register_shutdown_function(function() use ($donSach){ $donSach(); });

$idTong = (int) $pdo->query("SELECT id FROM garages WHERE is_master = 1 LIMIT 1")->fetchColumn();
$idKhac = (int) $pdo->query("SELECT id FROM garages WHERE is_master = 0 ORDER BY id LIMIT 1")->fetchColumn();

// ---------------------------------------------------------------------------
section('Tao hoi thoai phai GAN GARA dang mo');

$C   = new ChatConversationsModel();
$key = 'zzchat-' . bin2hex(random_bytes(6));
$cid = Model::trongGara($idTong, function() use ($C, $key){
    return (int) $C->create($key, null, 'ZZ Khach', '0909123456');
});
$g = $pdo->query("SELECT garage_id FROM chat_conversations WHERE id = $cid")->fetchColumn();
ok((int) $g === $idTong, 'Hoi thoai moi mang garage_id cua gara dang mo (khong NULL)',
   'garage_id = ' . var_export($g, true));

$thay = Model::trongGara($idTong, function() use ($C, $key){ return $C->findBySession($key); });
ok(!empty($thay) && (int) $thay['id'] === $cid,
   'Gui tin thu hai: tim lai DUNG hoi thoai cu (khong tao lan nua)');

$ds = Model::trongGara($idTong, function() use ($C){ return $C->getLists(); });
ok(in_array($cid, array_map('intval', array_column($ds, 'id')), true),
   'Hop thu admin cua gara do THAY hoi thoai');

if ($idKhac){
    $lot = Model::trongGara($idKhac, function() use ($C, $key){ return $C->findBySession($key); });
    ok(empty($lot), 'Gara khac KHONG thay hoi thoai nay');
}

// ---------------------------------------------------------------------------
section('Qua HTTP: khach gui 2 tin lien tiep, poll thay ca hai');

$base = web_goc();
$jar  = tempnam(sys_get_temp_dir(), 'zzchat');
register_shutdown_function(function() use ($jar){ @unlink($jar); });
$http = function($method, $url, $data = null) use ($jar){
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 20,
    ]);
    if ($method === 'POST'){
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query((array) $data));
    }
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, (string) $body];
};

list($code, $trang) = $http('GET', $base . '/');
$token = preg_match('~var TOKEN = "([^"]+)"~', $trang, $m) ? $m[1] : '';
ok($code === 200 && $token !== '', 'Trang chu co khung chat kem ma CSRF', "HTTP $code");

$the = 'zzchat-' . bin2hex(random_bytes(4));
list($c1, $b1) = $http('POST', $base . '/chat/send',
                       ['_token' => $token, 'body' => $the . ' tin 1', 'name' => 'ZZ Khach', 'phone' => '0909123456']);
$j1 = json_decode($b1, true);
ok(!empty($j1['ok']), 'Tin thu nhat gui duoc', "HTTP $c1 " . mb_substr($b1, 0, 200));

list($c2, $b2) = $http('POST', $base . '/chat/send', ['_token' => $token, 'body' => $the . ' tin 2']);
$j2 = json_decode($b2, true);
ok(!empty($j2['ok']), 'Tin thu hai CUNG gui duoc (truoc day do vi trung session_key)',
   "HTTP $c2 " . mb_substr(strip_tags($b2), 0, 200));

list($c3, $b3) = $http('GET', $base . '/chat/poll?after=0');
$j3 = json_decode($b3, true);
$noiDung = is_array($j3) && isset($j3['messages']) ? array_column($j3['messages'], 'body') : [];
ok(in_array($the . ' tin 1', $noiDung, true) && in_array($the . ' tin 2', $noiDung, true),
   'Poll tra ve ca hai tin (khach moi thay duoc tra loi)', mb_substr($b3, 0, 200));
ok(!empty($j3['hasInfo']), 'Poll biet khach da khai ten/SDT — khong hoi lai');

$gHttp = $pdo->query("SELECT c.garage_id FROM chat_conversations c JOIN chat_messages m ON m.conversation_id = c.id
                      WHERE m.body = " . $pdo->quote($the . ' tin 1') . " LIMIT 1")->fetchColumn();
ok($gHttp !== false && $gHttp !== null, 'Hoi thoai tao qua web co gara (admin moi thay)',
   'garage_id = ' . var_export($gHttp, true));
$soHt = (int) $pdo->query("SELECT COUNT(DISTINCT m.conversation_id) FROM chat_messages m
                           WHERE m.body LIKE " . $pdo->quote($the . '%'))->fetchColumn();
ok($soHt === 1, 'Hai tin nam chung MOT hoi thoai', "so hoi thoai: $soHt");

$donSach();
exit(summary());

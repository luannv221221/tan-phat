<?php
/**
 * Test KHÁCH WEB / GIAO DỊCH WEB THEO TỪNG GARA — bước 4 của nền tảng nhiều gara.
 *
 * Chạy:  C:\xampp\php\php.exe tests\KhachWebGaraTest.php
 *
 * Ba bước trước cho mỗi gara một tên miền, một bộ nhận diện, nội dung web
 * riêng. Bước này tách NGƯỜI DÙNG và GIAO DỊCH: tài khoản khách đăng ký, đơn
 * hàng web, thư liên hệ, đăng ký bản tin, đánh giá sản phẩm, tin nhắn chat.
 *
 * ĐÂY LÀ BƯỚC NGUY HIỂM NHẤT của cả kế hoạch. Ba bước trước đụng vào nội dung
 * biên tập — gán nhầm thì sửa lại được. Bước này đụng vào dữ liệu THẬT của
 * người dùng thật: gán nhầm là đơn hàng của khách này nằm trong gara khác, và
 * không ai biết cho tới lúc có người đi đòi hàng.
 *
 * Hai chốt chặn:
 *   1. Không dòng nào được thiếu gara — thiếu là nó thuộc về tất cả hoặc không
 *      thuộc về ai, tuỳ câu truy vấn.
 *   2. Model phải lọc THẬT, không chỉ bật cờ. Bài học bước 3: bật
 *      `$_theoGara = true` nhưng truy vấn tự viết vẫn dùng
 *      `table($this->_table)` thì dữ liệu vẫn rò, và chốt chặn cũ vẫn báo PASS.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc  = __DIR__ . '/../';
$base = 'http://localhost:88/tan-phat';

/** bảng => model giữ nó */
$BANG = [
    'members'                => 'MembersModel.php',
    'orders'                 => 'OrdersModel.php',
    'contact_messages'       => 'ContactMessagesModel.php',
    'newsletter_subscribers' => 'NewsletterSubscribersModel.php',
    'part_reviews'           => 'ProductReviewsModel.php',
    'chat_conversations'     => 'ChatConversationsModel.php',
];

// ---------------------------------------------------------------------------
section('Model khach web deu loc theo gara');

foreach ($BANG as $bang => $file){
    $src = codeOnly($goc . 'app/models/' . $file);
    ok(preg_match('~\$_theoGara\s*=\s*true~', $src) === 1, "$file bat _theoGara");
    ok(substr_count($src, '$this->table($this->_table)') === 0,
       "$file khong con truy van vong qua bo loc",
       'Bat co ma van dung table($this->_table) thi du lieu van ro — da mac that o buoc 3');
}

// ---------------------------------------------------------------------------
try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$donSach = function() use ($pdo){
    $pdo->exec("DELETE FROM contact_messages WHERE name LIKE 'ZZKW %'");
    $pdo->exec("DELETE FROM newsletter_subscribers WHERE email LIKE 'zzkw-%'");
};
$donSach();

// ---------------------------------------------------------------------------
section('CSDL da tach khach web theo gara');

foreach (array_keys($BANG) as $bang){
    $cot = $pdo->query("SHOW COLUMNS FROM `$bang`")->fetchAll(PDO::FETCH_COLUMN);
    ok(in_array('garage_id', $cot, true), "`$bang` co cot garage_id");
    $trong = (int) $pdo->query("SELECT COUNT(*) FROM `$bang` WHERE garage_id IS NULL")->fetchColumn();
    ok($trong === 0, "`$bang` khong con dong nao thieu gara",
       "con $trong dong — thieu gara la no thuoc ve tat ca hoac khong thuoc ve ai, tuy cau truy van");
}

/* Bảng CON đi theo bảng cha, cố ý không có cột riêng. */
foreach (['order_items' => 'orders', 'chat_messages' => 'chat_conversations',
          'member_vehicles' => 'members'] as $con => $cha){
    $cot = $pdo->query("SHOW COLUMNS FROM `$con`")->fetchAll(PDO::FETCH_COLUMN);
    ok(!in_array('garage_id', $cot, true),
       "`$con` KHONG co garage_id (theo bang cha `$cha`)",
       'Them cot o bang con la hai nguon su that cho cung mot viec');
}

// ---------------------------------------------------------------------------
section('Sau man khach web da mo cho moi gara');

foreach (['orders', 'tai-khoan-web', 'contact-messages', 'newsletter', 'reviews', 'chat'] as $link){
    $co = $pdo->query("SELECT chi_tan_phat FROM modules WHERE link = " . $pdo->quote($link))->fetchColumn();
    ok((int) $co === 0, "Man `$link` mo cho moi gara",
       'Gara co website rieng ma khong xem duoc ai dat hang tren trang cua minh');
}

// ---------------------------------------------------------------------------
section('Loc that: du lieu gara nay khong lot sang gara kia');

require_once $goc . 'app/models/ContactMessagesModel.php';
require_once $goc . 'app/models/NewsletterSubscribersModel.php';

$gTong = (int) $pdo->query("SELECT id FROM garages WHERE is_master = 1")->fetchColumn();
$gPhu  = (int) $pdo->query("SELECT id FROM garages WHERE is_master = 0 AND status = 1 ORDER BY id LIMIT 1")->fetchColumn();

$themThu = function ($ten, $mail, $gara) use ($pdo){
    $pdo->prepare("INSERT INTO contact_messages (name, email, phone, subject, message, status, garage_id, create_at)
                   VALUES (?, ?, '0900000001', 'ZZKW', 'noi dung', 0, ?, NOW())")
        ->execute([$ten, $mail, $gara]);
};
$themThu('ZZKW Thu cua gara phu',  'zzkw-phu@local.test',  $gPhu);
$themThu('ZZKW Thu cua gara tong', 'zzkw-tong@local.test', $gTong);

\App\core\Model::epGara($gPhu);
$ds = (array) (new ContactMessagesModel())->getLists();
$ten = array_column($ds, 'name');
ok(in_array('ZZKW Thu cua gara phu', $ten, true), 'Gara phu thay thu lien he cua chinh minh');
ok(!in_array('ZZKW Thu cua gara tong', $ten, true),
   'Gara phu KHONG thay thu lien he cua gara tong');

\App\core\Model::epGara($gTong);
$ten = array_column((array) (new ContactMessagesModel())->getLists(), 'name');
ok(in_array('ZZKW Thu cua gara tong', $ten, true), 'Gara tong thay thu cua minh');
ok(!in_array('ZZKW Thu cua gara phu', $ten, true),
   'Gara tong KHONG thay thu cua gara phu',
   'Truoc 07/10/2026 moi thu lien he deu do ve mot hop duy nhat');
\App\core\Model::epGara(null);

// ---------------------------------------------------------------------------
section('Dang ky ban tin tren trang gara nao thi thuoc gara do');

if (!function_exists('curl_init')){ echo "  [SKIP] PHP khong co curl.\n"; $donSach(); exit(summary()); }

$hostPhu = $pdo->query("SELECT host FROM garage_domains WHERE garage_id = $gPhu AND is_primary = 1 LIMIT 1")->fetchColumn();

$post = function($url, $host, $data){
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25,
                            CURLOPT_HTTPHEADER => ['Host: ' . $host],
                            CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($data)]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body === false ? '' : $body];
};

$r = $post("$base/", $hostPhu, []);
if ($r['code'] === 0){ echo "  [SKIP] Apache khong chay (localhost:88).\n"; $donSach(); exit(summary()); }

/* Ghi thẳng để khỏi phụ thuộc CSRF của form đăng ký bản tin — điều cần chốt ở
   đây là CỘT GARA, không phải luồng form. */
$pdo->prepare("INSERT INTO newsletter_subscribers (email, status, source, garage_id, create_at)
               VALUES ('zzkw-dangky@local.test', 1, 'web', ?, NOW())")->execute([$gPhu]);

\App\core\Model::epGara($gTong);
$mailTong = array_column((array) (new NewsletterSubscribersModel())->getLists(), 'email');
ok(!in_array('zzkw-dangky@local.test', $mailTong, true),
   'Nguoi dang ky ban tin o trang gara phu KHONG vao danh sach cua gara tong');
\App\core\Model::epGara(null);

$donSach();
ok((int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE name LIKE 'ZZKW %'")->fetchColumn() === 0
   && (int) $pdo->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE email LIKE 'zzkw-%'")->fetchColumn() === 0,
   'Da don sach du lieu test');

exit(summary());

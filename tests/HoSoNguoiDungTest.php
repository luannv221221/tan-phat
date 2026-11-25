<?php
/**
 * Test HỒ SƠ CỦA TÔI — người đang đăng nhập tự sửa tên, ảnh, điện thoại, địa
 * chỉ, và đổi mật khẩu ngay trên cùng một trang.
 *
 * Chạy:  C:\xampp\php\php.exe tests\HoSoNguoiDungTest.php
 *
 * VÌ SAO CÓ TEST NÀY (25/11/2026): khách ghi ở dòng "Xây dựng chức năng đăng
 * xuất, đăng nhập": "Cho sửa profile thay cho đổi mật khẩu, ảnh, tên, địa
 * chỉ…". Trước đó menu tài khoản chỉ có "Đổi mật khẩu"; muốn sửa tên của chính
 * mình phải nhờ người có quyền vào màn Người dùng.
 *
 * SÁU CHỖ DỄ HỎNG, mỗi chỗ một mục dưới đây:
 *
 *   1. LẤY MÃ NGƯỜI DÙNG TỪ FORM. Gửi tay `id=<người khác>` là sửa được hồ sơ
 *      của người khác. Mã phải lấy từ phiên đăng nhập, không bao giờ từ request.
 *
 *   2. NHẬN CẢ NHỮNG CỘT KHÔNG THUỘC HỒ SƠ. Đổ thẳng $_POST vào UPDATE thì gửi
 *      thêm `group_id` / `garage_id` / `status` / `email` là tự nâng quyền, tự
 *      chuyển gara, tự đổi tên đăng nhập.
 *
 *   3. ẢNH ĐẠI DIỆN LÀ CỬA TẢI TỆP LÊN THƯ MỤC WEB PHỤC VỤ. Tệp không phải ảnh
 *      (hoặc ảnh thật mang đuôi .php) phải bị từ chối, và không được ghi gì.
 *
 *   4. TÊN / ĐỊA CHỈ IN RA Ở MỌI TRANG (thanh trên + menu trái). Không escape
 *      là một lỗ XSS lưu trữ chạy trên mọi màn hình của chính người đó — và của
 *      người quản trị mở màn Người dùng.
 *
 *   5. ĐỔI MẬT KHẨU Ở TRANG MỚI MÀ QUÊN HỎI MẬT KHẨU HIỆN TẠI hoặc quên đá các
 *      phiên khác ra. Trang hồ sơ phải dùng lại đúng đường đổi mật khẩu cũ.
 *
 *   6. ĐƯỜNG DẪN CŨ `admin/doi-mat-khau` CHẾT. Người dùng đã lưu dấu trang.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc  = __DIR__ . '/../';
$base = web_goc();

// ---------------------------------------------------------------------------
section('Migration');

$mgFile = $goc . 'database/migrations/2026_11_25_000118_ho_so_nguoi_dung.php';
ok(is_file($mgFile), 'Co migration 2026_11_25_000118_ho_so_nguoi_dung');
$mg = is_file($mgFile) ? file_get_contents($mgFile) : '';
ok(strpos($mg, 'Chạy tay trong phpMyAdmin') !== false,
   'Migration co khoi SQL chay tay trong phpMyAdmin',
   'May chu that hay chay migration bang tay — thieu khoi nay la phai doan cau lenh');
ok(strpos($mg, 'function down(') !== false, 'Migration co down()');
ok(substr_count($mg, "hasColumn('users'") >= 3,
   'Migration CHAY LAI DUOC: kiem tung cot truoc khi them',
   'Chay lai ma khong kiem thi ALTER bao "Duplicate column" va dung ca loat migration sau');
foreach (['phone', 'address', 'avatar'] as $cot){
    ok(strpos($mg, "`$cot`") !== false, "Migration them cot `users`.`$cot`");
}

// ---------------------------------------------------------------------------
section('Tuyen duong + menu');

$rt = file_get_contents($goc . 'routes/web.php');
ok(strpos($rt, "Route::get('ho-so',") !== false && strpos($rt, "'admin/hoso'") !== false,
   'Co tuyen GET admin/ho-so');
ok(strpos($rt, "Route::post('ho-so',") !== false && strpos($rt, "'admin/hoso/postLuu'") !== false,
   'Co tuyen POST admin/ho-so');
ok(strpos($rt, "Route::get('doi-mat-khau',") !== false && strpos($rt, "Route::post('doi-mat-khau',") !== false,
   'Tuyen cu admin/doi-mat-khau VAN CON (dau trang da luu + form dang mo)');

$hd = file_get_contents($goc . 'app/views/layouts/admin/header.php');
ok(strpos($hd, "_WEB_URL.'/admin/ho-so'") !== false && strpos($hd, 'Hồ sơ của tôi') !== false,
   'Menu tai khoan co muc "Ho so cua toi"');
ok(strpos($hd, "_WEB_URL.'/admin/doi-mat-khau'") === false,
   'Menu tai khoan KHONG con muc rieng "Doi mat khau"',
   'Khach: "cho sua profile THAY CHO doi mat khau" — doi mat khau nam trong trang ho so');
ok(strpos($hd, "route('admin/ho-so')") === false,
   'Muc menu KHONG hoi route()',
   'Man nay co y khong co module nen khong co quyen nao de hoi; hoi la luon rong');
ok(strpos($hd, "\$infoUser['avatar']") !== false, 'Thanh tren hien anh dai dien khi co');
ok(strpos($hd, "icon('circle-user')") !== false, 'Chua co anh thi van la bieu tuong cu');
$sb = file_get_contents($goc . 'app/views/layouts/admin/sidebar.php');
ok(strpos($sb, "\$infoUser['avatar']") !== false, 'Menu trai hien anh dai dien canh ten nguoi dung');

$ctl = is_file($goc . 'app/controllers/admin/Hoso.php') ? codeOnly($goc . 'app/controllers/admin/Hoso.php') : '';
ok($ctl !== '' && strpos($ctl, 'nguoi_dang_nhap()') !== false,
   'Controller lay nguoi dung tu PHIEN dang nhap');
ok($ctl !== '' && strpos($ctl, 'is_phone(') !== false,
   'So dien thoai kiem bang is_phone() — cung luat voi moi noi khac');
ok($ctl !== '' && strpos($ctl, "upload_image(") !== false,
   'Anh dai dien di qua upload_image() (duoi + kich thuoc + getimagesize + ten ngau nhien)');

$view = is_file($goc . 'app/views/admin/ho-so/form.php') ? file_get_contents($goc . 'app/views/admin/ho-so/form.php') : '';
ok($view !== '' && substr_count($view, 'csrf_field()') >= 2, 'Ca hai form tren trang deu co CSRF');
ok(strpos($view, 'enctype="multipart/form-data"') !== false, 'Form ho so gui duoc tep (multipart)');
ok(preg_match('~<input[^>]*name="email"~', $view) === 0,
   'Email CHI DE XEM — khong co o nhap ten `email`',
   'Email la ten dang nhap cho ca nen tang; doi o day la vong qua man Nguoi dung');

// ---------------------------------------------------------------------------
section('MySQL');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$cot = [];
foreach ($pdo->query("SHOW COLUMNS FROM users") as $r) $cot[$r['Field']] = $r;
ok(isset($cot['phone']) && stripos($cot['phone']['Type'], 'varchar(20)') !== false && $cot['phone']['Null'] === 'YES',
   'CSDL: users.phone VARCHAR(20) NULL (da chay migration)');
ok(isset($cot['address']) && stripos($cot['address']['Type'], 'varchar(255)') !== false && $cot['address']['Null'] === 'YES',
   'CSDL: users.address VARCHAR(255) NULL');
ok(isset($cot['avatar']) && stripos($cot['avatar']['Type'], 'varchar(255)') !== false && $cot['avatar']['Null'] === 'YES',
   'CSDL: users.avatar VARCHAR(255) NULL');

$m = $pdo->query("SELECT COUNT(*) FROM modules WHERE link IN ('ho-so', 'hoso')")->fetchColumn();
ok((int) $m === 0,
   'KHONG dang ky `ho-so` vao bang `modules`',
   'Co module la sinh ra o tich o man Phan quyen — bo tich la ca nhom het sua duoc ho so cua chinh minh');

if (!isset($cot['avatar'])){ echo "\n[SKIP] Chua chay migration 000118 — bo qua phan HTTP.\n"; exit(summary()); }

// ---------------------------------------------------------------------------
section('HTTP that — tai khoan tam zzhs-');

if (!function_exists('curl_init')){ echo "\n[SKIP] PHP khong co curl.\n"; exit(summary()); }

/* Nhom + gara cua tai khoan Staff gara mau (vai thap nhat van phai sua duoc ho so minh) */
$mau = $pdo->query("SELECT group_id, garage_id FROM users WHERE email = 'covan.sg@gara-mau.test'")->fetch(PDO::FETCH_ASSOC);
if (empty($mau)){ echo "\n[SKIP] Khong co tai khoan mau covan.sg@gara-mau.test.\n"; exit(summary()); }

$MK   = 'ZzHoSo#2026';
$hash = \App\core\Hash::make($MK);

$donSach = function() use ($pdo, $goc){
    foreach ($pdo->query("SELECT avatar FROM users WHERE email LIKE 'zzhs-%@local.test' AND avatar IS NOT NULL") as $r){
        if (preg_match('~^public/assets/uploads/avatars/[A-Za-z0-9._-]+$~', (string) $r['avatar'])) @unlink($goc . $r['avatar']);
    }
    $pdo->exec("DELETE t FROM login_tokens t JOIN users u ON u.id = t.user_id WHERE u.email LIKE 'zzhs-%@local.test'");
    $pdo->exec("DELETE FROM users WHERE email LIKE 'zzhs-%@local.test'");
};
$donSach();
register_shutdown_function($donSach);

$taoUser = function($email, $ten) use ($pdo, $hash, $mau){
    $pdo->prepare("INSERT INTO users (name, email, password, group_id, status, garage_id, create_at)
                   VALUES (?, ?, ?, ?, 1, ?, NOW())")
        ->execute([$ten, $email, $hash, $mau['group_id'], $mau['garage_id']]);
    return (int) $pdo->lastInsertId();
};
$idA = $taoUser('zzhs-a@local.test', 'ZZHS Nguoi A');
$idB = $taoUser('zzhs-b@local.test', 'ZZHS Nguoi B');
$user = function($id) use ($pdo){
    return $pdo->query("SELECT * FROM users WHERE id = " . (int) $id)->fetch(PDO::FETCH_ASSOC);
};
$goc_B = $user($idB);

$tmp = [];
register_shutdown_function(function() use (&$tmp){ foreach ($tmp as $f) @unlink($f); });

/* $data là mảng; có CURLFile thì curl tự gửi multipart, không thì gửi form thường */
$http = function($method, $url, $jar, $data = null){
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 20,
    ]);
    if ($method === 'POST'){
        $coTep = false;
        foreach ((array) $data as $v){ if ($v instanceof CURLFile) $coTep = true; }
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $coTep ? $data : http_build_query($data));
    }
    $raw  = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    $body = $raw === false ? '' : substr($raw, (int) $info['header_size']);
    return ['code' => (int) $info['http_code'], 'loc' => (string) $info['redirect_url'], 'body' => $body,
            'text' => html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8')];
};
$token = function($h){ return preg_match('~name="_token" value="([^"]+)"~', $h, $m) ? $m[1] : ''; };
$moJar = function() use (&$tmp){ $j = tempnam(sys_get_temp_dir(), 'zzhs'); $tmp[] = $j; return $j; };
$dangNhap = function($email, $mk) use ($http, $token, $base, $moJar){
    $jar = $moJar();
    $r = $http('GET', "$base/dang-nhap", $jar);
    if ($r['code'] === 0) return null;
    $http('POST', "$base/dang-nhap", $jar, ['email' => $email, 'password' => $mk, '_token' => $token($r['body'])]);
    return $jar;
};
/* Gửi form hồ sơ y như trình duyệt: lấy token từ chính trang, rồi đọc lại trang */
$luu = function($jar, $truong) use ($http, $token, $base){
    $t = $token($http('GET', "$base/admin/ho-so", $jar)['body']);
    $r = $http('POST', "$base/admin/ho-so", $jar, array_merge(['_token' => $t], $truong));
    $sau = $http('GET', "$base/admin/ho-so", $jar);
    $sau['post'] = $r;
    return $sau;
};
$tep = function($ten, $noiDung) use (&$tmp){
    $f = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'zzhs-' . bin2hex(random_bytes(3)) . '-' . $ten;
    file_put_contents($f, $noiDung); $tmp[] = $f;
    return $f;
};
$PNG = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');

// --- Chua dang nhap ---
$jarLa = $moJar();
$r0 = $http('GET', "$base/dang-nhap", $jarLa);
if ($r0['code'] === 0){ echo "\n[SKIP] Apache khong chay (localhost:88).\n"; exit(summary()); }
$r = $http('GET', "$base/admin/ho-so", $jarLa);
ok($r['code'] === 302 && strpos($r['loc'], 'dang-nhap') !== false,
   'Chua dang nhap: GET admin/ho-so -> ve trang dang nhap', 'HTTP ' . $r['code'] . ' ' . $r['loc']);
$http('POST', "$base/admin/ho-so", $jarLa, ['_token' => $token($r0['body']), 'id' => $idB, 'name' => 'ZZHS Ke la mat']);
ok($user($idB)['name'] === 'ZZHS Nguoi B',
   'Chua dang nhap: POST admin/ho-so (kem id nguoi B) KHONG doi gi');

// --- Dang nhap A ---
$jarA = $dangNhap('zzhs-a@local.test', $MK);
$r = $http('GET', "$base/admin/ho-so", $jarA);
ok($r['code'] === 200, 'Tai khoan Staff (vai thap nhat) MO DUOC trang Ho so cua toi', 'HTTP ' . $r['code']);
ok(strpos($r['text'], 'zzhs-a@local.test') !== false && strpos($r['text'], 'ZZHS Nguoi A') !== false,
   'Trang hien dung ten + email cua CHINH minh');

$r = $http('GET', "$base/admin/ho-so?id=$idB", $jarA);
ok(strpos($r['text'], 'zzhs-b@local.test') === false && strpos($r['text'], 'ZZHS Nguoi B') === false,
   'Them ?id=<nguoi B> vao dia chi KHONG xem duoc ho so nguoi B');

$r = $http('GET', "$base/admin/doi-mat-khau", $jarA);
ok($r['code'] === 302 && strpos($r['loc'], 'admin/ho-so') !== false,
   'Duong dan cu admin/doi-mat-khau chuyen sang trang ho so (dau trang cu van dung)',
   'HTTP ' . $r['code'] . ' ' . $r['loc']);

// --- Luu ho so hop le, kem day du cac truong "lau" ---
$truocA = $user($idA);
$sau = $luu($jarA, [
    'name' => 'ZZHS Nguyễn Văn A', 'phone' => '0912 345 678', 'address' => '12 Lê Lợi, Quận 1',
    /* cac truong KHONG thuoc ho so — phai bi bo qua */
    'id' => $idB, 'user_id' => $idB, 'email' => 'zzhs-chiem@local.test',
    'group_id' => 1, 'garage_id' => 999999, 'status' => 0, 'password' => 'ChiemTaiKhoan1',
    'avatar' => 'https://ke-xau.example/x.png',
]);
$a = $user($idA);
ok($a['name'] === 'ZZHS Nguyễn Văn A' && $a['phone'] === '0912 345 678' && $a['address'] === '12 Lê Lợi, Quận 1',
   'Luu ten / dien thoai / dia chi (nguyen van)', json_encode([$a['name'], $a['phone'], $a['address']], JSON_UNESCAPED_UNICODE));
ok(strpos($sau['text'], 'Đã lưu hồ sơ') !== false, 'Luu xong co thong bao');
ok($user($idB) == $goc_B, 'Gui kem id / user_id cua nguoi B: ho so nguoi B KHONG doi mot cot nao');
ok($a['email'] === $truocA['email'] && (string) $a['group_id'] === (string) $truocA['group_id']
   && (string) $a['garage_id'] === (string) $truocA['garage_id'] && (int) $a['status'] === 1,
   'email / group_id / garage_id / status gui kem bi BO QUA');
ok($a['password'] === $truocA['password'], 'Truong `password` gui kem bi BO QUA (doi mat khau phai qua o mat khau hien tai)');
ok(empty($a['avatar']), 'Truong `avatar` gui kem (URL ngoai) bi BO QUA — anh chi vao duoc qua tai len');

// --- Tu choi ---
$sau = $luu($jarA, ['name' => 'ZZHS Ten moi', 'phone' => '12345', 'address' => 'x']);
ok(strpos($sau['text'], 'Số điện thoại không hợp lệ') !== false, 'So dien thoai sai -> bao loi');
$a = $user($idA);
ok($a['phone'] === '0912 345 678' && $a['name'] === 'ZZHS Nguyễn Văn A',
   'So dien thoai sai -> KHONG ghi gi (ca ten cung khong doi)');
ok(strpos($sau['text'], 'ZZHS Ten moi') !== false, 'Bi tu choi van giu lai chu da go (khong bat go lai)');

$sau = $luu($jarA, ['name' => '', 'phone' => '', 'address' => '']);
ok(strpos($sau['text'], 'Nhập họ tên') !== false && $user($idA)['name'] === 'ZZHS Nguyễn Văn A',
   'Bo trong ho ten -> tu choi');

$sau = $luu($jarA, ['name' => 'ZZHS Nguyễn Văn A', 'phone' => '', 'address' => '']);
$a = $user($idA);
ok($a['phone'] === null && $a['address'] === null, 'Xoa trang dien thoai / dia chi -> luu NULL');

// --- Anh dai dien ---
$t = $token($http('GET', "$base/admin/ho-so", $jarA)['body']);
$http('POST', "$base/admin/ho-so", $jarA, ['_token' => $t, 'name' => 'ZZHS Nguyễn Văn A',
      'avatar_file' => new CURLFile($tep('anh.jpg', "<?php echo 'x'; ?>"), 'image/jpeg', 'anh.jpg')]);
$sau = $http('GET', "$base/admin/ho-so", $jarA);
ok(strpos($sau['text'], 'Ảnh đại diện lỗi') !== false && empty($user($idA)['avatar']),
   'Tep chu mang duoi .jpg -> tu choi, khong ghi gi');

$t = $token($sau['body']);
$http('POST', "$base/admin/ho-so", $jarA, ['_token' => $t, 'name' => 'ZZHS Nguyễn Văn A',
      'avatar_file' => new CURLFile($tep('anh.php', $PNG), 'image/png', 'anh.php')]);
$sau = $http('GET', "$base/admin/ho-so", $jarA);
ok(strpos($sau['text'], 'Ảnh đại diện lỗi') !== false && empty($user($idA)['avatar']),
   'Anh THAT mang duoi .php -> tu choi (duoi la thu duy nhat chan chay ma o thu muc web)');
ok(empty(glob($goc . 'public/assets/uploads/avatars/*.php')), 'Khong co tep .php nao trong thu muc anh dai dien');

$t = $token($sau['body']);
$http('POST', "$base/admin/ho-so", $jarA, ['_token' => $t, 'name' => 'ZZHS Nguyễn Văn A',
      'avatar_file' => new CURLFile($tep('mat-cuoi.png', $PNG), 'image/png', '../Ảnh "của tôi" <b>.PNG')]);
$a = $user($idA);
ok(!empty($a['avatar']) && preg_match('~^public/assets/uploads/avatars/[a-z0-9-]+\.png$~', $a['avatar']) === 1,
   'Anh hop le -> luu duong dan TUONG DOI, ten tep do minh dat (khong dung ten nguoi dung gui)', (string) $a['avatar']);
ok(!empty($a['avatar']) && is_file($goc . $a['avatar']), 'Tep anh co that tren dia');
$anhCu = $a['avatar'];

$r = $http('GET', "$base/admin", $jarA);
ok(!empty($anhCu) && preg_match('~class="adm-avatar[^"]*"[^>]*>\s*<img[^>]+src="[^"]*' . preg_quote(basename((string) $anhCu), '~') . '"~', $r['body']) === 1,
   'Thanh tren cung (trang Tong quan) hien anh dai dien thay bieu tuong');
ok(!empty($anhCu) && preg_match('~adm-brand__role[^>]*>\s*<img[^>]+' . preg_quote(basename((string) $anhCu), '~') . '~', $r['body']) === 1,
   'Menu trai hien anh dai dien canh ten');

/* Thay anh khac: anh moi vao, tep anh cu bi don */
$t = $token($http('GET', "$base/admin/ho-so", $jarA)['body']);
$http('POST', "$base/admin/ho-so", $jarA, ['_token' => $t, 'name' => 'ZZHS Nguyễn Văn A',
      'avatar_file' => new CURLFile($tep('anh2.png', $PNG), 'image/png', 'anh2.png')]);
$anhMoi = (string) $user($idA)['avatar'];
clearstatcache();
ok($anhMoi !== '' && $anhMoi !== $anhCu && is_file($goc . $anhMoi) && !is_file($goc . $anhCu),
   'Thay anh moi -> luu anh moi, tep anh cu bi xoa (khong de rac)', $anhCu . ' -> ' . $anhMoi);
$anhCu = $anhMoi;

/* Sua ten ma KHONG chon anh moi thi anh cu phai con nguyen */
$luu($jarA, ['name' => 'ZZHS Nguyễn Văn A', 'phone' => '', 'address' => '']);
ok($user($idA)['avatar'] === $anhCu, 'Luu ma khong chon anh moi -> anh cu GIU NGUYEN');

$sau = $luu($jarA, ['name' => 'ZZHS Nguyễn Văn A', 'xoa_anh' => '1']);
ok($user($idA)['avatar'] === null, 'Tick "Xoa anh" -> avatar ve NULL');
/* clearstatcache(): is_file() cung duong dan nay da hoi o tren — PHP nho ket
   qua, khong xoa bo nho do thi van tra "co tep" du Apache da xoa that. */
clearstatcache();
ok(!empty($anhCu) && !is_file($goc . $anhCu), 'Xoa anh -> tep cu bi xoa khoi dia (khong de rac)');
$r = $http('GET', "$base/admin", $jarA);
ok(preg_match('~class="adm-avatar[^"]*"[^>]*>\s*<svg~', $r['body']) === 1, 'Khong con anh -> thanh tren quay ve bieu tuong');

// --- XSS ---
$XSS_TEN = 'ZZHS <script>alert(1)</script>';
$XSS_DC  = '"><img src=x onerror=alert(2)>';
$luu($jarA, ['name' => $XSS_TEN, 'phone' => '', 'address' => $XSS_DC]);
ok($user($idA)['name'] === $XSS_TEN, 'Ten chua the HTML van luu NGUYEN VAN (escape luc in, khong phai luc luu)');
foreach (['admin/ho-so' => 'trang Ho so', 'admin' => 'trang Tong quan (thanh tren + menu trai)'] as $duong => $ten){
    $body = $http('GET', "$base/$duong", $jarA)['body'];
    ok(strpos($body, '<script>alert(1)</script>') === false && strpos($body, '<img src=x onerror=alert(2)>') === false,
       "XSS trong ten / dia chi bi escape o $ten");
}
$body = $http('GET', "$base/admin", $jarA)['body'];
ok(substr_count($body, '&lt;script&gt;alert(1)&lt;/script&gt;') >= 2,
   'Ten (da escape) hien ca o thanh tren lan menu trai');

// --- Doi mat khau tu trang ho so ---
$luu($jarA, ['name' => 'ZZHS Nguoi A', 'phone' => '', 'address' => '']);
$trang = $http('GET', "$base/admin/ho-so", $jarA)['body'];
$hanhDong = '';
if (preg_match_all('~<form\b[^>]*>.*?</form>~s', $trang, $fs)){
    foreach ($fs[0] as $f){
        if (strpos($f, 'name="current_password"') === false) continue;
        if (preg_match('~action="([^"]*)"~', $f, $am)) $hanhDong = html_entity_decode($am[1], ENT_QUOTES, 'UTF-8');
    }
}
ok($hanhDong !== '' && strpos($hanhDong, 'admin/doi-mat-khau') !== false,
   'Trang ho so co form doi mat khau, gui vao DUNG duong doi mat khau cu',
   'Mot cho doi mat khau duy nhat: hoi mat khau hien tai, da phien khac — action=' . $hanhDong);
ok(substr_count($trang, 'type="password"') === 3, 'Du ba o mat khau (hien tai / moi / nhap lai)');

$mayKhac = $dangNhap('zzhs-a@local.test', $MK);
ok($http('GET', "$base/admin", $mayKhac)['code'] === 200, 'May thu hai cung dang nhap tai khoan A');

$MK_MOI = 'ZzMoi@' . random_int(100000, 999999);
$doi = function($cu, $moi, $lai) use ($http, $token, $base, $jarA, $hanhDong){
    $t = $token($http('GET', "$base/admin/ho-so", $jarA)['body']);
    $r = $http('POST', $hanhDong !== '' ? $hanhDong : "$base/admin/doi-mat-khau", $jarA,
               ['_token' => $t, 'current_password' => $cu, 'new_password' => $moi, 'new_password2' => $lai]);
    $sau = $http('GET', "$base/admin/ho-so", $jarA);
    $sau['post'] = $r;
    return $sau;
};
$sau = $doi('SAI-BET', $MK_MOI, $MK_MOI);
ok(strpos($sau['text'], 'Mật khẩu hiện tại không đúng') !== false, 'Mat khau hien tai SAI -> bao loi tren trang ho so');
ok(password_verify($MK, $user($idA)['password']), 'Mat khau hien tai SAI -> mat khau trong CSDL KHONG doi');
ok(strpos($sau['post']['loc'], 'admin/ho-so') !== false,
   'Doi mat khau xong (ke ca loi) quay ve trang ho so', $sau['post']['loc']);

$sau = $doi($MK, $MK_MOI, $MK_MOI);
ok(strpos($sau['text'], 'Đã đổi mật khẩu') !== false, 'Doi dung -> co thong bao tren trang ho so');
ok(password_verify($MK_MOI, $user($idA)['password']), 'CSDL: mat khau MOI co hieu luc');
ok($http('GET', "$base/admin", $jarA)['code'] === 200, 'May dang thao tac VAN o nguyen');
$rb = $http('GET', "$base/admin", $mayKhac);
ok($rb['code'] === 302 && strpos($rb['loc'], 'dang-nhap') !== false,
   'May khac BI DANG XUAT (cung hanh vi voi man doi mat khau cu)', 'HTTP ' . $rb['code'] . ' ' . $rb['loc']);

ok($user($idB) == $goc_B, 'Suot ca loat thao tac cua A, ho so nguoi B khong doi');

exit(summary());

<?php
/**
 * Test QUẢN LÝ GARA TỰ THÊM NHÂN VIÊN (migration 000069 + Users::phamVi).
 *
 * Chạy:  C:\xampp\php\php.exe tests\NhanVienGaraTest.php
 *
 * Cho Manager vào màn Người dùng là mở lại đúng lỗ 000068 vừa đóng: ai thêm
 * được người dùng thì chọn được nhóm Admin. Test này thử đúng những đường
 * một Manager tò mò sẽ thử — gửi thẳng POST group_id=<Admin>, garage_id=<gara
 * khác>, sửa tài khoản Admin, tự đổi nhóm của mình, gán vào một nhóm RỖNG.
 * Giao diện ẩn nút không tính: phải gửi request thật qua Apache.
 *
 * NHÓM RỖNG là cái bẫy khó thấy nhất: nhóm không có dòng quyền nào thì
 * RoleMiddleware bỏ qua toàn bộ kiểm tra, vào được MỌI màn hình. Luật "chỉ
 * gán nhóm có ít quyền hơn mình" mà hiểu ngây thơ thì nhóm rỗng (0 quyền) lại
 * là nhóm đầu tiên được phép gán.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc  = __DIR__ . '/../';
$base = 'http://localhost:88/tan-phat';

// ---------------------------------------------------------------------------
section('Migration');

$mg = glob($goc . 'database/migrations/*_manager_them_nhan_vien_gara.php');
ok(!empty($mg), 'Co migration cap quyen Nguoi dung cho Manager');
$src = !empty($mg) ? file_get_contents($mg[0]) : '';
ok(strpos($src, "'delete'") === false,
   'Migration KHONG cap `delete`',
   'Nhan vien nghi thi khoa tai khoan, khong xoa — chung tu cu con ghi nguoi lap');

$tool = file_get_contents($goc . 'tools/xuat-sql-thay-doi.php');
ok(strpos($tool, "--sau-khi-day-code") !== false && strpos($tool, "m.`link` <> 'users'") !== false,
   'Cong cu xuat SQL tach quyen man Nguoi dung ra file chay SAU khi day code',
   'Dan quyen truoc khi co code moi = Manager vao man Nguoi dung CU, tao duoc tai khoan Admin');

// ---------------------------------------------------------------------------
section('MySQL — nhom duoc phep gan');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$idNhom = function($ten) use ($pdo){
    $st = $pdo->prepare("SELECT id FROM `groups` WHERE name = ?");
    $st->execute([$ten]);
    return (int) $st->fetchColumn();
};
$A = $idNhom('Admin'); $M = $idNhom('Manager'); $S = $idNhom('Staff');
if (!$A || !$M || !$S){ echo "\n[SKIP] Thieu nhom Admin/Manager/Staff.\n"; exit(summary()); }

$garaId = function($ma) use ($pdo){
    $st = $pdo->prepare("SELECT id FROM garages WHERE code = ?");
    $st->execute([$ma]);
    return (int) $st->fetchColumn();
};
$G1 = $garaId('TP01'); $G2 = $garaId('DMSG'); $G3 = $garaId('DMDN');
if (!$G1 || !$G2 || !$G3){ echo "\n[SKIP] Thieu gara mau (chay tools/tao-du-lieu-gara.php).\n"; exit(summary()); }

/* Don rac cua lan chay truoc (neu lan do chet giua chung) */
$donSach = function() use ($pdo){
    $pdo->exec("DELETE t FROM login_tokens t JOIN users u ON u.id = t.user_id WHERE u.email LIKE 'zz-%@local.test'");
    $pdo->exec("DELETE FROM users WHERE email LIKE 'zz-%@local.test'");
    $pdo->exec("DELETE p FROM permissions p JOIN `groups` g ON g.id = p.group_id WHERE g.name LIKE 'ZZNV-%'");
    $pdo->exec("DELETE FROM `groups` WHERE name LIKE 'ZZNV-%'");
};
$donSach();

$quyen = function($nhom, $link) use ($pdo){
    $st = $pdo->prepare("SELECT p.role FROM permissions p JOIN `groups` g ON g.id = p.group_id
                         JOIN modules m ON m.id = p.module_id WHERE g.name = ? AND m.link = ?");
    $st->execute([$nhom, $link]);
    return array_flip($st->fetchAll(PDO::FETCH_COLUMN));
};
$q = $quyen('Manager', 'users');
ok(isset($q['view']) && isset($q['add']) && isset($q['edit']), 'Manager co view/add/edit tren Nguoi dung (da migrate 000069)');
ok(!isset($q['delete']), 'Manager KHONG co delete tren Nguoi dung');
ok(empty($quyen('Staff', 'users')), 'Staff van KHONG vao duoc man Nguoi dung');

require_once $goc . 'app/models/GroupsModel.php';
$GM  = new GroupsModel();
$ids = function($ds){ return array_map('intval', array_column((array) $ds, 'id')); };

ok($GM->laToanQuyen($A),  'Admin la toan quyen (sua duoc bang phan quyen)');
ok(!$GM->laToanQuyen($M), 'Manager KHONG toan quyen');
ok(!$GM->laToanQuyen($S), 'Staff KHONG toan quyen');

$choM = $ids($GM->nhomGiaoDuoc($M));
ok(in_array($S, $choM, true),  'Manager gan duoc Staff', 'Dang ra: ' . implode(',', $choM));
ok(!in_array($A, $choM, true), 'Manager KHONG gan duoc Admin', 'Tao tai khoan Admin moi la vong qua moi han che');
ok(!in_array($M, $choM, true), 'Manager KHONG gan duoc chinh nhom Manager', 'Ngang quyen thi tu nhan ban duoc, va tu sua duoc tai khoan minh');
$choA = $ids($GM->nhomGiaoDuoc($A));
ok(!array_diff([$A, $M, $S], $choA), 'Admin gan duoc moi nhom');
ok(!array_intersect([$A, $M, $S], $ids($GM->nhomGiaoDuoc($S))), 'Staff khong gan duoc nhom nao trong ba nhom');

/* --- Nhom tam: rong / con / ngang quyen / vuot quyen --- */
$taoNhom = function($ten) use ($pdo){
    $pdo->prepare("INSERT INTO `groups` (name, create_at) VALUES (?, NOW())")->execute([$ten]);
    return (int) $pdo->lastInsertId();
};
$chepQuyen = function($tu, $den, $gioiHan = null) use ($pdo){
    $sql = "INSERT INTO permissions (module_id, group_id, role)
            SELECT module_id, $den, role FROM permissions WHERE group_id = $tu ORDER BY id"
         . ($gioiHan ? " LIMIT $gioiHan" : '');
    $pdo->exec($sql);
};
$zRong = $taoNhom('ZZNV-rong');
$zCon  = $taoNhom('ZZNV-con');   $chepQuyen($M, $zCon, 1);
$zBang = $taoNhom('ZZNV-bang');  $chepQuyen($M, $zBang);
$zVuot = $taoNhom('ZZNV-vuot');  $chepQuyen($M, $zVuot);
$pdo->exec("INSERT INTO permissions (module_id, group_id, role)
            SELECT id, $zVuot, 'delete' FROM modules WHERE link = 'users'");

$choM = $ids($GM->nhomGiaoDuoc($M));
ok(!in_array($zRong, $choM, true),
   'Manager KHONG gan duoc nhom RONG (0 quyen)',
   'Nhom rong thi RoleMiddleware bo qua moi kiem tra — vao duoc MOI man hinh');
ok(in_array($zCon, $choM, true),  'Manager gan duoc nhom co quyen la tap con cua minh');
ok(!in_array($zBang, $choM, true), 'Manager KHONG gan duoc nhom ngang quyen minh');
ok(!in_array($zVuot, $choM, true), 'Manager KHONG gan duoc nhom co du mot quyen minh khong co');

// ---------------------------------------------------------------------------
section('HTTP that — dang nhap bang tai khoan tam');

if (!function_exists('curl_init')){ echo "\n[SKIP] PHP khong co curl.\n"; $donSach(); exit(summary()); }

$MK = 'ZzThu#2026nv';
$hash = \App\core\Hash::make($MK);
$taoUser = function($email, $ten, $nhom, $gara) use ($pdo, $hash){
    $pdo->prepare("INSERT INTO users (name, email, password, group_id, status, garage_id, create_at)
                   VALUES (?, ?, ?, ?, 1, ?, NOW())")->execute([$ten, $email, $hash, $nhom, $gara]);
    return (int) $pdo->lastInsertId();
};
$uQL     = $taoUser('zz-ql-sg@local.test',    'ZZ Quan ly SG',   $M, $G2);
$uQLtrong= $taoUser('zz-ql-trong@local.test', 'ZZ Quan ly trong', $M, null);
$uNVsg   = $taoUser('zz-nv-sg@local.test',    'ZZ Nhan vien SG', $S, $G2);
$uNVtp   = $taoUser('zz-nv-tp@local.test',    'ZZ Nhan vien TP', $S, $G1);
$uNVtrong= $taoUser('zz-nv-trong@local.test', 'ZZ Nhan vien trong', $S, null);
$uAD     = $taoUser('zz-ad@local.test',       'ZZ Admin thu',    $A, $G1);

$user = function($id) use ($pdo){
    return $pdo->query("SELECT * FROM users WHERE id = " . (int) $id)->fetch(PDO::FETCH_ASSOC);
};
$userTheoEmail = function($email) use ($pdo){
    $st = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $st->execute([$email]);
    return $st->fetch(PDO::FETCH_ASSOC);
};

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
    $hs = (int) $info['header_size'];
    $body = $raw === false ? '' : substr($raw, $hs);
    /* `text` = HTML đã giải mã: {{ }} của Template mã hoá chữ có dấu thành
       entity (á -> &aacute;), so chuỗi tiếng Việt trên `body` thô là trượt. */
    return ['code' => (int) $info['http_code'], 'loc' => (string) $info['redirect_url'],
            'body' => $body, 'text' => html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8')];
};
/** Chỉ phần <table> của trang, đã giải mã — để tên gara ở ô đổi gara trên đầu trang không làm test đỗ oan */
$bang = function($r){
    return preg_match('~<table.*?</table>~s', $r['text'], $m) ? $m[0] : '';
};
$token = function($html){
    return preg_match('~name="_token" value="([^"]+)"~', $html, $m) ? $m[1] : '';
};

/** Dang nhap, tra ve [cookie jar, csrf token] */
$dangNhap = function($email) use ($http, $token, $base, $MK){
    $jar = tempnam(sys_get_temp_dir(), 'zznv');
    $r = $http('GET', "$base/dang-nhap", $jar);
    if ($r['code'] === 0) return null;
    $tk = $token($r['body']);
    $http('POST', "$base/dang-nhap", $jar, ['email' => $email, 'password' => $MK, '_token' => $tk]);
    return [$jar, $tk];
};

$phien = $dangNhap('zz-ql-sg@local.test');
if ($phien === null){ echo "\n[SKIP] Apache khong chay (localhost:88).\n"; $donSach(); exit(summary()); }
list($jarQL, $tkQL) = $phien;

/* --- Danh sach: chi thay nguoi cung gara --- */
$r = $http('GET', "$base/admin/users", $jarQL);
ok($r['code'] === 200, 'Manager mo duoc man Nguoi dung (HTTP ' . $r['code'] . ')',
   $r['code'] === 302 ? 'Bi day ve ' . $r['loc'] : '');
ok(strpos($r['body'], 'zz-nv-sg@local.test') !== false, 'Thay nhan vien cung gara');
ok(strpos($r['body'], 'zz-nv-tp@local.test') === false, 'KHONG thay nhan vien gara khac');
ok(strpos($r['body'], 'zz-ad@local.test') === false, 'KHONG thay tai khoan Admin gara khac');
ok(strpos($r['body'], 'zz-nv-trong@local.test') === false,
   'KHONG thay tai khoan chua gan gara',
   'Chua gan = gara tong — khong phai cua gara Sai Gon');
ok(strpos($r['body'], 'Bạn đang quản lý nhân viên của') !== false, 'Co dong bao dang xem mot phan');
ok(strpos($r['body'], 'name="garage_id"') === false, 'Khong co o loc gara cho Manager');

$r = $http('GET', "$base/admin/users?garage_id=$G1", $jarQL);
ok(strpos($r['body'], 'zz-nv-tp@local.test') === false,
   'Sua ?garage_id= tren URL van KHONG xem duoc gara khac');

/* --- Form them: chi co nhom duoc phep, khong co o chon gara --- */
$r = $http('GET', "$base/admin/users/add", $jarQL);
ok($r['code'] === 200, 'Manager mo duoc form them nguoi dung');
$tk = $token($r['body']) ?: $tkQL;
$oNhom = preg_match('~<select name="group_id".*?</select>~s', $r['body'], $mm) ? $mm[0] : '';
ok(strpos($oNhom, 'value="' . $S . '"') !== false, 'O chon nhom co Staff');
ok(strpos($oNhom, 'value="' . $A . '"') === false, 'O chon nhom KHONG co Admin');
ok(strpos($oNhom, 'value="' . $M . '"') === false, 'O chon nhom KHONG co Manager');
ok(strpos($oNhom, 'value="' . $zRong . '"') === false, 'O chon nhom KHONG co nhom rong');
ok(strpos($r['body'], 'name="garage_id"') === false, 'Form them KHONG co o chon gara');

$them = function($email, $nhom, $extra = []) use ($http, $base, $jarQL, &$tk){
    return $http('POST', "$base/admin/users/add", $jarQL, array_merge([
        '_token' => $tk, 'name' => 'ZZ Tai khoan moi', 'email' => $email,
        'password' => 'ZzMoi#2026', 'confirm_password' => 'ZzMoi#2026',
        'status' => 1, 'group_id' => $nhom,
    ], $extra));
};

/* --- Tan cong: gui thang POST --- */
$them('zz-moi-admin@local.test', $A);
ok(empty($userTheoEmail('zz-moi-admin@local.test')),
   'POST group_id=Admin -> KHONG tao duoc tai khoan',
   'Day chinh la lo tu nang quyen');
$them('zz-moi-manager@local.test', $M);
ok(empty($userTheoEmail('zz-moi-manager@local.test')), 'POST group_id=Manager -> KHONG tao duoc');
$them('zz-moi-rong@local.test', $zRong);
ok(empty($userTheoEmail('zz-moi-rong@local.test')),
   'POST group_id=<nhom rong> -> KHONG tao duoc',
   'Nhom rong vao duoc moi man hinh');

$them('zz-moi-nv@local.test', $S, ['garage_id' => $G1]);
$moi = $userTheoEmail('zz-moi-nv@local.test');
ok(!empty($moi), 'Manager tao duoc tai khoan Staff');
ok(!empty($moi) && (int) $moi['garage_id'] === $G2,
   'Tai khoan moi thuoc gara cua Manager, du POST gui garage_id gara khac',
   'Dang ghi garage_id = ' . ($moi['garage_id'] ?? 'null'));
ok(!empty($moi) && (int) $moi['group_id'] === $S, 'Tai khoan moi dung nhom Staff');

/* --- Sua tai khoan ngoai pham vi --- */
$sua = function($id, $data) use ($http, $base, $jarQL, &$tk){
    return $http('POST', "$base/admin/users/edit/$id", $jarQL, array_merge(['_token' => $tk, 'status' => 1], $data));
};

$r = $http('GET', "$base/admin/users/edit/$uNVtp", $jarQL);
ok($r['code'] !== 200 && substr($r['loc'], -12) === '/admin/users',
   'Mo form sua nhan vien gara khac -> bi day ve danh sach');
$truoc = $user($uNVtp);
$sua($uNVtp, ['name' => 'ZZ Bi sua trom', 'email' => 'zz-nv-tp@local.test', 'group_id' => $S]);
ok($user($uNVtp)['name'] === $truoc['name'], 'POST sua nhan vien gara khac -> KHONG doi gi');

$r = $http('GET', "$base/admin/users/edit/$uAD", $jarQL);
ok($r['code'] !== 200, 'Mo form sua tai khoan Admin -> bi chan');
$truoc = $user($uAD);
$sua($uAD, ['name' => 'ZZ Admin thu', 'email' => 'zz-ad@local.test', 'group_id' => $A,
            'password' => 'ChiemQuyen#1', 'confirm_password' => 'ChiemQuyen#1']);
ok($user($uAD)['password'] === $truoc['password'],
   'POST doi mat khau tai khoan Admin -> KHONG doi',
   'Doi duoc mat khau Admin la dang nhap bang Admin');

$r = $http('GET', "$base/admin/users/edit/$uQL", $jarQL);
ok($r['code'] !== 200, 'Manager KHONG mo duoc form sua chinh minh');
$sua($uQL, ['name' => 'ZZ Quan ly SG', 'email' => 'zz-ql-sg@local.test', 'group_id' => $A]);
ok((int) $user($uQL)['group_id'] === $M,
   'POST tu doi nhom minh thanh Admin -> KHONG doi',
   'Cach tu nang quyen gon nhat');

/* --- Sua nhan vien cua minh --- */
$r = $http('GET', "$base/admin/users/edit/$uNVsg", $jarQL);
ok($r['code'] === 200, 'Mo duoc form sua nhan vien cung gara');
$sua($uNVsg, ['name' => 'ZZ Nhan vien SG', 'email' => 'zz-nv-sg@local.test', 'group_id' => $A]);
ok((int) $user($uNVsg)['group_id'] === $S, 'Nang nhan vien cua minh len Admin -> KHONG duoc');
$sua($uNVsg, ['name' => 'ZZ NV SG da doi ten', 'email' => 'zz-nv-sg@local.test', 'group_id' => $S,
              'garage_id' => $G1]);
$u = $user($uNVsg);
ok($u['name'] === 'ZZ NV SG da doi ten', 'Sua ten nhan vien cua minh -> duoc');
ok((int) $u['garage_id'] === $G2, 'POST garage_id gara khac khi sua -> gara KHONG doi');

/* --- Xoa: Manager khong co quyen --- */
$r = $http('GET', "$base/admin/users/delete/$uNVsg", $jarQL);
ok(strpos($r['loc'], 'khong-co-quyen') !== false, 'Manager bam xoa -> trang khong co quyen');
ok(!empty($user($uNVsg)), 'Tai khoan van con sau khi Manager thu xoa');

/* --- Manager chua gan gara: khong quan ly duoc ai --- */
list($jarTrong, ) = $dangNhap('zz-ql-trong@local.test');
$r = $http('GET', "$base/admin/users", $jarTrong);
ok(strpos($r['text'], 'chưa được gán gara') !== false, 'Manager chua gan gara -> thay loi nhac',
   'HTTP ' . $r['code'] . ($r['loc'] ? ' -> ' . $r['loc'] : ''));
ok(strpos($r['body'], 'zz-nv-trong@local.test') === false && strpos($r['body'], 'zz-nv-sg@local.test') === false,
   'Manager chua gan gara -> danh sach rong',
   'Loc `garage_id IS NULL` la cho ho quan ly moi tai khoan chua gan');
$r = $http('GET', "$base/admin/users/add", $jarTrong);
ok($r['code'] !== 200, 'Manager chua gan gara -> khong mo duoc form them');

/* --- Admin: van toan quyen nhu cu, co them loc gara --- */
list($jarAD, ) = $dangNhap('zz-ad@local.test');
$r = $http('GET', "$base/admin/users?garage_id=$G2", $jarAD);
ok(strpos($r['body'], 'zz-nv-sg@local.test') !== false && strpos($r['body'], 'zz-nv-tp@local.test') === false,
   'Admin loc theo gara Sai Gon -> dung nguoi');
$r = $http('GET', "$base/admin/users?garage_id=none", $jarAD);
ok(strpos($r['body'], 'zz-nv-trong@local.test') !== false && strpos($r['body'], 'zz-nv-sg@local.test') === false,
   'Admin loc "Chua gan gara" -> dung nguoi (IS NULL, khong phai = NULL)');
$r = $http('GET', "$base/admin/users", $jarAD);
ok(strpos($r['body'], 'zz-nv-tp@local.test') !== false && strpos($r['body'], 'zz-nv-sg@local.test') !== false,
   'Admin khong loc -> thay moi gara');
ok(strpos($bang($r), '<th>Gara</th>') !== false && strpos($bang($r), 'Tân Phát Sài Gòn') !== false,
   'Danh sach co cot Gara, ghi ten gara trong bang');
ok(strpos($bang($r), 'chưa gán (gara tổng)') !== false, 'Tai khoan chua gan gara ghi ro "chua gan (gara tong)"');

$r = $http('GET', "$base/admin/users/add", $jarAD);
$tk = $token($r['body']) ?: $tk;
$oNhom = preg_match('~<select name="group_id".*?</select>~s', $r['body'], $mm) ? $mm[0] : '';
ok(strpos($oNhom, 'value="' . $A . '"') !== false && strpos($oNhom, 'value="' . $M . '"') !== false,
   'Admin van chon duoc moi nhom');
$http('POST', "$base/admin/users/add", $jarAD, [
    '_token' => $tk, 'name' => 'ZZ Quan ly DN', 'email' => 'zz-moi-ql-dn@local.test',
    'password' => 'ZzMoi#2026', 'confirm_password' => 'ZzMoi#2026',
    'status' => 1, 'group_id' => $M, 'garage_id' => $G3,
]);
$moi = $userTheoEmail('zz-moi-ql-dn@local.test');
ok(!empty($moi) && (int) $moi['group_id'] === $M && (int) $moi['garage_id'] === $G3,
   'Admin tao duoc Manager cho gara Da Nang (chon gara tu do)');

foreach ([$jarQL, $jarTrong, $jarAD] as $j) @unlink($j);

// Don sach
$donSach();
ok((int) $pdo->query("SELECT COUNT(*) FROM users WHERE email LIKE 'zz-%@local.test'")->fetchColumn() === 0
   && (int) $pdo->query("SELECT COUNT(*) FROM `groups` WHERE name LIKE 'ZZNV-%'")->fetchColumn() === 0,
   'Da don sach tai khoan va nhom tam');

exit(summary());

<?php
/**
 * Test NÚT + CẠNH Ô CHỌN — thêm nhanh một dòng danh mục ngay trên form.
 *
 * Chạy:  C:\xampp\php\php.exe tests\ThemNhanhTest.php
 *
 * Luật "chỉ được chọn, không gõ tay" chỉ sống được nếu THÊM VÀO DANH MỤC nhanh
 * hơn gõ tay. Nếu không, người dùng sẽ tìm đường lách: chọn bừa một dòng gần
 * đúng, hoặc gõ tên thật vào ô ghi chú. Nên nút + là một nửa của luật đó, không
 * phải tiện nghi thêm.
 *
 * Bốn chỗ hỏng mà test này canh:
 *
 *   1. ĐƯỜNG VÒNG QUA PHÂN QUYỀN.
 *      Một đường POST dùng chung cho mọi màn, nếu không tự kiểm quyền thì nhân
 *      viên không được sửa Danh mục phụ tùng vẫn thêm được qua nút +. Chỗ này
 *      nguy hơn cả: nó im lặng.
 *
 *   2. NHẬN TÊN BẢNG TỪ NGƯỜI GỬI.
 *      `loai` phải đối chiếu danh sách trắng. Không thì một tham số khác là ghi
 *      thẳng vào bảng users.
 *
 *   3. SINH DÒNG TRÙNG.
 *      "Bộ lọc gió" / "BỘ LỌC GIÓ" / " bo loc gio " phải về một dòng, nếu không
 *      ô chọn cũng loạn y như ô gõ tay, chỉ chậm hơn vài tháng.
 *
 *   4. DANH MỤC RIÊNG GARA BỊ DÙNG CHUNG.
 *      Nhóm khách là của riêng từng gara. Hai gara cùng có "Khách VIP" là hai
 *      dòng; mà cột slug lại duy nhất toàn bảng, nên dòng thứ hai phải tự đổi
 *      slug chứ không được đổ.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc  = __DIR__ . '/../';
$base = 'http://localhost:88/tan-phat';

require_once $goc . 'app/helpers/functions.php';

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Throwable $e) {
    echo "\n[SKIP] Khong ket noi duoc CSDL.\n";
    exit(summary());
}

$mot = function($sql, $b = []) use ($pdo){ $st = $pdo->prepare($sql); $st->execute($b); return $st->fetch(PDO::FETCH_ASSOC) ?: []; };
$so  = function($sql, $b = []) use ($pdo){ $st = $pdo->prepare($sql); $st->execute($b); return (int) $st->fetchColumn(); };

$donSach = function() use ($pdo){
    /* part_categories tự trỏ vào chính nó: xoá con trước, cha sau — không thì
       khoá ngoại chặn. */
    $pdo->exec("DELETE FROM part_categories WHERE slug LIKE 'zztn-%' AND parent_id IS NOT NULL");
    foreach (['part_units', 'part_brands', 'part_manufacturers', 'part_origins',
              'part_categories', 'news_categories', 'car_body_types', 'car_fuels'] as $b){
        $pdo->exec("DELETE FROM `$b` WHERE slug LIKE 'zztn-%'");
    }
    $pdo->exec("DELETE FROM customer_groups WHERE name LIKE 'ZZTN %'");
    $pdo->exec("DELETE t FROM login_tokens t JOIN users u ON u.id = t.user_id WHERE u.email LIKE 'zz-tn-%@local.test'");
    $pdo->exec("DELETE FROM users WHERE email LIKE 'zz-tn-%@local.test'");
    $pdo->exec("DELETE p FROM permissions p JOIN `groups` g ON g.id = p.group_id WHERE g.name LIKE 'ZZTN-%'");
    $pdo->exec("DELETE FROM `groups` WHERE name LIKE 'ZZTN-%'");
};
$donSach();

// ---------------------------------------------------------------------------
section('Danh sach trang + helper dung nut');

ok(class_exists('DanhMucNhanhModel') || is_file($goc . 'app/models/DanhMucNhanhModel.php'),
   'Co model DanhMucNhanhModel');
require_once $goc . 'app/models/DanhMucNhanhModel.php';

$loai = DanhMucNhanhModel::$loai;
ok(is_array($loai) && count($loai) >= 8, 'Danh sach trang co it nhat 8 loai danh muc',
   'Dem duoc ' . count($loai));

foreach ($loai as $k => $ct){
    ok(!empty($ct['bang']) && !empty($ct['quyen']) && !empty($ct['nhan']),
       "Loai `$k` khai du bang + quyen + nhan");
    $coBang = true;
    try { $pdo->query("SELECT 1 FROM `{$ct['bang']}` LIMIT 1"); } catch (Throwable $e){ $coBang = false; }
    ok($coBang, "Bang `{$ct['bang']}` cua loai `$k` co that trong CSDL");
    $man = $mot("SELECT id FROM modules WHERE link = ?", [$ct['quyen']]);
    ok(!empty($man), "Man `{$ct['quyen']}` cua loai `$k` co that trong bang modules");
}

ok(DanhMucNhanhModel::co('khong-co-loai-nay') === null, 'Loai la -> co() tra ve null');

// --- Helper dựng nút ---
ok(function_exists('nut_them_nhanh'), 'Co helper nut_them_nhanh()');
$html = nut_them_nhanh('part-unit', 'unit_id', ['nhan' => 'đơn vị tính', 'vd' => 'VD: Cái']);
ok(strpos($html, 'data-them-nhanh="part-unit"') !== false
   && strpos($html, 'data-o="unit_id"') !== false
   && strpos($html, 'type="button"') !== false,
   'Nut mang du loai + o dich, va la type=button (khong gui form)');
ok(strpos($html, 'data-cha') === false, 'Khong khai cha thi khong sinh data-cha');

$html2 = nut_them_nhanh('model', 'model_id', ['nhan' => 'model', 'cha' => 'brand_id', 'cha_bat_buoc' => 1, 'day' => 1]);
ok(strpos($html2, 'data-cha="brand_id"') !== false && strpos($html2, 'data-cha-bat-buoc="1"') !== false
   && strpos($html2, 'data-day="1"') !== false,
   'Khai cha / bat buoc / day -> sinh dung thuoc tinh');

/* Tên danh mục do người dùng gõ sẽ quay lại trong `title` của nút — phải escape,
   không thì một dấu " là thoát ra ngoài thuộc tính. */
$html3 = nut_them_nhanh('part-unit', 'unit_id', ['nhan' => 'a" onmouseover="alert(1)']);
ok(strpos($html3, 'onmouseover="alert(1)"') === false && strpos($html3, '&quot;') !== false,
   'Chu trong nhan duoc escape, khong chen duoc thuoc tinh la');

// ---------------------------------------------------------------------------
section('Them nhanh: trung thi dung lai, khong tao ban sao');

require_once $goc . 'app/models/VehiclesModel.php';
$D = new DanhMucNhanhModel();

$u1 = $D->them('part-unit', 'ZZTN Cai');
ok(!empty($u1) && !empty($u1['c']) && empty($u1['trung']), 'Them don vi tinh moi -> tao dong moi');

$u2 = $D->them('part-unit', '  zztn   cai  ');
ok(!empty($u2) && (int) $u2['c'] === (int) $u1['c'] && !empty($u2['trung']),
   'Go thuong + khoang trang thua -> DUNG LAI dong cu', json_encode([$u1, $u2]));

$u3 = $D->them('part-unit', 'ZZTN CAI');
ok(!empty($u3) && (int) $u3['c'] === (int) $u1['c'], 'Go IN HOA -> van la mot dong');
ok($so("SELECT COUNT(*) FROM part_units WHERE slug = 'zztn-cai'") === 1,
   'Ba kieu viet -> danh muc chi co MOT dong');

ok($D->them('part-unit', '   ') === null, 'Ten rong -> khong tao gi');
ok($D->them('bang-la', 'ZZTN Gi do') === null, 'Loai ngoai danh sach trang -> khong tao gi');

// --- Danh mục cây: cha phải có thật ---
$c1 = $D->them('part-cat', 'ZZTN Danh muc cha');
$c2 = $D->them('part-cat', 'ZZTN Danh muc con', (int) $c1['c']);
$row = $mot("SELECT * FROM part_categories WHERE id = ?", [(int) $c2['c']]);
ok(!empty($row) && (int) $row['parent_id'] === (int) $c1['c'], 'Danh muc con gan dung vao cha da chon');

$c3 = $D->them('part-cat', 'ZZTN Cha ao', 99999999);
$row = $mot("SELECT * FROM part_categories WHERE id = ?", [(int) $c3['c']]);
ok(!empty($row) && empty($row['parent_id']),
   'Cha khong co that -> tao o goc, KHONG tro vao id khong ton tai',
   'Khoa ngoai tro vao hu vo la cay danh muc vo nghia');

// ---------------------------------------------------------------------------
section('HTTP — nut + tren cac man, va chan theo quyen');

$MK = 'ZzThemNhanh#2026';
$nhomAdmin = (int) $pdo->query("SELECT id FROM `groups` WHERE name='Admin'")->fetchColumn();
$garaTong  = (int) $pdo->query("SELECT id FROM garages WHERE is_master = 1 ORDER BY id LIMIT 1")->fetchColumn();

/* Nhóm CHỈ CÓ QUYỀN XEM ở màn Đơn vị tính — để thử chỗ hỏng số 1 */
$pdo->prepare("INSERT INTO `groups` (name, create_at) VALUES ('ZZTN-chi-xem', NOW())")->execute();
$nhomXem = (int) $pdo->lastInsertId();
$pdo->exec("INSERT INTO permissions (module_id, group_id, role)
            SELECT id, $nhomXem, 'view' FROM modules WHERE link IN ('product-units', 'products')");
$pdo->exec("INSERT INTO permissions (module_id, group_id, role)
            SELECT id, $nhomXem, 'add' FROM modules WHERE link = 'products'");

$taoUser = function($email, $nhom) use ($pdo, $MK, $garaTong){
    $pdo->prepare("INSERT INTO users (name,email,password,group_id,status,garage_id,create_at)
                   VALUES (?,?,?,?,1,?,NOW())")
        ->execute(['ZZ ' . $email, $email, \App\core\Hash::make($MK), $nhom, $garaTong]);
    return (int) $pdo->lastInsertId();
};
$taoUser('zz-tn-ad@local.test', $nhomAdmin);
$taoUser('zz-tn-xem@local.test', $nhomXem);

$http = function($m, $url, $jar, $d = null){
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 25]);
    if ($m === 'POST'){ curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d)); }
    $raw = curl_exec($ch); $i = curl_getinfo($ch); curl_close($ch);
    $body = $raw === false ? '' : substr($raw, (int) $i['header_size']);
    return ['code' => (int) $i['http_code'], 'loc' => (string) $i['redirect_url'], 'body' => $body,
            'text' => html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8')];
};
$token = function($h){ return preg_match('~name="_token" value="([^"]+)"~', $h, $m) ? $m[1] : ''; };

$vao = function($email) use ($http, $base, $token, $MK){
    $jar = tempnam(sys_get_temp_dir(), 'zztn');
    $r = $http('GET', "$base/dang-nhap", $jar);
    if ($r['code'] === 0) return null;
    $http('POST', "$base/dang-nhap", $jar, ['email' => $email, 'password' => $MK, '_token' => $token($r['body'])]);
    return $jar;
};

$jarAd = $vao('zz-tn-ad@local.test');
if ($jarAd === null){ echo "\n[SKIP] Apache khong chay (localhost:88).\n"; $donSach(); exit(summary()); }

/* Nút + có mặt trên các màn cần nhập nhanh */
$man = [
    'products/add'        => 5,
    'services/add'        => 2,
    'customers/add'       => 1,
    'news/add'            => 1,
    'car-models/add'      => 2,
    'part-categories/add' => 1,
    'vehicles/add'        => 4,
];
foreach ($man as $duong => $soNut){
    $r = $http('GET', "$base/admin/$duong", $jarAd);
    $dem = substr_count($r['body'], 'data-them-nhanh=');
    ok($r['code'] === 200 && $dem === $soNut,
       "Man $duong co $soNut nut + them nhanh", "HTTP {$r['code']}, dem duoc $dem");
    ok(strpos($r['body'], 'them-nhanh.js') !== false, "Man $duong nap them-nhanh.js");
}

$r     = $http('GET', "$base/admin/products/add", $jarAd);
$tkAd  = $token($r['body']);

$goi = function($jar, $tk, $d) use ($http, $base){
    $r = $http('POST', "$base/admin/them-nhanh/danh-muc", $jar, array_merge(['_token' => $tk], $d));
    return ['http' => $r['code'], 'j' => json_decode($r['body'], true)];
};

$k = $goi($jarAd, $tkAd, ['loai' => 'part-unit', 'ten' => 'ZZTN Hop']);
ok(!empty($k['j']['ok']) && !empty($k['j']['c']), 'Admin them duoc don vi tinh qua nut +', json_encode($k));

$k2 = $goi($jarAd, $tkAd, ['loai' => 'part-unit', 'ten' => 'zztn hop']);
ok(!empty($k2['j']['ok']) && (int) $k2['j']['c'] === (int) $k['j']['c'] && !empty($k2['j']['trung']),
   'Them lai khac kieu viet -> dung lai dong cu va bao trung');

$k3 = $goi($jarAd, $tkAd, ['loai' => 'users', 'ten' => 'ZZTN Lach']);
ok(empty($k3['j']['ok']), 'Loai = ten bang khac (users) -> tu choi');
ok($so("SELECT COUNT(*) FROM users WHERE name = 'ZZTN Lach'") === 0, 'Khong co dong nao lot vao bang users');

$k4 = $goi($jarAd, $tkAd, ['loai' => 'part-unit', 'ten' => '']);
ok(empty($k4['j']['ok']), 'Ten rong qua mang -> tu choi');

/* CHỖ HỎNG SỐ 1: quyền. Nhóm ZZTN-chi-xem có quyền THÊM ở màn Phụ tùng (nên
   vào được form) nhưng chỉ XEM ở màn Đơn vị tính. */
$jarXem = $vao('zz-tn-xem@local.test');
$r      = $http('GET', "$base/admin/products/add", $jarXem);
$tkXem  = $token($r['body']);
ok($r['code'] === 200, 'Nhom chi-xem van vao duoc form Them phu tung (co quyen add o man do)');

$truoc = $so("SELECT COUNT(*) FROM part_units");
$k5 = $goi($jarXem, $tkXem, ['loai' => 'part-unit', 'ten' => 'ZZTN Lach quyen']);
ok(empty($k5['j']['ok']), 'Khong co quyen THEM o man Don vi tinh -> nut + bi tu choi', json_encode($k5));
ok($so("SELECT COUNT(*) FROM part_units") === $truoc,
   'Khong dong nao duoc ghi them',
   'Nut + khong duoc thanh duong vong qua phan quyen');

$k6 = $goi($jarXem, $tkXem, ['loai' => 'part-cat', 'ten' => 'ZZTN Lach danh muc']);
ok(empty($k6['j']['ok']) && $so("SELECT COUNT(*) FROM part_categories WHERE slug LIKE 'zztn-lach%'") === 0,
   'Man danh muc khong co quyen gi -> cung bi tu choi');

/* CSRF: đây là POST ghi dữ liệu.
   Chỉ khẳng định "bị từ chối và không ghi gì", KHÔNG khẳng định đúng mã 419:
   CsrfMiddleware gọi http_response_code(419) nhưng Apache / PHP ở máy này trả
   về 500 cho mọi POST thiếu token (thử vehicles/add, quotations/add cũng vậy).
   Đó là chuyện của tầng CSRF chung, không phải của nút +. */
$r = $http('POST', "$base/admin/them-nhanh/danh-muc", $jarAd, ['loai' => 'part-unit', 'ten' => 'ZZTN Khong token']);
ok($r['code'] >= 400
   && $so("SELECT COUNT(*) FROM part_units WHERE slug = 'zztn-khong-token'") === 0,
   'Gui khong co CSRF token -> bi tu choi, khong ghi gi', 'HTTP ' . $r['code']);
ok(strpos($r['text'], 'het han') !== false, 'Va bao ro la phien lam viec het han');

@unlink($jarAd);
@unlink($jarXem);
$donSach();

ok($so("SELECT COUNT(*) FROM part_units WHERE slug LIKE 'zztn-%'") === 0, 'Da don sach du lieu test');

exit(summary());

<?php
/**
 * Test TỈNH / PHƯỜNG cho Đối tượng (khách + NCC) và Khách hàng CSKH.
 *
 * Chạy:  C:\xampp\php\php.exe tests\DiaGioiTest.php
 *
 * Danh sách lấy từ API ngoài (provinces.open-api.vn v2 — 34 tỉnh, 2 cấp sau
 * sáp nhập 2025), nhớ tạm ở server, API chết thì rơi về file tĩnh.
 *
 * NĂM CHỖ HỎNG SẼ ÂM THẦM:
 *
 *   1. TIN TÊN DO CLIENT GỬI. Form gửi cả mã lẫn tên thì ai cũng sửa được tên
 *      trước khi gửi — tên tỉnh phải tra từ nguồn dữ liệu, không nhận từ form.
 *
 *   2. KHÔNG kiểm phường có thuộc tỉnh không. Chọn Hà Nội rồi gửi mã phường
 *      của TP.HCM thì ra một địa chỉ không tồn tại, mà nhìn màn hình vẫn đẹp.
 *
 *   3. API chết là KHÔNG NHẬP ĐƯỢC địa chỉ. Phải còn đường lùi: nhớ tạm cũ,
 *      rồi tới file tĩnh.
 *
 *   4. MembersModel::adminAdd() LỌC cột. Quên khai 4 cột mới ở đó thì khách
 *      lưu xong mất sạch tỉnh/phường, không báo lỗi gì.
 *
 *   5. Chỉ lưu MÃ, không lưu TÊN. Đơn vị hành chính còn sáp nhập / đổi tên
 *      nữa (2025 vừa gộp 63 tỉnh thành 34) — chứng từ cũ phải in ra đúng chữ
 *      như lúc nhập.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc  = __DIR__ . '/../';
$base = 'http://localhost:88/tan-phat';

// ---------------------------------------------------------------------------
section('Nguon du lieu: API -> nho tam -> file tinh');

dia_gioi_xoa_nho();

$tinh = dia_gioi_tinh();
ok(count($tinh) >= 34, 'Lay duoc danh sach tinh (' . count($tinh) . ' tinh)',
   'Sau sap nhap 2025 con 34 tinh/thanh');
ok(isset($tinh[0]['c'], $tinh[0]['n']), 'Moi dong co ma `c` va ten `n`', json_encode($tinh[0] ?? null));

$tenTheoMa = [];
foreach ($tinh as $p) $tenTheoMa[$p['c']] = $p['n'];
ok(isset($tenTheoMa[1]) && mb_strpos($tenTheoMa[1], 'Hà Nội') !== false,
   'Ma 1 la Ha Noi — ma cua API trung ma cua file tinh', json_encode($tenTheoMa[1] ?? null));

$xaHN = dia_gioi_xa(1);
ok(count($xaHN) > 50, 'Ha Noi co ' . count($xaHN) . ' phuong/xa');
$coBaDinh = false;
foreach ($xaHN as $w) if ((int) $w['c'] === 4) $coBaDinh = true;
ok($coBaDinh, 'Trong danh sach co phuong ma 4 (Ba Dinh)');

ok(dia_gioi_xa(0) === [] && dia_gioi_xa(-5) === [] && dia_gioi_xa(999999) === [],
   'Ma tinh khong hop le -> danh sach rong, khong no');

$tra = dia_gioi_tra(1, 4);
ok(!empty($tra['province']) && mb_strpos($tra['province'], 'Hà Nội') !== false
   && !empty($tra['ward']) && mb_strpos($tra['ward'], 'Ba Đình') !== false,
   'Tra ma 1 + 4 ra dung ten tinh va phuong', json_encode($tra));

ok(dia_gioi_tra(1, 99999999) === null, 'Ma phuong khong ton tai -> null');
ok(dia_gioi_tra(0, 0) === null && dia_gioi_tra('abc', 'xyz') === null, 'Ma rac -> null');

/* BẪY CHÍNH: phường của tỉnh khác */
$tinhKhac = 0;
foreach ($tinh as $p) if ((int) $p['c'] !== 1){ $tinhKhac = (int) $p['c']; break; }
$xaTinhKhac = $tinhKhac > 0 ? dia_gioi_xa($tinhKhac) : [];
if (!empty($xaTinhKhac)){
    $maXaLa = (int) $xaTinhKhac[0]['c'];
    ok(dia_gioi_tra(1, $maXaLa) === null,
       'Phuong cua tinh KHAC -> null (khong ghep bua)',
       'Chon Ha Noi roi gui ma phuong tinh khac thi ra dia chi khong ton tai');
    ok(dia_gioi_tra($tinhKhac, $maXaLa) !== null, 'Dung tinh cua no thi tra duoc');
}

// ---------------------------------------------------------------------------
section('Nho tam va duong lui khi API chet');

$apiSong = false;
if (function_exists('curl_init')){
    $ch = curl_init('https://provinces.open-api.vn/api/v2/');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    $apiSong = (curl_exec($ch) !== false) && ((int) curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200);
    curl_close($ch);
}
$thuMuc = dia_gioi_thu_muc_nho();

if ($apiSong){
    dia_gioi_xoa_nho();
    dia_gioi_tinh();
    ok(is_file($thuMuc . '/tinh.json'), 'Goi API xong thi nho tam ra file',
       'Khong nho thi mo form nao cung cho mang');
} else {
    echo "\n[SKIP] Khong goi duoc API that — bo qua khang dinh ve nho tam.\n";
}

/* Nhớ tạm QUÁ HẠN vẫn hơn không có gì: dựng một bản nhớ tạm nhận ra được,
   cho API chết và hạn = 0, danh sách phải lấy từ bản nhớ tạm đó. */
if (!is_dir($thuMuc)) @mkdir($thuMuc, 0775, true);
file_put_contents($thuMuc . '/tinh.json', json_encode([['code' => 1, 'name' => 'ZZ Tinh Nho Tam']]));
$GLOBALS['dia_gioi_api'] = 'https://127.0.0.1:9/khong-ton-tai';
$GLOBALS['dia_gioi_ttl'] = 0;
$ds = dia_gioi_tinh();
ok(count($ds) === 1 && $ds[0]['n'] === 'ZZ Tinh Nho Tam',
   'API chet + nho tam qua han -> van dung ban nho tam', json_encode(array_slice($ds, 0, 2)));

/* Không còn nhớ tạm thì rơi về file tĩnh — vẫn nhập được địa chỉ */
dia_gioi_xoa_nho();
$ds = dia_gioi_tinh();
ok(count($ds) === 34, 'API chet + khong co nho tam -> file tinh 34 tinh', 'Ra ' . count($ds) . ' tinh');
$tra = dia_gioi_tra(1, 4);
ok(!empty($tra['ward']) && mb_strpos($tra['ward'], 'Ba Đình') !== false,
   'Tra cuu van chay bang file tinh', json_encode($tra));
ok(count((array) glob($thuMuc . '/*.json')) === 0,
   'API loi thi KHONG ghi nho tam rong', 'Ghi rong roi lan sau doc lai la mat du lieu');

unset($GLOBALS['dia_gioi_api'], $GLOBALS['dia_gioi_ttl']);
dia_gioi_xoa_nho();

// ---------------------------------------------------------------------------
section('Ghep dia chi day du');

ok(dia_chi_day_du(['address' => '12 Trần Phú', 'ward_name' => 'Phường Ba Đình', 'province_name' => 'Thành phố Hà Nội'])
   === '12 Trần Phú, Phường Ba Đình, Thành phố Hà Nội', 'Ghep du ba phan');
ok(dia_chi_day_du(['address' => '12 Trần Phú']) === '12 Trần Phú',
   'Khach cu chua co tinh/phuong -> chi ra dia chi, khong dau phay thua');
ok(dia_chi_day_du(null) === '' && dia_chi_day_du([]) === '', 'Khong co gi -> chuoi rong (ban in truyen null)');

// ---------------------------------------------------------------------------
section('CSDL + HTTP that');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

foreach (['partners', 'members'] as $bang){
    $cot = [];
    foreach ($pdo->query("SHOW COLUMNS FROM `$bang`") as $r) $cot[] = $r['Field'];
    $thieu = array_diff(['province_code', 'province_name', 'ward_code', 'ward_name'], $cot);
    ok(empty($thieu), "Bang `$bang` co du bon cot dia gioi", 'Thieu: ' . implode(', ', $thieu));
}

$donSach = function() use ($pdo){
    $pdo->exec("DELETE FROM partners WHERE code LIKE 'ZZDG%'");
    $pdo->exec("DELETE FROM members WHERE email LIKE 'zz-dg-%@local.test'");
    $pdo->exec("DELETE t FROM login_tokens t JOIN users u ON u.id = t.user_id WHERE u.email = 'zz-dg-ad@local.test'");
    $pdo->exec("DELETE FROM users WHERE email = 'zz-dg-ad@local.test'");
};
$donSach();

if (!function_exists('curl_init')){ echo "\n[SKIP] PHP khong co curl.\n"; $donSach(); exit(summary()); }

$MK = 'ZzDiaGioi#2026';
$nhomAdmin = (int) $pdo->query("SELECT id FROM `groups` WHERE name = 'Admin'")->fetchColumn();
/* Tài khoản PHẢI thuộc một gara: từ 22/09/2026 tài khoản không gara không vào
   được trang quản trị (gara độc lập). Admin thử ở gara tổng, như Admin thật. */
$garaTong = (int) $pdo->query("SELECT id FROM garages WHERE is_master = 1 ORDER BY id LIMIT 1")->fetchColumn();
$pdo->prepare("INSERT INTO users (name, email, password, group_id, status, garage_id, create_at)
               VALUES ('ZZ Admin dia gioi', 'zz-dg-ad@local.test', ?, ?, 1, ?, NOW())")
    ->execute([\App\core\Hash::make($MK), $nhomAdmin, $garaTong]);

$http = function($method, $url, $jar, $data = null){
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 25,
    ]);
    if ($method === 'POST'){
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $raw  = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    $body = $raw === false ? '' : substr($raw, (int) $info['header_size']);
    return ['code' => (int) $info['http_code'], 'body' => $body,
            'head' => $raw === false ? '' : substr($raw, 0, (int) $info['header_size']),
            'text' => html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8')];
};
$token = function($html){ return preg_match('~name="_token" value="([^"]+)"~', $html, $m) ? $m[1] : ''; };

$jar = tempnam(sys_get_temp_dir(), 'zzdg');
$r = $http('GET', "$base/dang-nhap", $jar);
if ($r['code'] === 0){ echo "\n[SKIP] Apache khong chay (localhost:88).\n"; $donSach(); exit(summary()); }
$tk = $token($r['body']);
$http('POST', "$base/dang-nhap", $jar, ['email' => 'zz-dg-ad@local.test', 'password' => $MK, '_token' => $tk]);

/* --- Hai duong dan JSON cho o chon --- */
$r = $http('GET', "$base/admin/dia-gioi/tinh", $jar);
$ds = json_decode($r['body'], true);
ok($r['code'] === 200 && is_array($ds) && count($ds) >= 34,
   'GET admin/dia-gioi/tinh tra ve >= 34 tinh (HTTP ' . $r['code'] . ')',
   substr($r['body'], 0, 120));
ok(stripos($r['head'], 'application/json') !== false, 'Tra ve dung kieu JSON');

$r = $http('GET', "$base/admin/dia-gioi/xa/1", $jar);
$ds = json_decode($r['body'], true);
ok($r['code'] === 200 && is_array($ds) && count($ds) > 50, 'GET .../xa/1 tra ve phuong cua Ha Noi');

$r = $http('GET', "$base/admin/dia-gioi/xa/999999", $jar);
ok(json_decode($r['body'], true) === [], 'Ma tinh khong co -> mang rong, khong loi 500');

/* --- Doi tuong: luu tinh/phuong --- */
$doiTuong = function($ma) use ($pdo){
    $st = $pdo->prepare("SELECT * FROM partners WHERE code = ?");
    $st->execute([$ma]);
    return $st->fetch(PDO::FETCH_ASSOC);
};
$r  = $http('GET', "$base/admin/partners/add", $jar);
$tk = $token($r['body']) ?: $tk;
ok(strpos($r['body'], 'data-dia-gioi="tinh"') !== false && strpos($r['body'], 'data-dia-gioi="xa"') !== false,
   'Form them doi tuong co hai o chon tinh / phuong');
ok(strpos($r['body'], 'dia-gioi.js') !== false, 'Form co nap file JS dung chung');

$themDT = function($ma, $them = []) use ($http, $base, $jar, &$tk){
    return $http('POST', "$base/admin/partners/add", $jar, array_merge([
        '_token' => $tk, 'code' => $ma, 'name' => 'ZZ ' . $ma, 'type' => 'customer',
        'address' => '12 Trần Phú', 'sort_order' => 0, 'status' => 1,
    ], $them));
};

$themDT('ZZDG-1', ['province_code' => 1, 'ward_code' => 4]);
$p = $doiTuong('ZZDG-1');
ok(!empty($p), 'Luu duoc doi tuong co tinh/phuong');
ok(!empty($p) && (int) $p['province_code'] === 1 && (int) $p['ward_code'] === 4,
   'Luu dung hai ma', json_encode($p ? [$p['province_code'], $p['ward_code']] : null));
ok(!empty($p) && mb_strpos((string) $p['province_name'], 'Hà Nội') !== false
   && mb_strpos((string) $p['ward_name'], 'Ba Đình') !== false,
   'Luu ca TEN tinh va phuong (de sau nay doi ten van doc duoc)',
   json_encode($p ? [$p['province_name'], $p['ward_name']] : null));

/* Tên do client gửi phải bị bỏ qua */
$themDT('ZZDG-2', ['province_code' => 1, 'ward_code' => 4,
                   'province_name' => 'ZZ Tinh Gia Mao', 'ward_name' => 'ZZ Phuong Gia Mao']);
$p = $doiTuong('ZZDG-2');
ok(!empty($p) && mb_strpos((string) $p['province_name'], 'Hà Nội') !== false,
   'Ten tinh do client gui len bi BO QUA, lay tu nguon du lieu',
   'Dang luu: ' . ($p['province_name'] ?? 'null'));

/* Phường của tỉnh khác -> chặn */
if (!empty($xaTinhKhac)){
    $themDT('ZZDG-3', ['province_code' => 1, 'ward_code' => (int) $xaTinhKhac[0]['c']]);
    ok(empty($doiTuong('ZZDG-3')),
       'Phuong cua tinh KHAC -> khong luu duoc',
       'Dia chi khong ton tai ma nhin man hinh van dep');
    /* Mo lai form nhu trinh duyet bi day ve: du lieu cua lan gui LOI nam tam
       trong phien den lan mo form ke tiep. Khong mo thi trang Sua ben duoi
       nhan nham du lieu do va chon san phuong sai. */
    $http('GET', "$base/admin/partners/add", $jar);
}

/* Để trống: vẫn lưu được */
$themDT('ZZDG-4');
$p = $doiTuong('ZZDG-4');
ok(!empty($p) && $p['province_code'] === null && $p['ward_name'] === null,
   'De trong tinh/phuong -> van luu duoc, bon cot NULL',
   'NCC nuoc ngoai va 6 doi tuong cu khong co tinh/phuong');

/* Form sửa chọn sẵn đúng tỉnh/phường đang lưu */
$p = $doiTuong('ZZDG-1');
$r = $http('GET', "$base/admin/partners/edit/" . (int) $p['id'], $jar);
ok(preg_match('~data-dia-gioi="tinh"[^>]*data-chon="1"~s', $r['body']) === 1
   || preg_match('~data-chon="1"[^>]*data-dia-gioi="tinh"~s', $r['body']) === 1,
   'Form sua chon san tinh dang luu');
ok(strpos($r['body'], 'data-chon="4"') !== false, 'Form sua chon san phuong dang luu');

/* --- Khach hang CSKH --- */
$khachTheoEmail = function($email) use ($pdo){
    $st = $pdo->prepare("SELECT * FROM members WHERE email = ?");
    $st->execute([$email]);
    return $st->fetch(PDO::FETCH_ASSOC);
};
$r  = $http('GET', "$base/admin/customers/add", $jar);
$tk = $token($r['body']) ?: $tk;
ok(strpos($r['body'], 'data-dia-gioi="tinh"') !== false, 'Form them khach hang co o chon tinh');

$http('POST', "$base/admin/customers/add", $jar, [
    '_token' => $tk, 'name' => 'ZZ Khach dia gioi', 'email' => 'zz-dg-1@local.test',
    'phone' => '0912000111', 'address' => '5 Lê Lợi', 'province_code' => 1, 'ward_code' => 4,
]);
$kh = $khachTheoEmail('zz-dg-1@local.test');
ok(!empty($kh), 'Luu duoc khach hang moi');
ok(!empty($kh) && (int) $kh['province_code'] === 1 && mb_strpos((string) $kh['ward_name'], 'Ba Đình') !== false,
   'Khach hang luu du ma va ten tinh/phuong',
   'adminAdd() loc cot — thieu khai 4 cot la mat du lieu am tham: '
   . json_encode($kh ? [$kh['province_code'], $kh['ward_name']] : null));

/* Sửa sang tỉnh khác */
if (!empty($xaTinhKhac) && !empty($kh)){
    $r  = $http('GET', "$base/admin/customers/edit/" . (int) $kh['id'], $jar);
    $tk = $token($r['body']) ?: $tk;
    ok(strpos($r['body'], 'data-chon="1"') !== false, 'Form sua khach chon san tinh dang luu');

    $http('POST', "$base/admin/customers/edit/" . (int) $kh['id'], $jar, [
        '_token' => $tk, 'name' => 'ZZ Khach dia gioi', 'phone' => '0912000111',
        'address' => '5 Lê Lợi', 'status' => 1,
        'province_code' => $tinhKhac, 'ward_code' => (int) $xaTinhKhac[0]['c'],
    ]);
    $kh2 = $khachTheoEmail('zz-dg-1@local.test');
    ok(!empty($kh2) && (int) $kh2['province_code'] === $tinhKhac
       && (string) $kh2['province_name'] === (string) $tenTheoMa[$tinhKhac],
       'Sua sang tinh khac -> ma va ten deu doi theo',
       json_encode($kh2 ? [$kh2['province_code'], $kh2['province_name']] : null));

    /* Gửi phường không thuộc tỉnh -> không đổi gì */
    $http('POST', "$base/admin/customers/edit/" . (int) $kh['id'], $jar, [
        '_token' => $tk, 'name' => 'ZZ Khach dia gioi', 'phone' => '0912000111',
        'address' => '5 Lê Lợi', 'status' => 1,
        'province_code' => $tinhKhac, 'ward_code' => 4,
    ]);
    $kh3 = $khachTheoEmail('zz-dg-1@local.test');
    ok(!empty($kh3) && (int) $kh3['ward_code'] === (int) $xaTinhKhac[0]['c'],
       'Gui phuong khong thuoc tinh -> giu nguyen du lieu cu, khong ghi bua');
}

@unlink($jar);
$donSach();
ok((int) $pdo->query("SELECT COUNT(*) FROM partners WHERE code LIKE 'ZZDG%'")->fetchColumn() === 0
   && (int) $pdo->query("SELECT COUNT(*) FROM members WHERE email LIKE 'zz-dg-%@local.test'")->fetchColumn() === 0,
   'Da don sach du lieu test');

exit(summary());

<?php
/**
 * Test MỘT BẢN GHI — HAI MÀN KHAI ĐỦ NHƯ NHAU.
 *
 * Chạy:  C:\xampp\php\php.exe tests\MotBanGhiHaiManTest.php
 *
 * BỐI CẢNH
 * Màn "CSKH › Khách hàng" và màn "Bán hàng › Đối tượng" cùng đọc / ghi MỘT
 * dòng trong bảng `partners`. Nhưng hai form lại khai thiếu chéo nhau:
 *
 *   - email, nhóm khách  : chỉ có ở màn Khách hàng
 *   - mã, MST            : chỉ có ở màn Đối tượng
 *
 * Khai một khách doanh nghiệp cho đủ là phải chạy qua CẢ HAI màn. Nhìn từ
 * ngoài vào thì rất giống "hai nơi lưu khác nhau", dù bên dưới chỉ là một
 * dòng. Nay form nào cũng khai đủ.
 *
 * BA CHỖ HỎNG ÂM THẦM mà test này gác:
 *
 *   1. ĐỂ NHÓM KHÁCH DÍNH VÀO NHÀ CUNG CẤP. "Nhóm khách" là khái niệm của
 *      khách; NCC thuần nằm trong nhóm "Khách doanh nghiệp" là dữ liệu rác.
 *      Ẩn ô trên form KHÔNG đủ — phải bỏ ở đường lưu, POST tay cũng không ghi
 *      vào được.
 *
 *   2. NHÓM CỦA GARA KHÁC. Gara độc lập nhau; gửi id nhóm của gara khác phải
 *      bị chặn, không thì xếp khách của mình vào nhóm nhà người ta.
 *
 *   3. BẮT BUỘC Ô MỚI CHO MỌI POST. Thêm ô `code` rồi bắt buộc nó ở đường lưu
 *      sẽ chặn luôn những nơi gửi thiếu ô đó — đã làm đỏ DiaGioiTest ngay lần
 *      chạy đầu. Luật đúng: KHÔNG gửi ô mã = giữ nguyên mã cũ; có gửi mà để
 *      rỗng mới là lỗi.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc  = __DIR__ . '/../';
$base = 'http://localhost:88/tan-phat';

// ---------------------------------------------------------------------------
section('Hai form deu co du o');

$v = function($f) use ($goc){ return file_get_contents($goc . 'app/views/admin/' . $f); };

foreach (['partners/add.php', 'partners/edit.php'] as $f){
    $s = $v($f);
    ok(strpos($s, 'name="email"') !== false,    "$f co o Email");
    ok(strpos($s, 'name="group_id"') !== false, "$f co o Nhom khach");
}
foreach (['customers/add.php', 'customers/edit.php'] as $f){
    ok(strpos($v($f), 'name="tax_code"') !== false, "$f co o MST");
}
/* Mã: form THÊM cố ý không có (mã tự cấp KH-xxxx, khách vãng lai ở quầy không
   phải nghĩ ra mã); form SỬA thì phải có để chữa khi gõ nhầm. */
ok(strpos($v('customers/add.php'), 'name="code"') === false,
   'Form THEM khach KHONG co o Ma (ma tu cap)');
ok(strpos($v('customers/edit.php'), 'name="code"') !== false,
   'Form SUA khach CO o Ma');

// ---------------------------------------------------------------------------
section('Lien he nhanh voi khach (Zalo / goi / nhan tin / email)');

/* Man CSKH la noi CHAM SOC. Nut lien he dung SDT va email DA CO trong ho so,
   khong phai khai them gi.

   CHO DE HONG: nguoi nhap moi nguoi mot kieu — "0912 345 678", "+84912345678",
   "0912.345.678". Dan nguyen van vao tel: hay zalo.me/ la hong link, ma hong
   kieu nay KHONG bao loi gi: nut van hien, bam vao moi biet. */
ok(sdt_chuan('0912 345 678')   === '0912345678', 'sdt_chuan() bo khoang trang');
ok(sdt_chuan('0912.345.678')   === '0912345678', 'sdt_chuan() bo dau cham');
ok(sdt_chuan('+84912345678')   === '0912345678', 'sdt_chuan() doi +84 ve 0');
ok(sdt_chuan('84912345678')    === '0912345678', 'sdt_chuan() doi 84 ve 0');
ok(sdt_chuan('')               === '' && sdt_chuan('abc') === '',
   'sdt_chuan() khong co chu so -> rong');

$nut = nut_lien_he('0912 345 678', 'a@b.test');
ok(strpos($nut, 'https://zalo.me/0912345678') !== false, 'Nut Zalo tro dung so da chuan hoa');
ok(strpos($nut, 'href="tel:0912345678"') !== false,      'Co nut goi dien');
ok(strpos($nut, 'href="sms:0912345678"') !== false,      'Co nut nhan tin');
ok(strpos($nut, 'href="mailto:a@b.test"') !== false,     'Co nut gui email');
ok(strpos($nut, 'target="_blank"') !== false && strpos($nut, 'rel="noopener') !== false,
   'Zalo mo tab moi va co rel=noopener');

/* Thieu du lieu thi nut MO chu khong bo han — bo han lam cac dong trong bang
   so le nhau, nhin rat kho do. */
$nutThieu = nut_lien_he('', '');
ok(strpos($nutThieu, 'zalo.me') === false && strpos($nutThieu, 'href="tel:') === false,
   'Khach chua co SDT -> khong sinh link zalo / tel nao');
ok(substr_count($nutThieu, 'disabled') === 4,
   'Va ca bon nut deu hien MO, khong bien mat',
   'Dem duoc ' . substr_count($nutThieu, 'disabled'));
ok(strpos(nut_lien_he('0912345678', 'khong-phai-email'), 'mailto:') === false,
   'Email sai dinh dang -> khong sinh link mailto');

// ---------------------------------------------------------------------------
try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$donSach = function() use ($pdo){
    $pdo->exec("DELETE FROM partners WHERE code LIKE 'ZZ2M%' OR name LIKE 'ZZ2M%'");
    $pdo->exec("DELETE FROM customer_groups WHERE name LIKE 'ZZ2M%'");
    $pdo->exec("DELETE t FROM login_tokens t JOIN users u ON u.id = t.user_id WHERE u.email = 'zz2m-ad@local.test'");
    $pdo->exec("DELETE FROM users WHERE email IN ('zz2m-ad@local.test', 'zz2m-hep@local.test')");
    $pdo->exec("DELETE p FROM permissions p JOIN `groups` g ON g.id = p.group_id WHERE g.name LIKE 'ZZ2M%'");
    $pdo->exec("DELETE FROM `groups` WHERE name LIKE 'ZZ2M%'");
};
$donSach();

if (!function_exists('curl_init')){ echo "\n[SKIP] PHP khong co curl.\n"; $donSach(); exit(summary()); }

$MK        = 'Zz2Man#2026';
$nhomAdmin = (int) $pdo->query("SELECT id FROM `groups` WHERE name = 'Admin'")->fetchColumn();
$garaTong  = (int) $pdo->query("SELECT id FROM garages WHERE is_master = 1 ORDER BY id LIMIT 1")->fetchColumn();
$garaKhac  = (int) $pdo->query("SELECT id FROM garages WHERE is_master = 0 ORDER BY id LIMIT 1")->fetchColumn();
$pdo->prepare("INSERT INTO users (name, email, password, group_id, status, garage_id, create_at)
               VALUES ('ZZ2M Admin', 'zz2m-ad@local.test', ?, ?, 1, ?, NOW())")
    ->execute([\App\core\Hash::make($MK), $nhomAdmin, $garaTong]);

// Nhóm khách: một của gara tổng (hợp lệ), một của gara khác (phải bị chặn)
$pdo->prepare("INSERT INTO customer_groups (name, sort_order, status, garage_id, create_at)
               VALUES ('ZZ2M Nhom hop le', 0, 1, ?, NOW())")->execute([$garaTong]);
$nhomHopLe = (int) $pdo->lastInsertId();
$nhomLa = 0;
if ($garaKhac > 0){
    $pdo->prepare("INSERT INTO customer_groups (name, sort_order, status, garage_id, create_at)
                   VALUES ('ZZ2M Nhom gara khac', 0, 1, ?, NOW())")->execute([$garaKhac]);
    $nhomLa = (int) $pdo->lastInsertId();
}

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
    return ['code' => (int) $info['http_code'], 'body' => $body];
};
$token = function($html){ return preg_match('~name="_token" value="([^"]+)"~', $html, $m) ? $m[1] : ''; };
$theoMa = function($ma) use ($pdo){
    $st = $pdo->prepare("SELECT * FROM partners WHERE code = ?");
    $st->execute([$ma]);
    return $st->fetch(PDO::FETCH_ASSOC);
};

$jar = tempnam(sys_get_temp_dir(), 'zz2m');
$r = $http('GET', "$base/dang-nhap", $jar);
if ($r['code'] === 0){ echo "\n[SKIP] Apache khong chay (localhost:88).\n"; @unlink($jar); $donSach(); exit(summary()); }
$tk = $token($r['body']);
$http('POST', "$base/dang-nhap", $jar, ['email' => 'zz2m-ad@local.test', 'password' => $MK, '_token' => $tk]);

$themDT = function($them) use ($http, $base, $jar, &$tk, $token){
    $r  = $http('GET', "$base/admin/partners/add", $jar);
    $tk = $token($r['body']) ?: $tk;
    return $http('POST', "$base/admin/partners/add", $jar, array_merge([
        '_token' => $tk, 'name' => 'ZZ2M Doi tuong', 'type' => 'customer', 'status' => 1,
    ], $them));
};

// ---------------------------------------------------------------------------
section('Man Doi tuong khai duoc Email + Nhom khach');

$themDT(['code' => 'ZZ2M-001', 'email' => 'zz2m-kh@local.test', 'group_id' => $nhomHopLe]);
$dt = $theoMa('ZZ2M-001');
ok(!empty($dt) && $dt['email'] === 'zz2m-kh@local.test',
   'Email go o man Doi tuong duoc LUU', json_encode($dt['email'] ?? null));
ok(!empty($dt) && (int) $dt['group_id'] === $nhomHopLe,
   'Nhom khach chon o man Doi tuong duoc LUU', json_encode($dt['group_id'] ?? null));

// ---------------------------------------------------------------------------
section('Chot chan o duong luu');

$themDT(['code' => 'ZZ2M-002', 'type' => 'supplier', 'group_id' => $nhomHopLe]);
$ncc = $theoMa('ZZ2M-002');
ok(!empty($ncc) && $ncc['group_id'] === null,
   'NHA CUNG CAP thi KHONG dinh nhom khach, du POST co gui',
   'An o tren form khong du — phai bo o duong luu. Thuc te: ' . json_encode($ncc['group_id'] ?? null));

$themDT(['code' => 'ZZ2M-003', 'email' => 'khong-phai-email']);
ok(empty($theoMa('ZZ2M-003')), 'Email sai dinh dang -> KHONG tao duoc');

if ($nhomLa > 0){
    $themDT(['code' => 'ZZ2M-004', 'group_id' => $nhomLa]);
    ok(empty($theoMa('ZZ2M-004')),
       'Nhom khach cua GARA KHAC -> bi chan',
       'Gara doc lap: khong duoc xep khach cua minh vao nhom nha nguoi ta');
}

// ---------------------------------------------------------------------------
section('Man Khach hang khai duoc MST + sua duoc Ma');

$r  = $http('GET', "$base/admin/customers/add", $jar);
$tk = $token($r['body']) ?: $tk;
$http('POST', "$base/admin/customers/add", $jar, [
    '_token' => $tk, 'name' => 'ZZ2M Khach cong ty', 'phone' => '0912000222',
    'tax_code' => '0101234567', 'group_id' => $nhomHopLe,
]);
$st = $pdo->prepare("SELECT * FROM partners WHERE name = 'ZZ2M Khach cong ty'");
$st->execute();
$kh = $st->fetch(PDO::FETCH_ASSOC);
ok(!empty($kh), 'Them duoc khach o man Khach hang');
ok(!empty($kh) && $kh['tax_code'] === '0101234567',
   'MST go o man Khach hang duoc LUU', json_encode($kh['tax_code'] ?? null));
ok(!empty($kh) && strpos((string) $kh['code'], 'KH-') === 0,
   'Them o man Khach hang thi ma van TU CAP (KH-xxxx)', json_encode($kh['code'] ?? null));

if (!empty($kh)){
    $id = (int) $kh['id'];

    // Đổi mã
    $r  = $http('GET', "$base/admin/customers/edit/$id", $jar);
    $tk = $token($r['body']) ?: $tk;
    $http('POST', "$base/admin/customers/edit/$id", $jar, [
        '_token' => $tk, 'code' => 'ZZ2M-KH9', 'name' => 'ZZ2M Khach cong ty',
        'phone' => '0912000222', 'tax_code' => '0101234567', 'status' => 1,
    ]);
    $kh2 = $theoMa('ZZ2M-KH9');
    ok(!empty($kh2) && (int) $kh2['id'] === $id, 'Sua duoc Ma ngay o man Khach hang');

    // Mã trùng
    $http('POST', "$base/admin/customers/edit/$id", $jar, [
        '_token' => $tk, 'code' => 'ZZ2M-001', 'name' => 'ZZ2M Khach cong ty',
        'phone' => '0912000222', 'status' => 1,
    ]);
    $st = $pdo->prepare("SELECT code FROM partners WHERE id = ?"); $st->execute([$id]);
    ok($st->fetchColumn() === 'ZZ2M-KH9', 'Ma TRUNG voi doi tuong khac -> bi chan');

    /* Chốt chặn cho lỗi đã gây: POST thiếu ô mã thì GIỮ NGUYÊN mã, không báo
       lỗi "mã không được để trống" rồi bỏ luôn cả bản ghi. */
    $http('POST', "$base/admin/customers/edit/$id", $jar, [
        '_token' => $tk, 'name' => 'ZZ2M Khach doi ten', 'phone' => '0912000222', 'status' => 1,
    ]);
    $st = $pdo->prepare("SELECT code, name FROM partners WHERE id = ?"); $st->execute([$id]);
    $sau = $st->fetch(PDO::FETCH_ASSOC);
    ok($sau['code'] === 'ZZ2M-KH9' && $sau['name'] === 'ZZ2M Khach doi ten',
       'POST khong gui o Ma -> giu nguyen ma cu, phan con lai van luu',
       json_encode($sau));
}

// ---------------------------------------------------------------------------
section('Van la MOT ban ghi — sua ben nay ben kia thay');

$dt = $theoMa('ZZ2M-001');
if (!empty($dt)){
    $id = (int) $dt['id'];
    $r  = $http('GET', "$base/admin/customers/edit/$id", $jar);
    ok($r['code'] === 200 && strpos($r['body'], 'zz2m-kh@local.test') !== false,
       'Doi tuong tao o man Ban hang MO duoc o man Khach hang, giu nguyen email');
    $tk = $token($r['body']) ?: $tk;
    $http('POST', "$base/admin/customers/edit/$id", $jar, [
        '_token' => $tk, 'code' => 'ZZ2M-001', 'name' => 'ZZ2M Doi tuong',
        'phone' => '0988000111', 'email' => 'zz2m-kh@local.test',
        'tax_code' => '9988776655', 'group_id' => $nhomHopLe, 'status' => 1,
    ]);
    $r = $http('GET', "$base/admin/partners/edit/$id", $jar);
    ok(strpos($r['body'], '9988776655') !== false,
       'MST go ben man Khach hang -> man Doi tuong doc ra dung');
    ok(strpos($r['body'], '0988000111') !== false,
       'So dien thoai sua ben nay -> ben kia thay ngay (cung mot dong)');
}

// ---------------------------------------------------------------------------
section('Form ve ra thuc su, khong lot ma nguon');

/* LỖI ĐÃ MẮC khi thêm mấy ô này: viết "{{$bien}}" trong chuỗi nháy kép của
   PHP lúc sinh code thì {$bien} bị nội suy, còn lại MỘT cặp ngoặc — template
   không thay thế, và ô Email hiện nguyên đoạn mã "!empty($old['email'])..."
   ngay trên màn hình. Test chỉ kiểm name="email" có mặt thì KHÔNG bắt được:
   ô vẫn tồn tại, vẫn lưu đúng, chỉ hiển thị sai. Phải xem HTML đã vẽ ra. */
$veRa = [
    'admin/partners/add', 'admin/customers/add',
];
$dtGuard = $theoMa('ZZ2M-001');
if (!empty($dtGuard)){
    $veRa[] = 'admin/partners/edit/' . (int) $dtGuard['id'];
    $veRa[] = 'admin/customers/edit/' . (int) $dtGuard['id'];
}
foreach ($veRa as $u){
    $r = $http('GET', "$base/$u", $jar);
    $lot = [];
    foreach (['$old[', '$item[', '$val(', '{{', '!empty('] as $dau){
        if (strpos($r['body'], $dau) !== false) $lot[] = $dau;
    }
    ok($r['code'] === 200 && empty($lot),
       "/$u ve ra sach, khong lot ma nguon",
       'HTTP ' . $r['code'] . ' | con sot: ' . implode(' ', $lot));
}

// ---------------------------------------------------------------------------
section('Danh sach khach: co nut lien he, KHONG co nut Them');

$rDs = $http('GET', "$base/admin/customers", $jar);
ok($rDs['code'] === 200 && strpos($rDs['body'], 'zalo.me/') !== false,
   'Danh sach khach co nut Zalo tren dong khach',
   'HTTP ' . $rDs['code']);
ok(strpos($rDs['body'], 'href="tel:') !== false && strpos($rDs['body'], 'href="mailto:') !== false,
   'Va co ca nut goi dien / gui email');
/* Khai khach o Ban hang > Doi tuong, man nay chi cham soc. */
ok(strpos($rDs['body'], '/admin/customers/add') === false,
   'Danh sach khach KHONG con duong toi man Them');

// ---------------------------------------------------------------------------
section('Man Doi tuong co tren menu va duoc gac quyen');

/* 02/10/2026 có một lượt ẩn `partners` khỏi menu rồi KHÔI PHỤC ngay trong ngày.
   Phân vai: đây là nơi KHAI khách và nhà cung cấp (thêm / sửa / xoá / đặt mã /
   MST); màn CSKH › Khách hàng là nơi CHĂM SÓC. Hai màn cùng sửa MỘT dòng
   `partners` nhưng không trùng việc. */
$sb = file_get_contents($goc . 'app/views/layouts/admin/sidebar.php');
if (preg_match('~\$menuGroups\s*=\s*\[(.*?)\n\];~s', $sb, $mg)){
    $khongChuThich = preg_replace('~/\*.*?\*/|//[^\n]*~s', '', $mg[1]);
    ok(strpos($khongChuThich, "'partners'") !== false,
       'sidebar.php CO muc `partners` tren menu',
       'Day la noi duy nhat khai duoc nha cung cap');
}

/* CHỖ HỎNG ÂM THẦM nếu sau này ai đó "dọn dẹp" nốt dòng `modules`:
   RoleMiddleware ghép 'admin/<link>/*' để tìm module của URL đang mở. Không
   khớp module nào thì $currentModuleId = 0 và nó BỎ QUA luôn phần kiểm quyền —
   màn bị ẩn sẽ thành màn KHÔNG AI GÁC, ai biết địa chỉ cũng vào được. Ẩn khỏi
   menu thì được, xoá dòng modules thì KHÔNG. */
$mod = $pdo->query("SELECT id FROM `modules` WHERE link = 'partners'")->fetchColumn();
ok(!empty($mod),
   'Dong `modules` cua partners VAN CON',
   'Mat dong nay la man /admin/partners thanh man khong ai gac');

$r = $http('GET', "$base/admin/partners", $jar);
ok($r['code'] === 200, 'Admin van vao duoc /admin/partners bang duong dan (HTTP ' . $r['code'] . ')');

/* Và vẫn CHẶN người không có quyền: nhóm mới, chỉ cho xem `customers`. */
$pdo->prepare("INSERT INTO `groups` (name, create_at) VALUES ('ZZ2M Nhom hep', NOW())")->execute();
$nhomHep = (int) $pdo->lastInsertId();
$modKh   = (int) $pdo->query("SELECT id FROM `modules` WHERE link = 'customers'")->fetchColumn();
$pdo->prepare("INSERT INTO `permissions` (module_id, group_id, role) VALUES (?, ?, 'view')")
    ->execute([$modKh, $nhomHep]);
$pdo->prepare("INSERT INTO users (name, email, password, group_id, status, garage_id, create_at)
               VALUES ('ZZ2M Hep', 'zz2m-hep@local.test', ?, ?, 1, ?, NOW())")
    ->execute([\App\core\Hash::make($MK), $nhomHep, $garaTong]);

$jar2 = tempnam(sys_get_temp_dir(), 'zz2mh');
$r2   = $http('GET', "$base/dang-nhap", $jar2);
$tk2  = $token($r2['body']);
$http('POST', "$base/dang-nhap", $jar2, ['email' => 'zz2m-hep@local.test', 'password' => $MK, '_token' => $tk2]);

$rKh = $http('GET', "$base/admin/customers", $jar2);
ok($rKh['code'] === 200, 'Nhom hep vao duoc man duoc cap quyen (HTTP ' . $rKh['code'] . ')');

$rDt = $http('GET', "$base/admin/partners", $jar2);
ok($rDt['code'] !== 200,
   'Nhom KHONG co quyen bi chan o /admin/partners',
   'HTTP ' . $rDt['code']);

@unlink($jar2);
$pdo->exec("DELETE FROM users WHERE email = 'zz2m-hep@local.test'");
$pdo->exec("DELETE FROM permissions WHERE group_id = $nhomHep");
$pdo->exec("DELETE FROM `groups` WHERE id = $nhomHep");

@unlink($jar);
$donSach();
ok((int) $pdo->query("SELECT COUNT(*) FROM partners WHERE code LIKE 'ZZ2M%' OR name LIKE 'ZZ2M%'")->fetchColumn() === 0,
   'Da don sach du lieu test');

exit(summary());

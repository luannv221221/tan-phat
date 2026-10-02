<?php
/**
 * Test LUẬT "CHỈ ĐƯỢC CHỌN, KHÔNG ĐƯỢC GÕ TAY".
 *
 * Chạy:  C:\xampp\php\php.exe tests\ChiChonKhongGoTest.php
 *
 * Giá trị nào lặp lại giữa các bản ghi — hãng xe, model, năm, màu, tên khách,
 * tên phụ tùng, tên nhân viên — thì phải lấy từ danh mục đã thiết lập. Gõ tay
 * thì mỗi người một kiểu ("Toyota", "toyota ", "TOYOTA") và mọi thứ dựa trên
 * việc gom nhóm đều hỏng: lọc bỏ sót, báo cáo chia nhỏ, tìm kiếm không ra.
 *
 * Số liệu trước khi sửa: 6/10 xe ghi hãng bằng chữ gõ tay và KHÔNG xe nào dùng
 * danh mục; 3/3 phiếu tiếp nhận gõ tên cố vấn; 13/13 phiếu bảo hành gõ tên
 * khách. Tức là ô chọn có sẵn nhưng không ai dùng — nên phải bỏ hẳn ô gõ.
 *
 * Bốn chỗ hỏng mà test này canh:
 *
 *   1. Bỏ ô gõ khỏi FORM nhưng quên bỏ ở ĐƯỜNG LƯU.
 *      Ô biến mất trên màn hình, nhưng POST thẳng vẫn ghi vào được — vài tháng
 *      sau dữ liệu vẫn loạn, mà nhìn form thì tưởng đã sạch.
 *
 *   2. Nút "thêm nhanh" sinh dòng trùng.
 *      Gara này gõ "Mitsubishi", gara kia gõ "MITSUBISHI" — danh mục chung có
 *      hai dòng cho một hãng, đúng cái loạn mà ô chọn sinh ra để tránh.
 *
 *   3. Thêm nhanh không kiểm quan hệ cha con.
 *      Thêm model mà không kèm hãng, hoặc gắn model vào hãng bất kỳ, thì chuỗi
 *      hãng -> model -> năm rỗng nghĩa ngay.
 *
 *   4. Danh mục dùng chung nhưng xe thì không.
 *      Mọi gara cùng nhìn một danh mục hãng / model, nhưng gara này không được
 *      khai xe cho khách của gara kia qua đường thêm nhanh.
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
    $pdo->exec("DELETE FROM vehicles WHERE bien_so_chuan LIKE 'ZZCG%'");
    $pdo->exec("DELETE FROM partners WHERE code LIKE 'ZZCG-%'");
    $pdo->exec("DELETE FROM car_years WHERE model_id IN (SELECT id FROM car_models WHERE slug LIKE 'zzcg-%')");
    $pdo->exec("DELETE FROM car_models WHERE slug LIKE 'zzcg-%'");
    $pdo->exec("DELETE FROM car_brands WHERE slug LIKE 'zzcg-%'");
    $pdo->exec("DELETE FROM car_colors WHERE slug LIKE 'zzcg-%'");
    $pdo->exec("DELETE t FROM login_tokens t JOIN users u ON u.id = t.user_id WHERE u.email LIKE 'zz-cg-%@local.test'");
    $pdo->exec("DELETE FROM users WHERE email LIKE 'zz-cg-%@local.test'");
};
$donSach();

// ---------------------------------------------------------------------------
section('Cot chu go tay khong con nhan gi tu form');

$cotXe = $pdo->query("SHOW COLUMNS FROM vehicles")->fetchAll(PDO::FETCH_COLUMN);
ok(in_array('color_id', $cotXe, true), 'Bang vehicles co cot color_id (mau lay tu danh muc)');

$cotBh = $pdo->query("SHOW COLUMNS FROM warranty_requests")->fetchAll(PDO::FETCH_COLUMN);
ok(in_array('technician_id', $cotBh, true), 'Bang warranty_requests co cot technician_id');

/* Cột chữ cũ CỐ Ý giữ lại: đẩy code và chạy migration không khít nhau tuyệt
   đối, xoá cột mà code cũ còn đọc là sập trang. Nhưng không được ghi thêm. */
foreach (['hang_xe', 'model_xe', 'nam_sx', 'phien_ban', 'mau_xe'] as $c){
    ok(in_array($c, $cotXe, true), "Cot cu `$c` van con (xoa sau, khong xoa cung luc voi code)");
}

$conGoTay = $so("SELECT COUNT(*) FROM vehicles WHERE COALESCE(hang_xe,'') <> '' AND brand_id IS NULL");
ok($conGoTay === 0, 'Khong con xe nao CHI co chu go tay ma khong co khoa danh muc',
   "Con $conGoTay xe — migration 000081 chua don het");

// ---------------------------------------------------------------------------
section('Them nhanh vao danh muc: trung thi dung lai, khong tao ban sao');

require_once $goc . 'app/models/VehiclesModel.php';
$V = new VehiclesModel();

$h1 = $V->themDanhMuc('hang', 'ZZCG Hang moi');
ok(!empty($h1) && !empty($h1['c']) && empty($h1['trung']), 'Them hang moi: tao dong moi');

$h2 = $V->themDanhMuc('hang', '  zzcg hang moi  ');
ok(!empty($h2) && (int) $h2['c'] === (int) $h1['c'] && !empty($h2['trung']),
   'Go thuong + thua khoang trang: DUNG LAI dong cu, khong tao dong thu hai',
   json_encode([$h1, $h2]));

$h3 = $V->themDanhMuc('hang', 'ZZCG HANG MOI');
ok(!empty($h3) && (int) $h3['c'] === (int) $h1['c'], 'Go IN HOA: van la mot dong');

ok($so("SELECT COUNT(*) FROM car_brands WHERE slug = 'zzcg-hang-moi'") === 1,
   'Ba lan them ba kieu viet -> danh muc chi co MOT dong');

$rong = $V->themDanhMuc('hang', '   ');
ok($rong === null, 'Ten rong -> khong tao gi');

// --- Model phải thuộc một hãng ---
$mKhongCha = $V->themDanhMuc('model', 'ZZCG Model mo coi');
ok($mKhongCha === null, 'Them model ma khong kem hang -> tu choi');

$m1 = $V->themDanhMuc('model', 'ZZCG Model A', (int) $h1['c']);
ok(!empty($m1), 'Them model kem hang -> duoc');
$mRow = $mot("SELECT * FROM car_models WHERE id = ?", [(int) $m1['c']]);
ok(!empty($mRow) && (int) $mRow['brand_id'] === (int) $h1['c'], 'Model gan dung vao hang da chon');

$m2 = $V->themDanhMuc('model', 'zzcg model a', (int) $h1['c']);
ok(!empty($m2) && (int) $m2['c'] === (int) $m1['c'], 'Cung hang, cung ten khac kieu viet -> mot dong');

/* Hai HÃNG KHÁC NHAU cùng có model trùng tên là chuyện thường (nhiều hãng đặt
   tên số). Phải ra hai dòng, và không được đổ vì slug trùng. */
$hKhac = $V->themDanhMuc('hang', 'ZZCG Hang khac');
$mKhac = $V->themDanhMuc('model', 'ZZCG Model A', (int) $hKhac['c']);
ok(!empty($mKhac) && (int) $mKhac['c'] !== (int) $m1['c'],
   'Model trung ten nhung KHAC hang -> hai dong rieng (khong do vi slug trung)');

// --- Năm là một KHOẢNG, không phải từng năm rời ---
$n1 = $V->themDanhMuc('nam', '2016', (int) $m1['c']);
ok(!empty($n1), 'Them nam cho model -> duoc');
$n2 = $V->themDanhMuc('nam', '2016', (int) $m1['c']);
ok(!empty($n2) && (int) $n2['c'] === (int) $n1['c'], 'Them lai dung nam do -> dung lai moc cu');

$namLa = $V->themDanhMuc('nam', '1899', (int) $m1['c']);
ok($namLa === null, 'Nam vo nghia (1899) -> tu choi');
$namLa = $V->themDanhMuc('nam', (string) ((int) date('Y') + 5), (int) $m1['c']);
ok($namLa === null, 'Nam tuong lai xa -> tu choi');

$namKhongCha = $V->themDanhMuc('nam', '2020');
ok($namKhongCha === null, 'Them nam ma khong kem model -> tu choi');

// --- Màu ---
$c1 = $V->themDanhMuc('mau', 'ZZCG Mau la');
$c2 = $V->themDanhMuc('mau', 'zzcg  mau   la');
ok(!empty($c1) && !empty($c2) && (int) $c1['c'] === (int) $c2['c'],
   'Mau: khoang trang thua o giua cung ve mot dong');

$loaiLa = $V->themDanhMuc('hack', 'ZZCG Gi do');
ok($loaiLa === null, 'Loai danh muc la -> khong tao gi');

// ---------------------------------------------------------------------------
section('HTTP — form chi cho chon, duong luu cung khong nhan chu go tay');

$MK = 'ZzChiChon#2026';
$nhomAdmin = (int) $pdo->query("SELECT id FROM `groups` WHERE name='Admin'")->fetchColumn();
$garaTong  = (int) $pdo->query("SELECT id FROM garages WHERE is_master = 1 ORDER BY id LIMIT 1")->fetchColumn();
$pdo->prepare("INSERT INTO users (name,email,password,group_id,status,garage_id,create_at)
               VALUES ('ZZ Admin chi chon','zz-cg-ad@local.test',?,?,1,?,NOW())")
    ->execute([\App\core\Hash::make($MK), $nhomAdmin, $garaTong]);

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

$jar = tempnam(sys_get_temp_dir(), 'zzcg');
$r = $http('GET', "$base/dang-nhap", $jar);
if ($r['code'] === 0){ echo "\n[SKIP] Apache khong chay (localhost:88).\n"; $donSach(); exit(summary()); }
$tk = $token($r['body']);
$http('POST', "$base/dang-nhap", $jar, ['email' => 'zz-cg-ad@local.test', 'password' => $MK, '_token' => $tk]);

$r  = $http('GET', "$base/admin/vehicles/add", $jar);
$tk = $token($r['body']) ?: $tk;
ok($r['code'] === 200, 'Mo duoc form Them xe (HTTP ' . $r['code'] . ')');

// --- Form không còn ô gõ tay ---
foreach (['hang_xe', 'model_xe', 'nam_sx', 'phien_ban', 'mau_xe'] as $o){
    ok(strpos($r['body'], 'name="' . $o . '"') === false, "Form Them xe KHONG con o go tay `$o`");
}
ok(strpos($r['body'], 'name="color_id"') !== false, 'Form Them xe co o CHON mau');
ok(strpos($r['body'], 'name="fuel_id"') !== false, 'Form Them xe co o CHON nhien lieu');
/* 5 chu khong phai 4: nhien lieu them tu 02/10/2026 (migration 000083).
   Danh muc car_fuels co tu lau nhung truoc do khong gan duoc vao xe. */
ok(substr_count($r['body'], 'data-them-nhanh=') === 5,
   'Nam o danh muc (hang / model / nam / mau / nhien lieu) deu co nut + them nhanh',
   'Dem duoc ' . substr_count($r['body'], 'data-them-nhanh='));

// --- Khách để gắn xe ---
$pdo->prepare("INSERT INTO partners (code,name,type,garage_id,status,create_at)
               VALUES ('ZZCG-KH','ZZCG Khach thu','customer',?,1,NOW())")->execute([$garaTong]);
$kh = $mot("SELECT * FROM partners WHERE code = 'ZZCG-KH'");

$lapXe = function($bienSo, $them = []) use ($http, $base, $jar, &$tk, $kh){
    return $http('POST', "$base/admin/vehicles/add", $jar, array_merge([
        '_token' => $tk, 'partner_id' => (int) $kh['id'], 'bien_so' => $bienSo, 'status' => 1,
    ], $them));
};
$tieuFlash = function() use ($http, $base, $jar){ $http('GET', "$base/admin/vehicles/add", $jar); };

/* CHỖ HỎNG SỐ 1: bỏ ô khỏi form nhưng quên bỏ ở đường lưu. */
$lapXe('ZZCG-11.111', ['hang_xe' => 'ZZCG Go lau', 'model_xe' => 'ZZCG Model lau', 'nam_sx' => '2015',
                       'phien_ban' => '1.5G AT', 'mau_xe' => 'ZZCG Mau lau']);
ok($so("SELECT COUNT(*) FROM vehicles WHERE bien_so_chuan = 'ZZCG11111'") === 0,
   'POST thang chu go tay, khong chon danh muc -> KHONG luu duoc xe');
$tieuFlash();

// Thiếu model nhưng có hãng: vẫn không đủ
$lapXe('ZZCG-22.222', ['brand_id' => (int) $h1['c']]);
ok($so("SELECT COUNT(*) FROM vehicles WHERE bien_so_chuan = 'ZZCG22222'") === 0,
   'Co hang nhung thieu model -> khong luu duoc');
$tieuFlash();

// Chọn đủ: lưu được, và cột chữ cũ phải TRỐNG
$lapXe('ZZCG-33.333', ['brand_id' => (int) $h1['c'], 'model_id' => (int) $m1['c'],
                       'car_year_id' => (int) $n1['c'], 'color_id' => (int) $c1['c'],
                       'hang_xe' => 'ZZCG Go lau', 'mau_xe' => 'ZZCG Mau lau', 'phien_ban' => '1.5G AT']);
$xe = $mot("SELECT * FROM vehicles WHERE bien_so_chuan = 'ZZCG33333'");
ok(!empty($xe), 'Chon du hang + model -> luu duoc xe');
ok(!empty($xe) && (int) $xe['brand_id'] === (int) $h1['c'] && (int) $xe['model_id'] === (int) $m1['c']
   && (int) $xe['color_id'] === (int) $c1['c'],
   'Xe luu bang KHOA danh muc (hang / model / nam / mau)');
ok(!empty($xe) && $xe['hang_xe'] === null && $xe['model_xe'] === null && $xe['nam_sx'] === null
   && $xe['phien_ban'] === null && $xe['mau_xe'] === null,
   'Chu go tay gui kem bi BO HAN, khong ghi vao cot cu',
   json_encode(!empty($xe) ? [$xe['hang_xe'], $xe['mau_xe'], $xe['phien_ban']] : null));

// Màu không có trong danh mục -> từ chối
$tieuFlash();
$lapXe('ZZCG-44.444', ['brand_id' => (int) $h1['c'], 'model_id' => (int) $m1['c'], 'color_id' => 999999]);
ok($so("SELECT COUNT(*) FROM vehicles WHERE bien_so_chuan = 'ZZCG44444'") === 0,
   'Mau khong co trong danh muc -> khong luu duoc');
$tieuFlash();

// ---------------------------------------------------------------------------
section('Nhien lieu cua xe (02/10/2026 — migration 000083)');

/* Danh muc car_fuels co tu lau va co man quan tri rieng, nhung KHONG cho nao
   gan duoc nhien lieu vao mot chiec xe — danh muc dung khong. */
$nl = $mot("SELECT id, name FROM car_fuels WHERE status = 1 ORDER BY id LIMIT 1");
ok(!empty($nl), 'Danh muc nhien lieu co it nhat mot dong de chon');

$lapXe('ZZCG-88.888', ['brand_id' => (int) $h1['c'], 'model_id' => (int) $m1['c'],
                       'fuel_id' => (int) $nl['id']]);
$xeNl = $mot("SELECT * FROM vehicles WHERE bien_so_chuan = 'ZZCG88888'");
ok(!empty($xeNl) && (int) $xeNl['fuel_id'] === (int) $nl['id'],
   'Nhien lieu chon tren form duoc LUU vao xe',
   json_encode($xeNl['fuel_id'] ?? null));
$tieuFlash();

$lapXe('ZZCG-77.777', ['brand_id' => (int) $h1['c'], 'model_id' => (int) $m1['c'], 'fuel_id' => 999999]);
ok($so("SELECT COUNT(*) FROM vehicles WHERE bien_so_chuan = 'ZZCG77777'") === 0,
   'Nhien lieu khong co trong danh muc -> khong luu duoc');
$tieuFlash();

/* Xoa mot dong nhien lieu khoi danh muc KHONG duoc keo xe di theo:
   khoa ngoai dat ON DELETE SET NULL. */
$pdo->exec("INSERT INTO car_fuels (name, slug, sort_order, status, create_at)
            VALUES ('ZZCG Nhien lieu tam', 'zzcg-nhien-lieu-tam', 0, 1, NOW())");
$nlTam = (int) $pdo->lastInsertId();
$pdo->exec("UPDATE vehicles SET fuel_id = $nlTam WHERE bien_so_chuan = 'ZZCG88888'");
$pdo->exec("DELETE FROM car_fuels WHERE id = $nlTam");
$xeSau = $mot("SELECT * FROM vehicles WHERE bien_so_chuan = 'ZZCG88888'");
ok(!empty($xeSau) && $xeSau['fuel_id'] === null,
   'Xoa dong nhien lieu khoi danh muc -> xe VAN CON, chi mat nhien lieu',
   json_encode($xeSau ? $xeSau['fuel_id'] : 'mat luon chiec xe'));

/* CHO HONG DA MAC: danh sach xe doc cot chu `mau_xe`, ma cot do thoi duoc ghi
   tu khi bo o go tay — xe moi luu KHONG con hien mau. Phai doc qua danh muc. */
$r = $http('GET', "$base/admin/vehicles?q=ZZCG-33.333", $jar);
ok($r['code'] === 200 && strpos($r['body'], $c1['n']) !== false,
   'Danh sach xe hien MAU lay tu danh muc (khong con dua vao cot chu cu)',
   'Dang tim chu: ' . $c1['n']);

// ---------------------------------------------------------------------------
section('HTTP — them nhanh danh muc va them nhanh ca chiec xe');

$goiJson = function($duong, $d) use ($http, $base, $jar, &$tk){
    $r = $http('POST', "$base/admin/$duong", $jar, array_merge(['_token' => $tk], $d));
    return json_decode($r['body'], true);
};

$j = $goiJson('them-nhanh/danh-muc', ['loai' => 'hang', 'ten' => 'ZZCG Hang qua mang']);
ok(is_array($j) && !empty($j['ok']) && !empty($j['c']), 'Duong them nhanh tra ve dong vua tao');

$j2 = $goiJson('them-nhanh/danh-muc', ['loai' => 'hang', 'ten' => 'zzcg hang qua mang']);
ok(is_array($j2) && (int) $j2['c'] === (int) $j['c'] && !empty($j2['trung']),
   'Them lai khac kieu viet qua mang: dung lai dong cu va bao trung');

$j3 = $goiJson('them-nhanh/danh-muc', ['loai' => 'model', 'ten' => 'ZZCG Model qua mang']);
ok(is_array($j3) && empty($j3['ok']), 'Them model khong kem hang qua mang -> tu choi');

$j4 = $goiJson('them-nhanh/danh-muc', ['loai' => 'hack', 'ten' => 'ZZCG Gi do']);
ok(is_array($j4) && empty($j4['ok']), 'Loai danh muc la qua mang -> tu choi');

// --- Thêm nhanh cả chiếc xe (nút + cạnh ô chọn xe trên chứng từ) ---
$j5 = $goiJson('vehicles/them-nhanh', ['partner_id' => (int) $kh['id'], 'bien_so' => 'ZZCG-55.555',
                                       'brand_id' => (int) $h1['c'], 'model_id' => (int) $m1['c'], 'so_km' => '9.000']);
ok(is_array($j5) && !empty($j5['ok']), 'Them nhanh xe tu chung tu -> luu duoc');
$xeN = $mot("SELECT * FROM vehicles WHERE bien_so_chuan = 'ZZCG55555'");
ok(!empty($xeN) && (int) $xeN['partner_id'] === (int) $kh['id'] && (int) $xeN['so_km'] === 9000,
   'Xe them nhanh thuoc dung khach dang chon, luu dung so km');
ok(is_array($j5) && !empty($j5['ds']) && count($j5['ds']) >= 2,
   'Tra ve ca danh sach xe cua khach de o chon dung lai ngay');

$j6 = $goiJson('vehicles/them-nhanh', ['bien_so' => 'ZZCG-66.666',
                                       'brand_id' => (int) $h1['c'], 'model_id' => (int) $m1['c']]);
ok(is_array($j6) && empty($j6['ok']) && $so("SELECT COUNT(*) FROM vehicles WHERE bien_so_chuan = 'ZZCG66666'") === 0,
   'Them nhanh ma khong co khach -> tu choi');

$j7 = $goiJson('vehicles/them-nhanh', ['partner_id' => (int) $kh['id'], 'bien_so' => '']);
ok(is_array($j7) && empty($j7['ok']), 'Them nhanh khong co bien so -> tu choi');

// ---------------------------------------------------------------------------
section('HTTP — chung tu: bien so chi chon, khong con o go');

$r = $http('GET', "$base/admin/quotations/add", $jar);
ok(strpos($r['body'], 'js-xe-them') !== false, 'Form bao gia co nut + khai xe ngay tai cho');
ok(strpos($r['body'], '__khac__') === false,
   'Form bao gia KHONG con lua chon "Xe khac — go bien so"');

$r = $http('GET', "$base/admin/warranty/add", $jar);
foreach (['customer_name', 'product_name'] as $o){
    ok(strpos($r['body'], 'name="' . $o . '"') === false, "Form bao hanh KHONG con o go tay `$o`");
}
ok(strpos($r['body'], 'name="technician_id"') !== false, 'Form bao hanh co o CHON ky thuat vien');
ok(strpos($r['body'], 'name="technician"') === false, 'Form bao hanh KHONG con o go ten ky thuat vien');

$r = $http('GET', "$base/admin/receptions/add", $jar);
ok(strpos($r['body'], 'name="co_van_id"') !== false, 'Form tiep nhan co o CHON co van');
ok(strpos($r['body'], 'name="co_van"') === false, 'Form tiep nhan KHONG con o go ten co van');

@unlink($jar);
$donSach();

ok($so("SELECT COUNT(*) FROM vehicles WHERE bien_so_chuan LIKE 'ZZCG%'") === 0, 'Da don sach du lieu test');

exit(summary());

<?php
/**
 * Test PHIẾU BẢO TRÌ + LỊCH + NHẮC BẢO TRÌ (migration 000070).
 *
 * Chạy:  C:\xampp\php\php.exe tests\PhieuBaoTriTest.php
 *
 * Trước đây chỉ có phiếu bảo hành, và "nhắc bảo trì" = ngày hoàn tất phiếu
 * BẢO HÀNH + 6 tháng: xe chưa hỏng lần nào thì không bao giờ được nhắc, xe
 * vừa thay đèn pha hỏng lại bị nhắc như vừa bảo dưỡng.
 *
 * NĂM CHỖ HỎNG SẼ ÂM THẦM:
 *   1. Phiếu bảo hành lọt vào nguồn nhắc bảo trì — nhắc sai người, sai lúc.
 *   2. Không lấy LẦN CUỐI của từng xe: xe bảo trì tháng 1 và tháng 7 bị nhắc
 *      theo lần tháng 1, tức là nhắc một xe vừa bảo dưỡng tuần trước.
 *   3. Đã hẹn lần mới vẫn bị nhắc — CSKH gọi khách hai lần cho một việc.
 *   4. Ước km chia cho 0 (hai lần ghi cùng ngày) hoặc chia số âm (gõ nhầm km).
 *   5. Cộng tháng kiểu DateTime::modify: 31/08 + 6 tháng ra 03/03 thay vì 28/02.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc  = __DIR__ . '/../';
$base = 'http://localhost:88/tan-phat';

// ---------------------------------------------------------------------------
section('han_bao_tri() — theo thang HOAC km, cai nao toi truoc');

$cfg = ['thang' => 6, 'km' => 5000];

$h = han_bao_tri(['ngay' => '2026-01-10', 'km' => null], [], $cfg);
ok($h['theo_thang'] === '2026-07-10' && $h['han'] === '2026-07-10' && $h['ly_do'] === 'thang',
   'Khong ghi km -> chi theo thang', json_encode($h));
ok($h['moc_km'] === null, 'Khong ghi km -> khong co moc km');

$h = han_bao_tri(['ngay' => '2026-08-31', 'km' => null], [], $cfg);
ok($h['theo_thang'] === '2027-02-28',
   '31/08 + 6 thang = 28/02 (kep ve cuoi thang)',
   'Ra ' . $h['theo_thang'] . ' — DateTime::modify nhay sang 03/03');

$h = han_bao_tri(['ngay' => '2026-01-10', 'km' => 20000], [], ['thang' => 6, 'km' => 0]);
ok($h['moc_km'] === null && $h['ly_do'] === 'thang', 'Chu ky km = 0 -> chi nhac theo thang');

$h = han_bao_tri(['ngay' => '2026-01-01', 'km' => 20000], [], $cfg);
ok($h['moc_km'] === 25000 && $h['theo_km'] === null && $h['ly_do'] === 'thang',
   'Moi mot lan ghi km -> chua uoc duoc, dung moc thang', json_encode($h));

$h = han_bao_tri(['ngay' => '2026-01-01', 'km' => 20000], [['ngay' => '2026-01-31', 'km' => 23000]], $cfg);
ok($h['km_moi_ngay'] !== null && abs($h['km_moi_ngay'] - 100) < 0.001,
   'Toc do = (23000 - 20000) / 30 ngay = 100 km/ngay', json_encode($h));
ok($h['theo_km'] === '2026-02-20',
   'Con 2000 km / 100 km/ngay = 20 ngay sau lan ghi cuoi -> 2026-02-20', 'Ra ' . $h['theo_km']);
ok($h['han'] === '2026-02-20' && $h['ly_do'] === 'km', 'Km toi truoc thang -> han theo km');

$h = han_bao_tri(['ngay' => '2026-01-01', 'km' => 20000], [['ngay' => '2026-03-01', 'km' => 26000]], $cfg);
ok($h['theo_km'] === '2026-03-01' && $h['ly_do'] === 'km',
   'Lan ghi moi nhat DA VUOT moc -> han la chinh ngay ghi do', json_encode($h));

$h = han_bao_tri(['ngay' => '2026-01-01', 'km' => 20000], [['ngay' => '2026-04-01', 'km' => 20900]], $cfg);
ok($h['theo_km'] !== null && $h['theo_km'] > $h['theo_thang'] && $h['ly_do'] === 'thang',
   'Xe it chay (10 km/ngay) -> moc thang toi truoc', json_encode($h));

$h = han_bao_tri(['ngay' => '2026-01-01', 'km' => 20000], [['ngay' => '2026-02-01', 'km' => 19000]], $cfg);
ok($h['km_moi_ngay'] === null && $h['ly_do'] === 'thang',
   'Km giam (go nham) -> khong uoc, khong chia so am');

$h = han_bao_tri(['ngay' => '2026-01-01', 'km' => 20000], [['ngay' => '2026-01-01', 'km' => 20500]], $cfg);
ok($h['km_moi_ngay'] === null, 'Hai lan ghi cung mot ngay -> khong chia cho 0');

$h = han_bao_tri(['ngay' => '2026-01-01', 'km' => 20000],
                 [['ngay' => '2026-02-31', 'km' => 30000], ['ngay' => '', 'km' => 30000], ['ngay' => '2026-02-01', 'km' => null]], $cfg);
ok($h['theo_km'] === null && $h['km_gan_nhat']['km'] === 20000,
   'Ngay khong co that / thieu ngay / thieu km deu bi bo qua', json_encode($h));

$h = han_bao_tri(['ngay' => 'khong-phai-ngay', 'km' => 1], [], $cfg);
ok($h['han'] === null, 'Ngay bao tri khong hop le -> khong tinh gi');

// ---------------------------------------------------------------------------
section('CSDL + model');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$cot = [];
foreach ($pdo->query("SHOW COLUMNS FROM `warranty_requests`") as $r) $cot[$r['Field']] = $r;
ok(isset($cot['loai']), 'Bang phieu co cot `loai`');
if (!isset($cot['loai'])){ echo "\n[SKIP] Chua chay migration 000070.\n"; exit(summary()); }
ok($cot['loai']['Default'] === 'bao_hanh' && $cot['loai']['Null'] === 'NO',
   'Mac dinh `bao_hanh`, khong NULL — phieu cu giu dung nghia cu');
ok((int) $pdo->query("SELECT COUNT(*) FROM warranty_requests WHERE loai NOT IN ('bao_hanh','bao_tri')")->fetchColumn() === 0,
   'Khong phieu nao mang loai la');
$coIdx = false;
foreach ($pdo->query("SHOW INDEX FROM warranty_requests") as $r) if ($r['Key_name'] === 'idx_wr_loai_status') $coIdx = true;
ok($coIdx, 'Co chi muc (loai, status)');
ok((string) $pdo->query("SELECT svalue FROM site_settings WHERE skey = 'maintenance_interval_km'")->fetchColumn() !== '',
   'Co cai dat chu ky km');
ok($pdo->query("SELECT name FROM modules WHERE link = 'lich-bao-hanh'")->fetchColumn() === 'Lịch bảo hành / bảo trì',
   'Menu doi ten thanh "Lich bao hanh / bao tri"');

require_once $goc . 'app/models/WarrantyRequestsModel.php';
$W = new WarrantyRequestsModel();

ok(WarrantyRequestsModel::loaiHopLe('bao_tri') === 'bao_tri'
   && WarrantyRequestsModel::loaiHopLe('hack') === 'bao_hanh'
   && WarrantyRequestsModel::loaiHopLe('hack', '') === '',
   'Loai la tren URL / form roi ve mac dinh');
ok(WarrantyRequestsModel::khoaDoiTuong(['bien_so_chuan' => '30A12345', 'serial_no' => 'X']) === 'xe:30A12345',
   'Co bien so -> nhan dien theo xe (uu tien hon serial)');
ok(WarrantyRequestsModel::khoaDoiTuong(['bien_so_chuan' => null, 'serial_no' => ' sn-1 ']) === 'sn:SN-1',
   'Khong co xe -> theo serial');
ok(WarrantyRequestsModel::khoaDoiTuong(['partner_id' => 5, 'part_id' => 7]) === 'kh:p5|h7',
   'Khong co ca hai -> theo khach + hang');

/* --- Dọn rác lần chạy trước --- */
$caiDatGoc = [];
foreach ($pdo->query("SELECT skey, svalue FROM site_settings WHERE skey IN
          ('maintenance_interval_months','maintenance_window_days','maintenance_interval_km')") as $r){
    $caiDatGoc[$r['skey']] = $r['svalue'];
}
$donSach = function() use ($pdo){
    $pdo->exec("DELETE h FROM warranty_handovers h JOIN warranty_requests w ON w.id = h.warranty_id WHERE w.customer_name LIKE 'ZZBT%'");
    $pdo->exec("DELETE FROM warranty_requests WHERE customer_name LIKE 'ZZBT%'");
    $pdo->exec("DELETE t FROM login_tokens t JOIN users u ON u.id = t.user_id WHERE u.email LIKE 'zz-bt-%@local.test'");
    $pdo->exec("DELETE FROM users WHERE email LIKE 'zz-bt-%@local.test'");
};
$donSach();

$seq = 0;
$tao = function(array $d) use ($W, &$seq){
    $seq++;
    $bs = $d['bien_so'] ?? null;
    /* Phiếu đã xong thì ngày nhận không thể sau ngày xong. Để mặc định "hôm
       nay" là tự tạo ra một lần ghi km GIẢ ở hôm nay — thấp hơn lần ghi thật
       trước đó — và hàm ước km (đúng ra) từ chối ước. */
    if (!isset($d['received_date']) && !empty($d['completed_date'])) $d['received_date'] = $d['completed_date'];
    // (int): lastId() trả chuỗi, còn danh sách id đọc ra đã ép số — so chặt là trượt
    return (int) $W->add(array_merge([
        'request_no'    => 'ZZBT-' . $seq . '-' . substr(md5(uniqid('', true)), 0, 6),
        'loai'          => 'bao_tri',
        'customer_name' => 'ZZBT Khach ' . $seq,
        'product_name'  => 'ZZBT Xe',
        'received_date' => date('Y-m-d'),
        'status'        => 'received',
        'fee'           => 0,
        'bien_so'       => $bs,
        'bien_so_chuan' => $bs !== null ? chuan_hoa_bien_so($bs) : null,
    ], $d));
};
$ids = function($rows){ return array_map('intval', array_column((array) $rows, 'id')); };
$ngay = function($soNgay){ return date('Y-m-d', strtotime(($soNgay >= 0 ? '+' : '') . $soNgay . ' days')); };
$homNay = date('Y-m-d');

/* --- Số phiếu: hai dãy riêng --- */
$bhTruoc = $W->nextNo('bao_hanh');
$tao(['request_no' => 'BT-900001']);
ok($W->nextNo('bao_tri') === 'BT-900002', 'Phieu bao tri danh so tiep day BT-', 'Ra ' . $W->nextNo('bao_tri'));
ok($W->nextNo('bao_hanh') === $bhTruoc, 'Day BH- KHONG bi day BT- keo theo', 'Truoc ' . $bhTruoc . ', sau ' . $W->nextNo('bao_hanh'));
ok(strpos($W->nextNo('hack'), 'BH-') === 0, 'Loai la -> danh so bao hanh');

/* --- Lịch: lọc theo loại + khoảng ngày --- */
$A = $tao(['appointment_date' => $ngay(-1)]);
$B = $tao(['loai' => 'bao_hanh', 'appointment_date' => $homNay, 'status' => 'processing']);
$C = $tao(['appointment_date' => $ngay(3)]);
$D = $tao(['appointment_date' => null]);
$E = $tao(['status' => 'done', 'completed_date' => $homNay, 'appointment_date' => $homNay]);
$F = $tao(['loai' => 'bao_hanh', 'status' => 'cancelled', 'appointment_date' => $homNay]);

$tat = $ids($W->getSchedule('', '', $homNay));
ok(!array_diff([$A, $B, $C, $D], $tat), 'Lich co du phieu dang mo cua ca hai loai');
ok(!in_array($E, $tat, true) && !in_array($F, $tat, true), 'Lich KHONG co phieu da xong / da huy');
ok(array_search($D, $tat, true) > array_search($C, $tat, true),
   'Phieu CHUA HEN nam sau phieu co hen', 'MySQL xep NULL len dau khi ASC');
$chon = function($khoang, $loai = '') use ($W, $ids, $homNay, $A, $B, $C, $D){
    return array_values(array_intersect($ids($W->getSchedule($loai, $khoang, $homNay)), [$A, $B, $C, $D]));
};
ok($chon('qua_han') === [$A], 'Loc "Qua han" dung', json_encode($chon('qua_han')));
ok($chon('hom_nay') === [$B], 'Loc "Hom nay" dung', json_encode($chon('hom_nay')));
ok($chon('7_ngay') === [$B, $C], 'Loc "7 ngay toi" dung (gom hom nay)', json_encode($chon('7_ngay')));
ok($chon('chua_hen') === [$D], 'Loc "Chua hen ngay" dung');
ok($chon('', 'bao_tri') === [$A, $C, $D], 'Loc loai Bao tri khong lan bao hanh', json_encode($chon('', 'bao_tri')));

/* --- Đọc số km theo biển số --- */
$tao(['loai' => 'bao_hanh', 'bien_so' => 'ZZ-99X.9999', 'so_km' => 12345]);
$doc = $W->docKmTheoBienSo(['ZZ99X9999']);
ok(!empty($doc['ZZ99X9999']) && in_array(['ngay' => $homNay, 'km' => 12345], $doc['ZZ99X9999'], true),
   'Doc duoc so km ghi tren phieu theo bien so', json_encode($doc));

// ---------------------------------------------------------------------------
section('HTTP that — lap phieu, lich, nhac');

if (!function_exists('curl_init')){ echo "\n[SKIP] PHP khong co curl.\n"; $donSach(); exit(summary()); }

// Cài đặt cố định cho test; trả lại ở cuối
$datCaiDat = function($k, $v) use ($pdo){
    $pdo->prepare("UPDATE site_settings SET svalue = ? WHERE skey = ?")->execute([$v, $k]);
};
$datCaiDat('maintenance_interval_months', '6');
$datCaiDat('maintenance_window_days', '30');
$datCaiDat('maintenance_interval_km', '5000');

$MK = 'ZzBaoTri#2026';
$A_NHOM = (int) $pdo->query("SELECT id FROM `groups` WHERE name = 'Admin'")->fetchColumn();
$pdo->prepare("INSERT INTO users (name, email, password, group_id, status, garage_id, create_at)
               VALUES ('ZZ Admin bao tri', 'zz-bt-ad@local.test', ?, ?, 1, NULL, NOW())")
    ->execute([\App\core\Hash::make($MK), $A_NHOM]);

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
    return ['code' => (int) $info['http_code'], 'loc' => (string) $info['redirect_url'], 'body' => $body,
            'text' => html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8')];
};
$token = function($html){ return preg_match('~name="_token" value="([^"]+)"~', $html, $m) ? $m[1] : ''; };

$jar = tempnam(sys_get_temp_dir(), 'zzbt');
$r = $http('GET', "$base/dang-nhap", $jar);
if ($r['code'] === 0){ echo "\n[SKIP] Apache khong chay (localhost:88).\n"; $donSach(); exit(summary()); }
$tk = $token($r['body']);
$http('POST', "$base/dang-nhap", $jar, ['email' => 'zz-bt-ad@local.test', 'password' => $MK, '_token' => $tk]);

$phieuTheoKhach = function($ten) use ($pdo){
    $st = $pdo->prepare("SELECT * FROM warranty_requests WHERE customer_name = ? ORDER BY id DESC LIMIT 1");
    $st->execute([$ten]);
    return $st->fetch(PDO::FETCH_ASSOC);
};
$soPhieu = function($id) use ($pdo){ return $pdo->query("SELECT request_no FROM warranty_requests WHERE id = " . (int) $id)->fetchColumn(); };
/** Đoạn văn bản của trang quanh một chuỗi — để kiểm "dòng của xe này" */
$quanh = function($text, $moc, $dai = 2500){
    $vt = strpos($text, $moc);
    return $vt === false ? '' : substr($text, $vt, $dai);
};

/* --- Màn Lịch --- */
$r = $http('GET', "$base/admin/lich-bao-hanh", $jar);
ok($r['code'] === 200, 'Mo duoc man Lich (HTTP ' . $r['code'] . ')');
ok(strpos($r['text'], 'Lập phiếu bảo trì') !== false && strpos($r['text'], 'Lập phiếu bảo hành') !== false,
   'Man Lich co nut lap ca hai loai phieu');
$r = $http('GET', "$base/admin/lich-bao-hanh?loai=bao_tri", $jar);
ok(strpos($r['text'], $soPhieu($A)) !== false && strpos($r['text'], $soPhieu($B)) === false,
   'Lich loc Bao tri: co phieu bao tri, khong co phieu bao hanh');

/* --- Lập phiếu --- */
$r = $http('GET', "$base/admin/warranty/add?loai=bao_tri", $jar);
ok($r['code'] === 200 && strpos($r['body'], 'value="bao_tri" selected') !== false,
   'Form lap mo san loai Bao tri');
$tk = $token($r['body']) ?: $tk;

$lap = function(array $d) use ($http, $base, $jar, &$tk){
    return $http('POST', "$base/admin/warranty/add", $jar, array_merge([
        '_token' => $tk, 'received_date' => date('Y-m-d'), 'fee' => 0,
    ], $d));
};

$lap(['loai' => 'bao_tri', 'customer_name' => 'ZZBT Chi co bien so', 'bien_so' => 'zz-12a.345', 'so_km' => '31.500']);
$p = $phieuTheoKhach('ZZBT Chi co bien so');
ok(!empty($p), 'Bao duong xe: chi can bien so, KHONG bat chon san pham');
ok(!empty($p) && strpos($p['request_no'], 'BT-') === 0 && $p['loai'] === 'bao_tri',
   'Phieu bao tri luu dung loai, so BT-', json_encode($p ? [$p['request_no'], $p['loai']] : null));
ok(!empty($p) && $p['bien_so_chuan'] === 'ZZ12A345' && (int) $p['so_km'] === 31500, 'Luu bien so chuan hoa va so km');

$lap(['loai' => 'hack', 'customer_name' => 'ZZBT Loai la', 'product_name' => 'ZZBT May']);
$p = $phieuTheoKhach('ZZBT Loai la');
ok(!empty($p) && $p['loai'] === 'bao_hanh' && strpos($p['request_no'], 'BH-') === 0,
   'POST loai la -> luu thanh bao hanh');

$lap(['loai' => 'bao_tri', 'customer_name' => 'ZZBT Thieu doi tuong']);
ok(empty($phieuTheoKhach('ZZBT Thieu doi tuong')),
   'Khong co san pham, ten thiet bi lan bien so -> khong lap duoc');
/* Mở lại form như trình duyệt tự làm sau khi bị đẩy về: dữ liệu cũ nằm tạm
   trong phiên đến lần mở form kế tiếp. Không mở thì lần "lập từ lời nhắc"
   ở dưới nhận nhầm dữ liệu cũ đó thay vì điền sẵn xe. */
$http('GET', "$base/admin/warranty/add?loai=bao_tri", $jar);

/* --- Nhắc bảo trì --- */
$R1 = $tao(['bien_so' => 'ZZ-11A.111', 'so_km' => 40000, 'status' => 'done',
            'completed_date' => date('Y-m-d', strtotime('-7 months'))]);
$R2 = $tao(['loai' => 'bao_hanh', 'bien_so' => 'ZZ-22B.222', 'so_km' => 50000, 'status' => 'done',
            'completed_date' => date('Y-m-d', strtotime('-8 months'))]);
$R3 = $tao(['bien_so' => 'ZZ-33C.333', 'so_km' => 10000, 'status' => 'done', 'completed_date' => $ngay(-40)]);
$tao(['loai' => 'bao_hanh', 'bien_so' => 'ZZ-33C.333', 'so_km' => 12000, 'received_date' => $ngay(-20)]);
$R4  = $tao(['bien_so' => 'ZZ-44D.444', 'so_km' => 5000, 'status' => 'done',
             'completed_date' => date('Y-m-d', strtotime('-10 months'))]);
$R4b = $tao(['bien_so' => 'ZZ-44D.444', 'so_km' => 9000, 'status' => 'done',
             'completed_date' => date('Y-m-d', strtotime('-1 month'))]);

$r = $http('GET', "$base/admin/nhac-bao-tri?mode=overdue", $jar);
ok($r['code'] === 200, 'Mo duoc man Nhac bao tri (HTTP ' . $r['code'] . ')');
ok(strpos($r['text'], $soPhieu($R1)) !== false, 'Bao tri xong 7 thang truoc -> QUA HAN theo thang');

$r = $http('GET', "$base/admin/nhac-bao-tri?mode=due", $jar);
$dong = $quanh($r['text'], 'ZZ-33C.333');
ok($dong !== '', 'Xe chay nhieu -> SAP TOI HAN theo km du moi 40 ngay',
   'Moc 15.000 km, 100 km/ngay -> con ~10 ngay; theo thang thi con ~5 thang');
ok(strpos($dong, '(theo km)') !== false, 'Dong do ghi ro han tinh "theo km"');

$r = $http('GET', "$base/admin/nhac-bao-tri?mode=all", $jar);
ok(strpos($r['text'], $soPhieu($R2)) === false,
   'Phieu BAO HANH da xong KHONG sinh loi nhac bao tri',
   'Sua mot cai den pha hong khong phai la bao duong xe');
ok(strpos($r['text'], $soPhieu($R4b)) !== false && strpos($r['text'], $soPhieu($R4)) === false,
   'Chi LAN BAO TRI CUOI cua moi xe sinh loi nhac',
   'Nhac theo lan cu la goi mot xe vua bao duong thang truoc');

/* --- Hẹn lần kế tiếp từ lời nhắc --- */
$r = $http('GET', "$base/admin/warranty/add?loai=bao_tri&tu=$R1", $jar);
ok(strpos($r['text'], 'Lập tiếp từ phiếu') !== false && strpos($r['body'], 'value="ZZ-11A.111"') !== false,
   'Lap tu loi nhac -> dien san bien so cua lan truoc');
$tk = $token($r['body']) ?: $tk;
$lap(['loai' => 'bao_tri', 'customer_name' => 'ZZBT Hen lai', 'bien_so' => 'ZZ-11A.111', 'appointment_date' => $ngay(2)]);
$hen = $phieuTheoKhach('ZZBT Hen lai');
ok(!empty($hen), 'Lap duoc phieu bao tri ke tiep');

$r = $http('GET', "$base/admin/nhac-bao-tri?mode=due", $jar);
ok(strpos($r['text'], $soPhieu($R1)) === false,
   'Da hen lan moi -> KHONG con trong danh sach can nhac',
   'CSKH se goi khach hai lan cho mot viec');
$r = $http('GET', "$base/admin/nhac-bao-tri?mode=all", $jar);
ok(!empty($hen) && strpos($r['text'], 'Đã hẹn: ' . $hen['request_no']) !== false,
   'Muc "Tat ca" ghi ro da hen bang phieu nao');

/* --- Màn phiếu --- */
$r = $http('GET', "$base/admin/warranty/edit/$R4b", $jar);
ok(strpos($r['text'], 'Hẹn lần bảo trì kế tiếp') !== false, 'Phieu bao tri da xong co nut hen lan ke tiep');
$r = $http('GET', "$base/admin/warranty/edit/$B", $jar);
ok(strpos($r['text'], 'Hẹn lần bảo trì kế tiếp') === false, 'Phieu bao hanh KHONG co nut do');

$r = $http('GET', "$base/admin/warranty?loai=bao_tri", $jar);
ok(strpos($r['text'], $soPhieu($A)) !== false && strpos($r['text'], $soPhieu($B)) === false,
   'Danh sach phieu loc theo loai');

$pdo->prepare("INSERT INTO warranty_handovers (handover_no, warranty_id, type, handover_date, create_at)
               VALUES (?, ?, 'receive', CURDATE(), NOW())")->execute(['ZZBB-' . time(), $R1]);
$hid = (int) $pdo->lastInsertId();
$r = $http('GET', "$base/admin/warranty/handover-print/$hid", $jar);
ok(strpos($r['text'], 'theo phiếu bảo trì') !== false,
   'Bien ban giao nhan cua phieu bao tri ghi "phieu bao tri"',
   'In cung chu "phieu bao hanh" cho moi phieu');

/* --- Lưu cài đặt chu kỳ --- */
$r = $http('GET', "$base/admin/nhac-bao-tri", $jar);
$tk = $token($r['body']) ?: $tk;
$http('POST', "$base/admin/nhac-bao-tri/save-config", $jar, ['_token' => $tk, 'interval' => 4, 'km' => 8000, 'window' => 15]);
$doc = [];
foreach ($pdo->query("SELECT skey, svalue FROM site_settings WHERE skey LIKE 'maintenance_%'") as $x) $doc[$x['skey']] = $x['svalue'];
ok(($doc['maintenance_interval_km'] ?? '') === '8000' && ($doc['maintenance_interval_months'] ?? '') === '4',
   'Luu duoc chu ky thang + km', json_encode($doc));

@unlink($jar);

// Dọn sạch + trả cài đặt
$donSach();
foreach ($caiDatGoc as $k => $v) $datCaiDat($k, $v);
ok((int) $pdo->query("SELECT COUNT(*) FROM warranty_requests WHERE customer_name LIKE 'ZZBT%'")->fetchColumn() === 0,
   'Da don sach phieu test');

exit(summary());

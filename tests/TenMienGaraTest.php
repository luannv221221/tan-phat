<?php
/**
 * Test NHẬN GARA THEO TÊN MIỀN — bước 1 của nền tảng nhiều gara.
 *
 * Chạy:  C:\xampp\php\php.exe tests\TenMienGaraTest.php
 *
 * Trước đây gara làm việc suy ra từ TÀI KHOẢN (trang quản trị) và luôn là gara
 * tổng (trang người dùng) — cả hệ thống chỉ có một website. Nay tên miền quyết
 * định đang phục vụ gara nào.
 *
 * BỐN CHỖ HỎNG ÂM THẦM mà test này gác:
 *
 *   1. KHỚP KIỂU "CHỨA" THAY VÌ KHỚP ĐÚNG. Dùng LIKE thì "a.etek.vn" khớp
 *      nhầm cả "gara-a.etek.vn" — gara này đọc được dữ liệu gara kia. Phải so
 *      BẰNG, sau khi hạ chữ thường / bỏ cổng / bỏ www.
 *
 *   2. TÀI KHOẢN GARA A VÀO ĐƯỢC ĐỊA CHỈ GARA B. Mọi lớp lọc phía dưới đều tin
 *      vào gara_hien_tai(); sai ở đây là sai toàn hệ thống.
 *
 *   3. HOST LẠ RƠI VỀ GARA TỔNG. Rơi về nghĩa là bất kỳ tên miền nào trỏ bừa
 *      vào máy chủ cũng xem được dữ liệu Tân Phát.
 *
 *   4. BẬT LÊN LÀ SẬP TRANG ĐANG CHẠY. Hai cửa thoát cố ý: bảng tên miền còn
 *      rỗng (chưa cấu hình) và host nội bộ (máy chạy thử) thì giữ nguyên cách
 *      cũ. Và chính tên miền gốc etek.rikkeiedu.org phải được khai cho gara
 *      tổng, không thì đẩy code lên là web sập.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc  = __DIR__ . '/../';
$base = 'http://localhost:88/tan-phat';

/* Model khong tu nap: cac test khac cung require tay nhu vay. */
require_once $goc . 'app/models/GarageDomainsModel.php';

// ---------------------------------------------------------------------------
section('Chuan hoa host');

ok(GarageDomainsModel::chuanHoaHost('GaraA.Etek.VN') === 'garaa.etek.vn', 'Ha chu thuong');
ok(GarageDomainsModel::chuanHoaHost('garaa.etek.vn:443') === 'garaa.etek.vn', 'Bo cong');
ok(GarageDomainsModel::chuanHoaHost('www.garaa.etek.vn') === 'garaa.etek.vn', 'Bo www.');
ok(GarageDomainsModel::chuanHoaHost('  garaa.etek.vn  ') === 'garaa.etek.vn', 'Bo khoang trang');
ok(GarageDomainsModel::chuanHoaHost('[::1]:88') === '[::1]', 'IPv6 giu nguyen dau ngoac, bo cong');
ok(GarageDomainsModel::chuanHoaHost('') === '', 'Rong -> rong');

// ---------------------------------------------------------------------------
section('Host noi bo (may chay thu) duoc bo qua luat ten mien');

foreach (['localhost', 'localhost:88', '127.0.0.1', '[::1]', 'may-cua-toi',
          'app.localhost', 'tanphat.test'] as $h){
    ok(la_host_noi_bo($h) === true, "`$h` la host noi bo");
}
foreach (['etek.rikkeiedu.org', 'tp01.etek.rikkeiedu.org', 'garaa.vn'] as $h){
    ok(la_host_noi_bo($h) === false, "`$h` KHONG phai host noi bo");
}

// ---------------------------------------------------------------------------
try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$donSach = function() use ($pdo){
    $pdo->exec("DELETE FROM garage_domains WHERE host LIKE 'zztm-%'");
};
$donSach();

// ---------------------------------------------------------------------------
section('Migration da khai ten mien cho moi gara');

$gara = $pdo->query("SELECT id, code, is_master FROM garages WHERE status = 1")->fetchAll(PDO::FETCH_ASSOC);
foreach ($gara as $g){
    $st = $pdo->prepare("SELECT COUNT(*) FROM garage_domains WHERE garage_id = ? AND is_primary = 1");
    $st->execute([(int) $g['id']]);
    ok((int) $st->fetchColumn() === 1, "Gara {$g['code']} co dung MOT ten mien chinh");
}

/* Tên miền gốc PHẢI thuộc gara tổng: trang đang chạy thật nằm ở đó. */
$st = $pdo->prepare("SELECT g.is_master FROM garage_domains d JOIN garages g ON g.id = d.garage_id
                     WHERE d.host = 'etek.rikkeiedu.org'");
$st->execute();
ok((int) $st->fetchColumn() === 1,
   'Ten mien goc etek.rikkeiedu.org tro ve GARA TONG',
   'Khong khai thi day code len la trang dang chay thanh "host la" va bi chan');

// ---------------------------------------------------------------------------
section('Tra gara theo host');

$M = new GarageDomainsModel();

$tp = $M->theoHost('tp01.etek.rikkeiedu.org');
ok(!empty($tp) && (int) $tp['is_master'] === 1, 'tp01... ra gara tong');

$sg = $M->theoHost('dmsg.etek.rikkeiedu.org');
ok(!empty($sg) && !empty($tp) && (int) $sg['id'] !== (int) $tp['id'],
   'dmsg... ra MOT GARA KHAC, khong phai gara tong');

ok($M->theoHost('TP01.Etek.RikkeiEdu.ORG:443') && (int) $M->theoHost('TP01.Etek.RikkeiEdu.ORG:443')['id'] === (int) $tp['id'],
   'Viet hoa + cong van ra dung gara do');

ok($M->theoHost('khong-co-that.example.com') === [], 'Host chua khai -> rong');
ok($M->theoHost('') === [], 'Host rong -> rong');

/* BẪY CHÍNH: khớp kiểu "chứa" thì tên miền dài hơn sẽ nuốt tên miền ngắn. */
$st = $pdo->prepare("INSERT INTO garage_domains (garage_id, host, is_primary, status, create_at)
                     VALUES (?, 'zztm-rieng.etek.rikkeiedu.org', 0, 1, NOW())");
$st->execute([(int) $sg['id']]);
ok($M->theoHost('rieng.etek.rikkeiedu.org') === [],
   'Host la hau to cua mot ten mien da khai -> KHONG khop',
   'Khop kieu LIKE thi gara nay doc duoc du lieu gara kia');
ok(!empty($M->theoHost('zztm-rieng.etek.rikkeiedu.org')),
   'Nhung chinh ten mien do thi van khop');

/* Gara bị khoá thì tên miền của nó cũng thôi phục vụ. */
$pdo->prepare("UPDATE garages SET status = 0 WHERE id = ?")->execute([(int) $sg['id']]);
ok($M->theoHost('dmsg.etek.rikkeiedu.org') === [],
   'Gara bi khoa -> ten mien cua no khong tra ve gara nao');
$pdo->prepare("UPDATE garages SET status = 1 WHERE id = ?")->execute([(int) $sg['id']]);

// ---------------------------------------------------------------------------
section('Middleware duoc dang ky va dung THU TU');

$cfg = file_get_contents($goc . 'configs/app.php');
ok(strpos($cfg, 'TenMienMiddleware::class') !== false, 'TenMienMiddleware nam trong global_middleware');
$viTriTenMien = strpos($cfg, 'TenMienMiddleware::class');
$viTriCsrf    = strpos($cfg, 'CsrfMiddleware::class,');
ok($viTriTenMien !== false && $viTriCsrf !== false && $viTriTenMien < $viTriCsrf,
   'Chay TRUOC cac middleware khac',
   'Chay sau thi middleware khac da kip lam viec voi gara sai');

// ---------------------------------------------------------------------------
section('Chay that qua HTTP');

if (!function_exists('curl_init')){ echo "  [SKIP] PHP khong co curl.\n"; $donSach(); exit(summary()); }

$http = function($url, $host, $jar = null, $data = null){
    $ch = curl_init($url);
    $opt = [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 25, CURLOPT_HTTPHEADER => ['Host: ' . $host]];
    if ($jar !== null){ $opt[CURLOPT_COOKIEJAR] = $jar; $opt[CURLOPT_COOKIEFILE] = $jar; }
    if ($data !== null){ $opt[CURLOPT_POST] = true; $opt[CURLOPT_POSTFIELDS] = http_build_query($data); }
    curl_setopt_array($ch, $opt);
    $raw  = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    $body = $raw === false ? '' : substr($raw, (int) $info['header_size']);
    return ['code' => (int) $info['http_code'], 'body' => $body];
};

$r = $http("$base/", 'localhost:88');
if ($r['code'] === 0){ echo "  [SKIP] Apache khong chay (localhost:88).\n"; $donSach(); exit(summary()); }
ok($r['code'] === 200, 'Host noi bo van vao duoc binh thuong (HTTP ' . $r['code'] . ')',
   'May chay thu va bo test khong co ten mien gara nao tro ve');

$r = $http("$base/", 'tp01.etek.rikkeiedu.org');
ok($r['code'] === 200, 'Ten mien cua gara tong vao duoc (HTTP ' . $r['code'] . ')');

$r = $http("$base/", 'dmsg.etek.rikkeiedu.org');
ok($r['code'] === 200, 'Ten mien cua gara khac cung vao duoc (HTTP ' . $r['code'] . ')');

$r = $http("$base/", 'khong-co-that.example.com');
ok($r['code'] === 404, 'Host LA bi chan voi ma 404 (HTTP ' . $r['code'] . ')',
   '404 chu khong phai 500: dia chi khong co that, khong phai may chu hong');
ok(mb_strpos($r['body'], 'Không tìm thấy gara') !== false,
   'Va noi ro la khong tim thay gara cho dia chi do');
ok(mb_strpos($r['body'], 'khong-co-that.example.com') !== false,
   'Trang bao in ra chinh dia chi dang go',
   'Gap trang nay gan nhu luon la go nham ten mien phu hoac DNS chua tro xong');

// ---------------------------------------------------------------------------
section('Tai khoan gara nay KHONG vao duoc dia chi gara kia');

$MK = 'Gara@2026';
$taiKhoanSG = $pdo->query("SELECT email FROM users WHERE garage_id = " . (int) $sg['id']
                        . " AND status = 1 AND email LIKE '%@gara-mau.test' LIMIT 1")->fetchColumn();
if (empty($taiKhoanSG)){
    echo "  [SKIP] Khong co tai khoan mau cua gara nay de thu.\n";
} else {
    $jar = tempnam(sys_get_temp_dir(), 'zztm');
    $hSG = 'dmsg.etek.rikkeiedu.org';
    $r   = $http("$base/dang-nhap", $hSG, $jar);
    preg_match('~name="_token" value="([^"]+)"~', $r['body'], $m);
    $http("$base/dang-nhap", $hSG, $jar, ['email' => $taiKhoanSG, 'password' => $MK, '_token' => $m[1] ?? '']);

    $r = $http("$base/admin", $hSG, $jar);
    ok($r['code'] === 200,
       'Vao duoc trang quan tri tai DUNG dia chi gara minh (HTTP ' . $r['code'] . ')',
       'Tai khoan thu: ' . $taiKhoanSG);

    $r = $http("$base/admin", 'tp01.etek.rikkeiedu.org', $jar);
    ok($r['code'] !== 200,
       'Cung phien do, mo dia chi GARA KHAC thi bi da ra (HTTP ' . $r['code'] . ')',
       'Khong chan thi nhan vien gara nay lam viec tren du lieu gara kia');

    @unlink($jar);
}

// ---------------------------------------------------------------------------
section('Buoc 2 — moi gara mot bo nhan dien rieng');

/* Truoc day `site_settings` dung CHUNG: moi gara xai chung mot ten, mot logo,
   mot hotline. Nay gara tu dat cua minh, chua dat thi roi ve mac dinh chung. */
require_once $goc . 'app/models/GarageSettingsModel.php';

$mod = $pdo->query("SELECT chi_tan_phat FROM modules WHERE link = 'settings'")->fetchColumn();
ok((int) $mod === 0,
   'Man Cau hinh da mo cho moi gara (bo co chi_tan_phat)',
   'Con co thi gara khac mo ra bi da ve "khong co quyen"');

$ctl = file_get_contents($goc . 'app/controllers/admin/Settings.php');
ok(strpos($ctl, 'la_gara_tong()') !== false && strpos($ctl, 'GarageSettingsModel') !== false,
   'Settings ghi dung cho: gara tong -> chung, gara khac -> rieng',
   'Ghi nham cho la mot gara bam Luu lam doi nhan dien cua TAT CA gara con lai');

/* Tron: rieng de len chung, RONG thi khong de. */
$gTong = (int) $pdo->query("SELECT id FROM garages WHERE is_master = 1")->fetchColumn();
$gPhu  = (int) $pdo->query("SELECT id FROM garages WHERE is_master = 0 AND status = 1 ORDER BY id LIMIT 1")->fetchColumn();

$datChung = function($k, $v) use ($pdo){
    $st = $pdo->prepare("SELECT id FROM site_settings WHERE skey = ?"); $st->execute([$k]);
    if ($id = $st->fetchColumn()) $pdo->prepare("UPDATE site_settings SET svalue = ? WHERE id = ?")->execute([$v, $id]);
    else $pdo->prepare("INSERT INTO site_settings (skey, svalue, update_at) VALUES (?,?,NOW())")->execute([$k, $v]);
};
$datRieng = function($g, $k, $v) use ($pdo){
    $st = $pdo->prepare("SELECT id FROM garage_settings WHERE garage_id = ? AND skey = ?"); $st->execute([$g, $k]);
    if ($id = $st->fetchColumn()) $pdo->prepare("UPDATE garage_settings SET svalue = ? WHERE id = ?")->execute([$v, $id]);
    else $pdo->prepare("INSERT INTO garage_settings (garage_id, skey, svalue, update_at) VALUES (?,?,?,NOW())")->execute([$g, $k, $v]);
};

$datChung('zztm_thu', 'CHUNG');
$datRieng($gPhu, 'zztm_thu', 'RIENG');
$datRieng($gPhu, 'zztm_rong', '');
$datChung('zztm_rong', 'CHUNG');

\App\core\Model::epGara($gPhu);
$GS  = new GarageSettingsModel();
$map = $GS->map();
ok(($map['zztm_thu'] ?? '') === 'RIENG', 'Gia tri RIENG de len gia tri chung');
ok(($map['zztm_rong'] ?? '') === 'CHUNG',
   'Gia tri rieng de RONG thi KHONG de — roi ve mac dinh chung',
   'De xuong la gara moi chua khai gi se co web trang tron, khong ten khong hotline');

\App\core\Model::epGara($gTong);
$mapTong = (new GarageSettingsModel())->map();
ok(($mapTong['zztm_thu'] ?? '') === 'CHUNG', 'Gara tong van thay gia tri chung, khong bi gara khac lam anh huong');
\App\core\Model::epGara(null);

$pdo->exec("DELETE FROM site_settings WHERE skey LIKE 'zztm_%'");
$pdo->exec("DELETE FROM garage_settings WHERE skey LIKE 'zztm_%'");

/* Chay that: hai ten mien ra hai ten trang khac nhau. */
$tenRieng = 'ZZTM Ten Rieng';
$datRieng($gPhu, 'site_name', $tenRieng);
$hostPhu = $pdo->query("SELECT host FROM garage_domains WHERE garage_id = $gPhu AND status = 1 LIMIT 1")->fetchColumn();

$r = $http("$base/", $hostPhu);
ok($r['code'] === 200 && strpos($r['body'], $tenRieng) !== false,
   'Trang web cua gara hien TEN RIENG cua gara do',
   'Host ' . $hostPhu . ' — HTTP ' . $r['code']);

$r = $http("$base/", 'tp01.etek.rikkeiedu.org');
ok($r['code'] === 200 && strpos($r['body'], $tenRieng) === false,
   'Trang cua gara tong KHONG bi doi theo',
   'Mot gara doi ten minh ma trang gara khac doi theo la dung chung mat roi');

$pdo->exec("DELETE FROM garage_settings WHERE garage_id = $gPhu AND skey = 'site_name'");

// ---------------------------------------------------------------------------
section('Man quan ly ten mien (08/10/2026)');

/* TRUOC DAY KHONG CO MAN NAO: khai ten mien phai go SQL tay vao CSDL that. Mo
   mot gara moi la ba cau INSERT / UPDATE / DELETE go dung thu tu, va mot lan da
   suyt hong vi dao thu tu (DELETE truoc UPDATE nen UPDATE khong khop dong nao,
   gara mo ra khong co ten mien).

   Moi luat nam o GarageDomainsModel — controller chi goi va bao lai. Nen kiem o
   tang model, cong mot vai khang dinh nguon cho phan noi day. */

$D = new GarageDomainsModel();

// --- Kiem host nguoi dung go vao ---
$hopLe = function($x) use ($D){ list($h, $l) = GarageDomainsModel::kiemHost($x); return $l === '' ? $h : false; };

ok($hopLe('Gara-A.Etek.Rikkeiedu.org') === 'gara-a.etek.rikkeiedu.org', 'Ha chu thuong khi khai');
ok($hopLe('https://gara-b.etek.vn:443/admin/x?y=1') === 'gara-b.etek.vn',
   'Dan ca dia chi tu thanh trinh duyet -> tu cat giao thuc, cong, duong dan',
   var_export($hopLe('https://gara-b.etek.vn:443/admin/x?y=1'), true));
ok($hopLe('localhost') === 'localhost', 'Host khong co dau cham VAN hop le (localhost, ten may trong LAN)');

/* Khong doi phai co dau cham, nhung moi NHAN phai dung ky tu hop le. Khong kiem
   o day thi MySQL nhan het — chi mot thu duy nhat no chan la trung. Host co dau
   cach hay dau tieng Viet luu vao trong nhu binh thuong, toi luc mo ten mien
   moi ra "Khong tim thay gara", ma nhin vao dau cung khong ra ly do. Da mac dung
   kieu do voi ma gara "Long Bien". */
foreach ([''            => 'rong',
          '   '         => 'toan khoang trang',
          'gara a.vn'   => 'co dau cach',
          'gará.etek.vn'=> 'co dau tieng Viet',
          '-gara.vn'    => 'nhan mo dau bang gach noi',
          'gara-.vn'    => 'nhan ket thuc bang gach noi',
          'gara..vn'    => 'hai dau cham lien nhau',
          '.gara.vn'    => 'dau cham o dau',
          'a@b.vn'      => 'co ky tu @'] as $xau => $vi){
    ok($hopLe($xau) === false, "Tu choi host $vi", 'Nhan vao: ' . var_export($hopLe($xau), true));
}
ok($hopLe(str_repeat('a', 64) . '.vn') === false, 'Tu choi nhan dai qua 63 ky tu');
ok($hopLe(str_repeat('a.', 100) . 'vn') === false, 'Tu choi host dai qua 190 ky tu (gioi han cot)');

// --- Thêm / trùng / chính / tắt / xoá, trên một gara tạm ---
$pdo->exec("DELETE FROM garage_domains WHERE host LIKE 'zztm-%'");
$pdo->exec("DELETE FROM garages WHERE code = 'ZZTMG'");
$pdo->exec("INSERT INTO garages (code, name, is_master, sort_order, status, create_at)
            VALUES ('ZZTMG', 'ZZ Gara ten mien', 0, 99, 1, NOW())");
$gTmp = (int) $pdo->lastInsertId();

register_shutdown_function(function() use ($pdo, $gTmp){
    $pdo->exec("DELETE FROM garage_domains WHERE garage_id = $gTmp");
    $pdo->exec("DELETE FROM garages WHERE id = $gTmp");
});

list($h1, $loi) = $D->them($gTmp, 'ZZTM-Mot.etek.vn');
ok($loi === '' && $h1 === 'zztm-mot.etek.vn', 'Khai duoc ten mien dau tien', $loi);

/* Ten mien DAU TIEN tu thanh ten mien CHINH, du khong tick: khong co host chinh
   thi theoGara() tra ve host dau theo thu tu chu cai, va tenMienGoc() suy ra
   ten mien goc khac nhau giua hai lan goi. */
$d1 = $D->aiGiuHost($h1);
ok((int) $d1['is_primary'] === 1, 'Ten mien DAU TIEN tu thanh ten mien chinh (du khong tick)');

list($h2, $loi) = $D->them($gTmp, 'zztm-hai.etek.vn');
ok($loi === '', 'Khai duoc ten mien thu hai', $loi);
ok((int) $D->aiGiuHost($h2)['is_primary'] === 0, 'Ten mien thu hai KHONG tu thanh chinh');

list(, $loi) = $D->them($gTmp, 'ZZTM-MOT.ETEK.VN');
ok($loi !== '' && strpos($loi, 'đã khai') !== false,
   'Khai lai cung host (khac kieu viet) -> bao da co', $loi);

$gKhac = (int) $pdo->query("SELECT id FROM garages WHERE is_master = 1 ORDER BY id LIMIT 1")->fetchColumn();
list(, $loi) = $D->them($gKhac, 'zztm-mot.etek.vn');
ok($loi !== '' && strpos($loi, 'ZZ Gara ten mien') !== false,
   'Host da thuoc gara khac -> bao RO la cua gara nao', $loi);

// --- Đặt làm chính: gỡ cờ ở host cũ, trong một giao dịch ---
$loi = $D->datLamChinh((int) $D->aiGiuHost($h2)['id']);
ok($loi === '', 'Dat duoc ten mien thu hai lam chinh', $loi);
$chinh = $pdo->query("SELECT host FROM garage_domains WHERE garage_id = $gTmp AND is_primary = 1")
             ->fetchAll(PDO::FETCH_COLUMN);
ok($chinh === [$h2], 'Chi CON MOT ten mien chinh sau khi doi',
   'Dang co: ' . implode(', ', $chinh)
   . ' — hai host cung mang co chinh thi tenMienGoc() suy ra khac nhau moi lan goi');

// --- Tắt: không tắt cái bật cuối cùng ---
list($moi, $loi) = $D->doiTrangThai((int) $D->aiGiuHost($h1)['id']);
ok($loi === '' && $moi === 0, 'Tat duoc mot ten mien khi gara con host khac', $loi);

list($moi, $loi) = $D->doiTrangThai((int) $D->aiGiuHost($h2)['id']);
ok($moi === null && strpos($loi, 'DUY NHẤT') !== false,
   'KHONG tat duoc ten mien bat CUOI CUNG cua mot gara',
   'Tat het la website gara do khong ai vao duoc, ma man hinh chi thay mot dong doi mau: ' . $loi);

$loi = $D->datLamChinh((int) $D->aiGiuHost($h1)['id']);
ok($loi !== '', 'KHONG dat ten mien DANG TAT lam chinh', $loi);

// --- Xoá: không xoá cái duy nhất; xoá host chính thì chuyển cờ ---
$loi = $D->xoa((int) $D->aiGiuHost($h1)['id']);
ok($loi === '', 'Xoa duoc ten mien khi gara con host khac', $loi);

$loi = $D->xoa((int) $D->aiGiuHost($h2)['id']);
ok($loi !== '' && strpos($loi, 'DUY NHẤT') !== false,
   'KHONG xoa duoc ten mien DUY NHAT cua mot gara', $loi);

$D->them($gTmp, 'zztm-ba.etek.vn');
$loi = $D->xoa((int) $D->aiGiuHost($h2)['id']);   // $h2 dang la host chinh
ok($loi === '', 'Xoa duoc host CHINH khi con host khac', $loi);
$chinh = $pdo->query("SELECT host FROM garage_domains WHERE garage_id = $gTmp AND is_primary = 1")
             ->fetchAll(PDO::FETCH_COLUMN);
ok($chinh === ['zztm-ba.etek.vn'],
   'Xoa host CHINH thi co chuyen sang host con lai',
   'Dang co: ' . implode(', ', $chinh)
   . ' — gara khong co host chinh thi gara mo sau nhan ten mien duoi mot host chay thu');

// --- Nối dây: route, controller, view ---
$rt = codeOnly($goc . 'routes/web.php');
foreach (['garages/ten-mien/(\d+)', 'garages/ten-mien-chinh/(\d+)',
          'garages/ten-mien-tat/(\d+)', 'garages/ten-mien-xoa/(\d+)'] as $r){
    ok(strpos($rt, $r) !== false, "Co route `$r`");
}
/* Route la `garages/...` nen RoleMiddleware chi khop toi quyen `view` cua module
   `garages`. Khai ten mien la SUA gara, nen controller phai tu kiem quyen
   `edit` — khong thi ai xem duoc danh sach gara la doi duoc ten mien. */
$gc = codeOnly($goc . 'app/controllers/admin/Garages.php');
ok(strpos($gc, 'chanNeuKhongSuaDuoc') !== false
   && preg_match("~route\('admin/' \. \\\$this->routeBase \. '/edit/'~", $gc) === 1,
   'Bon duong ghi ten mien tu kiem quyen `edit` cua gara',
   'Route la garages/... nen RoleMiddleware chi gac duoc quyen `view`');
/* Cat than tung ham ra roi tim trong than do, khong dung mot regex "N ky tu
   dau tien": ba ham sau tra dong ten mien va xu ly "khong tim thay" TRUOC khi
   kiem quyen, nen cua so co dinh bao do oan. */
$thanHam = function($ten) use ($gc){
    $i = strpos($gc, 'function ' . $ten . '(');
    if ($i === false) return '';
    $j = strpos($gc, "\n    public function ", $i + 1);
    $k = strpos($gc, "\n    private function ", $i + 1);
    if ($j === false || ($k !== false && $k < $j)) $j = $k;
    return $j === false ? substr($gc, $i) : substr($gc, $i, $j - $i);
};
foreach (['postTenMien', 'tenMienChinh', 'tenMienToggle', 'tenMienXoa'] as $ham){
    $than = $thanHam($ham);
    ok($than !== '' && strpos($than, 'chanNeuKhongSuaDuoc') !== false,
       "Garages::$ham() di qua chanNeuKhongSuaDuoc()",
       $than === '' ? 'Khong tim thay ham' : 'Than ham khong goi chot quyen');
}

$v = file_get_contents($goc . 'app/views/admin/garages/ten-mien.php');
ok(strpos($v, 'tenMienGoc') !== false,
   'Man hien TEN MIEN GOC cua he thong',
   'Doi ten mien chinh cua gara tong la doi dia chi moi gara mo sau — khong noi thi khong ai doan ra');
ok(strpos($v, 'ServerAlias') !== false && strpos($v, 'DNS') !== false,
   'Man nhac hai viec NGOAI he thong: tro DNS va ServerAlias cua Apache',
   'Khai o day chua du, ma bang du lieu thi noi rang da xong');
ok(strpos($v, 'la_host_noi_bo') !== false,
   'Man danh dau host cua may noi bo (chi dung de chay thu)');

$lv = file_get_contents($goc . 'app/views/admin/garages/lists.php');
ok(strpos($lv, 'ten-mien/') !== false, 'Danh sach gara co nut sang man Ten mien');
ok(strpos($lv, 'chưa khai') !== false,
   'Danh sach gara bao DO khi mot gara chua co ten mien nao',
   'Cot trong la cho duy nhat noi ra rang website gara do chua ai vao duoc');

$pdo->exec("DELETE FROM garage_domains WHERE garage_id = $gTmp");
$pdo->exec("DELETE FROM garages WHERE id = $gTmp");

// ---------------------------------------------------------------------------
$donSach();
ok((int) $pdo->query("SELECT COUNT(*) FROM garage_domains WHERE host LIKE 'zztm-%'")->fetchColumn() === 0
   && (int) $pdo->query("SELECT COUNT(*) FROM site_settings WHERE skey LIKE 'zztm_%'")->fetchColumn() === 0
   && (int) $pdo->query("SELECT COUNT(*) FROM garages WHERE code = 'ZZTMG'")->fetchColumn() === 0,
   'Da don sach du lieu test');

exit(summary());

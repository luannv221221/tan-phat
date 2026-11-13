<?php
/**
 * LICH TU CAP NHAT GIA (NGAY / TUAN / THANG) DAT NGAY TREN MAN BANG TINH GIA.
 *
 * Khach nhan 13/11/2026 (kem anh man Bang tinh gia tren dien thoai): "cho nay
 * cho them cai lich dat tu dong theo ngay, tuan, thang, nhu cai bao cao ke
 * toan y". Truoc do lich chi la cai nhan chi doc kem link sang Cau hinh.
 *
 * BA DIEU KHONG DUOC SAI:
 *
 *   1. LICH PHAI THAT SU CHAY. Dat "Hang ngay" ma bang gia khong tu tinh lai thi
 *      o chon chi la trang tri. Test do bang cach LUI MOC chay lan cuoi roi mo man.
 *
 *   2. DAT CHO MOT GARA KHONG DUOC DOI GARA KHAC. Bang gia la du lieu cua TUNG
 *      gara. Ghi vao site_settings (cau hinh CHUNG) thi gara tong chon "Hang ngay"
 *      cho bang gia cua minh la moi gara chua tu dat cung tu tinh lai hang ngay.
 *
 *   3. CHI DUNG MOT CHO. Lich con o Cau hinh chung thi gara tong bam Luu o do
 *      (de doi so tai khoan ngan hang) se chep gia tri rieng cua minh thanh mac
 *      dinh cho moi gara — vi form dien san gia tri da tron.
 */

require_once __DIR__ . '/_helpers.php';

$goc = __DIR__ . '/../';

// ===========================================================================
section('Nguon: mot cho dat lich, mot nguon so cho han');

$model = codeOnly($goc . 'app/models/PartPricesModel.php');
$ctl   = codeOnly($goc . 'app/controllers/admin/Partprices.php');
$view  = file_get_contents($goc . 'app/views/admin/part-prices/lists.php');
$web   = file_get_contents($goc . 'routes/web.php');

ok(strpos($web, "Route::post('part-prices/edit/lich'") !== false,
   'Lenh dat lich nam DUOI `edit/` (RoleMiddleware tu doi quyen edit) va la POST',
   'De ten khac thi chi doi quyen `view` — ai xem duoc cung dat lich duoc');
ok(preg_match('~function postLich\(\)\{\s*if \(!\$this->coQuyenTinh\(\)\)~', $ctl) === 1,
   'postLich() chot quyen NGAY DONG DAU (lop thu hai, nhu postTinhlai)');
ok(strpos($ctl, "'gara_id'") === false && strpos($ctl, "garage_id") === false,
   'postLich() KHONG doc ma gara tu form — model ghi cho gara dang lam viec');
ok(preg_match('~function datChuKyLich.*?GarageSettingsModel.*?saveMany~s', $model) === 1,
   'Lich ghi vao cau hinh RIENG cua gara (GarageSettingsModel)');
$thanDat = substr($model, (int) strpos($model, 'function datChuKyLich'), 500);
ok(strpos($thanDat, "Load::model('SettingsModel')") === false,
   'Lich KHONG bao gio ghi vao site_settings (cau hinh chung)',
   'Ghi vao do thi gara tong chon "Hang ngay" cho bang gia cua minh la moi gara chua tu dat cung chay hang ngay');

ok(strpos($model, 'const HAN') !== false, 'Co hang so HAN — mot nguon so cho cho ca chay lan hien thi');
ok(strpos($model, '$han = [') === false, 'chayNeuDenHan() khong con bang so rieng (dung chung self::HAN)');

$set = codeOnly($goc . 'app/controllers/admin/Settings.php');
ok(strpos($set, "'price_schedule'") === false, 'Settings KHONG con whitelist price_schedule');
ok(strpos(file_get_contents($goc . 'app/views/admin/settings/form.php'), 'price_schedule') === false,
   'Cau hinh chung KHONG con o lich (chi dung mot cho)');
ok(strpos($view, 'name="price_schedule"') !== false, 'Man Bang tinh gia co o chon lich');
ok(preg_match('~name="price_schedule".*?foreach \(\$tenChuKy~s', $view) === 1 || strpos($view, '$tenChuKy as $ma') !== false,
   'O chon liet ke du bon lua chon tu PartPricesModel::LICH');

// Khach 13/11/2026: "day ra ngoai, gan nut cap nhat, dat lich" — o dat lich phai o
// DAU THE, cung cum nut voi "Cap nhat gia", khong giau xuong than the.
$viTriLich  = (int) strpos($view, 'name="price_schedule"');
$viTriTinh  = (int) strpos($view, "/edit/tinh-lai");
$viTriThan  = (int) strpos($view, 'class="card-body border-bottom"');
ok($viTriLich > 0 && $viTriTinh > 0 && $viTriThan > 0, 'Tim thay o lich, nut Cap nhat gia va than the trong view');
ok($viTriLich < $viTriThan, 'O dat lich nam o DAU THE (truoc than the), khong con o than the');
ok($viTriLich < $viTriTinh && ($viTriTinh - $viTriLich) < 1500,
   'O dat lich dung NGAY TRUOC nut Cap nhat gia (cung cum card-tools)', 'cach nhau ' . ($viTriTinh - $viTriLich) . ' ky tu');
ok(substr_count($view, 'name="price_schedule"') === 1, 'Chi mot o chon lich tren man (khong nhan doi o than the)');
ok(strpos($view, 'Đặt lịch') !== false && strpos($view, 'Lưu lịch') === false,
   'Nut ten "Dat lich" (khach goi ten nhu vay), khong con "Luu lich"');

// ===========================================================================
section('Chay that');

$base = web_goc();
$http = function($method, $url, $jar, $post = null){
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_HEADER => true, CURLOPT_TIMEOUT => 60]);
    if ($method === 'POST'){ curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query((array) $post)); }
    $r = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hs   = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    preg_match('~^Location:\s*(.+)$~im', substr((string) $r, 0, $hs), $m);
    return ['code' => $code, 'loc' => isset($m[1]) ? trim($m[1]) : '', 'body' => substr((string) $r, $hs)];
};
$token = function($b){ return preg_match('~name="_token"[^>]*value="([^"]+)"~', $b, $m) ? $m[1] : ''; };

/* GIAI MA THUC THE TRUOC KHI DO CHU. Bo dung template cua du an ma hoa moi gia tri
   in qua {{ }} bang htmlentities: chu co dau thuoc bang Latin-1 thanh thuc the
   (o -> &ocirc;, a -> &agrave;, a -> &aacute;, dau gach dai -> &mdash;) con mot so
   chu khac (ằ, ự, ạ) de nguyen. Trinh duyet hien thi dung, nhung do chuoi tho thi
   "Khong tu chay" / "Hang ngay" / "Hang thang" truot con "Hang tuan" thi khong —
   dung cai kieu mot nua dung mot nua sai de nham la loi cua code. */
$chu = function($body){ return html_entity_decode((string) $body, ENT_QUOTES | ENT_HTML5, 'UTF-8'); };
$dangNhap = function($email) use ($base, $http, $token){
    $jar = tempnam(sys_get_temp_dir(), 'lb');
    $r = $http('GET', "$base/dang-nhap", $jar);
    if ($r['code'] === 0) return null;
    $http('POST', "$base/dang-nhap", $jar, ['_token' => $token($r['body']), 'email' => $email, 'password' => 'Gara@2026']);
    return $jar;
};

$jarM = $dangNhap('chugara.sg@gara-mau.test');       // Manager gara 2 — co quyen edit
if ($jarM === null){ echo "\n[SKIP] Apache khong chay (localhost:88).\n"; exit(summary()); }
$jarS = $dangNhap('covan.sg@gara-mau.test');         // Staff gara 2 — chi co quyen xem
$jarA = $dangNhap('admin@gara-mau.test');            // Admin Tan Phat — gara TONG

require_once $goc . 'vendor/autoload.php';
require_once $goc . 'config.php';
try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){ echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary()); }

/* CHUP — va tra lai — CA BANG site_settings va cac dong lich cua moi gara.
   Chup theo danh sach khoa cua chinh code dang thu la bai hoc cua CauHinhWebsiteTest:
   khi code bi pha, danh sach do khong con dung. */
$chupSite = [];
foreach ($pdo->query('SELECT skey, svalue FROM site_settings')->fetchAll(PDO::FETCH_ASSOC) as $x) $chupSite[$x['skey']] = $x['svalue'];
$chupGara = $pdo->query("SELECT id, garage_id, skey, svalue FROM garage_settings
                          WHERE skey IN ('price_schedule', 'price_last_run')")->fetchAll(PDO::FETCH_ASSOC);

$docGara = function($gid, $khoa) use ($pdo){
    $st = $pdo->prepare('SELECT svalue FROM garage_settings WHERE garage_id = ? AND skey = ?');
    $st->execute([$gid, $khoa]);
    $v = $st->fetchColumn();
    return $v === false ? null : $v;
};
$datGara = function($gid, $khoa, $v) use ($pdo, $docGara){
    if ($docGara($gid, $khoa) === null){
        $pdo->prepare('INSERT INTO garage_settings (garage_id, skey, svalue, update_at) VALUES (?, ?, ?, NOW())')->execute([$gid, $khoa, $v]);
    } else {
        $pdo->prepare('UPDATE garage_settings SET svalue = ? WHERE garage_id = ? AND skey = ?')->execute([$v, $gid, $khoa]);
    }
};
$docSite = function($khoa) use ($pdo){
    $st = $pdo->prepare('SELECT svalue FROM site_settings WHERE skey = ?'); $st->execute([$khoa]);
    $v = $st->fetchColumn();
    return $v === false ? null : $v;
};
$layGaraId = function($email) use ($pdo){
    $st = $pdo->prepare('SELECT garage_id FROM users WHERE email = ?'); $st->execute([$email]);
    return (int) $st->fetchColumn();
};
$G2 = $layGaraId('chugara.sg@gara-mau.test');       // gara cua Manager
$G1 = $layGaraId('admin@gara-mau.test');            // gara tong
$G3 = 3;                                             // gara thu ba — kiem "khong lan sang"

try {
    // ------------------------------------------------------- hien thi o chon
    $datGara($G2, 'price_schedule', 'tat');
    $datGara($G2, 'price_last_run', date('Y-m-d H:i:s'));
    $r = $http('GET', "$base/admin/part-prices", $jarM);
    ok($r['code'] === 200, 'Manager mo duoc man Bang tinh gia', 'HTTP ' . $r['code']);
    ok(strpos($r['body'], 'name="price_schedule"') !== false, 'Manager (co quyen edit) THAY o chon lich');
    $htmlLich = (int) strpos($r['body'], 'name="price_schedule"');
    $htmlThan = (int) strpos($r['body'], 'card-body border-bottom');
    $htmlTinh = (int) strpos($r['body'], '/edit/tinh-lai');
    ok($htmlLich > 0 && $htmlLich < $htmlThan, 'Trang that: o chon lich nam o dau the, truoc than the',
       "lich=$htmlLich than=$htmlThan");
    ok($htmlLich < $htmlTinh && $htmlTinh < $htmlThan, 'Trang that: o chon lich ngay truoc nut Cap nhat gia, cung dau the',
       "lich=$htmlLich tinh=$htmlTinh than=$htmlThan");
    ok(strpos($chu($r['body']), 'Đặt lịch') !== false, 'Trang that: co nut "Dat lich"');
    foreach (['Không tự chạy', 'Hằng ngày', 'Hằng tuần', 'Hằng tháng'] as $nhan){
        ok(strpos($chu($r['body']), '>' . $nhan . '<') !== false, "O chon co lua chon \"$nhan\"");
    }
    ok(preg_match('~<option value="tat"\s+selected~', $r['body']) === 1, 'Lua chon hien tai duoc chon san');
    ok(strpos($chu($r['body']), 'Đang tắt') !== false, 'Lich tat thi noi ro: chi doi khi bam Cap nhat gia');
    $tok = $token($r['body']);

    // ---------------------------------------------------------- dat + luu dung
    $r = $http('POST', "$base/admin/part-prices/edit/lich", $jarM, ['_token' => $tok, 'price_schedule' => 'ngay']);
    ok($r['code'] === 302 && strpos($r['loc'], 'khong-co-quyen') === false, 'Dat lich "Hang ngay" duoc', 'HTTP ' . $r['code'] . ' ' . $r['loc']);
    ok($docGara($G2, 'price_schedule') === 'ngay', 'Luu vao cau hinh RIENG cua gara', var_export($docGara($G2, 'price_schedule'), true));

    // --------------------------------------- (2) khong lan sang gara khac / chung
    ok($docSite('price_schedule') === ($chupSite['price_schedule'] ?? null),
       'site_settings (cau hinh CHUNG) KHONG doi', var_export($docSite('price_schedule'), true));
    $coDongG3Moi = $pdo->prepare("SELECT COUNT(*) FROM garage_settings WHERE garage_id = ? AND skey = 'price_schedule' AND svalue = 'ngay'");
    $coDongG3Moi->execute([$G3]);
    ok((int) $coDongG3Moi->fetchColumn() === 0, 'Gara khac KHONG bi dat theo "Hang ngay"');

    $thay = $http('GET', "$base/admin/part-prices", $jarM);
    ok(preg_match('~<option value="ngay"\s+selected~', $thay['body']) === 1, 'Mo lai: o chon hien "Hang ngay"');

    // ------------------------------------------------ (1) lich THAT SU chay
    $cu = date('Y-m-d H:i:s', time() - 2 * 86400);
    $datGara($G2, 'price_last_run', $cu);
    $r = $http('GET', "$base/admin/part-prices", $jarM);
    $sau = $docGara($G2, 'price_last_run');
    ok($sau !== null && strtotime($sau) > time() - 120,
       'Lich "Hang ngay", lan cuoi 2 ngay truoc: mo man la bang gia TU TINH LAI', "$cu -> $sau");
    ok(strpos($chu($r['body']), 'Đã tới kỳ theo lịch') !== false, 'Nguoi dung duoc BAO la bang gia vua tu cap nhat');

    // chua toi han -> khong chay
    $vua = date('Y-m-d H:i:s', time() - 3600);
    $datGara($G2, 'price_last_run', $vua);
    $http('GET', "$base/admin/part-prices", $jarM);
    ok($docGara($G2, 'price_last_run') === $vua, 'Chua toi han (1 gio truoc, lich ngay): KHONG tinh lai');

    // lich tat -> khong chay du da qua han
    $datGara($G2, 'price_schedule', 'tat');
    $datGara($G2, 'price_last_run', $cu);
    $http('GET', "$base/admin/part-prices", $jarM);
    ok($docGara($G2, 'price_last_run') === $cu, 'Lich TAT: du da 2 ngay van KHONG tu tinh lai');

    // ------------------------------------------- tuan / thang + dong "ky ke tiep"
    foreach (['tuan' => 604800, 'thang' => 2592000] as $ck => $han){
        $datGara($G2, 'price_schedule', $ck);
        $datGara($G2, 'price_last_run', date('Y-m-d H:i:s', time() - $han + 3600));   // con 1 gio nua moi toi han
        $r = $http('GET', "$base/admin/part-prices", $jarM);
        ok(strpos($chu($r['body']), 'Lần tự tính tiếp theo') !== false,
           "Lich \"$ck\": man noi ro THOI DIEM tu tinh tiep theo");
        $dung = date('d/m/Y', strtotime($docGara($G2, 'price_last_run')) + $han);
        ok(strpos($r['body'], $dung) !== false, "Lich \"$ck\": ngay ke tiep = lan cuoi + dung chu ky ($dung)");

        $datGara($G2, 'price_last_run', date('Y-m-d H:i:s', time() - $han - 3600));   // qua han 1 gio
        $http('GET', "$base/admin/part-prices", $jarM);
        ok(strtotime($docGara($G2, 'price_last_run')) > time() - 120, "Lich \"$ck\": qua han thi tu tinh lai");
    }

    // ------------------------------------------------ gia tri la bi TU CHOI
    $datGara($G2, 'price_schedule', 'tuan');
    $r = $http('GET', "$base/admin/part-prices", $jarM);
    $r = $http('POST', "$base/admin/part-prices/edit/lich", $jarM, ['_token' => $token($r['body']), 'price_schedule' => 'moi-gio']);
    ok($docGara($G2, 'price_schedule') === 'tuan',
       'Gia tri la bi TU CHOI — khong lang le doi thanh "tat" (lich da dat truoc do con nguyen)',
       var_export($docGara($G2, 'price_schedule'), true));
    ok(strpos($chu($http('GET', "$base/admin/part-prices", $jarM)['body']), 'Lịch không hợp lệ') !== false,
       'Nguoi dung duoc noi vi sao khong luu');

    // ----------------------------------------------------- Staff: chi xem
    if ($jarS !== null){
        $rs = $http('GET', "$base/admin/part-prices", $jarS);
        if ($rs['code'] === 200){
            ok(strpos($rs['body'], 'name="price_schedule"') === false, 'Staff (chi xem) KHONG co o chon lich');
            ok(strpos($chu($rs['body']), 'Lịch tự cập nhật giá') !== false, 'Staff van THAY lich dang dung (dang chu)');
            $truoc = $docGara($G2, 'price_schedule');
            $tS = $token($http('GET', "$base/admin/loi-tat", $jarS)['body']);
            $rp = $http('POST', "$base/admin/part-prices/edit/lich", $jarS, ['_token' => $tS, 'price_schedule' => 'ngay']);
            ok($rp['code'] === 302 && strpos($rp['loc'], 'khong-co-quyen') !== false,
               'Staff gui tay POST dat lich: bi CHAN', 'HTTP ' . $rp['code'] . ' ' . $rp['loc']);
            ok($docGara($G2, 'price_schedule') === $truoc, '...va lich KHONG doi');
        } else {
            ok(true, '(Staff khong mo duoc man Bang tinh gia — bo qua) HTTP ' . $rs['code']);
        }
    }

    // ------------------------------------------------- chua dang nhap -> khong
    $jarN = tempnam(sys_get_temp_dir(), 'lb');
    $http('GET', "$base/dang-nhap", $jarN);
    $truoc = $docGara($G2, 'price_schedule');
    $http('POST', "$base/admin/part-prices/edit/lich", $jarN, ['price_schedule' => 'ngay']);
    ok($docGara($G2, 'price_schedule') === $truoc, 'Chua dang nhap: POST dat lich khong doi duoc gi');
    @unlink($jarN);

    // ============================ gara TONG: ghi RIENG, khong chep thanh mac dinh
    $siteTruoc = $docSite('price_schedule');
    $rA = $http('GET', "$base/admin/part-prices", $jarA);
    if ($rA['code'] === 200 && strpos($rA['body'], 'name="price_schedule"') !== false){
        $http('POST', "$base/admin/part-prices/edit/lich", $jarA, ['_token' => $token($rA['body']), 'price_schedule' => 'thang']);
        ok($docGara($G1, 'price_schedule') === 'thang', 'Gara TONG dat lich: ghi vao cau hinh RIENG cua gara tong', var_export($docGara($G1, 'price_schedule'), true));
        ok($docSite('price_schedule') === $siteTruoc,
           'Gara tong dat lich cho bang gia cua MINH: site_settings (mac dinh cho MOI gara) KHONG doi',
           var_export($docSite('price_schedule'), true));
        ok($docGara($G2, 'price_schedule') === 'tuan', 'Va gara 2 van giu lich cua rieng no');

        // (3) luu o Cau hinh chung khong chep lich cua gara tong thanh mac dinh
        $rc = $http('GET', "$base/admin/settings", $jarA);
        $tC = $token($rc['body']);
        $post = ['_token' => $tC];
        $hienCo = [];
        foreach ($pdo->query('SELECT skey, svalue FROM site_settings')->fetchAll(PDO::FETCH_ASSOC) as $x) $hienCo[$x['skey']] = $x['svalue'];
        foreach (['site_name','site_short_name','site_slogan','meta_description','meta_keywords','logo','hotline','email','address',
                  'tax_code','facebook','zalo','messenger','bank_name','bank_account','bank_holder','show_car_filter','show_topbar',
                  'linh_vuc','dien_tich_xuong','cost_method','footer_html'] as $k){
            $post[$k] = isset($hienCo[$k]) ? $hienCo[$k] : '';
        }
        $http('POST', "$base/admin/settings/save", $jarA, $post);
        ok($docSite('price_schedule') === $siteTruoc,
           'Gara tong bam Luu o Cau hinh chung: lich RIENG cua minh KHONG bi chep thanh mac dinh cho moi gara',
           var_export($docSite('price_schedule'), true));
        ok($docGara($G1, 'price_schedule') === 'thang', 'Va lich cua gara tong khong bi bam Luu o Cau hinh chung lam mat');
    } else {
        ok(true, '(Admin khong mo duoc man hoac khong co o lich — bo qua phep thu gara tong) HTTP ' . $rA['code']);
    }
} finally {
    // ---------------------------------------------------- tra CA BANG ve nhu cu
    $pdo->beginTransaction();
    $pdo->exec("DELETE FROM garage_settings WHERE skey IN ('price_schedule', 'price_last_run')");
    $ins = $pdo->prepare('INSERT INTO garage_settings (garage_id, skey, svalue, update_at) VALUES (?, ?, ?, NOW())');
    foreach ($chupGara as $x) $ins->execute([$x['garage_id'], $x['skey'], $x['svalue']]);

    $hienCoSite = [];
    foreach ($pdo->query('SELECT skey, svalue FROM site_settings')->fetchAll(PDO::FETCH_ASSOC) as $x) $hienCoSite[$x['skey']] = $x['svalue'];
    foreach ($chupSite as $k => $v){
        if (!array_key_exists($k, $hienCoSite)) $pdo->prepare('INSERT INTO site_settings (skey, svalue) VALUES (?, ?)')->execute([$k, $v]);
        elseif ($hienCoSite[$k] !== $v) $pdo->prepare('UPDATE site_settings SET svalue = ? WHERE skey = ?')->execute([$v, $k]);
    }
    foreach ($hienCoSite as $k => $v){
        if (!array_key_exists($k, $chupSite)) $pdo->prepare('DELETE FROM site_settings WHERE skey = ?')->execute([$k]);
    }
    $pdo->commit();
}

$lech = [];
foreach ($pdo->query('SELECT skey, svalue FROM site_settings')->fetchAll(PDO::FETCH_ASSOC) as $x){
    if (!array_key_exists($x['skey'], $chupSite) || $chupSite[$x['skey']] !== $x['svalue']) $lech[] = $x['skey'];
}
ok(empty($lech), 'Da tra CA BANG site_settings ve nhu cu', empty($lech) ? count($chupSite) . ' dong' : implode(', ', $lech));

$sauGara = $pdo->query("SELECT garage_id, skey, svalue FROM garage_settings WHERE skey IN ('price_schedule','price_last_run') ORDER BY garage_id, skey")->fetchAll(PDO::FETCH_ASSOC);
$truocGara = $chupGara;
usort($truocGara, function($a, $b){ return [$a['garage_id'], $a['skey']] <=> [$b['garage_id'], $b['skey']]; });
$a1 = json_encode(array_map(function($x){ return [$x['garage_id'], $x['skey'], $x['svalue']]; }, $truocGara));
$a2 = json_encode(array_map(function($x){ return [$x['garage_id'], $x['skey'], $x['svalue']]; }, $sauGara));
ok($a1 === $a2, 'Da tra cac dong lich cua moi gara ve nhu cu');

foreach ([$jarM, $jarS, $jarA] as $j){ if ($j) @unlink($j); }
exit(summary());

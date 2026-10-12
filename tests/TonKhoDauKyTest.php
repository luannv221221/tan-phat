<?php
/**
 * TỒN KHO ĐẦU KỲ — màn nạp số lượng gara đang có lúc bắt đầu dùng phần mềm.
 *
 * Chạy:  C:\xampp\php\php.exe tests\TonKhoDauKyTest.php
 *
 * Theo mẫu Excel khách gửi 12/10/2026 (tám cột, mỗi dòng một mã hàng tại một
 * kho). Màn KHÔNG ghi thẳng vào bảng tồn — nó dựng PHIẾU NHẬP NHÁP loại
 * `ton_dau_ky`, người dùng soát rồi Ghi sổ ở màn Phiếu nhập kho.
 *
 * SÁU CHỖ HỎNG SẼ ÂM THẦM, mỗi chỗ một mục bên dưới:
 *
 *   1. CỘNG TRÙNG. Tờ hướng dẫn dặn "chỉ import một lần" — dặn thì vẫn có
 *      người bấm hai lần. Nếu import cộng thẳng vào `stocks` thì lần thứ hai
 *      nhân đôi số tồn và KHÔNG CÓ DẤU VẾT NÀO. Chốt thật: mặt hàng đã có phát
 *      sinh ở kho đó thì bỏ qua dòng ấy.
 *
 *   2. ĐỌC SAI SỐ TIỀN. Ô "350.000" trong file Việt là ba trăm năm mươi nghìn.
 *      Đọc bằng (float) của PHP ra 350 — bé đi một nghìn lần, mà vẫn là một số
 *      hợp lệ nên không ai thấy gì lạ.
 *
 *   3. TIN CỘT THÀNH TIỀN TRONG FILE. Đó là công thức Excel; người sửa số
 *      lượng mà không mở lại Excel thì nó lệch. Phải tính lại.
 *
 *   4. TỰ TẠO MẶT HÀNG TỪ FILE. Chốt 12/10/2026: mã lạ thì BỎ QUA, báo rõ.
 *      Tự tạo là cách nhanh nhất sinh hai bản ghi cho cùng một món.
 *
 *   5. DỊCH VỤ LỌT VÀO TỒN KHO. "Thay dầu" có tồn = 0 thì mọi hoá đơn chứa nó
 *      bị chặn ở bước kiểm tồn (xem PartsModel::coKho).
 *
 *   6. DÒNG BỎ QUA MÀ KHÔNG NÓI GÌ. Khai tồn đầu kỳ làm đúng một lần; một
 *      dòng rơi im lặng là một mặt hàng thiếu tồn mãi mãi, phát hiện ra lúc
 *      bán hàng mới biết.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc  = __DIR__ . '/../';
$base = 'http://localhost:88/tan-phat';

// ---------------------------------------------------------------------------
section('Migration + noi day');

$mg = glob($goc . 'database/migrations/*_man_ton_kho_dau_ky.php');
ok(count($mg) === 1, 'Co migration khai man Ton kho dau ky');
$src = count($mg) === 1 ? file_get_contents($mg[0]) : '';
ok(strpos($src, "'ton-kho-dau-ky'") !== false, 'Migration khai link `ton-kho-dau-ky`');
ok(strpos($src, 'chi_tan_phat') === false || strpos($src, "chi_tan_phat` = 1") === false,
   'Man KHONG mang co chi_tan_phat (day la man cua GARA)');
ok(strpos($src, "`groups` WHERE `name` = ?") !== false,
   'Migration cap quyen cho MOI nhom cung ten (moi gara mot nhom rieng tu 000093)',
   'Cap cho nhom dau tien la cac gara khac mo man ra khong thay gi');

$rt = codeOnly($goc . 'routes/web.php');
foreach (['ton-kho-dau-ky', 'ton-kho-dau-ky/file-mau', 'ton-kho-dau-ky/import'] as $r){
    ok(strpos($rt, "'" . $r . "'") !== false, "Co route `$r`");
}
$sb = file_get_contents($goc . 'app/views/layouts/admin/sidebar.php');
ok(preg_match("~'Kho'\s*=>\s*\[[^\]]*'ton-kho-dau-ky'~", $sb) === 1,
   'Man nam trong nhom Kho tren menu trai');

// ---------------------------------------------------------------------------
section('Loai phieu `ton_dau_ky`');

require_once $goc . 'app/models/GoodsReceiptsModel.php';
ok(isset(GoodsReceiptsModel::$types['ton_dau_ky']), 'Co loai phieu `ton_dau_ky`');
ok(!isset(GoodsReceiptsModel::loaiTuChon()['ton_dau_ky']),
   'Loai `ton_dau_ky` KHONG nam trong o chon cua form Them phieu nhap',
   'Chon tay la di vong qua cac chot cua man Ton kho dau ky, ma phieu van mang dung nhan do');
ok(isset(GoodsReceiptsModel::loaiTuChon()['nhap_mua']), 'Cac loai con lai van chon duoc');

$av = file_get_contents($goc . 'app/views/admin/goods-receipts/add.php');
ok(strpos($av, '$typesChon') !== false && !preg_match('~foreach \(\$types as~', $av),
   'Form Them phieu nhap dung $typesChon, khong dung $types');

// ---------------------------------------------------------------------------
section('Doc so kieu Viet (cho hong so 2)');

require_once $goc . 'app/controllers/admin/Tonkhodauky.php';
$rc  = new ReflectionClass('Tonkhodauky');
$doSo = $rc->getMethod('doSo'); $doSo->setAccessible(true);
/* Khong tao doi tuong that: constructor goi $this->model(...) can ca tang
   framework. invoke tren mot the hien chua khoi tao la du cho ham thuan tuy. */
$tkdk = $rc->newInstanceWithoutConstructor();
$so = function($s) use ($doSo, $tkdk){ return $doSo->invoke($tkdk, $s); };

ok($so('350.000') === 350000.0, 'Dau cham la dau NGHIN: "350.000" -> 350000',
   'Ep (float) thang ra 350 — be di mot nghin lan ma van la so hop le');
ok($so('7.000.000') === 7000000.0, '"7.000.000" -> 7000000');
ok($so('1,450,000') === 1450000.0, 'Kieu Anh: "1,450,000" -> 1450000');
ok($so('12,5') === 12.5, 'Dau phay la THAP PHAN khi sau no khong phai 3 chu so: "12,5" -> 12.5');
ok($so('12.5') === 12.5, '"12.5" -> 12.5');
ok($so('1.234,56') === 1234.56, 'Lan lon ca hai: "1.234,56" -> 1234.56 (dau dung SAU la thap phan)');
ok($so('1,234.56') === 1234.56, '"1,234.56" -> 1234.56');
ok($so('20') === 20.0, 'So nguyen tron');
ok($so('') === null && $so('   ') === null, 'O trong -> null');
ok($so('abc') === null, 'Chu -> null');
ok($so('-3') === -3.0, 'So am van doc ra (nguoi goi tu chan)');

// ---------------------------------------------------------------------------
section('Doc cot theo TEN, khong theo thu tu');

$doCot = $rc->getMethod('doCot'); $doCot->setAccessible(true);
$cot = function($h) use ($doCot, $tkdk){ return $doCot->invoke($tkdk, $h); };

$chuan = ['Mã hàng','Tên hàng hóa','Đơn vị tính','Kho','Số lượng tồn đầu kỳ',
          'Đơn giá vốn (VNĐ)','Thành tiền (VNĐ)','Ghi chú'];
$c = $cot($chuan);
ok(is_array($c) && $c['ma'] === 0 && $c['kho'] === 3 && $c['sl'] === 4 && $c['gia'] === 5,
   'Nhan dung tam cot cua file mau khach gui', json_encode($c));

/* DAO THU TU + CHEN COT LA: nguoi dung hay them cot, doi cho, xoa cot Ghi chu.
   Bam cung vao thu tu thi mot file nhin bang mat van hop le lai import ra so
   luong nam o cot gia. */
$dao = ['Kho','Ghi chú','Mã hàng','Cột lạ','Số lượng tồn đầu kỳ','Đơn giá vốn'];
$c = $cot($dao);
ok(is_array($c) && $c['kho'] === 0 && $c['ma'] === 2 && $c['sl'] === 4 && $c['gia'] === 5,
   'Dao thu tu va chen cot la van nhan dung', json_encode($c));

ok($cot(['Mã hàng','Tên hàng hóa']) === null,
   'Thieu cot bat buoc (Kho / So luong) -> tu choi ca file',
   'Doan bua mot trong ba cot do la import ra so sai ma khong ai biet');
ok($cot(['MA HANG','kho','so luong ton dau ky']) !== null,
   'Khong phan biet hoa thuong va dau tieng Viet');

// ---------------------------------------------------------------------------
section('Chay that qua HTTP');

if (!function_exists('curl_init')){ echo "\n[SKIP] PHP khong co curl.\n"; exit(summary()); }

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$MK   = 'ZzTonDk#2026';
$hash = \App\core\Hash::make($MK);
$tmp  = [];

$donSach = function() use ($pdo, &$tmp){
    $pdo->exec("DELETE i FROM goods_receipt_items i JOIN goods_receipts r ON r.id = i.receipt_id
                 WHERE r.reason LIKE 'ZZTDK%' OR r.receipt_no LIKE 'ZZTDK%'");
    $pdo->exec("DELETE FROM goods_receipts WHERE reason LIKE 'ZZTDK%' OR receipt_no LIKE 'ZZTDK%'");
    /* Phieu do chinh man nay lap mang ly do co dinh — don theo kho tam. */
    $pdo->exec("DELETE i FROM goods_receipt_items i JOIN goods_receipts r ON r.id = i.receipt_id
                 WHERE r.warehouse_id IN (SELECT id FROM warehouses WHERE code LIKE 'ZZTDK%')");
    $pdo->exec("DELETE FROM goods_receipts WHERE warehouse_id IN (SELECT id FROM warehouses WHERE code LIKE 'ZZTDK%')");
    $pdo->exec("DELETE FROM stock_cards WHERE warehouse_id IN (SELECT id FROM warehouses WHERE code LIKE 'ZZTDK%')");
    $pdo->exec("DELETE FROM stocks      WHERE warehouse_id IN (SELECT id FROM warehouses WHERE code LIKE 'ZZTDK%')");
    $pdo->exec("DELETE FROM warehouses  WHERE code LIKE 'ZZTDK%'");
    $pdo->exec("DELETE FROM parts       WHERE code LIKE 'ZZTDK%'");
    $pdo->exec("DELETE t FROM login_tokens t JOIN users u ON u.id = t.user_id WHERE u.email LIKE 'zz-tdk%@local.test'");
    $pdo->exec("DELETE FROM users WHERE email LIKE 'zz-tdk%@local.test'");
    foreach ($tmp as $f) @unlink($f);
};
$donSach();
register_shutdown_function($donSach);

$garaT = (int) $pdo->query("SELECT id FROM garages WHERE is_master = 1 ORDER BY id LIMIT 1")->fetchColumn();
$nhomA = (int) $pdo->query("SELECT id FROM `groups` WHERE name = 'Admin' LIMIT 1")->fetchColumn();
if (!$garaT || !$nhomA){ echo "\n[SKIP] Thieu gara tong / nhom Admin.\n"; exit(summary()); }

/* Kho RIENG cho test: dung kho that thi mat hang nao cung co the da co phat
   sinh, va khang dinh "nhan duoc dong nay" se do vi ly do khac. */
$pdo->prepare("INSERT INTO warehouses (code,name,is_default,sort_order,status,garage_id,create_at)
               VALUES ('ZZTDK-KHO','ZZ Kho ton dau ky',0,99,1,?,NOW())")->execute([$garaT]);
$khoId = (int) $pdo->lastInsertId();

$donVi = (int) $pdo->query("SELECT id FROM part_units WHERE garage_id IS NULL ORDER BY id LIMIT 1")->fetchColumn();
$tenDonVi = (string) $pdo->query("SELECT name FROM part_units WHERE id = $donVi")->fetchColumn();
$taoHang = function($ma, $ten, $loai) use ($pdo, $donVi){
    $pdo->prepare("INSERT INTO parts (code,name,item_type,slug,unit_id,price,status,show_on_web,create_at)
                   VALUES (?,?,?,?,?,100000,1,0,NOW())")
        ->execute([$ma, $ten, $loai, strtolower($ma), $donVi]);
    return (int) $pdo->lastInsertId();
};
$hangA  = $taoHang('ZZTDK-A', 'ZZ Hang A', 'part');
$hangB  = $taoHang('ZZTDK-B', 'ZZ Hang B', 'part');
$hangC  = $taoHang('ZZTDK-C', 'ZZ Hang C', 'part');
$dichVu = $taoHang('ZZTDK-DV', 'ZZ Dich vu', 'service');

$pdo->prepare("INSERT INTO users (name,email,password,group_id,status,garage_id,create_at)
               VALUES ('ZZ Ton dau ky','zz-tdk@local.test',?,?,1,?,NOW())")
    ->execute([$hash, $nhomA, $garaT]);

$jar = tempnam(sys_get_temp_dir(), 'zztdk'); $tmp[] = $jar;
$http = function($m, $url, $d = null, $file = null) use ($jar){
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 40]);
    if ($m === 'POST'){
        curl_setopt($ch, CURLOPT_POST, true);
        if ($file){ $d['file'] = new CURLFile($file, 'text/csv', basename($file));
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $d); }
        else      { curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d)); }
    }
    $raw = curl_exec($ch); $i = curl_getinfo($ch); curl_close($ch);
    $body = $raw === false ? '' : substr($raw, (int) $i['header_size']);
    return ['code' => (int) $i['http_code'], 'loc' => (string) $i['redirect_url'],
            'text' => html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8')];
};
$token = function($h){ return preg_match('~name="_token" value="([^"]+)"~', $h, $m) ? $m[1] : ''; };

$r = $http('GET', "$base/dang-nhap");
if ($r['code'] === 0){ echo "\n[SKIP] Apache khong chay (localhost:88).\n"; exit(summary()); }
$tk = $token($r['text']);
$http('POST', "$base/dang-nhap", ['email' => 'zz-tdk@local.test', 'password' => $MK, '_token' => $tk]);

$r = $http('GET', "$base/admin/ton-kho-dau-ky");
ok($r['code'] === 200, 'Mo duoc man Ton kho dau ky (HTTP ' . $r['code'] . ')', $r['loc']);
$tk = $token($r['text']) ?: $tk;

/* MAN PHAI TU GIAI THICH: no dung dung mot lan, khong ai ngoi canh chi. */
ok(strpos($r['text'], 'Thẻ kho') !== false && strpos($r['text'], 'khoảng ngày') !== false,
   'Man noi ro khac gi voi dong "Ton dau ky" o The kho / Bien dong ton',
   'Cung ten khac nghia la loai nham lan dat nhat');
ok(strpos($r['text'], 'NHÁP') !== false,
   'Man noi ro import ra phieu NHAP, chua vao ton');

$csv = function($rows) use (&$tmp){
    $p = tempnam(sys_get_temp_dir(), 'zztdk') . '.csv'; $tmp[] = $p;
    $h = fopen($p, 'w'); fwrite($h, "\xEF\xBB\xBF");
    foreach ($rows as $r) fputcsv($h, $r);
    fclose($h);
    return $p;
};
$tieuDe = ['Mã hàng','Tên hàng hóa','Đơn vị tính','Kho','Số lượng tồn đầu kỳ',
           'Đơn giá vốn (VNĐ)','Thành tiền (VNĐ)','Ghi chú'];

$nap = function($file) use ($http, $base, &$tk){
    $http('POST', "$base/admin/ton-kho-dau-ky/import", ['_token' => $tk, 'ngay' => '2026-01-01'], $file);
    return $http('GET', "$base/admin/ton-kho-dau-ky");
};

// --- Lần 1: file hợp lệ ---
$f1 = $csv([$tieuDe,
    ['ZZTDK-A', 'ZZ Hang A', $tenDonVi, 'ZZ Kho ton dau ky', '12', '850.000', '10.200.000', ''],
    ['ZZTDK-B', 'ZZ Hang B', $tenDonVi, 'ZZ Kho ton dau ky', '5',  '980.000', '4.900.000',  'ghi chu'],
    /* CUNG MA, CUNG KHO, HAI DONG: nguoi lap file hay tach theo vi tri de
       trong kho. Phai CONG lai chu khong bo mot dong. */
    ['ZZTDK-B', 'ZZ Hang B', $tenDonVi, 'ZZ Kho ton dau ky', '3',  '980.000', '2.940.000',  'dong thu hai'],
]);
$r = $nap($f1);

$phieu = $pdo->query("SELECT * FROM goods_receipts WHERE warehouse_id = $khoId AND receipt_type = 'ton_dau_ky'
                      ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
ok(!empty($phieu), 'Import xong co MOT phieu ton dau ky');
ok(!empty($phieu) && (int) $phieu['status'] === 0,
   'Phieu o trang thai NHAP — chua dong nao vao ton',
   'Ghi so thang la mat buoc soat, va lo nap hai lan thi so nhan doi khong dau vet');

$dong = !empty($phieu)
      ? $pdo->query("SELECT i.*, p.code FROM goods_receipt_items i JOIN parts p ON p.id = i.part_id
                      WHERE i.receipt_id = {$phieu['id']} ORDER BY p.code")->fetchAll(PDO::FETCH_ASSOC)
      : [];
ok(count($dong) === 2, 'Hai ma hang -> HAI dong (khong phai ba)', 'Dang co ' . count($dong) . ' dong');

$theoMa = [];
foreach ($dong as $d) $theoMa[$d['code']] = $d;
ok(isset($theoMa['ZZTDK-B']) && (float) $theoMa['ZZTDK-B']['quantity'] === 8.0,
   'Hai dong cung ma duoc CONG lai: 5 + 3 = 8',
   'Dang la: ' . (isset($theoMa['ZZTDK-B']) ? $theoMa['ZZTDK-B']['quantity'] : 'khong co dong'));

// Chỗ hỏng 2 — đọc số kiểu Việt, qua đường HTTP thật
ok(isset($theoMa['ZZTDK-A']) && (float) $theoMa['ZZTDK-A']['unit_cost'] === 850000.0,
   'Gia von "850.000" vao CSDL la 850000',
   'Dang la: ' . (isset($theoMa['ZZTDK-A']) ? $theoMa['ZZTDK-A']['unit_cost'] : '?'));

// Chỗ hỏng 3 — thành tiền tính lại, không lấy cột trong file
ok(isset($theoMa['ZZTDK-B']) && (float) $theoMa['ZZTDK-B']['amount'] === 8 * 980000.0,
   'Thanh tien TINH LAI tu so luong x don gia, khong lay cot trong file',
   'File ghi 4.900.000 cho dong dau; dung phai la 8 x 980.000 = 7.840.000, dang la '
   . (isset($theoMa['ZZTDK-B']) ? $theoMa['ZZTDK-B']['amount'] : '?'));
ok(!empty($phieu) && (float) $phieu['total_amount'] === (12 * 850000.0 + 8 * 980000.0),
   'Tong phieu cong dung');

// --- Chỗ hỏng 1: nạp lại lần hai thì KHÔNG cộng trùng ---
section('1. Nap lai lan hai KHONG cong trung');

/* Ghi so phieu dau de co phat sinh that trong the kho — do la trang thai that
   sau khi nguoi dung soat xong. */
$pdo->exec("UPDATE goods_receipts SET status = 1 WHERE id = {$phieu['id']}");
foreach ($dong as $d){
    $pdo->prepare("INSERT INTO stock_cards (warehouse_id,part_id,move_date,doc_type,doc_id,doc_no,
                     qty_in,qty_out,unit_cost,balance_qty,balance_value,create_at)
                   VALUES (?,?,?,'receipt',?,?,?,0,?,?,?,NOW())")
        ->execute([$khoId, (int) $d['part_id'], '2026-01-01', (int) $phieu['id'], $phieu['receipt_no'],
                   $d['quantity'], $d['unit_cost'], $d['quantity'], $d['amount']]);
    $pdo->prepare("INSERT INTO stocks (warehouse_id,part_id,quantity,avg_cost,update_at)
                   VALUES (?,?,?,?,NOW())")
        ->execute([$khoId, (int) $d['part_id'], $d['quantity'], $d['unit_cost']]);
}

$r = $nap($f1);
$soPhieu = (int) $pdo->query("SELECT COUNT(*) FROM goods_receipts
                               WHERE warehouse_id = $khoId AND receipt_type = 'ton_dau_ky'")->fetchColumn();
ok($soPhieu === 1, 'Nap lai cung file -> KHONG lap them phieu nao',
   "Dang co $soPhieu phieu — moi dong deu phai roi vao chot 'da co phat sinh'");
ok(strpos($r['text'], 'đã có phát sinh') !== false,
   'Bao ro ly do: kho da co phat sinh cho mat hang do',
   'Day la chot that chong cong trung, khong phai loi dan trong tai lieu');

$tonA = (float) $pdo->query("SELECT quantity FROM stocks WHERE warehouse_id = $khoId AND part_id = $hangA")->fetchColumn();
ok($tonA === 12.0, 'Ton khong bi nhan doi sau lan nap thu hai', "Dang la $tonA, dung phai 12");

// --- Chỗ hỏng 4, 5, 6: các dòng bị bỏ qua đều phải nói rõ lý do ---
section('4+5+6. Dong bo qua phai noi ro ly do');

$f2 = $csv([$tieuDe,
    ['ZZTDK-LA',  'Hang khong co trong danh muc', $tenDonVi, 'ZZ Kho ton dau ky', '4', '1.000', '4.000', ''],
    ['ZZTDK-DV',  'ZZ Dich vu',  $tenDonVi,  'ZZ Kho ton dau ky', '5', '150.000', '750.000', ''],
    ['ZZTDK-C',   'ZZ Hang C',   $tenDonVi,  'ZZ Kho khong co',   '2', '100.000', '200.000', ''],
    ['ZZTDK-C',   'ZZ Hang C',   $tenDonVi,  'ZZ Kho ton dau ky', '-3', '100.000', '0', ''],
    ['',          'Thieu ma',    $tenDonVi,  'ZZ Kho ton dau ky', '1', '1.000', '1.000', ''],
]);
$r = $nap($f2);

$mong = [
    'chưa có trong danh mục'  => 'Ma la -> BO QUA, khong tu tao mat hang (chot 4)',
    'là dịch vụ'              => 'Dich vu -> bo qua, khong co ton kho (chot 5)',
    'không tìm thấy kho'      => 'Kho khong khop ten -> bo qua, noi ro phai trung Danh muc kho',
    'lớn hơn 0'               => 'So luong am / bang 0 -> bo qua',
    'thiếu mã hàng'           => 'Dong thieu ma hang -> bo qua',
];
foreach ($mong as $chuoi => $nhan){
    ok(strpos($r['text'], $chuoi) !== false, $nhan, "Khong thay cau chua: \"$chuoi\"");
}
ok(substr_count($r['text'], 'Dòng ') >= 5,
   'MOI dong bo qua deu duoc liet ke kem so dong (chot 6)',
   'Mot dong roi im lang la mot mat hang thieu ton mai mai');
ok((int) $pdo->query("SELECT COUNT(*) FROM parts WHERE code = 'ZZTDK-LA'")->fetchColumn() === 0,
   'Ma la KHONG bi tu tao thanh mat hang moi');

// --- File mẫu tải về ---
section('File mau tai ve');

$r = $http('GET', "$base/admin/ton-kho-dau-ky/file-mau");
ok($r['code'] === 200, 'Tai duoc file mau (HTTP ' . $r['code'] . ')');
ok(strpos($r['text'], 'Mã hàng') !== false && strpos($r['text'], 'Số lượng tồn đầu kỳ') !== false,
   'File mau co dung tieu de tam cot cua khach');
ok(strpos($r['text'], "\xEF\xBB\xBF") !== false || strpos($r['text'], 'Mã hàng') !== false,
   'File mau la UTF-8 (Excel mo ra dung tieng Viet)');

$donSach();
ok((int) $pdo->query("SELECT COUNT(*) FROM warehouses WHERE code LIKE 'ZZTDK%'")->fetchColumn() === 0
   && (int) $pdo->query("SELECT COUNT(*) FROM parts WHERE code LIKE 'ZZTDK%'")->fetchColumn() === 0,
   'Da don sach du lieu test');

echo "\n";
exit(summary());

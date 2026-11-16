<?php
/**
 * MA QR CHUYEN KHOAN (VietQR) TREN HOA DON — anh yeu cau 16/11/2026.
 *
 * Khach mo app ngan hang quet ma tren hoa don la ra san: ngan hang, so tai
 * khoan, chu tai khoan, so tien cua hoa don, noi dung = so hoa don. Chuan
 * VietQR cua NAPAS dung tren khung EMVCo:
 *
 *   00 = 01                  phien ban
 *   01 = 11 / 12             11: ma tinh (khong so tien), 12: ma dong (co so tien)
 *   38 = 00 A000000727       GUID cua NAPAS
 *        01 [00 BIN][01 STK] ngan hang nhan + so tai khoan
 *        02 QRIBFTTA         dich vu: chuyen nhanh toi TAI KHOAN
 *   53 = 704                 VND
 *   54 = so tien             (chi co o ma dong)
 *   58 = VN
 *   62 = 08 noi dung         noi dung chuyen khoan
 *   63 = CRC16-CCITT-FALSE   tinh tren toan bo chuoi, ke ca "6304"
 *
 * BA DIEU PHAI GIU:
 *   1. Chuoi dung dinh dang va dung CRC — so voi MAU CONG KHAI, khong so voi
 *      chinh ham minh viet (so voi chinh minh thi sai dau cung "dung").
 *   2. Gara chua khai tai khoan thi KHONG ra QR, va khong loi.
 *   3. Gara A khong thay, khong dung duoc tai khoan cua gara B (ke ca Tan Phat).
 *      Tien chuyen theo QR la tien that: in nham tai khoan la khach chuyen nham
 *      nguoi.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

$goc = __DIR__ . '/../';

/* Doc chuoi TLV cua EMVCo. Viet RIENG o day, khong muon ham cua ban ma — test
   ma dung chinh ham can test de doc lai thi hai cai sai giong nhau van khop. */
$tlv = function($s){
    $o = []; $i = 0; $n = strlen((string) $s);
    while ($i + 4 <= $n){
        $id  = substr($s, $i, 2);
        $len = substr($s, $i + 2, 2);
        if (!ctype_digit($len)) return null;
        $o[$id] = substr($s, $i + 4, (int) $len);
        $i += 4 + (int) $len;
    }
    return $i === $n ? $o : null;
};

/* CRC-16/CCITT-FALSE viet tay theo dinh nghia (da thuc 0x1021, khoi dau
   0xFFFF, khong dao bit, khong XOR cuoi) — de doi chieu voi ham cua ban ma. */
$crcTay = function($s){
    $crc = 0xFFFF;
    for ($i = 0, $n = strlen($s); $i < $n; $i++){
        $crc ^= ord($s[$i]) << 8;
        for ($b = 0; $b < 8; $b++){
            $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
            $crc &= 0xFFFF;
        }
    }
    return sprintf('%04X', $crc);
};

$crcDung = function($chuoi) use ($crcTay){
    if (strlen((string) $chuoi) < 8 || substr($chuoi, -8, 4) !== '6304') return false;
    return $crcTay(substr($chuoi, 0, -4)) === substr($chuoi, -4);
};

// ===========================================================================
section('Lop VietQr co mat');

$coLop = class_exists('\App\core\VietQr');
ok($coLop, 'Co lop \\App\\core\\VietQr (core/VietQr.php)');
if (!$coLop){ exit(summary()); }

use App\core\VietQr;

// ===========================================================================
section('CRC16-CCITT-FALSE');

ok(VietQr::crc16('123456789') === '29B1',
   'CRC cua "123456789" = 29B1 (gia tri kiem chuan cua CRC-16/CCITT-FALSE)',
   'Nhan duoc: ' . var_export(VietQr::crc16('123456789'), true));
ok(VietQr::crc16('') === 'FFFF', 'CRC chuoi rong = FFFF (khoi dau, khong XOR cuoi)');
ok(VietQr::crc16('A') === $crcTay('A') && strlen(VietQr::crc16('A')) === 4,
   'CRC luon 4 ky tu hex, viet hoa, dem so 0 o dau');

// ===========================================================================
section('So voi MAU CONG KHAI (README cua vietnam-qr-pay va sunary/vietqr)');

/* Bon chuoi chep nguyen van tu README cua hai thu vien ma nguon mo, CRC da
   doi chieu doc lap bang binascii.crc_hqx cua Python ngay 16/11/2026:
     github.com/xuannghia/vietnam-qr-pay   (ACB 970416, STK 257678859)
     github.com/sunary/vietqr              (Techcombank 970407, STK 9796868) */
$mau = [
    ['ACB, ma tinh, khong so tien',
     ['970416', '257678859', null, ''],
     '00020101021138530010A0000007270123000697041601092576788590208QRIBFTTA53037045802VN6304AE9F'],
    ['ACB, ma dong, 10.000d, "Chuyen tien"',
     ['970416', '257678859', 10000, 'Chuyen tien'],
     '00020101021238530010A0000007270123000697041601092576788590208QRIBFTTA53037045405100005802VN62150811Chuyen tien630453E6'],
    ['ACB, ma dong, 999.999d, "Cam on nhe"',
     ['970416', '257678859', 999999, 'Cam on nhe'],
     '00020101021238530010A0000007270123000697041601092576788590208QRIBFTTA530370454069999995802VN62140810Cam on nhe6304E786'],
    ['Techcombank, ma tinh, co noi dung',
     ['970407', '9796868', null, 'gen by sunary/vietqr'],
     '00020101021138510010A00000072701210006970407010797968680208QRIBFTTA53037045802VN62240820gen by sunary/vietqr6304BE74'],
];
foreach ($mau as $m){
    list($ten, $vao, $mong) = $m;
    ok($crcDung($mong), "Mau cong khai tu kiem dung CRC: $ten");
    $ra = VietQr::chuoi($vao[0], $vao[1], $vao[2], $vao[3]);
    ok($ra === $mong, "Dung y het mau: $ten", "Mong: $mong\n         Ra:   " . var_export($ra, true));
}

// ===========================================================================
section('Chuoi cho mot hoa don: dung tung truong');

$q = VietQr::chuoi('970436', '0011000123456', 1954800, 'HD-000002');
$t = $tlv($q);
ok(is_array($t), 'Doc duoc chuoi TLV tron ven (khong thua, khong thieu ky tu)', var_export($q, true));
if (is_array($t)){
    /* strval: PHP doi khoa '38' thanh so nguyen 38 (con '00', '01' thi giu chuoi) */
    ok(array_map('strval', array_keys($t)) === ['00', '01', '38', '53', '54', '58', '62', '63'],
       'Thu tu the: 00 01 38 53 54 58 62 63', implode(' ', array_keys($t)));
    ok($t['00'] === '01', '00 = 01 (phien ban)');
    ok($t['01'] === '12', '01 = 12 (ma dong — co so tien)');
    $t38 = $tlv($t['38']);
    ok(is_array($t38) && $t38['00'] === 'A000000727', '38/00 = A000000727 (GUID NAPAS)');
    $t3801 = is_array($t38) ? $tlv($t38['01']) : null;
    ok(is_array($t3801) && $t3801['00'] === '970436', '38/01/00 = BIN ngan hang nhan (970436 Vietcombank)');
    ok(is_array($t3801) && $t3801['01'] === '0011000123456', '38/01/01 = so tai khoan');
    ok(is_array($t38) && $t38['02'] === 'QRIBFTTA', '38/02 = QRIBFTTA (chuyen toi tai khoan)');
    ok($t['53'] === '704', '53 = 704 (VND)');
    ok($t['54'] === '1954800', '54 = 1954800 (so tien, khong phan le, khong dau cham)');
    ok($t['58'] === 'VN', '58 = VN');
    $t62 = $tlv($t['62']);
    ok(is_array($t62) && $t62['08'] === 'HD-000002', '62/08 = so hoa don');
    ok($crcDung($q), '63 = CRC dung (doi chieu bang ham CRC viet tay trong test)');
}

/* So tien: dong Viet Nam khong co hao. Tong luu decimal(15,2) nen doc tu CSDL
   ra la "1954800.00" — in thang vao the 54 la ngan hang doc thanh 195.480.000. */
$t = $tlv(VietQr::chuoi('970436', '0011000123456', '1954800.00', 'HD-1'));
ok(is_array($t) && $t['54'] === '1954800', 'So tien dang chuoi "1954800.00" -> "1954800"');
$t = $tlv(VietQr::chuoi('970436', '0011000123456', 1954800.5, 'HD-1'));
ok(is_array($t) && $t['54'] === '1954801', 'Le nua dong lam tron len (1954800.5 -> 1954801)');
$t = $tlv(VietQr::chuoi('970436', '0011000123456', 0, 'HD-1'));
ok(is_array($t) && !isset($t['54']) && $t['01'] === '11', 'So tien 0 -> ma tinh, khong co the 54');

/* So tai khoan go co dau cach (thoi quen chep tu the ngan hang) van dung. */
$t = $tlv(VietQr::chuoi('970436', ' 0011 0001 23456 ', 1000, 'HD-1'));
$t38 = is_array($t) ? $tlv($t['38']) : null;
$t3801 = is_array($t38) ? $tlv($t38['01']) : null;
ok(is_array($t3801) && $t3801['01'] === '0011000123456', 'So tai khoan co dau cach -> bo dau cach');

// ===========================================================================
section('Thieu / sai thong tin -> KHONG ra chuoi (khong loi)');

ok(VietQr::chuoi('', '0011000123456', 1000, 'HD-1') === null,      'Chua chon ngan hang -> null');
ok(VietQr::chuoi('970436', '', 1000, 'HD-1') === null,            'Chua co so tai khoan -> null');
ok(VietQr::chuoi(null, null, 1000, 'HD-1') === null,              'Ca hai null -> null');
ok(VietQr::chuoi('123456', '0011000123456', 1000, 'HD-1') === null,
   'BIN khong co trong danh sach NAPAS -> null (khong in ma toi ngan hang khong ton tai)');
ok(VietQr::chuoi('970436', '0011-000-123', 1000, 'HD-1') === null, 'So tai khoan co ky tu la -> null');
ok(VietQr::chuoi('970436', str_repeat('1', 20), 1000, 'HD-1') === null, 'So tai khoan qua 19 ky tu -> null');
ok(VietQr::chuoi('970436', '0011000123456', -5000, 'HD-1') === null, 'So tien am -> null');

// ===========================================================================
section('Noi dung chuyen khoan');

ok(VietQr::noiDung('HD-000002') === 'HD-000002', 'So hoa don giu nguyen');
ok(VietQr::noiDung('Hoá đơn HĐ-0001/2026') === 'Hoa don HD-0001 2026',
   'Bo dau tieng Viet, ky tu la thanh dau cach', var_export(VietQr::noiDung('Hoá đơn HĐ-0001/2026'), true));
ok(strlen(VietQr::noiDung(str_repeat('A', 40))) === 25, 'Cat con 25 ky tu (gioi han cua the 62/08)');
ok(VietQr::noiDung("  HD   01  ") === 'HD 01', 'Gop dau cach thua');
ok(VietQr::noiDung('') === '', 'Rong -> rong');

// ===========================================================================
section('Danh sach ngan hang (ma BIN NAPAS) — de CHON, khong go tay');

$ds = VietQr::nganHang();
ok(is_array($ds) && count($ds) >= 40, 'Danh sach co it nhat 40 ngan hang', is_array($ds) ? count($ds) . ' dong' : '');
$binSai = [];
foreach ((array) $ds as $bin => $nh){
    if (!preg_match('~^\d{6}$~', (string) $bin) || empty($nh['ten']) || empty($nh['ten_day_du'])) $binSai[] = $bin;
}
ok(empty($binSai), 'Moi dong: BIN dung 6 chu so, co ten ngan va ten day du', implode(', ', $binSai));
foreach (['970436' => 'Vietcombank', '970415' => 'VietinBank', '970418' => 'BIDV', '970405' => 'Agribank',
          '970407' => 'Techcombank', '970422' => 'MBBank', '970416' => 'ACB', '970432' => 'VPBank',
          '970403' => 'Sacombank', '970423' => 'TPBank'] as $bin => $ten){
    ok(isset($ds[$bin]) && $ds[$bin]['ten'] === $ten, "BIN $bin = $ten");
}
ok(VietQr::tenNganHang('970436') === 'Vietcombank', 'tenNganHang(970436) = Vietcombank');
ok(VietQr::tenNganHang('123456') === '', 'tenNganHang(BIN la) = rong');
/* Vi dien tu (MoMo, ZaloPay) khong phai tai khoan ngan hang cua gara: ma
   VietQR cua vi dung so tai khoan rieng do vi cap, go so dien thoai vao la sai. */
ok(!isset($ds['971025']) && !isset($ds['971101']), 'Khong co vi dien tu MoMo / ZaloPay trong danh sach');

// ===========================================================================
section('QR cho hoa don tu cau hinh cua gara');

$cfCo = ['bank_bin' => '970436', 'bank_account' => '0011000123456', 'bank_holder' => 'CONG TY ZZQR'];
$hd = VietQr::choHoaDon($cfCo, 1954800, 'HD-000002');
ok(is_array($hd), 'Gara da khai du -> co QR');
if (is_array($hd)){
    $t = $tlv($hd['chuoi']);
    ok(is_array($t) && $t['54'] === '1954800' && $crcDung($hd['chuoi']), 'Chuoi QR mang dung so tien, dung CRC');
    ok($hd['ngan_hang'] === 'Vietcombank', 'Kem ten ngan hang (lay tu danh sach, khong lay o go tay)');
    ok($hd['so_tk'] === '0011000123456' && $hd['chu_tk'] === 'CONG TY ZZQR', 'Kem so TK + chu TK de in canh ma');
    ok($hd['noi_dung'] === 'HD-000002' && (int) $hd['so_tien'] === 1954800, 'Kem noi dung + so tien de in canh ma');
}
ok(VietQr::choHoaDon([], 1954800, 'HD-1') === null, 'Gara chua khai gi -> khong QR');
ok(VietQr::choHoaDon(['bank_account' => '0011000123456', 'bank_name' => 'Vietcombank - CN Ha Noi'], 1954800, 'HD-1') === null,
   'Gara moi co ten ngan hang GO TAY (du lieu cu, chua chon BIN) -> khong QR');
ok(VietQr::choHoaDon(['bank_bin' => '970436'], 1954800, 'HD-1') === null, 'Co ngan hang ma chua co so TK -> khong QR');
ok(VietQr::choHoaDon($cfCo, 0, 'HD-1') === null, 'Hoa don khong con gi phai tra (0d) -> khong QR');

// ===========================================================================
section('Ma nguon: ve tai cho, khong gui so tien ra dich vu ngoai');

$view = file_get_contents($goc . 'app/views/admin/print/chung-tu.php');
ok(strpos($view, 'https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js') !== false,
   'Mau in nap thu vien ve QR tu cdnjs, GHI RO phien ban (qrcode-generator 1.4.4)');
ok(strpos($view, 'data-vietqr') !== false, 'Mau in co cho dat chuoi VietQR (data-vietqr)');

$coVietqrIo = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($goc . 'app', FilesystemIterator::SKIP_DOTS));
foreach ($it as $f){
    if (substr($f->getFilename(), -4) !== '.php') continue;
    if (stripos(codeOnly($f->getPathname()), 'img.vietqr.io') !== false) $coVietqrIo[] = $f->getFilename();
}
ok(empty($coVietqrIo), 'Khong noi nao goi img.vietqr.io (so tien + thong tin khach khong roi khoi may)',
   implode(', ', $coVietqrIo));

$form = file_get_contents($goc . 'app/views/admin/settings/form.php');
ok(preg_match('~<select[^>]*name="bank_bin"~', $form) === 1, 'Man Cau hinh: ngan hang la O CHON (select name="bank_bin")');
ok(strpos($form, 'name="bank_name"') === false, 'Man Cau hinh: KHONG con o go tay ten ngan hang');

// ===========================================================================
section('CSDL that — tung gara mot tai khoan, khong lan sang nhau');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){ echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary()); }

$so = function($sql, $b = []) use ($pdo){ $st = $pdo->prepare($sql); $st->execute($b); return (int) $st->fetchColumn(); };

$donCli = function() use ($pdo){
    $g = "SELECT id FROM (SELECT id FROM garages WHERE code LIKE 'ZZQR%') zg";
    foreach (["DELETE FROM garage_settings WHERE garage_id IN ($g)",
              "DELETE FROM garages WHERE code LIKE 'ZZQR%'"] as $c){
        try { $pdo->exec($c); } catch (\Throwable $e){}
    }
};
$donCli();
register_shutdown_function($donCli);

$taoGara = function($ma, $ten) use ($pdo){
    $pdo->prepare("INSERT INTO garages (code, name, is_master, status, sort_order, create_at)
                   VALUES (?, ?, 0, 1, 99, NOW())")->execute([$ma, $ten]);
    return (int) $pdo->lastInsertId();
};
$GA = $taoGara('ZZQRA', 'ZZQR Gara A');
$GB = $taoGara('ZZQRB', 'ZZQR Gara B');

$stkA = '1903' . random_int(10000000, 99999999);
$now  = date('Y-m-d H:i:s');
$st = $pdo->prepare("INSERT INTO garage_settings (garage_id, skey, svalue, update_at) VALUES (?, ?, ?, ?)");
$st->execute([$GA, 'bank_bin',     '970407',       $now]);
$st->execute([$GA, 'bank_name',    'Techcombank',  $now]);
$st->execute([$GA, 'bank_account', $stkA,          $now]);
$st->execute([$GA, 'bank_holder',  'ZZQR GARA A',  $now]);

$cfA = cau_hinh_in_an($GA);
$cfB = cau_hinh_in_an($GB);
ok(isset($cfA['bank_bin']) && $cfA['bank_bin'] === '970407' && $cfA['bank_account'] === $stkA,
   'Phieu in cua gara A doc dung ngan hang + so TK cua gara A');
ok(empty($cfB['bank_bin']) && empty($cfB['bank_account']),
   'Phieu in cua gara B (chua khai) KHONG mang tai khoan nao — ca cua gara A lan cua Tan Phat');

/* Man Cau hinh cua gara doc map() — ban tron Cau hinh chung lam nen. Tai
   khoan ngan hang thi KHONG duoc thua huong: gara B chua khai ma thay so tai
   khoan cua Tan Phat dien san la de bam Luu "chep" luon thanh cua minh. */
$mapB = \App\core\Model::trongGara($GB, function(){ return \App\core\Load::model('GarageSettingsModel')->map(); });
$chungBank = $pdo->query("SELECT svalue FROM site_settings WHERE skey = 'bank_account'")->fetchColumn();
ok(empty($mapB['bank_account']) && empty($mapB['bank_bin']) && empty($mapB['bank_holder']),
   'Gara B mo man Cau hinh: o tai khoan TRONG — khong thua huong tai khoan cua Tan Phat',
   'Dang thay: ' . var_export(isset($mapB['bank_account']) ? $mapB['bank_account'] : null, true));
$mapA = \App\core\Model::trongGara($GA, function(){ return \App\core\Load::model('GarageSettingsModel')->map(); });
ok(isset($mapA['bank_account']) && $mapA['bank_account'] === $stkA, 'Gara A mo man Cau hinh: thay dung so TK cua minh');
ok(!in_array($stkA, array_map('strval', array_values((array) $mapB)), true), 'Gara B khong thay so TK cua gara A o dau ca');

// ---------------------------------------------------------------------------
section('Mau in hoa don (CLI)');

\App\core\Load::model('PartsModel');
$canhBao = [];
$inHd = function($cf, $tong, $so, $hienNh = true) use (&$canhBao){
    set_error_handler(function($no, $str, $file, $line) use (&$canhBao){ $canhBao[] = "$str @ " . basename($file) . ":$line"; return true; });
    ob_start();
    try {
        in_chung_tu(['loai' => 'HOÁ ĐƠN BÁN HÀNG', 'so' => $so, 'ngay' => date('Y-m-d'),
                     'subtotal' => $tong, 'vatRate' => 0, 'tax' => 0, 'total' => $tong,
                     'hienNganHang' => $hienNh, 'nhanKy' => ['NGƯỜI MUA HÀNG', 'ĐẠI DIỆN BÊN BÁN']],
            ['name' => 'Khach ZZQR'], [], $cf, '', false);
    } finally {
        $html = ob_get_clean();
        restore_error_handler();
    }
    return $html;
};
$layQr = function($html){
    return preg_match('~data-vietqr="([^"]+)"~', $html, $m) ? html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8') : null;
};

$hA = $inHd($cfA, 2519000, 'ZZQR-CLI-1');
$qA = $layQr($hA);
$tA = $tlv($qA);
ok($qA !== null && $crcDung($qA), 'Hoa don gara A: co ma QR, CRC dung');
ok(is_array($tA) && $tA['54'] === '2519000', 'Hoa don gara A: QR mang dung tong tien 2.519.000');
$t62 = is_array($tA) ? $tlv($tA['62']) : null;
ok(is_array($t62) && $t62['08'] === 'ZZQR-CLI-1', 'Hoa don gara A: noi dung QR = so hoa don');
$t38 = is_array($tA) ? $tlv($tA['38']) : null;
$t3801 = is_array($t38) ? $tlv($t38['01']) : null;
ok(is_array($t3801) && $t3801['00'] === '970407' && $t3801['01'] === $stkA, 'Hoa don gara A: QR tro vao tai khoan cua gara A');
$chuA = html_entity_decode($hA, ENT_QUOTES | ENT_HTML5, 'UTF-8');
foreach (['Techcombank', $stkA, 'ZZQR GARA A', '2.519.000', 'ZZQR-CLI-1'] as $chu){
    ok(mb_strpos($chuA, $chu) !== false, "Canh ma QR co ghi: $chu");
}

$hB = $inHd($cfB, 2519000, 'ZZQR-CLI-2');
ok($layQr($hB) === null, 'Hoa don gara B (chua khai tai khoan): KHONG co ma QR');
ok(strpos($hB, $stkA) === false, 'Hoa don gara B khong lo so TK cua gara A');
ok($chungBank === false || $chungBank === '' || strpos($hB, (string) $chungBank) === false,
   'Hoa don gara B khong in so TK cua Tan Phat');
ok(strpos($hB, 'qrcode.min.js') === false, 'Khong co QR thi khong nap thu vien ve QR');
ok(stripos($hB, 'TỔNG CỘNG THANH TOÁN') !== false, 'Hoa don gara B van in day du, khong vo');

$hBg = $inHd($cfA, 2519000, 'ZZQR-CLI-3', false);
ok($layQr($hBg) === null, 'Bao gia (khong phai chung tu doi tien) khong in ma QR');

$hKhong = $inHd($cfA, 0, 'ZZQR-CLI-4');
ok($layQr($hKhong) === null, 'Hoa don 0d: khong in ma QR');

$cu = ['bank_name' => 'Vietcombank - CN Ha Noi', 'bank_account' => '0011000999888', 'bank_holder' => 'ZZQR CU'];
$hCu = $inHd($cu, 500000, 'ZZQR-CLI-5');
ok($layQr($hCu) === null && strpos($hCu, '0011000999888') !== false,
   'Du lieu cu (ten ngan hang go tay, chua chon BIN): van in chu nhu truoc, chi khong co QR');

/* DONG NHAC TREN MAN HINH (16/11/2026) — anh mo hoa don gara Sai Gon tren dien
   thoai, khong thay QR, hoi "qr cho nay dau". Gara chua chon ngan hang thi hoa
   don im lang khong co QR, khong ai biet vi sao. Nhac nam trong thanh cong cu
   (an khi in), KHONG in ra giay. */
$nhac = function($html){
    return preg_match('~<div class="nhac-qr"[^>]*>(.*?)</div>~s', $html, $m)
        ? html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8') : null;
};
$trongThanhCongCu = function($html){
    $a = strpos($html, '<div class="thanh-cong-cu">');
    $b = strpos($html, '<div class="nhac-qr"');
    $c = strpos($html, '<div class="to-in">');
    return $a !== false && $b !== false && $c !== false && $a < $b && $b < $c;
};
$nB = $nhac($hB);
ok($nB !== null && mb_strpos($nB, 'Chưa có mã QR chuyển khoản') !== false,
   'Hoa don gara chua khai tai khoan: man hinh NHAC "Chua co ma QR chuyen khoan"', var_export($nB, true));
ok($nB !== null && mb_strpos($nB, 'Cấu hình') !== false && mb_strpos($nB, 'ngân hàng') !== false,
   'Loi nhac chi cho: vao Cau hinh chon ngan hang');
ok($trongThanhCongCu($hB), 'Loi nhac nam trong thanh cong cu (an khi in, khong ra giay)');
ok($nhac($hA) === null, 'Hoa don da co QR: khong nhac');
ok($nhac($hBg) === null, 'Bao gia: khong nhac (bao gia khong in QR la dung)');
ok($nhac($hKhong) === null, 'Hoa don 0d: khong nhac (khong co gi de chuyen)');
$nCu = $nhac($hCu);
ok($nCu !== null && mb_strpos($nCu, 'chọn lại') !== false,
   'Du lieu cu (ngan hang go tay): nhac CHON LAI ngan hang tu danh sach', var_export($nCu, true));

ok(empty($canhBao), 'In 5 kieu hoa don khong phat ra canh bao PHP nao', implode(' | ', $canhBao));

// ===========================================================================
section('HTTP that — gara tu khai o man Cau hinh, in hoa don ra QR');

if (!function_exists('curl_init')){ echo "\n[SKIP] PHP khong co curl.\n"; exit(summary()); }

$base = web_goc();
$http = function($method, $url, $jar, $data = null){
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 30]);
    if ($method === 'POST'){ curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query((array) $data)); }
    $raw  = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    $body = $raw === false ? '' : substr($raw, (int) $info['header_size']);
    return ['code' => (int) $info['http_code'], 'loc' => (string) $info['redirect_url'], 'body' => $body,
            'text' => html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8')];
};
$token = function($html){ return preg_match('~name="_token"[^>]*value="([^"]+)"~', $html, $m) ? $m[1] : ''; };
$jars = [];
register_shutdown_function(function() use (&$jars){ foreach ($jars as $j) @unlink($j); });
$dangNhap = function($email) use ($http, $token, $base, &$jars){
    $jar = tempnam(sys_get_temp_dir(), 'zzqr');
    $jars[] = $jar;
    $r = $http('GET', "$base/dang-nhap", $jar);
    if ($r['code'] === 0) return null;
    $http('POST', "$base/dang-nhap", $jar, ['_token' => $token($r['body']), 'email' => $email, 'password' => 'Gara@2026']);
    return $jar;
};

/* Doc moi o cua form Cau hinh nhu trinh duyet se gui — de bam Luu y nhu nguoi
   dung bam, khong gui thieu o nao (o vang mat la bi ghi thanh rong). */
$oCuaForm = function($html){
    $f = [];
    if (!preg_match('~<form[^>]*settings/save.*?</form>~s', $html, $m)) return $f;
    $form = $m[0];
    preg_match_all('~<input[^>]*>~i', $form, $ins);
    foreach ($ins[0] as $tag){
        if (!preg_match('~name="([^"]+)"~', $tag, $n)) continue;
        if (preg_match('~type="(file|submit|button)"~i', $tag)) continue;
        if (preg_match('~type="(checkbox|radio)"~i', $tag) && !preg_match('~\schecked~i', $tag)) continue;
        $f[$n[1]] = preg_match('~value="([^"]*)"~', $tag, $v) ? html_entity_decode($v[1], ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
    }
    preg_match_all('~<select[^>]*name="([^"]+)"[^>]*>(.*?)</select>~is', $form, $ss, PREG_SET_ORDER);
    foreach ($ss as $s){
        $f[$s[1]] = preg_match('~<option[^>]*value="([^"]*)"[^>]*\sselected~i', $s[2], $o) ? $o[1]
                  : (preg_match('~<option[^>]*value="([^"]*)"~i', $s[2], $o) ? $o[1] : '');
    }
    preg_match_all('~<textarea[^>]*name="([^"]+)"[^>]*>(.*?)</textarea>~is', $form, $ts, PREG_SET_ORDER);
    foreach ($ts as $tx) $f[$tx[1]] = html_entity_decode($tx[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return $f;
};

$G2 = $so("SELECT garage_id FROM users WHERE email = 'chugara.sg@gara-mau.test'");
$G3 = $so("SELECT garage_id FROM users WHERE email = 'chugara.dn@gara-mau.test'");
if (!$G2 || !$G3 || $G2 === $G3){ echo "\n[SKIP] Thieu tai khoan thu chugara.sg / chugara.dn tren may local.\n"; exit(summary()); }

$jar2 = $dangNhap('chugara.sg@gara-mau.test');
if ($jar2 === null){ echo "\n[SKIP] Apache khong chay (localhost:88).\n"; exit(summary()); }
$jar3 = $dangNhap('chugara.dn@gara-mau.test');

/* Chup cau hinh ngan hang that cua gara 2, gara 3 va Cau hinh chung — tra lai
   y nguyen khi xong, ke ca khi test chet giua chung. */
$chup = function() use ($pdo, $G2, $G3){
    return [
        'gara' => $pdo->query("SELECT garage_id, skey, svalue, update_at FROM garage_settings
                                WHERE garage_id IN ($G2, $G3) AND skey LIKE 'bank%'")->fetchAll(PDO::FETCH_ASSOC),
        'chung' => $pdo->query("SELECT skey, svalue FROM site_settings WHERE skey LIKE 'bank%'")->fetchAll(PDO::FETCH_KEY_PAIR),
    ];
};
$truoc = $chup();
$traLai = function() use ($pdo, $G2, $G3, $truoc){
    try {
        $pdo->exec("DELETE FROM garage_settings WHERE garage_id IN ($G2, $G3) AND skey LIKE 'bank%'");
        $st = $pdo->prepare("INSERT INTO garage_settings (garage_id, skey, svalue, update_at) VALUES (?, ?, ?, ?)");
        foreach ($truoc['gara'] as $r) $st->execute([$r['garage_id'], $r['skey'], $r['svalue'], $r['update_at']]);
        $pdo->exec("DELETE FROM site_settings WHERE skey LIKE 'bank%'");
        $st = $pdo->prepare("INSERT INTO site_settings (skey, svalue) VALUES (?, ?)");
        foreach ($truoc['chung'] as $k => $v) $st->execute([$k, $v]);
        $pdo->exec("DELETE FROM sales_invoices WHERE invoice_no LIKE 'ZZQR-%'");
    } catch (\Throwable $e){ echo "  (tra lai cau hinh loi: " . $e->getMessage() . ")\n"; }
};
register_shutdown_function($traLai);
$pdo->exec("DELETE FROM sales_invoices WHERE invoice_no LIKE 'ZZQR-%'");

/* Tan Phat (gara tong) da chon Vietcombank. Gara 2 cung dung Vietcombank —
   chuyen rat thuong. Ban cu cua Settings chi luu o "khac mac dinh chung", nen
   gara chon CUNG ngan hang voi Tan Phat la o do bi bo di — va hoa don cua gara
   mat ma QR ma khong ai biet vi sao. */
$pdo->prepare("REPLACE INTO site_settings (skey, svalue) VALUES ('bank_bin', '970436')")->execute();
$stkTP = (string) $pdo->query("SELECT svalue FROM site_settings WHERE skey = 'bank_account'")->fetchColumn();

$r = $http('GET', "$base/admin/settings", $jar2);
ok($r['code'] === 200 && $token($r['body']) !== '', 'Chu gara 2 mo duoc man Cau hinh', 'HTTP ' . $r['code'] . ' ' . $r['loc']);
ok(preg_match('~<select[^>]*name="bank_bin"~', $r['body']) === 1, 'Man Cau hinh cua gara co o CHON ngan hang');
ok(strpos($r['body'], 'value="970436"') !== false && strpos($r['body'], 'value="970415"') !== false,
   'O chon co Vietcombank (970436), VietinBank (970415)...');
ok($stkTP === '' || strpos($r['text'], $stkTP) === false, 'Man Cau hinh cua gara 2 KHONG hien so TK cua Tan Phat');

$f = $oCuaForm($r['body']);
$stk2 = '0071' . random_int(10000000, 99999999);
$f['_token']       = $token($r['body']);
$f['bank_bin']     = '970436';
$f['bank_account'] = $stk2;
$f['bank_holder']  = 'ZZQR GARA MAU SAI GON';
$f['bank_name']    = 'Ngan hang go tay ZZ';       // co gui len cung khong duoc nhan
$http('POST', "$base/admin/settings/save", $jar2, $f);

$docG = function($g, $k) use ($pdo){
    $st = $pdo->prepare("SELECT svalue FROM garage_settings WHERE garage_id = ? AND skey = ?");
    $st->execute([$g, $k]);
    $v = $st->fetchColumn();
    return $v === false ? null : $v;
};
ok($docG($G2, 'bank_bin') === '970436',
   'Gara 2 chon CUNG ngan hang voi Tan Phat: van luu duoc (khong bi coi la "giong mac dinh" roi bo)',
   var_export($docG($G2, 'bank_bin'), true));
ok($docG($G2, 'bank_account') === $stk2 && $docG($G2, 'bank_holder') === 'ZZQR GARA MAU SAI GON',
   'Gara 2: luu dung so TK + chu TK');
ok($docG($G2, 'bank_name') === 'Vietcombank', 'Ten ngan hang lay theo o chon, khong nhan chu go tay gui len',
   var_export($docG($G2, 'bank_name'), true));
ok($docG($G3, 'bank_account') === null || $docG($G3, 'bank_account') === '', 'Gara 3 khong bi ghi gi');
ok((string) $pdo->query("SELECT svalue FROM site_settings WHERE skey = 'bank_account'")->fetchColumn() === $stkTP,
   'Cau hinh chung (Tan Phat) khong bi doi');

/* BIN khong co trong danh sach (sua tay form) -> khong luu. */
$r = $http('GET', "$base/admin/settings", $jar2);
$f = $oCuaForm($r['body']);
$f['_token'] = $token($r['body']);
$f['bank_bin'] = '123456';
$http('POST', "$base/admin/settings/save", $jar2, $f);
ok($docG($G2, 'bank_bin') === '970436', 'Gui BIN la (123456) -> tu choi, giu ngan hang cu');

/* Gara 3 mo man Cau hinh: khong thay gi cua gara 2. */
$r3 = $http('GET', "$base/admin/settings", $jar3);
ok($r3['code'] === 200, 'Chu gara 3 mo duoc man Cau hinh');
ok(strpos($r3['text'], $stk2) === false && strpos($r3['text'], 'ZZQR GARA MAU SAI GON') === false,
   'Man Cau hinh gara 3 KHONG thay so TK / chu TK cua gara 2');

// ---------------------------------------------------------------------------
$taoHd = function($g, $so, $tong) use ($pdo){
    $pdo->prepare("INSERT INTO sales_invoices (invoice_no, invoice_date, vat_rate, subtotal, tax_amount, total_amount,
                   status, garage_id, create_at) VALUES (?, CURDATE(), 0, ?, 0, ?, 0, ?, NOW())")
        ->execute([$so, $tong, $tong, $g]);
    return (int) $pdo->lastInsertId();
};
$hd2 = $taoHd($G2, 'ZZQR-0002', 1954800);
$hd3 = $taoHd($G3, 'ZZQR-0003', 378000);

$r = $http('GET', "$base/admin/sales-invoices/print/$hd2", $jar2);
ok($r['code'] === 200, 'Gara 2 mo duoc trang in hoa don cua minh', 'HTTP ' . $r['code']);
$q2 = $layQr($r['body']);
$t2 = $tlv($q2);
ok($q2 !== null && $crcDung($q2), 'Trang in hoa don gara 2 co ma QR, CRC dung', var_export($q2, true));
ok(is_array($t2) && $t2['54'] === '1954800', 'QR hoa don gara 2: so tien = tong hoa don (1.954.800)');
$t62 = is_array($t2) ? $tlv($t2['62']) : null;
ok(is_array($t62) && $t62['08'] === 'ZZQR-0002', 'QR hoa don gara 2: noi dung = so hoa don');
$t38 = is_array($t2) ? $tlv($t2['38']) : null;
$t3801 = is_array($t38) ? $tlv($t38['01']) : null;
ok(is_array($t3801) && $t3801['00'] === '970436' && $t3801['01'] === $stk2, 'QR hoa don gara 2: vao tai khoan cua gara 2');
ok(strpos($r['body'], 'qrcode-generator/1.4.4/qrcode.min.js') !== false, 'Trang in nap thu vien ve QR (cdnjs, 1.4.4)');
foreach (['Vietcombank', $stk2, 'ZZQR GARA MAU SAI GON', '1.954.800', 'ZZQR-0002'] as $chu){
    ok(mb_strpos($r['text'], $chu) !== false, "Canh QR co ghi: $chu");
}

$w = $http('GET', "$base/admin/sales-invoices/print/$hd2?word=1", $jar2);
ok($w['code'] === 200 && strpos($w['body'], '<script') === false && strpos($w['text'], $stk2) !== false,
   'Ban Word (khong chay duoc JS): khong co script, van co chu so TK de chuyen khoan');

$r = $http('GET', "$base/admin/sales-invoices/print/$hd3", $jar3);
ok($r['code'] === 200, 'Gara 3 mo duoc trang in hoa don cua minh', 'HTTP ' . $r['code']);
ok($layQr($r['body']) === null, 'Gara 3 chua khai tai khoan: hoa don KHONG co ma QR');
ok(strpos($r['text'], $stk2) === false && ($stkTP === '' || strpos($r['text'], $stkTP) === false),
   'Hoa don gara 3 khong in so TK cua gara 2, cung khong in cua Tan Phat');
ok(!preg_match('~(Warning|Notice|Fatal error|Deprecated)\b~', $r['body']), 'Hoa don gara 3 khong co loi PHP');
$n3 = $nhac($r['body']);
ok($n3 !== null && mb_strpos($n3, 'Chưa có mã QR chuyển khoản') !== false,
   'Hoa don gara 3 (chua khai): trang in nhac vi sao khong co QR', var_export($n3, true));
ok(preg_match('~<div class="nhac-qr"[^>]*>.*?href="[^"]*/admin/settings"~s', $r['body']) === 1,
   'Chu gara co quyen Cau hinh: loi nhac kem link mo man Cau hinh');
ok($nhac($http('GET', "$base/admin/sales-invoices/print/$hd2", $jar2)['body']) === null,
   'Hoa don gara 2 (da co QR): khong nhac');
ok($nhac($w['body']) === null, 'Ban Word: khong co loi nhac');

$r = $http('GET', "$base/admin/sales-invoices/print/$hd2", $jar3);
ok(strpos($r['text'], $stk2) === false && $layQr($r['body']) === null,
   'Gara 3 go thang dia chi in hoa don cua gara 2: khong thay ma QR / so TK cua gara 2', 'HTTP ' . $r['code']);

exit(summary());

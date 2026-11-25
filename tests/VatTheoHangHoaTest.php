<?php
/**
 * VAT NHẬP / VAT XUẤT THEO HÀNG HOÁ — khách yêu cầu 25/11/2026:
 *
 *   "thêm vào hàng hóa 2 trường (VAT nhập, VAT xuất) dùng cho các phiếu mua
 *    hàng và bán hàng (các phiếu thêm trường này)"
 *
 * Chạy:  C:\xampp\php\php.exe tests\VatTheoHangHoaTest.php
 *
 * Trước đây thuế suất chỉ có MỘT con số ở đầu phiếu, nhân với cả tổng. Một
 * phiếu có dầu nhớt 10% và dịch vụ 8% thì không lập đúng được. Nay:
 *
 *   - Hàng hoá có `vat_in` (đơn mua) + `vat_out` (báo giá, hoá đơn). NULL =
 *     "theo phiếu" — dùng thuế suất mặc định đầu phiếu.
 *   - Mỗi DÒNG của đơn mua / báo giá / hoá đơn mang `vat_rate` + `tax_amount`.
 *   - Thuế đầu phiếu = TỔNG thuế các dòng, máy chủ tự tính lại từ dòng gửi lên.
 *   - Thuế suất chỉ CHỌN từ 0 / 5 / 8 / 10 (luật "chọn, không gõ tay") — gửi số
 *     khác lên thì máy chủ không nhận.
 *
 * Bất biến giữ nguyên từ trước: VAT đầu vào KHÔNG bao giờ chảy vào giá vốn
 * (xem VatMuaHangVaGiaWebTest) — phiếu nhập kho sinh từ đơn mua vẫn không có thuế.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use App\core\Load;

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ===========================================================================
section('Migration 000117');

$mg = glob($goc . 'database/migrations/*_000117_*.php');
ok(count($mg) === 1 && strpos($mg[0], 'vat_theo_hang_hoa') !== false,
   'Co dung mot migration 000117_vat_theo_hang_hoa', 'Tim thay ' . count($mg));
$mgSrc = count($mg) === 1 ? file_get_contents($mg[0]) : '';
foreach (['`vat_in`', '`vat_out`', '`vat_rate`', '`tax_amount`', 'purchase_order_items',
          'quotation_items', 'sales_invoice_items'] as $chu){
    ok(strpos($mgSrc, $chu) !== false, "Migration nhac toi $chu");
}
ok(substr_count($mgSrc, 'hasColumn') >= 3, 'Kiem cot truoc khi them — chay lai duoc');
ok(preg_match('~vat_in`\s+DECIMAL\(5,\s*2\)\s+NULL~i', $mgSrc) === 1,
   '`parts`.`vat_in` cho phep NULL (= theo phieu)');
ok(preg_match('~vat_rate`\s+DECIMAL\(5,\s*2\)\s+NOT NULL DEFAULT 0~i', $mgSrc) === 1,
   'Cot vat_rate cua dong NOT NULL DEFAULT 0 — code cu ghi dong van chay');
ok(mb_strpos($mgSrc, 'Chạy tay trong phpMyAdmin') !== false, 'Co khoi SQL chay tay trong phpMyAdmin');
ok(stripos($mgSrc, 'UPDATE') !== false, 'Co buoc dien VAT cho dong cua phieu cu');
ok(preg_match('~UPDATE\s+`?(quotations|sales_invoices|purchase_orders)`?\s+SET~i', $mgSrc) === 0,
   'KHONG sua tong tien dau phieu cua chung tu cu');

// ===========================================================================
section('Helper: muc thue suat duoc chon');

ok(function_exists('muc_vat') && muc_vat() === [0, 5, 8, 10], 'muc_vat() = 0 / 5 / 8 / 10');
ok(function_exists('vat_hop_le'), 'Co vat_hop_le()');
if (function_exists('vat_hop_le')){
    ok(vat_hop_le('10') === 10.0 && vat_hop_le('8.00') === 8.0 && vat_hop_le(5) === 5.0 && vat_hop_le('0') === 0.0,
       'vat_hop_le() nhan 10 / 8.00 / 5 / 0');
    ok(vat_hop_le('7') === null && vat_hop_le('abc') === null && vat_hop_le('') === null
       && vat_hop_le(null) === null && vat_hop_le('-10') === null && vat_hop_le(['10']) === null,
       'vat_hop_le() tu choi 7 / abc / rong / null / am / mang');
}
ok(function_exists('vat_cho_dong'), 'Co vat_cho_dong()');
if (function_exists('vat_cho_dong')){
    ok(vat_cho_dong('8', 5, 10) === 8.0, 'Dong gui 8 hop le -> 8 (nguoi dung chon de)');
    ok(vat_cho_dong('7', 5, 10) === 5.0, 'Dong gui 7 (khong hop le) -> VAT rieng cua hang (5)');
    ok(vat_cho_dong(null, null, 10) === 10.0, 'Khong gui, hang khong co VAT rieng -> mac dinh phieu (10)');
    ok(vat_cho_dong('', '8.00', 0) === 8.0, 'Gui rong -> VAT rieng cua hang');
}
ok(function_exists('thue_dong') && thue_dong(12345, 8) == 988 && thue_dong(150000, 10) == 15000,
   'thue_dong() = round(thanh tien x thue suat / 100)');
if (function_exists('o_chon_vat')){
    $h = o_chon_vat('vat_in', null, ['theo_phieu' => true]);
    ok(strpos($h, '<select name="vat_in"') !== false && mb_strpos($h, '— Theo phiếu —') !== false,
       'o_chon_vat(): o chon co lua chon "— Theo phieu —"');
    ok(preg_match('~<option value=""[^>]*selected~', $h) === 1, 'NULL -> chon san "Theo phieu"');
    ok(substr_count($h, '<option') === 5, 'Theo phieu + 4 muc thue suat');
    ok(mb_strpos($h, '0% (không chịu thuế)') !== false, 'Muc 0 ghi ro "0% (khong chiu thue)"');
    $h2 = o_chon_vat('vat_rate', '8.00', ['id' => 'vat_rate']);
    ok(preg_match('~<option value="8"\s+selected~', $h2) === 1 && substr_count($h2, '<option') === 4,
       'Thue suat 8.00 da luu -> chon san 8; khong co "Theo phieu"');
    $h3 = o_chon_vat('x"><script>', '10', ['id' => '"><b>']);
    ok(strpos($h3, '<script>') === false && strpos($h3, '"><b>') === false, 'Ten / id o chon duoc escape');
} else {
    ok(false, 'Co ham o_chon_vat()');
}

// ===========================================================================
section('Ma nguon: o chon, khong o go tay');

$vHang = [
    'app/views/admin/products/add.php', 'app/views/admin/products/edit.php',
    'app/views/admin/services/add.php', 'app/views/admin/services/edit.php',
];
foreach ($vHang as $v){
    $s = file_get_contents($goc . $v);
    ok(preg_match("~o_chon_vat\(\s*'vat_in'~", $s) === 1 && preg_match("~o_chon_vat\(\s*'vat_out'~", $s) === 1,
       "$v: hai o chon VAT nhap / VAT xuat");
    ok(mb_strpos($s, 'VAT nhập') !== false && mb_strpos($s, 'VAT xuất') !== false, "$v: nhan VAT nhap / VAT xuat");
}
foreach (['Products', 'Services'] as $ctl){
    $c = codeOnly($goc . "app/controllers/admin/$ctl.php");
    ok(preg_match("~'vat_in'\s*=>.*?vat_hop_le~s", $c) === 1 && preg_match("~'vat_out'\s*=>.*?vat_hop_le~s", $c) === 1,
       "$ctl luu vat_in / vat_out qua vat_hop_le() (ngoai danh sach -> NULL)");
}

$vPhieu = [
    'app/views/admin/purchase-orders/add.php', 'app/views/admin/purchase-orders/edit.php',
    'app/views/admin/quotations/add.php',      'app/views/admin/quotations/edit.php',
    'app/views/admin/sales-invoices/add.php',  'app/views/admin/sales-invoices/edit.php',
];
foreach ($vPhieu as $v){
    $s = file_get_contents($goc . $v);
    ok(preg_match('~<input[^>]*name="vat_rate"~', $s) === 0, "$v: KHONG con o go tay thue suat");
    ok(preg_match("~o_chon_vat\(\s*'vat_rate'~", $s) === 1, "$v: thue suat dau phieu la o CHON");
    ok(mb_strpos($s, 'VAT mặc định cho dòng') !== false, "$v: nhan \"VAT mac dinh cho dong\"");
    ok(preg_match("~vat\[\]~", $s) === 1, "$v: moi dong co o vat[]");
    ok(strpos($s, 'MUC_VAT') !== false, "$v: JS lay danh sach thue suat tu muc_vat()");
    ok(preg_match('~data-vat~', $s) === 1, "$v: o chon hang mang VAT rieng cua hang (data-vat)");
    ok(preg_match('~>VAT %<~', $s) === 1, "$v: cot \"VAT %\" o dau bang");
}

$cPo = codeOnly($goc . 'app/controllers/admin/Purchaseorders.php');
$viTao = strpos($cPo, 'function taoPhieuNhap');
$than  = $viTao !== false ? substr($cPo, $viTao, 3000) : '';
ok($than !== '' && strpos($than, 'vat_rate') === false && strpos($than, 'tax_amount') === false,
   'taoPhieuNhap() van KHONG dung toi vat_rate / tax_amount — VAT khong vao gia von');

// ===========================================================================
section('CSDL');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}
$cot = function($bang) use ($pdo){
    $ra = [];
    foreach ($pdo->query("SHOW COLUMNS FROM `$bang`") as $r) $ra[$r['Field']] = $r;
    return $ra;
};
$cp = $cot('parts');
ok(isset($cp['vat_in']) && $cp['vat_in']['Null'] === 'YES' && stripos($cp['vat_in']['Type'], 'decimal(5,2)') === 0,
   'parts.vat_in DECIMAL(5,2) NULL');
ok(isset($cp['vat_out']) && $cp['vat_out']['Null'] === 'YES', 'parts.vat_out NULL');
foreach (['purchase_order_items', 'quotation_items', 'sales_invoice_items'] as $b){
    $c = $cot($b);
    ok(isset($c['vat_rate']) && $c['vat_rate']['Null'] === 'NO' && (float) $c['vat_rate']['Default'] === 0.0,
       "$b.vat_rate NOT NULL DEFAULT 0");
    ok(isset($c['tax_amount']) && $c['tax_amount']['Null'] === 'NO', "$b.tax_amount NOT NULL");
}
if (!isset($cp['vat_in'])){ echo "\n[DUNG] Chua chay migration 000117.\n"; exit(summary()); }

$so  = function($sql, $p = []) use ($pdo){ $st = $pdo->prepare($sql); $st->execute($p); return $st->fetchColumn(); };
$mot = function($sql, $p = []) use ($pdo){ $st = $pdo->prepare($sql); $st->execute($p); return $st->fetch(PDO::FETCH_ASSOC); };
$nhieu = function($sql, $p = []) use ($pdo){ $st = $pdo->prepare($sql); $st->execute($p); return $st->fetchAll(PDO::FETCH_ASSOC); };
$ins = function($bang, array $d) use ($pdo){
    $k = array_keys($d);
    $pdo->prepare("INSERT INTO `$bang` (`" . implode('`,`', $k) . "`) VALUES (" . rtrim(str_repeat('?,', count($k)), ',') . ")")
        ->execute(array_values($d));
    return (int) $pdo->lastInsertId();
};

$G1   = (int) $so("SELECT garage_id FROM users WHERE email = 'admin@gara-mau.test'");
$bay  = date('Y-m-d H:i:s');
$hom  = date('Y-m-d');
$DAU  = 'ZZVAT-';

/* Dọn sót của lần chạy trước (chết giữa chừng) */
$donSach = function() use ($pdo, $DAU){
    foreach ($pdo->query("SELECT id FROM sales_invoices WHERE note LIKE 'ZZVAT%' OR note LIKE '%ZZVAT-BG%' OR customer_name LIKE 'ZZVAT%'")
                 ->fetchAll(PDO::FETCH_COLUMN) as $id){
        $pdo->prepare('DELETE FROM sales_invoice_items WHERE invoice_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM sales_invoices WHERE id = ?')->execute([$id]);
    }
    foreach ($pdo->query("SELECT id FROM quotations WHERE note LIKE 'ZZVAT%' OR customer_name LIKE 'ZZVAT%'")->fetchAll(PDO::FETCH_COLUMN) as $id){
        $pdo->prepare('DELETE FROM sales_invoices WHERE quotation_id = ? AND status = 0')->execute([$id]);
        $pdo->prepare('DELETE FROM quotation_items WHERE quotation_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM quotations WHERE id = ?')->execute([$id]);
    }
    foreach ($pdo->query("SELECT id FROM purchase_orders WHERE note LIKE 'ZZVAT%'")->fetchAll(PDO::FETCH_COLUMN) as $id){
        $pdo->prepare('DELETE FROM purchase_order_items WHERE po_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM purchase_orders WHERE id = ?')->execute([$id]);
    }
    $pdo->exec("DELETE FROM partners WHERE code LIKE 'ZZVAT-%'");
    $pdo->exec("DELETE FROM parts WHERE code LIKE 'ZZVAT-%'");
};
$donSach();

/* Bốn mặt hàng kho tổng: hai hàng hoá, hai dịch vụ; một nửa có VAT riêng */
$P1 = $ins('parts', ['code' => $DAU . 'P1', 'name' => 'ZZVAT Dau nhot', 'slug' => 'zzvat-dau-nhot', 'item_type' => 'part',
                     'price' => 100000, 'status' => 1, 'show_on_web' => 0, 'vat_in' => 5, 'vat_out' => 8, 'create_at' => $bay]);
$P2 = $ins('parts', ['code' => $DAU . 'P2', 'name' => 'ZZVAT Loc gio', 'slug' => 'zzvat-loc-gio', 'item_type' => 'part',
                     'price' => 50000, 'status' => 1, 'show_on_web' => 0, 'create_at' => $bay]);
$S1 = $ins('parts', ['code' => $DAU . 'S1', 'name' => 'ZZVAT Cong thay dau', 'slug' => 'zzvat-cong-thay-dau', 'item_type' => 'service',
                     'price' => 30000, 'status' => 1, 'show_on_web' => 0, 'vat_out' => 8, 'create_at' => $bay]);
$S2 = $ins('parts', ['code' => $DAU . 'S2', 'name' => 'ZZVAT Rua xe', 'slug' => 'zzvat-rua-xe', 'item_type' => 'service',
                     'price' => 45000, 'status' => 1, 'show_on_web' => 0, 'create_at' => $bay]);
$NCC = $ins('partners', ['code' => $DAU . 'NCC', 'name' => 'ZZVAT Nha cung cap', 'type' => 'supplier', 'status' => 1,
                         'sort_order' => 0, 'garage_id' => $G1, 'create_at' => $bay]);

try {

// ===========================================================================
section('Phieu cu: dien VAT cho dong (migration chay lai) + doc an toan');

/* Phiếu CŨ: đầu phiếu 10%, thuế 15.000 tính trên tổng; dòng chưa biết VAT */
$bgCu = $ins('quotations', ['quote_no' => $DAU . 'BGCU', 'quote_date' => $hom, 'vat_rate' => 10, 'subtotal' => 150000,
                            'tax_amount' => 15000, 'total_amount' => 165000, 'status' => 'draft', 'note' => 'ZZVAT cu',
                            'garage_id' => $G1, 'create_at' => $bay]);
$ins('quotation_items', ['quotation_id' => $bgCu, 'part_id' => $P2, 'quantity' => 2, 'unit_price' => 50000, 'amount' => 100000]);
$ins('quotation_items', ['quotation_id' => $bgCu, 'part_id' => $S2, 'quantity' => 1, 'unit_price' => 50000, 'amount' => 50000]);
/* Phiếu MỚI: mặc định 10% nhưng người dùng chọn 0% cho mọi dòng — thuế 0 */
$bgMoi = $ins('quotations', ['quote_no' => $DAU . 'BGMOI', 'quote_date' => $hom, 'vat_rate' => 10, 'subtotal' => 100000,
                             'tax_amount' => 0, 'total_amount' => 100000, 'status' => 'draft', 'note' => 'ZZVAT moi',
                             'garage_id' => $G1, 'create_at' => $bay]);
$ins('quotation_items', ['quotation_id' => $bgMoi, 'part_id' => $P2, 'quantity' => 2, 'unit_price' => 50000, 'amount' => 100000,
                         'vat_rate' => 0, 'tax_amount' => 0]);

/* Đọc phiếu cũ TRƯỚC khi migration chạy lại (phiếu lập trong khoảng giữa
   chạy migration và đẩy code): dòng phải mang VAT đầu phiếu, không phải 0 */
$dongCu = Load::model('QuotationItemsModel')->getByQuotation($bgCu);
ok(count($dongCu) === 2 && (float) $dongCu[0]['vat_rate'] === 10.0 && (float) $dongCu[0]['tax_amount'] === 10000.0
   && (float) $dongCu[1]['tax_amount'] === 5000.0,
   'Doc dong phieu cu: VAT lay theo dau phieu (10%), thue 10.000 + 5.000',
   json_encode(array_map(function($d){ return [$d['vat_rate'], $d['tax_amount']]; }, $dongCu)));
$dongMoi = Load::model('QuotationItemsModel')->getByQuotation($bgMoi);
ok(count($dongMoi) === 1 && (float) $dongMoi[0]['vat_rate'] === 0.0,
   'Phieu moi co dong 0% (thue dau phieu 0): KHONG bi doi thanh 10%');

$m = require $mg[0];
$m->setDb(new \App\core\Database());
ob_start(); $m->up(); ob_end_clean();
ok($nhieu("SELECT vat_rate, tax_amount FROM quotation_items WHERE quotation_id = ? ORDER BY id", [$bgCu])
   == [['vat_rate' => '10.00', 'tax_amount' => '10000.00'], ['vat_rate' => '10.00', 'tax_amount' => '5000.00']],
   'Migration chay lai: dien VAT 10% + thue tung dong cho phieu cu');
ok($mot("SELECT subtotal, tax_amount, total_amount FROM quotations WHERE id = ?", [$bgCu])
   == ['subtotal' => '150000.00', 'tax_amount' => '15000.00', 'total_amount' => '165000.00'],
   'Tong tien dau phieu cu KHONG doi');
ok($mot("SELECT vat_rate, tax_amount FROM quotation_items WHERE quotation_id = ?", [$bgMoi])
   == ['vat_rate' => '0.00', 'tax_amount' => '0.00'],
   'Migration chay lai KHONG dung toi phieu moi (dong 0% co chu y)');

/* Xoá NGAY hai phiếu dựng tay: số báo giá kế tiếp đọc từ phiếu id lớn nhất của
   gara (QuotationsModel::nextNo), "ZZVAT-BGMOI" không có đuôi số -> phiếu lập
   qua HTTP bên dưới sẽ ra BG-000001 và trùng khoá. */
foreach ([$bgCu, $bgMoi] as $__id){
    $pdo->prepare('DELETE FROM quotation_items WHERE quotation_id = ?')->execute([$__id]);
    $pdo->prepare('DELETE FROM quotations WHERE id = ?')->execute([$__id]);
}

// ===========================================================================
section('In: mot muc thue / nhieu muc thue');

Load::model('PartsModel');   // mẫu in đọc PartsModel::LOAI_DICH_VU
$inThu =function(array $dong, $vat, $thue){
    $cong = 0; foreach ($dong as $d) $cong += $d['amount'];
    ob_start();
    try {
        in_chung_tu(['loai' => 'HOÁ ĐƠN BÁN HÀNG', 'so' => 'ZZVAT-IN', 'ngay' => date('Y-m-d'),
                     'subtotal' => $cong, 'vatRate' => $vat, 'tax' => $thue, 'total' => $cong + $thue,
                     'hienNganHang' => false, 'nhanKy' => ['A', 'B']],
                    ['name' => 'Khach ZZVAT'], $dong,
                    ['site_name' => 'Gara ZZ', 'address' => '', 'hotline' => '', 'email' => '', 'tax_code' => ''], '', false);
    } finally { $html = ob_get_clean(); }
    return $html;
};
$dongIn = function($ten, $tien, $vat){
    return ['part_code' => 'ZZ', 'part_name' => $ten, 'note' => '', 'unit_name' => '', 'quantity' => 1,
            'unit_price' => $tien, 'discount_percent' => 0, 'amount' => $tien, 'item_type' => 'part',
            'vat_rate' => $vat, 'tax_amount' => round($tien * $vat / 100)];
};
$kiemCot = function($html){
    preg_match('~<table class="bang-hang">.*?<thead>(.*?)</thead>~s', $html, $h);
    $soCot = isset($h[1]) ? substr_count($h[1], '<th') : 0;
    preg_match('~<table class="bang-hang">(.*?)</table>~s', $html, $b);
    $sai = [];
    foreach (preg_split('~</tr>~', isset($b[1]) ? $b[1] : '') as $tr){
        if (strpos($tr, '<td') === false) continue;
        $o = 0;
        preg_match_all('~<td([^>]*)>~', $tr, $tds);
        foreach ($tds[1] as $a) $o += preg_match('~colspan="(\d+)"~', $a, $c) ? (int) $c[1] : 1;
        if ($o !== $soCot) $sai[] = $o;
    }
    return $sai;
};

$hDeu = $inThu([$dongIn('A', 100000, 8), $dongIn('B', 50000, 8)], 10, 12000);
ok(mb_strpos($hDeu, 'Thuế GTGT (8%)') !== false && mb_strpos($hDeu, 'Thuế GTGT (10%)') === false,
   'Moi dong 8% (dau phieu mac dinh 10%): in "Thue GTGT (8%)" — muc THAT cua dong');
$hTron = $inThu([$dongIn('A', 100000, 10), $dongIn('B', 50000, 8), $dongIn('C', 20000, 10)], 10, 16000);
ok(mb_strpos($hTron, 'Thuế GTGT 10%') !== false && mb_strpos($hTron, 'Thuế GTGT 8%') !== false,
   'Dong tron 10% + 8%: in moi muc mot dong');
ok(preg_match('~Thuế GTGT 10%</td><td class="phai">12\.000</td>~u', $hTron) === 1
   && preg_match('~Thuế GTGT 8%</td><td class="phai">4\.000</td>~u', $hTron) === 1,
   'Moi dong thue in dung tien thue cua muc do (10%: 12.000, 8%: 4.000)');
ok(mb_strpos($hTron, 'chưa thuế') !== false, 'Van in "Cong tien hang chua thue"');
ok($kiemCot($hTron) === [] && $kiemCot($hDeu) === [], 'Colspan cac dong thue khop so cot');
$h0 = $inThu([$dongIn('A', 100000, 0)], 0, 0);
ok(mb_strpos($h0, 'Thuế GTGT') === false, 'Thue 0% van KHONG in dong thue thua');
$hKhongCot = $inThu([['part_code' => 'ZZ', 'part_name' => 'X', 'note' => '', 'unit_name' => '', 'quantity' => 1,
                      'unit_price' => 100000, 'discount_percent' => 0, 'amount' => 100000, 'item_type' => 'part']], 10, 10000);
ok(mb_strpos($hKhongCot, 'Thuế GTGT (10%)') !== false, 'Dong khong mang vat_rate (du lieu cu) -> in theo dau phieu');

// ===========================================================================
section('HTTP — dang nhap');

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
    preg_match('~^Location:\s*(.+)$~im', substr((string) $r, 0, $hs), $mm);
    return ['code' => $code, 'loc' => isset($mm[1]) ? trim($mm[1]) : '', 'body' => substr((string) $r, $hs)];
};
$token = function($b){ return preg_match('~name="_token"[^>]*value="([^"]+)"~', $b, $mm) ? $mm[1] : ''; };
$jar = tempnam(sys_get_temp_dir(), 'vat');
$r = $http('GET', "$base/dang-nhap", $jar);
if ($r['code'] === 0){ echo "\n[SKIP] Apache khong chay (localhost:88).\n"; throw new \RuntimeException('skip'); }
$http('POST', "$base/dang-nhap", $jar, ['_token' => $token($r['body']), 'email' => 'admin@gara-mau.test', 'password' => 'Gara@2026']);
$get  = function($u) use ($http, $base, $jar){ return $http('GET', "$base/admin/$u", $jar); };
$post = function($u, array $d, $tuTrang = null) use ($http, $base, $jar, $token){
    $f = $http('GET', "$base/admin/" . ($tuTrang !== null ? $tuTrang : $u), $jar);
    return $http('POST', "$base/admin/$u", $jar, array_merge(['_token' => $token($f['body'])], $d));
};
$idTu = function($loc){ return preg_match('~/edit/(\d+)~', $loc, $mm) ? (int) $mm[1] : 0; };
$r = $get('quotations/add');
ok($r['code'] === 200, 'Dang nhap admin, mo duoc Lap bao gia', 'HTTP ' . $r['code'] . ' ' . $r['loc']);

// ===========================================================================
section('HTTP — hang hoa / dich vu: luu VAT nhap / VAT xuat');

$r = $get('products/add');
ok(preg_match('~<select name="vat_in"~', $r['body']) === 1 && preg_match('~<select name="vat_out"~', $r['body']) === 1,
   'Form Them hang hoa co o chon VAT nhap + VAT xuat');
$r = $post('products/add', ['code' => $DAU . 'P9', 'name' => 'ZZVAT Hang moi', 'item_type' => 'part', 'price' => '120000',
                            'status' => 1, 'vat_in' => '8', 'vat_out' => '7']);
$p9 = (int) $so("SELECT id FROM parts WHERE code = ?", [$DAU . 'P9']);
$hang9 = $mot("SELECT vat_in, vat_out FROM parts WHERE id = ?", [$p9]);
ok($p9 > 0 && $hang9['vat_in'] === '8.00', 'Luu VAT nhap 8%', json_encode($hang9));
ok($p9 > 0 && $hang9['vat_out'] === null, 'VAT xuat gui 7 (khong co trong danh sach) -> NULL (theo phieu)');
if ($p9 > 0){
    $r = $get("products/edit/$p9");
    ok(preg_match('~<select name="vat_in"[^>]*>.*?<option value="8"\s+selected~s', $r['body']) === 1,
       'Form Sua hang hoa chon san VAT nhap 8%');
    $post("products/edit/$p9", ['code' => $DAU . 'P9', 'name' => 'ZZVAT Hang moi', 'item_type' => 'part', 'price' => '120000',
                                'status' => 1, 'vat_in' => '', 'vat_out' => '10']);
    $hang9 = $mot("SELECT vat_in, vat_out FROM parts WHERE id = ?", [$p9]);
    ok($hang9['vat_in'] === null && $hang9['vat_out'] === '10.00', 'Sua: VAT nhap ve "theo phieu", VAT xuat 10%', json_encode($hang9));
}
$r = $get('services/add');
ok(preg_match('~<select name="vat_out"~', $r['body']) === 1, 'Form Them dich vu co o chon VAT xuat');
$post('services/add', ['code' => $DAU . 'S9', 'name' => 'ZZVAT Dich vu moi', 'price' => '90000', 'status' => 1,
                       'vat_in' => '5', 'vat_out' => '8']);
$s9 = $mot("SELECT id, vat_in, vat_out FROM parts WHERE code = ?", [$DAU . 'S9']);
ok(!empty($s9) && $s9['vat_out'] === '8.00' && $s9['vat_in'] === '5.00', 'Dich vu luu VAT xuat 8% / VAT nhap 5%', json_encode($s9));
if (!empty($s9)){
    $post("services/edit/{$s9['id']}", ['code' => $DAU . 'S9', 'name' => 'ZZVAT Dich vu moi', 'price' => '90000', 'status' => 1,
                                        'vat_in' => '', 'vat_out' => '99']);
    $s9b = $mot("SELECT vat_in, vat_out FROM parts WHERE id = ?", [$s9['id']]);
    ok($s9b['vat_in'] === null && $s9b['vat_out'] === null, 'Sua dich vu: 99% bi tu choi -> NULL', json_encode($s9b));
}

// ===========================================================================
section('HTTP — form phieu: o chon VAT dau phieu + du lieu VAT cua hang');

foreach (['quotations/add' => 8, 'sales-invoices/add' => 8, 'purchase-orders/add' => 5] as $u => $vatP1){
    $r = $get($u);
    ok(preg_match('~<select name="vat_rate" id="vat_rate"~', $r['body']) === 1, "$u: thue suat dau phieu la o CHON");
    foreach (['0', '5', '8', '10'] as $v) ok(preg_match('~<option value="' . $v . '"~', $r['body']) === 1, "$u: co muc $v%");
    ok(preg_match('~"id":' . $P1 . ',[^}]*"vat":' . $vatP1 . '(\.0)?[,}]~', $r['body']) === 1,
       "$u: JS biet VAT rieng cua ZZVAT-P1 = $vatP1%");
    ok(preg_match('~"id":' . $P2 . ',[^}]*"vat":null~', $r['body']) === 1, "$u: hang khong co VAT rieng -> null (theo phieu)");
}

// ===========================================================================
section('HTTP — bao gia: thue tinh theo tung dong');

$r = $post('quotations/add', [
    'quote_date' => $hom, 'vat_rate' => '10',
    'line_part' => [$P1, $P2], 'line_qty' => [2, 1], 'line_price' => [100000, 50000], 'line_disc' => [0, 0],
    'line_note' => ['ZZVAT', ''], 'line_vat' => ['8', '7'],
    'sv_part' => [$S1, $S2], 'sv_qty' => [1, 1], 'sv_price' => [30000, 20000], 'sv_disc' => [0, 0], 'sv_note' => ['', ''],
    'sv_vat' => ['', '5'],
]);
$bg = $idTu($r['loc']);
ok($bg > 0, 'Lap duoc bao gia', 'HTTP ' . $r['code'] . ' ' . $r['loc']);
if ($bg > 0) $pdo->prepare("UPDATE quotations SET note = 'ZZVAT-BG' WHERE id = ?")->execute([$bg]);
$dBg = [];
foreach ($nhieu("SELECT part_id, vat_rate, tax_amount, amount FROM quotation_items WHERE quotation_id = ?", [$bg]) as $d) $dBg[(int) $d['part_id']] = $d;
ok(isset($dBg[$P1]) && $dBg[$P1]['vat_rate'] === '8.00' && $dBg[$P1]['tax_amount'] === '16000.00',
   'Dong chon 8%: luu 8%, thue 16.000');
ok(isset($dBg[$P2]) && $dBg[$P2]['vat_rate'] === '10.00' && $dBg[$P2]['tax_amount'] === '5000.00',
   'Dong gui 7% (khong hop le), hang khong VAT rieng -> mac dinh phieu 10%');
ok(isset($dBg[$S1]) && $dBg[$S1]['vat_rate'] === '8.00' && $dBg[$S1]['tax_amount'] === '2400.00',
   'Dong gui rong -> VAT xuat rieng cua dich vu 8%');
ok(isset($dBg[$S2]) && $dBg[$S2]['vat_rate'] === '5.00' && $dBg[$S2]['tax_amount'] === '1000.00', 'Dong chon 5%');
$hBg = $mot("SELECT vat_rate, subtotal, tax_amount, total_amount FROM quotations WHERE id = ?", [$bg]);
ok($hBg == ['vat_rate' => '10.00', 'subtotal' => '300000.00', 'tax_amount' => '24400.00', 'total_amount' => '324400.00'],
   'Dau phieu: mac dinh 10%, thue = TONG thue dong (24.400), khong phai 10% x tong (30.000)', json_encode($hBg));

$r = $post('quotations/add', ['quote_date' => $hom, 'vat_rate' => '12',
    'line_part' => [$P2], 'line_qty' => [1], 'line_price' => [50000], 'line_disc' => [0], 'line_note' => ['ZZVAT']]);
$bg2 = $idTu($r['loc']);
if ($bg2 > 0) $pdo->prepare("UPDATE quotations SET note = 'ZZVAT-BG2' WHERE id = ?")->execute([$bg2]);
$hBg2 = $mot("SELECT vat_rate, tax_amount FROM quotations WHERE id = ?", [$bg2]);
ok($bg2 > 0 && $hBg2['vat_rate'] === '0.00' && $hBg2['tax_amount'] === '0.00',
   'Thue suat dau phieu 12% (gui tay) -> 0; dong khong gui VAT theo mac dinh 0', json_encode($hBg2));

if ($bg > 0){
    $r = $get("quotations/edit/$bg");
    ok(preg_match('~<select name="vat_rate" id="vat_rate"[^>]*>.*?<option value="10"\s+selected~s', $r['body']) === 1,
       'Sua bao gia: o thue mac dinh chon san 10%');
    ok(preg_match('~"part_id":' . $P1 . ',[^}]*"vat":8(\.0)?[,}]~', $r['body']) === 1
       && preg_match('~"part_id":' . $S2 . ',[^}]*"vat":5(\.0)?[,}]~', $r['body']) === 1,
       'Sua bao gia: moi dong mo ra dung VAT da luu (8%, 5%)');

    $j = json_decode($get("quotations/copy-lines/$bg")['body'], true);
    $vatChep = [];
    foreach (array_merge((array) ($j['hang'] ?? []), (array) ($j['dichvu'] ?? [])) as $d) $vatChep[$d['part_id']] = $d['vat'] ?? null;
    ok(isset($vatChep[$P1]) && (float) $vatChep[$P1] === 8.0 && (float) $vatChep[$P2] === 10.0 && (float) $vatChep[$S2] === 5.0,
       'Chep tu bao gia cu: mang theo VAT tung dong', json_encode($vatChep));
    $j = json_decode($get("sales-invoices/copy-lines/$bg?tu=baogia")['body'], true);
    $vatChep = [];
    foreach (array_merge((array) ($j['hang'] ?? []), (array) ($j['dichvu'] ?? [])) as $d) $vatChep[$d['part_id']] = $d['vat'] ?? null;
    ok(isset($vatChep[$S1]) && (float) $vatChep[$S1] === 8.0, 'Hoa don chep tu bao gia: mang theo VAT tung dong', json_encode($vatChep));

    /* Chuyển báo giá -> hoá đơn: VAT từng dòng đi theo */
    $r = $get("quotations/convert/$bg");
    $hdTuBg = $idTu($r['loc']);
    ok($hdTuBg > 0, 'Chuyen bao gia thanh hoa don', $r['loc']);
    $dHd = [];
    foreach ($nhieu("SELECT part_id, vat_rate, tax_amount FROM sales_invoice_items WHERE invoice_id = ?", [$hdTuBg]) as $d) $dHd[(int) $d['part_id']] = $d['vat_rate'];
    ok($dHd == [$P1 => '8.00', $P2 => '10.00', $S1 => '8.00', $S2 => '5.00'], 'Hoa don mang dung VAT tung dong cua bao gia', json_encode($dHd));
    ok($so("SELECT tax_amount FROM sales_invoices WHERE id = ?", [$hdTuBg]) === '24400.00',
       'Thue hoa don = tong thue dong (24.400)');
}

// ===========================================================================
section('HTTP — hoa don: luu, ghi so, in, HDDT');

$r = $post('sales-invoices/add', ['invoice_date' => $hom, 'warehouse_id' => '', 'vat_rate' => '10',
    'sv_part' => [$S1, $S2], 'sv_qty' => [1, 2], 'sv_price' => [100000, 45000], 'sv_disc' => [0, 0],
    'sv_note' => ['ZZVAT', ''], 'sv_vat' => ['8', '10']]);
$hd = $idTu($r['loc']);
ok($hd > 0, 'Lap hoa don (chi dich vu)', 'HTTP ' . $r['code'] . ' ' . $r['loc']);
if ($hd > 0){
    $pdo->prepare("UPDATE sales_invoices SET note = 'ZZVAT-HD' WHERE id = ?")->execute([$hd]);
    $hHd = $mot("SELECT subtotal, tax_amount, total_amount FROM sales_invoices WHERE id = ?", [$hd]);
    ok($hHd == ['subtotal' => '190000.00', 'tax_amount' => '17000.00', 'total_amount' => '207000.00'],
       'Hoa don: thue 8.000 + 9.000 = 17.000', json_encode($hHd));

    $r = $get("sales-invoices/edit/$hd");
    ok(preg_match('~"part_id":' . $S1 . ',[^}]*"vat":8(\.0)?[,}]~', $r['body']) === 1, 'Sua hoa don: dong mo ra dung VAT 8%');

    /* Ghi sổ: thuế ghi nhận (Có 3331, đọc ở báo cáo từ tax_amount) = TỔNG thuế
       dòng. Bản cũ tính lại 10% x tổng = 19.000 ngay lúc ghi sổ. */
    $pdo->prepare("UPDATE sales_invoices SET tax_amount = 0, total_amount = 0 WHERE id = ?")->execute([$hd]);
    $get("sales-invoices/post/$hd");
    $hHd = $mot("SELECT status, subtotal, tax_amount, total_amount FROM sales_invoices WHERE id = ?", [$hd]);
    ok((int) $hHd['status'] === 1 && $hHd['tax_amount'] === '17000.00' && $hHd['total_amount'] === '207000.00',
       'Ghi so: thue GTGT dau ra = tong thue dong (17.000), khong tinh lai 10% x tong', json_encode($hHd));

    $r = $get("sales-invoices/edit/$hd");
    ok(mb_strpos(html_entity_decode($r['body'], ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'nhiều mức') !== false,
       'Hoa don da ghi so, dong tron 8% + 10%: nhan thue ghi "nhieu muc"');

    $r = $get("sales-invoices/print/$hd");
    ok(mb_strpos($r['body'], 'Thuế GTGT 8%') !== false && mb_strpos($r['body'], 'Thuế GTGT 10%') !== false,
       'In hoa don dong tron: moi muc thue mot dong');

    $f = $get("sales-invoices/edit/$hd");
    $http('POST', "$base/admin/sales-invoices/einvoice/$hd", $jar, ['_token' => $token($f['body'])]);
    $xml = $get("sales-invoices/einvoice-xml/$hd")['body'];
    ok(preg_match('~<MHHDVu>ZZVAT-S1</MHHDVu>.*?<TSuat>8</TSuat>.*?</HHDVu>~s', $xml) === 1
       && preg_match('~<MHHDVu>ZZVAT-S2</MHHDVu>.*?<TSuat>10</TSuat>.*?</HHDVu>~s', $xml) === 1,
       'XML HDDT: thue suat theo tung dong', substr($xml, 0, 300));
    ok(strpos($xml, '<TgTThue>17000</TgTThue>') !== false, 'XML HDDT: tong thue = tong thue dong (17000)');
    ok(preg_match('~<LTSuat>\s*<TSuat>8</TSuat>\s*<ThTien>100000</ThTien>\s*<TThue>8000</TThue>~', $xml) === 1,
       'XML HDDT: tong hop theo tung muc thue suat');
}

// ===========================================================================
section('HTTP — don mua: thue theo dong, VAT nhap cua hang');

$r = $post('purchase-orders/add', ['partner_id' => $NCC, 'order_date' => $hom, 'note' => 'ZZVAT-PO', 'vat_rate' => '0',
    'part' => [$P1, $P2], 'qty' => [10, 2], 'price' => [100000, 50000], 'disc' => [10, 0], 'line_note' => ['', ''],
    'vat' => ['', '8']]);
$po = $idTu($r['loc']);
ok($po > 0, 'Lap don mua', 'HTTP ' . $r['code'] . ' ' . $r['loc']);
if ($po > 0){
    $dPo = [];
    foreach ($nhieu("SELECT part_id, amount, vat_rate, tax_amount FROM purchase_order_items WHERE po_id = ?", [$po]) as $d) $dPo[(int) $d['part_id']] = $d;
    ok(isset($dPo[$P1]) && $dPo[$P1]['vat_rate'] === '5.00' && $dPo[$P1]['tax_amount'] === '45000.00',
       'Dong khong chon VAT -> VAT NHAP rieng cua hang (5%) tren tien da tru CK (900.000)', json_encode($dPo[$P1] ?? null));
    ok(isset($dPo[$P2]) && $dPo[$P2]['vat_rate'] === '8.00' && $dPo[$P2]['tax_amount'] === '8000.00', 'Dong chon 8%');
    $hPo = $mot("SELECT vat_rate, subtotal, tax_amount, total_amount FROM purchase_orders WHERE id = ?", [$po]);
    ok($hPo == ['vat_rate' => '0.00', 'subtotal' => '1000000.00', 'tax_amount' => '53000.00', 'total_amount' => '1053000.00'],
       'Dau don: thue = tong thue dong (53.000)', json_encode($hPo));

    $r = $get("purchase-orders/edit/$po");
    ok(preg_match('~"part_id":' . $P2 . ',[^}]*"vat":8(\.0)?[,}]~', $r['body']) === 1, 'Sua don mua: dong mo ra dung VAT 8%');

    /* Sửa: đổi VAT mặc định, gửi lại không kèm VAT dòng -> dòng theo hàng / mặc định mới */
    $post("purchase-orders/edit/$po", ['partner_id' => $NCC, 'order_date' => $hom, 'note' => 'ZZVAT-PO', 'vat_rate' => '10',
        'part' => [$P1, $P2], 'qty' => [10, 2], 'price' => [100000, 50000], 'disc' => [10, 0], 'line_note' => ['', '']],
        "purchase-orders/edit/$po");
    $hPo = $mot("SELECT vat_rate, tax_amount FROM purchase_orders WHERE id = ?", [$po]);
    ok($hPo == ['vat_rate' => '10.00', 'tax_amount' => '55000.00'],
       'Sua don, mac dinh 10%: hang co VAT nhap rieng giu 5% (45.000), hang khong co -> 10% (10.000)', json_encode($hPo));
}

} catch (\RuntimeException $e){
    if ($e->getMessage() !== 'skip') throw $e;
} finally {
    $donSach();
    $pdo->exec("DELETE FROM quotations WHERE quote_no LIKE 'ZZVAT-%'");
}
ok((int) $so("SELECT COUNT(*) FROM parts WHERE code LIKE 'ZZVAT-%'") === 0, 'Da don du lieu thu');

exit(summary());

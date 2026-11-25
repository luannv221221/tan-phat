<?php
/**
 * Test BẢN IN BÁO GIÁ / HOÁ ĐƠN KHÔNG THỪA DÒNG.
 *
 * Chạy:  C:\xampp\php\php.exe tests\InKhongThuaDongTest.php
 *
 * Khách ghi 25/11/2026 ở dòng "Danh sách báo giá": "Các bản in hóa đơn bị lỗi
 * thừa dòng". Mở bản in ra xem thì thấy gốc:
 *
 *   1. CỘT TÊN HÀNG BỊ BÓP. Bảy cột còn lại đặt cứng 134mm + đệm ô, khổ A4 trừ
 *      lề còn 186mm — cột Tên chỉ còn ~26mm. Tên ngắn như "Bugi Iridium NGK"
 *      cũng bẻ hai dòng, mỗi mặt hàng chiếm gấp đôi chỗ, tờ hoá đơn ba món dài
 *      như sáu món.
 *   2. CỘT CK LUÔN IN dù không dòng nào có chiết khấu — 14mm trắng lấy từ cột Tên.
 *   3. THUẾ 0% vẫn in hai dòng "Cộng tiền hàng chưa thuế" + "Thuế GTGT (0%) 0"
 *      lặp lại đúng số tổng.
 *   4. ĐẦU TRANG: gara không khai điện thoại / email thì có một dòng trống giữa
 *      địa chỉ và mã số thuế, hoặc dòng bắt đầu bằng " | Email".
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$mau = file_get_contents(__DIR__ . '/../app/views/admin/print/chung-tu.php');

// ---------------------------------------------------------------------------
section('Cot Ten hang du rong tren kho A4');

/* Cộng bề rộng đặt cứng của các cột (mm) trong khuôn tiêu đề bảng hàng. */
$coDinh = 0;
if (preg_match('~<table class="bang-hang">(.*?)</thead>~s', $mau, $m)
    && preg_match_all('~width:\s*(\d+(?:\.\d+)?)mm~', $m[1], $w)){
    $coDinh = array_sum(array_map('floatval', $w[1]));
}
ok($coDinh > 0 && $coDinh <= 105,
   'Cac cot co dinh cong lai <= 105mm — cot Ten con >= ~60mm tren A4',
   'hien: ' . $coDinh . 'mm');
ok(preg_match('~td\.phai[^{]*\{[^}]*white-space:\s*nowrap~', $mau) === 1,
   'Cot so (don gia, thanh tien) khong be dong');

// ---------------------------------------------------------------------------
\App\core\Load::model('PartsModel');
$canhBao = [];
$inThu = function(array $dong, $vat, array $cf) use (&$canhBao){
    $cong = 0; foreach ($dong as $d) $cong += $d['amount'];
    $thue = round($cong * $vat / 100);
    set_error_handler(function($no, $str, $file, $line) use (&$canhBao){
        $canhBao[] = "$str @ " . basename($file) . ":$line"; return true; });
    ob_start();
    try {
        in_chung_tu(['loai' => 'HOÁ ĐƠN BÁN HÀNG', 'so' => 'ZZIN-1', 'ngay' => date('Y-m-d'),
                     'subtotal' => $cong, 'vatRate' => $vat, 'tax' => $thue, 'total' => $cong + $thue,
                     'hienNganHang' => false, 'nhanKy' => ['NGƯỜI MUA HÀNG', 'ĐẠI DIỆN BÊN BÁN']],
                    ['name' => 'Khach ZZIN'], $dong, $cf, '', false);
    } finally {
        $html = ob_get_clean();
        restore_error_handler();
    }
    return $html;
};
$dongHang = function($ten, $tien, $ck = 0){
    return ['part_code' => 'PT-1', 'part_name' => $ten, 'note' => '', 'unit_name' => 'Chiếc',
            'quantity' => 1, 'unit_price' => $tien, 'discount_percent' => $ck, 'amount' => $tien,
            'item_type' => 'part'];
};
$cf = ['site_name' => 'Gara ZZ', 'address' => '1 Duong Thu', 'hotline' => '', 'email' => '', 'tax_code' => '0101'];

/* Số cột tiêu đề và mọi colspan phải khớp: bỏ cột CK mà quên colspan là bảng vỡ. */
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
    return [$soCot, $sai];
};

// ---------------------------------------------------------------------------
section('Khong dong nao chiet khau -> khong in cot CK');

$h1 = $inThu([$dongHang('Bugi Iridium NGK', 189000), $dongHang('Loc dau', 120000)], 10, $cf);
ok(preg_match('~<th[^>]*>\s*CK\s*</th>~', $h1) === 0, 'Khong co cot CK khi khong ai chiet khau');
list($n1, $sai1) = $kiemCot($h1);
ok($n1 === 7 && empty($sai1), 'Moi dong trong bang khop 7 cot', "cot: $n1, dong lech: " . json_encode($sai1));

$h2 = $inThu([$dongHang('Bugi Iridium NGK', 189000, 5), $dongHang('Loc dau', 120000)], 10, $cf);
ok(preg_match('~<th[^>]*>\s*CK\s*</th>~', $h2) === 1, 'Co dong chiet khau -> CO cot CK');
ok(strpos($h2, '>5%<') !== false, 'Va in dung muc chiet khau');
list($n2, $sai2) = $kiemCot($h2);
ok($n2 === 8 && empty($sai2), 'Moi dong trong bang khop 8 cot', "cot: $n2, dong lech: " . json_encode($sai2));

// ---------------------------------------------------------------------------
section('Thue 0% khong in hai dong thua');

$h0 = $inThu([$dongHang('Loc gio', 200000)], 0, $cf);
ok(mb_strpos($h0, 'Thuế GTGT') === false, 'VAT 0%: khong in dong "Thue GTGT (0%) 0"');
ok(mb_strpos($h0, 'chưa thuế') === false, 'VAT 0%: khong in dong "Cong tien hang chua thue" (trung so tong)');
ok(mb_strpos($h0, 'TỔNG CỘNG THANH TOÁN') !== false, 'Van in dong tong cong');
ok(mb_strpos($h1, 'Thuế GTGT (10%)') !== false && mb_strpos($h1, 'chưa thuế') !== false,
   'VAT 10%: van in day du chua thue + thue');

// ---------------------------------------------------------------------------
section('Dau trang khong co dong trong');

$dau = function($html){
    return preg_match('~<div class="dong-nho">(.*?)</div>~s', $html, $m) ? $m[1] : '';
};
$d1 = $dau($h1);
ok(preg_match('~<br\s*/?>\s*<br~', $d1) === 0 && preg_match('~<br\s*/?>\s*$~', trim($d1)) === 0,
   'Khong khai dien thoai / email -> khong co dong trong giua dia chi va MST', trim($d1));
ok(strpos($d1, '|') === false || preg_match('~Điện thoại[^<]*\|~u', $d1) === 1,
   'Khong co dong bat dau bang dau "|"', trim($d1));

$cf2 = $cf; $cf2['hotline'] = '';  $cf2['email'] = 'a@zz.test';
$d2 = $dau($inThu([$dongHang('Loc gio', 200000)], 10, $cf2));
ok(preg_match('~(^|<br\s*/?>)\s*(&nbsp;)?\s*\|~', trim($d2)) === 0,
   'Chi co email (khong co dien thoai) -> khong mo dau bang "|"', trim($d2));
ok(mb_strpos($d2, 'a@zz.test') !== false, 'Va email van in ra');

$cf3 = $cf; $cf3['hotline'] = '0909'; $cf3['email'] = 'a@zz.test';
$d3 = $dau($inThu([$dongHang('Loc gio', 200000)], 10, $cf3));
ok(mb_strpos($d3, '0909') !== false && mb_strpos($d3, 'a@zz.test') !== false && mb_strpos($d3, '0101') !== false,
   'Khai du: van in dien thoai, email, MST', trim($d3));

ok(empty($canhBao), 'Khong canh bao PHP khi in', implode(' | ', array_slice($canhBao, 0, 3)));

exit(summary());

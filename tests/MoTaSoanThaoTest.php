<?php
/**
 * Test Ô MÔ TẢ HÀNG HOÁ / DỊCH VỤ CÓ BỘ SOẠN THẢO.
 *
 * Chạy:  C:\xampp\php\php.exe tests\MoTaSoanThaoTest.php
 *
 * Khách ghi 25/11/2026 ở mục Danh mục hàng hoá: "Các mô tả thiếu bộ ckeditor
 * hoặc tương đương". Ô Mô tả ở form hàng hoá và dịch vụ là textarea trơn —
 * không in đậm, không gạch đầu dòng, không chèn bảng thông số được, trong khi
 * Tin tức và Chân trang đã có bộ soạn thảo (public/assets/js/soan-thao.js).
 *
 * Ba chỗ phải đi cùng nhau, thiếu một là hỏng:
 *   1. Form gắn bộ soạn thảo.
 *   2. Đường LƯU lọc qua loc_html_an_toan() — mô tả giờ in ra trang khách
 *      không escape, nên lọc ở cửa vào (cả đường nhập Excel).
 *   3. Trang chi tiết in HTML đã lọc. Mô tả CŨ là chữ trơn có xuống dòng —
 *      vẫn phải giữ xuống dòng, và dấu "<" trong chữ trơn không được thành thẻ.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Form gan bo soan thao cho o Mo ta');

foreach (['products/add', 'products/edit', 'services/add', 'services/edit'] as $f){
    $v = file_get_contents($goc . "app/views/admin/$f.php");
    ok(strpos($v, "public/assets/js/soan-thao.js") !== false, "$f.php nap soan-thao.js");
    ok(preg_match('~oSoanThao\(\s*document\.querySelector\(\s*\'\[name="description"\]\'\s*\)\s*\)~', $v) === 1,
       "$f.php bien o description thanh bo soan thao");
}

// ---------------------------------------------------------------------------
section('Duong luu loc HTML');

$p = codeOnly($goc . 'app/controllers/admin/Products.php');
ok(preg_match("~'description'\s*=>\s*!empty\(\\\$f\['description'\]\)\s*\?\s*loc_html_an_toan\(~", $p) === 1,
   'Products: form luu mo ta qua loc_html_an_toan()');
/* Nhap Excel KHONG loc luc luu: chu tron kieu "a<b" qua bo loc HTML la mat chu.
   Trang khach loc luc IN (mo_ta_hang_hoa) moi thu trong nhu HTML — xem duoi. */
$s = codeOnly($goc . 'app/controllers/admin/Services.php');
ok(preg_match("~'description'\s*=>\s*!empty\(\\\$f\['description'\]\)\s*\?\s*loc_html_an_toan\(~", $s) === 1,
   'Services: luu mo ta qua loc_html_an_toan()');

// ---------------------------------------------------------------------------
section('Trang chi tiet in mo ta');

$d = file_get_contents($goc . 'app/views/storefront/detail.php');
ok(strpos($d, 'mo_ta_hang_hoa($part[\'description\'])') !== false, 'detail.php in qua mo_ta_hang_hoa()');
ok(strpos($d, "nl2br(e(\$part['description']))") === false, 'Khong con escape het (the <strong> hien thanh chu)');

ok(function_exists('mo_ta_hang_hoa'), 'Co ham mo_ta_hang_hoa()');
if (function_exists('mo_ta_hang_hoa')){
    $cu = mo_ta_hang_hoa("Dong 1\nDong 2 co dau <3 va a<b");
    ok(strpos($cu, '<br') !== false, 'Mo ta CU (chu tron): van giu xuong dong', $cu);
    ok(strpos($cu, '&lt;3') !== false && strpos($cu, 'a&lt;b') !== false,
       'Mo ta CU: dau "<" trong chu tron duoc escape, khong thanh the', $cu);

    $moi = mo_ta_hang_hoa('<p><strong>Bugi</strong> <em>NGK</em></p><ul><li>Ren 14mm</li></ul>'
                        . '<script>alert(1)</script><img src=x onerror="alert(2)">');
    ok(strpos($moi, '<strong>Bugi</strong>') !== false && strpos($moi, '<li>Ren 14mm</li>') !== false,
       'Mo ta MOI (HTML): giu in dam, gach dau dong', $moi);
    ok(stripos($moi, '<script') === false && stripos($moi, 'onerror') === false,
       'Mo ta MOI: the / thuoc tinh chay duoc bi loc', $moi);
    ok(mo_ta_hang_hoa('') === '' && mo_ta_hang_hoa(null) === '', 'Rong vao rong ra');
}

exit(summary());

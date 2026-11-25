<?php
/**
 * Test PHIẾU KHO TRÊN ĐIỆN THOẠI GIỐNG BÁN HÀNG / MUA HÀNG.
 *
 * Chạy:  C:\xampp\php\php.exe tests\PhieuKhoMobileTest.php
 *
 * Khách ghi 25/11/2026 (chữ đỏ — việc mới): "Sửa các phiếu nhập kho, xuất
 * kho… liên quan hàng hóa trên mobile nhất quán giống bán hàng, mua hàng".
 *
 * Đo trên khổ 375px: ô chọn hàng của phiếu nhập bị bóp còn ~80px (không đọc
 * được tên), không gõ tìm được, không có ảnh; chọn trùng mặt hàng ra hai dòng;
 * nút xoá dòng 24px; "Tổng cộng" nằm ngoài vùng nhìn thấy. Đơn mua đã có đủ
 * những thứ đó. Áp cho cả 8 màn: nhập / xuất / chuyển kho / kiểm kê × thêm, sửa.
 */

require_once __DIR__ . '/_helpers.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

$man = ['goods-receipts/add', 'goods-receipts/edit', 'goods-issues/add', 'goods-issues/edit',
        'transfers/add', 'transfers/edit', 'stock-takes/add', 'stock-takes/edit'];

foreach ($man as $f){
    section($f);
    $v = file_get_contents($goc . "app/views/admin/$f.php");

    ok(strpos($v, "public/assets/js/dong-hang-kho.js") !== false, 'Nap dong-hang-kho.js (gop dong trung)');
    ok(preg_match('~<table class="[^"]*\bbang-dong-kho\b[^"]*">(?:(?!</table>).)*<tbody id="lines">~s', $v) === 1,
       'Bang dong hang co class bang-dong-kho (o chon hang rong tren dien thoai)');
    ok(preg_match("~function buildSelect\(.*?js-search.*?\n\s*\}~s", $v) === 1,
       'O chon hang go tim duoc (js-search) nhu don mua');
    ok(preg_match("~function buildSelect\(.*?data-img.*?\n\s*\}~s", $v) === 1,
       'O chon hang co anh (data-img) nhu don mua');
    ok(preg_match("~\\\$partJs\[\] = \[[^;]*'img'\s*=>~", $v) === 1, 'Danh sach hang kem anh');
    ok(strpos($v, 'nut-xoa-dong') !== false && strpos($v, "rm.innerHTML='&times;'") === false
       && strpos($v, "rm.innerHTML = '&times;'") === false,
       'Nut xoa dong to (nut-xoa-dong, bieu tuong thung rac)');
    ok(strpos($v, "closest('.rm-row')") !== false,
       'Bam trung bieu tuong trong nut van xoa duoc dong (closest, khong phai e.target)');
}

section('Tong cong phieu nhap nam ngoai vung cuon');
foreach (['goods-receipts/add', 'goods-receipts/edit'] as $f){
    $v = file_get_contents($goc . "app/views/admin/$f.php");
    ok(strpos($v, 'tong-man-hep') !== false && strpos($v, 'lines-total-hep') !== false,
       "$f: co khoi tong cho man hep");
}

section('CSS + JS dung chung');
$css = file_get_contents($goc . 'public/assets/css/admin-theme.css');
ok(preg_match('~\.bang-dong-kho > tbody > tr > td:first-child[^{]*\{\s*min-width:\s*2\d\dpx~', $css) === 1,
   'Man hep: cot hang hoa cua phieu kho rong >= 200px');
ok(preg_match('~\.bang-dong-kho > tfoot\s*\{\s*display:\s*none~', $css) === 1,
   'Man hep: tfoot an, thay bang khoi tong ngoai vung cuon');
ok(preg_match('~\.bang-dong-kho \.nut-xoa-dong~', $css) === 1, 'Nut xoa dong 34px cho phieu kho');

$js = file_get_contents($goc . 'public/assets/js/dong-hang-kho.js');
ok(strpos($js, 'line_qty[]') !== false && strpos($js, 'line_actual[]') !== false,
   'Gop dong trung: cong ca so luong (nhap/xuat/chuyen) lan so dem (kiem ke)');
ok(strpos($js, "tbody#lines") !== false, 'Gop dong chi chay trong bang dong hang');

$adm = file_get_contents($goc . 'public/assets/js/admin.js');
ok(preg_match('~var CHON = \'[^\']*actual\[\]~', $adm) === 1, 'Nut -/+ gan ca o so dem kiem ke (line_actual[])');
ok(preg_match('~actual\\\\\[\\\\\]\$/\.test\(o\.name\)\s*\?\s*0~', $adm) === 1,
   'O so dem kiem ke giam duoc ve 0 (khong ep ve 1 nhu so luong ban)');

exit(summary());

<?php
/**
 * Test Ô "LẮP CHO ĐỜI XE" GẬP THEO HÃNG.
 *
 * Chạy:  C:\xampp\php\php.exe tests\DoiXeGapTheoHangTest.php
 *
 * Khách ghi 25/11/2026 ở dòng "tạo phụ tùng (phụ tùng theo xe)": "Bỏ phần dài
 * này đi, click vào công ty mới hiện ra". Form hàng hoá in MỌI cặp hãng–model
 * kèm mọi đời xe thành một cột dài (khung cuộn 460px) — vài chục model là phải
 * cuộn mãi mới tới hãng cần tick.
 *
 * Nay gom theo HÃNG (cái khách gọi "công ty"): mỗi hãng một dòng, gập sẵn, kèm
 * số đời đã tick; bấm vào hãng mới hiện model + năm. Ô lọc nhanh vẫn giữ: gõ là
 * tự mở hãng có kết quả. Áp cho cả form Thêm lẫn Sửa.
 */

require_once __DIR__ . '/_helpers.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

foreach (['add', 'edit'] as $f){
    section("products/$f.php");
    $v = file_get_contents($goc . "app/views/admin/products/$f.php");

    ok(preg_match('~\$fitBrands\[\$cy\[\'brand_name\'\]\]\[\$cy\[\'model_name\'\]\]\[\]\s*=\s*\$cy~', $v) === 1,
       'Gom doi xe theo HANG roi MODEL');
    ok(preg_match('~<details class="fit-brand[^"]*">\s*<summary~', $v) === 1,
       'Moi hang la mot khoi gap (details/summary) — bam moi hien');
    ok(strpos($v, '<details class="fit-brand" open') === false && strpos($v, 'fit-brand border rounded mb-1" open') === false,
       'Mac dinh GAP, khong mo san');
    ok(strpos($v, 'fit-dem') !== false, 'Dong hang co so doi da tick');
    ok(strpos($v, 'name="fitments[]"') !== false, 'O tick van gui fitments[] (duong luu khong doi)');
    ok(strpos($v, 'id="fit-filter"') !== false, 'Van co o loc nhanh');
    ok(preg_match('~\.fit-brand\'\).forEach\(function \(b\)\s*\{[^}]*b\.open\s*=~s', $v) === 1,
       'Go loc thi tu mo hang co ket qua');
    ok(strpos($v, '$fitGroups') === false, 'Bo cach gom cu theo "Hang — Model" (mot cot dai)');
}

exit(summary());

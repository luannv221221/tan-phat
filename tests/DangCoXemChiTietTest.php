<?php
/**
 * Test CỘT "ĐANG CÓ" Ở MÀN QUẢN LÝ CÔNG TY: ẨN, BẤM "XEM CHI TIẾT" MỚI HIỆN.
 *
 * Chạy:  C:\xampp\php\php.exe tests\DangCoXemChiTietTest.php
 *
 * Anh chụp cột "Đang có" 25/11/2026 (mỗi dòng một chuỗi dài "1 kho · 6 người
 * dùng · 7 đối tượng · 3 nhóm khách · 4 xe · 7 báo giá..." tràn khỏi màn):
 * "ẩn đoạn này đi ấn xem chi tiết thì xem".
 *
 * Cột vẫn giữ (nó nói trước công ty nào còn dữ liệu — vì sao không có nút Xoá),
 * chỉ thu lại thành nút "Xem chi tiết"; bấm mới xổ ra, mỗi thứ một dòng.
 * Công ty chưa có gì thì vẫn ghi "trống" — không có gì để xem chi tiết.
 */

require_once __DIR__ . '/_helpers.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Nguon: cot "Dang co" thu sau nut "Xem chi tiet"');

$v = file_get_contents($goc . 'app/views/admin/garages/lists.php');
ok(strpos($v, '<details class="dang-co">') !== false, 'Cot "Dang co" boc trong <details class="dang-co"> (dong mo khong can JS)');
ok(mb_strpos($v, '<summary>Xem chi tiết</summary>') !== false, 'Nut bam ghi dung "Xem chi tiết"');
ok(strpos($v, "implode(' · '") === false, 'Khong con in ca chuoi dai noi bang " · " ra thang o bang');
ok(mb_strpos($v, 'trống') !== false, 'Cong ty chua co gi van ghi "trống"');
$css = file_get_contents($goc . 'public/assets/css/admin-theme.css');
ok(strpos($css, 'details.dang-co') !== false, 'Co CSS cho nut / danh sach xo ra');

// ---------------------------------------------------------------------------
section('Chay that: man Quan ly cong ty');

$base = web_goc();
$jar  = tempnam(sys_get_temp_dir(), 'dc');
$http = function($url, $post = null) use ($jar){
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 30]);
    if ($post !== null){ curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post)); }
    $b = curl_exec($ch);
    $c = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $c, 'body' => (string) $b];
};

$r = $http("$base/dang-nhap");
if ($r['code'] === 0){ echo "\n[SKIP] Apache khong chay (localhost:88).\n"; exit(summary()); }
/* Mat khau tai khoan thu lay tu hang MK_MAU cua tool sinh du lieu — khong chep lai. */
preg_match("~const MK_MAU = '([^']+)'~", file_get_contents($goc . 'tools/tao-du-lieu-gara-mau.php'), $mk);
preg_match('~name="_token"[^>]*value="([^"]+)"~', $r['body'], $tk);
$http("$base/dang-nhap", ['_token' => $tk[1] ?? '', 'email' => 'admin@gara-mau.test', 'password' => $mk[1] ?? '']);

$r = $http("$base/admin/garages");
if ($r['code'] !== 200 || strpos($r['body'], 'Mã công ty') === false){
    echo "\n[SKIP] Khong dang nhap duoc admin@gara-mau.test (tao lai bang tools/tao-du-lieu-gara-mau.php).\n";
    exit(summary());
}
ok(preg_match('~<tbody>(.*?)</tbody>~s', $r['body'], $tb) === 1, 'Co bang danh sach cong ty');
$than = isset($tb[1]) ? $tb[1] : '';
$soNut = preg_match_all('~<details class="dang-co">\s*<summary>Xem chi tiết</summary>~u', $than);
ok($soNut >= 1, 'Cong ty co du lieu hien nut "Xem chi tiết"', "$soNut nut");
$ngoai = preg_replace('~<details class="dang-co">.*?</details>~su', '', $than);
ok(mb_strpos($ngoai, 'nhóm khách') === false && mb_strpos($ngoai, 'người dùng') === false,
   'Ngoai nut "Xem chi tiết" khong con chu dem nao (kho / nguoi dung / nhom khach...) hien san');
ok(preg_match('~<details class="dang-co">.*?<li>[^<]*\d+ (kho|người dùng)~su', $than) === 1,
   'Bam ra thi thay tung muc mot dong (<li>)');

@unlink($jar);
exit(summary());

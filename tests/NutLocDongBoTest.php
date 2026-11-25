<?php
/**
 * Test NÚT LỌC Ở CÁC MÀN DANH SÁCH: CÓ CHỮ, CÙNG MỘT KIỂU.
 *
 * Chạy:  C:\xampp\php\php.exe tests\NutLocDongBoTest.php
 *
 * Anh chụp màn Phiếu thu 25/11/2026, mũi tên chỉ vào nút lọc: "mấy cái nút".
 * Nút đó là `btn-default btn-block` chỉ có hình cái phễu — trắng, viền mảnh, dài
 * hết cột, trông như một ô nhập bị trống chứ không ra nút bấm. Thêm ba màn nữa
 * cũng chỉ có hình (Thẻ kho, Biến động tồn, Bảo hành / bảo trì), mỗi màn một màu,
 * trong khi 19 màn khác dùng chung nút xanh "🔍 Lọc".
 *
 * Chốt: mọi nút gửi của form lọc (GET) phải CÓ CHỮ; nút "Lọc" cùng một kiểu
 * `btn btn-sm btn-info` + kính lúp.
 */

require_once __DIR__ . '/_helpers.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Quet moi form loc (GET) trong app/views/admin');

$chiHinh = []; $locKhacKieu = []; $soNutLoc = 0;
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($goc . 'app/views/admin', FilesystemIterator::SKIP_DOTS));
foreach ($it as $f){
    if (substr($f->getFilename(), -4) !== '.php') continue;
    $path = str_replace('\\', '/', substr($f->getPathname(), strlen($goc)));
    $src  = file_get_contents($f->getPathname());
    if (!preg_match_all('~<form[^>]*method="get"[^>]*>.*?</form>~si', $src, $forms)) continue;
    foreach ($forms[0] as $form){
        /* Nut trong <noscript> chi hien khi tat JS (o chon tu gui form) — khong tinh. */
        $form = preg_replace('~<noscript>.*?</noscript>~si', '', $form);
        if (!preg_match_all('~<button[^>]*type="submit"[^>]*>(.*?)</button>~si', $form, $nut, PREG_SET_ORDER)) continue;
        foreach ($nut as $n){
            $chu = trim(html_entity_decode(strip_tags($n[1]), ENT_QUOTES, 'UTF-8'));
            if ($chu === '') { $chiHinh[] = $path; continue; }
            if ($chu === 'Lọc'){
                $soNutLoc++;
                if (!preg_match('~class="btn btn-sm btn-info"~', $n[0]) || strpos($n[1], 'fa-search') === false){
                    $locKhacKieu[] = $path;
                }
            }
        }
    }
}
ok(empty($chiHinh), 'Khong con nut loc CHI CO HINH (trong nhu o nhap trong)', implode(' | ', array_unique($chiHinh)));
ok(empty($locKhacKieu), 'Moi nut "Lọc" cung mot kieu: btn btn-sm btn-info + kinh lup', implode(' | ', array_unique($locKhacKieu)));
ok($soNutLoc >= 24, 'Bo quet thay du nut "Lọc" (19 man cu + 5 man vua sua)', "$soNutLoc nut");

// ---------------------------------------------------------------------------
section('Man anh chup: Phieu thu / Phieu chi');

$v = file_get_contents($goc . 'app/views/admin/acc-vouchers/lists.php');
ok(strpos($v, '<i class="fas fa-search mr-1"></i> Lọc</button>') !== false, 'Nut "🔍 Lọc" co chu');
ok(strpos($v, 'btn-block') === false, 'Khong con nut keo dai het cot (btn-block)');
ok(preg_match('~href="\{\{_WEB_URL\.\'/admin/\'\.\$routeBase\}\}" class="btn btn-sm btn-default">Xoá</a>~u', $v) === 1,
   'Co nut "Xoá" bo loc, ve dung danh sach dang xem (thu / chi)');

exit(summary());

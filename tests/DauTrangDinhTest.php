<?php
/**
 * Test DAU TRANG DINH KHI CUON (storefront).
 *
 * Chay:  C:\xampp\php\php.exe tests\DauTrangDinhTest.php
 *
 * Boi canh — ban cu lam bang su kien scroll:
 *
 *     window.addEventListener("scroll", () => {
 *       if (window.scrollY >= topHeader.clientHeight) {
 *         header.style.position = "fixed";
 *         topHeader.style.display = "none";
 *         topBar.style.display = "none";
 *       }
 *       if (scrollY == 0) { ... content.style.paddingTop = header.clientHeight + "px"; }
 *     });
 *
 * Do duoc tren trinh duyet o kho 1440x900:
 *   - Vuot moc 74px, noi dung NHAY 226px trong dung mot nac lan chuot
 *     (tieu de tu y=261 len y=35).
 *   - Cuon nguoc len KHONG bao gio go fixed -> phai va bang padding-top
 *     225px cho .content, trang giat them lan nua va nam o trang thai khac
 *     han luc moi tai.
 *
 * Nay dung position:sticky voi le am. Test nay giu cho khong ai quay lai
 * cach cu — grep thang vao file JS/CSS, khong can trinh duyet.
 */

require_once __DIR__ . '/_helpers.php';

echo 'PHP ' . PHP_VERSION . "\n";

$goc = __DIR__ . '/../';

/**
 * Bo comment cua file JS.
 *
 * Header dinh la cho da tung sai, nen phan comment trong script.js co trich
 * lai NGUYEN VAN doan code cu de giai thich. Grep thang vao text nguon se
 * trung comment va bao fail oan.
 *
 * Chi bo /* * / va dong bat dau bang // — khong dung regex cho // giua dong
 * vi se an luon phan sau cua "https://...".
 */
function jsKhongComment($path){
    $src = file_get_contents($path);
    $src = preg_replace('#/\*.*?\*/#s', '', $src);
    $dong = preg_split('/\R/', $src);
    $giu  = [];
    foreach ($dong as $d){
        if (strpos(ltrim($d), '//') === 0) continue;
        $giu[] = $d;
    }
    return implode("\n", $giu);
}

$jsPath  = $goc . 'public/assets/storefront/js/script.js';
$cssPath = $goc . 'public/assets/storefront/css/theme.css';

ok(is_file($jsPath),  'Co file script.js');
ok(is_file($cssPath), 'Co file theme.css');

$js     = jsKhongComment($jsPath);
$jsThua = file_get_contents($jsPath);
$css    = file_get_contents($cssPath);

// ---------------------------------------------------------------------------
section('Khong quay lai cach cu (nghe scroll)');

ok(strpos($js, 'content.style.paddingTop') === false,
   'Bo han cai va padding-top cho .content',
   'Con cai va nay nghia la header lai bi ket o position:fixed');

ok(!preg_match('/header\.style\.position\s*=\s*["\']fixed["\']/', $js),
   'Khong con gan position:fixed cho header bang JS');

ok(!preg_match('/topBar\.style\.display\s*=\s*["\']none["\']/', $js),
   'Khong con an dai lien he bang display:none');

ok(!preg_match('/topHeader\.style\.display\s*=\s*["\']none["\']/', $js),
   'Khong con an hang logo bang display:none');

ok(!preg_match('/addEventListener\(\s*["\']scroll["\']\s*,\s*\(\)\s*=>/', $js),
   'Khong con listener scroll dieu khien header');

// ---------------------------------------------------------------------------
section('Cach moi: sticky + le am do duoc');

ok(strpos($js, 'datLeAm') !== false,
   'Co ham dat le am cho header');

ok(preg_match('/header\.style\.top\s*=\s*-Math\.round\(/', $js),
   'Le am duoc gan bang so AM (dau tru truoc Math.round)');

ok(strpos($js, "querySelector(\".car-filter\")") !== false,
   'Do le am tu thanh loc xe');

ok(strpos($js, '.header__primary-menu') !== false,
   'Co duong lui sang menu khi admin tat thanh loc',
   'Thanh loc bat/tat duoc o admin > Cau hinh website — tat di ma van do theo '
   . 'no thi le am ra 0');

ok(preg_match('/getBoundingClientRect\(\)\.top\s*-\s*header\.getBoundingClientRect\(\)\.top/', $js),
   'Le am = hieu hai getBoundingClientRect, khong viet cung so px',
   'Viet cung la sai ngay khi admin tat dai lien he');

ok(strpos($js, "addEventListener(\"resize\", datLeAm)") !== false,
   'Do lai khi doi kho man hinh');

ok(strpos($js, "addEventListener(\"load\", datLeAm)") !== false,
   'Do lai sau khi anh logo tai xong',
   'Truoc load, hang logo chua co chieu cao that -> le am thieu');

// ---------------------------------------------------------------------------
section('Kho dien thoai khong ghim');

ok(preg_match('/window\.innerWidth\s*<\s*768/', $js),
   'Co nga re rieng cho duoi 768px');

ok(preg_match('/innerWidth\s*<\s*768\s*\)\s*\{\s*header\.style\.position\s*=\s*["\']static["\']/s', $js),
   'Duoi 768px thi tra header ve static (khong ghim)',
   'Dau trang o kho dien thoai cao 307px = 38% man hinh, ghim vao la mat gan '
   . 'nua cho doc');

ok(strpos($jsThua, '$("#button")') !== false,
   'Van con nut mui ten ve dau trang',
   'Kho dien thoai khong ghim nua nen nut nay la duong ve duy nhat');

// ---------------------------------------------------------------------------
section('CSS du phong + khoang tho');

ok(preg_match('/\.header\{[^}]*position:sticky/s', $css),
   'theme.css van dat .header position:sticky',
   'La muc du phong khi JS chua chay');

ok(preg_match('/\.header\{[^}]*z-index:1030/s', $css),
   'Header co z-index de khong bi the san pham de len');

$viTriTho = strpos($css, '.car-filter{ padding-top:10px');
ok($viTriTho !== false,
   'Chua khoang tho 10px tren thanh loc',
   'Luc bi ghim, mep tren khung loc chinh la mep tren man hinh');

// Phai nam trong @media min-width — kho dien thoai xep thanh loc 2x2, cong
// them padding la day dau trang len 38%+ man hinh.
$mediaGanNhat = $viTriTho === false
    ? false
    : strrpos(substr($css, 0, $viTriTho), '@media');
ok($mediaGanNhat !== false
   && strpos(substr($css, $mediaGanNhat, 40), 'min-width:768px') !== false,
   'Khoang tho chi ap tu 768px tro len');

exit(summary());

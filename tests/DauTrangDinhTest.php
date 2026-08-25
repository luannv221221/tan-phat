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

ok(strpos($js, 'datViTri') !== false,
   'Co ham dat vi tri dinh');

ok(preg_match('/header\.style\.top\s*=\s*-Math\.round\(/', $js),
   'Le am cua header duoc gan bang so AM (dau tru truoc Math.round)');

ok(preg_match('/getBoundingClientRect\(\)\.top\s*-\s*header\.getBoundingClientRect\(\)\.top/', $js),
   'Le am = hieu hai getBoundingClientRect, khong viet cung so px',
   'Viet cung la sai ngay khi admin tat dai lien he');

ok(preg_match('/var\s+nav\s*=\s*header\.querySelector\(["\']\.header__primary-menu["\']\)/', $js),
   'Moc ghim cua header la MENU XANH',
   'Thanh loc da ra khoi header roi, do theo no la sai');

ok(strpos($js, "addEventListener(\"resize\", datViTri)") !== false,
   'Do lai khi doi kho man hinh');

ok(strpos($js, "addEventListener(\"load\", datViTri)") !== false,
   'Do lai sau khi anh logo tai xong',
   'Truoc load, hang logo chua co chieu cao that -> le am thieu');

// ---------------------------------------------------------------------------
section('Thanh loc ghim NGAY DUOI menu xanh');

ok(preg_match('/thanhLoc\.style\.position\s*=\s*["\']sticky["\']/', $js),
   'Thanh loc cung duoc dat sticky');

ok(preg_match('/thanhLoc\.style\.top\s*=\s*Math\.ceil\(nav\.getBoundingClientRect\(\)\.height\)/', $js),
   'Moc dinh cua thanh loc = chieu cao menu xanh, lam tron LEN',
   'Menu cao 50.4px; lam tron xuong 50 thi thanh loc ghim cao hon day menu '
   . '0.4px va bi menu che mat mot vach');

// ---------------------------------------------------------------------------
section('Thanh loc khong duoc DOI CHIEU CAO khi ghim');

// Da tung lam: nhan nam trong .car-filter va thu lai luc ghim. Do duoc tren
// trinh duyet: o cua no trong dong chay cung co theo -> toan bo noi dung phia
// duoi bi keo len 49px dung vao khoanh khac ghim. Dung y het loi cua ban cu.
ok(strpos($js, 'dang-ghim') === false,
   'Khong con class dang-ghim trong JS');
ok(strpos($css, 'dang-ghim') === false,
   'Khong con luat CSS cho dang-ghim');
ok(strpos($js, 'IntersectionObserver') === false,
   'Khong con theo doi trang thai ghim');

ok(strpos($css, '.car-filter-nhan') !== false,
   'Nhan nam o khoi RIENG (.car-filter-nhan), ngoai phan bi ghim',
   'Nho vay nhan cu troi di nhu noi dung thuong, con thanh loc giu nguyen '
   . 'chieu cao mai mai');

$partial = file_get_contents(__DIR__ . '/../app/views/layouts/storefront/partials/car-filter.php');
ok(strpos($partial, '<div class="car-filter-nhan">') !== false,
   'Partial dung nhan thanh the anh em, khong long vao trong');
ok(preg_match('/car-filter-nhan.*?<div class="car-filter">/s', $partial) === 1,
   'Nhan dung TRUOC thanh loc');

// ---------------------------------------------------------------------------
section('Thanh loc da ra khoi <header>');

$master = file_get_contents(__DIR__ . '/../app/views/layouts/storefront/master.php');
$vtHeaderDong = strpos($master, '</header>');
$vtGoiPartial = strpos($master, "partials/car-filter");
ok($vtHeaderDong !== false && $vtGoiPartial !== false && $vtGoiPartial > $vtHeaderDong,
   'master.php goi thanh loc SAU khi dong </header>',
   'Nam trong header thi mo o chon ra la danh sach do xuong de len chinh '
   . 'dai menu, va dau trang phinh len 225px');

ok(strpos($master, 'ob_start()') !== false && strpos($master, "\$content['thanhLoc']") !== false,
   'Master dung san chuoi HTML roi truyen xuong view con',
   'View con chay qua eval trong Template::run(), $this o do la Template chu '
   . 'khong phai Controller -> tu no khong goi duoc $this->render()');

$home = file_get_contents(__DIR__ . '/../app/views/storefront/home.php');
ok(strpos($home, '{!! $thanhLoc !!}') !== false,
   'Trang chu tu in thanh loc');

$vtSlider = strpos($home, '<section class="slider">');
$vtLoc    = strpos($home, '{!! $thanhLoc !!}');
$vtCat    = strpos($home, '<section class="categories');
ok($vtSlider !== false && $vtLoc > $vtSlider,
   'Trang chu: thanh loc nam DUOI bang-ron');
ok($vtCat !== false && $vtLoc < $vtCat,
   'Trang chu: thanh loc nam TREN khoi danh muc');

// Chi duoc in DUNG MOT LAN: master in cho moi trang tru trang chu, trang chu
// tu in. In ca hai la ra hai thanh loc chong nhau.
ok(preg_match('/if\s*\(!\$laTrangChu\)\s*echo\s+\$thanhLoc/', $master) === 1,
   'Master bo qua trang chu de khong in hai lan');

// ---------------------------------------------------------------------------
section('Kho dien thoai khong ghim');

ok(preg_match('/window\.innerWidth\s*<\s*768/', $js),
   'Co nga re rieng cho duoi 768px');

ok(preg_match('/innerWidth\s*<\s*768\s*\)\s*\{\s*header\.style\.position\s*=\s*["\']static["\']/s', $js),
   'Duoi 768px thi tra header ve static (khong ghim)');

ok(preg_match('/innerWidth\s*<\s*768.*?thanhLoc\.style\.position\s*=\s*["\']static["\']/s', $js),
   'Duoi 768px thi thanh loc cung ve static',
   'Thanh loc o kho dien thoai cao 212px, ghim vao la mat 1/4 man hinh');

ok(strpos($jsThua, '$("#button")') !== false,
   'Van con nut mui ten ve dau trang',
   'Kho dien thoai khong ghim nua nen nut nay la duong ve duy nhat');

// ---------------------------------------------------------------------------
section('CSS');

ok(preg_match('/\.header\{[^}]*position:sticky/s', $css),
   'theme.css van dat .header position:sticky',
   'La muc du phong khi JS chua chay');

ok(preg_match('/\.header\{[^}]*z-index:1030/s', $css),
   'Header co z-index de khong bi the san pham de len');

ok(preg_match('/\.car-filter\{[^}]*position:sticky/s', $css),
   'theme.css dat .car-filter position:sticky');

ok(preg_match('/\.car-filter\{[^}]*z-index:1020/s', $css),
   'Thanh loc o duoi header (1030) va tren noi dung',
   'Cao hon header thi luc dang truot no de len ca menu');

exit(summary());

<?php
/**
 * THỬ NGHIỆM 16/10/2026 — MENU GỌN CHO KHO TỔNG (_KHO_TONG_MENU_GON).
 *
 * Chạy:  C:\xampp\php\php.exe tests\MenuGonKhoTongTest.php
 *
 * Trang quản trị của GARA TỔNG (Tân Phát) ẩn bốn nhóm không phải việc của kho
 * tổng — Bán hàng, Kế toán, CSKH, Quản lý website — và kéo "Quản lý gara" ra
 * thành mục riêng ngoài menu. Gara thường KHÔNG đổi gì.
 *
 * ĐÂY LÀ BẢN THỬ, PHẢI QUAY LẠI ĐƯỢC. Nên test khoá đúng bốn điều sau:
 *
 *   1. CÒN CÔNG TẮC, VÀ TẮT LÀ VỀ NHƯ CŨ. Nếu bốn nhóm kia bị xoá hẳn khỏi
 *      $menuGroups thay vì lọc lúc vẽ thì không còn đường lui — phải sửa lại
 *      mã nguồn mới khôi phục được.
 *
 *   2. ĂN LAN SANG GARA THƯỜNG. Gara vẫn phải thấy đủ menu: họ bán hàng, thu
 *      chi, chăm sóc khách và chạy web — ẩn nhầm là họ mất việc hằng ngày.
 *
 *   3. CHỈ ẨN MENU, KHÔNG ĐỤNG PHÂN QUYỀN. Thử một bố cục menu mà đi gỡ dòng
 *      trong `permissions` là đánh đổi sai chiều: bật lại menu thì quyền đã
 *      mất rồi, và không ai nhớ đã gỡ những gì.
 *
 *   4. MỤC ĐƠN KHÔNG CÓ ICON. sidebar.php gọi icon($ic) thẳng tay, không có
 *      đường lui — tên icon không nằm trong sprite thì ra ô vuông trống, mà
 *      nó nằm ngay đầu menu nên hỏng là hỏng chỗ dễ thấy nhất. Cùng cái bẫy
 *      MenuKeToanTest đã gác cho icon của nhóm.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';
$sb  = file_get_contents($goc . 'app/views/layouts/admin/sidebar.php');

/** Phần PHP đầu sidebar.php — tới `?>` đầu tiên, trước khối HTML */
$dauFile = function() use ($sb){
    $het = strpos($sb, "\n?>");
    return $het === false ? null : substr($sb, strlen('<?php'), $het - strlen('<?php'));
};

/**
 * Chạy THẬT khối logic của sidebar.php rồi trả về menu nó dựng ra.
 *
 * Chép lại danh sách nhóm sang test là test tự nói chuyện với chính mình: sửa
 * sidebar.php mà quên sửa test thì test vẫn xanh.
 *
 * $listModules để rỗng nên vòng lặp dựng $allowed không chạy — không gọi tới
 * route() (cần phiên đăng nhập + DB).
 */
$dungMenu = function($laGaraTong) use ($dauFile){
    $ma = $dauFile();
    if ($ma === null) return null;

    $garaHienTai = ['id' => 1, 'name' => 'Thu', 'is_master' => $laGaraTong ? 1 : 0];
    $listModules = [];
    $menuGroups = $groupIcons = $groupSeparators = $anNhom = $menuDon = null;

    eval($ma);
    return ['nhom' => array_keys($menuGroups), 'don' => $menuDon, 'groups' => $menuGroups];
};

// ---------------------------------------------------------------------------
section('Cong tac — tat la ve nhu cu (chot 1)');

$cfg = file_get_contents($goc . 'config.php');
ok(strpos($cfg, "_KHO_TONG_MENU_GON") !== false, 'Co hang _KHO_TONG_MENU_GON o config.php');
ok(defined('_KHO_TONG_MENU_GON'), 'Hang duoc dinh nghia khi nap config');

/* Bon nhom van phai NAM NGUYEN trong $menuGroups — loc luc ve, khong xoa han. */
foreach (['Bán hàng', 'Kế toán', 'CSKH', 'Quản lý website'] as $n){
    ok(preg_match("~'" . preg_quote($n, '~') . "'\s*=>\s*\[~u", $sb) === 1,
       "\$menuGroups VAN con nhom \"$n\" trong ma nguon",
       'Xoa han thi tat cong tac cung khong khoi phuc duoc');
}
/* `garages` cung vay: van nam trong nhom He thong o ma nguon, chi rut ra luc ve. */
ok(preg_match("~'Hệ thống'\s*=>\s*\[[^\]]*'garages'~u", $sb) === 1,
   '`garages` VAN con trong nhom He thong o ma nguon');

ok(preg_match('~defined\(\s*\x27_KHO_TONG_MENU_GON\x27\s*\)\s*&&\s*_KHO_TONG_MENU_GON~', $sb) === 1,
   'sidebar.php kiem defined() truoc khi doc hang',
   'Thieu hang (config cu, script rieng) thi ca menu vo chu khong chi mat mot nhom');

/* TAT CONG TAC = DU TAM NHOM. Chay o tien trinh rieng, dinh nghia hang truoc
   khi nap config.php — define() lan hai khong ghi de duoc gia tri dau. */
$kich = <<<'PHP'
error_reporting(0);
define('_KHO_TONG_MENU_GON', false);
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';
$sb = file_get_contents(__DIR__ . '/app/views/layouts/admin/sidebar.php');
$ma = substr($sb, 5, strpos($sb, "\n?>") - 5);
$garaHienTai = ['id' => 1, 'name' => 'Thu', 'is_master' => 1];
$listModules = [];
eval($ma);
echo implode('|', array_keys($menuGroups)) . "\n" . count($menuDon);
PHP;
$tam = sys_get_temp_dir() . '/zz_menu_tat_' . getmypid() . '.php';
file_put_contents($tam, "<?php\n" . str_replace('__DIR__', var_export(realpath($goc), true), $kich));
$ra = trim((string) shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tam) . ' 2>&1'));
@unlink($tam);

$dong = explode("\n", $ra);
$nhomTat = isset($dong[0]) ? explode('|', $dong[0]) : [];
ok(count($nhomTat) === 8,
   'Tat cong tac: gara tong thay lai du 8 nhom (' . count($nhomTat) . ')',
   'Ket qua tien trinh con: ' . $ra);
ok(isset($dong[1]) && trim($dong[1]) === '0',
   'Tat cong tac: khong con muc don nao — `garages` ve lai trong nhom He thong');

// ---------------------------------------------------------------------------
section('Bat cong tac — gara tong thay gi');

$tong = $dungMenu(true);
ok($tong !== null, 'Chay duoc khoi logic cua sidebar.php');

if ($tong !== null){
    foreach (['Bán hàng', 'Kế toán', 'CSKH', 'Quản lý website'] as $n){
        ok(!in_array($n, $tong['nhom'], true), "Gara tong KHONG thay nhom \"$n\"");
    }
    /* GIU LAI — day moi la viec cua kho tong. "Hang hoa" nam trong danh sach
       nay theo dung yeu cau: kho tong giu danh muc hang dung chung. */
    foreach (['Kho', 'Hàng hoá', 'Danh mục xe', 'Hệ thống'] as $n){
        ok(in_array($n, $tong['nhom'], true), "Gara tong VAN thay nhom \"$n\"");
    }

    ok(isset($tong['don']['garages']), '"Quan ly gara" ra mot muc rieng ngoai menu');
    ok(!in_array('garages', $tong['groups']['Hệ thống'], true),
       '`garages` da rut khoi nhom He thong luc ve',
       'De ca hai cho thi cung mot man hien hai lan tren mot menu');
}

// ---------------------------------------------------------------------------
section('Gara thuong KHONG doi gi (chot 2)');

$gara = $dungMenu(false);
ok($gara !== null && count($gara['nhom']) === 8,
   'Gara thuong van thay du 8 nhom (' . ($gara === null ? '?' : count($gara['nhom'])) . ')');
if ($gara !== null){
    foreach (['Bán hàng', 'Kế toán', 'CSKH', 'Quản lý website'] as $n){
        ok(in_array($n, $gara['nhom'], true), "Gara thuong VAN thay nhom \"$n\"");
    }
    ok(empty($gara['don']), 'Gara thuong khong co muc don nao');
    ok(in_array('garages', $gara['groups']['Hệ thống'], true),
       '`garages` van nam trong nhom He thong cua gara thuong',
       'Ho khong co quyen nen khong hien — nhung khong duoc rut khoi cau truc');
}

// ---------------------------------------------------------------------------
section('Chi an menu, khong dung phan quyen (chot 3)');

/* Khong duoc co migration nao xoa quyen / xoa module nhan danh ban thu nay. */
$xau = [];
foreach (glob($goc . 'database/migrations/*.php') as $f){
    $src = file_get_contents($f);
    if (strpos($src, 'KHO_TONG_MENU_GON') !== false) $xau[] = basename($f);
}
ok(empty($xau), 'Khong migration nao di kem ban thu nay',
   'Co: ' . implode(', ', $xau) . ' — sua CSDL thi tat cong tac khong con la duong lui');

/* Cac man bi an van con route — go thang duong dan van vao duoc, dung y do. */
$rt = codeOnly($goc . 'routes/web.php');
foreach (['quotations', 'phieu-thu', 'customers', 'news'] as $r){
    ok(strpos($rt, "'" . $r . "'") !== false, "Man bi an van con route `$r`");
}

// ---------------------------------------------------------------------------
section('Muc don phai co icon that (chot 4)');

$sprite = $goc . 'public/assets/vendor/lucide/lucide.svg';
ok(is_file($sprite), 'Tim thay sprite icon');
if (is_file($sprite) && $tong !== null){
    $svg = file_get_contents($sprite);
    foreach ((array) $tong['don'] as $link => $ic){
        ok(strpos($svg, 'id="i-' . $ic . '"') !== false,
           "Icon \"$ic\" cua muc don `$link` co that trong sprite",
           'icon() khong co duong lui — thieu la ra o vuong trong ngay dau menu');
    }
}

exit(summary());

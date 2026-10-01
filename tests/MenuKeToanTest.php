<?php
/**
 * Test NHÓM "KẾ TOÁN" TRÊN MENU ADMIN (Phiếu thu / Phiếu chi).
 *
 * Chạy:  C:\xampp\php\php.exe tests\MenuKeToanTest.php
 *
 * Giai đoạn này hai màn hình MỚI CÓ CHỖ ĐỨNG, chưa có nghiệp vụ. Nên test
 * không kiểm số liệu, mà kiểm đúng ba chỗ dễ làm hụt khi dựng chỗ đứng:
 *
 *  1. KHAI MODULE. Menu trái chỉ vẽ link có dòng trong bảng `modules`. Thêm
 *     tên vào $menuGroups mà quên migration thì nút không bao giờ hiện.
 *
 *  2. ĐĂNG KÝ ROUTE. Đây là chỗ sai IM LẶNG: Route::is() trả TRUE cho đường
 *     dẫn chưa khai route (không có route thì không có middleware để hỏi), nên
 *     bỏ trống route KHÔNG làm nút biến mất — nó làm nút hiện ra cho MỌI tài
 *     khoản, kể cả nhóm chưa được cấp quyền. Giống bẫy đã ghi trong
 *     QuanLyModuleTest: "màn hình KHÔNG AI GÁC" nguy hơn "màn hình bị khoá".
 *
 *  3. CÓ ICON. sidebar.php gọi icon($groupIcons[$groupName]) thẳng tay, không
 *     có đường lui. Thêm nhóm mà quên icon là PHP Warning ngay trên menu.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

const LINKS = ['phieu-thu' => 'Phiếu thu', 'phieu-chi' => 'Phiếu chi'];

// ---------------------------------------------------------------------------
section('Menu trai co nhom Ke toan');

/* Khớp bằng TÊN LINK (ASCII) chứ không bằng chữ "Kế toán": file nguồn viết
   tiếng Việt ở dạng tổ hợp dấu (NFD), chuỗi trong test này ở dạng dựng sẵn
   (NFC) — nhìn giống nhau nhưng khác byte, so thẳng là fail oan. */
$sb = file_get_contents($goc . 'app/views/layouts/admin/sidebar.php');

ok(preg_match("~=>\s*\['phieu-thu',\s*'phieu-chi'\]~", $sb) === 1,
   'sidebar.php co mot nhom chua ca phieu-thu lan phieu-chi',
   'Menu trai dung tu $menuGroups; link nao khong nam trong do thi khong hien');

// Rút khối $menuGroups và $groupIcons ra để so khoá — cả hai lấy từ CÙNG một
// file nên không vướng chuyện dạng dấu.
$lay = function($ten) use ($sb){
    if (!preg_match('~\$' . $ten . '\s*=\s*\[(.*?)\n\];~s', $sb, $m)) return null;
    preg_match_all("~^\s*'([^']+)'\s*=>~m", $m[1], $k);
    return $k[1];
};
$nhom = $lay('menuGroups');
$icon = $lay('groupIcons');

ok(is_array($nhom) && is_array($icon), 'Doc duoc $menuGroups va $groupIcons');

if (is_array($nhom) && is_array($icon)){
    $thieu = array_diff($nhom, $icon);
    ok(empty($thieu),
       'MOI nhom tren menu deu co icon',
       'thieu icon: ' . implode(', ', $thieu)
       . ' — sidebar.php goi icon($groupIcons[$groupName]) khong co duong lui');

    // Kế toán đứng ngay sau Bán hàng: phiếu thu sinh ra từ hoá đơn bán.
    $viTriKT = null;
    foreach ($nhom as $i => $g){
        if (preg_match("~'" . preg_quote($g, '~') . "'\s*=>\s*\['phieu-thu'~", $sb)) { $viTriKT = $i; break; }
    }
    ok($viTriKT !== null && $viTriKT === 1,
       'Nhom Ke toan dung thu hai, ngay sau Ban hang',
       'thu tu thuc te: ' . implode(' | ', $nhom));
}

// Icon phải thật sự có trong sprite, không thì ra ô trống.
$sprite = file_get_contents($goc . 'public/assets/vendor/lucide/lucide.svg');
if (preg_match("~=>\s*'receipt',~", $sb)){
    ok(strpos($sprite, 'id="i-receipt"') !== false,
       'Icon "receipt" co that trong sprite Lucide');
}

// ---------------------------------------------------------------------------
section('Route da dang ky — de RoleMiddleware gac duoc');

$rt = file_get_contents($goc . 'routes/web.php');
foreach (array_keys(LINKS) as $link){
    $ctl = str_replace('-', '', $link);
    ok(strpos($rt, "Route::get('$link', 'admin/$ctl');") !== false,
       "Co route GET /admin/$link",
       'Khong khai route thi Route::is() tra TRUE -> nut hien ra cho moi nhom');
}

// ---------------------------------------------------------------------------
section('Controller + view giu cho');

foreach (['Phieuthu', 'Phieuchi'] as $ctl){
    $f = $goc . 'app/controllers/admin/' . $ctl . '.php';
    ok(is_file($f), "Co controller $ctl.php");
    if (is_file($f)){
        ok(strpos(file_get_contents($f), 'admin/ke-toan/sap-co') !== false,
           "$ctl tro toi view giu cho dung chung");
    }
}
ok(is_file($goc . 'app/views/admin/ke-toan/sap-co.php'),
   'Co view "dang xay dung"',
   'Khai route ma khong co view thi bam vao ra 404 — trong nhu hong');

// ---------------------------------------------------------------------------
section('Da khai module + phan quyen trong CSDL');

try {
    $pdo = new PDO(
        'mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
        _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (\PDOException $e){
    echo "  [SKIP] Khong ket noi duoc MySQL — bo qua phan CSDL.\n";
    exit(summary());
}

foreach (LINKS as $link => $ten){
    $m = $pdo->query("SELECT id, name, chi_tan_phat FROM `modules` WHERE link = " . $pdo->quote($link))->fetch(PDO::FETCH_ASSOC);
    ok(!empty($m), "Module `$link` da co trong bang modules",
       'Chay: php migrate.php');
    if (empty($m)) continue;

    ok($m['name'] === $ten, "Ten man hinh la \"$ten\"", 'dang co: ' . $m['name']);

    /* Gara nao cung thu chi tien cua gara do, khong phai man rieng cua kho
       tong — bat co nay la Manager cac gara khac mat nut. */
    ok((int) $m['chi_tan_phat'] === 0,
       "`$link` KHONG danh rieng cho gara tong");

    $q = $pdo->query("SELECT g.name, p.role FROM `permissions` p
                      JOIN `groups` g ON g.id = p.group_id
                      WHERE p.module_id = " . (int) $m['id'])->fetchAll(PDO::FETCH_ASSOC);
    $theoNhom = [];
    foreach ($q as $r){ $theoNhom[$r['name']][] = $r['role']; }

    foreach (['Admin', 'Manager'] as $n){
        ok(isset($theoNhom[$n]) && count(array_intersect(['view','add','edit','delete'], $theoNhom[$n])) === 4,
           "$n du bon quyen tren `$link`");
    }
    // Ở quầy, nhân viên viết phiếu thu khi khách trả tiền; sửa / xoá phiếu
    // tiền thì để cấp quản lý.
    ok(isset($theoNhom['Staff']) && in_array('view', $theoNhom['Staff'], true)
        && in_array('add', $theoNhom['Staff'], true),
       "Staff xem va lap duoc `$link`");
    ok(isset($theoNhom['Staff']) && !in_array('delete', $theoNhom['Staff'], true),
       "Staff KHONG xoa duoc `$link`");
}

exit(summary());

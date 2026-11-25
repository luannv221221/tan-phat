<?php
/**
 * Test ĐỔI CHỮ "GARA" THÀNH "CÔNG TY" TRÊN GIAO DIỆN.
 *
 * Chạy:  C:\xampp\php\php.exe tests\GaraThanhCongTyTest.php
 *
 * Khách ghi 25/11/2026 trong file Tracking: "đổi gara thành công ty (gara chỉ là 1
 * việc của công ty)" và "công ty nghĩa hoàng (gara là ngành nghề)". Anh chốt
 * cùng ngày: đổi toàn bộ.
 *
 * HAI NGHĨA CỦA CHỮ "GARA", CHỈ ĐỔI MỘT:
 *   - ĐƠN VỊ dùng hệ thống (Quản lý gara, Mã gara, gara tổng, chủ gara, gara
 *     khác...) -> "công ty".
 *   - NGÀNH NGHỀ (thiết bị gara ô tô, sửa chữa bảo dưỡng gara, khách mang xe tới
 *     gara) -> GIỮ: chính khách nói "gara là ngành nghề".
 * Và không đụng: tên biến / bảng / đường dẫn (`garages`, `$gara`, `garage_id`),
 * giá trị lưu trong CSDL (`linh_vuc` = 'gara'), mã nội bộ (`data-nguon="gara"`).
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Quet: khong con chu "gara" hien thi nao chi DON VI');

/* Cùng cách tìm với script đổi: chỉ chuỗi PHP + chữ HTML, bỏ chú thích, bỏ
   chuỗi kiểu định danh ('gara', 'gara_tong'), bỏ $bien / ->thuoc_tinh / url. */
$re = '~(?<!->)(?<![\$\w/\-\.#])(gara|Gara|GARA)(?![\w\-(])~u';

/* Chỗ được GIỮ: [file => số lần]. Thêm vào đây phải có lý do. */
$duocGiu = [
    'app/controllers/Contact.php'             => 1,  // "thiết bị gara ô tô" — ngành nghề
    'app/views/layouts/storefront/master.php' => 1,  // slogan mặc định "thiết bị gara ô tô"
    'app/views/storefront/about.php'          => 2,  // "thiết bị gara ô tô", "khách hàng và gara trên toàn quốc"
    'app/views/storefront/home.php'           => 1,  // alt="Thiết bị gara"
    'app/views/admin/print/phieu-thu-chi.php' => 1,  // tên dự phòng "Gara ô tô"
    'app/views/admin/settings/form.php'       => 3,  // value="gara" (giá trị lưu) + nhãn lĩnh vực "bảo dưỡng gara" (2 chỗ)
    'app/views/admin/tai-khoan-web/lists.php' => 1,  // "Khách mang xe tới gara" — nơi sửa xe
    'app/views/admin/quotations/add.php'      => 7,  // data-nguon="gara", khoá JS gara:, 'gara', chú thích JS
    'app/views/admin/quotations/edit.php'     => 1,  // chú thích JS
    'app/views/storefront/thanh-toan.php'     => 1,  // class="gara"
    'app/views/admin/garages/ten-mien.php'    => 1,  // tên miền ví dụ gara.etek...
    'app/models/GarageDomainsModel.php'       => 1,  // tên miền ví dụ gara.etek...
];

$dem = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($goc . 'app', FilesystemIterator::SKIP_DOTS));
foreach ($it as $f){
    if (substr($f->getFilename(), -4) !== '.php') continue;
    $path = str_replace('\\', '/', substr(realpath($f->getPathname()), strlen(realpath($goc)) + 1));
    foreach (token_get_all(file_get_contents($f->getPathname())) as $t){
        if (!is_array($t)) continue;
        if (!in_array($t[0], [T_INLINE_HTML, T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true)) continue;
        if ($t[0] === T_CONSTANT_ENCAPSED_STRING && preg_match('~^[\'"][a-z0-9_]*[\'"]$~', $t[1])) continue;
        $n = preg_match_all($re, $t[1]);
        if ($n) $dem[$path] = (isset($dem[$path]) ? $dem[$path] : 0) + $n;
    }
}
$thua = [];
foreach ($dem as $p => $n){
    $cho = isset($duocGiu[$p]) ? $duocGiu[$p] : 0;
    if ($n > $cho) $thua[] = "$p: $n (duoc giu $cho)";
}
ok(empty($thua), 'Khong con chu "gara" chi don vi tren giao dien', implode(' | ', array_slice($thua, 0, 8))
   . (count($thua) > 8 ? ' ... +' . (count($thua) - 8) . ' file' : ''));

// ---------------------------------------------------------------------------
section('Nhan chinh da doi');

$doc = function($p) use ($goc){ return file_get_contents($goc . $p); };
ok(mb_strpos($doc('app/controllers/admin/Garages.php'), "'Quản lý công ty'") !== false, 'Tieu de man: "Quản lý công ty"');
ok(mb_strpos($doc('app/views/admin/garages/lists.php'), 'Mã công ty') !== false, 'Cot "Mã công ty"');
/* Nhãn ghép từ biến ("Thêm " . $labelOne) — bộ quét ở trên coi chuỗi một từ viết
   thường là mã nội bộ nên không thấy; nút "Thêm gara" đã sót đúng chỗ này. */
ok(preg_match('~private \$labelOne\s*=\s*\'công ty\'~u', $doc('app/controllers/admin/Garages.php')) === 1,
   'Nhan ghep tu bien: nut "Thêm công ty", tieu de "Sửa công ty"');
ok(mb_strpos($doc('app/views/admin/garages/edit.php'), 'Tài khoản chủ công ty') !== false, 'Khoi "Tài khoản chủ công ty"');
ok(mb_strpos($doc('app/controllers/admin/Settings.php'), "'Cấu hình công ty'") !== false, 'Man cau hinh: "Cấu hình công ty"');
ok(mb_strpos($doc('app/views/layouts/admin/header.php'), 'Công ty của tài khoản') !== false, 'Thanh tren: "Công ty của tài khoản"');
ok(mb_strpos($doc('app/views/admin/users/add.php'), '— Chọn công ty —') !== false, 'Nguoi dung: "— Chọn công ty —"');
ok(mb_strpos($doc('app/errors/gara-khong-ton-tai.php'), 'Không tìm thấy công ty') !== false, 'Trang loi ten mien: "Không tìm thấy công ty"');

// ---------------------------------------------------------------------------
section('Nghia nganh nghe + ma noi bo GIU NGUYEN');

$st = $doc('app/views/admin/settings/form.php');
ok(strpos($st, 'value="gara"') !== false, 'Gia tri luu linh_vuc van la "gara" (doi la dao lua chon cua gara cu)');
ok(mb_strpos($st, 'Sửa chữa, bảo dưỡng gara') !== false, 'Nhan linh vuc khach dat: "Sửa chữa, bảo dưỡng gara"');
ok(strpos($doc('app/views/admin/quotations/add.php'), 'data-nguon="gara"') !== false, 'Ma nguon bao gia data-nguon="gara" giu nguyen');
ok(mb_strpos($doc('app/views/layouts/storefront/master.php'), 'thiết bị gara ô tô') !== false, 'Slogan "thiết bị gara ô tô" giu nguyen');

// ---------------------------------------------------------------------------
try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}
section('Ten man hinh trong CSDL (menu trai lay tu bang modules)');

$ten = $pdo->query("SELECT link, name FROM modules WHERE link IN ('garages', 'garage-catalog')")->fetchAll(PDO::FETCH_KEY_PAIR);
ok(isset($ten['garages']) && $ten['garages'] === 'Quản lý công ty', 'modules garages = "Quản lý công ty"', json_encode($ten, JSON_UNESCAPED_UNICODE));
ok(isset($ten['garage-catalog']) && $ten['garage-catalog'] === 'Danh mục của công ty', 'modules garage-catalog = "Danh mục của công ty"');
$conGara = (int) $pdo->query("SELECT COUNT(*) FROM modules WHERE name LIKE '%gara%'")->fetchColumn();
ok($conGara === 0, 'Khong con ten man hinh nao chua chu "gara"', "con $conGara");

exit(summary());

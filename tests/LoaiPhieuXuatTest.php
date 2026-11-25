<?php
/**
 * Test LOẠI PHIẾU XUẤT KHO.
 *
 * Chạy:  C:\xampp\php\php.exe tests\LoaiPhieuXuatTest.php
 *
 * Khách ghi 25/11/2026 ở dòng "Phiếu xuất kho trả lại NCC": "thêm xuất bảo
 * hành, xuất dùng nội bộ". Trước đây chỉ có Xuất bán / Xuất khác / Xuất trả NCC
 * — hàng đem đi bảo hành cho khách hay lấy dùng trong xưởng đều phải ghi là
 * "Xuất khác", cuối tháng không tách ra được.
 *
 * Kèm một lỗi tìm thấy lúc rà: đặt hàng nội bộ (gara đặt kho tổng) ghi phiếu
 * xuất với loại 'ban' — không có trong danh sách loại. Danh sách phiếu xuất
 * hiện chữ "ban" trần trụi, mở sửa thì ô Loại nhảy về lựa chọn đầu tiên.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/GoodsIssuesModel.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Co du loai phieu xuat khach can');

$t = GoodsIssuesModel::$types;
ok(isset($t['xuat_bao_hanh']) && mb_stripos($t['xuat_bao_hanh'], 'bảo hành') !== false,
   'Co loai "Xuat bao hanh"', json_encode($t, JSON_UNESCAPED_UNICODE));
ok(isset($t['xuat_noi_bo']) && mb_stripos($t['xuat_noi_bo'], 'nội bộ') !== false,
   'Co loai "Xuat dung noi bo"', json_encode($t, JSON_UNESCAPED_UNICODE));
foreach (['xuat_ban', 'xuat_khac', 'xuat_tra'] as $k){
    ok(isset($t[$k]), "Van giu loai cu `$k` (phieu cu khong mat nhan)");
}
foreach (array_keys($t) as $k){
    ok(strlen($k) <= 20, "Ma loai `$k` vua cot issue_type VARCHAR(20)");
}

// ---------------------------------------------------------------------------
section('Moi cho GHI phieu xuat deu dung ma co trong danh sach');

$lot = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($goc . 'app', FilesystemIterator::SKIP_DOTS));
foreach ($it as $f){
    if (substr($f->getFilename(), -4) !== '.php') continue;
    $code = codeOnly($f->getPathname());
    if (preg_match_all("~'issue_type'\s*=>\s*'([a-z_]+)'~", $code, $m)){
        foreach ($m[1] as $ma) if (!isset($t[$ma])) $lot[] = basename($f->getPathname()) . ": '$ma'";
    }
}
ok(empty($lot), "Khong cho nao ghi ma loai la (vd. 'ban' o dat hang noi bo)", implode(', ', $lot));

// ---------------------------------------------------------------------------
try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

section('Du lieu: khong con phieu xuat mang loai la');

$dsMa = implode(',', array_map([$pdo, 'quote'], array_keys($t)));
$la = $pdo->query("SELECT issue_type, COUNT(*) c FROM goods_issues WHERE issue_type NOT IN ($dsMa)
                   GROUP BY issue_type")->fetchAll(PDO::FETCH_KEY_PAIR);
ok(empty($la), 'Moi phieu xuat trong CSDL mang ma loai co nhan', json_encode($la));

exit(summary());

<?php
/**
 * LOẠI PHIẾU NHẬP — mọi giá trị trong CSDL phải có tên trong $types.
 *
 * Chạy:  C:\xampp\php\php.exe tests\LoaiPhieuNhapTest.php
 *
 * SINH RA TỪ MỘT LỖI THẬT (phát hiện 29/10/2026). Màn "Đặt hàng kho tổng" sinh
 * phiếu nhập bên gara với `receipt_type` = 'mua' — một giá trị KHÔNG CÓ trong
 * GoodsReceiptsModel::$types. Hai hậu quả, cả hai đều im lặng:
 *
 *   1. Màn Phiếu nhập in ra chữ trần "mua": view rơi về
 *      `{{$types[...] ?? $item['receipt_type']}}` nên không chỗ nào vỡ.
 *
 *   2. NẶNG HƠN: báo cáo "Gara còn nợ NCC" lọc đúng 'nhap_mua' nên BỎ SÓT toàn
 *      bộ phiếu loại 'mua'. Gara nợ kho tổng thật mà báo cáo không thấy —
 *      lệch 3.460.000 đ trên máy chạy thử.
 *
 * ĐÂY LÀ KIỂU LỖI KHÓ THẤY NHẤT: không ngoại lệ, không dòng log, chỉ là một
 * con số nhỏ đi. Nên test khoá cả hai đầu — danh sách nhãn và dữ liệu thật.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Ma nguon — khong noi tay mot loai la');

/* Model cua du an khong nam trong autoload cua composer (vendor/ chi co
   autoload.php rong) — phai nap tay truoc khi doc hang tinh. */
\App\core\Load::model('GoodsReceiptsModel');
$ds = \GoodsReceiptsModel::$types;
ok(!empty($ds) && is_array($ds), 'Doc duoc GoodsReceiptsModel::$types (' . count($ds) . ' loai)');
foreach (['nhap_mua', 'nhap_khac', 'nhap_tra', 'ton_dau_ky'] as $k){
    ok(isset($ds[$k]), "Co loai `$k`");
}

/* MỌI CHỖ GHI `receipt_type` phải dùng một khoá có trong $types. Quét cả app:
   đây chính là cách lỗi cũ lọt — một chỗ gõ 'mua' trong khi cả hệ thống dùng
   'nhap_mua', mà PHP không có gì để báo. */
$la = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($goc . 'app', FilesystemIterator::SKIP_DOTS));
foreach ($rii as $f){
    if ($f->getExtension() !== 'php') continue;
    $src = codeOnly($f->getPathname());
    if (!preg_match_all("~['\"]receipt_type['\"]\s*=>\s*'([a-z_]+)'~", $src, $m)) continue;
    foreach ($m[1] as $gt){
        if (!isset($ds[$gt])) $la[] = $f->getFilename() . ": '" . $gt . "'";
    }
}
ok(empty($la),
   'Moi cho ghi `receipt_type` deu dung khoa co trong $types',
   'Gia tri la: ' . implode(', ', $la)
   . ' — loai khong co trong $types thi man hinh in chu tran va bao cao bo sot');

/* Bao cao no NCC loc theo 'nhap_mua'. Khoa lai de ai doi ten loai thi thay
   ngay la phai sua ca hai cho. */
$gm = codeOnly($goc . 'app/models/GoodsReceiptsModel.php');
ok(preg_match("~tienMuaTheoNcc~", $gm) === 1, 'Van con tienMuaTheoNcc()');
ok(substr_count($gm, "'nhap_mua'") >= 2,
   "Bao cao no NCC va danh sach nhan cung dung chuoi 'nhap_mua'");

// ---------------------------------------------------------------------------
section('Du lieu that — khong con loai nao la');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$coDb = [];
foreach ($pdo->query("SELECT `receipt_type`, COUNT(*) AS c FROM `goods_receipts` GROUP BY `receipt_type`") as $r){
    $coDb[$r['receipt_type']] = (int) $r['c'];
}
$laDb = [];
foreach ($coDb as $gt => $n){
    if (!isset($ds[$gt])) $laDb[] = "$gt ($n phieu)";
}
ok(empty($laDb),
   'CSDL khong con loai phieu nhap nao ngoai danh sach'
   . (empty($coDb) ? ' (chua co phieu nao)' : ': ' . implode(', ', array_keys($coDb))),
   'Loai la: ' . implode(', ', $laDb) . ' — chay migration 000100');

/* Chot rieng cho lan hong cu: 'mua' phai bien han. */
ok(!isset($coDb['mua']),
   "Khong con phieu nao loai 'mua'",
   'Con ' . (isset($coDb['mua']) ? $coDb['mua'] : 0) . ' phieu — chung dang bi bao cao no NCC bo sot');

// ---------------------------------------------------------------------------
section('Dat hang kho tong sinh dung loai');

$dh = codeOnly($goc . 'app/models/DatHangNoiBoModel.php');
if (preg_match("~['\"]receipt_type['\"]\s*=>\s*'([a-z_]+)'~", $dh, $m)){
    ok($m[1] === 'nhap_mua',
       "Phieu nhap ben gara mang loai 'nhap_mua' (dang la '{$m[1]}')",
       'Gara mua cua kho tong la nhap mua — kho tong la mot NCC cua gara');
} else {
    ok(false, 'Doc duoc loai phieu nhap ma DatHangNoiBoModel sinh ra');
}

/* Migration sua du lieu phai CHAY LAI DUOC: lan hai khong con dong nao khop. */
$mg = glob($goc . 'database/migrations/*_sua_loai_phieu_nhap_noi_bo.php');
ok(count($mg) === 1, 'Co migration 000100 sua du lieu cu');
if (count($mg) === 1){
    $src = file_get_contents($mg[0]);
    ok(strpos($src, "WHERE `receipt_type` = 'mua'") !== false,
       'Migration chi dong toi dong loai `mua`, khong dung toi loai khac');
}

exit(summary());

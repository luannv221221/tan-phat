<?php
/**
 * Chạy toàn bộ test.
 *
 *   C:\xampp\php\php.exe tests\run.php
 *
 * Exit code 0 = tất cả pass, 1 = có test fail (dùng được cho CI sau này).
 */

$tests = [
    'QueryBuilderTest.php'        => 'B1 — Sinh SQL + chan injection (khong can DB)',
    'DatabaseIntegrationTest.php' => 'B1/B2 — PDO that: insert/update/delete/transaction',
    'ModelsSmokeTest.php'         => 'Model that cua app khong bi vo',
    'EnvTest.php'                 => 'H4/M7/M8 — .env + Connection dung chung',
    'HashTest.php'                => 'B4 — bcrypt + nang cap md5 + token ngau nhien',
    'SecurityTest.php'            => 'H2/H3 — CSRF + session ngoai webroot',
    'HelpersTest.php'             => 'slugify() + _WEB_URL khong co gach doi',
    'MigratorTest.php'            => 'H5 — migration runner + rollback',
    'MySqlLiveTest.php'           => 'END-TO-END tren MySQL THAT (tu skip neu khong co DB)',
    'CarCatalogTest.php'          => 'NGHIEP VU — cay danh muc xe (MySQL that)',
    'PartsTest.php'               => 'NGHIEP VU — phu tung + lien ket xe (MySQL that)',
    'CarFilterTest.php'           => 'NGHIEP VU — bo loc xe o header (MySQL that)',
    'OrderStockTest.php'          => 'NGHIEP VU — don hang tru/cong kho theo trang thai',
    'RememberLoginTest.php'       => 'BAO MAT — ghi nho dang nhap admin',
    'StockGuardTest.php'          => 'NGHIEP VU — chot an toan cua kho (ton am, transaction long)',
    'ItemTypeTest.php'            => 'NGHIEP VU — phan loai phu tung / thiet bi / dich vu',
    'PhanTrangTest.php'           => 'GIAO DIEN — phan trang + o chon so dong/trang',
    'DichVuTest.php'              => 'NGHIEP VU — man hinh Dich vu + tab bao gia',
    'InAnTest.php'                => 'IN AN — bieu mau bao gia / hoa don + so tien bang chu',
    'DauTrangDinhTest.php'        => 'GIAO DIEN — dau trang dinh khi cuon (storefront)',
    'SanPhamLienQuanTest.php'     => 'STOREFRONT — goi y san pham lien quan o trang chi tiet',
    'MaHoaHtmlTest.php'           => 'DU LIEU — luu nguyen van, escape luc in (loi &#38;#38;)',
    'QuanLyModuleTest.php'        => 'PHAN QUYEN — man hinh dang ky module',
    'VideoTrangChuTest.php'       => 'STOREFRONT — khoi Video o trang chu',
    'ChepBaoGiaTest.php'          => 'BAN HANG — chep dong hang tu bao gia cu',
    'ChepHoaDonTest.php'          => 'BAN HANG — chep dong hang tu chung tu cu (hoa don ban)',
    'XeCuaKhachTest.php'          => 'CSKH — xe cua khach (bien so, so km) + tra theo bien so',
    'NhieuGaraTest.php'           => 'HE THONG — nhieu gara (tang 1: khai niem gara)',
    'DanhMucGaraTest.php'         => 'HANG HOA — nhieu gara (tang 2: danh muc rieng cua gara)',
    'NguonBaoGiaTest.php'         => 'BAN HANG — nhieu gara (tang 3: chon nguon danh muc khi lap bao gia)',
    'ThemKhachHangTest.php'       => 'CSKH — them khach vang lai tai gara',
];

$php      = PHP_BINARY;
$failed   = [];
$totalP   = 0;
$totalF   = 0;

/* Khoi test tu bo qua khi thieu dieu kien (Apache chua chay, chua co du lieu...).
   PHAI dem va bao ra: truoc day bo qua trong im lang nen chay khi Apache tat van
   ra "tat ca PASS", trong khi 7 khang dinh cua CarFilterTest da khong chay — ke ca
   "khong lo du lieu ton kho" va "bang parts VAN CON sau khi thu injection".
   Xanh-co-bo-qua phai nhin khac xanh-chay-du. */
$skipped  = [];

foreach ($tests as $file => $desc){
    $path = __DIR__ . DIRECTORY_SEPARATOR . $file;

    echo str_repeat('=', 60) . "\n";
    echo "$file — $desc\n";
    echo str_repeat('=', 60) . "\n";

    $output = [];
    $code   = 0;
    exec(escapeshellarg($php) . ' ' . escapeshellarg($path) . ' 2>&1', $output, $code);

    $text = implode("\n", $output);
    echo $text . "\n\n";

    if (preg_match('/PASS:\s*(\d+)\s+FAIL:\s*(\d+)/', $text, $m)){
        $totalP += (int)$m[1];
        $totalF += (int)$m[2];
    }

    if (preg_match_all('/\[SKIP\]\s*(.+)/', $text, $sm)){
        foreach ($sm[1] as $ly) $skipped[] = $file . ' — ' . trim($ly);
    }

    if ($code !== 0) $failed[] = $file;
}

echo str_repeat('#', 60) . "\n";
echo "TONG KET: PASS $totalP | FAIL $totalF";
if (!empty($skipped)) echo " | BO QUA " . count($skipped) . " khoi";
echo "
";

if (!empty($skipped)){
    echo "
CAC KHOI BI BO QUA (khong chay, khong tinh la pass):
";
    foreach ($skipped as $k) echo "  - $k
";
    echo "
";
}

if (empty($failed)){
    echo empty($skipped)
        ? "Tat ca test PASS.
"
        : "Khong co test nao FAIL, NHUNG co khoi bi bo qua — xem danh sach tren.
";
} else {
    echo "Test FAIL: " . implode(', ', $failed) . "\n";
}
echo str_repeat('#', 60) . "\n";

exit(empty($failed) ? 0 : 1);

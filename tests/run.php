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
];

$php      = PHP_BINARY;
$failed   = [];
$totalP   = 0;
$totalF   = 0;

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

    if ($code !== 0) $failed[] = $file;
}

echo str_repeat('#', 60) . "\n";
echo "TONG KET: PASS $totalP | FAIL $totalF\n";

if (empty($failed)){
    echo "Tat ca test PASS.\n";
} else {
    echo "Test FAIL: " . implode(', ', $failed) . "\n";
}
echo str_repeat('#', 60) . "\n";

exit(empty($failed) ? 0 : 1);

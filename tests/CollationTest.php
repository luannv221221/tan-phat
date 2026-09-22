<?php
/**
 * Test COLLATION — mọi bảng dùng chung utf8mb4_unicode_ci.
 *
 * Chạy:  C:\xampp\php\php.exe tests\CollationTest.php
 *
 * CHUYỆN ĐÃ XẢY RA
 * Migration 000072 tạo bảng `vehicles` / `receptions` chỉ ghi
 * `DEFAULT CHARSET=utf8mb4`, quên ghi collation. MySQL 8 trên máy local tự gán
 * `utf8mb4_0900_ai_ci`. Export từ local dán lên server (MySQL 5.7 / MariaDB)
 * báo `#1273 Unknown collation: 'utf8mb4_0900_ai_ci'` và dừng giữa chừng.
 *
 * Trên máy local mọi thứ vẫn chạy bình thường — lỗi chỉ lộ ra lúc deploy, nên
 * phải có test chặn từ trước.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Migration tao bang phai ghi collation');

$thieu = [];
foreach (glob($goc . 'database/migrations/*.php') as $f){
    $src = file_get_contents($f);
    /* Mỗi câu CREATE TABLE phải có COLLATE ngay trong câu đó — đếm theo câu,
       không đếm theo file: một file tạo hai bảng mà chỉ ghi collation cho một
       bảng thì vẫn lỗi. */
    if (!preg_match_all('~CREATE TABLE(.*?)ENGINE=InnoDB([^"\']*)~si', $src, $mm)) continue;
    foreach ($mm[2] as $i => $duoi){
        if (stripos($duoi, 'COLLATE') === false) $thieu[] = basename($f);
    }
}
ok(empty($thieu), 'Moi cau CREATE TABLE trong migration deu ghi COLLATE',
   'Quen ghi thi MySQL 8 tu gan utf8mb4_0900_ai_ci — server cu khong co: ' . implode(', ', array_unique($thieu)));

ok(strpos(file_get_contents($goc . 'tools/xuat-csdl.php'), "str_replace('utf8mb4_0900_ai_ci', 'utf8mb4_unicode_ci'") !== false,
   'Cong cu dump toan bo tu doi collation rieng cua MySQL 8',
   'Dump nguyen van SHOW CREATE TABLE tu MySQL 8 thi dan len MySQL 5.7 / MariaDB se vo');

$tool = file_get_contents($goc . 'tools/xuat-sql-thay-doi.php');
ok(strpos($tool, 'utf8mb4_0900_ai_ci') === false,
   'Cong cu xuat SQL trien khai khong chua utf8mb4_0900_ai_ci');
ok(substr_count($tool, 'DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;') >= 2,
   'Cau tao bang xe / phieu tiep nhan trong SQL trien khai ghi ro collation');

// ---------------------------------------------------------------------------
section('CSDL that');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

/* Mặc định của CSDL: bảng tạo SAU mà không ghi collation (kể cả tạo tay
   trong phpMyAdmin) sẽ nhận collation này. Để 0900 là lần export kế tiếp lại vỡ. */
$macDinh = (string) $pdo->query("SELECT @@collation_database")->fetchColumn();
ok($macDinh === 'utf8mb4_unicode_ci', 'Collation MAC DINH cua CSDL la utf8mb4_unicode_ci',
   "Dang la $macDinh — bang tao sau se lai nhan collation nay");

$lech = $pdo->query("SELECT TABLE_NAME, TABLE_COLLATION FROM information_schema.TABLES
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'
                       AND TABLE_COLLATION <> 'utf8mb4_unicode_ci'")->fetchAll(PDO::FETCH_ASSOC);
ok(empty($lech), 'Moi bang dung utf8mb4_unicode_ci',
   'Lech: ' . implode(', ', array_map(function($r){ return $r['TABLE_NAME'] . '=' . $r['TABLE_COLLATION']; }, $lech)));

$cotLech = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS
                              WHERE TABLE_SCHEMA = DATABASE() AND COLLATION_NAME IS NOT NULL
                                AND COLLATION_NAME <> 'utf8mb4_unicode_ci'")->fetchColumn();
ok($cotLech === 0, 'Moi cot chu dung utf8mb4_unicode_ci',
   "Con $cotLech cot lech — so chuoi giua hai bang se bao 'Illegal mix of collations'");

/* So biển số giữa hai bảng có collation khác nhau là chỗ lỗi hay lộ ra nhất */
try {
    $pdo->query("SELECT COUNT(*) FROM vehicles v JOIN quotations q ON q.bien_so_chuan = v.bien_so_chuan")->fetchColumn();
    ok(true, 'So bien so giua `vehicles` va `quotations` khong bao loi collation');
} catch (\PDOException $e){
    ok(false, 'So bien so giua `vehicles` va `quotations` khong bao loi collation', $e->getMessage());
}

exit(summary());

<?php
/**
 * XUẤT TOÀN BỘ CSDL ra file .sql — thay cho mysqldump.
 *
 *   C:\xampp\php\php.exe tools\xuat-csdl.php
 *   C:\xampp\php\php.exe tools\xuat-csdl.php --ra=deploy\ban-sao.sql
 *   C:\xampp\php\php.exe tools\xuat-csdl.php --bo-log      (bỏ bảng nhật ký truy cập)
 *
 * VÌ SAO KHÔNG DÙNG mysqldump: trên máy này `mysql.exe` và `mysqldump.exe`
 * không kết nối được (đã thử nhiều lần, luôn hỏng). PDO thì chạy tốt, nên
 * đọc bảng bằng PDO rồi tự sinh câu lệnh.
 *
 * File sinh ra nhập thẳng vào phpMyAdmin được: có DROP TABLE + CREATE TABLE
 * lấy nguyên văn từ `SHOW CREATE TABLE`, nên khớp tuyệt đối kiểu cột, chỉ mục,
 * khoá ngoại — không phải đoán lại.
 *
 * BA CHỐT QUAN TRỌNG:
 *
 *   1. SET FOREIGN_KEY_CHECKS = 0 bao quanh cả file. Không có nó thì thứ tự
 *      bảng phải đúng theo chiều khoá ngoại, mà `parts` <-> `garages` <->
 *      `warehouses` tham chiếu vòng nhau — sắp kiểu gì cũng có cái hỏng.
 *
 *   2. Giá trị bọc bằng PDO::quote(), KHÔNG nối chuỗi tay. Dữ liệu thật có
 *      dấu nháy đơn (tên khách "Cty TNHH 'ABC'"), nối tay là vỡ file.
 *
 *   3. NULL phải ra chữ NULL không nháy. quote(null) cho ra chuỗi rỗng '' —
 *      khác hẳn: cột `email` NULL nghĩa là khách vãng lai, còn '' thì đụng
 *      khoá UNIQUE ngay ở khách thứ hai.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

/* --- Tham số dòng lệnh --- */
$raFile = __DIR__ . '/../deploy/tanphat_php.sql';
$boLog  = false;
foreach ($argv as $a){
    if (strpos($a, '--ra=') === 0) $raFile = substr($a, 5);
    if ($a === '--bo-log')         $boLog  = true;
}
if (!preg_match('~^([a-zA-Z]:)?[\\\\/]~', $raFile)){
    $raFile = __DIR__ . '/../' . $raFile;   // đường dẫn tương đối -> tính từ gốc dự án
}

/* Bảng nhật ký: to, đổi liên tục, và không ai cần khi dựng lại hệ thống. */
$bangLog = ['visits'];

$db = new PDO(
    'mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
    _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$bangs = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo 'CSDL: ' . _DB . ' — ' . count($bangs) . " bang\n";
echo 'Ghi ra: ' . $raFile . "\n\n";

@mkdir(dirname($raFile), 0777, true);
$f = fopen($raFile, 'wb');
if (!$f){ echo "Khong mo duoc file de ghi.\n"; exit(1); }

$ra = function($s) use ($f){ fwrite($f, $s); };

$ra("-- =====================================================================\n");
$ra('-- ' . _DB . " — ban sao toan bo CSDL\n");
$ra('-- Sinh luc ' . date('Y-m-d H:i:s') . " bang tools/xuat-csdl.php\n");
$ra("--\n");
$ra("-- Cach dung: phpMyAdmin > chon CSDL > tab Import > chon file nay.\n");
$ra("-- File nay XOA VA TAO LAI moi bang (DROP TABLE IF EXISTS), tuc la GHI DE\n");
$ra("-- toan bo du lieu dang co o dich. Sao luu truoc khi nhap vao may dang chay.\n");
$ra("-- =====================================================================\n\n");

$ra("SET NAMES utf8mb4;\n");
$ra("SET FOREIGN_KEY_CHECKS = 0;\n");
$ra("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n");

$tongDong = 0;
$boQua    = [];

foreach ($bangs as $bang){
    $ra("-- ---------------------------------------------------------------------\n");
    $ra("-- $bang\n");
    $ra("-- ---------------------------------------------------------------------\n");
    $ra("DROP TABLE IF EXISTS `$bang`;\n");

    // Lấy nguyên văn định nghĩa bảng — khớp tuyệt đối, không phải dựng lại
    $tao = $db->query("SHOW CREATE TABLE `$bang`")->fetch(PDO::FETCH_NUM);
    $ra($tao[1] . ";\n\n");

    if ($boLog && in_array($bang, $bangLog, true)){
        $ra("-- (bo qua du lieu: bang nhat ky, chay voi --bo-log)\n\n");
        $boQua[] = $bang;
        continue;
    }

    $cot = $db->query("SHOW COLUMNS FROM `$bang`")->fetchAll(PDO::FETCH_COLUMN);
    $ten = '`' . implode('`,`', $cot) . '`';

    /* Đọc theo lô, không fetchAll cả bảng: `visits` hơn nghìn dòng còn nhẹ,
       nhưng bảng ảnh hay bài viết có cột TEXT dài thì nạp hết vào RAM là phí. */
    $st  = $db->query("SELECT * FROM `$bang`");
    $dem = 0;
    $lo  = [];

    while ($r = $st->fetch(PDO::FETCH_ASSOC)){
        $gt = [];
        foreach ($cot as $c){
            $v = $r[$c];
            // NULL phải ra chữ NULL — quote(null) cho ra '' , khác nghĩa hoàn toàn
            $gt[] = $v === null ? 'NULL' : $db->quote((string) $v);
        }
        $lo[] = '(' . implode(',', $gt) . ')';
        $dem++;

        // Gom 100 dòng một câu INSERT: nhanh hơn hẳn mỗi dòng một câu, mà vẫn
        // đủ ngắn để phpMyAdmin không kêu gói tin quá lớn.
        if (count($lo) >= 100){
            $ra("INSERT INTO `$bang` ($ten) VALUES\n" . implode(",\n", $lo) . ";\n");
            $lo = [];
        }
    }
    if (!empty($lo)){
        $ra("INSERT INTO `$bang` ($ten) VALUES\n" . implode(",\n", $lo) . ";\n");
    }

    $ra("\n");
    $tongDong += $dem;
    printf("  %-26s %6d dong\n", $bang, $dem);
}

$ra("SET FOREIGN_KEY_CHECKS = 1;\n");
$ra("\n-- Het.\n");
fclose($f);

echo "\n" . str_repeat('=', 52) . "\n";
printf("Xong: %d bang, %s dong, %s\n",
    count($bangs), number_format($tongDong),
    number_format(filesize($raFile) / 1024, 1) . ' KB');
if (!empty($boQua)) echo 'Bo qua du lieu cua: ' . implode(', ', $boQua) . "\n";
echo str_repeat('=', 52) . "\n";

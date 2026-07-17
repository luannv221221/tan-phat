<?php
/**
 * Test cho Migrator (H5).
 *
 * Chạy:  C:\xampp\php\php.exe tests\MigratorTest.php
 *
 * Dung migration gia (SQLite-compatible) trong thu muc tam de test CO CHE chay,
 * khong dung migration that (viet bang cu phap MySQL).
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/_FakeConnection.php';
require_once __DIR__ . '/../core/QueryBuilder.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Migration.php';
require_once __DIR__ . '/../core/Migrator.php';

use App\core\Database;
use App\core\Migrator;

echo "PHP " . PHP_VERSION . " | sqlite in-memory\n";

// ---- Dung thu muc migration tam ----
$dir = sys_get_temp_dir() . '/mig_test_' . getmypid();
@mkdir($dir, 0777, true);

function writeMigration($dir, $name, $up, $down){
    $php = "<?php\nuse App\\core\\Migration;\nreturn new class extends Migration {\n"
         . "    public function up(){ \$this->run(\"$up\"); }\n"
         . "    public function down(){ \$this->run(\"$down\"); }\n"
         . "};\n";
    file_put_contents($dir . '/' . $name . '.php', $php);
}

writeMigration($dir, '2026_01_01_000001_create_hang_xe',
    'CREATE TABLE hang_xe (id INTEGER PRIMARY KEY, ten TEXT)',
    'DROP TABLE hang_xe');

writeMigration($dir, '2026_01_01_000002_create_dong_xe',
    'CREATE TABLE dong_xe (id INTEGER PRIMARY KEY, hang_id INTEGER, ten TEXT)',
    'DROP TABLE dong_xe');

$db  = new Database();
$mig = new Migrator($db, $dir);

// ================================================================
section('Trang thai ban dau');

ok(count($mig->filesOnDisk()) === 2, 'Tim thay 2 file migration tren dia');
ok(count($mig->pending()) === 2, 'Ca 2 deu dang cho');
ok($mig->ranMigrations() === [], 'Chua co migration nao da chay');

// ================================================================
section('Chay migration (up)');

$n = $mig->up();
ok($n === 2, 'Chay 2 migration', "n=$n");

// Bang phai ton tai that
$tables = $db->getRaw("SELECT name FROM sqlite_master WHERE type='table'");
$names  = array_column($tables, 'name');
ok(in_array('hang_xe', $names, true), 'Bang hang_xe da duoc tao that');
ok(in_array('dong_xe', $names, true), 'Bang dong_xe da duoc tao that');
ok(in_array('migrations', $names, true), 'Bang migrations da duoc tao');

ok(count($mig->pending()) === 0, 'Khong con migration nao cho');
ok(count($mig->ranMigrations()) === 2, 'Ghi nhan 2 migration da chay');

// ================================================================
section('Chay lai — khong duoc chay trung');

$n2 = $mig->up();
ok($n2 === 0, 'Chay lai khong lam gi (idempotent)', "n=$n2");
ok(count($mig->ranMigrations()) === 2, 'Van chi 2 ban ghi, khong trung lap');

// ================================================================
section('Thu tu chay theo ten file');

$ran = $mig->ranMigrations();
ok($ran[0] === '2026_01_01_000001_create_hang_xe', 'Migration 001 chay TRUOC', $ran[0]);
ok($ran[1] === '2026_01_01_000002_create_dong_xe', 'Migration 002 chay SAU', $ran[1]);

// ================================================================
section('Them migration moi -> chi chay cai moi');

writeMigration($dir, '2026_01_01_000003_create_model_xe',
    'CREATE TABLE model_xe (id INTEGER PRIMARY KEY, ten TEXT)',
    'DROP TABLE model_xe');

ok(count($mig->pending()) === 1, 'Chi 1 migration moi dang cho');
$n3 = $mig->up();
ok($n3 === 1, 'Chi chay 1 migration moi', "n=$n3");

$tables = $db->getRaw("SELECT name FROM sqlite_master WHERE type='table'");
ok(in_array('model_xe', array_column($tables, 'name'), true), 'Bang model_xe da tao');

// ================================================================
section('Rollback batch gan nhat');

// Batch 2 chi co migration 003
$r = $mig->rollback();
ok($r === 1, 'Rollback 1 migration cua batch gan nhat', "r=$r");

$tables = $db->getRaw("SELECT name FROM sqlite_master WHERE type='table'");
$names  = array_column($tables, 'name');
ok(!in_array('model_xe', $names, true), 'Bang model_xe da bi xoa that');
ok(in_array('hang_xe', $names, true), 'Bang cua batch 1 KHONG bi dung toi');
ok(count($mig->pending()) === 1, 'Migration 003 quay lai trang thai cho');

// ================================================================
section('Rollback tiep -> ca batch 1');

$r2 = $mig->rollback();
ok($r2 === 2, 'Rollback ca 2 migration cua batch 1', "r=$r2");

$tables = $db->getRaw("SELECT name FROM sqlite_master WHERE type='table'");
$names  = array_column($tables, 'name');
ok(!in_array('hang_xe', $names, true), 'hang_xe da xoa');
ok(!in_array('dong_xe', $names, true), 'dong_xe da xoa');
ok($mig->ranMigrations() === [], 'Bang migrations rong');

$r3 = $mig->rollback();
ok($r3 === 0, 'Rollback khi khong con gi -> khong loi', "r=$r3");

// ================================================================
section('File migration hong -> bao loi ro rang');

file_put_contents($dir . '/2026_01_01_000004_hong.php', "<?php\nreturn 'khong phai migration';\n");
$threw = false;
try { $mig->up(); } catch (\RuntimeException $e){
    $threw = strpos($e->getMessage(), 'phai `return`') !== false;
}
ok($threw, 'File khong return Migration -> nem RuntimeException voi thong bao ro');

@unlink($dir . '/2026_01_01_000004_hong.php');

// ================================================================
section('Migration THAT — kiem tra cu phap va noi dung');

$realDir = __DIR__ . '/../database/migrations';
ok(is_dir($realDir), 'Thu muc database/migrations ton tai');

$realFiles = glob($realDir . '/*.php');
ok(count($realFiles) >= 2, 'Co it nhat 2 migration that', 'so file: ' . count($realFiles));

foreach ($realFiles as $f){
    $name = basename($f);

    // Lint cu phap
    $out = [];
    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($f) . ' 2>&1', $out);
    ok(strpos(implode(' ', $out), 'No syntax errors') !== false, "$name: cu phap dung");

    // Phai return object Migration
    $obj = require $f;
    ok($obj instanceof App\core\Migration, "$name: return object Migration");
}

// Migration 002 phai sua cot password thanh 255
$fixPwd = file_get_contents($realDir . '/2026_07_17_000002_fix_password_column_length.php');
ok(strpos($fixPwd, 'VARCHAR(255)') !== false,
   'Migration sua password: dat VARCHAR(255) (bcrypt dai 60, cot cu chi 50)');

// Baseline phai tao password 255 ngay tu dau
$base = file_get_contents($realDir . '/2026_07_17_000001_create_base_tables.php');
ok(preg_match('/`password`\s+VARCHAR\(255\)/i', $base) === 1,
   'Baseline tao cot password VARCHAR(255) ngay tu dau');
ok(strpos($base, 'IF NOT EXISTS') !== false,
   'Baseline dung IF NOT EXISTS (chay duoc tren DB dang co du lieu)');
ok(strpos($base, 'utf8mb4') !== false, 'Baseline dung utf8mb4');

// ================================================================
section('Bcrypt dai bao nhieu? (chung minh varchar(50) la loi)');

$h = password_hash('bat_ky_mat_khau_nao', PASSWORD_DEFAULT);
ok(strlen($h) === 60, 'Hash bcrypt dai dung 60 ky tu', 'len=' . strlen($h));
ok(strlen($h) > 50, 'Dai HON varchar(50) cua schema cu => se bi cat cut tren MySQL');
ok(strlen(md5('x')) === 32, 'md5 chi 32 ky tu — vi sao schema cu de 50 la du');

// Chung minh hau qua: hash bi cat con 50 thi verify that bai
$cut = substr($h, 0, 50);
ok(password_verify('bat_ky_mat_khau_nao', $cut) === false,
   'Hash bi cat con 50 ky tu -> password_verify() THAT BAI => khong ai dang nhap duoc');

// ---- Don dep ----
array_map('unlink', glob($dir . '/*.php'));
@rmdir($dir);

exit(summary());

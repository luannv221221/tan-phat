<?php
/**
 * CLI chạy migration.
 *
 *   C:\xampp\php\php.exe migrate.php              -> chay cac migration dang cho
 *   C:\xampp\php\php.exe migrate.php status       -> xem trang thai
 *   C:\xampp\php\php.exe migrate.php rollback     -> rollback batch gan nhat
 *   C:\xampp\php\php.exe migrate.php make ten_gi_do -> sinh file migration moi
 *
 * Exit code 0 = thanh cong, 1 = loi (dung duoc cho CI).
 */

if (PHP_SAPI !== 'cli'){
    http_response_code(403);
    die('migrate.php chi chay duoc tu dong lenh (CLI).');
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

// Nạp các file config trong configs/ (session, app...)
$allConfigs = scandir(__DIR__ . '/configs');
foreach ($allConfigs as $configFile){
    if ($configFile !== '.' && $configFile !== '..'){
        require_once __DIR__ . '/configs/' . $configFile;
    }
}

use App\core\Database;
use App\core\Migrator;

$command = isset($argv[1]) ? $argv[1] : 'migrate';
$arg     = isset($argv[2]) ? $argv[2] : null;

echo "Migration — DB: " . _DB . " @ " . _HOST . "\n";
echo str_repeat('-', 50) . "\n";

// `make` khong can ket noi DB
if ($command === 'make'){
    if (empty($arg)){
        echo "Thieu ten. Vi du: php migrate.php make create_xe_table\n";
        exit(1);
    }
    // Migrator can Database o constructor -> tao ket noi that
}

try {
    $db       = new Database();
    $migrator = new Migrator($db);

    switch ($command){
        case 'migrate':
            $migrator->up();
            break;

        case 'rollback':
            $migrator->rollback();
            break;

        case 'status':
            $migrator->status();
            break;

        case 'make':
            $migrator->make($arg);
            break;

        default:
            echo "Lenh khong hop le: $command\n";
            echo "Cac lenh: migrate | rollback | status | make <ten>\n";
            exit(1);
    }

    foreach ($migrator->output() as $line){
        echo $line . "\n";
    }

    exit(0);

} catch (\Throwable $e){
    echo "\nLOI: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ':' . $e->getLine() . "\n";
    exit(1);
}

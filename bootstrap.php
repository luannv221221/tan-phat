<?php
//Một số ứng dụng đặt là file init.php

require_once 'vendor/autoload.php'; //Import autoload composer

//Cần phải import file config.php đầu tiên
require_once 'config.php';

/*
 * M11 — Bật/tắt hiển thị lỗi theo APP_DEBUG trong .env.
 *
 * Bản cũ đặt `php_value error_reporting -1` trong .htaccess => luôn bật, kể cả production,
 * và nằm ở một chỗ khác hẳn nơi cấu hình ứng dụng. Nay gom về một nguồn duy nhất.
 *
 * display_errors=0 ở production: lỗi vẫn được GHI LOG, chỉ không in ra trình duyệt.
 */
if (_DEBUG){
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);      // vẫn báo cáo đầy đủ...
    ini_set('display_errors', '0'); // ...nhưng không in ra cho người dùng thấy
}
ini_set('log_errors', '1');

/*
 * Bắt mọi exception chưa được xử lý.
 *
 * Database::query() nay NÉM exception thay vì die() — để transaction() rollback được
 * và để caller tự xử lý. Đổi lại, cần một lưới cuối ở đây, nếu không exception
 * lọt ra ngoài thành fatal error in stack trace (kèm SQL, đường dẫn file) ra trình duyệt.
 */
set_exception_handler(function(\Throwable $e){

    error_log('Uncaught: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());

    if (PHP_SAPI === 'cli'){
        fwrite(STDERR, "\nLOI: " . $e->getMessage() . "\n"
             . $e->getFile() . ':' . $e->getLine() . "\n");
        exit(1);
    }

    http_response_code(500);

    if (defined('_DEBUG') && _DEBUG === true){
        echo '<h3>Loi ung dung</h3>';
        echo '<pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
        echo '<p>' . htmlspecialchars($e->getFile() . ':' . $e->getLine(), ENT_QUOTES, 'UTF-8') . '</p>';
        exit;
    }

    echo '<h3>Da co loi xay ra</h3>';
    echo '<p>Vui long thu lai hoac lien he quan tri vien.</p>';
    exit;
});

//Load tất cả các file config trong folder configs
$allConfigs = scandir('configs');
if (!empty($allConfigs)){
    foreach ($allConfigs as $configFile){
        if ($configFile!='.' && $configFile!='..'){
            require_once 'configs/'.$configFile;
        }
    }
}

//require_once 'app/helpers/functions.php'; //Import file functions.php trong helper

//require_once 'core/Route.php'; //Import file Route.php

require_once 'routes/web.php'; //Import file route config (web.php)

require_once 'routes/api.php'; //Import file route config (api.php)

require_once 'core/Helper.php';

//require_once 'app/App.php'; //import file App.php

//require_once 'core/Controller.php'; //import file Controller.php

//require_once 'core/Connection.php'; //Import file Connection.php

//require_once 'core/QueryBuilder.php'; //Import trait QueryBuilder.php

//require_once 'core/Database.php'; //Import file Database.php (Sau khi kết nối)

//require_once 'core/Model.php'; //Import file Model.php
<?php
/**
 * Autoloader tối giản — thay cho `composer dump-autoload` khi máy chưa cài composer.
 *
 * Tương đương phần "autoload" trong compos.json:
 *   psr-4: { "App\\": "./" }
 *   files: ["app/helpers/functions.php"]
 *
 * Khi nào cài được composer thì chạy `composer install` để file này được
 * sinh lại chuẩn; tự động ghi đè, không cần xoá tay.
 */

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    // App\core\Env  ->  core/Env  ->  <root>/core/Env.php
    $relative = substr($class, strlen($prefix));
    $relative = str_replace('\\', '/', $relative);
    $file = __DIR__ . '/../' . $relative . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

// Phần "files" của composer — helper toàn cục (slugify, e, csrf_field, route, ...).
require_once __DIR__ . '/../app/helpers/functions.php';

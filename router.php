<?php
/**
 * Router cho PHP built-in server (chỉ dùng khi dev, không phải production).
 * Mô phỏng RewriteRule trong .htaccess:  ^(.+)$ -> index.php?module=$1
 *
 * Chạy:  php -S 127.0.0.1:8899 router.php   (từ thư mục gốc dự án)
 */

$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = ltrim(urldecode($uri), '/');

// File tĩnh có thật (css/js/ảnh) -> để server tự phục vụ.
if ($path !== '' && is_file(__DIR__ . '/' . $path)) {
    return false;
}

if ($path !== '') {
    $_GET['module'] = $path;
}

require __DIR__ . '/index.php';

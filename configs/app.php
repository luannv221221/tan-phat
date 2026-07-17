<?php
use App\app\providers\AppServiceProvider;
use App\app\middlewares\AuthMiddleware;
use App\app\middlewares\RoleMiddleware;
use App\app\middlewares\CsrfMiddleware;

/*Cấu hình về mặt ứng dụng*/
$config['app'] = [
    'global_middleware' => [
        // Chạy cho MỌI request. Chỉ kiểm tra POST/PUT/PATCH/DELETE.
        // Đặt global (không đặt theo route) để không bao giờ quên
        // bảo vệ một form mới thêm vào sau này.
        CsrfMiddleware::class,
    ],

    'route_middleware' =>[
        'admin/*' => [
            AuthMiddleware::class,
            RoleMiddleware::class
        ],

        'dang-nhap' => AuthMiddleware::class
    ],

    'boot' => [
        AppServiceProvider::class
    ]
];

?>
<?php
use App\app\providers\AppServiceProvider;
use App\app\middlewares\AuthMiddleware;
use App\app\middlewares\RoleMiddleware;
use App\app\middlewares\CsrfMiddleware;
use App\app\middlewares\TenMienMiddleware;

/*Cấu hình về mặt ứng dụng*/
$config['app'] = [
    'global_middleware' => [
        /* ĐỨNG TRƯỚC MỌI THỨ: host quyết định request này thuộc gara nào.
           Chạy sau thì các middleware khác đã kịp làm việc với gara sai. */
        TenMienMiddleware::class,

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
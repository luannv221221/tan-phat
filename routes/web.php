<?php
//File cấu hình route (phiên bản web)
/*
 * Có 2 cách làm:
 * - Cách 1: Dùng Class (Giống Laravel)
 * - Cách 2: Dùng mảng (Giống Codeigniter)
 *
 *
 * */

use App\core\Route;

Route::group('admin', function(){

   //Trang tổng quan admin
   Route::get('/', 'admin/dashboard');

   //Trang danh sách group
   Route::get('groups', 'admin/groups');
   
   //Trang thêm group
   Route::get('groups/add', 'admin/groups/add');
   
   Route::post('groups/add', 'admin/groups/postAdd');
   
   //Trang sửa group
   Route::get('groups/edit/(\d+)', 'admin/groups/edit/$1'); 
   
   Route::post('groups/edit/(\d+)', 'admin/groups/postEdit/$1');

   //Trang xoá group
   Route::get('groups/delete/(\d+)', 'admin/groups/delete/$1');

   //Trang phân quyền
   Route::get('groups/permission/(\d+)', 'admin/groups/permission/$1');

   Route::post('groups/permission/(\d+)', 'admin/groups/postPermission/$1');

   //Route users

   Route::get('users', 'admin/users');

   Route::get('users/add', 'admin/users/add');

   Route::post('users/add', 'admin/users/postAdd');

   Route::get('users/edit/(\d+)', 'admin/users/edit/$1');

   Route::post('users/edit/(\d+)', 'admin/users/postEdit/$1');

   Route::get('users/delete/(\d+)', 'admin/users/delete/$1');

   //Route products
   Route::get('products', 'admin/products');

   //Route news
   Route::get('news', 'admin/news');

   /* =========================================================
    * DANH MỤC XE + DANH MỤC PHỤ TÙNG
    *
    * 7 danh mục này dùng chung App\core\LookupCrudController
    * và chung view app/views/admin/lookup/*.
    *
    * URL dùng gạch ngang (car-body-types) cho đẹp; controller đích
    * phải viết liền vì App::handleUrl() chỉ ucfirst() đoạn cuối
    * để tìm file (car-body-types -> Car-body-types.php, không tồn tại).
    * ========================================================= */

   $lookupModules = [
       'car-body-types'        => 'carbodytypes',
       'car-fuels'             => 'carfuels',
       'car-colors'            => 'carcolors',
       'product-brands'        => 'productbrands',
       'product-origins'       => 'productorigins',
       'product-manufacturers' => 'productmanufacturers',
       'product-units'         => 'productunits',
   ];

   foreach ($lookupModules as $url => $controller){
       Route::get($url,                    'admin/'.$controller);
       Route::get($url.'/add',             'admin/'.$controller.'/add');
       Route::post($url.'/add',            'admin/'.$controller.'/postAdd');
       Route::get($url.'/edit/(\d+)',      'admin/'.$controller.'/edit/$1');
       Route::post($url.'/edit/(\d+)',     'admin/'.$controller.'/postEdit/$1');
       Route::get($url.'/delete/(\d+)',    'admin/'.$controller.'/delete/$1');
   }

   Route::get('khong-co-quyen', 'admin/dashboard/noPermission');

});

Route::get('dang-nhap', 'auth/login');

Route::post('dang-nhap', 'auth/postLogin');

Route::get('dang-xuat', 'auth/logout');

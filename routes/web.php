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

   //Route products (Quản lý phụ tùng — CRUD đầy đủ + gán đời xe)
   Route::get('products', 'admin/products');
   Route::get('products/add', 'admin/products/add');
   Route::post('products/add', 'admin/products/postAdd');
   Route::get('products/edit/(\d+)', 'admin/products/edit/$1');
   Route::post('products/edit/(\d+)', 'admin/products/postEdit/$1');
   Route::get('products/delete/(\d+)', 'admin/products/delete/$1');
   // Thư viện ảnh phụ tùng (TASK_77)
   Route::post('products/images/(\d+)', 'admin/products/postImages/$1');
   Route::get('products/image-delete/(\d+)', 'admin/products/imageDelete/$1');
   Route::get('products/image-primary/(\d+)', 'admin/products/imagePrimary/$1');
   // Import phụ tùng từ Excel/CSV (TASK_78)
   Route::get('products/import', 'admin/products/import');
   Route::post('products/import', 'admin/products/postImport');
   Route::get('products/import-template', 'admin/products/importTemplate');
   // Tìm phụ tùng (JSON) cho ô chọn phụ kiện đi kèm (TASK_81)
   Route::get('products/search-json', 'admin/products/searchJson');
   // Xuất catalogue CSV (TASK_85)
   Route::get('products/export', 'admin/products/export');

   //Route news
   Route::get('news', 'admin/news');

   /* =========================================================
    * DANH MỤC XE + DANH MỤC PHỤ TÙNG
    *
    * Mỗi danh mục có controller riêng trong app/controllers/admin/
    * (kế thừa thẳng App\core\Controller) và view riêng
    * app/views/admin/<route-base>/*.
    *
    * URL dùng gạch ngang (car-body-types) cho đẹp; controller đích
    * phải viết liền vì App::handleUrl() chỉ ucfirst() đoạn cuối
    * để tìm file (car-body-types -> Car-body-types.php, không tồn tại).
    *
    * Vòng lặp dưới chỉ nối URL -> controller; 6 route/danh mục giống
    * hệt nhau nên gom lại cho gọn, không liên quan tới base class.
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

   /* =========================================================
    * DANH MỤC XE CÓ QUAN HỆ (Ưu tiên 1)
    *
    * Cùng dạng 6 route/controller như trên, nhưng controller phức tạp hơn
    * (dropdown phụ thuộc, upload logo, cây cha-con) — xem từng file controller.
    * ========================================================= */
   $relationalModules = [
       'car-brands'      => 'carbrands',       // Hãng xe (upload logo)
       'car-models'      => 'carmodels',       // Model xe (dropdown hãng + kiểu dáng)
       'car-years'       => 'caryears',        // Đời xe (cascade hãng -> model)
       'part-categories' => 'partcategories',  // Danh mục phụ tùng (cây cha-con)
       'attributes'      => 'attributes',       // Thông số kỹ thuật (TASK_90)
   ];

   $lookupModules = array_merge($lookupModules, $relationalModules);

   foreach ($lookupModules as $url => $controller){
       Route::get($url,                    'admin/'.$controller);
       Route::get($url.'/add',             'admin/'.$controller.'/add');
       Route::post($url.'/add',            'admin/'.$controller.'/postAdd');
       Route::get($url.'/edit/(\d+)',      'admin/'.$controller.'/edit/$1');
       Route::post($url.'/edit/(\d+)',     'admin/'.$controller.'/postEdit/$1');
       Route::get($url.'/delete/(\d+)',    'admin/'.$controller.'/delete/$1');
   }

   /* =========================================================
    * KẾ TOÁN (KT-1 + KT-2) — theo KE_TOAN_SPEC_DE_XUAT.md
    * ========================================================= */
   $accModules = [
       'accounts'   => 'accounts',    // Danh mục tài khoản (cây)
       'cost-items' => 'costitems',   // Mã phí
       'projects'   => 'projects',    // Mã vụ việc
       'vouchers'   => 'vouchers',    // Phiếu thu / chi
       'journal'    => 'journal',     // Phiếu kế toán (định khoản tự do) - KT-3
       'partners'   => 'partners',    // Đối tượng khách/NCC - KT-4
   ];
   foreach ($accModules as $url => $controller){
       Route::get($url,                 'admin/'.$controller);
       Route::get($url.'/add',          'admin/'.$controller.'/add');
       Route::post($url.'/add',         'admin/'.$controller.'/postAdd');
       Route::get($url.'/edit/(\d+)',   'admin/'.$controller.'/edit/$1');
       Route::post($url.'/edit/(\d+)',  'admin/'.$controller.'/postEdit/$1');
       Route::get($url.'/delete/(\d+)', 'admin/'.$controller.'/delete/$1');
   }
   // Phiếu: ghi sổ / huỷ ghi sổ
   Route::get('vouchers/post/(\d+)',   'admin/vouchers/post/$1');
   Route::get('vouchers/unpost/(\d+)', 'admin/vouchers/unpost/$1');
   Route::get('journal/post/(\d+)',    'admin/journal/post/$1');
   Route::get('journal/unpost/(\d+)',  'admin/journal/unpost/$1');
   // Sổ quỹ (chỉ xem)
   Route::get('cash-book', 'admin/cashbook');
   // Công nợ (chỉ xem) - KT-4
   Route::get('debt', 'admin/debt');
   // Sổ sách (chỉ xem) - KT-5
   Route::get('nhat-ky-chung', 'admin/generaljournal');
   Route::get('so-cai', 'admin/ledger');

   Route::get('khong-co-quyen', 'admin/dashboard/noPermission');

});

Route::get('dang-nhap', 'auth/login');

Route::post('dang-nhap', 'auth/postLogin');

Route::get('dang-xuat', 'auth/logout');

<?php

use App\core\Controller;

class Products extends Controller{
    private $__data = [];

    public function index(){
        $this->__data['sub_content'] = 'admin/products/lists'; //gọi view

        $this->__data['page_title'] = 'Quản lý sản phẩm';
        $this->__data['content']['page_name'] = 'Danh sách sản phẩm';

        $this->render('layouts/admin/master_admin', $this->__data);
    }
}
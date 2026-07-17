<?php

use App\core\Controller;

class News extends Controller{
    private $__data = [];

    public function index(){
        $this->__data['sub_content'] = 'admin/news/lists'; //gọi view

        $this->__data['page_title'] = 'Quản lý tin tức';
        $this->__data['content']['page_name'] = 'Danh sách tin tức';

        $this->render('layouts/admin/master_admin', $this->__data);
    }
}
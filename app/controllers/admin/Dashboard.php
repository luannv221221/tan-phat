<?php
use App\core\Controller;

class Dashboard extends Controller {
    private $__data = [];

    public function index(){

        $this->__data['sub_content'] = 'admin/dashboard'; //gọi view

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function noPermission(){

        $this->render('admin/no-permission');
    }
}
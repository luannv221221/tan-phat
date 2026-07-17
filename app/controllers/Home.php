<?php
use App\core\Controller;
use App\core\Session;
class Home extends Controller {
    private $__data = [];
    public function index(){

        $this->__data['sub_content'] = 'home/index'; //gọi view

        $this->render('layouts/client/master_client', $this->__data);
    }
}
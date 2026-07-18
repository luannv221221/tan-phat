<?php

use App\core\Controller;
use App\core\Session;

/**
 * CSKH — Lịch bảo hành / bảo trì (CS-03). Chỉ xem: phiếu chưa hoàn tất theo ngày hẹn.
 */
class Warrantyschedule extends Controller {

    private $__data = [];
    private $__model;

    function __construct(){
        $this->__model = $this->model('WarrantyRequestsModel');
    }

    public function index(){
        $this->__data['sub_content'] = 'admin/lich-bao-hanh/index';
        $this->__data['page_title']  = 'Lịch bảo hành';

        $this->__data['content']['page_name'] = 'Lịch bảo hành / bảo trì';
        $this->__data['content']['rows']      = $this->__model->getSchedule();
        $this->__data['content']['statuses']  = WarrantyRequestsModel::$statuses;
        $this->__data['content']['today']     = date('Y-m-d');
        $this->__data['content']['msg']       = Session::flash('msg');

        $this->render('layouts/admin/master_admin', $this->__data);
    }
}

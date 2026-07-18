<?php

use App\core\Controller;
use App\core\Request;
use App\core\Session;

/**
 * CSKH — Báo cáo / thống kê (CS-04). Chỉ xem: số phiếu bảo hành theo trạng thái.
 */
class Cskhreport extends Controller {

    private $__data = [];
    private $__model, $__request;

    function __construct(){
        $this->__model   = $this->model('WarrantyRequestsModel');
        $this->__request = new Request();
    }

    public function index(){
        $this->__data['sub_content'] = 'admin/bao-cao-cskh/index';
        $this->__data['page_title']  = 'Báo cáo CSKH';

        $f    = $this->__request->getFields();
        $from = isset($f['from']) ? trim($f['from']) : '';
        $to   = isset($f['to'])   ? trim($f['to'])   : '';

        $counts = $this->__model->countByStatus($from, $to);
        $total = 0; $totalFee = 0.0;
        foreach ($counts as $c){ $total += $c['total']; $totalFee += $c['fee']; }

        $this->__data['content']['page_name']  = 'Báo cáo CSKH';
        $this->__data['content']['statuses']   = WarrantyRequestsModel::$statuses;
        $this->__data['content']['counts']     = $counts;
        $this->__data['content']['total']      = $total;
        $this->__data['content']['totalFee']   = $totalFee;
        $this->__data['content']['filterFrom'] = $from;
        $this->__data['content']['filterTo']   = $to;
        $this->__data['content']['msg']        = Session::flash('msg');

        $this->render('layouts/admin/master_admin', $this->__data);
    }
}

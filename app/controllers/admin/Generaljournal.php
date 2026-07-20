<?php

use App\core\Controller;
use App\core\Request;
use App\core\Session;

/**
 * KT-5 — Nhật ký chung: mọi bút toán ĐÃ GHI SỔ theo thời gian (Nợ TK / Có TK / tiền).
 * Chỉ xem. Tái dùng sổ cái chuẩn hoá getPostedLedger.
 */
class Generaljournal extends Controller {

    private $__data = [];
    private $__entryModel, $__accModel, $__request;

    private $viewDir = 'admin/nhat-ky-chung';

    function __construct(){
        $this->__entryModel = $this->model('AccVoucherEntriesModel');
        $this->__accModel   = $this->model('AccAccountsModel');
        $this->__request    = new Request();
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/index';
        $this->__data['page_title']  = 'Nhật ký chung';

        $f    = $this->__request->getFields();
        $from = isset($f['from']) ? trim($f['from']) : '';
        $to   = isset($f['to'])   ? trim($f['to'])   : '';

        $rows = $this->__entryModel->getPostedLedger(0, $from, $to, 0);

        $map = [];
        foreach ($this->__accModel->getTree() as $a){ $map[(int) $a['id']] = $a['code'] . ' - ' . $a['name']; }

        $total = 0.0;
        foreach ($rows as $r){ $total += (float) $r['amount']; }

        $this->__data['content']['page_name']  = 'Nhật ký chung';
        $this->__data['content']['rows']       = $rows;
        $this->__data['content']['accountMap'] = $map;
        $this->__data['content']['total']      = $total;
        $this->__data['content']['from']       = $from;
        $this->__data['content']['to']         = $to;
        $this->__data['content']['msg']        = Session::flash('msg');

        $this->render('layouts/admin/master_admin', $this->__data);
    }
}

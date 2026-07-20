<?php

use App\core\Controller;
use App\core\Request;
use App\core\Session;

/**
 * KT-2 — Sổ quỹ: liệt kê phiếu thu/chi ĐÃ GHI SỔ của 1 quỹ theo thời gian,
 * kèm số dư đầu kỳ và số dư luỹ kế. Chỉ xem (không CRUD).
 */
class Cashbook extends Controller {

    private $__data = [];
    private $__voucherModel, $__accModel, $__request;

    private $viewDir = 'admin/cash-book';

    function __construct(){
        $this->__voucherModel = $this->model('AccVouchersModel');
        $this->__accModel     = $this->model('AccAccountsModel');
        $this->__request      = new Request();
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/index';
        $this->__data['page_title']  = 'Sổ quỹ';

        $cashAccounts = $this->__accModel->getCashAccounts();

        $f       = $this->__request->getFields();
        $cashId  = isset($f['cash_account_id']) && $f['cash_account_id'] !== '' ? (int) $f['cash_account_id'] : 0;
        if ($cashId <= 0 && !empty($cashAccounts)){
            $cashId = (int) $cashAccounts[0]['id'];  // mặc định quỹ đầu tiên
        }
        $from = isset($f['from']) ? trim($f['from']) : '';
        $to   = isset($f['to'])   ? trim($f['to'])   : '';

        $opening = $cashId > 0 ? $this->__voucherModel->getBalanceBefore($cashId, $from) : 0.0;
        $rows    = $cashId > 0 ? $this->__voucherModel->getCashBook($cashId, $from, $to) : [];

        $this->__data['content']['page_name']    = 'Sổ quỹ';
        $this->__data['content']['cashAccounts'] = $cashAccounts;
        $this->__data['content']['cashId']       = $cashId;
        $this->__data['content']['from']         = $from;
        $this->__data['content']['to']           = $to;
        $this->__data['content']['opening']      = $opening;
        $this->__data['content']['rows']         = $rows;
        $this->__data['content']['msg']          = Session::flash('msg');

        $this->render('layouts/admin/master_admin', $this->__data);
    }
}

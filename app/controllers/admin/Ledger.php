<?php

use App\core\Controller;
use App\core\Request;
use App\core\Session;

/**
 * KT-5 — Sổ cái / sổ chi tiết 1 tài khoản: số dư đầu kỳ + phát sinh Nợ/Có + luỹ kế.
 * Số dư quy ước theo bên Nợ (Nợ − Có); TK nguồn vốn/doanh thu sẽ ra số âm — đúng bản chất.
 * Chỉ xem.
 */
class Ledger extends Controller {

    private $__data = [];
    private $__entryModel, $__accModel, $__request;

    private $viewDir = 'admin/so-cai';

    function __construct(){
        $this->__entryModel = $this->model('AccVoucherEntriesModel');
        $this->__accModel   = $this->model('AccAccountsModel');
        $this->__request    = new Request();
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/index';
        $this->__data['page_title']  = 'Sổ cái tài khoản';

        $accounts = $this->__accModel->getDetailAccounts();

        $f       = $this->__request->getFields();
        $accId   = isset($f['account_id']) && $f['account_id'] !== '' ? (int) $f['account_id'] : 0;
        if ($accId <= 0 && !empty($accounts)){ $accId = (int) $accounts[0]['id']; }
        $from = isset($f['from']) ? trim($f['from']) : '';
        $to   = isset($f['to'])   ? trim($f['to'])   : '';

        $opening = 0.0; $rows = [];
        if ($accId > 0){
            $ledger = $this->__entryModel->getPostedLedger(0, '', '', $accId);
            foreach ($ledger as $l){
                $dr = ($l['debit_account_id']  === $accId) ? (float) $l['amount'] : 0.0;
                $cr = ($l['credit_account_id'] === $accId) ? (float) $l['amount'] : 0.0;
                $effect = $dr - $cr;

                if ($from !== '' && $l['voucher_date'] < $from){
                    $opening += $effect;
                } elseif ($to === '' || $l['voucher_date'] <= $to){
                    $rows[] = $l + ['_dr' => $dr, '_cr' => $cr];
                }
            }
        }

        $acc = $accId > 0 ? $this->__accModel->getDetail($accId) : null;

        $map = [];
        foreach ($this->__accModel->getTree() as $a){ $map[(int) $a['id']] = $a['code'] . ' - ' . $a['name']; }

        $this->__data['content']['page_name'] = 'Sổ cái tài khoản';
        $this->__data['content']['accountMap']= $map;
        $this->__data['content']['accounts']  = $accounts;
        $this->__data['content']['accId']     = $accId;
        $this->__data['content']['account']   = $acc;
        $this->__data['content']['opening']   = $opening;
        $this->__data['content']['rows']      = $rows;
        $this->__data['content']['from']      = $from;
        $this->__data['content']['to']        = $to;
        $this->__data['content']['msg']       = Session::flash('msg');

        $this->render('layouts/admin/master_admin', $this->__data);
    }
}

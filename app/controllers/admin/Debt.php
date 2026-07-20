<?php

use App\core\Controller;
use App\core\Request;
use App\core\Session;

/**
 * KT-4 — Công nợ (chỉ xem).
 *
 * Công nợ ròng của 1 đối tượng = (họ nợ mình) − (mình nợ họ):
 *   Nợ 131 / Có 331 -> tăng ; Có 131 / Nợ 331 -> giảm.
 * Dương = phải thu (khách nợ) · Âm = phải trả (nợ NCC).
 *
 * - Không chọn đối tượng: bảng TỔNG HỢP số dư mọi đối tượng.
 * - Chọn đối tượng: SỔ CHI TIẾT (số dư đầu kỳ + phát sinh + luỹ kế).
 */
class Debt extends Controller {

    private $__data = [];
    private $__entryModel, $__partnerModel, $__accModel, $__request;

    private $viewDir = 'admin/debt';

    function __construct(){
        $this->__entryModel   = $this->model('AccVoucherEntriesModel');
        $this->__partnerModel = $this->model('PartnersModel');
        $this->__accModel     = $this->model('AccAccountsModel');
        $this->__request      = new Request();
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/index';
        $this->__data['page_title']  = 'Công nợ';

        $recvIds = $this->__accModel->getIdsByCodePrefix('131');
        $payIds  = $this->__accModel->getIdsByCodePrefix('331');

        $f         = $this->__request->getFields();
        $partnerId = isset($f['partner_id']) && $f['partner_id'] !== '' ? (int) $f['partner_id'] : 0;
        $from      = isset($f['from']) ? trim($f['from']) : '';
        $to        = isset($f['to'])   ? trim($f['to'])   : '';

        $partners = $this->__partnerModel->getLists();
        $pMap = [];
        foreach ($partners as $p){ $pMap[(int) $p['id']] = $p; }

        $this->__data['content']['page_name']  = 'Công nợ';
        $this->__data['content']['partners']   = $partners;
        $this->__data['content']['partnerId']  = $partnerId;
        $this->__data['content']['from']       = $from;
        $this->__data['content']['to']         = $to;
        $this->__data['content']['msg']        = Session::flash('msg');

        if ($partnerId > 0){
            // ===== Sổ chi tiết 1 đối tượng =====
            $ledger  = $this->__entryModel->getPostedLedger($partnerId, '', '', 0);
            $opening = 0.0; $rows = []; $bal = 0.0;

            foreach ($ledger as $l){
                $d = $this->delta($l, $recvIds, $payIds);
                if ($d === 0.0) continue; // chỉ dòng liên quan công nợ

                if ($from !== '' && $l['voucher_date'] < $from){
                    $opening += $d;                 // dồn vào số dư đầu kỳ
                } elseif ($to === '' || $l['voucher_date'] <= $to){
                    $rows[] = $l + ['_delta' => $d]; // luỹ kế tính ở view
                }
            }

            $this->__data['content']['mode']    = 'detail';
            $this->__data['content']['partner'] = isset($pMap[$partnerId]) ? $pMap[$partnerId] : null;
            $this->__data['content']['opening'] = $opening;
            $this->__data['content']['rows']    = $rows;
        } else {
            // ===== Tổng hợp số dư =====
            $ledger = $this->__entryModel->getPostedLedger(0, '', $to, 0);
            $bal = []; // partner_id => net (số dư đến ngày $to)
            foreach ($ledger as $l){
                $pid = $l['partner_id'];
                if (!$pid) continue;
                $d = $this->delta($l, $recvIds, $payIds);
                if ($d === 0.0) continue;
                if (!isset($bal[$pid])) $bal[$pid] = 0.0;
                $bal[$pid] += $d;
            }

            $summary = [];
            foreach ($bal as $pid => $net){
                if (abs($net) < 0.005) continue;
                $summary[] = [
                    'partner' => isset($pMap[$pid]) ? $pMap[$pid] : ['name' => '#' . $pid, 'code' => ''],
                    'net'     => $net,
                ];
            }

            $this->__data['content']['mode']    = 'summary';
            $this->__data['content']['summary'] = $summary;
        }

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    /** Biến động công nợ ròng của 1 dòng sổ cái */
    private function delta($l, $recvIds, $payIds){
        $d = 0.0;
        $amt = (float) $l['amount'];
        if (in_array($l['debit_account_id'], $recvIds, true))  $d += $amt;
        if (in_array($l['credit_account_id'], $recvIds, true)) $d -= $amt;
        if (in_array($l['debit_account_id'], $payIds, true))   $d += $amt;
        if (in_array($l['credit_account_id'], $payIds, true))  $d -= $amt;
        return $d;
    }
}

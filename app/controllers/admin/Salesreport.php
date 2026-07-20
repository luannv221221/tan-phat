<?php

use App\core\Controller;
use App\core\Request;
use App\core\Session;

/**
 * BÁN HÀNG — Báo cáo bán hàng (SAL-10/12/13). Chỉ xem.
 * Gộp hoá đơn ĐÃ GHI SỔ theo khách hàng và theo nhân viên: doanh thu, giá vốn, lãi gộp.
 */
class Salesreport extends Controller {

    private $__data = [];
    private $__model, $__request;

    function __construct(){
        $this->__model   = $this->model('SalesInvoicesModel');
        $this->__request = new Request();
    }

    public function index(){
        $this->__data['sub_content'] = 'admin/bao-cao-ban-hang/index';
        $this->__data['page_title']  = 'Báo cáo bán hàng';

        $f    = $this->__request->getFields();
        $from = isset($f['from']) ? trim($f['from']) : '';
        $to   = isset($f['to'])   ? trim($f['to'])   : '';

        $rows = $this->__model->getPostedForReport($from, $to);

        $byCustomer = [];
        $byStaff    = [];
        $tot = ['revenue' => 0.0, 'tax' => 0.0, 'cost' => 0.0, 'count' => 0];

        foreach ($rows as $r){
            $rev  = (float) $r['subtotal'];
            $tax  = (float) $r['tax_amount'];
            $cost = (float) $r['cost_amount'];

            $cKey = $r['customer_id'] !== null ? (int) $r['customer_id'] : 0;
            $cName = !empty($r['customer_full']) ? $r['customer_full'] : (!empty($r['customer_name']) ? $r['customer_name'] : 'Khách vãng lai');
            $this->accumulate($byCustomer, $cKey, $cName, $rev, $tax, $cost);

            $sKey = $r['created_by'] !== null ? (int) $r['created_by'] : 0;
            $sName = !empty($r['staff_name']) ? $r['staff_name'] : 'Không rõ';
            $this->accumulate($byStaff, $sKey, $sName, $rev, $tax, $cost);

            $tot['revenue'] += $rev; $tot['tax'] += $tax; $tot['cost'] += $cost; $tot['count']++;
        }

        $this->__data['content']['page_name']   = 'Báo cáo bán hàng';
        $this->__data['content']['byCustomer']  = array_values($byCustomer);
        $this->__data['content']['byStaff']     = array_values($byStaff);
        $this->__data['content']['totals']      = $tot;
        $this->__data['content']['filterFrom']  = $from;
        $this->__data['content']['filterTo']    = $to;
        $this->__data['content']['msg']         = Session::flash('msg');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    private function accumulate(&$bucket, $key, $name, $rev, $tax, $cost){
        if (!isset($bucket[$key])){
            $bucket[$key] = ['name' => $name, 'revenue' => 0.0, 'tax' => 0.0, 'cost' => 0.0, 'count' => 0];
        }
        $bucket[$key]['revenue'] += $rev;
        $bucket[$key]['tax']     += $tax;
        $bucket[$key]['cost']    += $cost;
        $bucket[$key]['count']++;
    }
}

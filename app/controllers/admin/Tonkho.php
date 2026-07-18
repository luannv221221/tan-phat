<?php

use App\core\Controller;
use App\core\Request;
use App\core\Session;

/**
 * KHO — Báo cáo tồn kho hiện tại (WH-09/11). Chỉ xem.
 */
class Tonkho extends Controller {

    private $__data = [];
    private $__stock, $__warehouse, $__request;

    function __construct(){
        $this->__stock     = $this->model('StocksModel');
        $this->__warehouse = $this->model('WarehousesModel');
        $this->__request   = new Request();
    }

    public function index(){
        $this->__data['sub_content'] = 'admin/ton-kho/index';
        $this->__data['page_title']  = 'Tồn kho';

        $f       = $this->__request->getFields();
        $whId    = !empty($f['warehouse_id']) ? (int) $f['warehouse_id'] : 0;
        $keyword = isset($f['keyword']) ? trim($f['keyword']) : '';

        $rows = $this->__stock->getStockList($whId, $keyword);

        $totalValue = 0.0;
        foreach ($rows as $r){ $totalValue += (float) $r['quantity'] * (float) $r['avg_cost']; }

        $this->__data['content']['page_name']    = 'Tồn kho';
        $this->__data['content']['rows']         = $rows;
        $this->__data['content']['warehouses']   = $this->__warehouse->getActive();
        $this->__data['content']['filterWh']     = $whId;
        $this->__data['content']['filterKeyword']= $keyword;
        $this->__data['content']['totalValue']   = $totalValue;
        $this->__data['content']['msg']          = Session::flash('msg');

        $this->render('layouts/admin/master_admin', $this->__data);
    }
}

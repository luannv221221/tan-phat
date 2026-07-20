<?php

use App\core\Controller;
use App\core\Request;
use App\core\Session;

/**
 * KHO — Thẻ kho 1 phụ tùng (WH-08). Chỉ xem.
 *
 * Chọn phụ tùng (+ kho + khoảng ngày): tồn đầu kỳ + phát sinh nhập/xuất + số dư luỹ kế.
 * Thẻ kho theo TỪNG kho (số dư luỹ kế chỉ đúng khi khoá 1 kho) -> mặc định kho mặc định.
 */
class Thekho extends Controller {

    private $__data = [];
    private $__stock, $__warehouse, $__part, $__request;

    function __construct(){
        $this->__stock     = $this->model('StocksModel');
        $this->__warehouse = $this->model('WarehousesModel');
        $this->__part      = $this->model('PartsModel');
        $this->__request   = new Request();
    }

    public function index(){
        $this->__data['sub_content'] = 'admin/the-kho/index';
        $this->__data['page_title']  = 'Thẻ kho';

        $f      = $this->__request->getFields();
        $partId = !empty($f['part_id']) ? (int) $f['part_id'] : 0;
        $whId   = isset($f['warehouse_id']) && $f['warehouse_id'] !== '' ? (int) $f['warehouse_id'] : 0;
        $from   = isset($f['from']) ? trim($f['from']) : '';
        $to     = isset($f['to'])   ? trim($f['to'])   : '';

        // Mặc định kho mặc định (thẻ kho theo từng kho)
        if ($whId === 0){
            $def  = $this->__warehouse->getDefault();
            $whId = !empty($def) ? (int) $def['id'] : 0;
        }

        $opening = ['qty' => 0.0, 'value' => 0.0];
        $cards   = [];
        $part    = null;
        if ($partId > 0){
            $part    = $this->__part->getDetail($partId);
            $opening = $this->__stock->getBalanceBefore($partId, $whId, $from);
            $cards   = $this->__stock->getCards($partId, $whId, $from, $to);
        }

        $this->__data['content']['page_name']   = 'Thẻ kho';
        $this->__data['content']['parts']       = $this->__part->getForSelect();
        $this->__data['content']['warehouses']  = $this->__warehouse->getActive();
        $this->__data['content']['part']        = $part;
        $this->__data['content']['cards']       = $cards;
        $this->__data['content']['opening']     = $opening;
        $this->__data['content']['filterPart']  = $partId;
        $this->__data['content']['filterWh']    = $whId;
        $this->__data['content']['filterFrom']  = $from;
        $this->__data['content']['filterTo']    = $to;
        $this->__data['content']['msg']         = Session::flash('msg');

        $this->render('layouts/admin/master_admin', $this->__data);
    }
}

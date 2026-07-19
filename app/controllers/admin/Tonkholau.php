<?php

use App\core\Controller;
use App\core\Request;
use App\core\Session;

/**
 * KHO-3 — Báo cáo Hàng tồn lâu (chậm luân chuyển). Chỉ xem.
 *
 * Với mỗi dòng tồn (qty>0): số ngày kể từ phát sinh gần nhất tới ngày báo cáo.
 * Gộp theo dải tuổi tồn để nhìn nhanh hàng cần đẩy/giảm giá.
 */
class Tonkholau extends Controller {

    private $__data = [];
    private $__stock, $__warehouse, $__request;

    /** Dải tuổi tồn (ngày): [nhãn, min, max] — max null = trở lên */
    private $buckets = [
        ['0–30 ngày',    0,   30],
        ['31–90 ngày',   31,  90],
        ['91–180 ngày',  91,  180],
        ['181–365 ngày', 181, 365],
        ['Trên 365 ngày',366, null],
    ];

    function __construct(){
        $this->__stock     = $this->model('StocksModel');
        $this->__warehouse = $this->model('WarehousesModel');
        $this->__request   = new Request();
    }

    public function index(){
        $this->__data['sub_content'] = 'admin/ton-kho-lau/index';
        $this->__data['page_title']  = 'Hàng tồn lâu';

        $f       = $this->__request->getFields();
        $whId    = !empty($f['warehouse_id']) ? (int) $f['warehouse_id'] : 0;
        $minDays = isset($f['min_days']) && $f['min_days'] !== '' ? max(0, (int) $f['min_days']) : 90;
        $asOf    = !empty($f['as_of']) ? $f['as_of'] : date('Y-m-d');

        $rows = $this->__stock->getAging($whId, $minDays, $asOf);

        // Gộp theo dải tuổi + tổng giá trị
        $summary = [];
        foreach ($this->buckets as $b){ $summary[$b[0]] = ['qty_rows' => 0, 'value' => 0.0]; }
        $totalValue = 0.0;
        foreach ($rows as &$r){
            $d = $r['days_idle'];
            $label = '—';
            foreach ($this->buckets as $b){
                if ($d !== null && $d >= $b[1] && ($b[2] === null || $d <= $b[2])){ $label = $b[0]; break; }
            }
            $r['bucket'] = $label;
            if (isset($summary[$label])){
                $summary[$label]['qty_rows'] += 1;
                $summary[$label]['value']    += (float) $r['value'];
            }
            $totalValue += (float) $r['value'];
        }
        unset($r);

        $this->__data['content']['page_name']   = 'Hàng tồn lâu (chậm luân chuyển)';
        $this->__data['content']['rows']        = $rows;
        $this->__data['content']['summary']     = $summary;
        $this->__data['content']['buckets']     = $this->buckets;
        $this->__data['content']['warehouses']  = $this->__warehouse->getActive();
        $this->__data['content']['filterWh']    = $whId;
        $this->__data['content']['filterMin']   = $minDays;
        $this->__data['content']['filterAsOf']  = $asOf;
        $this->__data['content']['totalValue']  = $totalValue;
        $this->__data['content']['msg']         = Session::flash('msg');

        $this->render('layouts/admin/master_admin', $this->__data);
    }
}

<?php

use App\core\Model;

/**
 * KHO-2 — Phiếu kiểm kê kho.
 */
class StockTakesModel extends Model {

    protected $_table   = 'stock_takes';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public function getLists($from = '', $to = ''){
        $q = $this->table($this->_table)
            ->select('`stock_takes`.*, `warehouses`.`name` AS warehouse_name')
            ->joinOn('warehouses', 'stock_takes.warehouse_id', 'warehouses.id');
        if ($from !== '') $q = $q->where('stock_takes.take_date', '>=', $from);
        if ($to !== '')   $q = $q->where('stock_takes.take_date', '<=', $to);
        return $q->orderBy('stock_takes.take_date', 'DESC')->orderBy('stock_takes.id', 'DESC')->get();
    }

    public function getDetail($id){ return $this->getFirst($id); }

    public function nextNo(){
        $row = $this->table($this->_table)->select('`take_no`')->orderBy('id', 'DESC')->first();
        $n = 0;
        if (!empty($row) && preg_match('/(\d+)$/', $row['take_no'], $m)){ $n = (int) $m[1]; }
        return 'PKK-' . str_pad($n + 1, 6, '0', STR_PAD_LEFT);
    }

    public function add($data){
        $data['create_at'] = date('Y-m-d H:i:s');
        $this->addNew($data);
        return $this->lastId();
    }

    public function edit($data, $id){
        $data['update_at'] = date('Y-m-d H:i:s');
        return $this->updateById($data, $id);
    }

    public function remove($id){ return $this->deleteById($id); }
}

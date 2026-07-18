<?php

use App\core\Model;

/**
 * KHO-2 — Phiếu điều chuyển kho.
 */
class WarehouseTransfersModel extends Model {

    protected $_table   = 'warehouse_transfers';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public function getLists($from = '', $to = ''){
        // QueryBuilder không cho self-join alias (warehouses 2 lần) -> map tên kho ở PHP.
        $q = $this->table($this->_table)->select('*');
        if ($from !== '') $q = $q->where('transfer_date', '>=', $from);
        if ($to !== '')   $q = $q->where('transfer_date', '<=', $to);
        $rows = $q->orderBy('transfer_date', 'DESC')->orderBy('id', 'DESC')->get();

        $whs = $this->table('warehouses')->select('`id`, `name`')->get();
        $map = [];
        foreach ($whs ?: [] as $w){ $map[(int) $w['id']] = $w['name']; }
        foreach ($rows as &$r){
            $r['from_name'] = $map[(int) $r['from_warehouse_id']] ?? '';
            $r['to_name']   = $map[(int) $r['to_warehouse_id']] ?? '';
        }
        return $rows;
    }

    public function getDetail($id){ return $this->getFirst($id); }

    public function nextNo(){
        $row = $this->table($this->_table)->select('`transfer_no`')->orderBy('id', 'DESC')->first();
        $n = 0;
        if (!empty($row) && preg_match('/(\d+)$/', $row['transfer_no'], $m)){ $n = (int) $m[1]; }
        return 'PDC-' . str_pad($n + 1, 6, '0', STR_PAD_LEFT);
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

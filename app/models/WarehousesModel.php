<?php

use App\core\Model;

/**
 * KHO — Danh mục kho (phẳng, có 1 kho mặc định).
 */
class WarehousesModel extends Model {

    protected $_table   = 'warehouses';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public function getLists(){
        return $this->table($this->_table)
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('name', 'ASC')
                    ->get();
    }

    /** Đang hoạt động — cho dropdown chọn kho trên phiếu */
    public function getActive(){
        return $this->table($this->_table)
                    ->where('status', '=', 1)
                    ->orderBy('is_default', 'DESC')
                    ->orderBy('name', 'ASC')
                    ->get();
    }

    /** Kho mặc định (hoặc kho đầu tiên đang bật) */
    public function getDefault(){
        $r = $this->table($this->_table)
                  ->where('status', '=', 1)
                  ->where('is_default', '=', 1)
                  ->first();
        if (!empty($r)) return $r;
        return $this->table($this->_table)
                    ->where('status', '=', 1)
                    ->orderBy('id', 'ASC')->first();
    }

    public function getDetail($id){ return $this->getFirst($id); }

    public function findByCode($code){
        return $this->table($this->_table)->where('code', '=', $code)->first();
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

    /** Bỏ cờ mặc định ở mọi kho khác (chỉ 1 kho mặc định) */
    public function clearDefaultExcept($id){
        return $this->update('warehouses', ['is_default' => 0], '`id` != ?', [(int) $id]);
    }

    public function remove($id){
        return $this->deleteById($id);
    }
}

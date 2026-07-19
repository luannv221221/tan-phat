<?php

use App\core\Model;

/** HR — Chức vụ. */
class PositionsModel extends Model {

    protected $_table   = 'positions';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public function getLists(){
        return $this->table($this->_table)->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->get();
    }
    public function getActive(){
        return $this->table($this->_table)->where('status', '=', 1)->orderBy('sort_order', 'ASC')->get();
    }
    public function getDetail($id){ return $this->getFirst($id); }
    public function add($data){ $data['create_at'] = date('Y-m-d H:i:s'); $this->addNew($data); return $this->lastId(); }
    public function edit($data, $id){ $data['update_at'] = date('Y-m-d H:i:s'); return $this->updateById($data, $id); }
    public function remove($id){ return $this->deleteById($id); }
}

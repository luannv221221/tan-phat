<?php

use App\core\Model;

/**
 * CSKH — Nhóm khách hàng (gắn vào partners) — RIÊNG từng gara.
 */
class CustomerGroupsModel extends Model {

    protected $_table    = 'customer_groups';
    protected $_fields   = '*';
    protected $_primary  = 'id';
    protected $_theoGara = true;

    public function getLists(){
        return $this->bangGara()
                    ->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->get();
    }

    public function getActive(){
        return $this->bangGara()
                    ->where('status', '=', 1)->orderBy('name', 'ASC')->get();
    }

    public function getDetail($id){ return $this->getFirst($id); }

    public function add($data){
        $data['create_at'] = date('Y-m-d H:i:s');
        $this->addNew($data);
        return $this->lastId();
    }

    public function edit($data, $id){
        $data['update_at'] = date('Y-m-d H:i:s');
        return $this->updateById($data, $id);
    }

    public function remove($id){
        // partners.group_id ON DELETE SET NULL -> xoá an toàn.
        return $this->deleteById($id);
    }
}

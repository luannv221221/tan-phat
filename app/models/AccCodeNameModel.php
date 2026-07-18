<?php

use App\core\Model;

/**
 * KT-1 — Base cho các danh mục kế toán đơn giản dạng (code, name):
 * mã phí, mã vụ việc. Lớp con chỉ khai báo $_table.
 */
abstract class AccCodeNameModel extends Model {

    protected $_fields  = '*';
    protected $_primary = 'id';

    public function getLists(){
        return $this->table($this->_table)
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('code', 'ASC')
                    ->get();
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

    public function remove($id){
        // Tham chiếu ở acc_voucher_entries để ON DELETE SET NULL -> xoá an toàn.
        return $this->deleteById($id);
    }
}

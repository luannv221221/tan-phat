<?php

use App\core\Model;

/**
 * KT-4 — Đối tượng: khách hàng + nhà cung cấp (DÙNG CHUNG toàn hệ thống).
 */
class PartnersModel extends Model {

    protected $_table   = 'partners';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public static $types = [
        'customer' => 'Khách hàng',
        'supplier' => 'Nhà cung cấp',
        'both'     => 'Cả hai',
    ];

    public function getLists(){
        return $this->table($this->_table)
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('name', 'ASC')
                    ->get();
    }

    /** Đang hoạt động — cho dropdown chọn đối tượng trên phiếu */
    public function getActive(){
        return $this->table($this->_table)
                    ->where('status', '=', 1)
                    ->orderBy('name', 'ASC')
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
        // acc_vouchers.partner_id ON DELETE SET NULL -> xoá an toàn (phiếu giữ partner_name).
        return $this->deleteById($id);
    }
}

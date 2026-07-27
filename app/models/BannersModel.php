<?php

use App\core\Model;

/**
 * Banner slider trang chủ storefront.
 * `image` lưu đường dẫn tương đối (public/assets/uploads/banners/<file>).
 */
class BannersModel extends Model {

    protected $_table   = 'banners';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /** Danh sách quản trị — theo thứ tự hiển thị */
    public function getLists(){
        return $this->table($this->_table)
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('id', 'ASC')->get();
    }

    /** Banner đang bật cho storefront */
    public function getActive(){
        return $this->table($this->_table)
                    ->where('status', '=', 1)
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('id', 'ASC')->get();
    }

    public function getDetail($id){
        return $this->getFirst($id);
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
        return $this->deleteById($id);
    }
}

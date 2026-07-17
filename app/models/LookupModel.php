<?php

use App\core\Model;

/**
 * Lớp cha cho MỌI danh mục tra cứu đơn giản có cùng cấu trúc
 * (name, slug, sort_order, status) và cùng thao tác CRUD.
 *
 * Đang dùng cho:
 *   - Danh mục xe:     kiểu dáng (dòng xe), nhiên liệu, màu xe
 *   - Danh mục phụ tùng: thương hiệu, xuất xứ, hãng sản xuất, đơn vị tính
 *
 * Lớp con chỉ cần khai báo $_table. Thêm danh mục tra cứu mới thì kế thừa lớp này,
 * đừng chép lại.
 */
abstract class LookupModel extends Model {

    protected $_fields  = '*';
    protected $_primary = 'id';

    public function getLists($onlyActive = false){
        $q = $this->table($this->_table);

        if ($onlyActive){
            $q = $q->where('status', '=', 1);
        }

        return $q->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->get();
    }

    public function getDetail($id){
        return $this->getFirst($id);
    }

    public function findBySlug($slug){
        return $this->table($this->_table)->where('slug', '=', $slug)->first();
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

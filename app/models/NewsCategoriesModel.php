<?php

use App\core\Model;

/**
 * CMS — Danh mục tin.
 */
class NewsCategoriesModel extends Model {

    protected $_table   = 'news_categories';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /* Nội dung website thuộc về MỘT gara — 07/10/2026, khi mỗi gara có trang
       riêng. Thiếu cờ này là gara nào mở web cũng thấy bài của gara khác. */
    protected $_theoGara = true;

    public function getLists(){
        return $this->bangGara()->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->get();
    }
    public function getActive(){
        return $this->bangGara()->where('status', '=', 1)->orderBy('sort_order', 'ASC')->get();
    }
    public function getDetail($id){ return $this->getFirst($id); }
    public function findBySlug($slug){
        return $this->bangGara()->where('slug', '=', $slug)->first();
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

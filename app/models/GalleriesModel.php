<?php

use App\core\Model;

/** CMS — Thư viện ảnh/video (album). */
class GalleriesModel extends Model {

    protected $_table   = 'galleries';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public function getLists($status = '', $keyword = ''){
        $q = $this->table($this->_table)->select('*');
        if ($status === '0' || $status === '1') $q = $q->where('is_published', '=', (int) $status);
        if ($keyword !== '') $q = $q->whereLike('name', '%' . $keyword . '%');
        return $q->orderBy('sort_order', 'ASC')->orderBy('id', 'DESC')->get();
    }

    public function getPublished($limit = 0, $offset = 0){
        $q = $this->table($this->_table)->where('is_published', '=', 1)
                  ->orderBy('sort_order', 'ASC')->orderBy('id', 'DESC');
        if ($limit > 0) $q = $q->limit((int) $limit, (int) $offset);
        return $q->get();
    }

    public function countPublished(){
        $r = $this->table($this->_table)->select('COUNT(*) AS total')->where('is_published', '=', 1)->first();
        return (int) ($r['total'] ?? 0);
    }

    public function getBySlugPublished($slug){
        return $this->table($this->_table)->where('slug', '=', $slug)->where('is_published', '=', 1)->first();
    }

    public function getDetail($id){ return $this->getFirst($id); }
    public function findBySlug($slug){ return $this->table($this->_table)->where('slug', '=', $slug)->first(); }
    public function add($data){ $data['create_at'] = date('Y-m-d H:i:s'); $this->addNew($data); return $this->lastId(); }
    public function edit($data, $id){ $data['update_at'] = date('Y-m-d H:i:s'); return $this->updateById($data, $id); }
    public function remove($id){ return $this->deleteById($id); }
}

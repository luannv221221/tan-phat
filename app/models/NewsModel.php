<?php

use App\core\Model;

/**
 * CMS — Tin tức.
 */
class NewsModel extends Model {

    protected $_table   = 'news';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /** Danh sách quản trị: join tên danh mục, lọc trạng thái + từ khoá */
    public function getLists($status = '', $keyword = ''){
        $q = $this->table($this->_table)
            ->select('`news`.*, `news_categories`.`name` AS category_name')
            ->leftJoinOn('news_categories', 'news.category_id', 'news_categories.id');
        if ($status === '0' || $status === '1') $q = $q->where('news.is_published', '=', (int) $status);
        if ($keyword !== '') $q = $q->whereLike('news.title', '%' . $keyword . '%');
        return $q->orderBy('news.id', 'DESC')->get();
    }

    /** Tin ĐÃ đăng cho storefront (lọc theo danh mục nếu có) */
    public function getPublished($categoryId = 0, $limit = 0, $offset = 0){
        $q = $this->table($this->_table)
            ->select('`news`.*, `news_categories`.`name` AS category_name, `news_categories`.`slug` AS category_slug')
            ->leftJoinOn('news_categories', 'news.category_id', 'news_categories.id')
            ->where('news.is_published', '=', 1);
        if ($categoryId > 0) $q = $q->where('news.category_id', '=', (int) $categoryId);
        $q = $q->orderBy('news.published_at', 'DESC')->orderBy('news.id', 'DESC');
        if ($limit > 0) $q = $q->limit((int) $limit, (int) $offset);
        return $q->get();
    }

    public function countPublished($categoryId = 0){
        $q = $this->table($this->_table)->select('COUNT(*) AS total')->where('is_published', '=', 1);
        if ($categoryId > 0) $q = $q->where('category_id', '=', (int) $categoryId);
        $r = $q->first();
        return (int) ($r['total'] ?? 0);
    }

    public function getBySlugPublished($slug){
        return $this->table($this->_table)
            ->select('`news`.*, `news_categories`.`name` AS category_name, `news_categories`.`slug` AS category_slug')
            ->leftJoinOn('news_categories', 'news.category_id', 'news_categories.id')
            ->where('news.slug', '=', $slug)
            ->where('news.is_published', '=', 1)->first();
    }

    public function incrementView($id){
        $r = $this->getFirst($id);
        if (!empty($r)) $this->updateById(['view_count' => (int) $r['view_count'] + 1], $id);
    }

    public function getDetail($id){ return $this->getFirst($id); }
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
    public function remove($id){ return $this->deleteById($id); }
}

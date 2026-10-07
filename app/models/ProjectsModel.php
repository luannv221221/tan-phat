<?php

use App\core\Model;

/**
 * CMS — Dự án / công trình (portfolio) hiển thị trên website.
 *
 * Bảng `site_projects`. KHÁC `acc_projects` (mã vụ việc bên kế toán) — trước
 * đây bảng này tên `projects` nên rất dễ nhầm hai thứ với nhau.
 */
class ProjectsModel extends Model {

    protected $_table   = 'site_projects';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /* Nội dung website thuộc về MỘT gara — 07/10/2026, khi mỗi gara có trang
       riêng. Thiếu cờ này là gara nào mở web cũng thấy bài của gara khác. */
    protected $_theoGara = true;

    public function getLists($status = '', $keyword = ''){
        $q = $this->bangGara()->select('*');
        if ($status === '0' || $status === '1') $q = $q->where('is_published', '=', (int) $status);
        if ($keyword !== '') $q = $q->whereLike('name', '%' . $keyword . '%');
        return $q->orderBy('sort_order', 'ASC')->orderBy('id', 'DESC')->get();
    }

    public function getPublished($limit = 0, $offset = 0){
        $q = $this->bangGara()
            ->where('is_published', '=', 1)
            ->orderBy('sort_order', 'ASC')->orderBy('completed_at', 'DESC')->orderBy('id', 'DESC');
        if ($limit > 0) $q = $q->limit((int) $limit, (int) $offset);
        return $q->get();
    }

    public function countPublished(){
        $r = $this->bangGara()->select('COUNT(*) AS total')->where('is_published', '=', 1)->first();
        return (int) ($r['total'] ?? 0);
    }

    public function getBySlugPublished($slug){
        return $this->bangGara()->where('slug', '=', $slug)->where('is_published', '=', 1)->first();
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

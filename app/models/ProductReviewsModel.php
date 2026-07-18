<?php

use App\core\Model;

/**
 * CSKH — Đánh giá / bình luận sản phẩm (TASK_84).
 * status: 0 chờ duyệt / 1 đã duyệt (hiển thị công khai).
 */
class ProductReviewsModel extends Model {

    protected $_table   = 'product_reviews';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /** Đánh giá ĐÃ DUYỆT của 1 sản phẩm — cho storefront */
    public function getApprovedByPart($partId){
        return $this->table($this->_table)
            ->where('part_id', '=', (int) $partId)
            ->where('status', '=', 1)
            ->orderBy('id', 'DESC')->get();
    }

    /** Điểm trung bình + số lượt (đã duyệt) */
    public function summary($partId){
        $r = $this->table($this->_table)
            ->select('COUNT(*) AS cnt, AVG(`rating`) AS avg_rating')
            ->where('part_id', '=', (int) $partId)
            ->where('status', '=', 1)->first();
        return ['count' => (int) ($r['cnt'] ?? 0), 'avg' => round((float) ($r['avg_rating'] ?? 0), 1)];
    }

    /** Danh sách kiểm duyệt (admin) — kèm tên sản phẩm */
    public function getForModeration($status = ''){
        $q = $this->table($this->_table)
            ->select('`product_reviews`.*, `parts`.`code` AS part_code, `parts`.`name` AS part_name, `parts`.`slug` AS part_slug')
            ->joinOn('parts', 'product_reviews.part_id', 'parts.id');
        if ($status === '0' || $status === '1') $q = $q->where('product_reviews.status', '=', (int) $status);
        return $q->orderBy('product_reviews.status', 'ASC')->orderBy('product_reviews.id', 'DESC')->get();
    }

    public function countPending(){
        $r = $this->table($this->_table)->select('COUNT(*) AS c')->where('status', '=', 0)->first();
        return (int) ($r['c'] ?? 0);
    }

    public function getDetail($id){ return $this->getFirst($id); }

    /** Thành viên gửi đánh giá (chờ duyệt) */
    public function submit($data){
        $data['status']    = 0;
        $data['create_at'] = date('Y-m-d H:i:s');
        $this->addNew($data);
        return $this->lastId();
    }

    public function setStatus($id, $status){
        return $this->updateById(['status' => (int) $status], $id);
    }

    public function remove($id){ return $this->deleteById($id); }
}

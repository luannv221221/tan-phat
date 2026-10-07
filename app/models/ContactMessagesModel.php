<?php

use App\core\Model;

/**
 * CSKH web — Hộp thư liên hệ từ storefront.
 */
class ContactMessagesModel extends Model {

    protected $_table   = 'contact_messages';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /* Khách web và giao dịch web thuộc về MỘT gara — 07/10/2026, khi mỗi gara
       có website riêng. Thiếu cờ này là đơn đặt trên trang gara A rơi vào hộp
       đơn của gara B. */
    protected $_theoGara = true;

    public static $statuses = [
        'new'     => 'Mới',
        'handled' => 'Đã xử lý',
    ];

    public function getDetail($id){ return $this->getFirst($id); }

    public function getLists($status = '', $keyword = ''){
        $q = $this->bangGara();
        if ($status !== '' && isset(self::$statuses[$status])) $q = $q->where('status', '=', $status);
        if ($keyword !== ''){
            $q = $q->where(function($sub) use ($keyword){
                $like = '%' . $keyword . '%';
                $sub->whereLike('name', $like);
                $sub->whereOrLike('email', $like);
                $sub->whereOrLike('phone', $like);
                $sub->whereOrLike('subject', $like);
            });
        }
        return $q->orderBy('id', 'DESC')->get();
    }

    public function countNew(){
        $r = $this->bangGara()->select('COUNT(*) AS c')->where('status', '=', 'new')->first();
        return (int) ($r['c'] ?? 0);
    }

    public function add($data){
        $data['create_at'] = date('Y-m-d H:i:s');
        $this->addNew($data);
        return $this->lastId();
    }

    public function setStatus($id, $status){
        return $this->updateById(['status' => $status, 'update_at' => date('Y-m-d H:i:s')], (int) $id);
    }

    public function remove($id){ return $this->deleteById($id); }
}

<?php

use App\core\Model;

/**
 * CSKH web — Người đăng ký nhận bản tin.
 */
class NewsletterSubscribersModel extends Model {

    protected $_table   = 'newsletter_subscribers';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public function getDetail($id){ return $this->getFirst($id); }

    public function findByEmail($email){
        return $this->table($this->_table)->where('email', '=', $email)->first();
    }

    public function getLists($keyword = ''){
        $q = $this->table($this->_table);
        if ($keyword !== '') $q = $q->whereLike('email', '%' . $keyword . '%');
        return $q->orderBy('id', 'DESC')->get();
    }

    public function countActive(){
        $r = $this->table($this->_table)->select('COUNT(*) AS c')->where('status', '=', 1)->first();
        return (int) ($r['c'] ?? 0);
    }

    /** Đăng ký (idempotent): trả 'added' | 'exists' | 'reactivated' */
    public function subscribe($email, $source = 'storefront'){
        $email = strtolower(trim($email));
        $row = $this->findByEmail($email);
        if (!empty($row)){
            if ((int) $row['status'] === 1) return 'exists';
            $this->updateById(['status' => 1], (int) $row['id']);
            return 'reactivated';
        }
        $this->addNew(['email' => $email, 'status' => 1, 'source' => $source, 'create_at' => date('Y-m-d H:i:s')]);
        return 'added';
    }

    public function setStatus($id, $status){
        return $this->updateById(['status' => (int) $status], (int) $id);
    }

    public function remove($id){ return $this->deleteById($id); }
}

<?php

use App\core\Model;

/**
 * STOREFRONT — Thành viên website. Mật khẩu bcrypt.
 */
class MembersModel extends Model {

    protected $_table   = 'members';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public function findByEmail($email){
        return $this->table($this->_table)->where('email', '=', $email)->first();
    }

    public function getDetail($id){ return $this->getFirst($id); }

    /** Tạo thành viên mới (băm mật khẩu). @return int id */
    public function register($data){
        $data['password']  = password_hash($data['password'], PASSWORD_BCRYPT);
        $data['status']    = 1;
        $data['create_at'] = date('Y-m-d H:i:s');
        $this->addNew($data);
        return $this->lastId();
    }

    /** Xác thực đăng nhập. @return array|null bản ghi thành viên nếu đúng */
    public function checkLogin($email, $password){
        $m = $this->findByEmail($email);
        if (empty($m) || (int) $m['status'] !== 1) return null;
        if (!password_verify($password, $m['password'])) return null;
        return $m;
    }

    public function updateProfile($data, $id){
        $data['update_at'] = date('Y-m-d H:i:s');
        return $this->updateById($data, $id);
    }
}

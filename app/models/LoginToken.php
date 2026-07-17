<?php

use App\core\Model;

class LoginToken extends Model{
    protected $_table = 'login_token'; //Gán tên bảng
    protected $_fields = '*'; //Các field cần lấy khi fetch và fetchAll
    protected $_primary = 'id'; //Trường khoá chính

    public function add($data){
        $this->addNew($data);
        return $this->lastId();
    }

    public function remove($id){
        return $this->deleteById($id);
    }

    public function getToken($id){
        return $this->getFirst($id);
    }

    public function removeByUser($userId){
        return $this->delete($this->_table, '`user_id` = ?', [$userId]);
    }

    /**
     * Xoá token đã quá hạn — MỘT câu DELETE duy nhất.
     *
     * Bản cũ (AuthMiddleware::removeLoginToken) làm thế này ở MỌI request:
     *   1. getLists()  -> nạp TOÀN BỘ bảng login_token về PHP
     *   2. foreach     -> tính giờ trong PHP
     *   3. remove($id) -> MỘT câu DELETE cho TỪNG token hết hạn
     * Với 1.000 user đang đăng nhập: 1.000 dòng + tới 1.000 câu DELETE / request.
     *
     * Bản cũ còn bỏ sót: token có current_activity = NULL không bao giờ bị dọn
     * (vòng lặp bỏ qua bằng `if (!empty($item['current_activity']))`).
     * Auth::postLogin insert token KHÔNG kèm current_activity => NULL.
     * Ai đăng nhập rồi không vào trang admin sẽ để lại token sống mãi.
     * Nay dọn cả trường hợp đó dựa trên create_at.
     *
     * @param int $minutes Số phút không hoạt động thì coi là hết hạn
     */
    public function removeExpired($minutes = 15){
        $limit = date('Y-m-d H:i:s', time() - ($minutes * 60));

        return $this->delete(
            $this->_table,
            '(`current_activity` IS NOT NULL AND `current_activity` < ?)
             OR (`current_activity` IS NULL AND `create_at` < ?)',
            [$limit, $limit]
        );
    }

    public function edit($data, $id){
        return $this->updateById($data, $id);
    }

    public function getLists(){
        return $this->getList();
    }

}
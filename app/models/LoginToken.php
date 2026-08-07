<?php

use App\core\Model;

class LoginToken extends Model{

    /** Tên cookie ghi nhớ đăng nhập — dùng chung Auth và AuthMiddleware */
    const REMEMBER_COOKIE = 'tanphat_remember';

    /** Số ngày giữ đăng nhập khi có tick "Ghi nhớ" */
    const REMEMBER_DAYS = 30;

    protected $_table = 'login_tokens'; //Gán tên bảng
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
    public function removeExpired($minutes = 15, $rememberDays = 30){
        $limit    = date('Y-m-d H:i:s', time() - ($minutes * 60));
        $limitRem = date('Y-m-d H:i:s', time() - ($rememberDays * 86400));

        // Token có tick "ghi nhớ" đo bằng NGÀY, còn lại đo bằng PHÚT.
        // Trước đây mọi token đều bị xoá sau 15 phút không thao tác — đó chính
        // là lý do phiên đăng nhập hay hết, không phải do session PHP.
        return $this->delete(
            $this->_table,
            '(`remember` = 0 AND (
                  (`current_activity` IS NOT NULL AND `current_activity` < ?)
               OR (`current_activity` IS NULL AND `create_at` < ?)))
             OR (`remember` = 1 AND (
                  (`current_activity` IS NOT NULL AND `current_activity` < ?)
               OR (`current_activity` IS NULL AND `create_at` < ?)))',
            [$limit, $limit, $limitRem, $limitRem]
        );
    }

    /** Tìm token theo cookie ghi nhớ (so bằng HASH, cookie giữ bản gốc) */
    public function findByRemember($rawCookie){
        if ($rawCookie === '' || $rawCookie === null) return null;

        $r = $this->table($this->_table)
                  ->where('remember', '=', 1)
                  ->where('remember_hash', '=', hash('sha256', $rawCookie))
                  ->first();

        return !empty($r) ? $r : null;
    }

    public function edit($data, $id){
        return $this->updateById($data, $id);
    }

    public function getLists(){
        return $this->getList();
    }

}
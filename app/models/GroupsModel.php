<?php
use App\core\Model;
class GroupsModel extends Model{
    protected $_table = 'groups'; //Gán tên bảng
    protected $_fields = '*'; //Các field cần lấy khi fetch và fetchAll
    protected $_primary = 'id'; //Trường khoá chính

    public function getLists(){
        return $this->getList();
    }

    public function add($data){
        return $this->addNew($data);
    }

    public function edit($data, $id){
        return $this->updateById($data, $id);
    }

    public function remove($id){
        return $this->deleteById($id);
    }

    public function getDetail($id){
        return $this->getFirst($id);
    }

    public function getGroupByUser($userId){
        // joinOn() bọc backtick tự động.
        // Bản cũ: join($this->_table, 'users.group_id=groups.id')
        // => sinh ra `groups`.id không backtick. `GROUPS` là TỪ KHOÁ DÀNH RIÊNG
        //    của MySQL 8.0 (window function) nên câu này lỗi cú pháp trên MySQL 8.
        $data = $this->table('users')
            ->joinOn($this->_table, 'users.group_id', $this->_table . '.id')
            ->select('users.group_id')
            ->where('users.id', '=', $userId)
            ->first();
        return $data;
    }
}
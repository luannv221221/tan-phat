<?php
use App\core\Model;

class PermissionsModel extends Model{
    protected $_table = 'permissions'; //Gán tên bảng
    protected $_fields = '*'; //Các field cần lấy khi fetch và fetchAll
    protected $_primary = 'id'; //Trường khoá chính

    public function add($data){
        return $this->addNew($data);
    }

    public function remove($id){
        return $this->delete($this->_table, '`group_id` = ?', [$id]);
    }

    public function getPermission($groupId){
        return $this->getList('`group_id` = ?', [$groupId]);
    }
}
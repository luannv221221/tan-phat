<?php
use App\core\Model;
class ModulesModel extends Model{
    protected $_table = 'modules'; //Gán tên bảng
    protected $_fields = '*'; //Các field cần lấy khi fetch và fetchAll
    protected $_primary = 'id'; //Trường khoá chính

    public function getLists(){
        return $this->getList();
    }
}
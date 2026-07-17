<?php
//Lớp core model kế thừa từ lớp database
//1 số ứng dụng có tên là db_bussiness

namespace App\core;

use App\core\Database;

class Model extends Database {

    protected $_table;   //Gán tên bảng
    protected $_fields;  //Các field cần lấy khi fetch và fetchAll
    protected $_primary; //Trường khoá chính

    /** Kiểm tra model đã khai báo đủ thuộc tính bắt buộc chưa */
    protected function assertConfigured($needPrimary = false){
        if (empty($this->_fields)) die('Thiếu thuộc tính $_fields');
        if (empty($this->_table))  die('Thiếu thuộc tính $_table');
        if ($needPrimary && empty($this->_primary)) die('Thiếu thuộc tính $_primary');
    }

    /**
     * Lấy tất cả bản ghi.
     *
     * @param string $where    Mệnh đề WHERE dùng placeholder `?`, vd: 'group_id = ?'
     * @param array  $bindings Giá trị cho các `?`
     *
     * CẢNH BÁO: $where là chuỗi raw. Chỉ viết tên cột + `?`,
     * không nối giá trị người dùng vào đây.
     */
    public function getList($where='', array $bindings = []){
        $this->assertConfigured();

        $sql = "SELECT $this->_fields FROM ".$this->wrapField($this->_table);

        if (!empty($where)){
            $sql .= ' WHERE '.$where;
        }

        return $this->getRaw($sql, $bindings);
    }

    /**
     * Lấy danh sách theo giới hạn (phân trang).
     * $limit/$start ép kiểu int nên an toàn.
     */
    public function getLimit($limit, $start=0, $where='', array $bindings = []){
        $this->assertConfigured();

        $limit = (int)$limit;
        $start = (int)$start;

        $sql = "SELECT $this->_fields FROM ".$this->wrapField($this->_table);

        if (!empty($where)){
            $sql .= ' WHERE '.$where;
        }

        $sql .= " LIMIT $start, $limit";

        return $this->getRaw($sql, $bindings);
    }

    /** Lấy 1 bản ghi theo khoá chính */
    public function getFirst($id){
        $this->assertConfigured(true);

        $sql = "SELECT $this->_fields FROM ".$this->wrapField($this->_table)
             . ' WHERE '.$this->wrapField($this->_primary).' = ?';

        return $this->firstRaw($sql, [$id]);
    }

    /** Thêm bản ghi */
    public function addNew($data){
        return $this->insert($this->_table, $data);
    }

    /** Sửa bản ghi theo khoá chính */
    public function updateById($data, $id){
        $this->assertConfigured(true);

        $where = $this->wrapField($this->_primary).' = ?';

        return $this->update($this->_table, $data, $where, [$id]);
    }

    /** Xoá bản ghi theo khoá chính */
    public function deleteById($id){
        $this->assertConfigured(true);

        $where = $this->wrapField($this->_primary).' = ?';

        return $this->delete($this->_table, $where, [$id]);
    }
}

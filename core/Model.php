<?php
//Lớp core model kế thừa từ lớp database
//1 số ứng dụng có tên là db_bussiness

namespace App\core;

use App\core\Database;

class Model extends Database {

    protected $_table;   //Gán tên bảng
    protected $_fields;  //Các field cần lấy khi fetch và fetchAll
    protected $_primary; //Trường khoá chính

    /**
     * Bảng này là dữ liệu RIÊNG của từng gara (có cột `garage_id`).
     *
     * Bật lên thì getList / getLimit / getFirst / updateById / deleteById tự
     * thêm điều kiện "thuộc gara đang làm việc", addNew tự ghi gara đó — gara B
     * gõ tay /edit/<id của gara A> nhận "không tìm thấy", không phụ thuộc việc
     * controller có nhớ kiểm hay không.
     *
     * Truy vấn tự viết (getRaw, table()->...) KHÔNG được lớp này chặn: dùng
     * dkGara() để thêm điều kiện.
     */
    protected $_theoGara = false;

    /** Gara ép từ ngoài — chỉ có tác dụng ở dòng lệnh (test, công cụ) */
    private static $__garaEp = null;

    /** Kiểm tra model đã khai báo đủ thuộc tính bắt buộc chưa */
    protected function assertConfigured($needPrimary = false){
        if (empty($this->_fields)) die('Thiếu thuộc tính $_fields');
        if (empty($this->_table))  die('Thiếu thuộc tính $_table');
        if ($needPrimary && empty($this->_primary)) die('Thiếu thuộc tính $_primary');
    }

    /** Ép gara cho dòng lệnh. null = bỏ ép; 0 = giả lập "không xác định được gara". */
    public static function epGara($id){
        self::$__garaEp = $id === null ? null : (int) $id;
    }

    /**
     * Gara để lọc:
     *   > 0  -> lọc theo gara đó
     *   0    -> ĐÓNG: không khớp dòng nào (web mà không xác định được gara)
     *   null -> không lọc (dòng lệnh chưa ép gara: migrate, gieo dữ liệu, xuất SQL)
     */
    public static function garaLoc(){
        if (PHP_SAPI === 'cli') return self::$__garaEp;
        $id = function_exists('gara_hien_tai_id') ? gara_hien_tai_id() : null;
        return $id ? (int) $id : 0;
    }

    /**
     * Điều kiện gara cho truy vấn tự viết: [sql, bindings].
     *   dkGara('q') -> ["`q`.`garage_id` = ?", [5]]
     * Không lọc -> ['1 = 1', []]; đóng -> ['1 = 0', []].
     */
    public function dkGara($bi = ''){
        $g = self::garaLoc();
        if ($g === null) return ['1 = 1', []];
        if ($g === 0)    return ['1 = 0', []];
        $cot = ($bi !== '' ? $this->wrapField($bi) . '.' : '') . '`garage_id`';
        return [$cot . ' = ?', [$g]];
    }

    /** Ghép điều kiện gara vào $where của các hàm có sẵn (bind của gara đứng SAU) */
    private function voiGara($where, array $bindings){
        if (!$this->_theoGara) return [$where, $bindings];
        list($dk, $b) = $this->dkGara($this->_table);
        $where = $where !== '' ? '(' . $where . ') AND ' . $dk : $dk;
        return [$where, array_merge($bindings, $b)];
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
        list($where, $bindings) = $this->voiGara((string) $where, $bindings);

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
        list($where, $bindings) = $this->voiGara((string) $where, $bindings);

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
        list($where, $bindings) = $this->voiGara($this->wrapField($this->_primary).' = ?', [$id]);

        $sql = "SELECT $this->_fields FROM ".$this->wrapField($this->_table).' WHERE '.$where;

        return $this->firstRaw($sql, $bindings);
    }

    /** Thêm bản ghi */
    public function addNew($data){
        if ($this->_theoGara){
            $g = self::garaLoc();
            if ($g === 0){
                throw new \RuntimeException('Khong xac dinh duoc gara lam viec — khong ghi du lieu.');
            }
            if ($g !== null) $data['garage_id'] = $g;
        }
        return $this->insert($this->_table, $data);
    }

    /** Sửa bản ghi theo khoá chính */
    public function updateById($data, $id){
        $this->assertConfigured(true);

        /* Không cho form chuyển dữ liệu sang gara khác */
        if ($this->_theoGara && self::garaLoc() !== null) unset($data['garage_id']);

        list($where, $bindings) = $this->voiGara($this->wrapField($this->_primary).' = ?', [$id]);

        return $this->update($this->_table, $data, $where, $bindings);
    }

    /** Xoá bản ghi theo khoá chính */
    public function deleteById($id){
        $this->assertConfigured(true);
        list($where, $bindings) = $this->voiGara($this->wrapField($this->_primary).' = ?', [$id]);

        return $this->delete($this->_table, $where, $bindings);
    }
}

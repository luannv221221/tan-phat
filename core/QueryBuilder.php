<?php

namespace App\core;

/**
 * QueryBuilder — sinh câu lệnh SQL dùng bound parameters.
 *
 * QUY TẮC BẤT DI BẤT DỊCH:
 *   - Giá trị (value)     -> LUÔN đi qua placeholder `?` + $bindings. Không bao giờ nối vào chuỗi SQL.
 *   - Định danh (field)   -> đi qua wrapField(), chỉ chấp nhận [a-zA-Z0-9_.] rồi bọc backtick.
 *   - Toán tử (compare)   -> đi qua whitelist. Không nằm trong whitelist thì ném exception.
 *
 * Sai một trong ba quy tắc trên là mở lại lỗ SQL injection.
 */
trait QueryBuilder{

    public $tableQuery = '';
    public $fieldQuery = '*';
    public $whereQuery = '';
    public $joinQuery = '';
    public $limitQuery = '';
    public $orderByQuery = '';
    public $groupByQuery = '';
    public $havingQuery = '';

    /** Giá trị sẽ được bind vào placeholder, theo đúng thứ tự xuất hiện trong SQL */
    public $bindings = [];

    /** Toán tử so sánh được phép dùng */
    protected static $allowedOperators = [
        '=', '!=', '<>', '<', '>', '<=', '>=',
        'LIKE', 'NOT LIKE', 'IS', 'IS NOT',
    ];

    // ================= Helper nội bộ =================

    /**
     * Bọc định danh (tên bảng/cột) bằng backtick sau khi kiểm tra ký tự hợp lệ.
     * Cho phép: users, users.id, *, users.*
     * Ném exception nếu có ký tự lạ — chặn injection qua tên cột.
     */
    protected function wrapField($field){
        $field = trim($field);

        if ($field === '*') return '*';

        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.([A-Za-z_][A-Za-z0-9_]*|\*))?$/', $field)){
            throw new \InvalidArgumentException("Ten cot/bang khong hop le: '$field'");
        }

        $parts = explode('.', $field);
        foreach ($parts as $i => $p){
            $parts[$i] = ($p === '*') ? '*' : '`'.$p.'`';
        }
        return implode('.', $parts);
    }

    /** Kiểm tra toán tử nằm trong whitelist */
    protected function wrapOperator($compare){
        $compare = strtoupper(trim($compare));
        if (!in_array($compare, self::$allowedOperators, true)){
            throw new \InvalidArgumentException("Toan tu khong duoc phep: '$compare'");
        }
        return $compare;
    }

    /**
     * Xác định từ nối cho mệnh đề where kế tiếp.
     * Thay cho thủ thuật strpos('WHERE') + str_replace('( AND') của bản cũ:
     * bản cũ dò chữ 'WHERE' trong chuỗi SQL đã chứa cả giá trị người dùng,
     * nên một giá trị chứa chữ "WHERE" có thể làm lệch logic.
     */
    protected function whereConnector($isOr = false){
        $w = rtrim($this->whereQuery);
        if ($w === '')                 return ' WHERE ';
        if (substr($w, -1) === '(')    return '';           // ngay sau dấu mở nhóm
        return $isOr ? ' OR ' : ' AND ';
    }

    /** Mở nhóm điều kiện lồng nhau: where(function($q){ ... }) */
    protected function whereGroup(\Closure $callback, $isOr = false){
        $this->whereQuery .= $this->whereConnector($isOr) . '(';
        $callback($this);
        $this->whereQuery .= ')';
        return $this;
    }

    /** Dựng mệnh đề IN / NOT IN với đúng số placeholder */
    protected function buildIn($field, array $valueArr, $not, $isOr){
        if (empty($valueArr)){
            // IN() rỗng là SQL không hợp lệ. Dùng hằng luôn-sai (hoặc luôn-đúng với NOT IN).
            $this->whereQuery .= $this->whereConnector($isOr) . ($not ? '1=1' : '1=0');
            return $this;
        }

        $placeholders = implode(',', array_fill(0, count($valueArr), '?'));
        $this->whereQuery .= $this->whereConnector($isOr)
                           . $this->wrapField($field)
                           . ($not ? ' NOT IN(' : ' IN(') . $placeholders . ')';

        foreach ($valueArr as $v){
            $this->bindings[] = $v;
        }
        return $this;
    }

    /** Xoá toàn bộ state sau khi chạy xong 1 câu truy vấn (sửa lỗi H1) */
    public function resetQuery(){
        $this->tableQuery   = '';
        $this->fieldQuery   = '*';
        $this->whereQuery   = '';
        $this->joinQuery    = '';
        $this->limitQuery   = '';
        $this->orderByQuery = '';
        $this->groupByQuery = '';
        $this->havingQuery  = '';
        $this->bindings     = [];
        return $this;
    }

    // ================= API công khai =================

    public function table($table){
        $this->tableQuery = $table;
        return $this;
    }

    /**
     * Danh sách field. Đây là chuỗi do lập trình viên viết (vd: 'users.id, groups.name as g'),
     * KHÔNG phải dữ liệu người dùng — nên giữ nguyên dạng raw.
     * Tuyệt đối không truyền dữ liệu từ $_GET/$_POST vào đây.
     */
    public function select($field=''){
        if (!empty($field)){
            $this->fieldQuery = $field;
        }
        return $this;
    }

    public function where($field, $compare='', $value=''){
        if ($field instanceof \Closure){
            return $this->whereGroup($field, false);
        }

        $this->whereQuery .= $this->whereConnector(false)
                           . $this->wrapField($field) . ' '
                           . $this->wrapOperator($compare) . ' ?';
        $this->bindings[] = $value;
        return $this;
    }

    public function orWhere($field, $compare='', $value=''){
        if ($field instanceof \Closure){
            return $this->whereGroup($field, true);
        }

        $this->whereQuery .= $this->whereConnector(true)
                           . $this->wrapField($field) . ' '
                           . $this->wrapOperator($compare) . ' ?';
        $this->bindings[] = $value;
        return $this;
    }

    public function whereLike($field, $value){
        $this->whereQuery .= $this->whereConnector(false)
                           . $this->wrapField($field) . ' LIKE ?';
        $this->bindings[] = $value;
        return $this;
    }

    public function whereOrLike($field, $value){
        $this->whereQuery .= $this->whereConnector(true)
                           . $this->wrapField($field) . ' LIKE ?';
        $this->bindings[] = $value;
        return $this;
    }

    public function whereIn($field, $valueArr){
        return $this->buildIn($field, (array)$valueArr, false, false);
    }

    public function whereNotIn($field, $valueArr){
        return $this->buildIn($field, (array)$valueArr, true, false);
    }

    public function whereOrIn($field, $valueArr){
        return $this->buildIn($field, (array)$valueArr, false, true);
    }

    public function whereOrNotIn($field, $valueArr){
        return $this->buildIn($field, (array)$valueArr, true, true);
    }

    public function whereNull($field){
        $this->whereQuery .= $this->whereConnector(false) . $this->wrapField($field) . ' IS NULL';
        return $this;
    }

    public function whereNotNull($field){
        $this->whereQuery .= $this->whereConnector(false) . $this->wrapField($field) . ' IS NOT NULL';
        return $this;
    }

    /** Kiểu JOIN được phép */
    protected static $allowedJoins = ['INNER', 'LEFT', 'RIGHT'];

    /**
     * JOIN với định danh được bọc backtick TỰ ĐỘNG — NÊN DÙNG CÁI NÀY.
     *
     *   ->joinOn('groups', 'users.group_id', 'groups.id')
     *   => INNER JOIN `groups` ON `users`.`group_id` = `groups`.`id`
     *
     * Vì sao cần: `groups` là TỪ KHOÁ DÀNH RIÊNG của MySQL 8.0 (window function GROUPS).
     * Viết `groups.id` không backtick sẽ lỗi cú pháp trên MySQL 8.
     * Bọc backtick bằng tay thì sớm muộn cũng có người quên — nên để builder tự làm.
     *
     * @param string $type INNER | LEFT | RIGHT
     */
    public function joinOn($table, $left, $right, $type = 'INNER'){
        $type = strtoupper(trim($type));
        if (!in_array($type, self::$allowedJoins, true)){
            throw new \InvalidArgumentException("Kieu JOIN khong hop le: '$type'");
        }

        $this->joinQuery .= ' ' . $type . ' JOIN ' . $this->wrapField($table)
                          . ' ON ' . $this->wrapField($left)
                          . ' = ' . $this->wrapField($right) . ' ';
        return $this;
    }

    public function leftJoinOn($table, $left, $right){
        return $this->joinOn($table, $left, $right, 'LEFT');
    }

    public function rightJoinOn($table, $left, $right){
        return $this->joinOn($table, $left, $right, 'RIGHT');
    }

    /**
     * @deprecated Dùng joinOn() thay thế.
     *
     * $relation là biểu thức nối bảng dạng RAW do lập trình viên viết
     * (vd: 'users.group_id=`groups`.id'). Builder KHÔNG bọc backtick giúp,
     * nên rất dễ vỡ với từ khoá dành riêng của MySQL 8 (groups, rank, rows...).
     *
     * TUYỆT ĐỐI không truyền dữ liệu người dùng vào $relation.
     */
    public function join($table, $relation){
        $this->joinQuery .= ' INNER JOIN '.$this->wrapField($table).' ON '.$relation.' ';
        return $this;
    }

    /** @deprecated Dùng leftJoinOn() thay thế. */
    public function leftJoin($table, $relation){
        $this->joinQuery .= ' LEFT JOIN '.$this->wrapField($table).' ON '.$relation.' ';
        return $this;
    }

    /** @deprecated Dùng rightJoinOn() thay thế. */
    public function rightJoin($table, $relation){
        $this->joinQuery .= ' RIGHT JOIN '.$this->wrapField($table).' ON '.$relation.' ';
        return $this;
    }

    /** LIMIT: ép kiểu int nên an toàn, không cần placeholder (MySQL không bind được LIMIT ở mọi driver) */
    public function limit($number, $start=0){
        $number = (int)$number;
        $start  = (int)$start;
        $this->limitQuery = "LIMIT $start, $number";
        return $this;
    }

    public function orderBy($field, $type='ASC'){
        $type = strtoupper(trim($type)) === 'DESC' ? 'DESC' : 'ASC';

        $operator = (strpos($this->orderByQuery, 'ORDER BY') !== false) ? ', ' : 'ORDER BY ';
        $this->orderByQuery .= $operator . $this->wrapField($field) . ' ' . $type;
        return $this;
    }

    public function groupBy($field){
        $this->groupByQuery = 'GROUP BY ' . $this->wrapField($field);
        return $this;
    }

    public function having($field, $compare, $value){
        $this->havingQuery = 'HAVING ' . $this->wrapField($field) . ' '
                           . $this->wrapOperator($compare) . ' ?';
        $this->bindings[] = $value;
        return $this;
    }

    /** Ghép câu SELECT hoàn chỉnh từ các mảnh */
    protected function compileSelect(){
        return "SELECT $this->fieldQuery FROM "
             . $this->wrapField($this->tableQuery)
             . " $this->joinQuery $this->whereQuery $this->groupByQuery $this->havingQuery $this->orderByQuery $this->limitQuery";
    }

    public function get(){
        $sql      = $this->compileSelect();
        $bindings = $this->bindings;
        $this->resetQuery();
        return $this->getRaw($sql, $bindings);
    }

    public function first(){
        $sql      = $this->compileSelect();
        $bindings = $this->bindings;
        $this->resetQuery();
        return $this->firstRaw($sql, $bindings);
    }
}

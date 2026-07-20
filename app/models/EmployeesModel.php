<?php

use App\core\Model;

/** HR — Hồ sơ nhân viên. */
class EmployeesModel extends Model {

    protected $_table   = 'employees';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public static $genders = ['male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác'];

    public function getLists($deptId = 0, $status = '', $keyword = ''){
        $q = $this->table($this->_table)
            ->select('`employees`.*, `departments`.`name` AS dept_name, `positions`.`name` AS pos_name')
            ->leftJoinOn('departments', 'employees.department_id', 'departments.id')
            ->leftJoinOn('positions', 'employees.position_id', 'positions.id');
        if ($deptId > 0) $q = $q->where('employees.department_id', '=', (int) $deptId);
        if ($status === '0' || $status === '1') $q = $q->where('employees.status', '=', (int) $status);
        if ($keyword !== ''){
            $q = $q->where(function($sub) use ($keyword){
                $like = '%' . $keyword . '%';
                $sub->whereLike('employees.name', $like);
                $sub->whereOrLike('employees.code', $like);
                $sub->whereOrLike('employees.phone', $like);
            });
        }
        return $q->orderBy('employees.code', 'ASC')->get();
    }

    /** Đang làm việc — cho dropdown chọn nhân viên (đơn nghỉ phép) */
    public function getActive(){
        return $this->table($this->_table)->where('status', '=', 1)->orderBy('name', 'ASC')->get();
    }

    public function getDetail($id){ return $this->getFirst($id); }
    public function findByCode($code){ return $this->table($this->_table)->where('code', '=', $code)->first(); }
    public function add($data){ $data['create_at'] = date('Y-m-d H:i:s'); $this->addNew($data); return $this->lastId(); }
    public function edit($data, $id){ $data['update_at'] = date('Y-m-d H:i:s'); return $this->updateById($data, $id); }
    public function remove($id){ return $this->deleteById($id); }
}

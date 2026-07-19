<?php

use App\core\Model;

/** HR — Đơn nghỉ phép. Luồng pending -> approved / rejected. */
class LeaveRequestsModel extends Model {

    protected $_table   = 'leave_requests';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public static $statuses = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối'];
    public static $types    = ['annual' => 'Nghỉ phép năm', 'sick' => 'Nghỉ ốm', 'unpaid' => 'Nghỉ không lương', 'other' => 'Khác'];

    public function getLists($status = '', $from = '', $to = ''){
        $q = $this->table($this->_table)
            ->select('`leave_requests`.*, `employees`.`name` AS emp_name, `employees`.`code` AS emp_code')
            ->joinOn('employees', 'leave_requests.employee_id', 'employees.id');
        if ($status !== '' && isset(self::$statuses[$status])) $q = $q->where('leave_requests.status', '=', $status);
        if ($from !== '') $q = $q->where('leave_requests.from_date', '>=', $from);
        if ($to !== '')   $q = $q->where('leave_requests.from_date', '<=', $to);
        return $q->orderBy('leave_requests.id', 'DESC')->get();
    }

    public function countPending(){
        $r = $this->table($this->_table)->select('COUNT(*) AS c')->where('status', '=', 'pending')->first();
        return (int) ($r['c'] ?? 0);
    }

    public function getDetail($id){ return $this->getFirst($id); }
    public function add($data){ $data['create_at'] = date('Y-m-d H:i:s'); $this->addNew($data); return $this->lastId(); }
    public function edit($data, $id){ $data['update_at'] = date('Y-m-d H:i:s'); return $this->updateById($data, $id); }
    public function remove($id){ return $this->deleteById($id); }
}

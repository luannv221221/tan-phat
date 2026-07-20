<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/** HR — Đơn nghỉ phép (CRUD + duyệt). */
class Leaverequests extends Controller {

    private $__data = [];
    private $__model, $__emp, $__request, $__response;
    private $routeBase = 'leave-requests';
    private $labelOne  = 'đơn nghỉ phép';
    private $labelMany = 'Đơn nghỉ phép';
    private $viewDir   = 'admin/leave-requests';

    function __construct(){
        $this->__model    = $this->model('LeaveRequestsModel');
        $this->__emp      = $this->model('EmployeesModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    private function baseData(){
        $this->__data['content']['routeBase'] = $this->routeBase;
        $this->__data['content']['labelOne']  = $this->labelOne;
        $this->__data['content']['statuses']  = LeaveRequestsModel::$statuses;
        $this->__data['content']['types']     = LeaveRequestsModel::$types;
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;
        $this->baseData();
        $f = $this->__request->getFields();
        $status = isset($f['status']) && isset(LeaveRequestsModel::$statuses[$f['status']]) ? $f['status'] : '';
        $from   = isset($f['from']) ? trim($f['from']) : '';
        $to     = isset($f['to'])   ? trim($f['to'])   : '';
        $this->__data['content']['page_name']    = $this->labelMany;
        $this->__data['content']['dataList']     = $this->__model->getLists($status, $from, $to);
        $this->__data['content']['pending']      = $this->__model->countPending();
        $this->__data['content']['filterStatus'] = $status;
        $this->__data['content']['filterFrom']   = $from;
        $this->__data['content']['filterTo']     = $to;
        $this->__data['content']['msg']          = Session::flash('msg');
        $this->__data['content']['msgError']     = Session::flash('msgError');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function add(){
        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Lập ' . $this->labelOne;
        $this->baseData();
        $this->__data['content']['page_name'] = 'Lập ' . $this->labelOne;
        $this->__data['content']['employees'] = $this->__emp->getActive();
        $this->__data['content']['item']      = null;
        $this->__data['content']['today']     = date('Y-m-d');
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $errors = $this->validate();
        if (!empty($errors)){ $this->flash($errors, 'add'); return; }
        $this->__model->add(array_merge($this->buildData(), [
            'status'     => 'pending',
            'created_by' => Session::get('dataUser'),
        ]));
        Session::flash('msg', 'Đã lập ' . $this->labelOne);
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    public function edit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Sửa ' . $this->labelOne;
        $this->baseData();
        $this->__data['content']['page_name'] = 'Sửa ' . $this->labelOne;
        $this->__data['content']['employees'] = $this->__emp->getActive();
        $this->__data['content']['item']      = $item;
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        $errors = $this->validate();
        if (!empty($errors)){ $this->flash($errors, 'edit/' . $id); return; }
        $this->__model->edit($this->buildData(), $id);
        Session::flash('msg', 'Cập nhật ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    /** Duyệt / từ chối đơn */
    public function setStatus($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){ $this->__response->redirect('admin/khong-co-quyen'); return; }
        $f = $this->__request->getFields();
        $st = isset($f['status']) ? $f['status'] : '';
        if (!isset(LeaveRequestsModel::$statuses[$st])){ Session::flash('msgError', 'Trạng thái không hợp lệ'); $this->__response->redirect('admin/' . $this->routeBase); return; }
        $this->__model->edit(['status' => $st], $id);
        Session::flash('msg', 'Đã chuyển: ' . LeaveRequestsModel::$statuses[$st]);
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    public function delete($id){
        if (empty($this->__model->getDetail($id))){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        $this->__model->remove($id);
        Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ===== Helper =====
    private function validate(){
        $f = $this->__request->getFields();
        $errors = [];
        $empId = !empty($f['employee_id']) ? (int) $f['employee_id'] : 0;
        if ($empId <= 0 || empty($this->__emp->getDetail($empId))) $errors['employee_id'] = 'Chọn nhân viên';
        if (empty($f['from_date'])) $errors['from_date'] = 'Chọn ngày bắt đầu';
        if (empty($f['to_date']))   $errors['to_date'] = 'Chọn ngày kết thúc';
        if (!empty($f['from_date']) && !empty($f['to_date']) && $f['to_date'] < $f['from_date']) $errors['to_date'] = 'Ngày kết thúc phải sau ngày bắt đầu';
        return $errors;
    }
    private function buildData(){
        $f = $this->__request->getFields();
        $days = isset($f['days']) ? (float) str_replace(',', '.', preg_replace('/[^\d.,]/', '', (string) $f['days'])) : 0;
        if ($days <= 0 && !empty($f['from_date']) && !empty($f['to_date'])){
            $days = (strtotime($f['to_date']) - strtotime($f['from_date'])) / 86400 + 1;
        }
        return [
            'employee_id' => (int) $f['employee_id'],
            'leave_type'  => (!empty($f['leave_type']) && isset(LeaveRequestsModel::$types[$f['leave_type']])) ? $f['leave_type'] : 'annual',
            'from_date'   => $f['from_date'],
            'to_date'     => $f['to_date'],
            'days'        => $days > 0 ? $days : 1,
            'reason'      => !empty($f['reason']) ? trim($f['reason']) : null,
        ];
    }
    private function flash($errors, $back){
        Session::flash('errors', $errors);
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}

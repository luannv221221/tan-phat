<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/** HR — Hồ sơ nhân viên (CRUD). */
class Employees extends Controller {

    private $__data = [];
    private $__model, $__dept, $__pos, $__request, $__response;
    private $routeBase = 'employees';
    private $labelOne  = 'nhân viên';
    private $labelMany = 'Nhân viên';
    private $viewDir   = 'admin/employees';

    function __construct(){
        $this->__model    = $this->model('EmployeesModel');
        $this->__dept     = $this->model('DepartmentsModel');
        $this->__pos      = $this->model('PositionsModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    private function baseData(){
        $this->__data['content']['routeBase'] = $this->routeBase;
        $this->__data['content']['labelOne']  = $this->labelOne;
        $this->__data['content']['genders']   = EmployeesModel::$genders;
    }
    private function formData(){
        $this->__data['content']['departments'] = $this->__dept->getActive();
        $this->__data['content']['positions']   = $this->__pos->getActive();
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;
        $this->baseData();
        $f = $this->__request->getFields();
        $dept    = !empty($f['dept']) ? (int) $f['dept'] : 0;
        $status  = isset($f['status']) ? trim($f['status']) : '';
        $keyword = isset($f['q']) ? trim($f['q']) : '';
        $this->__data['content']['page_name']     = $this->labelMany;
        $this->__data['content']['dataList']      = $this->__model->getLists($dept, $status, $keyword);
        $this->__data['content']['departments']   = $this->__dept->getActive();
        $this->__data['content']['filterDept']    = $dept;
        $this->__data['content']['filterStatus']  = $status;
        $this->__data['content']['filterKeyword'] = $keyword;
        $this->__data['content']['msg']           = Session::flash('msg');
        $this->__data['content']['msgError']      = Session::flash('msgError');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function add(){
        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Thêm ' . $this->labelOne;
        $this->baseData(); $this->formData();
        $this->__data['content']['page_name'] = 'Thêm ' . $this->labelOne;
        $this->__data['content']['item']      = null;
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $errors = $this->validate();
        if (!empty($errors)){ $this->flash($errors, 'add'); return; }
        $data = $this->buildData();
        if (!empty($this->__model->findByCode($data['code']))){ $this->flash(['code' => 'Mã nhân viên đã tồn tại'], 'add'); return; }
        $this->__model->add($data);
        Session::flash('msg', 'Thêm ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    public function edit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Sửa ' . $this->labelOne;
        $this->baseData(); $this->formData();
        $this->__data['content']['page_name'] = 'Sửa ' . $this->labelOne;
        $this->__data['content']['item']      = $item;
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){
        if (empty($this->__model->getDetail($id))){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        $errors = $this->validate();
        if (!empty($errors)){ $this->flash($errors, 'edit/' . $id); return; }
        $data = $this->buildData();
        $ex = $this->__model->findByCode($data['code']);
        if (!empty($ex) && $ex['id'] != $id){ $this->flash(['code' => 'Mã nhân viên đã thuộc người khác'], 'edit/' . $id); return; }
        $this->__model->edit($data, $id);
        Session::flash('msg', 'Cập nhật ' . $this->labelOne . ' thành công');
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
        if (empty($f['code']) || trim($f['code']) === '') $errors['code'] = 'Nhập mã nhân viên';
        if (empty($f['name']) || trim($f['name']) === '') $errors['name'] = 'Nhập họ tên';
        return $errors;
    }
    private function buildData(){
        $f = $this->__request->getFields();
        $deptId = !empty($f['department_id']) ? (int) $f['department_id'] : 0;
        $posId  = !empty($f['position_id']) ? (int) $f['position_id'] : 0;
        return [
            'code'          => trim($f['code']),
            'name'          => trim($f['name']),
            'department_id' => ($deptId > 0 && !empty($this->__dept->getDetail($deptId))) ? $deptId : null,
            'position_id'   => ($posId > 0 && !empty($this->__pos->getDetail($posId))) ? $posId : null,
            'gender'        => (!empty($f['gender']) && isset(EmployeesModel::$genders[$f['gender']])) ? $f['gender'] : null,
            'dob'           => !empty($f['dob']) ? $f['dob'] : null,
            'phone'         => !empty($f['phone']) ? trim($f['phone']) : null,
            'email'         => !empty($f['email']) ? trim($f['email']) : null,
            'address'       => !empty($f['address']) ? trim($f['address']) : null,
            'hire_date'     => !empty($f['hire_date']) ? $f['hire_date'] : null,
            'salary_base'   => isset($f['salary_base']) ? (float) preg_replace('/[^\d]/', '', (string) $f['salary_base']) : 0,
            'status'        => !empty($f['status']) ? 1 : 0,
            'note'          => !empty($f['note']) ? trim($f['note']) : null,
        ];
    }
    private function flash($errors, $back){
        Session::flash('errors', $errors);
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}

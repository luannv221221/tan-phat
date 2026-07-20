<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/** KT-4 — Đối tượng (khách hàng / nhà cung cấp). */
class Partners extends Controller {

    private $__data = [];
    private $__model, $__request, $__response;

    private $routeBase = 'partners';
    private $labelOne  = 'đối tượng';
    private $labelMany = 'Đối tượng (khách / NCC)';
    private $viewDir   = 'admin/partners';

    function __construct(){
        $this->__model    = $this->model('PartnersModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    private function baseData(){
        $this->__data['content']['routeBase'] = $this->routeBase;
        $this->__data['content']['labelOne']  = $this->labelOne;
        $this->__data['content']['types']     = PartnersModel::$types;
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;
        $this->baseData();
        $this->__data['content']['page_name'] = $this->labelMany;
        $this->__data['content']['dataList']  = $this->__model->getLists();
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['msgError']  = Session::flash('msgError');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function add(){
        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Thêm ' . $this->labelOne;
        $this->baseData();
        $this->__data['content']['page_name'] = 'Thêm ' . $this->labelOne;
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $errors = $this->validateInput(null);
        if (!empty($errors)){ $this->flash($errors, 'add'); return; }
        $this->__model->add($this->buildData());
        Session::flash('msg', 'Thêm ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    public function edit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Sửa ' . $this->labelOne;
        $this->baseData();
        $this->__data['content']['page_name'] = 'Sửa ' . $this->labelOne;
        $this->__data['content']['item']      = $item;
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){
        if (empty($this->__model->getDetail($id))){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        $errors = $this->validateInput($id);
        if (!empty($errors)){ $this->flash($errors, 'edit/' . $id); return; }
        $this->__model->edit($this->buildData(), $id);
        Session::flash('msg', 'Cập nhật ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    public function delete($id){
        if (empty($this->__model->getDetail($id))){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        $this->__model->remove($id);
        Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ===== Helper =====

    private function validateInput($id){
        $f = $this->__request->getFields();
        $errors = [];
        $code = isset($f['code']) ? trim($f['code']) : '';
        if ($code === ''){
            $errors['code'] = 'Mã đối tượng không được để trống';
        } else {
            $ex = $this->__model->findByCode($code);
            if (!empty($ex) && ($id === null || $ex['id'] != $id)){
                $errors['code'] = 'Mã này đã tồn tại';
            }
        }
        if (!isset($f['name']) || trim($f['name']) === ''){
            $errors['name'] = 'Tên đối tượng không được để trống';
        }
        return $errors;
    }

    private function buildData(){
        $f = $this->__request->getFields();
        $type = isset($f['type']) && isset(PartnersModel::$types[$f['type']]) ? $f['type'] : 'both';
        return [
            'code'       => trim($f['code']),
            'name'       => trim($f['name']),
            'type'       => $type,
            'tax_code'   => !empty($f['tax_code']) ? trim($f['tax_code']) : null,
            'phone'      => !empty($f['phone']) ? trim($f['phone']) : null,
            'address'    => !empty($f['address']) ? trim($f['address']) : null,
            'sort_order' => isset($f['sort_order']) ? (int) $f['sort_order'] : 0,
            'status'     => !empty($f['status']) ? 1 : 0,
        ];
    }

    private function flash($errors, $back){
        Session::flash('errors', $errors);
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}

<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * CSKH — Nhóm khách hàng (CRUD độc lập).
 */
class Customergroups extends Controller {

    private $__data = [];
    private $__model, $__request, $__response;

    private $routeBase = 'customer-groups';
    private $labelOne  = 'nhóm khách hàng';
    private $labelMany = 'Nhóm khách hàng';
    private $viewDir   = 'admin/customer-groups';

    function __construct(){
        $this->__model    = $this->model('CustomerGroupsModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    private function baseData(){
        $this->__data['content']['routeBase'] = $this->routeBase;
        $this->__data['content']['labelOne']  = $this->labelOne;
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
        $this->__data['content']['item']      = null;
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $this->__request->rules(['name' => 'required|min:1']);
        $this->__request->message(['name.required' => 'Tên nhóm không được để trống', 'name.min' => 'Tên nhóm không được để trống']);
        if (!$this->__request->validate()){ $this->flashErrors('add'); return; }
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
        $this->__request->rules(['name' => 'required|min:1']);
        $this->__request->message(['name.required' => 'Tên nhóm không được để trống', 'name.min' => 'Tên nhóm không được để trống']);
        if (!$this->__request->validate()){ $this->flashErrors('edit/' . $id); return; }
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

    private function buildData(){
        $f = $this->__request->getFields();
        return [
            'name'             => trim($f['name']),
            'discount_percent' => isset($f['discount_percent']) ? (float) preg_replace('/[^\d.]/', '', str_replace(',', '.', (string) $f['discount_percent'])) : 0,
            'note'             => !empty($f['note']) ? trim($f['note']) : null,
            'sort_order'       => isset($f['sort_order']) ? (int) $f['sort_order'] : 0,
            'status'           => !empty($f['status']) ? 1 : 0,
        ];
    }

    private function flashErrors($back){
        Session::flash('errors', $this->__request->error());
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}

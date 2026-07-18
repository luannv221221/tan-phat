<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * KHO — Danh mục kho (phẳng). CRUD độc lập, kế thừa App\core\Controller.
 * Mã kho (code) duy nhất; 1 kho được đánh dấu mặc định.
 */
class Warehouses extends Controller {

    private $__data = [];
    private $__model, $__request, $__response;

    private $routeBase = 'warehouses';
    private $labelOne  = 'kho';
    private $labelMany = 'Danh mục kho';
    private $viewDir   = 'admin/warehouses';

    function __construct(){
        $this->__model    = $this->model('WarehousesModel');
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
        $this->applyRules();
        if (!$this->__request->validate()){
            $this->flashErrors();
            $this->__response->redirect('admin/' . $this->routeBase . '/add');
            return;
        }

        $data = $this->buildData();
        if (!empty($this->__model->findByCode($data['code']))){
            $this->flashOne('code', 'Mã kho này đã tồn tại', 'add');
            return;
        }

        $id = $this->__model->add($data);
        if (!empty($data['is_default'])) $this->__model->clearDefaultExcept($id);

        Session::flash('msg', 'Thêm ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    public function edit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase);
            return;
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
            $this->__response->redirect('admin/' . $this->routeBase);
            return;
        }

        $this->applyRules();
        if (!$this->__request->validate()){
            $this->flashErrors();
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
            return;
        }

        $data = $this->buildData();
        $existing = $this->__model->findByCode($data['code']);
        if (!empty($existing) && $existing['id'] != $id){
            $this->flashOne('code', 'Mã kho này đã thuộc về kho khác', 'edit/' . $id);
            return;
        }

        $this->__model->edit($data, $id);
        if (!empty($data['is_default'])) $this->__model->clearDefaultExcept($id);

        Session::flash('msg', 'Cập nhật ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    public function delete($id){
        if (empty($this->__model->getDetail($id))){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase);
            return;
        }

        // Chặn xoá kho còn tồn / còn chứng từ (khoá ngoại RESTRICT ở phiếu).
        $result = $this->__model->remove($id);
        if ($result === false){
            Session::flash('msgError', 'Không xoá được: kho này đang có tồn hoặc chứng từ liên quan');
        } else {
            Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công');
        }
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ===== Helper =====

    private function applyRules(){
        $this->__request->rules([
            'code' => 'required|min:1',
            'name' => 'required|min:1',
        ]);
        $this->__request->message([
            'code.required' => 'Mã kho không được để trống',
            'code.min'      => 'Mã kho không được để trống',
            'name.required' => 'Tên kho không được để trống',
            'name.min'      => 'Tên kho không được để trống',
        ]);
    }

    private function buildData(){
        $f = $this->__request->getFields();
        return [
            'code'       => trim($f['code']),
            'name'       => trim($f['name']),
            'address'    => !empty($f['address']) ? trim($f['address']) : null,
            'phone'      => !empty($f['phone']) ? trim($f['phone']) : null,
            'is_default' => !empty($f['is_default']) ? 1 : 0,
            'sort_order' => isset($f['sort_order']) ? (int) $f['sort_order'] : 0,
            'status'     => !empty($f['status']) ? 1 : 0,
        ];
    }

    private function flashErrors(){
        Session::flash('errors', $this->__request->error());
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
    }

    private function flashOne($field, $msg, $back){
        Session::flash('errors', [$field => $msg]);
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}
